#!/usr/bin/env node
/**
 * Kiểm tra SAU KHI DEPLOY theme Resi lên 1 domain: SEO (index/canonical/hreflang/sitemap/redirect) + trải nghiệm người dùng
 * (trang nào lỗi, đổi ngôn ngữ, tìm kiếm, tốc độ, ảnh WebP). Không cần cài gì (Node >= 18). Chỉ GỬI REQUEST ĐỌC (GET), không ghi gì lên site.
 *
 * Dùng:
 *   node tools/post-deploy-check.mjs https://propertyplus.com.vn              # production: mọi thứ PHẢI cho index
 *   node tools/post-deploy-check.mjs https://loikaka.propertyplus.com.vn --staging   # server test: noindex là ĐÚNG
 *   node tools/post-deploy-check.mjs https://propertyplus.com.vn --warm        # thêm: duyệt thử ~300 URL trong sitemap để "làm nóng" cache
 *   tuỳ chọn: --sample=6 (số URL lấy mẫu mỗi nhóm sitemap)  --warm-max=300
 *
 * Kết quả: ✓ đạt, ! cảnh báo (nên xem), ✗ LỖI (phải sửa trước khi mở cho Google). Thoát mã 1 nếu có lỗi.
 * Chạy 2 lần: lần 1 ngay sau deploy (cache lạnh, có thể có vài 503 do API chậm), lần 2 sau 5-10 phút (cron sitemap/WebP đã chạy).
 */

import { readFileSync } from 'node:fs';

const args = process.argv.slice(2);
const flags = new Set(args.filter((a) => a.startsWith('--')));
const opt = (name, def) => { const a = args.find((x) => x.startsWith(`--${name}=`)); return a ? a.slice(name.length + 3) : def; };
const base = (args.find((a) => !a.startsWith('--')) || '').replace(/\/+$/, '');
if (!/^https?:\/\/[^/]+$/.test(base)) {
  console.error('Dùng: node tools/post-deploy-check.mjs https://ten-mien [--staging] [--warm] [--sample=6] [--warm-max=300]');
  process.exit(2);
}
const STAGING = flags.has('--staging');
const SAMPLE = Number(opt('sample', 6));
const WARM_MAX = Number(opt('warm-max', 300));
const HOST = new URL(base).host;
const IS_HTTPS = base.startsWith('https://');
const LOCAL = /^(localhost|127\.0\.0\.1)(:|$)/.test(HOST); // php -S / môi trường dev không nén và không cache: bỏ cảnh báo tốc độ
const UA = 'Mozilla/5.0 (compatible; pplus-postdeploy/1.0)';

// ----------------------------------------------------------------------------------------------- tiện ích
const C = process.stdout.isTTY ? { g: '\x1b[32m', r: '\x1b[31m', y: '\x1b[33m', d: '\x1b[2m', b: '\x1b[1m', x: '\x1b[0m' } : { g: '', r: '', y: '', d: '', b: '', x: '' };
const tally = { pass: 0, warn: 0, fail: 0 };
const failures = [];
const warnings = [];
function say(level, name, detail = '') {
  const mark = level === 'pass' ? `${C.g}✓${C.x}` : level === 'warn' ? `${C.y}!${C.x}` : `${C.r}✗${C.x}`;
  tally[level]++;
  if (level === 'fail') failures.push(`${name}${detail ? ' — ' + detail : ''}`);
  if (level === 'warn') warnings.push(`${name}${detail ? ' — ' + detail : ''}`);
  if (level !== 'pass' || VERBOSE) console.log(`  ${mark} ${name}${detail ? `  ${C.d}${detail}${C.x}` : ''}`);
}
const VERBOSE = flags.has('--verbose');
/** level = mức khi KHÔNG đạt: 'fail' hoặc 'warn' */
const check = (cond, name, detail = '', level = 'fail') => say(cond ? 'pass' : level, name, cond ? '' : detail);
const section = (t) => console.log(`\n${C.b}== ${t}${C.x}`);

const dec = (s) => { try { return decodeURIComponent(s); } catch { return s; } };
const norm = (p) => (dec(p).replace(/\/+$/, '') || '/').toLowerCase();
const pathOf = (u) => { try { return dec(new URL(u, base).pathname); } catch { return u; } };
const esc = (s) => s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const median = (a) => { const s = [...a].sort((x, y) => x - y); return s.length ? s[Math.floor(s.length / 2)] : 0; };

async function http(url, { follow = false, headers = {}, retry503 = true, method = 'GET', body } = {}) {
  const u = /^https?:/.test(url) ? url : base + encodeURI(url).replace(/%25([0-9A-F]{2})/g, '%$1');
  for (let attempt = 0; attempt < 3; attempt++) {
    const t0 = performance.now();
    try {
      const r = await fetch(u, { method, body, redirect: follow ? 'follow' : 'manual', headers: { 'user-agent': UA, 'accept-encoding': 'gzip, br', ...headers }, signal: AbortSignal.timeout(60000) });
      const type = r.headers.get('content-type') || '';
      const text = /text|json|xml|javascript/.test(type) ? await r.text() : (await r.arrayBuffer(), '');
      const out = { url: u, status: r.status, headers: r.headers, text, type, loc: r.headers.get('location') || '', ms: Math.round(performance.now() - t0) };
      // 503 = API chậm lúc cache lạnh (theme chủ ý trả 503 + Retry-After thay vì trang rỗng): thử lại tối đa 2 lần.
      if (r.status === 503 && retry503 && attempt < 2) { await sleep(4000); continue; }
      return out;
    } catch (e) {
      if (attempt === 2) return { url: u, status: 0, headers: new Headers(), text: '', type: '', loc: '', ms: 0, err: String(e.message || e).slice(0, 80) };
      await sleep(1500);
    }
  }
}
async function pool(items, n, fn) {
  const out = new Array(items.length); let i = 0;
  await Promise.all(Array.from({ length: Math.min(n, items.length) }, async () => { while (i < items.length) { const k = i++; out[k] = await fn(items[k], k); } }));
  return out;
}
let seed = 20261103;
const rnd = () => { seed = (seed * 1664525 + 1013904223) % 4294967296; return seed / 4294967296; };
const pick = (arr, n) => { const a = [...arr]; const o = []; while (a.length && o.length < n) o.push(a.splice(Math.floor(rnd() * a.length), 1)[0]); return o; };

const strip = (h) => h.replace(/<(script|style)[\s\S]*?<\/\1>/gi, ' ');
const attrOf = (tag, name) => { const m = new RegExp(`\\s${name}=(?:"([^"]*)"|'([^']*)')`, 'i').exec(tag); return m ? (m[1] ?? m[2] ?? '') : ''; };
const metaContent = (h, name) => { const m = new RegExp(`<meta\\s[^>]*(?:name|property)=["']${esc(name)}["'][^>]*>`, 'i').exec(h); return m ? attrOf(m[0], 'content') : ''; };
function head(h) {
  const title = (/<title[^>]*>([\s\S]*?)<\/title>/i.exec(h) || [, ''])[1].replace(/\s+/g, ' ').trim();
  const canonical = (/<link\s[^>]*rel=["']canonical["'][^>]*>/i.exec(h) || [''])[0];
  const hreflang = {};
  for (const m of h.matchAll(/<link\s[^>]*rel=["']alternate["'][^>]*>/gi)) { const l = attrOf(m[0], 'hreflang'); if (l) hreflang[l] = attrOf(m[0], 'href'); }
  const robots = [...h.matchAll(/<meta\s[^>]*name=["']robots["'][^>]*>/gi)].map((m) => attrOf(m[0], 'content')).join(', ');
  const lang = (/<html[^>]*\slang=["']([^"']+)["']/i.exec(h) || [, ''])[1];
  const jsonld = [...h.matchAll(/<script[^>]*type=["']application\/ld\+json["'][^>]*>([\s\S]*?)<\/script>/gi)].map((m) => m[1]);
  return { title, canonical: attrOf(canonical, 'href'), hreflang, robots, lang, jsonld, description: metaContent(h, 'description'), ogUrl: metaContent(h, 'og:url') };
}

// ----------------------------------------------------------------------------------------------- 1. robots + chỉ mục
async function checkRobotsAndIndexing() {
  section('1. robots.txt và chặn index');
  const r = await http('/robots.txt');
  check(r.status === 200, 'robots.txt trả 200', `HTTP ${r.status} ${r.err || ''}`);
  const disallowAll = /^\s*Disallow:\s*\/\s*$/mi.test(r.text);
  if (STAGING) check(disallowAll, 'Server test: robots.txt chặn toàn site (đúng cho staging)', 'robots.txt không có "Disallow: /" — Google có thể index bản test', 'warn');
  else check(!disallowAll, 'robots.txt KHÔNG chặn toàn site', 'có "Disallow: /" → Google sẽ không index. Tắt noindex (PPLUS_NOINDEX=false trong wp-config.php) và xoá cache');
  if (!STAGING) { // server test noindex chỉ trả "Disallow: /" (không có Sitemap) — đúng thiết kế
    check(new RegExp(`^Sitemap:\\s*${esc(base)}/sitemap\\.xml\\s*$`, 'mi').test(r.text), 'robots.txt khai báo Sitemap của chính domain này', `nội dung: ${(r.text.match(/^Sitemap:.*$/mi) || ['không có dòng Sitemap'])[0]}. Có file robots.txt vật lý ở gốc site đang đè bản của theme?`);
    check(/Disallow:\s*\*\/admin\/\*/i.test(r.text), 'robots.txt giữ các Disallow của web chính (admin, culture, statusCode…)', 'thiếu "Disallow: */admin/*" — file robots.txt vật lý đang đè bản của theme?', 'warn');
  }

  const home = await http('/');
  const xr = home.headers.get('x-robots-tag') || '';
  const h = head(home.text);
  if (STAGING) {
    check(/noindex/i.test(xr + h.robots), 'Server test đang noindex (đúng)', 'trang chủ KHÔNG noindex — Google có thể index bản test', 'warn');
  } else {
    check(!/noindex/i.test(xr), 'Header X-Robots-Tag không có noindex', `X-Robots-Tag: ${xr}`);
    check(!/noindex/i.test(h.robots), 'Trang chủ không có <meta robots noindex>', `meta robots = "${h.robots}" — còn bật noindex hoặc cache HTML cũ chưa xoá?`);
    // Giữ các thứ web Angular cũ đang có: xác minh Search Console + theo dõi truy cập (inc/tracking.php).
    check(/<meta[^>]+name=["']google-site-verification["'][^>]+content=["']_EwTjlz4bpbpsE8dEwY3bbIye3ejKDjCV1JlDZRfy1U["']/i.test(home.text), 'Có thẻ google-site-verification của web chính (giữ xác minh Search Console)', 'thiếu thẻ — có thể MẤT xác minh quyền sở hữu Search Console. Kiểm inc/tracking.php / hằng PPLUS_GOOGLE_SITE_VERIFICATION');
    check(/googletagmanager\.com\/gtm\.js/.test(home.text) && /GTM-WRVHCF8Q/.test(home.text), 'Có Google Tag Manager GTM-WRVHCF8Q (giữ theo dõi truy cập)', 'thiếu GTM — số liệu truy cập/chuyển đổi dừng. Kiểm hằng PPLUS_GTM_ID và site không ở chế độ noindex');
  }
}

// ----------------------------------------------------------------------------------------------- 2. chuyển hướng / 404
async function checkRedirects() {
  section('2. Chuyển hướng và mã lỗi (giữ SEO của link cũ)');
  const R = [
    // [đường dẫn, mã mong đợi, đích (nếu 301), ghi chú]
    ['/en', 301, '/', '"/en" gộp về "/" (tiếng Anh = trang chủ mặc định)'],
    ['/vi/', 301, '/vi', 'bỏ dấu "/" cuối'],
    ['/vi/cho-thue-can-ho/ha-noi/', 301, '/vi/cho-thue-can-ho/ha-noi', 'bỏ dấu "/" cuối'],
    ['/en/rental-apartment/ha-noi/', 301, '/en/rental-apartment/ha-noi', 'bỏ dấu "/" cuối'],
    ['/en/cho-thue-can-ho', 404, null, 'gốc tiếng Việt dưới /en phải 404 như web chính'],
    ['/vi/rental-apartment', 404, null, 'gốc tiếng Anh dưới /vi phải 404 như web chính'],
    ['/vi/biet-thu-cho-thue/ha-noi/biet-thu-ciputra', 301, '/vi/cho-thue-can-ho/ha-noi/biet-thu-ciputra', 'biệt thự đã bỏ khỏi Resi → về căn hộ tương ứng'],
    ['/en/projects/apartment-for-rent-in-vinhomes-metropolis-hanoi-26', 301, '/en/rental-apartment/ha-noi/quan-ba-dinh/vinhomes-metropolis-1', 'URL dự án cũ (bảng 138 dòng)'],
    ['/vi/du-ans/tong-quan-ve-can-ho-cho-thue-o-lotte-the-residence-ha-noi-59', 301, '/vi/cho-thue-can-ho/ha-noi/quan-ba-dinh/lotte-the-residence-hanoi-80', 'URL dự án cũ (bảng 138 dòng)'],
    ['/ha-noi/quan-tay-ho', 301, '/vi/cho-thue-can-ho/ha-noi/quan-tay-ho', 'URL cũ không tiền tố đã được Google index (bảng chuyển hướng admin)'],
    ['/san-pham/221/Apartments.html', 301, '/vi/cho-thue-can-ho/ha-noi', 'URL .html của web đời đầu, có backlink DR cao'],
    ['/en/apartments/apartment-for-rent-in-ba-dinh-36', 301, '/en/rental-apartment/ha-noi/ba-dinh-district', 'URL landing kiểu cũ có backlink'],
    ['/Error', 301, '/', 'trang /Error có 19 tên miền trỏ về'],
    ['/en/blog', 404, null, 'web chính trả 200 "Trang chủ" rỗng (soft-404) → ở đây phải 404 thật'],
    ['/js/about-us', 404, null, 'URL rác trong sitemap cũ của web chính'],
    ['/vi/trang-khong-ton-tai-xyz', 404, null, 'URL bất kỳ không tồn tại phải 404 (không phải 200 trang rỗng)'],
  ];
  for (const [p, want, to, why] of R) {
    const r = await http(p, { retry503: false });
    if (want === 301) {
      const okLoc = r.loc && norm(pathOf(r.loc)) === norm(to) && (/^\//.test(r.loc) || new URL(r.loc).host === HOST);
      check([301, 308].includes(r.status) && okLoc, `301: ${p}`, `${why} — nhận HTTP ${r.status}${r.loc ? ' → ' + r.loc : ''} (cần → ${to})`);
    } else {
      check(r.status === want || (want === 404 && r.status === 410), `${want}: ${p}`, `${why} — nhận HTTP ${r.status}`);
    }
  }
  if (IS_HTTPS) {
    const r = await http(`http://${HOST}/`, { retry503: false });
    check(r.status === 301 && /^https:/i.test(r.loc), 'http:// → 301 → https://', `HTTP ${r.status} ${r.loc || r.err || ''} (bật "Always Use HTTPS" ở Cloudflare / nginx)`);
    const w = await http(`https://www.${HOST.replace(/^www\./, '')}/`, { retry503: false });
    if (w.status) check([301, 308].includes(w.status) && !/www\./i.test(new URL(w.loc, base).host), 'www → 301 → tên miền chính (không có www)', `HTTP ${w.status} ${w.loc || ''}`, 'warn');
  }
}

// ----------------------------------------------------------------------------------------------- 3. sitemap
const SITEMAP = { sets: {}, all: [] };

// Nguồn sitemap = Admin (inc/sitemap-admin.php, 5/11/2026, theo Dev A): /sitemap.xml và /sitemaps/sitemap_*.xml là PROXY của route sitemap bên Admin,
// không còn tính qua WP-Cron. Giữ đúng dạng web chính cũ: /sitemap.xml là <urlset> liệt kê 6 file con (không phải <sitemapindex>) và mọi <loc> là
// domain chính propertyplus.com.vn (kể cả khi kiểm server test). Nội dung "như Admin": villa (301/410) và vài URL rác trong "pages" là có sẵn ở nguồn.
async function checkSitemapAdmin(idx) {
  say('pass', 'Sitemap lấy từ Admin (X-Pplus-Sitemap-Source: admin) — không phụ thuộc WP-Cron');
  const names = ['products', 'posts', 'pages', 'quan', 'type', 'villa'];
  const listed = [...idx.text.matchAll(/<loc>([^<]+)<\/loc>/g)].map((m) => m[1].replace(/&amp;/g, '&'));
  const missing = names.filter((f) => !listed.some((c) => c.endsWith(`/sitemaps/sitemap_${f}.xml`)));
  check(!missing.length, 'Index của Admin liệt kê đủ 6 file con (products, posts, pages, quan, type, villa)', `thiếu: ${missing.join(', ')}`);
  const min = { products: 2400, posts: 150, pages: 20, quan: 250, type: 250, villa: 0 };
  const sets = {};
  for (const f of names) {
    const r = await http(`/sitemaps/sitemap_${f}.xml`);
    check(r.status === 200 && /<urlset/.test(r.text), `Sitemap con /sitemaps/sitemap_${f}.xml trả 200 (từ Admin)`, `HTTP ${r.status} cache=${r.headers.get('x-pplus-sitemap-cache') || '?'} — theme không lấy được từ Admin (Admin lỗi hoặc chặn IP server?)`);
    sets[f] = [...r.text.matchAll(/<loc>([^<]+)<\/loc>/g)].map((m) => dec(m[1].replace(/&amp;/g, '&').replace(/&#0?39;/g, "'")));
    check(sets[f].length >= min[f], `sitemap_${f}.xml: ${sets[f].length} URL`, `dự kiến ≥ ${min[f]} — Admin trả ít hơn bình thường`, 'warn');
    const lm = /<lastmod>([^<]+)<\/lastmod>/.exec(r.text);
    if (lm && f !== 'villa') check(Date.now() - Date.parse(lm[1]) < 14 * 864e5, `sitemap_${f}.xml được Admin làm mới trong 14 ngày (lastmod ${lm[1].slice(0, 10)})`, 'lastmod quá cũ — Admin không còn tự làm mới sitemap', 'warn');
  }
  console.log(`  ${C.d}số URL: ${names.map((f) => `${f}=${sets[f].length}`).join('  ')}${C.x}`);
  const all = Object.values(sets).flat();
  const hostBad = all.filter((u) => { try { return new URL(u).host !== 'propertyplus.com.vn'; } catch { return true; } });
  check(!hostBad.length, 'Mọi URL trong sitemap Admin thuộc domain chính propertyplus.com.vn', `${hostBad.length} URL sai domain, vd ${hostBad[0]}`);

  // Cùng cấu trúc với nhánh theme để các mục sau (lấy mẫu trang) dùng lại: dự án = products, blog = posts, landing = quan + type.
  SITEMAP.sets = { pplusprojects: sets.products, pplusblogs: sets.posts, pplusareas: [...new Set([...sets.quan, ...sets.type])] };
  SITEMAP.all = Object.values(SITEMAP.sets).flat();
  const bad = SITEMAP.all.filter((u) => { const p = pathOf(u); return (p !== '/' && /\/$/.test(p)) || !/^\/(vi|en|ja)(\/|$)/.test(p) && p !== '/' || /\/\//.test(p) || /\/(biet-thu-cho-thue|villa-for-rent)(\/|$)/.test(p); });
  check(!bad.length, 'URL trong products/posts/quan/type đều ở dạng chuẩn (có /vi|/en|/ja, không "/" cuối, không biệt thự)', `${bad.length} URL sai dạng, vd ${bad.slice(0, 2).join(' , ')}`, 'warn');

  // "pages" (28 URL) có sẵn vài URL rác của web chính cũ (/js/about-us, /vi|en|ja/blog...) mà Resi cố ý trả 404 (web cũ trả 200 rỗng = soft-404): chỉ cảnh báo, sửa ở Admin.
  const pagesBad = [];
  await pool(sets.pages, 3, async (u) => { const r = await http(pathOf(u)); if (r.status !== 200) pagesBad.push(`${r.status} ${pathOf(u)}`); });
  check(!pagesBad.length, 'Mọi URL trong sitemap_pages.xml trả 200', `${pagesBad.length} URL không 200 (URL rác có sẵn ở nguồn Admin, nên xoá khỏi Admin): ${pagesBad.slice(0, 4).join(' ; ')}`, 'warn');
  // villa: Resi 301 sang căn hộ tương ứng (hoặc 410 với biệt thự đã gỡ) — chỉ cần KHÔNG 5xx.
  const villaRows = await pool(pick(sets.villa, 5), 3, async (u) => ({ u, r: await http(pathOf(u), { retry503: false }) }));
  const villaBad = villaRows.filter(({ r }) => r.status >= 500 || r.status === 0);
  check(!villaBad.length, `${villaRows.length} URL biệt thự lấy mẫu trả 301/410 như thiết kế (không 5xx)`, villaBad.map(({ u, r }) => `${r.status} ${pathOf(u)}`).join(' ; '));

  const picks = Object.entries(SITEMAP.sets).flatMap(([k, v]) => pick(v, SAMPLE).map((u) => [k, u]));
  const rows = await pool(picks, 3, async ([k, u]) => { const r = await http(pathOf(u)); return { k, u, r }; });
  let bad200 = 0;
  for (const { k, u, r } of rows) {
    const h = head(r.text);
    const selfCanon = !h.canonical || norm(pathOf(h.canonical)) === norm(pathOf(u));
    const noindex = /noindex/i.test(h.robots + (r.headers.get('x-robots-tag') || ''));
    if (r.status !== 200 || !selfCanon || (noindex && !STAGING)) { bad200++; say('fail', `Mẫu sitemap [${k}] ${pathOf(u).slice(0, 90)}`, `HTTP ${r.status}${r.loc ? ' → ' + r.loc : ''}${!selfCanon ? ' | canonical khác URL: ' + pathOf(h.canonical) : ''}${noindex && !STAGING ? ' | noindex' : ''}`); }
  }
  if (!bad200) say('pass', `${rows.length} URL lấy mẫu từ sitemap Admin đều 200, index được, canonical tự trỏ`);
}

async function checkSitemap() {
  section('3. Sitemap');
  const idx = await http('/sitemap.xml');
  check(idx.status === 200, '/sitemap.xml trả 200 (trực tiếp, không redirect)', `HTTP ${idx.status} ${idx.loc || idx.err || ''}`);
  if ((idx.headers.get('x-pplus-sitemap-source') || '') === 'admin') { await checkSitemapAdmin(idx); return; }
  check(/<sitemapindex/.test(idx.text), '/sitemap.xml là <sitemapindex> hợp lệ', 'không phải sitemapindex (web chính cũ dùng <urlset> sai chuẩn)');
  const children = [...idx.text.matchAll(/<loc>([^<]+)<\/loc>/g)].map((m) => m[1].replace(/&amp;/g, '&'));
  check(children.length >= 4, `Index liệt kê ${children.length} file con`, 'quá ít file — cron sitemap chưa chạy? (xem mục "WP-Cron" trong docs/GO_LIVE_CHECKLIST.md)', 'warn');
  const wrongHost = children.filter((c) => new URL(c).host !== HOST);
  check(!wrongHost.length, 'Mọi file con nằm trên đúng domain', `sai domain: ${wrongHost.slice(0, 2).join(', ')} — cấu hình "Site Address" (siteurl/home) của WordPress đang sai`);

  for (const c of children) {
    const r = await http(new URL(c).pathname);
    const name = (/wp-sitemap-([a-z-]+?)(?:-\d+)?\.xml/.exec(c) || [, c])[1];
    check(r.status === 200 && /<urlset/.test(r.text), `File con ${new URL(c).pathname}`, `HTTP ${r.status}`);
    const urls = [...r.text.matchAll(/<loc>([^<]+)<\/loc>/g)].map((m) => dec(m[1].replace(/&amp;/g, '&').replace(/&#0?39;/g, "'")));
    (SITEMAP.sets[name] = SITEMAP.sets[name] || []).push(...urls);
  }
  SITEMAP.all = Object.values(SITEMAP.sets).flat();
  const n = (k) => (SITEMAP.sets[k] || []).length;
  console.log(`  ${C.d}số URL: ${Object.entries(SITEMAP.sets).map(([k, v]) => `${k}=${v.length}`).join('  ')}  (tổng ${SITEMAP.all.length})${C.x}`);
  // Ngưỡng theo dữ liệu thật 3/11/2026: areas ~300, căn hộ ~4.2k (2 ngôn ngữ), dự án ~2.5k (3 ngôn ngữ), blog ~190 (3 ngôn ngữ)
  check(n('pplusareas') >= 250, `Nhóm khu vực/landing/trang tĩnh: ${n('pplusareas')} URL`, 'dự kiến ≥ 250 — cron chưa build xong?', 'warn');
  check(n('pplusapartments') >= 1500, `Nhóm căn hộ: ${n('pplusapartments')} URL`, 'dự kiến ≥ 1500 — cron sitemap chưa chạy xong (mất ~15-25 phút sau lần chạy đầu; cần WP-Cron hoạt động)', 'warn');
  check(n('pplusprojects') >= 2000, `Nhóm dự án: ${n('pplusprojects')} URL`, 'dự kiến ≥ 2000 (≈ 838 dự án × 3 ngôn ngữ) — cron chưa xong?', 'warn');
  check(n('pplusblogs') >= 150, `Nhóm blog: ${n('pplusblogs')} URL`, 'dự kiến ≈ 190 (63 bài × 3 ngôn ngữ) — cron chưa xong?', 'warn');

  const dup = SITEMAP.all.length - new Set(SITEMAP.all.map(norm)).size;
  check(dup <= 5, 'Không có URL trùng giữa các file sitemap (≤ 5 trang tĩnh trùng là chấp nhận được)', `${dup} URL trùng`, 'warn');
  const bad = SITEMAP.all.filter((u) => { const p = pathOf(u); return (p !== '/' && /\/$/.test(p)) || !/^\/(vi|en|ja)(\/|$)/.test(p) && p !== '/' || /\/\//.test(p) || /\/(biet-thu-cho-thue|villa-for-rent)(\/|$)/.test(p); });
  check(!bad.length, 'URL trong sitemap đều ở dạng chuẩn (có /vi|/en|/ja, không "/" cuối, không biệt thự)', `${bad.length} URL sai dạng, vd ${bad.slice(0, 2).join(' , ')}`);
  const hostBad = SITEMAP.all.filter((u) => { try { return new URL(u).host !== HOST; } catch { return true; } });
  check(!hostBad.length, 'Mọi URL trong sitemap thuộc đúng domain', `${hostBad.length} URL sai domain, vd ${hostBad[0]}`);

  // 6 file sitemap con CŨ của web chính — nếu đã nộp riêng trong Search Console thì không được 404
  for (const f of ['products', 'posts', 'pages', 'quan', 'type', 'villa']) {
    const r = await http(`/sitemaps/sitemap_${f}.xml`);
    check(r.status === 200 && /<urlset/.test(r.text), `Sitemap con cũ /sitemaps/sitemap_${f}.xml còn trả 200`, `HTTP ${r.status} — flush rewrite rules (Settings → Permalinks → Save) rồi thử lại`, 'warn');
  }

  // Lấy mẫu: mỗi URL trong sitemap phải trả ĐÚNG 200 (không redirect), index được, canonical tự trỏ
  const groups = Object.entries(SITEMAP.sets).filter(([k]) => k !== 'posts-page');
  const picks = groups.flatMap(([k, v]) => pick(v, SAMPLE).map((u) => [k, u]));
  const rows = await pool(picks, 3, async ([k, u]) => { const r = await http(pathOf(u)); return { k, u, r }; });
  let bad200 = 0;
  for (const { k, u, r } of rows) {
    const h = head(r.text);
    const selfCanon = !h.canonical || norm(pathOf(h.canonical)) === norm(pathOf(u));
    const noindex = /noindex/i.test(h.robots + (r.headers.get('x-robots-tag') || ''));
    if (r.status !== 200 || !selfCanon || (noindex && !STAGING)) { bad200++; say('fail', `Mẫu sitemap [${k}] ${pathOf(u).slice(0, 90)}`, `HTTP ${r.status}${r.loc ? ' → ' + r.loc : ''}${!selfCanon ? ' | canonical khác URL: ' + pathOf(h.canonical) : ''}${noindex && !STAGING ? ' | noindex' : ''}`); }
  }
  if (!bad200) say('pass', `${rows.length} URL lấy mẫu từ sitemap đều 200, index được, canonical tự trỏ`);
}

// ----------------------------------------------------------------------------------------------- 3b. URL đang có traffic / backlink
async function checkPriority() {
  section('3b. URL đang có traffic hoặc backlink (tools/priority-urls.txt) — không được 404/503');
  let list = [];
  try { list = readFileSync(new URL('./priority-urls.txt', import.meta.url), 'utf8').split(/\r?\n/).map((x) => x.trim()).filter((x) => x && !x.startsWith('#')); } catch { say('warn', 'Không đọc được tools/priority-urls.txt'); return; }
  const rows = await pool(list, 3, async (p) => {
    let r = await http(p, { retry503: true });
    let hops = 0;
    while ([301, 302, 308].includes(r.status) && r.loc && hops < 3) { r = await http(pathOf(r.loc) + (new URL(r.loc, base).search || ''), { retry503: true }); hops++; }
    return { p, r, hops };
  });
  const bad = rows.filter(({ r }) => r.status !== 200);
  for (const { p, r } of bad) say('fail', `URL ưu tiên ${p.slice(0, 95)}`, `HTTP ${r.status}${r.err ? ' ' + r.err : ''} — đang có traffic/backlink mà không mở được`);
  const slow = rows.filter(({ r }) => r.status === 200 && r.ms > 8000);
  for (const { p, r } of slow) say('warn', `URL ưu tiên chậm ${p.slice(0, 90)}`, `${r.ms}ms — payload API nặng, cần làm nóng cache (--warm) ngay sau deploy`);
  if (!bad.length) say('pass', `${rows.length} URL ưu tiên đều mở được (200, có thể qua 301)`);
}

// ----------------------------------------------------------------------------------------------- 4. kiểm tra trang theo mẫu × ngôn ngữ
const LANGS = ['en', 'vi', 'ja'];
const STATIC_PAGES = [
  ['trang chủ', { en: '/', vi: '/vi', ja: '/ja' }],
  ['danh sách căn hộ', { en: '/en/rental-apartment/ha-noi', vi: '/vi/cho-thue-can-ho/ha-noi', ja: '/ja/rental-apartment/ha-noi' }],
  ['quận', { en: '/en/rental-apartment/ha-noi/tay-ho-district', vi: '/vi/cho-thue-can-ho/ha-noi/quan-tay-ho', ja: '/ja/rental-apartment/ha-noi/tay-ho-district' }],
  ['về chúng tôi', { en: '/en/about-us', vi: '/vi/ve-chung-toi', ja: '/ja/about-us' }],
  ['liên hệ', { en: '/en/contact', vi: '/vi/lien-he', ja: '/ja/contact' }],
  ['blog (danh sách)', { en: '/en/blogs', vi: '/vi/blogs', ja: '/ja/blogs' }],
  ['dịch vụ', { en: '/en/service/the-management-of-property-plus-1', vi: '/vi/service/dich-vu-quan-ly-cua-property-plus-1', ja: '/ja/service/the-management-of-property-plus-1' }],
  ['chủ nhà', { en: '/en/owner/owner-benefit', vi: '/vi/owner/loi-ich-chu-nha', ja: '/ja/owner/owner-benefit' }],
  ['tiện ích (easy-living)', { en: '/en/easy-living/24', vi: '/vi/easy-living/145', ja: '/ja/easy-living/24' }, 'optional'],
  ['chi tiết yêu thích (noindex)', { en: '/en/favourite/favourite-detail', vi: '/vi/favourite/favourite-detail', ja: '/ja/favourite/favourite-detail' }, 'noindex'],
  ['yêu thích (noindex)', { en: '/en/danh-sach-yeu-thich', vi: '/vi/danh-sach-yeu-thich', ja: '/ja/danh-sach-yeu-thich' }, 'noindex'],
];
const langOfPath = (p) => (/^\/(vi|en|ja)(\/|$)/.exec(p) || [, 'en'])[1];

const TTFB = [];
async function auditPage(label, path, { expectNoindex = false, optional = false, hreflangTargets = false } = {}) {
  const lang = langOfPath(path);
  const r = await http(path);
  const tag = `${label} [${lang}] ${path.slice(0, 80)}`;
  if (optional && r.status === 404) return null; // URL mẫu có thể đã bị gỡ khỏi backend: bỏ qua, không tính lỗi
  if (r.status !== 200) { say('fail', tag, `HTTP ${r.status}${r.loc ? ' → ' + r.loc : ''}${r.err ? ' ' + r.err : ''}`); return null; }
  const h = head(r.text);
  const body = strip(r.text);
  const problems = [];
  const warns = [];
  if (h.lang !== lang) problems.push(`<html lang="${h.lang}" (cần "${lang}")`);
  const h1s = (body.match(/<h1[\s>]/gi) || []).length;
  // Trang chính (Angular) cũng KHÔNG có <h1> ở chi tiết căn hộ và danh sách blog (đối chiếu bằng Chrome 3/11/2026) → chỉ cảnh báo, không phải lỗi hồi quy.
  if (!expectNoindex && h1s !== 1) warns.push(h1s === 0 ? 'không có thẻ <h1> (SEO nên có; trang chính cũ cũng thiếu ở chi tiết căn hộ và danh sách blog)' : `${h1s} thẻ <h1>`);
  if (h.title.length < 3) problems.push(`title quá ngắn: "${h.title}"`);
  else if (h.title.length < (lang === 'ja' ? 4 : 10)) warns.push(`title ngắn: "${h.title}"`);
  if (h.title.length > 90) warns.push(`title dài ${h.title.length} ký tự`);
  if (!expectNoindex && h.description.length < 40) warns.push(`meta description ngắn/thiếu (${h.description.length})`);
  if (!h.canonical) problems.push('thiếu canonical');
  else {
    if (new URL(h.canonical, base).host !== HOST) problems.push(`canonical sai domain: ${h.canonical}`);
    else if (norm(pathOf(h.canonical)) !== norm(path)) problems.push(`canonical khác URL: ${pathOf(h.canonical)}`);
  }
  for (const l of [...LANGS, 'x-default']) {
    if (!h.hreflang[l]) problems.push(`thiếu hreflang ${l}`);
    else if (new URL(h.hreflang[l], base).host !== HOST) problems.push(`hreflang ${l} sai domain`);
  }
  if (h.hreflang[lang] && norm(pathOf(h.hreflang[lang])) !== norm(path)) problems.push(`hreflang ${lang} không trỏ về chính trang này (${pathOf(h.hreflang[lang])})`);
  const noindex = /noindex/i.test(h.robots + (r.headers.get('x-robots-tag') || ''));
  if (expectNoindex) { if (!noindex) problems.push('trang này phải noindex'); } else if (noindex && !STAGING) problems.push(`noindex (${h.robots})`);
  for (const j of h.jsonld) { try { JSON.parse(j); } catch { problems.push('JSON-LD không parse được'); break; } }
  if (!expectNoindex && !h.jsonld.length) warns.push('không có JSON-LD');
  if (!expectNoindex && h.ogUrl && norm(pathOf(h.ogUrl)) !== norm(path)) warns.push(`og:url khác URL: ${pathOf(h.ogUrl)}`);
  // rò rỉ môi trường test / localhost vào HTML
  const leak = (r.text.match(/https?:\/\/(?:localhost|127\.0\.0\.1|[a-z0-9-]*loikaka[a-z0-9.-]*|[a-z0-9-]+\.test)(?::\d+)?/gi) || []).filter((x) => !x.includes(HOST));
  if (leak.length) problems.push(`HTML còn link môi trường khác: ${[...new Set(leak)].slice(0, 2).join(', ')}`);
  if (IS_HTTPS) { // mixed content = tài nguyên con (ảnh/script/iframe/css/canonical…) nạp qua http:// ; link <a> http:// chỉ tốn thêm 1 lần 301
    const mixed = [...body.matchAll(/<(?:img|script|iframe|source|video|audio|link)\b[^>]*?\s(?:src|href)=["'](http:\/\/[^"']+)["']/gi)].map((m) => m[1]).filter((u) => !/schema\.org|w3\.org|xmlns/.test(u));
    if (mixed.length) problems.push(`${mixed.length} tài nguyên http:// (mixed content), vd ${mixed[0].slice(0, 60)}`);
  }
  // link nội bộ: gốc sai ngôn ngữ (vd /en/cho-thue-can-ho/... → 404) hoặc còn trỏ http:// / domain cũ
  const own = [...body.matchAll(/<a\b[^>]*?\shref=["']([^"'#]+)["']/gi)].map((m) => m[1].replace(/&amp;/g, '&'))
    .map((u) => { try { return new URL(u, base); } catch { return null; } })
    .filter((x) => x && (x.host === HOST || /(^|\.)propertyplus\.com\.vn$/.test(x.host)));
  const wrongRoot = own.filter((x) => /^\/(en|ja)\/(cho-thue-can-ho|ve-chung-toi|lien-he|biet-thu-cho-thue|so-sanh-van-phong)(\/|$)/.test(dec(x.pathname)) || /^\/vi\/(rental-apartment|about-us|contact|villa-for-rent|office-compare)(\/|$)/.test(dec(x.pathname)));
  if (wrongRoot.length) problems.push(`${wrongRoot.length} link nội bộ có gốc sai ngôn ngữ (sẽ 404), vd ${dec(wrongRoot[0].pathname).slice(0, 80)}`);
  const insecure = own.filter((x) => IS_HTTPS && x.protocol === 'http:');
  if (insecure.length) warns.push(`${insecure.length} link nội bộ còn http:// (thêm 1 lần 301), vd ${insecure[0].pathname.slice(0, 60)}`);
  // nút đổi ngôn ngữ phải khớp hreflang
  const sw = {};
  for (const m of body.matchAll(/data-lang="(vi|en|ja)">\s*<a\s[^>]*href="([^"]+)"/g)) sw[m[1]] = m[2];
  for (const l of LANGS.filter((x) => x !== lang)) {
    if (sw[l] && h.hreflang[l] && norm(pathOf(sw[l])) !== norm(pathOf(h.hreflang[l]))) problems.push(`nút đổi sang ${l} (${pathOf(sw[l])}) lệch hreflang (${pathOf(h.hreflang[l])})`);
  }
  if (r.text.length > 600000) warns.push(`HTML nặng ${(r.text.length / 1024) | 0} KB`);
  const enc = r.headers.get('content-encoding') || '';
  if (!LOCAL && !/gzip|br|zstd/.test(enc)) warns.push('HTML không nén (gzip/brotli)');
  if (hreflangTargets) {
    for (const l of LANGS.filter((x) => x !== lang)) {
      if (!h.hreflang[l]) continue;
      const t = await http(pathOf(h.hreflang[l]));
      if (t.status !== 200) problems.push(`trang đích đổi sang ${l} (${pathOf(h.hreflang[l])}) trả HTTP ${t.status}`);
    }
  }
  TTFB.push({ label, lang, path, ms: r.ms, cf: r.headers.get('cf-cache-status') || '' });
  if (problems.length) say('fail', tag, problems.join(' | ')); else if (warns.length) say('warn', tag, warns.join(' | ')); else say('pass', tag);
  return { r, h, body };
}

const PAGE_CACHE = {};
async function checkPages() {
  section('4. Trang theo mẫu × 3 ngôn ngữ (200, lang, h1, title, canonical, hreflang, JSON-LD, đổi ngôn ngữ, rò môi trường)');
  for (const [label, byLang, mode] of STATIC_PAGES) {
    for (const l of LANGS) {
      const res = await auditPage(label, byLang[l], { expectNoindex: mode === 'noindex', optional: mode === 'optional', hreflangTargets: l === 'en' });
      PAGE_CACHE[`${label}|${l}`] = res;
    }
  }
  // Trang động lấy từ sitemap: căn hộ, bài blog, landing — mỗi loại 1 trang/ngôn ngữ
  const byKind = [
    ['chi tiết căn hộ', (SITEMAP.sets.pplusapartments || [])],
    ['bài blog', (SITEMAP.sets.pplusblogs || [])],
    ['landing', (SITEMAP.sets.pplusareas || []).filter((u) => /^\/(vi|en|ja)\/[^/]+\/ha-noi\/[^/]+$/.test(pathOf(u)))],
    ['dự án (sitemap)', (SITEMAP.sets.pplusprojects || [])],
  ];
  for (const [label, urls] of byKind) {
    for (const l of LANGS) {
      const mine = urls.filter((u) => langOfPath(pathOf(u)) === l);
      if (!mine.length) { if (urls.length && !(label === 'chi tiết căn hộ' && l === 'ja')) say('warn', `${label} [${l}]`, 'sitemap chưa có URL mẫu ngôn ngữ này'); continue; } // căn hộ JA không có trong sitemap (chủ ý)
      const res = await auditPage(label, pathOf(pick(mine, 1)[0]), { hreflangTargets: l === 'en' });
      PAGE_CACHE[`${label}|${l}`] = res;
    }
  }
}

// ----------------------------------------------------------------------------------------------- 5. tìm kiếm
async function checkSearch() {
  section('5. Tìm kiếm (gợi ý dự án qua REST)');
  for (const [lang, q] of [['vi', 'kim ma'], ['en', 'somerset'], ['ja', 'lotte']]) {
    const r = await http(`/wp-json/pplus/v1/search-group?q=${encodeURIComponent(q)}&lang=${lang}`);
    let n = -1; try { n = JSON.parse(r.text).projects.length; } catch { /* để n = -1 */ }
    check(r.status === 200 && n > 0, `Gợi ý dự án "${q}" (${lang}) trả ${n} kết quả`, `HTTP ${r.status}, ${n} kết quả — REST bị chặn (plugin bảo mật/Cloudflare WAF) hoặc API backend lỗi`);
  }
  const q = await http(`/vi/cho-thue-can-ho/ha-noi?q=${encodeURIComponent('Somerset')}`);
  check(q.status === 200, 'Trang danh sách mở được với tham số tìm kiếm ?q=', `HTTP ${q.status}`);

  // URL có ký tự không phải ASCII (alias tiếng Nhật của trang landing). Server IIS + PHP FastCGI từng đổi chúng thành "?" (404/301 sai) — xem docs/GO_LIVE_CHECKLIST.md mục "URL tiếng Nhật".
  // (SITEMAP.sets giữ URL đã giải mã = chữ Nhật thật; bản 4/11 chỉ lọc "%XX" nên khối này chưa từng chạy → lọc cả hai dạng.)
  const nonAscii = (SITEMAP.sets.pplusareas || []).filter((u) => /[^\x00-\x7F]|%[89A-F][0-9A-F]/i.test(u)).slice(0, 6);
  // 5/11/2026: chỉ kiểm mã 200 là KHÔNG ĐỦ — loikaka từng trả 200 cho 12/14 URL tiếng Nhật nhưng hiện SAI trang (danh sách cha hoặc toà nhà khác;
  // canonical vẫn đúng vì lấy từ URL yêu cầu nên cũng không bắt được). Nên so thêm: title khác trang cha, và không trùng title giữa các URL khác nhau.
  const titleOf = (html) => ((/<title>([^<]*)<\/title>/i.exec(html || '') || [, ''])[1] || '').trim();
  const seenTitles = new Map();
  const parentTitles = new Map();
  for (const u of nonAscii) {
    const p = pathOf(u);
    const r = await http(p);
    check(r.status === 200, `URL tiếng Nhật mở được: ${p.slice(0, 70)}`, `HTTP ${r.status}${r.loc ? " → " + r.loc.slice(0, 80) : ""} — alias không phải ASCII bị xử lý sai ở server: xem docs/GO_LIVE_CHECKLIST.md mục "URL tiếng Nhật"`);
    if (r.status !== 200) continue;
    const title = titleOf(r.text);
    const parent = p.replace(/\/[^/]*$/, '');
    if (!parentTitles.has(parent)) parentTitles.set(parent, titleOf((await http(parent)).text));
    check(title !== '' && title !== parentTitles.get(parent), `URL tiếng Nhật hiện đúng trang riêng (không phải trang cha): ${p.slice(0, 60)}`, `title "${title.slice(0, 40)}" trùng trang cha ${parent} — alias bị cắt/xoá nên hiện danh sách thay vì trang này (xem inc/language-route.php: PATH_INFO giải mã UTF-8)`);
    if (seenTitles.has(title)) check(false, `Mỗi URL tiếng Nhật một nội dung riêng`, `${p.slice(0, 60)} và ${seenTitles.get(title).slice(0, 60)} cùng title "${title.slice(0, 40)}" — một trong hai đang hiện sai trang`);
    else seenTitles.set(title, p);
  }

  // Form "Gửi yêu cầu tư vấn" (căn hộ, dự án, trang Liên hệ, modal footer): route REST phải tồn tại và biến JS phải được in ra.
  // (4/11/2026: cả hai từng thiếu → bấm Gửi không làm gì mà không ai biết.) KHÔNG gửi form thật: chỉ gọi route với dữ liệu rỗng — validate xong là trả lỗi, không gọi API backend.
  const adv = await http('/wp-json/pplus/v1/advise', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ OwnerEmail: '', OwnerMobile: '', lang: 'en' }) });
  let advMsg = ''; try { advMsg = JSON.parse(adv.text).message || ''; } catch { /* để rỗng */ }
  check(adv.status === 200 && /Email or Phone/i.test(advMsg), 'Route form tư vấn /wp-json/pplus/v1/advise hoạt động (validate dữ liệu rỗng)', `HTTP ${adv.status} ${advMsg || adv.text.slice(0, 80)} — route không đăng ký hoặc bị chặn: mọi form "Gửi yêu cầu tư vấn" sẽ không gửi được`);
  const home = await http('/vi');
  check(/var PPLUS_ADVISE_URL\s*=/.test(home.text) && /var PPLUS_ADVISE_CONFIRM\s*=/.test(home.text), 'Biến JS của form tư vấn (PPLUS_ADVISE_URL/CONFIRM) có trong trang', 'thiếu — bấm Gửi sẽ báo "PPLUS_ADVISE_CONFIRM is not defined" và không gửi được');

  // Trang cảm ơn = URL đo chuyển đổi của GTM (5/11/2026): web chính chuyển sang /Apartment/IssueLeaseApartmentSuccses sau mỗi form thành công và GTM bắn
  // GA4 "Đăng ký form thành công" + Google Ads theo URL đó. Resi giữ UX không chuyển trang nhưng nạp URL này trong iframe ẩn (inc/form-success-route.php).
  const ty = await http('/Apartment/IssueLeaseApartmentSuccses');
  check(ty.status === 200 && /noindex/i.test((ty.headers.get('x-robots-tag') || '') + ty.text.slice(0, 8000)), 'Trang cảm ơn /Apartment/IssueLeaseApartmentSuccses mở được và noindex (URL đo chuyển đổi GTM/Google Ads)', `HTTP ${ty.status} — thiếu trang cảm ơn nên mất dữ liệu chuyển đổi form, hoặc trang không noindex`);
  const conv = /var PPLUS_CONVERSION_URL\s*=\s*"([^"]*)"/.exec(home.text);
  if (!STAGING) {
    check(!!conv && /IssueLeaseApartmentSuccses/.test(conv[1]), 'Điểm đo chuyển đổi form (PPLUS_CONVERSION_URL) được in ra khi GTM bật', 'rỗng/thiếu — form thành công sẽ không bắn sự kiện chuyển đổi (GTM đang tắt hoặc site còn noindex?)');
  }
}

// ----------------------------------------------------------------------------------------------- 6. tốc độ + cache + ảnh
async function checkPerformance() {
  section('6. Tốc độ, nén, cache tài nguyên tĩnh, ảnh WebP');
  const warmRows = [];
  for (const [label, byLang] of STATIC_PAGES.filter(([l]) => ['trang chủ', 'danh sách căn hộ', 'blog (danh sách)'].includes(l))) {
    const p = byLang.vi;
    const r2 = await http(p, { retry503: false });
    if (r2.status !== 200) continue;
    const cold = TTFB.find((x) => x.path === p)?.ms ?? r2.ms;
    warmRows.push({ label, cold, warm: r2.ms, cf: r2.headers.get('cf-cache-status') || '' });
  }
  for (const w of warmRows) console.log(`  ${C.d}${w.label}: lần 1 ${w.cold}ms, lần 2 ${w.warm}ms ${w.cf ? '(cf-cache-status: ' + w.cf + ')' : ''}${C.x}`);
  const slow = warmRows.filter((w) => w.warm > 2500);
  check(!slow.length, 'Tải lần 2 (đã có cache) dưới 2.5s', slow.map((s) => `${s.label} ${s.warm}ms`).join(', ') + ' — cache API/transient không hoạt động? object cache? PHP-FPM chậm?', 'warn');
  const med = median(TTFB.map((x) => x.ms));
  console.log(`  ${C.d}thời gian trả HTML trung vị trên ${TTFB.length} trang vừa kiểm: ${med}ms${C.x}`);

  const home = PAGE_CACHE['trang chủ|en'];
  if (home) {
    const assets = [...new Set([...home.r.text.matchAll(/(?:href|src)=["']([^"']+\.(?:css|js)(?:\?[^"']*)?)["']/gi)].map((m) => m[1]))]
      .map((u) => new URL(u.replace(/&amp;/g, '&'), base)).filter((u) => u.host === HOST).slice(0, 4);
    for (const u of assets) {
      const r = await http(u.pathname + u.search);
      const cc = r.headers.get('cache-control') || '';
      const age = Number((/max-age=(\d+)/.exec(cc) || [])[1] || 0);
      const enc = r.headers.get('content-encoding') || '';
      check(r.status === 200, `Tài nguyên ${u.pathname.slice(-50)}`, `HTTP ${r.status}`);
      if (!LOCAL) {
        check(age >= 604800, `  cache-control ≥ 7 ngày (${u.pathname.split('/').pop()})`, `cache-control: "${cc}" — cấu hình nginx/Cloudflare cache cho file tĩnh`, 'warn');
        check(/gzip|br|zstd/.test(enc), `  nén gzip/brotli (${u.pathname.split('/').pop()})`, 'file CSS/JS chưa nén', 'warn');
      }
    }
  }
  // ảnh WebP trên trang chi tiết căn hộ
  const det = PAGE_CACHE['chi tiết căn hộ|vi'] || PAGE_CACHE['chi tiết căn hộ|en'];
  if (det) {
    const webp = [...det.r.text.matchAll(/(?:src|srcset|href)=["']([^"']*pplus-img[^"'\s,]+)/gi)].map((m) => m[1]);
    if (!webp.length) say('warn', 'Trang chi tiết chưa dùng ảnh WebP của theme (uploads/pplus-img)', 'ảnh WebP tạo ngầm bằng WP-Cron sau lần xem đầu — chạy lại script sau vài phút; nếu vẫn không có: PHP thiếu GD-WebP hoặc thư mục wp-content/uploads không ghi được');
    else {
      const r = await http(new URL(webp[0].replace(/&amp;/g, '&'), base).pathname);
      check(r.status === 200 && /image\/webp/.test(r.type), `Ảnh WebP ${webp[0].split('/').pop()} mở được`, `HTTP ${r.status} ${r.type}`);
    }
  }
}

// ----------------------------------------------------------------------------------------------- 7. làm nóng cache
async function warm() {
  section(`7. Làm nóng cache: duyệt tối đa ${WARM_MAX} URL trong sitemap (3 luồng)`);
  const order = ['pplusareas', 'pplusprojects', 'pplusapartments', 'pplusblogs'];
  const per = Math.ceil(WARM_MAX / order.length);
  const urls = order.flatMap((k) => pick(SITEMAP.sets[k] || [], per)).slice(0, WARM_MAX);
  const t0 = Date.now();
  const rs = await pool(urls, 3, async (u) => { const r = await http(pathOf(u), { retry503: false }); return { u, status: r.status, ms: r.ms }; });
  const by = (f) => rs.filter((x) => f(x.status)).length;
  const e5 = by((s) => s >= 500 || s === 0), e4 = by((s) => s >= 400 && s < 500), r3 = by((s) => s >= 300 && s < 400);
  console.log(`  ${C.d}${rs.length} URL trong ${((Date.now() - t0) / 1000) | 0}s — 2xx ${by((s) => s >= 200 && s < 300)}, 3xx ${r3}, 4xx ${e4}, 5xx/lỗi ${e5}; thời gian trung vị ${median(rs.map((x) => x.ms))}ms${C.x}`);
  check(e5 / Math.max(1, rs.length) <= 0.02, 'Tỷ lệ 5xx khi duyệt thử ≤ 2%', `${e5}/${rs.length}: ${rs.filter((x) => x.status >= 500 || x.status === 0).slice(0, 3).map((x) => pathOf(x.u)).join(' , ')}`);
  check(e4 + r3 === 0, 'URL trong sitemap không có 3xx/4xx', `${e4 + r3} URL: ${rs.filter((x) => x.status >= 300 && x.status < 500).slice(0, 3).map((x) => `${x.status} ${pathOf(x.u)}`).join(' , ')}`);
}

// ----------------------------------------------------------------------------------------------- chạy
console.log(`${C.b}Kiểm tra sau deploy: ${base}${C.x}  ${STAGING ? '(chế độ server test: noindex là đúng)' : '(chế độ production: phải cho Google index)'}`);
const t0 = Date.now();
await checkRobotsAndIndexing();
await checkRedirects();
await checkSitemap();
await checkPriority();
await checkPages();
await checkSearch();
await checkPerformance();
if (flags.has('--warm')) await warm();

console.log(`\n${C.b}KẾT QUẢ${C.x}: ${C.g}${tally.pass} đạt${C.x}, ${C.y}${tally.warn} cảnh báo${C.x}, ${C.r}${tally.fail} lỗi${C.x}  (${((Date.now() - t0) / 1000) | 0}s)`);
if (failures.length) { console.log(`\n${C.r}LỖI cần sửa trước khi mở cho Google:${C.x}`); failures.forEach((f) => console.log('  ✗ ' + f)); }
if (warnings.length) { console.log(`\n${C.y}Cảnh báo (xem xét):${C.x}`); warnings.forEach((f) => console.log('  ! ' + f)); }
process.exit(failures.length ? 1 : 0);
