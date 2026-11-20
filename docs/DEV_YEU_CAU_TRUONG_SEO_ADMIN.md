# Yêu cầu Dev: ô SEO riêng cho Dự án / Căn hộ trong Admin + vệ sinh sitemap

Ngày 20/11/2026. Người đề xuất: SEO Propertyplus (theo ý chủ site: "dự án/căn hộ nào điền trong Admin thì lấy trong Admin, không điền thì lấy mặc định như hiện tại").

## 0. CẬP NHẬT 21/11/2026 — đọc trước (thu hẹp phạm vi yêu cầu)

Đối chiếu lại với Admin thật: **với DỰ ÁN, các ô cần thiết đã có sẵn** trong "Bài viết Dự án" (mỗi dự án có nút sửa → `DataList/EditOrCreateVP?id={projectId}&type=4`; mở lần đầu Admin tự tạo bản ghi trống #id với Tiêu đề = tên dự án). Resi (theme) đã đọc các ô đó qua `Project/Detail → dataSelected`:

| Ô trong Admin (bản ghi bài viết dự án) | Resi dùng làm gì |
|---|---|
| **Tiêu đề** | `<title>` trang dự án (khi chưa có Meta Title và khác tên dự án) — ví dụ Vinhomes Metropolis (#26): title = "Căn Hộ Cho Thuê Ở Vinhomes Metropolis - 20 Liễu Giai, Ba Đình" |
| **Giới thiệu ngắn** | meta description (từ gói 20d, khi ≥ 60 ký tự; trước đó bị bỏ qua, meta description lặp lại tiêu đề) |
| Tab **Hỗ trợ SEO**: Meta Title, Meta Description | ưu tiên cao nhất cho `<title>` và meta description (chỉ bản tiếng Việt; EN/JA dùng Tiêu đề + Giới thiệu ngắn của dòng ngôn ngữ) |
| **Nội dung** | bài viết hiển thị dưới danh sách căn hộ |
| Tiêu đề rút gọn, Hiển thị Menu | KHÔNG phải SEO: tên ngắn ở thẻ/menu; nhóm menu ("Theo tòa nhà" = 12 tòa tuyển chọn trong menu) |

→ **Dev KHÔNG cần thêm `seoTitle` / `seoDescription` / `seoIntro` cho dự án**: nhân viên điền các ô trên là Resi dùng ngay (không phải chép thêm code). Phần còn thật sự thiếu và cần Dev:
1. **Căn hộ (Apartment):** không có bản ghi bài viết nào (`dataSelected` luôn rỗng) → cần `seoTitle`, `seoDescription` (hoặc dùng ô `Description` sẵn có) như mục 2 bên dưới.
2. *(tùy chọn)* Dự án: ô **Index** ở tab Hỗ trợ SEO hiện Resi chưa đọc ở trang dự án (chỉ đọc ở trang đích quận/loại). Muốn "không index dự án này" thì Resi cần đọc ô đó — **chưa bật** vì phải kiểm dữ liệu trước (ô chưa tích có thể đang là `false` ở nhiều bản ghi → bật sẽ noindex nhầm hàng loạt). Nếu cần, dùng `seoNoIndex` mới như mục 2.
3. *(tùy chọn)* Dự án: Meta Title / Meta Description riêng cho EN và JA (hiện chỉ có Tiêu đề + Giới thiệu ngắn ở dòng ngôn ngữ — vẫn dùng được).

**Lưu ý vận hành:** mở nút sửa của dự án chưa có bài viết sẽ tạo bản ghi trống (hiện chưa ảnh hưởng gì). Theme (gói 21f) đã xử lý: Tiêu đề trùng tên dự án được coi là chưa nhập nên tiêu đề tự sinh vẫn dùng.

## 1. Vì sao cần

Search Console 20/11: Google đã thu thập các trang dự án/căn hộ của Resi nhưng **chưa lập chỉ mục** (nhóm "đã thu thập – chưa lập chỉ mục" tăng 665 → 1.030). Nguyên nhân chính nằm ở nội dung: title chỉ là tên dự án, mô tả 11–27 ký tự, trang mỏng.

- **Hiện web tự sinh title/mô tả từ dữ liệu** (đã làm, chờ deploy: ví dụ "Căn Hộ Cho Thuê Tại Le Capitole - Quận Đống Đa, Hà Nội | Property Plus"). Cách này đủ tốt cho ~1.600 trang thường, **nhưng với các dự án/căn hộ quan trọng nhân viên muốn tự viết** (từ khóa, điểm bán hàng, tiếng Nhật chuẩn).
- **Admin hiện không có chỗ nhập SEO ở cấp dự án và căn hộ.** Chỉ khi dự án có "bài viết" (khoảng 2% dự án) mới có `metaTitle`/`metaDescription`/`isIndex`; căn hộ thì `dataSelected` luôn rỗng. Ô "Mô tả" của căn hộ (`description`) có sẵn nhưng 0/15 căn mẫu được điền.

## 2. Đề xuất (phần Backend + Admin do bên kỹ thuật làm — chủ site hoặc Dev; phần web đã code sẵn)

### 2.1 Trường mới
Thêm cho **Dự án (Project)** và **Căn hộ (Apartment)**, mỗi trường **riêng cho từng ngôn ngữ vi / en / ja**:

| Trường | Kiểu | Gợi ý độ dài | Dùng làm gì |
|---|---|---|---|
| `SeoTitle` | text | ≤ 70 ký tự | thẻ `<title>` |
| `SeoDescription` | text | 120–160 ký tự | thẻ `meta description` |
| `SeoIntro` | text thuần (không HTML) | 300–1.500 ký tự | đoạn giới thiệu đầu khối "Thông tin tòa nhà" của trang dự án (chỉ Dự án) |
| `SeoNoIndex` | checkbox | – | tích = trang trả `noindex` (dùng cho dự án/căn hộ không muốn lên Google) |

Với **Căn hộ** có thể dùng luôn ô `Description` đã có (web đã hiển thị ô này trên trang và nay dùng làm meta description khi ≥ 60 ký tự); chỉ cần thêm `SeoTitle` nếu muốn.

### 2.2 API trả về
Trong 2 API đã có, thêm vào đối tượng chi tiết (tên JSON **đúng như bảng**, camelCase):

- `GET {lang}/Project/Detail?id=…` → `result.projectDetail.seoTitle`, `seoDescription`, `seoIntro`, `seoNoIndex`
- `GET {lang}/Apartment/_GetDetail?…` → `result.apartmentDetail.seoTitle`, `seoDescription`, `seoNoIndex`

Quy tắc: **trống thì trả chuỗi rỗng/`null`**. **Không tự lấy tiếng Việt thay cho tiếng Anh/Nhật khi bản en/ja chưa nhập** (web sẽ dùng nội dung tự sinh đúng ngôn ngữ).

### 2.3 Màn hình Admin
Trong form Dự án và form Căn hộ thêm khối **"SEO"** (không bắt buộc): 3 tab VI / EN / JA, mỗi tab 3 ô như trên có **bộ đếm ký tự** (cảnh báo khi > 70 với title, > 160 với mô tả), cộng 1 checkbox "Không cho Google lập chỉ mục". Không cần bước duyệt.

### 2.4 Web đã xử lý sẵn (commit trên `origin/main`, khớp tên trường ở 2.2)
Thứ tự ưu tiên, **ô nào có giá trị thì dùng, không thì xuống dòng dưới**:

| | 1 | 2 | 3 | 4 (mặc định hiện tại) |
|---|---|---|---|---|
| Title dự án | `seoTitle` | `metaTitle` bài viết dự án | tiêu đề bài viết | tự sinh từ dữ liệu |
| Mô tả dự án | `seoDescription` | `metaDescription` bài viết | mô tả viết tay của bài viết (≥ 60 ký tự) | tự sinh từ dữ liệu |
| Title căn hộ | `seoTitle` | `metaTitle` | – | tự sinh (loại phòng, giá, quận, ID) |
| Mô tả căn hộ | `seoDescription` | `metaDescription` | ô `description` (≥ 60 ký tự) | tự sinh từ dữ liệu |
| Giới thiệu tòa nhà | `seoIntro` | – | – | câu tự sinh từ dữ liệu |
| Index | `seoNoIndex` = true → `noindex, follow` | dự án "vỏ" (0 căn đăng, chưa có bài) → `noindex` | – | `index` |

**Trước khi Backend có trường mới web hoàn toàn bình thường** (không lỗi, dùng nội dung tự sinh). Khi Dev thêm trường đúng tên là có hiệu lực ngay, không phải sửa web. Đã kiểm bằng giả lập backend có/không có trường, cả 3 ngôn ngữ.

## 3. Sitemap (nối tiếp mục 2A/2B đã trao đổi) — Back sinh sitemap cần lọc thêm

Web đã tự lọc ở phía mình (bản deploy sắp tới) nhưng **gốc nên sửa ở bộ sinh sitemap** của Back. Kiểm toán 206 URL trong `sitemap_quan.xml` và `sitemap_type.xml` (file `docs/content/landing-audit-2026-11-20.csv`):

1. **Bản ghi Hoạt động / Báo chí / Khám phá cũng mở được bằng đường dẫn trang đích** (`loai` = 90/91/92): EN 18, JA 19 trang (văn hóa công ty, team building, hiến máu, quyên sách, phỏng vấn lãnh đạo, "Quy tắc bấm chuông cửa"…). Đây là dữ liệu thật của các mục đó trong Admin, **không xóa**; nhưng ở đường dẫn trang đích chúng có **nội dung rỗng (0–18 ký tự)**, trang chỉ là danh sách căn hộ chung với tiêu đề lạc đề; 28 trang đang để `index`. → Không đưa vào sitemap trang đích: **chỉ lấy `loai` ∈ {1,2,3,4,9}** và `isIndex = true`.
2. **URL 404/410 trong sitemap**: 3 alias EN 404 (`book-donation-at-the-children-hopital`, `voluntary-blood-donation`, `fdfdf`) và 6 trang `Villa-*` trả 410 (EN 3, JA 3, `loai` = 8) → bỏ.
3. **URL lặp** ngay trong cùng một file (vd `quan-tay-ho` xuất hiện 3 lần; `sitemap_quan` 319 dòng chỉ 201 URL duy nhất, `sitemap_type` 301 dòng / 195) → mỗi URL một lần.
4. Trang `loai` = 5 (`03-bedrooms-apartment-for-rent-in-r2-royal-city-ready-to-move-in`) Admin đang tắt index nhưng vẫn nằm trong sitemap → bỏ khi `isIndex = false`.
5. `lastmod` hiện là cùng một thời điểm cho mọi URL (`2026-11-17T09:54:52`); nên là ngày sửa thật của bản ghi.

Dev chỉ cần lọc sitemap như trên; không xóa dữ liệu Hoạt động / Báo chí / Khám phá.

## 4. Nghiệm thu

```bash
# 4.1 Sau khi Dev thêm trường: nhập thử SeoTitle cho 1 dự án, rồi
curl -s https://propertyplus.com.vn/vi/cho-thue-can-ho/ha-noi/quan-dong-da/le-capitole-1721 | findstr /i "<title>"   # phải ra đúng SeoTitle đã nhập
# 4.2 tích "Không index" -> phải có noindex
curl -s https://propertyplus.com.vn/vi/cho-thue-can-ho/ha-noi/quan-dong-da/le-capitole-1721 | findstr /i "noindex"
# 4.3 API trả trường mới
curl -s "https://admin.propertyplus.com.vn/vi/Project/Detail?id=1721"   # có "seoTitle", "seoDescription", "seoIntro", "seoNoIndex"
# 4.4 sitemap: không còn alias rác, không trùng
curl -s https://propertyplus.com.vn/sitemaps/sitemap_quan.xml | findstr /i "a-great-day Villa- fdfdf"   # không ra dòng nào
```

Dev B kiểm tra lại cả 3 ngôn ngữ sau khi Dev A xong.

## 5. Câu hỏi cho Dev

- `dataSelected` có các trường `titleGoogleSeo` và `keyGoogleSeo` (đang `null` ở mọi bản ghi): hệ thống cũ có dùng không? Nếu có thì có thể dùng chung thay vì tạo mới.
- Trường `Description` của căn hộ có đang lưu theo từng ngôn ngữ không (API en/ja trả bản dịch hay bản Việt)?
- Ước lượng công: thêm 4 cột × 3 ngôn ngữ cho 2 bảng, hiển thị trong form, trả qua 2 API; sửa bộ sinh sitemap.
