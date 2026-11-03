# Checklist đưa Resi lên trang chính (propertyplus.com.vn)

Mục tiêu: thay bản Angular bằng theme WordPress Resi **trên cùng domain** mà không mất thứ hạng SEO và không làm người dùng gặp lỗi.

**Trả lời ngắn:** làm đúng 3 bước — (1) cấu hình server theo mục 2 (7 việc, Dev A), (2) chạy `tools/post-deploy-check.mjs` trước và sau khi chuyển, (3) nộp lại sitemap + theo dõi Search Console 2 tuần (mục 4).

---

## 1. Theme đã xử lý sẵn để khớp trang chính

| Hạng mục | Cách xử lý |
|---|---|
| Dạng URL | `/vi`, `/en`, `/ja` + không dấu `/` cuối; `/` = trang chủ tiếng Anh; `/en` → 301 `/`. Gốc sai ngôn ngữ (`/en/cho-thue-can-ho`) → 404 như trang chính |
| URL cũ của Angular | 138 dòng 301 (`Home/GetLogUrlSite`), tự làm mới mỗi ngày bằng cron |
| Biệt thự | URL gốc biệt thự → 301 sang căn hộ tương ứng; biệt thự thật → 410 |
| URL rác trang chính trả 200 "Trang chủ" rỗng (soft-404) | `/vi\|en\|ja/blog`, `/js/about-us`, dịch vụ sai hậu tố → **404 thật** (tốt hơn cho SEO). Search Console sẽ báo "Not found" cho nhóm này — bình thường |
| canonical / hreflang | Mỗi trang có canonical tự trỏ + hreflang vi/en/ja + x-default (= tiếng Anh) |
| Đổi ngôn ngữ | Nút đổi ngôn ngữ = hreflang = trang tương ứng (đã kiểm 186 trang menu × 3 ngôn ngữ) |
| API lỗi | Trả 503 + Retry-After (Google hiểu là tạm thời), không trả trang rỗng/404 |
| Trang phân trang `?paged≥2` | `noindex, follow` |
| robots.txt | Sinh động từ theme, giữ các `Disallow` của trang chính, trỏ `Sitemap: /sitemap.xml` |
| Sitemap | Xem mục 3 |
| Link trong bài viết CMS | Bài SEO quận/dự án/blog do biên tập viên gõ tay có link dạng `http://propertyplus.com.vn/en/cho-thue-can-ho/...` (http + gốc tiếng Việt dưới trang EN = 404 ở cả web chính). Theme viết lại thành URL chuẩn của site đang chạy (trang quận Tây Hồ: 17 link hỏng → 0) |
| Ảnh | WebP 400w/800w sinh ngầm bằng WP-Cron; khi backend có file `_400x300` thật thì dùng luôn |

## 2. Việc phải làm trên server (Dev A) — TRƯỚC khi chuyển domain

1. **Tắt noindex:** trong `wp-config.php` đặt `define('PPLUS_NOINDEX', false);` (server test `loikaka` đặt `true`). Xoá cache trang/Cloudflare sau khi đổi. *Đây là lỗi nghiêm trọng nhất nếu quên: Google sẽ bỏ toàn bộ site.*
2. **WP-Cron chạy thật:** sitemap, ảnh WebP, làm mới cache API, bảng 301 đều dựa vào WP-Cron. Khuyến nghị:
   ```php
   define('DISABLE_WP_CRON', true);   // trong wp-config.php
   ```
   và cron hệ thống mỗi phút: `* * * * * curl -s https://propertyplus.com.vn/wp-cron.php?doing_wp_cron >/dev/null 2>&1`
   (hoặc `wp cron event run --due-now`). Sau khi bật, sitemap tự build lần đầu trong ~20 phút.
3. **PHP:** extension `gd` có WebP (`php -r "var_dump(gd_info()['WebP Support']);"` → `true`), `curl`, `mbstring`; thư mục `wp-content/uploads` **ghi được** (theme tạo `uploads/pplus-img/`).
4. **Không có file vật lý `robots.txt` / `sitemap.xml`** ở gốc web (sẽ đè bản của theme). Xoá nếu có.
5. **Hai Page WordPress phải tồn tại** (Pages → Add New, đúng slug + Page Template): `ve-chung-toi` (template "Ve chung toi") cho `/vi/ve-chung-toi`, `/en/about-us`, `/ja/about-us`; và `lien-he` (template "Lien he") cho `/vi/lien-he`, `/en/contact`, `/ja/contact`. Thiếu thì 6 URL này trả 404 (server test `loikaka` hiện đang thiếu cả hai). Xoá hoặc để nháp Page mẫu của WP (`trang-mau`/`sample-page`) — theme đã loại khỏi sitemap.
6. **Site Address:** Settings → General: cả hai ô là `https://propertyplus.com.vn`. Rồi Settings → Permalinks → Save (theme cũng tự flush một lần ở request đầu, cần DB ghi được).
7. **nginx / Cloudflare:**
   - Nén gzip/brotli cho HTML/CSS/JS; file tĩnh `Cache-Control: max-age ≥ 7 ngày`.
   - HTML để `DYNAMIC` (đừng "Cache Everything" nếu chưa có luật bỏ qua cookie).
   - Cloudflare: bật Always HTTPS; www → 301 về tên miền chính; **tắt** Web Analytics tự chèn (beacon) và JS Detections (mỗi cái thêm 1 request chặn); Email Obfuscation có thể tắt (theme đã bọc `<!--email_off-->`).
   - Purge toàn bộ cache Cloudflare ngay sau khi deploy.

## 3. Sitemap đã đối chiếu với trang chính

Trang chính có 6 file sitemap con (`products, posts, pages, quan, type, villa`), bản chụp từ **25/12/2024**; `/sitemap.xml` của họ là `<urlset>` liệt kê 6 file đó (sai chuẩn sitemapindex).

| Nhóm | Trang chính | Resi (sau khi cron build xong) |
|---|---|---|
| Dự án (`products`) | 2.550 URL (850 dự án × 3 ngôn ngữ, gồm 17 dự án đã gỡ khỏi backend và 26 URL thiếu quận dạng `ha-noi//slug`) | **2.497** (VI 833, EN 832, JA 832) lấy từ `Project/_GetProjects` — danh sách dự án đang hiển thị (838), bỏ dự án thiếu quận; có thêm 6 dự án mới hơn bản chụp 12/2024 |
| Blog (`posts`) | 189 (63 bài × vi/en/ja) | **189 — khớp 100%** |
| Landing quận/loại/khu vực (`quan`, `type`) | VI 16, EN ~186, JA ~91 (gồm 6 biệt thự, 3 alias test, ~37 alias "tiêu đề blog" nội dung mỏng) | ~270 URL (VI 103, EN ~100, JA ~100): link trên menu/footer + **42 landing EN/JA đã kiểm 200** (`inc/sitemap-landing-seed.php`); loại biệt thự, alias lỗi, alias "tiêu đề blog" |
| Trang tĩnh (`pages`) | 28 (gồm 8 URL rác) | các trang thật (trang chủ, về chúng tôi, dịch vụ, chủ nhà, blog…) |
| Biệt thự (`villa`) | 283 | **không liệt kê** (301/410) |
| Chi tiết căn hộ | không có | ~4.200 URL (vi + en) — Resi liệt kê thêm để Google khám phá nhanh hơn |

Đối chiếu bằng số liệu thật (3/11/2026): không có URL hợp lệ nào của sitemap trang chính bị bỏ sót — 71 URL dự án còn thiếu = 42 URL của 16 dự án đã gỡ khỏi backend + 26 URL thiếu quận + 3 URL dự án đổi alias (vẫn có trong sitemap Resi với alias mới, URL cũ vẫn 200), 46 landing thiếu là biệt thự + alias test + alias "tiêu đề blog", 8 trang tĩnh thiếu là URL rác (xem bảng mục 1). Mỗi URL trong sitemap Resi chỉ nằm ở 1 file.

Các file sitemap con cũ (`/sitemaps/sitemap_products.xml`, …) vẫn trả **200** (nội dung tương ứng, `villa` và `type` rỗng hợp lệ) để Search Console không báo lỗi nếu trước đây đã nộp riêng từng file.

## 4. Quy trình chuyển

1. **Server test** (`loikaka`): deploy → `node tools/post-deploy-check.mjs https://loikaka.propertyplus.com.vn --staging` → sửa mọi dòng ✗.
2. **Giờ ít người truy cập:** deploy production (cùng commit đã test) → purge Cloudflare.
3. Ngay lập tức: `node tools/post-deploy-check.mjs https://propertyplus.com.vn --warm` (làm nóng cache bằng ~300 URL trong sitemap; vài 503 lần đầu là bình thường, chạy lại).
4. Sau **20–25 phút** (cron sitemap xong) chạy lại script: số URL trong sitemap phải đạt ngưỡng (khu vực ≥ 250, căn hộ ≥ 1.500, dự án ≥ 2.000, blog ≈ 189) và không còn ✗.
5. **Search Console** (giữ nguyên property cũ, *không* dùng "Change of address"):
   - Sitemaps: nộp lại `https://propertyplus.com.vn/sitemap.xml`; trạng thái phải là "Success".
   - URL Inspection → "Test live URL" cho 5 URL: trang chủ, danh sách, quận, dự án, chi tiết căn hộ → "URL is available to Google", canonical do người dùng khai báo = canonical Google chọn.
6. **2 tuần đầu:** theo dõi báo cáo Pages (404 tăng do URL rác là bình thường, 5xx thì không), Crawl stats (5xx, thời gian phản hồi), Core Web Vitals.
7. **Rollback:** giữ nguyên cấu hình/bản build Angular cũ ở upstream dự phòng; nếu tỷ lệ 5xx > 2% sau khi làm nóng hoặc trang chủ lỗi, chuyển upstream về bản cũ rồi điều tra (quay lại chỉ mất vài giây vì URL không đổi).

## 5. Rủi ro đã biết

| Rủi ro | Tác động | Cách xử lý |
|---|---|---|
| Cache lạnh sau deploy | Vài trang 503 (API backend ~1–5s) | Chạy `--warm`; theme tự thử lại ở lần tải sau |
| Cron không chạy | Sitemap thiếu URL, không có ảnh WebP | Mục 2.2; script cảnh báo "cron chưa xong" |
| Còn noindex / robots.txt vật lý | Mất index toàn site | Script báo ✗ ngay ở mục 1 |
| Tiêu đề trang danh sách/landing | Resi dùng `metaTitle` (SEO) có fallback `title`; trang chính chỉ dùng `title`. Ví dụ đo thật: `/en/rental-apartment/ha-noi` — trang chính "Apartment For Rent In Hanoi", Resi "hanoi apartment for rent" (meta description cũng khác). Trang chủ, quận, dự án, về chúng tôi: trùng 100% | **Quyết định còn mở**: khuyến nghị khớp trang chính (`title`) lúc ra mắt để giữ tiêu đề đã index, đổi sau |
| Thiếu `<h1>` | Chi tiết căn hộ và danh sách blog không có `<h1>` — trang chính cũ cũng không có (đã đối chiếu bằng Chrome), nên không phải hồi quy | Có thể thêm sau (cải thiện SEO), không chặn ra mắt |
| API EN nặng | `en/Project/Detail?id=45` ~2 MB (VI ~200 KB) → lần tải lạnh đầu có thể quá 8s và trả 503 thoáng qua | Chạy `--warm`; về lâu dài nhờ Dev backend giảm payload |
| 5 dự án thiếu quận (`ha-noi//slug-id`, 3 là dự án test) | URL này 301 về danh sách thay vì mở trang dự án | Chấp nhận; sửa ở backend (gán quận) thì tự hết |
| ~37 URL landing "tiêu đề blog" (`/ha-noi/silent-connection`, …) | Vẫn trả 200 như trang chính (danh sách chung dưới H1 của bài blog), không đưa vào sitemap | Muốn gọn: 301/410 sau ra mắt |
| Ảnh chưa có bản 400×300 | Theme tự tạo WebP từ 800×600 | Khi upload `_400x300` thì tự dùng |

## 6. Lệnh hữu ích (WP-CLI)

```bash
wp cron event run --due-now                     # chạy ngay các việc đến hạn (sitemap, WebP…)
wp option delete pplus_sitemap_built_at         # ép build lại sitemap ở lần cron kế
wp rewrite flush                                # nạp lại rewrite rules
node tools/post-deploy-check.mjs https://propertyplus.com.vn --warm
```
