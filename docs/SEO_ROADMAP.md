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
