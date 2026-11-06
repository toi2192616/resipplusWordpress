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
2. **WP-Cron chạy thật:** ảnh WebP, làm mới cache API, bảng 301 dựa vào WP-Cron (sitemap thì KHÔNG còn: từ 5/11/2026 lấy thẳng từ Admin, xem mục 3). Khuyến nghị:
   ```php
   define('DISABLE_WP_CRON', true);   // trong wp-config.php
   ```
   và cron hệ thống mỗi phút: `* * * * * curl -s https://propertyplus.com.vn/wp-cron.php?doing_wp_cron >/dev/null 2>&1`
   (hoặc `wp cron event run --due-now`). Sau khi bật, ảnh WebP và bảng 301 tự chạy.
3. **PHP:** extension `gd` có WebP (`php -r "var_dump(gd_info()['WebP Support']);"` → `true`), `curl`, `mbstring`; thư mục `wp-content/uploads` **ghi được** (theme tạo `uploads/pplus-img/`).
4. **Không có file vật lý `robots.txt` / `sitemap.xml`** ở gốc web (sẽ đè bản của theme). Xoá nếu có.
5. **Hai Page WordPress phải tồn tại** (Pages → Add New, đúng slug + Page Template): `ve-chung-toi` (template "Ve chung toi") cho `/vi/ve-chung-toi`, `/en/about-us`, `/ja/about-us`; và `lien-he` (template "Lien he") cho `/vi/lien-he`, `/en/contact`, `/ja/contact`. Thiếu thì 6 URL này trả 404 (server test `loikaka` hiện đang thiếu cả hai). **Trang Liên hệ đã được làm lại ngày 4/11/2026** (trước đó là bản copy của web Văn phòng, hiện thô vì không có CSS): có banner, icon, form gửi thật (`pplus/v1/advise`) và bản đồ Resi dùng chung với footer (`pplus_resi_map_embed_url()`, đổi ngôn ngữ theo trang). Trang này **không có trong menu/sitemap của web chính** (mồ côi) — nay có link ở tiêu đề "Liên hệ" của footer và nằm trong sitemap. Sau khi deploy: mở `/vi/lien-he`, `/en/contact`, `/ja/contact`, kiểm tra bản đồ ghim đúng "PropertyPlus Viet Nam_Apartment for rent in Ha Noi" và thử gửi 1 form thật (script không gửi form). Xoá hoặc để nháp Page mẫu của WP (`trang-mau`/`sample-page`) — theme đã loại khỏi sitemap.
6. **Site Address:** Settings → General: cả hai ô là `https://propertyplus.com.vn`. Rồi Settings → Permalinks → Save (theme cũng tự flush một lần ở request đầu, cần DB ghi được).
7. **nginx / Cloudflare:**
   - Nén gzip/brotli cho HTML/CSS/JS; file tĩnh `Cache-Control: max-age ≥ 7 ngày`.
   - HTML để `DYNAMIC` (đừng "Cache Everything" nếu chưa có luật bỏ qua cookie).
   - Cloudflare: bật Always HTTPS; www → 301 về tên miền chính; **tắt** Web Analytics tự chèn (beacon) và JS Detections (mỗi cái thêm 1 request chặn); Email Obfuscation có thể tắt (theme đã bọc `<!--email_off-->`).
   - Purge toàn bộ cache Cloudflare ngay sau khi deploy.

### 2b. Những thứ web Angular cũ đang có mà Resi phải giữ (phát hiện 4/11/2026)
- **Xác minh Search Console:** trang chính có `<meta name="google-site-verification" content="_EwTj…">` (tài khoản quản trị hiện chỉ là "chủ sở hữu được ủy quyền", không biết chủ gốc xác minh bằng thẻ, GTM hay DNS). Theme đã xuất lại thẻ này (`inc/tracking.php`); **hỏi chủ sở hữu gốc** xem có dùng DNS không và **không xoá** bản ghi DNS/Tag nào.
- **Google Tag Manager `GTM-WRVHCF8Q`:** toàn bộ theo dõi truy cập/chuyển đổi. Theme đã xuất lại; chỉ nạp khi site cho phép index (server test không gửi dữ liệu thử). Tắt/đổi ID: `define('PPLUS_GTM_ID', '')` trong wp-config.
- **Kiểm sau deploy:** `post-deploy-check.mjs` báo ✗ nếu thiếu 1 trong 2; vào Tag Assistant/GTM Preview xem thẻ GA4 có bắn.
- **Biểu mẫu:** thử gửi 1 biểu mẫu "Tư vấn" và 1 biểu mẫu "Đăng ký cho thuê" thật (qua `Favourite/IssueLeaseApartment`) và kiểm người nhận có nhận được — bộ kiểm tự động không gửi biểu mẫu.
- **Đo chuyển đổi form (phát hiện 5/11/2026):** web Angular sau MỖI form thành công (tư vấn, dịch vụ, cho thuê…) chuyển hẳn sang `/Apartment/IssueLeaseApartmentSuccses`; container GTM bắn sự kiện GA4 "Đăng ký form thành công" + các tag chuyển đổi Google Ads khi URL đó được xem (điều kiện: Page Path chứa `IssueLeaseApartmentSuccses`), ngoài ra còn bắt click `tel:`, `zalo.me`, `m.me`, `line.me`, `mailto:`. Resi giữ quyết định không chuyển trang nên nạp trang cảm ơn trong **iframe ẩn** ngay sau khi gửi thành công (`inc/form-success-route.php` + `pplusTrackFormSuccess()` trong main.js) — GTM trong iframe tự bắn đúng trigger cũ, không phải sửa GTM. Chỉ nạp khi GTM bật (server test không gửi dữ liệu giả). **Sau khi lên production:** gửi 1 form thật (nội dung "TEST"), mở GA4 → Realtime/DebugView hoặc Tag Assistant và xác nhận thấy sự kiện "Đăng ký form thành công" + chuyển đổi Ads; `post-deploy-check` kiểm trang cảm ơn 200/noindex và biến `PPLUS_CONVERSION_URL`. Đã nối lại 2 form bị vô hiệu (`onsubmit="return false"`): chi tiết blog và chi tiết dự án.

## 3. Sitemap

**Nguồn sitemap = Admin (5/11/2026, theo Dev A: Admin đã có route sitemap, không tự sinh).** `inc/sitemap-admin.php` PROXY `GET {api-base}/sitemap.xml` và `/sitemaps/sitemap_{products,posts,pages,quan,type,villa}.xml` của Admin ra đúng các URL cũ trên DOMAIN CHÍNH (`/sitemap.xml`, `/sitemaps/sitemap_*.xml`). Không trỏ `robots.txt` sang domain Admin và không redirect: Search Console đã nộp từng file `/sitemaps/sitemap_*.xml` của propertyplus.com.vn (Google đọc thành công, vd products 2.550 URL; `/sitemap.xml` được đọc là 6 "trang" vì web cũ dùng `<urlset>` thay vì `<sitemapindex>` - giữ nguyên như Admin), đổi URL/host sẽ làm đứt liên tục. Không phụ thuộc WP-Cron. Cache transient: dưới 30 phút dùng ngay, quá hạn thì lấy lại từ Admin, Admin lỗi thì phục vụ bản cũ (tối đa 7 ngày), chưa có bản nào thì 503 + `Retry-After`. Header chẩn đoán: `X-Pplus-Sitemap-Source: admin`, `X-Pplus-Sitemap-Cache: hit|miss|stale|none`. Bật lại bộ sinh cũ của theme: `define("PPLUS_SITEMAP_SOURCE", "theme");` trong `wp-config.php`.

Khác biệt cần biết so với bộ sinh cũ: sitemap Admin KHÔNG có trang chi tiết căn hộ (bộ sinh cũ thêm ~4.200 URL, web gốc không có) và giữ nguyên vài URL "không sạch" ở nguồn: `villa` (292 URL, Resi 301 sang căn hộ hoặc 410), 24 URL dạng `ha-noi//slug` (dự án thiếu quận, Resi 301 về trang cha), 8 URL rác trong `pages` (`/js/about-us`, `/vi|en|ja/blog`... Resi cố ý 404). Sửa ở Admin thì sitemap tự sạch; script kiểm tra chỉ cảnh báo các mục này. Số liệu Admin 5/11/2026: products 2.562, posts 189, pages 28, quan 319, type 301, villa 292 (file tĩnh trên domain chính cũ: 2.550 / 189 / 28 / 310 / 292 / 283).

### Đối chiếu với trang chính (bản chụp 25/12/2024 và bộ sinh cũ của theme, nay không dùng)

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
4. Sau **vài phút** (làm nóng cache xong) chạy lại script: sitemap lấy từ Admin phải đạt ngưỡng (products ≥ 2.400, posts ≥ 150, pages ≥ 20, quan ≥ 250, type ≥ 250) và không còn ✗.
5. **Search Console** (giữ nguyên property cũ, *không* dùng "Change of address"):
   - Sitemaps: nộp lại `https://propertyplus.com.vn/sitemap.xml`; trạng thái phải là "Success".
   - URL Inspection → "Test live URL" cho 5 URL: trang chủ, danh sách, quận, dự án, chi tiết căn hộ → "URL is available to Google", canonical do người dùng khai báo = canonical Google chọn.
6. **2 tuần đầu:** theo dõi báo cáo Pages (404 tăng do URL rác là bình thường, 5xx thì không), Crawl stats (5xx, thời gian phản hồi), Core Web Vitals.
7. **Rollback:** giữ nguyên cấu hình/bản build Angular cũ ở upstream dự phòng; nếu tỷ lệ 5xx > 2% sau khi làm nóng hoặc trang chủ lỗi, chuyển upstream về bản cũ rồi điều tra (quay lại chỉ mất vài giây vì URL không đổi).

## 5. Rủi ro đã biết

| Rủi ro | Tác động | Cách xử lý |
|---|---|---|
| Cache lạnh sau deploy | Vài trang 503 (API backend ~1–5s) | Chạy `--warm`; theme tự thử lại ở lần tải sau |
| Cron không chạy | Không có ảnh WebP, bảng 301 không làm mới (sitemap không bị ảnh hưởng) | Mục 2.2 |
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

## Phát hiện khi rà soát loikaka ngày 4/11/2026 (bản effc3c1) — cần Dev A xử lý

Lệnh chạy lại: `node tools/post-deploy-check.mjs https://loikaka.propertyplus.com.vn --staging` (kết quả lần đó: 75 đạt, 9 cảnh báo, 11 lỗi).

1. **Hai Page `ve-chung-toi` và `lien-he` chưa có** → 6 URL (`/vi/ve-chung-toi`, `/en/about-us`, `/ja/about-us`, `/vi/lien-he`, `/en/contact`, `/ja/contact`) trả 404. Tạo trong WP admin (Pages → Add New, đúng slug + Page Template "Ve chung toi" / "Lien he"). Link "Liên hệ" ở footer chỉ hiện khi Page `lien-he` đã tồn tại.
2. **URL tiếng Nhật bị server làm hỏng (13/14 URL tiếng Nhật trong sitemap lỗi; local chạy tốt).** Triệu chứng: `/ja/rental-apartment/ha-noi/<alias tiếng Nhật>` trả 404, hoặc 301 sang một URL toàn dấu `?` (`.../ha-noi???????????`); canonical bị cắt ngay trước ký tự tiếng Nhật đầu tiên. Header phản hồi có `x-powered-by: ASP.NET` + PHP → server là **IIS + PHP FastCGI**, nơi `REQUEST_URI` bị đổi sang bảng mã ANSI nên mọi ký tự không phải ASCII thành `?`; phần sau dấu `?` đầu tiên bị coi là query string. Ảnh hưởng: toàn bộ trang landing tiếng Nhật (Search Console ghi nhận Nhật Bản ~98 click/3 tháng).
   - **Cập nhật 5/11/2026 – nguyên nhân thật đã tìm ra và sửa trong theme.** Sau khi Dev A deploy bản mới, loikaka vẫn sai cả 14/14 URL tiếng Nhật trong sitemap: 2 URL trả 404 (alias bắt đầu bằng chữ Latin), 12 URL trả **200 nhưng hiện SAI trang** (danh sách Hà Nội hoặc toà nhà khác); canonical vẫn đúng nên không phát hiện được qua canonical. Nguyên nhân: WordPress khớp route bằng `PATH_INFO` nếu có (máy local cung cấp bản đã giải mã UTF-8). Khi `PATH_INFO` rỗng/hỏng (IIS) WP dùng `REQUEST_URI` còn dạng `%E3%83…`, alias vào query var vẫn là chuỗi `%E3…`, rồi `sanitize_text_field()` (inc/reference-index-route.php) **xoá mọi `%XX`** → alias thuần tiếng Nhật thành rỗng (hiện danh sách Hà Nội), alias có chữ Latin ở đầu bị cắt cụt (`…Chi-Thanh`) → 404. Dữ liệu trong admin KHÔNG sai (web chính chạy đúng các URL này vì dùng Express, không phải IIS).
   - Sửa: `inc/language-route.php` luôn đặt `$_SERVER["PATH_INFO"]` = đường dẫn đã giải mã UTF-8 khi URL có byte ≥ 0x80 (không phụ thuộc server). Đã kiểm chứng bằng máy chủ PHP giả lập 3 kiểu (REQUEST_URI + PATH_INFO bị đổi thành `?` như IIS; không có PATH_INFO; PATH_INFO giải mã như local): cả 14 URL ra đúng trang (title/canonical/h1 trùng bản local), 27 URL thông thường (404, 301, query string, `%20`…) không đổi. **Chưa chạy trên IIS thật** → sau deploy chạy lại `post-deploy-check`; nếu vẫn báo lỗi mới cần bước `diag-url.php` bên dưới.
   - `tools/post-deploy-check.mjs` nay bắt được kiểu "200 nhưng sai trang" (title trùng trang cha hoặc trùng giữa các URL khác nhau). Bản trước chỉ lọc `%XX` trong khi sitemap lưu URL đã giải mã nên khối kiểm tra này thực tế chưa từng chạy.
   - Code đã thêm (inc/language-route.php, đầu file): nếu `REQUEST_URI` chứa `?` và IIS cung cấp `UNENCODED_URL` / `HTTP_X_ORIGINAL_URL` / `HTTP_X_REWRITE_URL` chứa ký tự không phải ASCII thì dùng bản đó. **Chưa kiểm chứng được trên IIS** (không có quyền vào server).
   - Dev A kiểm chứng: tạo file `diag-url.php` ở thư mục gốc site với nội dung bên dưới, mở `https://loikaka.propertyplus.com.vn/diag-url.php/%E3%83%8F%E3%83%8E%E3%82%A4?x=%E3%83%8F`, gửi kết quả cho Dev B, rồi XOÁ file.
     ```php
     <?php header("Content-Type: text/plain; charset=utf-8");
     foreach (["REQUEST_URI","UNENCODED_URL","HTTP_X_ORIGINAL_URL","HTTP_X_REWRITE_URL","PATH_INFO","ORIG_PATH_INFO","QUERY_STRING","SERVER_SOFTWARE"] as $k) echo $k," = ",$_SERVER[$k] ?? "(không có)","
";
     ```
   - Nếu sau khi deploy bản mới mà `post-deploy-check` vẫn báo "URL tiếng Nhật mở được" thất bại thì cần chỉnh phía server (IIS URL Rewrite: đặt server variable chứa URL gốc dạng UTF-8, hoặc chạy PHP qua nginx/Apache). Không chuyển được lên production nếu chưa sửa — mất toàn bộ landing tiếng Nhật.
3. **Form "Gửi yêu cầu tư vấn" không hoạt động (lỗi có từ trước, đã sửa trong commit này).** `inc/advise-form.php` (route `pplus/v1/advise`) chưa từng được `require` và 3 biến JS `PPLUS_ADVISE_URL/CONFIRM/CLOSE` không được in ra → bấm Gửi báo "PPLUS_ADVISE_CONFIRM is not defined", không gửi được gì. Ảnh hưởng chi tiết căn hộ/dự án/văn phòng, landing, trang Liên hệ, modal "Hòm thư góp ý". Sau khi deploy: `post-deploy-check` có mục kiểm tra route + biến JS; ngoài ra gửi **1 form thật** với nội dung "TEST - bỏ qua" (script không gửi thật vì sẽ tạo lead trong admin) và xem lead có xuất hiện trong admin không.
4. **WP-Cron không chạy → sitemap thiếu** *(Từ 5/11/2026 sitemap lấy từ Admin nên phần sitemap không còn phụ thuộc cron; cron vẫn cần cho ảnh WebP và bảng 301.)* (index chỉ 2 file con; căn hộ/dự án/blog 0 URL). Gọi `wp-cron.php` qua HTTP (Cloudflare) bị 524 sau ~125 giây: job sitemap chạy quá lâu cho 1 request web. Cần chạy cron bằng bộ lập lịch hệ thống (Task Scheduler / cron gọi `php wp-cron.php` hoặc `wp cron event run --due-now` mỗi phút), không qua HTTP.
5. **hreflang có `%20` ở cuối** với alias backend có dấu cách cuối (vd "Japanese-area "): đã sửa trong `pplus_language_switch_url()` (cắt khoảng trắng cuối). Nên báo backend bỏ dấu cách thừa trong alias.
