# Bộ rà soát SEO / link — `tools/seo-crawl.mjs`

Dev B phụ trách. Chỉ gửi request **đọc** (GET/HEAD), không ghi gì lên site. Không cần cài gì (Node ≥ 18).

## Cách chạy

```bash
# Domain thật: mặc định LẤY MẪU 120 URL từ sitemap (phân tầng theo sitemap con × ngôn ngữ), 2 luồng, nghỉ 300 ms
node tools/seo-crawl.mjs https://propertyplus.com.vn
node tools/seo-crawl.mjs https://propertyplus.com.vn --limit=60 --links --max-links=60 --delay=400

# Danh sách URL ưu tiên (đang có lượt hiển thị trên Google) thay cho sitemap
node tools/seo-crawl.mjs https://propertyplus.com.vn --urls=tools/priority-urls.txt --links

# Bản chạy ở máy (http://localhost:8081): không giới hạn tốc độ, tự đổi tên miền trong sitemap về localhost
node tools/seo-crawl.mjs http://localhost:8081 --limit=0 --links

# Server test (noindex là ĐÚNG)
node tools/seo-crawl.mjs https://loikaka.propertyplus.com.vn --staging
```

Quét **tất cả** trên domain thật phải thêm `--yes-full` (tránh vô tình quét toàn site; server thật chậm, ~4 s/request PHP). Cùng `--seed` thì ra cùng một mẫu, so sánh được giữa các lần chạy. Công cụ **tự dừng** nếu 25 request đầu có > 25 % lỗi 5xx/timeout, và có trần `--max-requests` (900).

Kết quả nằm ở `crawl-reports/<host>-<ngày-giờ>/` (thư mục này đã ignore trong git): `report.md` (tóm tắt + ví dụ), `issues.csv`, `pages.csv`. Thoát mã 1 nếu có lỗi mức `error` (dùng được trong kiểm tra sau deploy).

## Bảng quy tắc

| Mã | Mức | Ý nghĩa | Thường sửa ở đâu |
|---|---|---|---|
| H001 / H002 | error | HTTP ≥ 400 / 5xx hoặc timeout | code (Dev A) hoặc dữ liệu Admin |
| H003 / H005 | warn | chuyển hướng ≥ 2 lần / URL trong sitemap bị chuyển hướng | sitemap, bảng chuyển hướng |
| I001 | error | URL trong sitemap nhưng `noindex` (dự án "vỏ") | sitemap ở Backend / theme |
| I003 / I004 / I006 | error / warn | thiếu canonical / canonical không tự trỏ / canonical sai host (localhost) | theme |
| P001 / P003 | error | thiếu `<title>` / title là tên mặc định của theme | Admin hoặc theme |
| P002 | warn | title quá ngắn/dài (chữ Nhật tính 2 đơn vị, ngưỡng 24–72) | Admin |
| P004 / P005 / P006 | warn | thiếu / quá ngắn-dài / lặp lại y hệt title của meta description | Admin |
| P007 / P008 | warn | title / description trùng nhau giữa các trang | Admin |
| P009 | error / warn | số thẻ H1 ≠ 1 | theme |
| P011 | warn | nội dung chữ quá ít theo loại trang (dự án 900, trang đích 700, blog 800…) | Admin (viết bài) |
| P012 | error | lộ chữ `null` / `undefined` / `NaN` ra nội dung hoặc meta | theme / dữ liệu |
| P013 / P014 | warn | `<html lang>` khác tiền tố; title sai ngôn ngữ (vd /ja không có chữ Nhật) | Admin / theme |
| S001 / S002 | error / warn | JSON-LD hỏng / thiếu BreadcrumbList | theme |
| L001 / L004 / L005 | warn | thiếu hreflang / thiếu x-default / hreflang không trỏ về chính trang | theme |
| U001 | warn / info | đuôi URL vô nghĩa (`-`, `--`) / gạch kép, gạch cuối (URL cũ đã index thì KHÔNG đổi) | Admin (quy trình chuyển hướng trước) |
| U002 | warn | trộn alias quận (`-district` ở /vi, `quan-` ở /en /ja) | Backend/theme |
| U003 | warn | cùng một dự án (id) có nhiều dạng URL trong cùng ngôn ngữ (trùng nội dung) | theme (canonical) |
| U005 / U006 / U007 | error / warn | URL có `//` / URL quá dài / tỉnh `ha-noi` nhưng quận lạ (dự án tỉnh khác) | dữ liệu Backend |
| F001 / F002 | warn | TTFB > 1,5 s (domain thật) / HTML > 700 KB | hạ tầng / theme |
| K001 / K002 / K003 / K005 | error / warn | link nội bộ hỏng / bị chuyển hướng / trỏ trang noindex / trỏ localhost (cần `--links`) | theme / Admin |
| M001 / M002 | error / warn | sitemap con không tải được / lặp `<loc>` | Backend |

Thêm quy tắc: sửa `checkPage()` (theo trang), `checkSet()` (theo tập trang), `checkLinks()` (link nội bộ) trong `tools/seo-crawl.mjs`.

## Lưu ý đã rút ra khi viết

- `sitemap.xml` gốc liệt kê các sitemap con ngay trong khối `<urlset>` (không dùng `<sitemapindex>`); công cụ nhận cả hai dạng.
- Trên localhost, sitemap (lấy từ Admin) vẫn ghi tên miền thật: công cụ tự đổi về địa chỉ đang chạy.
- Trang 404 của theme có title mặc định `propertyplus-resi` (đã noindex): không tính là lỗi riêng, chỉ ghi H001.
- Mẫu nhỏ chỉ để **ước tính**; số "ước tính ~N URL noindex trong sitemap" ngoại suy từ mẫu.
