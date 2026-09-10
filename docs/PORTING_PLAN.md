# Kế hoạch port Angular → WordPress (PropertyPlus **Resi** – căn hộ/nhà ở)

> **Nguồn Angular MỚI (bản này):** `G:\WorkLamThem\PPlusAgular\PPlusAngular`
> **Theme WordPress đích:** `G:\WorkLamThem\PPlusAgular\PPlusResiWordpress\wp-content\themes\propertyplus-resi`
> **Kế thừa know-how từ dự án cũ (bản Văn phòng):** `G:\WorkLamThem\PPlusAgular\PPlusWordpress`
> (đọc kèm `docs/PORTING_NOTES.md`, `docs/SCREENS_AND_TASK_SPLIT.md`, `docs/DOTNET_API_REQUIREMENTS.md` của dự án cũ — **các bài học kỹ thuật bên dưới tái dùng nguyên xi**).

Kế hoạch này **không làm lại từ đầu**. Cách làm, quy ước, cạm bẫy kỹ thuật đã được đúc kết ở
dự án Văn phòng và áp dụng y nguyên. Tài liệu này chỉ nêu **những gì KHÁC** ở bản Resi và
danh sách màn hình/route/API cụ thể của nó.

---

## 0. Khác biệt cốt lõi so với dự án cũ (bản Văn phòng)

| Hạng mục | Bản cũ (Văn phòng) | Bản mới (Resi – tài liệu này) |
|---|---|---|
| Chủ đề | Cho thuê **văn phòng** | Cho thuê **căn hộ + biệt thự** |
| Slug chính | `cho-thue-van-phong` | `cho-thue-can-ho`, `biet-thu-cho-thue` |
| Controller API chính | `Office/*` | `Apartment/*` |
| Controller API mới (chưa từng port) | – | `Service/*`, `Owner/*`, `EasyLiving/*`, `Project/*` |
| Domain API .NET | `https://admin.propertyplus.vn` | `https://admin.propertyplus.com.vn/` (xem `src/app/configs/app.configs.ts`) |
| Ngôn ngữ | vi, en (2) | **vi, en, ja (3)** |
| Nhóm trang mới | – | **Dịch vụ (Service), Chủ nhà (Owner), Tiện ích (EasyLiving)** |
| Độ sâu route landing | `:province/:alias` | thêm cấp `:province/:district/:project` rồi `/:id` |

Ngoài bảng trên, **hạ tầng nền giống hệt**: cùng bộ `assets/officebase`, cùng cách enqueue theo
`angular.json`, cùng header/footer LESS component, cùng pattern `inc/api-client.php` + cache transient,
cùng cách chạy local bằng `php -S` + `router.php`.

---

## 1. Bài học kỹ thuật tái dùng nguyên xi (từ `PORTING_NOTES.md` cũ)

Bắt buộc áp dụng lại, **không phát hiện lại từ đầu**:

1. **Copy asset tĩnh nguyên trạng** `src/assets/officebase` và `src/assets/Content` (bản Resi có thêm
   `src/assets/icons`) sang `theme/assets/`. Lập script copy để đồng bộ lại mỗi khi Angular đổi asset.
2. **Compile LESS → CSS trước khi enqueue.** WordPress không tự compile LESS. Các file `.less` mà
   `angular.json` liệt kê thẳng trong `styles[]` phải chạy `lessc` ra `.css`. Ở bản Resi
   (`angular.json` dòng 50–59) các file cần compile là:
   - `assets/slick/slick.less`, `assets/slick/slick-theme.less`
   - `style/icon.less`, `Common/Styles/common.less`, `Common/Styles/core.less`, `style/layout.less`
3. **CSS header/footer nằm ở LESS của component**, KHÔNG nằm trong `officebase`. Copy + compile riêng:
   - `src/app/partials/teamplate-header/teamplate-header.component.less`
   - `src/app/partials/teamplate-footer/teamplate-footer.component.less`
   (2 component để `ViewEncapsulation.None` nên Angular gộp vào bundle toàn cục — rất dễ quên).
4. **Xoá `::ng-deep` khỏi CSS sau khi compile.** `lessc` giữ nguyên `::ng-deep` → trình duyệt coi cả
   rule là không hợp lệ và bỏ qua **im lặng**. Sau mỗi lần compile: `grep -l "::ng-deep"` rồi `sed` xoá.
5. **Cấu trúc div lồng nhau của CSS grid phải đúng** (vd `.box-list-body .list-product-office`,
   `.result-box-detail .list-product-office`) — mỗi trang list dùng wrapper class riêng, kiểm tra kỹ.
6. **Xác định field API bằng cách đọc thẳng controller/DTO .NET**, không đoán qua Angular. (Với Resi:
   đọc `ApartmentController`, `Models/Map/ApartmentFilter.cs`... bên `PPlus.Manager.Web.Public*`).
7. **Chạy local:** `php -S` cần `router.php` ở gốc site; URL trang custom sẽ có prefix `/index.php/...`
   khi chạy local — không phải lỗi 404.
8. **Hành vi JS** (toggle menu mobile, modal gọi/mail/chat, tap submenu mobile) viết lại thuần jQuery
   trong `assets/js/main.js` bằng `data-*` attribute, thay cho event binding Angular.

---

## 2. Bản đồ màn hình Resi → template WordPress

Nguồn route: `src/app/app-routing.module.ts`. Nguồn component: `src/app/modules/*`.
Cột "Giữ Angular" = quyết định giữ nguyên bên Angular (giống dự án cũ: favourite/compare/dashboard/login).

### Nhóm nền tảng dùng chung (Phase 0 — làm trước, 1 người)

| Việc | Nguồn Angular | File đích |
|---|---|---|
| Copy asset + enqueue đúng thứ tự `angular.json` | `src/assets/officebase/**`, `angular.json` | `assets/officebase/**`, `functions.php` |
| Header (menu, mega dropdown, chọn ngôn ngữ **vi/en/ja**, yêu thích) | `partials/teamplate-header/*` | `parts/header.php` |
| Footer (liên hệ, menu, chính sách, social, modal gọi/mail/chat, form đăng ký) | `partials/teamplate-footer/*` | `parts/footer.php` |
| API client chung + cache transient | `services/*.service.ts`, `configs/app.configs.ts` | `inc/api-client.php` |
| Card căn hộ / breadcrumb / pagination dùng chung | `partials/office-list`, `partials/project-list` | `parts/apartment-card.php`, `parts/breadcrumb-part.php`, `parts/pagination.php` |
| Multi-ngôn ngữ: hàm `pplus_current_lang()` xử lý 3 tiền tố URL `vi`/`en`/`ja` | route module | `inc/lang.php` |

### Nhóm "Bất động sản" (phức tạp: filter + map + mega-menu)

| # | Màn hình | Route Angular | API chính | Template đích |
|---|---|---|---|---|
| A1 | Danh sách căn hộ cho thuê (filter popup + Google Maps) | `vi/cho-thue-can-ho` | `Apartment/_GetApartments`, `Apartment/_GetMarkers` | `page-apartment-list.php` |
| A2 | Chi tiết căn hộ/biệt thự (lightbox + map + form AJAX) | `.../:province/:district/:project/:id` | `Apartment/_GetDetail` | `inc/apartment-detail-route.php` + `page-apartment-detail.php` |
| A3 | Landing theo tỉnh | `.../:province` | `Reference/GetIndexProvince` | `inc/reference-route.php` + `page-landing.php` |
| A4 | Landing theo alias/loại hình + **biệt thự** | `.../:province/:alias`, `biet-thu-cho-thue/:province/:alias` | `Reference/GetIndex` | `page-reference.php` |
| A5 | Landing theo dự án | `.../:province/:district/:project` | `Reference/GetIndex` (cấp project) / `Project/_Project` | `page-project.php` |
| A6 | Danh sách đường theo tỉnh | `vi/street/:province` | `Reference/GetIndexProvinceStreet` | `inc/street-route.php` + `page-street.php` |

### Nhóm "Nội dung + Trang mới của Resi"

| # | Màn hình | Route Angular | API chính | Template đích |
|---|---|---|---|---|
| B1 | Trang chủ | `''`, `vi`, `en`, `ja` | `Home/GetIndex`, `Home/_GetApartments` | `front-page.php` |
| B2 | Danh sách tin tức | `vi/blogs` | `Blogs/GetIndex` | `archive-news.php` |
| B3 | Chi tiết tin tức | `vi/blog/:alias` | `Blogs/GetDetail` | `inc/blog-routes.php` + `single-news.php` |
| B4 | Về chúng tôi | `vi/ve-chung-toi` | `About/GetIndex` | `page-about.php` |
| B5 | Về chúng tôi — trang con | `vi/ve-chung-toi/:alias` | `About/TinTucHoatDong` | `page-about-subpage.php` |
| B6 | Liên hệ | `vi/lien-he` | form → `Owner/IssueNewApartment` / advise | `page-contact.php` |
| **C1** | **Dịch vụ** (4 loại: quản lý / dọn phòng / kỹ thuật / đăng ký) | `vi/service/...-{1..4}` | `Service/ServiceType`, `Service/RegistService`, `Service/IssueLeaseApartmentService` | `page-service.php` (đọc `TypeSite` 1–4) |
| **C2** | **Chủ nhà** (2 loại: lợi ích / đăng ký cho thuê) | `vi/owner/loi-ich-chu-nha`, `vi/owner/dang-ky-chu-nha` | `Owner/IssueNewApartment` | `page-owner.php` (đọc `TypeSite` 1–2) |
| **C3** | **Tiện ích (Easy Living)** | `vi/easy-living/:id` | `EasyLiving/_Detail` | `inc/easyliving-route.php` + `page-easyliving.php` |
| B7 | Gửi mail thành công | `Apartment/IssueLeaseApartmentSuccses` | – (tĩnh) | `page-send-success.php` |
| B8 | 404 | `**` | – | `404.php` |

> **Giữ nguyên bên Angular** (không port, theo quyết định như dự án cũ): `danh-sach-yeu-thich`
> (Favourite), `favourite-detail`, `so-sanh-van-phong` (ProductCompare), `dashboard`, login/register.

**C1/C2/C3 là phần hoàn toàn mới** so với dự án cũ — cần đọc kỹ 3 component
(`modules/service/list-service`, `modules/owner/owner-index`, `modules/easy-living`) + 3 service
tương ứng để dựng template và xác định field API (đọc thẳng controller .NET như bài học #6).

---

## 3. Đa ngôn ngữ (vi / en / ja) — điểm khác lớn nhất

Route có 3 tiền tố với **slug khác nhau theo ngôn ngữ**, ví dụ:
- Căn hộ: `vi/cho-thue-can-ho` · `en/rental-apartment` · `ja/rental-apartment`
- Biệt thự: `vi/biet-thu-cho-thue` · `en/villa-for-rent` · `ja/villa-for-rent`
- Liên hệ: `vi/lien-he` · `en/contact` · `ja/contact`
- Dịch vụ: `vi/service/dich-vu-...` · `en/service/the-management-...` · `ja/service/...`

Kế hoạch: dựng **bảng ánh xạ slug ↔ (lang, page_key)** tập trung trong `inc/lang.php`, để rewrite rule
và menu/hreflang dùng chung một nguồn. Mỗi API call truyền `{lang}` vào path
(`${apiUrl}{lang}/Controller/Action`) đúng như Angular (`GlobalConstants.CurrentLanguage`).
`pplus_current_lang()` suy ra lang từ prefix URL đầu tiên, mặc định `vi`.

---

## 4. Cấu hình theme (trang Settings)

Tái dùng `inc/settings-page.php` của dự án cũ, đổi mặc định:
- `pplus_dotnet_api_base` = `https://admin.propertyplus.com.vn` (bản Resi, có `.com`)
- `pplus_angular_app_base` = domain Angular Resi đang chạy thật (xác nhận trước khi deploy)
- `pplus_google_map_keys` = key thật (kiểm tra HTTP-referrer khi đổi domain)
- (tuỳ chọn) `pplus_default_lang` = `vi`

---

## 5. Thứ tự thực hiện đề xuất

1. **Phase 0 (nền):** scaffold theme `propertyplus-resi` → copy asset → compile LESS (6 file + header/footer)
   → xoá `::ng-deep` → `functions.php` enqueue đúng thứ tự → `parts/header.php` + `parts/footer.php`
   (có chọn 3 ngôn ngữ) → `inc/api-client.php` + `inc/lang.php` → `parts/apartment-card.php`,
   `parts/pagination.php`, `parts/breadcrumb-part.php`. **Test responsive header/footer trước khi đi tiếp.**
2. **Phase 1 — Bất động sản (A1–A6):** A1 → A2 → A3 → A4 (kèm biệt thự) → A5 → A6.
3. **Phase 1 — Nội dung (B1–B8):** B1 → B2 → B3 → B4 → B5 → B6 → B7 → B8.
4. **Phase 2 — Trang mới Resi (C1–C3):** Service → Owner → EasyLiving (đọc controller .NET để chốt field).
5. **Phase 3 — SEO/hạ tầng:** sitemap, 301 redirect legacy, hreflang 3 ngôn ngữ, plugin sync cache
   (tái dùng `ppplus-seo-sync`), trang Settings.

Chia 2 dev song song: Dev A = nhóm Bất động sản (A) + EasyLiving (C3, có map); Dev B = Nội dung (B) +
Service/Owner (C1/C2). Quy ước tránh đụng code: không ai sửa `parts/header.php`, `parts/footer.php`,
`inc/api-client.php`, `inc/lang.php`, các `parts/*-card.php` — đổi thì báo người phụ trách Phase 0.
CSS riêng từng trang để file riêng, enqueue có điều kiện (`is_page_template(...)`), không đụng `main.css`.

---

## 6. Checklist khởi động (Phase 0)

- [x] Tạo khung theme `wp-content/themes/propertyplus-resi/` (`style.css` header, `functions.php`, `index.php`) — copy khung từ theme cũ + đổi tên/metadata Resi
- [x] Copy plugins `sqlite-database-integration` + `ppplus-seo-sync`
- [x] Copy `src/assets/{officebase,Content,icons}` → theme
- [x] Compile 6 file LESS (slick×2, icon, common, core, layout) + header/footer component LESS
- [x] `functions.php` enqueue CSS/JS **đúng thứ tự `angular.json`** (trỏ file .css/.js thật, bỏ bundle .min cũ của office)
- [x] `parts/header.php` — port 1:1 Angular Resi (mega menu quận/biệt thự/loại/dịch vụ/chủ nhà, 3 ngôn ngữ, footer-mobile bar). **Đã render thử với API thật: 21KB, 54 link.**
- [x] `parts/footer.php` — port 1:1 (liên hệ, tìm căn hộ, chính sách, social, 5 modal gọi/mail/chat + form tư vấn). **Đã render thử: 15KB, khớp field API.**
- [x] `inc/api-client.php` — domain → `admin.propertyplus.com.vn`, `pplus_current_lang()` (vi/en/ja), `pplus_localize()` (dịch qua `Home/GetLangApi`), `pplus_language_options/lang_prefix/lang_url`
- [x] `inc/language-route.php` — strip tiền tố `/en` `/ja`, hreflang 3 ngôn ngữ
- [x] `assets/js/resi-ui.js` — tương tác header/footer (menu mobile, submenu tap, modal)
- [ ] Cài WordPress core (Laragon) + `router.php` + `run-local.bat` (chạy local) — **việc thủ công của máy dev**
- [ ] `parts/apartment-card.php`, `parts/breadcrumb-part.php`, `parts/pagination.php` (component dùng chung, làm khi vào A1)
- [ ] Test responsive header/footer trên trình duyệt thật

## 7. Trạng thái & việc tiếp theo (cập nhật 2026-09-02)

**Đã xong (Phase 0, kiểm chứng với API `.com.vn` thật):** toàn bộ checklist mục 6 ở trên trừ 3 dòng cuối.
Field header/footer đã đối chiếu trực tiếp response `HeaderInvokeAsync`/`FooterInvokeAsync`/`GetLangApi` — khớp 100%.

**Còn lại — mỗi template là 1 khối riêng (đọc component Angular tương ứng để lấy đúng field):**
Theo thứ tự đề xuất: A1 danh sách căn hộ (`page-apartment-list.php` + `inc/apartment-list-route.php`,
API `Apartment/_GetApartments`+`_GetMarkers`) → A2 chi tiết (`Apartment/_GetDetail`) → A3/A4 landing
(`Reference/*`) → A6 street → B1 home (`front-page.php`, `Home/GetIndex`) → B2/B3 blog → B4/B5 about →
B6 contact → C1 service → C2 owner → C3 easy-living → SEO/sitemap/redirect + trang Settings.
Mỗi template cần: compile `.component.less` tương ứng → xoá `::ng-deep` → thêm enqueue có điều kiện
trong `functions.php` → viết `inc/*-route.php` (nếu route động) → viết `page-*.php`/`single-*.php`.

> Khi code tiếp, mở kèm 3 doc của dự án cũ (`../PPlusWordpress/docs/*`) để tra cách đã giải quyết
> từng phần (filter popup, Google Maps bounds, lightbox, advise-form...), chỉ đổi Office→Apartment.
