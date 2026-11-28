# Lộ trình SEO propertyplus.com.vn (Resi) — tổng phụ trách: SEO Propertyplus

Cập nhật: 3/11/2026. Chiến lược và số liệu gốc: `SEO_STRATEGY_JA_EN.md`. Điều kiện lên trang chính: `GO_LIVE_CHECKLIST.md`. Công cụ kiểm: `tools/post-deploy-check.mjs`.

## 1. Quyền hạn vận hành (chủ site đã giao ngày 3/11/2026)

| Tôi tự làm | Chủ site làm |
|---|---|
| Lập kế hoạch, audit, phân tích Ahrefs (chỉ đọc), tìm ý tưởng, viết bài | Liên hệ **báo chí** và mọi kênh **trả phí / tương tác bên ngoài** (PR, mua link, quảng cáo, gửi email outreach) |
| Chuyển hướng và nội dung trong admin (đăng nhập do chủ site bấm một lần) | Bấm đăng nhập admin, cấp quyền Google Search Console |
| Code theme Resi, commit, push lên `origin/main` | Dev A: deploy lên server, cấu hình server/Cloudflare/cron |
| Quản trị dự án Ahrefs (không nạp từ khoá/credit khi chưa chốt) | Duyệt chi tiêu credit, duyệt bản tiếng Nhật cuối (người bản ngữ) |

## 2. Đã thực hiện

| Hạng mục | Kết quả |
|---|---|
| Dự án Ahrefs "Propertyplus.com.vn (Resi)" | Tạo xong; lịch quét tắt, chưa nạp Rank Tracker (còn 46 chỗ) |
| Phân tích 3 đối thủ + quy mô từ khoá | hanoi-living (DR17, 284/tháng), vietlonghousing (DR11, 235/tháng), sakurahousing (0). Ta DR26 nhưng chỉ 58 lượt/tháng |
| **Bảng chuyển hướng admin** (nguồn chung cho trang chính và Resi) | +22 dòng: 2 URL không tiền tố đã index (`/ha-noi/quan-tay-ho`, `/ha-noi/quan-long-bien`), `/Error` (19 tên miền trỏ về), 12 URL landing kiểu cũ (`/apartments/…-36/-37/-38`, `/en/apartments/…`, `/projects/…`, `/en/can-ho`), 4 URL `.html` đời đầu (`/san-pham/…`, `/chi-tiet-san-pham/…`), 1 URL tiếng Nhật. Mỗi dòng đã kiểm 301 đúng đích trên Resi |
| Theme: tra bảng chuyển hướng cả với URL có đuôi `.html` | `inc/url-canonical.php` |
| Theme: trang **easy-living** (`/{ngôn ngữ}/easy-living/{id}`) | Trước đây 404 trên Resi trong khi ~35 URL loại này có backlink và từng có từ khoá đứng hạng. Có title/description/H1/canonical/hreflang/JSON-LD `LocalBusiness`, bản đồ, khối liên quan; 404 thật cho id sai |
| Sitemap khớp trang chính | Xem `GO_LIVE_CHECKLIST.md` mục 3 |
| Sửa 404 trong bài viết CMS (link `/en/cho-thue-can-ho/…`) | Đã viết lại tự động |

## 3. Phát hiện cần Dev A / chủ site xử lý (ngoài phạm vi theme)

1. `https://www.propertyplus.com.vn/` đang **302 sang trang đăng nhập admin** (2 tên miền trỏ về) → phải 301 về tên miền chính không www.
2. `https://app.propertyplus.com.vn/` hiện là trang mẫu "Test Page for the HTTP Server on Red Hat Enterprise Linux" nhưng có 10 tên miền trỏ về → đặt trang/redirect hợp lý.
3. Admin còn trang rác `https://propertyplus.com.vn/Error` được 19 tên miền trỏ về (đã chuyển hướng về `/`).
4. `loikaka.propertyplus.com.vn` có link từ bên ngoài (`/blogs/ai--iot--aiot-860`) — giữ noindex, không dùng làm URL chính.
5. Backend: loại địa điểm easy-living ở bản EN/JA vẫn là tiếng Việt ("Nhà hàng"); alias JA của căn hộ có `--` nên căn hộ JA chưa vào sitemap.

## 4. Kế hoạch 12 tuần

**Tuần 0 — trước khi đưa Resi lên (chặn rủi ro)**
- [x] Chuyển hướng URL có backlink, easy-living, sitemap, bộ kiểm.
- [ ] Dev A deploy các commit mới (cần: cron WP, GD-WebP, `PPLUS_NOINDEX=false`, tạo Page `ve-chung-toi` + `lien-he`) — xem checklist.
- [x] Search Console: đọc được qua phiên Google của chủ site (số gốc ở `SEO_STRATEGY_JA_EN.md` mục 7).
- [ ] Chủ site: quyết định tiêu đề trang danh sách (khuyến nghị dùng `title` của trang chính).
- [x] Sửa 503 cho bài blog/dự án nặng (nâng thời gian chờ API); thêm `tools/priority-urls.txt` (53 URL có traffic/backlink) vào bộ kiểm.
- [ ] Chạy `node tools/post-deploy-check.mjs https://propertyplus.com.vn --warm` — không còn ✗.

**Tuần 1–2 — ổn định và đo lường**
- Nộp `/sitemap.xml` trong Search Console; kiểm tra 5 URL đại diện (URL Inspection).
- Theo dõi báo cáo Pages: URL 404 mới phải bằng 0 với danh sách backlink (xuất lại từ Ahrefs "Best by links" mỗi tuần, so với Resi).
- Thêm 3 đối thủ vào Ahrefs; chạy Content Gap (JA/EN) và Link Intersect; bật Site Audit hằng tuần.
- Nạp Rank Tracker (46 từ khoá: 20 JA, 15 EN, 11 tên toà) sau khi chủ site chốt.

**Tuần 2–4 — cụm từ khoá đầu (KD 1–5, đối thủ yếu) + cửa vào "khu vực sống"**
- Mở rộng 2 bài đang có lượt hiển thị (cộng đồng người Nhật / người Hàn tại Hà Nội) thành cẩm nang JA/EN: 日本人街・韓国人街・タイホー区 (Search Console: ハノイ 韓国人街 163 hiển thị, ハノイ 韓国 232), liên kết sang danh sách theo khu.
- Viết bản nội dung tiếng Nhật và tiếng Anh cho các trang đích: ハノイ 賃貸（日本人向け）, ハノイ サービスアパート, ハノイ アパート 家賃相場, ハノイ 不動産; hanoi apartment for rent, serviced apartment hanoi, hanoi expat housing. Bản nháp lưu trong `docs/content/`, người bản ngữ duyệt, tôi đưa vào admin ("Bài viết các Quận/huyện", "Vùng căn hộ").
- Thêm khối FAQ có dữ liệu có cấu trúc `FAQPage` vào các trang đích (theme).

**Tuần 4–8 — đuôi dài theo tên toà nhà**
- Chọn ~60 toà theo lượt tìm kiếm (Ahrefs) → chuẩn hoá tên katakana/Anh/Việt trong title, H1, đoạn mở đầu; địa chỉ tiếng Nhật; liên kết nội bộ từ trang quận và bài đời sống.

**Tuần 6–12 — nội dung đời sống và uy tín**
- 30–40 bài đời sống JA/EN (tái dùng `easy-living`): điện/ổ cắm, SIM, rác, siêu thị Nhật, bệnh viện, trường học, chi phí, thú cưng, thủ tục thuê.
- Backlink: danh sách mục tiêu (cộng đồng Nhật tại Việt Nam, trường/hiệp hội, chủ đầu tư Nhật, blog expat) soạn sẵn kèm email mẫu — **chủ site gửi/liên hệ**.
- Đo tìm kiếm AI (Brand Radar): FAQ, `Organization`/`RealEstateAgent`, `sameAs` (Google Business Profile tiếng Nhật, LINE OA, Facebook).

## 5. Chỉ số theo dõi hằng tuần
- Số URL có backlink còn 404 trên Resi (mục tiêu 0).
- Số từ khoá top 3 / top 10 trong Rank Tracker; traffic organic (mốc đầu 58 lượt/tháng; mục tiêu tháng 6: 600–1.000, ước tính).
- Số tên miền trỏ về mới (mốc đầu 650).
- Báo cáo Search Console: trang hợp lệ đã index, lỗi 5xx/404 mới.

## 6. Cập nhật 20/11/2026 — đo lường sau go-live (tuần 2–3)

**Số liệu Search Console (dữ liệu Google trễ đến 17/11, phần lớn là trước/ngay sau go-live):**

| Chỉ số | 3/11 | 13/11 | 20/11 |
|---|---|---|---|
| Nhấp / hiển thị, 28 ngày | — | — | 327 / 19,5K (kỳ trước 332 / 18,5K; vị trí TB 12,4 → 13,9) |
| Nhật Bản, 28 ngày | — | — | 30 nhấp / 1,4K hiển thị; truy vấn: ハノイ 日本人街, 韓国人街, 韓国 (chưa có cụm đầu "賃貸/アパート" đáng kể) |
| Đã lập chỉ mục | 708 | 720 | **669** |
| Chưa lập chỉ mục | 4.169 | 4.059 | **4.220** |
| Đã thu thập – chưa lập chỉ mục | 665 | 793 | **1.030** |
| Đã phát hiện – chưa lập chỉ mục | 2.416 | 2.422 | 2.398 |
| Trùng lặp, chưa chọn canonical | 661 | 439 | **341** (giảm đều từ ~860) |
| Googlebot thu thập/ngày | ~100–250 | — | **tăng vọt lên 600–700 từ ~17/11** (server nhanh lại sau bản cập nhật cron) |

**Chẩn đoán.** Nhóm "đã thu thập – chưa lập chỉ mục" tăng vì Google đã thu thập trang Resi (18/11) nhưng chê chất lượng. So với trang có lượt nhấp (Lotte, The Authentic: 7.400–13.900 ký tự chữ, title 70 ký tự, mô tả ~70), trang bị loại: 1.400–4.700 ký tự, title chỉ là tên dự án ("Le Capitole"), mô tả 11–27 ký tự, title căn hộ lặp tên tòa. Mẫu 16 URL sitemap: 7 "dự án vỏ" noindex; **8/9 dự án cho phép index chỉ có tên làm title + mô tả**. Cột trái `#web-content` trống vì phần lớn dự án không có bài SEO, trong khi API đã trả sẵn (và dịch en/ja) địa chỉ, năm hoàn thành, dịch vụ, tiện ích, tới 89 địa điểm lân cận.

**Đã làm (đã push, chờ Dev A chép — gói `capnhat-resi-2026-11-20b.zip`):**
- `1b20add` — title + meta description tự động cho trang dự án và căn hộ khi Admin chưa nhập (vi/en/ja, dữ liệu thật; metaTitle/metaDescription Admin giữ nguyên).
- `db9f7b3` — khối "Thông tin tòa nhà" trên trang dự án (3 ngôn ngữ, nhóm địa điểm lân cận, FAQ + JSON-LD `FAQPage`).
- Kết quả đo ở local: trang Le Capitole 1.382 → 2.936 ký tự chữ; 15 trang mẫu × 3 ngôn ngữ chạy 200, không lỗi PHP, JSON-LD hợp lệ.

**Việc tiếp theo (theo thứ tự):**
1. Dev A chép gói → tôi đối chiếu 3 ngôn ngữ + dung lượng file; yêu cầu lập chỉ mục lại 5 URL đại diện (URL Inspection) và theo dõi nhóm "đã thu thập – chưa lập chỉ mục" sau 2–4 tuần (mục tiêu: ngừng tăng rồi giảm).
2. Cụm đầu tiếng Nhật/Anh (trụ B): tiêu đề trang danh sách trong Admin đang yếu (`/ja/rental-apartment/ha-noi` = "ハノイの賃貸アパート"; `/en/rental-apartment/ha-noi` = "hanoi apartment for rent") — soạn tiêu đề/mô tả + khối nội dung JA/EN (cần chủ site đăng nhập Admin; người bản ngữ duyệt bản tiếng Nhật).
3. Chính sách trang mỏng: sau khi có dữ liệu mới, quyết định noindex cho dự án chỉ có 1–2 căn và không có bài viết (hiện chưa làm — chưa có bằng chứng đủ).
4. Dev A/Back: sitemap vẫn liệt kê dự án noindex (mục 2A/2B), www → 301.
5. Backlink / Ahrefs Content Gap (chỉ đọc) khi chủ site duyệt.

## 7. Cập nhật tối 20/11/2026 — vệ sinh trang đích, lớp ghi đè từ Admin

**Kiểm toán 206 URL** trong `sitemap_quan.xml` + `sitemap_type.xml` (EN + JA; chi tiết từng dòng: `docs/content/landing-audit-2026-11-20.csv`):
- **~36 trang đích rỗng** (`loai` 90/91/92, EN 18 + JA 19): bản ghi Hoạt động / Báo chí / Khám phá (team building, hiến máu, quyên sách, phỏng vấn…). **Đây là dữ liệu thật của công ty (Admin: Danh sách hoạt động 6, Bài viết Báo chí 4, Tin tức Blog > Khám phá 63) — không xóa**; vấn đề chỉ là khi mở bằng đường dẫn trang đích thì nội dung 0–18 ký tự → trang chỉ là danh sách căn hộ chung với tiêu đề lạc đề. **28 trang đang `index`** và nằm trong sitemap.
- 3 alias EN trả 404, 6 trang `Villa-*` trả 410, 1 trang `loai` 5 Admin đã tắt index — vẫn nằm trong sitemap.
- URL lặp: `sitemap_quan` 319 dòng (201 URL thật), `sitemap_type` 301 dòng (195 URL thật).
- Lỗi dữ liệu Admin: JA `cau-giay-district` có title "ドンダー区の賃貸アパート" (là tên quận **Đống Đa**, đúng phải là カウザイ区); một số cặp trang trùng tiêu đề/tranh nhau từ khoá (EN `luxury-apartment` vs `luxury-apartment-for-rent-in-ha-noi`, cặp luxury tiếng Nhật); vài trang đích thật còn mỏng (ciputra-area ~1,3K ký tự, times-city-park-hill-area, Zenpark/Zurich ~1K).

**Đã làm ở theme (không cần chủ site sửa Admin):**
- `inc/landing-hygiene.php`: trang đích tin nội bộ rỗng (`loai` ≥ 90 và < 200 ký tự) → `noindex, follow`; sitemap `quan`/`type` loại alias rác (denylist 25 EN + 23 JA) và URL lặp; các sitemap khác chỉ khử lặp. Local: `sitemap_quan` 319 → 153 URL, `sitemap_type` 301 → 147.
- **Lớp ghi đè từ Admin cho dự án / căn hộ** — ô nào Admin điền thì dùng, bỏ trống thì dùng tự sinh như hiện tại (title, mô tả, giới thiệu toà nhà, noindex). Theme đã đọc sẵn các trường `seoTitle`, `seoDescription`, `seoIntro`, `seoNoIndex`; chưa có trường thì chạy như cũ. Mô tả viết tay sẵn có trong bài viết dự án nay cũng được dùng. Yêu cầu cho Dev: `docs/DEV_YEU_CAU_TRUONG_SEO_ADMIN.md`.

**Việc tiếp theo:**
1. Dev A chép gói `capnhat-resi-2026-11-20c.zip` (thay 20b) → đối chiếu dung lượng + `robots`/sitemap.
2. **Đã làm trực tiếp trong Admin (20/11 tối, chủ site cho phép + đăng nhập):** title JA `cau-giay-district` (ドンダー区 → カウザイ区の賃貸アパート, đã lên trang thật); trang danh sách Hà Nội (Menu tỉnh thành #1) — EN title "Hanoi Apartment for Rent | Serviced & Luxury Apartments | Property Plus" + mô tả mới, JA title "ハノイの賃貸アパート・サービスアパート｜家賃相場と最新の物件一覧" + mô tả kính ngữ (API đã trả, trang thật cập nhật sau cache ~10 phút); JA Vinhomes Skylake (DataList #1228) sửa nhầm quận カウザイ区 → ナムトゥリエム区. Quy tắc: văn bản tiếng Nhật luôn dùng kính ngữ (です・ます). Còn lại: ~20 title JA dự án thiếu Meta Title/bị cụt ("…の概"), trang trùng tiêu đề, trang mỏng; 3 alias EN 404 tìm trong Khám phá/Hoạt động. Chủ site chỉ cần gửi `DEV_YEU_CAU_TRUONG_SEO_ADMIN.md` cho người sửa code Admin.
3. Dev: thêm 4 trường SEO (vi/en/ja) cho Dự án + Căn hộ, lọc sitemap ở gốc (loai ∈ {1,2,3,4,9}, isIndex = true, bỏ 404/410, không lặp, lastmod thật).
4. Tiêu đề/mô tả trụ B (JA/EN), gộp các cặp trang trùng tiêu đề, bổ sung nội dung trang đích mỏng; đo lại Search Console sau 2–4 tuần.

## 8. Cập nhật 21/11/2026 — trang "Tất cả tòa nhà" (liên kết nội bộ cho ~840 dự án)

**Vấn đề:** menu chỉ có 12 tòa tuyển chọn (Admin: bài viết dự án có "Hiển thị Menu = Theo tòa nhà"); phần lớn trang dự án không có liên kết nội bộ nào → Google "đã phát hiện, chưa lập chỉ mục" (~2.400 URL).

**Đã làm (local, chưa deploy — gói `capnhat-resi-2026-11-21e`):**
- Trang mới `/{vi|en|ja}/buildings` (`inc/building-directory.php`, `page-building-directory.php`, `assets/css/building-dir-component.css`): 564 tòa còn căn đang cho thuê, nhóm theo 11 quận (Tây Hồ 191, Ba Đình 178, Hai Bà Trưng 55, Cầu Giấy 54…), chip nhảy quận, mỗi tên là link thật tới trang dự án; có title/description 3 ngôn ngữ (tiếng Nhật kính ngữ), canonical + hreflang tự có (gốc `buildings` thêm vào `PPLUS_ROOTS_SHARED`), tự thêm 3 URL vào sitemap con `pages`.
- Link "Xem tất cả tòa nhà ›" ở cuối cột "Tìm theo tòa nhà" của menu (`parts/header.php` + CSS `.menu-all-buildings`) → có trên mọi trang.
- **Dữ liệu là file tĩnh** `data/building-directory.json` (191 KB), tạo bằng `php tools/gen-building-directory.php` (~6 phút, chỉ đọc API; `--normalize-only` chuẩn hóa lại tên quận không gọi API). Lý do: server thật chậm (~4 s/request PHP) và WP-Cron nhạy cảm (xem mục 6) nên không gọi ~250 lần API lúc có request. Chỉ gồm dự án có ≥ 1 căn đang đăng (dự án "vỏ" 36% tự noindex). **Cần chạy lại khi có dự án mới** (gợi ý: mỗi tháng hoặc trước mỗi bản phát hành).
- Tên quận chuẩn hóa theo cách web đang gọi (API trả không nhất quán: "Nam Từ Liêm" không "Quận", "Tây Hồ district" có dấu Việt, tiếng Nhật "タイホ区" so với "タイホー区").
- Kiểm thử: 19 test đơn vị (nhóm/sắp xếp/dự án ẩn/văn bản 3 ngôn ngữ/sitemap/canonical), smoke 8 trang, 3 ngôn ngữ HTTP 200; link đích mẫu trên web thật đều 200.

**Việc tiếp theo:** Dev A chép gói 20e; yêu cầu lập chỉ mục lại 3 URL `/…/buildings` trong Search Console; theo dõi nhóm "đã phát hiện, chưa lập chỉ mục" sau 2–4 tuần; sau này cân nhắc đánh dấu tòa đã có bài viết trong danh sách.

## 9. Cập nhật tối 21/11/2026 — trạng thái sau khi Dev A chép gói 21i, bật lại GTM

- **Dev A đã chép gói 21i** (xác nhận bằng dung lượng file tĩnh và dấu hiệu hành vi trên web thật). Bộ rà soát `tools/seo-crawl.mjs` (mẫu 60 URL, trước → sau): lỗi 11 → 5, cảnh báo 385 → 72, dòng trùng sitemap quận 224 → 0, URL noindex trong sitemap ước tính 18 % → 8 %.
- Kiểm sau deploy (`tools/post-deploy-check.mjs`): 118 đạt / 4 cảnh báo / 3 lỗi — `/vi/` (gạch cuối) chưa 301, `www` trả 200 (canonical đúng), 2 URL dự án noindex trong sitemap (dự án "vỏ"; sẽ lọc khi có danh sách dự án vỏ), ngưỡng sitemap quận/loại trong công cụ đã sửa 250 → 100.
- **GTM (GA4 + Google Ads) bị tắt từ 13/11** theo yêu cầu giảm 650 KB JS → 8 ngày không ghi lượt truy cập/chuyển đổi. Chủ site giao tôi quyết định: **bật lại kiểu nạp trễ** (tương tác đầu tiên hoặc ~3,5 s sau khi tải xong; nạp NGAY với khách quảng cáo gclid/utm và trang cảm ơn form). Gói 21j (commit 36005b7); hết dữ liệu 13/11 → deploy là khoảng trống không bù được.
- Việc tiếp theo: gửi lại sitemap trong Search Console + URL Inspection 5 trang đại diện; viết mô tả blog và mô tả trang dịch vụ (Admin); lọc dự án "vỏ" khỏi sitemap sản phẩm; chọn chủ đề đang hot và viết bài JA/EN/VI.

## 10. Cập nhật 26/11/2026 — gói 26a (sitemap dự án, mô tả blog)

- **Sitemap sản phẩm bỏ trang dự án "vỏ"** (0 căn đang đăng và bài viết < 200 ký tự, trang vốn tự noindex): 2.538 → **1.713 URL** (571 × 3 ngôn ngữ = 564 tòa có căn + 7 dự án có bài viết). Danh sách theo từng ngôn ngữ nằm trong `data/building-directory.json` (`shell`: vi 281, en 279, ja 279), do `tools/gen-building-directory.php` tạo (nay kiểm cả id có trong sitemap nhưng API danh sách không trả về — nhóm này sót 15 dự án ở lần chạy đầu). Kiểm chứng trên web thật: 18/18 URL mẫu bị bỏ đang noindex; 7 dự án giữ lại (Vinhomes Skylake, Vinhomes Riverside, Sunshine City, The Authentic, 3 dự án Ocean Park) đang index. Bộ lọc tự bỏ qua nếu thiếu dữ liệu hoặc nếu sẽ bỏ > 60 % URL.
- **Trang `/{lang}/blogs`:** tiêu đề + mô tả riêng 3 ngôn ngữ (trước: `/ja/blogs` = "Blog - PropertyPlus", mô tả = tagline).
- **Mô tả trang bài viết:** Meta Description (Admin) → Giới thiệu ngắn → đoạn mở đầu bài; trước đây nhiều bài en/ja bỏ trống phần tóm tắt nên dùng chung câu giới thiệu công ty (vd bài JA khu người Hàn, 1.777 lượt hiển thị / 3 tháng).
- **Số đo blog (GSC 24/8–23/11):** 414 nhấp / 69,7 nghìn hiển thị. Riêng `/en/blog/...-230` (nhà hàng Hàn) có **40,9 nghìn hiển thị, 3 nhấp**, chủ yếu là truy vấn "… near me" từ người không ở Hà Nội → khi theo dõi CTR/hiển thị toàn site phải **tách trang này ra**.
- **Việc Admin (đã soạn sẵn):** `docs/content/blog-meta-drafts-2026-11-26.json` — mô tả viết tay cho 9 bài cho thuê quan trọng × 3 ngôn ngữ (kèm giá trị cũ để khôi phục). Thêm: tên chuyên mục blog tiếng Nhật sai ("Tài liệu tham khảo" → 「通り」, "Khám phá Việt Nam" chưa dịch, 「在留 - 時在留」 cụt chữ); bài JA #66 (trường quốc tế) bị hỏng chữ trong nội dung gốc, cần viết lại.

## 11. Cập nhật 27/11/2026 — 26a đã lên web; gói 27a (mô tả trang tĩnh, nhãn dịch máy, www)

- **26a đã chạy trên web thật** (27/11): sitemap dự án 1.713 URL, tiêu đề `/…/blogs` đúng 3 ngôn ngữ, `tools/post-deploy-check.mjs` 123 đạt / 2 cảnh báo / 0 lỗi; đã nộp lại `sitemap_products.xml`, Google đọc cùng ngày (1.713).
- **Gói 27a** (commit 24fb944, 5 file, chờ Dev A chép): mô tả riêng cho 21 trang (giới thiệu, chủ nhà, dịch vụ × 3 ngôn ngữ) viết theo nội dung đang có trên trang — trước đó dùng chung câu giới thiệu công ty (do theme, **không phải Admin** như ghi ở mục 9); sửa 7 chuỗi dịch máy trong từ điển API (chỉ thay khi API còn chuỗi cũ); `www` → 301 tên miền chính; bỏ gạch đầu dòng ở mô tả blog.
- **Còn lại:** SEO theo tên tòa nhà (Lotte The Residence, The Authentic Haseko, Grandeur Palace, Five Residences, Fraser Suites); đo lại Search Console 30/11; bài hướng dẫn JA/EN theo truy vấn thật; 2–9/12 xem lại trang Le Capitole; khoảng 10/12 so lượng khách thật trước/sau khi đổi web (mốc trước 11/11; web Angular cũ mất ~6–8 s mới hiện nội dung theo chủ site).

## 12. Cập nhật 28/11/2026 — gói 28a (thay 27a), rà bản dịch toàn bộ dự án

- **Gói 28a** (commit afd32f9, 11 file, chờ Dev A chép; thay 27a): canonical trang dự án về đường dẫn chính thức; sửa câu mô tả tự sinh; **thử gộp Lotte** (trang tổng quan VI/EN 301 → trang dự án, menu + sitemap theo; tắt bằng `PPLUS_MERGE_LANDINGS=false`). Đo sau 2–4 tuần kể từ khi lên web: trang dự án Lotte được lập chỉ mục lại chưa, nhấp/hiển thị so với 3 tháng trước (dự án 42 nhấp, tổng quan 3). Kết quả tốt mới mở rộng sang tòa khác.
- **Bản dịch:** 37 tòa có bài đều đủ vi/en/ja; đã bổ sung 31 ô "Giới thiệu ngắn". Chi tiết: `docs/content/admin-changes-2026-11-28.md`.
- Còn lại: đo lập chỉ mục 30/11; bài hướng dẫn JA/EN theo truy vấn thật (tuần sau); đường dẫn dự án tiếng Nhật dạng `--80` (cần sửa trước khi gộp bản JA).
