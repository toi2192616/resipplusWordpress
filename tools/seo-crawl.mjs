#!/usr/bin/env node
/**
 * Bộ rà soát SEO/link theo QUY TẮC cho web Resi (Dev B phụ trách). Chỉ GỬI REQUEST ĐỌC (GET/HEAD), không ghi gì lên site. Không cần cài gì (Node >= 18).
 *
 *   node tools/seo-crawl.mjs <https://ten-mien> [tuỳ chọn]
 *
 * Nguồn URL:   mặc định đọc <base>/sitemap.xml (index -> các sitemap con) và LẤY MẪU phân tầng theo (sitemap con x ngôn ngữ).
 *   --urls=file.txt        mỗi dòng 1 URL (hoặc đường dẫn /vi/...); dùng thay sitemap (vd tools/priority-urls.txt)
 *   --limit=120            số URL tối đa (mặc định 120 trên domain thật, 300 trên localhost). --limit=0 = TẤT CẢ (domain thật BẮT BUỘC thêm --yes-full)
 *   --seed=1               hạt giống lấy mẫu (cùng seed = cùng mẫu, so sánh được giữa các lần chạy)
 *   --links                gom link nội bộ của các trang đã tải và kiểm tra mã trạng thái của từng đích (--max-links=250)
 * Lịch sự:     --concurrency=2 --delay=300 (ms giữa 2 request của 1 luồng) --max-requests=900 --timeout=30000; tự DỪNG nếu >25% lỗi 5xx/timeout
 *              (localhost: mặc định concurrency 4, không delay). Tôn trọng Retry-After khi 429/503.
 * Chế độ:      --staging  (server test: noindex là ĐÚNG, không báo lỗi noindex)
 * Kết quả:     --out=thu-muc (mặc định crawl-reports/<host>-<ngày-giờ>): report.md (tóm tắt + ví dụ), issues.csv, pages.csv. Thoát mã 1 nếu có lỗi mức "error".
 *
 * QUY TẮC (id: mức — ý nghĩa) xem docs/CRAWLER.md. Thêm/sửa quy tắc ở hàm checkPage()/checkSet()/checkLinks() bên dưới.
 */

import { writeFileSync, mkdirSync, readFileSync, existsSync } from 'node:fs';
import { join } from 'node:path';

// ------------------------------------------------------------------------------------------------ tham số
const argv = process.argv.slice(2);
const opt = (name, def) => { const a = argv.find((x) => x.startsWith(`--${name}=`)); return a ? a.slice(name.length + 3) : def; };
const flag = (name) => argv.includes(`--${name}`);
const base = (argv.find((a) => !a.startsWith('--')) || '').replace(/\/+$/, '');
if (!/^https?:\/\/[^/]+$/.test(base)) {
  console.error('Dùng: node tools/seo-crawl.mjs https://ten-mien [--urls=file] [--limit=120] [--links] [--staging] [--out=dir]   (xem đầu file)');
  process.exit(2);
}
const HOST = new URL(base).host;
const LOCAL = /^(localhost|127\.0\.0\.1)(:|$)/.test(HOST);
const STAGING = flag('staging');
const LIMIT = Number(opt('limit', LOCAL ? 300 : 120));
if (LIMIT === 0 && !LOCAL && !flag('yes-full')) {
  console.error('--limit=0 (quét TẤT CẢ) trên domain thật phải kèm --yes-full (tránh vô tình quét toàn site). Mặc định lấy mẫu 120 URL.');
  process.exit(2);
}
const CONC = Number(opt('concurrency', 2));
const DELAY = Number(opt('delay', LOCAL ? 0 : 300));
const MAX_REQ = Number(opt('max-requests', 900));
const TIMEOUT = Number(opt('timeout', LOCAL ? 90000 : 30000)); // php -S (local) chạy 1 luồng: chờ lâu hơn
const SEED = Number(opt('seed', 1));
const CHECK_LINKS = flag('links');
const MAX_LINKS = Number(opt('max-links', LOCAL ? 1000 : 250));
const UA = 'Mozilla/5.0 (compatible; pplus-seocrawl/1.0; +internal-audit)';
const stamp = new Date().toISOString().replace(/[-:T]/g, '').slice(0, 12);
const OUT = opt('out', join('crawl-reports', `${HOST.replace(/[:.]/g, '-')}-${stamp}`));

// ------------------------------------------------------------------------------------------------ tiện ích
let requests = 0;
let fails5xx = 0;
let aborted = false;
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
function rng(seed) { let s = seed >>> 0 || 1; return () => { s = (s * 1664525 + 1013904223) >>> 0; return s / 4294967296; }; }
const decodeEnt = (s) => s.replace(/&nbsp;/g, ' ').replace(/&amp;/g, '&').replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"').replace(/&#0?39;|&apos;/g, "'").replace(/&#(\d+);/g, (_, n) => String.fromCodePoint(Number(n)));
const squash = (s) => decodeEnt(s).replace(/\s+/g, ' ').trim();
const hasCJK = (s) => /[぀-ヿ一-鿿]/.test(s);
/** "Độ rộng" hiển thị: chữ CJK tính 2. Title ~ 600px ≈ 60 ký tự Latin ≈ 30 CJK. */
const width = (s) => [...s].reduce((n, ch) => n + (/[぀-ヿ一-鿿＀-￯]/.test(ch) ? 2 : 1), 0);
const csv = (v) => `"${String(v ?? '').replace(/"/g, '""')}"`;
function urlLang(u) { const m = new URL(u).pathname.match(/^\/(vi|en|ja)(\/|$)/); return m ? m[1] : null; }
function normalize(u, from) { try { const x = new URL(u, from); x.hash = ''; return x.href.replace(/\/$/, x.pathname === '/' ? '/' : ''); } catch { return null; } }

/** Tải 1 URL, KHÔNG tự theo chuyển hướng (ghi lại cả chuỗi). */
async function fetchChain(url, { method = 'GET', maxHops = 6 } = {}) {
  const chain = [];
  let cur = url;
  const t0 = Date.now();
  let ttfb = null;
  for (let hop = 0; hop <= maxHops; hop++) {
    if (aborted || requests >= MAX_REQ) return { chain, error: aborted ? 'aborted' : 'max-requests', ttfb, ms: Date.now() - t0 };
    requests++;
    let res;
    try {
      const ctl = new AbortController();
      const timer = setTimeout(() => ctl.abort(), TIMEOUT);
      const s = Date.now();
      res = await fetch(cur, { method, redirect: 'manual', headers: { 'user-agent': UA, accept: 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.5', 'accept-language': 'en' }, signal: ctl.signal });
      ttfb = ttfb ?? Date.now() - s;
      clearTimeout(timer);
    } catch (e) {
      fails5xx++;
      return { chain, error: e.name === 'AbortError' ? 'timeout' : String(e.cause?.code || e.message), ttfb, ms: Date.now() - t0 };
    }
    const status = res.status;
    if (status >= 500) fails5xx++;
    if (status === 429 || status === 503) { const ra = Number(res.headers.get('retry-after')); if (ra > 0 && ra < 30) await sleep(ra * 1000); }
    chain.push({ url: cur, status, location: res.headers.get('location') || '' });
    if (status >= 300 && status < 400 && res.headers.get('location')) {
      try { await res.arrayBuffer(); } catch { /* ignorar */ }
      const next = normalize(res.headers.get('location'), cur);
      if (!next || chain.some((c) => c.url === next)) return { chain, error: 'redirect-loop', ttfb, ms: Date.now() - t0 };
      cur = next;
      continue;
    }
    let html = '';
    if (method === 'GET') { try { html = await res.text(); } catch { /* ignorar */ } } else { try { await res.arrayBuffer(); } catch { /* ignorar */ } }
    return { chain, status, headers: res.headers, html, ttfb, ms: Date.now() - t0, finalUrl: cur };
  }
  return { chain, error: 'too-many-redirects', ttfb, ms: Date.now() - t0 };
}

// ------------------------------------------------------------------------------------------------ phân tích HTML (regex, không phụ thuộc)
function attrs(tag) {
  const o = {};
  for (const m of tag.matchAll(/([a-zA-Z_:-]+)\s*=\s*("([^"]*)"|'([^']*)'|([^\s>]+))/g)) o[m[1].toLowerCase()] = decodeEnt(m[3] ?? m[4] ?? m[5] ?? '');
  return o;
}
function parsePage(html, pageUrl) {
  const p = { title: '', description: null, robots: [], canonical: null, h1: [], htmlLang: '', hreflang: [], jsonld: [], jsonldErrors: 0, links: [], text: '', imgNoAlt: 0, noscriptOnly: false };
  const t = html.match(/<title[^>]*>([\s\S]*?)<\/title>/i);
  p.title = t ? squash(t[1].replace(/<[^>]*>/g, '')) : '';
  for (const m of html.matchAll(/<meta\s[^>]*>/gi)) {
    const a = attrs(m[0]);
    const name = (a.name || a.property || '').toLowerCase();
    if (name === 'description' && p.description === null) p.description = squash(a.content || '');
    if (name === 'robots' || name === 'googlebot') p.robots.push((a.content || '').toLowerCase());
  }
  for (const m of html.matchAll(/<link\s[^>]*>/gi)) {
    const a = attrs(m[0]);
    const rel = (a.rel || '').toLowerCase();
    if (rel === 'canonical' && !p.canonical) p.canonical = a.href || '';
    if (rel === 'alternate' && a.hreflang) p.hreflang.push({ lang: a.hreflang.toLowerCase(), href: a.href || '' });
  }
  p.htmlLang = (html.match(/<html[^>]*\slang=["']?([a-zA-Z-]+)/i) || [])[1] || '';
  for (const m of html.matchAll(/<h1[\s\S]*?<\/h1>/gi)) p.h1.push(squash(m[0].replace(/<[^>]*>/g, ' ')));
  for (const m of html.matchAll(/<script[^>]*type=["']application\/ld\+json["'][^>]*>([\s\S]*?)<\/script>/gi)) {
    try { const j = JSON.parse(m[1]); p.jsonld.push(...[].concat(j['@graph'] || j)); } catch { p.jsonldErrors++; }
  }
  for (const m of html.matchAll(/<a\s[^>]*href=("([^"]*)"|'([^']*)')/gi)) {
    const href = decodeEnt(m[2] ?? m[3] ?? '').trim();
    if (!href || /^(#|mailto:|tel:|javascript:|data:)/i.test(href)) continue;
    const abs = normalize(href, pageUrl);
    if (abs) p.links.push(abs);
  }
  for (const m of html.matchAll(/<img\s[^>]*>/gi)) { const a = attrs(m[0]); if (a.alt === undefined) p.imgNoAlt++; }
  const body = html.replace(/<script[\s\S]*?<\/script>/gi, ' ').replace(/<style[\s\S]*?<\/style>/gi, ' ').replace(/<noscript[\s\S]*?<\/noscript>/gi, ' ').replace(/<svg[\s\S]*?<\/svg>/gi, ' ').replace(/<!--[\s\S]*?-->/g, ' ');
  const main = (body.match(/<main[\s\S]*<\/main>/i) || [body])[0];
  p.text = squash(main.replace(/<[^>]*>/g, ' '));
  return p;
}
function pageType(u) {
  const path = new URL(u).pathname;
  const segs = path.replace(/^\/(vi|en|ja)(?=\/|$)/, '').split('/').filter(Boolean);
  if (segs.length === 0) return 'home';
  if (/^(blogs?|khampha)/i.test(segs[0])) return 'blog';
  if (/^(service|owner|about-us|ve-chung-toi|contact|lien-he|favourite|danh-sach-yeu-thich|easy-living|buildings|street|author)$/i.test(segs[0])) return 'other';
  if (!/^(cho-thue-can-ho|rental-apartment|biet-thu-cho-thue|villa-for-rent)$/.test(segs[0])) return 'other';
  const rest = segs.slice(1);                       // [tinh, ...]
  if (rest.length <= 1) return 'province';
  const last = rest[rest.length - 1];
  if (/^\d+$/.test(last)) return 'apartment';
  if (rest.length >= 3 && /-\d+$/.test(last)) return 'project';
  if (rest.length === 2) return /(^quan-|-district$)/i.test(last) ? 'district' : 'landing';
  return 'other';
}
const projectKey = (u) => { const segs = new URL(u).pathname.split('/').filter(Boolean); const last = segs[segs.length - 1] || ''; const m = last.match(/-(\d+)$/); return m && segs.length >= 5 && !/^\d+$/.test(last) ? m[1] : null; };

// ------------------------------------------------------------------------------------------------ vấn đề
const issues = [];
const add = (rule, severity, url, detail = '', extra = {}) => issues.push({ rule, severity, url, detail, ...extra });

// ------------------------------------------------------------------------------------------------ sitemap + lấy mẫu
/** Trên localhost, sitemap (lấy từ Admin) vẫn ghi tên miền thật: đổi về base để rà soát bản đang chạy local. */
const toBase = (u) => (LOCAL ? u.replace(/^https?:\/\/(www\.)?propertyplus\.com\.vn/i, base) : u);
async function readSitemap(url, depth = 0) {
  const r = await fetchChain(url);
  if (!r.html || r.status !== 200) { add('M001', 'error', url, `sitemap không tải được (${r.status || r.error})`); return []; }
  const locs = [...r.html.matchAll(/<loc>\s*([^<]+?)\s*<\/loc>/g)].map((m) => toBase(decodeEnt(m[1])));
  // Sitemap gốc của site liệt kê các sitemap con trong <urlset> (không dùng <sitemapindex>): cả 2 dạng đều nhận.
  const isIndex = /<sitemapindex/i.test(r.html) || (locs.length > 0 && locs.every((l) => /sitemap[^/]*\.xml(\?|$)/i.test(l)));
  if (isIndex && depth < 2) {
    const out = [];
    for (const child of locs) { const urls = await readSitemap(child, depth + 1); urls.forEach((u) => out.push({ ...u, sitemap: child.replace(/^.*\//, '') })); await sleep(DELAY); }
    return out;
  }
  const seen = new Set();
  return locs.map((u) => { if (seen.has(u)) add('M002', 'warn', u, `lặp <loc> trong ${url.replace(/^.*\//, '')}`); seen.add(u); return { url: u, sitemap: url.replace(/^.*\//, '') }; });
}
function sample(entries, limit) {
  if (!limit || entries.length <= limit) return entries;
  const rand = rng(SEED);
  const groups = new Map();
  for (const e of entries) { const k = `${e.sitemap}|${urlLang(e.url) || '-'}`; (groups.get(k) || groups.set(k, []).get(k)).push(e); }
  const keys = [...groups.keys()].sort();
  const picked = [];
  const per = Math.max(1, Math.floor(limit / keys.length));
  for (const k of keys) { const arr = groups.get(k); for (let i = arr.length - 1; i > 0; i--) { const j = Math.floor(rand() * (i + 1)); [arr[i], arr[j]] = [arr[j], arr[i]]; } picked.push(...arr.slice(0, per)); }
  // bù cho đủ limit từ phần còn lại
  const left = entries.filter((e) => !picked.includes(e));
  while (picked.length < limit && left.length) picked.push(left.splice(Math.floor(rand() * left.length), 1)[0]);
  return picked.slice(0, limit);
}

// ------------------------------------------------------------------------------------------------ quy tắc theo trang
const pages = [];
const HANOI_DISTRICTS = new Set(['ba-dinh', 'hoan-kiem', 'hai-ba-trung', 'dong-da', 'tay-ho', 'cau-giay', 'thanh-xuan', 'hoang-mai', 'long-bien', 'nam-tu-liem', 'bac-tu-liem', 'ha-dong', 'son-tay', 'ba-vi', 'chuong-my', 'dan-phuong', 'dong-anh', 'gia-lam', 'hoai-duc', 'me-linh', 'my-duc', 'phu-xuyen', 'phuc-tho', 'quoc-oai', 'soc-son', 'thach-that', 'thanh-oai', 'thanh-tri', 'thuong-tin', 'ung-hoa']);
const SITE_DEFAULT_TITLES =new Set(['propertyplus-resi', 'propertyplus', 'property plus', '']);
function checkPage(rec) {
  const { url, fetched: f, parsed: p, type, lang, sitemap } = rec;
  const finalStatus = f.status;
  // --- HTTP
  if (f.error) { add('H002', 'error', url, `không tải được: ${f.error}`, { sitemap }); return; }
  if (finalStatus >= 500) add('H002', 'error', url, `HTTP ${finalStatus}`, { sitemap });
  else if (finalStatus >= 400) add('H001', 'error', url, `HTTP ${finalStatus}`, { sitemap });
  if (f.chain.length > 1) {
    const via = f.chain.slice(0, -1).map((c) => `${c.status}→${c.url.replace(base, '')}`).join(' | ');
    add(f.chain.length > 2 ? 'H003' : 'H005', 'warn', url, f.chain.length > 2 ? `chuyển hướng ${f.chain.length - 1} lần: ${via}` : `URL trong danh sách chuyển hướng (${via}); nên dùng URL cuối`, { sitemap });
  }
  if (!p || finalStatus >= 400) {
    if (finalStatus === 404 && SITE_DEFAULT_TITLES.has((p?.title || '').toLowerCase())) { /* trang 404 dùng title mặc định: bỏ qua, chỉ ghi nhận H001 */ }
    return;
  }
  const robots = [...p.robots, (f.headers.get('x-robots-tag') || '').toLowerCase()].join(',');
  const noindex = /noindex/.test(robots);
  // --- khả năng index
  if (noindex && !STAGING && rec.fromSitemap) add('I001', 'error', url, 'URL trong sitemap nhưng noindex', { sitemap });
  if (!noindex && STAGING) add('I002', 'error', url, 'server test mà KHÔNG noindex');
  rec.noindex = noindex;
  if (!noindex) {
    if (!p.canonical) add('I003', 'error', url, 'thiếu canonical', { sitemap });
    else {
      const c = normalize(p.canonical, url);
      const self = normalize(f.finalUrl, url);
      if (!LOCAL && /localhost|127\.0\.0\.1/.test(p.canonical)) add('I006', 'error', url, `canonical trỏ localhost: ${p.canonical}`, { sitemap });
      else if (c && new URL(c).host !== HOST) add('I006', 'warn', url, `canonical khác host: ${p.canonical}`, { sitemap });
      else if (c !== self) add('I004', 'warn', url, `canonical không phải chính nó → ${p.canonical}`, { sitemap });
      rec.canonical = c;
    }
  }
  // --- on-page
  const title = p.title;
  if (!title) add('P001', 'error', url, 'thiếu <title>', { sitemap });
  else {
    if (SITE_DEFAULT_TITLES.has(title.toLowerCase())) add('P003', 'error', url, `title là tên mặc định của theme: "${title}"`, { sitemap });
    const w = width(title);
    if (w < 24) add('P002', 'warn', url, `title quá ngắn (${w} đơn vị): "${title}"`, { sitemap });
    else if (w > 72) add('P002', 'warn', url, `title quá dài (${w} đơn vị): "${title}"`, { sitemap });
  }
  if (p.description === null || p.description === '') add('P004', 'warn', url, 'thiếu meta description', { sitemap });
  else {
    const dw = width(p.description);
    if (dw < 70) add('P005', 'warn', url, `description quá ngắn (${dw} đơn vị): "${p.description}"`, { sitemap });
    else if (dw > 330) add('P005', 'warn', url, `description quá dài (${dw} đơn vị)`, { sitemap });
    if (title && p.description.toLowerCase() === title.toLowerCase()) add('P006', 'warn', url, 'description lặp lại y hệt title', { sitemap });
  }
  if (p.h1.length !== 1 && !['other'].includes(type)) add('P009', p.h1.length === 0 ? 'error' : 'warn', url, `${p.h1.length} thẻ H1`, { sitemap });
  const minText = { project: 900, landing: 700, district: 900, province: 600, apartment: 300, blog: 800, home: 500 }[type];
  if (minText && p.text.length < minText) add('P011', 'warn', url, `nội dung chữ chỉ ${p.text.length} ký tự (ngưỡng ${type}: ${minText})`, { sitemap });
  const leak = (title + ' ' + (p.description || '') + ' ' + p.text).match(/(^|[\s>(:])(null|undefined|NaN|\[object Object\])($|[\s<.,;:)])/);
  if (leak) add('P012', 'error', url, `lộ chữ "${leak[2]}" trong nội dung/meta`, { sitemap });
  if (lang && p.htmlLang && p.htmlLang.slice(0, 2).toLowerCase() !== lang) add('P013', 'warn', url, `<html lang="${p.htmlLang}"> khác tiền tố /${lang}`, { sitemap });
  if (lang === 'ja' && title && !hasCJK(title)) add('P014', 'warn', url, `trang /ja nhưng title không có chữ Nhật: "${title}"`, { sitemap });
  if (lang === 'vi' && title && /[぀-ヿ]/.test(title)) add('P014', 'warn', url, `trang /vi nhưng title có chữ Nhật: "${title}"`, { sitemap });
  if (lang === 'en' && title && /[ạảãàáâậầấẩẫăắằặẳẵẹẻẽèéêệềếểễịỉĩìíọỏõòóôộồốổỗơợờớởỡụủũùúưựừứửữỳỵỷỹđ]/i.test(title)) add('P014', 'warn', url, `trang /en nhưng title có dấu tiếng Việt: "${title}"`, { sitemap });
  // --- dữ liệu có cấu trúc
  if (p.jsonldErrors) add('S001', 'error', url, `${p.jsonldErrors} khối JSON-LD không đọc được`, { sitemap });
  if (!noindex && type !== 'home' && !p.jsonld.some((x) => /BreadcrumbList/.test(String(x['@type'])))) add('S002', 'warn', url, 'không có BreadcrumbList', { sitemap });
  // --- hreflang
  if (!noindex && ['project', 'landing', 'district', 'province', 'home'].includes(type)) {
    if (p.hreflang.length === 0) add('L001', 'warn', url, 'không có hreflang', { sitemap });
    else {
      if (!p.hreflang.some((h) => h.lang === 'x-default')) add('L004', 'warn', url, 'thiếu hreflang x-default', { sitemap });
      const selfLang = lang;
      if (selfLang && !p.hreflang.some((h) => h.lang === selfLang && normalize(h.href, url) === normalize(f.finalUrl, url))) add('L005', 'warn', url, `hreflang ${selfLang} không trỏ về chính trang`, { sitemap });
    }
  }
  // --- URL
  const path = new URL(url).pathname;
  const lastSeg = decodeURIComponent(path.split('/').filter(Boolean).pop() || '');
  if (/^[-_\d]+$/.test(lastSeg) && type !== 'apartment') add('U001', 'warn', url, `đuôi URL vô nghĩa: "${lastSeg}"`, { sitemap });
  else if (/--/.test(lastSeg) || /[-_]$/.test(lastSeg) || /\s|%20/.test(path)) add('U001', 'info', url, `đuôi URL xấu (gạch kép/gạch cuối/khoảng trắng; URL cũ đã index thì KHÔNG đổi): "${lastSeg}"`, { sitemap });
  if (lang === 'vi' && /-district(\/|$)/.test(path)) add('U002', 'warn', url, 'trang /vi dùng alias quận tiếng Anh (-district)', { sitemap });
  if ((lang === 'en' || lang === 'ja') && /\/quan-[^/]+/.test(path)) add('U002', 'warn', url, `trang /${lang} dùng alias quận tiếng Việt (quan-...)`, { sitemap });
  if (['project', 'apartment'].includes(type)) {
    const segs = path.replace(/^\/(vi|en|ja)/, '').split('/').filter(Boolean);
    if (segs[1] === 'ha-noi' && segs[2]) {
      const d = decodeURIComponent(segs[2]).toLowerCase().replace(/^(quan|huyen|thi-xa)-/, '').replace(/-district$/, '');
      if (!HANOI_DISTRICTS.has(d) && !/-\d+$/.test(segs[2])) add('U007', 'warn', url, `tỉnh ha-noi nhưng quận/huyện lạ: "${segs[2]}" (dự án thuộc tỉnh khác?)`, { sitemap });
    }
  }
  if (path.length > 115) add('U006', 'warn', url, `URL dài ${path.length} ký tự`, { sitemap });
  if (/\/\//.test(path)) add('U005', 'error', url, 'URL có "//"', { sitemap });
  // --- hiệu năng (không áp dụng cho localhost)
  if (!LOCAL && f.ttfb > 1500) add('F001', 'warn', url, `TTFB ${f.ttfb} ms`, { sitemap });
  if (f.html.length > 700000) add('F002', 'warn', url, `HTML nặng ${Math.round(f.html.length / 1024)} KB`, { sitemap });
}

// ------------------------------------------------------------------------------------------------ quy tắc theo tập trang
function checkSet(recs) {
  const ok = recs.filter((r) => r.parsed && r.fetched.status === 200 && !r.noindex);
  for (const [field, rule, label] of [['title', 'P007', 'title'], ['description', 'P008', 'description']]) {
    const map = new Map();
    for (const r of ok) { const v = (r.parsed[field] || '').toLowerCase(); if (v) (map.get(v) || map.set(v, []).get(v)).push(r.url); }
    for (const [v, urls] of map) if (urls.length > 1) add(rule, 'warn', urls[0], `${label} trùng ở ${urls.length} URL: "${v.slice(0, 90)}" (vd: ${urls.slice(1, 3).map((u) => u.replace(base, '')).join(' , ')})`);
  }
  // cùng 1 dự án (id) có nhiều dạng URL
  const byProject = new Map();
  for (const r of recs) { const k = projectKey(r.url); if (k) (byProject.get(k) || byProject.set(k, []).get(k)).push(r); }
  for (const [k, list] of byProject) {
    const uniq = new Set(list.map((r) => new URL(r.url).pathname.replace(/^\/(vi|en|ja)/, '').toLowerCase()));
    const sameLang = list.filter((r) => r.lang === list[0].lang);
    if (sameLang.length > 1) add('U003', 'warn', sameLang[0].url, `dự án #${k} có ${sameLang.length} dạng URL cùng ngôn ngữ: ${sameLang.map((r) => new URL(r.url).pathname).join(' | ')}`);
    void uniq;
  }
}

// ------------------------------------------------------------------------------------------------ kiểm tra link nội bộ
async function checkLinks(recs) {
  const inbound = new Map();
  for (const r of recs) if (r.parsed) for (const l of new Set(r.parsed.links)) { if (new URL(l).host !== HOST) continue; (inbound.get(l) || inbound.set(l, []).get(l)).push(r.url); }
  const crawled = new Map(recs.map((r) => [normalize(r.url), r]));
  const targets = [...inbound.entries()].filter(([u]) => !/\.(png|jpe?g|webp|gif|svg|css|js|ico|woff2?|pdf|xml)(\?|$)/i.test(u) && !/\/(wp-(content|includes|json|admin)|feed)\b/.test(u))
    .sort((a, b) => b[1].length - a[1].length).slice(0, MAX_LINKS);
  console.log(`  kiểm tra ${targets.length} link nội bộ (nhiều trang trỏ tới nhất trước)…`);
  let idx = 0;
  const results = new Map();
  await Promise.all(Array.from({ length: CONC }, async () => {
    while (idx < targets.length && !aborted) {
      const [u, from] = targets[idx++];
      const known = crawled.get(u);
      let st;
      if (known?.fetched?.chain) st = { first: known.fetched.chain[0].status, final: known.fetched.status, to: known.fetched.finalUrl, noindex: known.noindex };
      else {
        const r = await fetchChain(u, { method: 'HEAD', maxHops: 4 });
        let rr = r;
        if (!r.error && (r.status === 405 || r.status === 501)) rr = await fetchChain(u, { method: 'GET', maxHops: 4 });
        st = { first: rr.chain[0]?.status ?? 0, final: rr.status ?? 0, to: rr.finalUrl, err: rr.error };
        await sleep(DELAY);
      }
      results.set(u, st);
      const ex = from.slice(0, 2).map((x) => x.replace(base, '')).join(' , ');
      if (st.err || st.final >= 400 || st.final === 0) add('K001', 'error', u, `link nội bộ hỏng (${st.err || st.final}), ${from.length} trang trỏ tới, vd: ${ex}`);
      else if (st.first >= 300 && st.first < 400) add('K002', 'warn', u, `link nội bộ bị chuyển hướng (${st.first}) → ${st.to}; ${from.length} trang trỏ tới, vd: ${ex}`);
      else if (st.noindex && !STAGING) add('K003', 'warn', u, `link nội bộ tới trang noindex; ${from.length} trang trỏ tới, vd: ${ex}`);
      if (/localhost|127\.0\.0\.1/.test(u) && !LOCAL) add('K005', 'error', u, `link trỏ localhost: ${ex}`);
    }
  }));
  return results;
}

// ------------------------------------------------------------------------------------------------ chạy
async function main() {
  console.log(`Rà soát ${base}  (${LOCAL ? 'localhost' : 'domain thật'}${STAGING ? ', staging' : ''}; concurrency ${CONC}, delay ${DELAY}ms, tối đa ${MAX_REQ} request)`);
  let entries;
  const urlsFile = opt('urls', '');
  if (urlsFile) {
    entries = readFileSync(urlsFile, 'utf8').split(/\r?\n/).map((s) => s.trim()).filter((s) => s && !s.startsWith('#')).map((s) => ({ url: s.startsWith('http') ? s : base + (s.startsWith('/') ? s : '/' + s), sitemap: 'file', fromSitemap: false }));
  } else {
    entries = (await readSitemap(`${base}/sitemap.xml`)).map((e) => ({ ...e, fromSitemap: true }));
    console.log(`  sitemap: ${entries.length} URL trong ${new Set(entries.map((e) => e.sitemap)).size} sitemap con`);
  }
  const picked = sample(entries, LIMIT);
  console.log(`  lấy mẫu ${picked.length}${LIMIT === 0 ? ' (tất cả)' : ` / ${entries.length} (seed ${SEED})`}`);
  const recs = [];
  let i = 0;
  const started = Date.now();
  await Promise.all(Array.from({ length: CONC }, async () => {
    while (i < picked.length && !aborted) {
      const e = picked[i++];
      const f = await fetchChain(e.url);
      const parsed = f.html ? parsePage(f.html, f.finalUrl || e.url) : null;
      const rec = { url: e.url, sitemap: e.sitemap, fromSitemap: e.fromSitemap, fetched: f, parsed, type: pageType(e.url), lang: urlLang(e.url) };
      recs.push(rec);
      if (recs.length === 25 && fails5xx / requests > 0.25) { aborted = true; console.error('!! >25% lỗi 5xx/timeout ở 25 request đầu: DỪNG để không làm nặng server.'); }
      if (recs.length % 20 === 0) process.stdout.write(`  …${recs.length}/${picked.length}\r`);
      await sleep(DELAY);
    }
  }));
  console.log(`  đã tải ${recs.length} trang (${requests} request, ${Math.round((Date.now() - started) / 1000)}s)`);
  recs.forEach(checkPage);
  checkSet(recs);
  if (CHECK_LINKS) await checkLinks(recs);

  // ---- báo cáo
  mkdirSync(OUT, { recursive: true });
  const sevRank = { error: 0, warn: 1, info: 2 };
  issues.sort((a, b) => sevRank[a.severity] - sevRank[b.severity] || a.rule.localeCompare(b.rule));
  writeFileSync(join(OUT, 'issues.csv'), '﻿' + ['rule,severity,url,detail,sitemap'].concat(issues.map((x) => [x.rule, x.severity, x.url, x.detail, x.sitemap || ''].map(csv).join(','))).join('\n'));
  writeFileSync(join(OUT, 'pages.csv'), '﻿' + ['url,type,lang,status,final_url,redirects,ttfb_ms,size_kb,noindex,title,description_chars,h1_count,text_chars,canonical,sitemap'].concat(recs.map((r) => [r.url, r.type, r.lang || '', r.fetched.status || r.fetched.error, r.fetched.finalUrl || '', Math.max(0, (r.fetched.chain?.length || 1) - 1), r.fetched.ttfb ?? '', Math.round((r.fetched.html?.length || 0) / 1024), r.noindex ? 'noindex' : '', r.parsed?.title || '', (r.parsed?.description || '').length, r.parsed?.h1.length ?? '', r.parsed?.text.length ?? '', r.canonical || '', r.sitemap].map(csv).join(','))).join('\n'));
  const byRule = new Map();
  for (const x of issues) (byRule.get(x.rule) || byRule.set(x.rule, []).get(x.rule)).push(x);
  const errs = issues.filter((x) => x.severity === 'error').length;
  const lines = [`# Báo cáo rà soát ${base}`, '', `- Thời điểm: ${new Date().toISOString()}  |  Mẫu: ${recs.length} trang${LIMIT === 0 ? ' (tất cả)' : ` / ${entries.length} (seed ${SEED})`}  |  Request: ${requests}`, `- **${errs} lỗi (error)**, ${issues.filter((x) => x.severity === 'warn').length} cảnh báo (warn), ${issues.filter((x) => x.severity === 'info').length} ghi chú (info)`, '', '## Theo quy tắc', '', '| Quy tắc | Mức | Số lượng | Ví dụ |', '|---|---|---|---|'];
  for (const [rule, list] of [...byRule].sort((a, b) => (sevRank[a[1][0].severity] - sevRank[b[1][0].severity]) || b[1].length - a[1].length)) lines.push(`| ${rule} | ${list[0].severity} | ${list.length} | ${String(list[0].detail).replace(/\|/g, '/').slice(0, 110)} — ${list[0].url.replace(base, '')} |`);
  const typeCount = {};
  recs.forEach((r) => { typeCount[r.type] = (typeCount[r.type] || 0) + 1; });
  const fromMap = recs.filter((r) => r.fromSitemap).length;
  const ni = issues.filter((x) => x.rule === 'I001').length;
  lines.splice(4, 0, `- Loại trang trong mẫu: ${Object.entries(typeCount).map(([k, v]) => `${k} ${v}`).join(', ')}`, ...(fromMap ? [`- Sitemap: ${ni}/${fromMap} URL mẫu là noindex (${((100 * ni) / fromMap).toFixed(1)}%) → ước tính ~${Math.round((ni / fromMap) * entries.length)} URL noindex trong tổng ${entries.length} (chỉ là ước tính từ mẫu)`] : []));
  lines.push('', '## Chi tiết (tối đa 8 ví dụ mỗi quy tắc)', '');
  for (const [rule, list] of byRule) { lines.push(`### ${rule} (${list.length})`); for (const x of list.slice(0, 8)) lines.push(`- ${x.url.replace(base, '') || '/'} — ${x.detail}`); lines.push(''); }
  writeFileSync(join(OUT, 'report.md'), lines.join('\n'));
  console.log(`\nKết quả: ${errs} lỗi, ${issues.length - errs} cảnh báo  →  ${OUT}/report.md (issues.csv, pages.csv)`);
  for (const [rule, list] of [...byRule].sort((a, b) => (sevRank[a[1][0].severity] - sevRank[b[1][0].severity]) || b[1].length - a[1].length).slice(0, 14)) console.log(`  ${list[0].severity === 'error' ? '✗' : '!'} ${rule.padEnd(5)} ${String(list.length).padStart(4)}  ${String(list[0].detail).slice(0, 100)}`);
  process.exit(errs ? 1 : 0);
}
main().catch((e) => { console.error(e); process.exit(2); });
