#!/usr/bin/env node
/**
 * Công cụ thống kê: dự án Resi nào CHƯA CÓ căn hộ nào đang đăng web, và trong số đó dự án nào CÓ / KHÔNG có bài viết dự án.
 * Chỉ ĐỌC (không ghi gì lên Admin). Node 18+, không cần cài thêm gì.
 *
 *   node tools/projects-without-apartments.mjs                       # ghi CSV vào thư mục ./du-an-resi-ket-qua
 *   node tools/projects-without-apartments.mjs --out=D:/ket-qua      # chọn thư mục ghi
 *   node tools/projects-without-apartments.mjs --min-article=300     # bài viết ngắn hơn N ký tự chữ = "bài viết rỗng/tên suông"
 *   node tools/projects-without-apartments.mjs --api=https://admin.propertyplus.com.vn --site=https://propertyplus.com.vn --lang=vi
 *
 * Cách làm (khoảng 70 + 185 + ~vài trăm lần gọi API nhẹ, mất 5-10 phút; chạy tuần tự/2 luồng, có nghỉ giữa các lần gọi để không dồn tải lên Back):
 *   1. Project/_GetProjects            -> toàn bộ dự án (12 dự án/lần).
 *   2. Apartment/_GetApartments        -> toàn bộ căn hộ đang đăng (12 căn/lần, đếm theo projectId). Khung toạ độ rộng vì không gửi khung thì API
 *                                         chỉ trả căn trong "cửa sổ ngầm định" quanh Hà Nội (thiếu ~40 căn).
 *   3. Với dự án có 0 căn ở bước 2: gọi lại Apartment/_GetApartments theo project_id để XÁC NHẬN đúng là 0 (đúng như trang dự án trên web đếm),
 *      rồi Project/Detail để xem "dataSelected" = bài viết dự án và độ dài chữ của nó.
 *
 * Phân loại:
 *   "Có căn hộ"                              - đang có >= 1 căn hộ đăng web.
 *   "Chưa có căn hộ - CÓ bài viết"           - 0 căn hộ, bài viết dự án có nội dung (>= --min-article ký tự chữ).
 *   "Chưa có căn hộ - bài viết rỗng/tên suông" - 0 căn hộ, có bản ghi bài viết nhưng gần như không có chữ (vd dự án Hòa Xá).
 *   "Chưa có căn hộ - không bài viết"        - 0 căn hộ, không có bài viết ("dự án vỏ").
 */
import fs from 'node:fs';
import path from 'node:path';

const arg = (k, d) => { const a = process.argv.find((x) => x.startsWith(`--${k}=`)); return a ? a.slice(k.length + 3) : d; };
const API = arg('api', 'https://admin.propertyplus.com.vn').replace(/\/$/, '');
const SITE = arg('site', 'https://propertyplus.com.vn').replace(/\/$/, '');
const LANG = arg('lang', 'vi');
const OUT = path.resolve(arg('out', 'du-an-resi-ket-qua'));
const MIN_ARTICLE = parseInt(arg('min-article', '300'), 10);
const DELAY = parseInt(arg('delay', '200'), 10);
const CONC = Math.max(1, parseInt(arg('concurrency', '2'), 10));
const WIDE = { ne_lat: 30, ne_lng: 110, sw_lat: 10, sw_lng: 100 };

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const HEAD = { 'X-Requested-With': 'XMLHttpRequest', 'User-Agent': 'pplus-project-report/1.0', Accept: 'application/json' };

async function call(method, urlPath, body) {
  const url = `${API}/${LANG}/${urlPath}`;
  for (let attempt = 1; attempt <= 3; attempt++) {
    try {
      const r = await fetch(url, {
        method, redirect: 'manual', signal: AbortSignal.timeout(60000),
        headers: body ? { ...HEAD, 'Content-Type': 'application/json' } : HEAD,
        body: body ? JSON.stringify(body) : undefined,
      });
      if (r.status >= 300 && r.status < 400) return null; // Back trả 302 về trang đăng nhập = không tìm thấy
      if (r.status === 200) { const j = JSON.parse(await r.text()); return j && j.result ? j.result : null; }
    } catch { /* thử lại */ }
    await sleep(1500 * attempt);
  }
  throw new Error(`Gọi API thất bại 3 lần: ${method} ${urlPath}`);
}

async function pages(urlPath, extra, listKey, label) {
  const items = []; let total = Infinity; let skip = 0;
  while (skip < total) {
    const res = await call('POST', urlPath, { skip, ...extra });
    const list = (res && res[listKey]) || [];
    total = res ? Number(res.total_count || 0) : 0;
    items.push(...list);
    if (!list.length) break;
    skip += 12;
    if ((skip / 12) % 20 === 0 || skip >= total) process.stdout.write(`  ${label}: ${Math.min(skip, total)}/${total}\r`);
    await sleep(DELAY);
  }
  process.stdout.write('\n');
  return { items, total };
}

async function pool(list, n, fn) {
  const out = new Array(list.length); let i = 0;
  await Promise.all(Array.from({ length: n }, async () => { while (i < list.length) { const k = i++; out[k] = await fn(list[k], k); await sleep(DELAY); } }));
  return out;
}

const textLen = (html) => String(html || '').replace(/<(script|style)[\s\S]*?<\/\1>/gi, ' ').replace(/<[^>]*>/g, ' ').replace(/&nbsp;|&#160;/g, ' ').replace(/\s+/g, ' ').trim().length;
const csvCell = (v) => { const s = v == null ? '' : String(v); return /[",\r\n]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s; };
const csv = (rows, cols) => '\uFEFF' + [cols.map((c) => csvCell(c[0])).join(','), ...rows.map((r) => cols.map((c) => csvCell(r[c[1]])).join(','))].join('\r\n') + '\r\n';

(async () => {
  console.log(`API ${API} · ngôn ngữ ${LANG} · ghi vào ${OUT}`);
  console.log('1/3 Lấy danh sách dự án…');
  const proj = await pages('Project/_GetProjects', WIDE, 'listProjects', 'dự án');
  const projects = new Map();
  for (const p of proj.items) projects.set(p.projectId, p);

  console.log('2/3 Lấy toàn bộ căn hộ đang đăng và đếm theo dự án…');
  const apt = await pages('Apartment/_GetApartments', { price_sort_type: 2, apartmentTypeList: '1,2', ...WIDE }, 'listApartments', 'căn hộ');
  const count = new Map();
  for (const a of apt.items) count.set(a.projectId, (count.get(a.projectId) || 0) + 1);
  const unknown = [...count.keys()].filter((id) => !projects.has(id));

  const zero = [...projects.keys()].filter((id) => !count.get(id));
  console.log(`  ${projects.size} dự án, ${apt.items.length} căn hộ đang đăng, ${count.size} dự án có căn; ${zero.length} dự án 0 căn ở lượt quét tổng → xác nhận từng dự án…`);

  console.log('3/3 Xác nhận số căn từng dự án 0 căn + đọc bài viết dự án…');
  let done = 0;
  const detail = await pool(zero, CONC, async (id) => {
    const r = await call('POST', 'Apartment/_GetApartments', { skip: 0, price_sort_type: 2, apartmentTypeList: '1,2', project_id: id });
    const n = r ? Number(r.total_count || 0) : 0;
    let art = null;
    if (n === 0) {
      const d = await call('GET', `Project/Detail?id=${id}`);
      const s = d && d.dataSelected;
      art = s ? { title: s.title || '', len: textLen(s.content) + textLen(s.description) } : { title: '', len: -1 };
    }
    if (++done % 25 === 0 || done === zero.length) process.stdout.write(`  đã xử lý ${done}/${zero.length}\r`);
    return { id, n, art };
  });
  process.stdout.write('\n');
  const info = new Map(detail.map((d) => [d.id, d]));

  const rows = [];
  for (const [id, p] of projects) {
    let cls; let alen = ''; let atitle = ''; let n = count.get(id) || 0;
    if (n === 0) {
      const d = info.get(id);
      if (d && d.n > 0) { n = d.n; }
      else if (d && d.art) {
        alen = d.art.len < 0 ? '' : d.art.len; atitle = d.art.title;
        cls = d.art.len < 0 ? 'Chưa có căn hộ - không bài viết' : d.art.len >= MIN_ARTICLE ? 'Chưa có căn hộ - CÓ bài viết' : 'Chưa có căn hộ - bài viết rỗng/tên suông';
      }
    }
    rows.push({
      cls: n > 0 ? 'Có căn hộ' : cls, id, name: p.projectName, district: p.districtName, province: p.provinceName, address: p.address,
      apts: n, alen, atitle, url: p.urlHerf ? `${SITE}/${LANG}/${String(p.urlHerf).replace(/^\//, '')}` : '',
    });
  }
  const ORDER = { 'Chưa có căn hộ - CÓ bài viết': 0, 'Chưa có căn hộ - bài viết rỗng/tên suông': 1, 'Chưa có căn hộ - không bài viết': 2, 'Có căn hộ': 3 };
  rows.sort((a, b) => ORDER[a.cls] - ORDER[b.cls] || String(a.district).localeCompare(String(b.district), 'vi') || String(a.name).localeCompare(String(b.name), 'vi'));

  const cols = [['Phân loại', 'cls'], ['Mã dự án', 'id'], ['Tên dự án', 'name'], ['Quận/huyện', 'district'], ['Tỉnh/TP', 'province'], ['Địa chỉ', 'address'],
    ['Số căn hộ đang đăng', 'apts'], ['Độ dài bài viết (ký tự chữ)', 'alen'], ['Tiêu đề bài viết', 'atitle'], ['Link trang dự án (web)', 'url']];
  fs.mkdirSync(OUT, { recursive: true });
  fs.writeFileSync(path.join(OUT, 'du-an-resi-tat-ca.csv'), csv(rows, cols));
  const noApt = rows.filter((r) => r.cls !== 'Có căn hộ');
  fs.writeFileSync(path.join(OUT, 'du-an-resi-chua-co-can-ho.csv'), csv(noApt, cols));
  fs.writeFileSync(path.join(OUT, 'du-an-resi-co-bai-viet-chua-co-can-ho.csv'), csv(rows.filter((r) => r.cls === 'Chưa có căn hộ - CÓ bài viết'), cols));

  const by = {}; for (const r of rows) by[r.cls] = (by[r.cls] || 0) + 1;
  console.log('\nKẾT QUẢ');
  for (const k of Object.keys(ORDER)) console.log(`  ${String(by[k] || 0).padStart(4)}  ${k}`);
  console.log(`  ${String(rows.length).padStart(4)}  tổng dự án (${apt.items.length} căn hộ đang đăng)`);
  if (unknown.length) console.log(`  Lưu ý: ${unknown.length} dự án có căn hộ nhưng không nằm trong danh sách dự án (mã: ${unknown.slice(0, 10).join(', ')})`);
  console.log(`Đã ghi 3 file CSV (mở bằng Excel) vào ${OUT}`);
})().catch((e) => { console.error('\nLỖI:', e.message); process.exit(1); });
