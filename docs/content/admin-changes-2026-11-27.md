# Thay đổi dữ liệu trong Admin — 27/11/2026 (Dev B): SEO theo tên tòa nhà

Đã kiểm qua API: 17/17 giá trị mới đang chạy, không ô nào khác thay đổi (nội dung bài giống từng ký tự). Giá trị cũ ghi ở đây để khôi phục.

Mỗi tòa: trang dự án và trang giới thiệu **dùng chung 1 bản ghi** (Admin: `/admin/DataList/EditOrCreate/{bản ghi}`). Tiếng Việt chỉ sửa ô MetaTitle / MetaDescription (tab Hỗ trợ SEO của form chính); tiếng Anh/Nhật chỉ sửa ô "Giới thiệu ngắn" (Description) của dòng ngôn ngữ.

## Số đo trước khi sửa (Search Console, 26/8–25/11, trang có tên tòa trong URL)

| Tòa | Nhấp | Hiển thị | Trang chính Google chọn | Cụm truy vấn chính |
|---|---|---|---|---|
| Lotte | 58 | 2.170 | trang dự án VI | cho thuê căn hộ lotte liễu giai, chung cư lotte liễu giai, lotte the residence hanoi |
| The Authentic | 38 | 1.050 | đường dẫn CŨ `…/the-authentic-280` (26 nhấp / 695), trang giới thiệu VI | the authentic haseko |
| Grandeur Palace | 16 | 1.360 | trang giới thiệu VI (1.207) | cho thuê chung cư grandeur palace giảng võ, grandeur palace |
| The Five Residences | 20 | 633 | trang dự án VI | the five residences hanoi |
| Fraser (2 tòa) | 17 | 568 | Fraser Suites: trang dự án VI | fraser suites |

Tiếng Nhật gần như chưa có hiển thị cho các tên này.

## Giá trị cũ → mới

| Bản ghi | Tòa | Ngôn ngữ | Ô | Cũ | Mới |
|---|---|---|---|---|---|
| 59 | Lotte The Residence | vi | MetaTitle | *(trống)* | Cho Thuê Căn Hộ Lotte Liễu Giai – Lotte The Residence Hà Nội |
| 59 | Lotte The Residence | vi | MetaDescription | *(trống)* | Cho thuê căn hộ Lotte Liễu Giai (54 Liễu Giai, Ba Đình): căn hộ dịch vụ 1–4 phòng ngủ, 64–180 m² trong tòa Lotte Center, có dọn phòng, bảo vệ 24/7. |
| 59 | Lotte The Residence | en | Description | *(trống)* | Serviced apartments for rent at Lotte The Residence Hanoi, 54 Lieu Giai, Ba Dinh: 1–4 bedrooms, 64–180 m², in Lotte Center, with housekeeping included. |
| 59 | Lotte The Residence | ja | Description | *(trống)* | ロッテ レジデンス ハノイ（バーディン区リューザイ通り54番地）の賃貸サービスアパートです。ロッテセンター内の1～4寝室・64～180m²のお部屋で、清掃サービスが含まれております。 |
| 1264 | The Authentic Haseko | vi | MetaDescription | *(trống)* | The Authentic Haseko (Long Biên): căn hộ dịch vụ chất lượng Nhật Bản của tập đoàn Haseko, 1–2 phòng ngủ, 58–124 m², có ăn sáng, dọn phòng, giặt là. |
| 1264 | The Authentic Haseko | en | Description | With the criteria of "Japanese Quality," The Authentic Haseko brings the perfect living experience to the high-end foreign business community. | The Authentic Haseko, Long Bien: Japanese-quality serviced apartments by Haseko, 1–2 bedrooms, 58–124 m², with breakfast, housekeeping and laundry. |
| 1264 | The Authentic Haseko | ja | Description | 「日本品質」を基準にザオーセンティック長谷工はハイクラスの外国人のコミュニティに最適なライフスタイルを提供する。 | 長谷工コーポレーションが手がける、ロンビエン区の日本品質の賃貸サービスアパートです。1～2寝室・58～124m²で、朝食・清掃・洗濯のサービスが含まれております。 |
| 1267 | Grandeur Palace | vi | MetaTitle | *(trống)* | Cho Thuê Chung Cư Grandeur Palace Giảng Võ, Ba Đình |
| 1267 | Grandeur Palace | vi | MetaDescription | *(trống)* | Cho thuê chung cư Grandeur Palace Giảng Võ (138B Giảng Võ, Ba Đình): tòa 22 tầng do Văn Phú - Invest đầu tư, có bể bơi, siêu thị, bảo vệ 24/7. |
| 1267 | Grandeur Palace | en | Description | Grandeur Palace is not just an ordinary housing project, it is an architectural masterpiece combining villas and luxury apartments. Invested wholeheartedly by investor Van Phu Invest, the project is an ideal destination for commercial customers. | Apartments for rent at Grandeur Palace, 138B Giang Vo, Ba Dinh: a 22-floor building by Van Phu - Invest with a swimming pool, supermarket and 24/7 security. |
| 1267 | Grandeur Palace | ja | Description | グランドールパレスは普通のアパートのプロジェクトだけではなく別荘と高級な家賃アパートを組み合わせた建築傑作品でもある。ヴァンフーインベスト投資主が全力で投資したこのプロジェクトは商業顧客にとって理想的な目的地である。 | グランドールパレス ジアン・ヴォー（バーディン区ジアン・ヴォー138B）の賃貸アパートです。ヴァンフー・インベストが開発した22階建てで、プール、スーパー、24時間警備がございます。 |
| 69 | The Five Residences | ja | Description | ファイブレジデンスハノイは345ドイカン、バーディンに位置する5つ星基準の高級なアパートであり、専門家、上級職員に適している。 | ファイブ レジデンス ハノイ（バーディン区ドイカン通り345番地）の賃貸サービスアパートです。1～3寝室・65～131m²。屋内プールやジムを備え、朝食と清掃サービスが含まれております。 |
| 53 | Fraser Suites | en | Description | *(trống)* | Fraser Suites Hanoi serviced apartments at 51 Xuan Dieu, Tay Ho: studios to 4 bedrooms, 40–569 m², with an outdoor pool, breakfast and housekeeping. |
| 53 | Fraser Suites | ja | Description | *(trống)* | フレイザー スイーツ ハノイ（タイホー区スアン・ディウ通り51番地）の賃貸サービスアパートです。スタジオ～4寝室・40～569m²。屋外プールを備え、朝食と清掃サービスが含まれております。 |
| 1261 | Fraser Residence | vi | MetaDescription | *(trống)* | Cho thuê căn hộ dịch vụ Fraser Residence Hà Nội (C5 Đỗ Nhuận, Bắc Từ Liêm): studio đến 3 phòng ngủ, 40–100 m², bể bơi ngoài trời, bảo vệ 24/7. |
| 1261 | Fraser Residence | en | Description | Fraser Residence project rents modern, high-end serviced apartments, attracting many interested customers in the West of Tay Ho. | Serviced apartments for rent at Fraser Residence Hanoi, C5 Do Nhuan, Bac Tu Liem: studios to 3 bedrooms, 40–100 m², outdoor pool and 24/7 security. |
| 1261 | Fraser Residence | ja | Description | フレイザー レジデンス プロジェクトは、モダンなハイエンドのサービスアパートメントを提供しており、タイホー西部に興味を持つ多くの顧客を惹きつけています。 | フレイザー レジデンス ハノイ（バクトゥーリエム区ドーニュアン通りC5）の賃貸サービスアパートです。スタジオ～3寝室・40～100m²で、屋外プール、フロント、24時間警備がございます。 |

Dữ liệu dùng để viết: địa chỉ, số tầng, loại phòng, diện tích, tiện ích và dịch vụ trong chính dữ liệu dự án + bài viết của web. Không đưa giá và số căn trống (thay đổi liên tục).

## Việc còn lại phát hiện trong lúc làm

- Mô tả tự sinh (khi Admin để trống) bị lỗi câu: tiếng Nhật `…m²。です。`, tiếng Anh `…m2..` (theme, `inc/seo-listing-meta.php`).
- Trang dự án chấp nhận **mọi alias** miễn đúng id và tự khai canonical theo chính nó (vd `/vi/…/the-authentic-280` và `/…/the-authentic-haseko-280` cùng index) → Google đang dồn vào đường dẫn cũ.
- Trang dự án và trang giới thiệu cùng tiêu đề/mô tả (chung bản ghi) → tự cạnh tranh; cần quyết định canonical trong code sau khi có số đo.

## Kiểm tra URL trên Search Console (27/11)

- `…/vi/cho-thue-can-ho/ha-noi/grandeur-palace-giang-vo`: đang trên Google → **đã yêu cầu lập chỉ mục lại**.
- `…/quan-ba-dinh/lotte-the-residence-hanoi-80` (trang dự án): **không trên Google** — "Trùng lặp, Google chọn trang chính tắc khác": Google chọn trang giới thiệu `…/tong-quan-ve-can-ho-cho-thue-o-lotte-the-residence-ha-noi` (đọc 26/11).
- Trang giới thiệu Lotte: đang trên Google → **đã yêu cầu lập chỉ mục lại**.

Đề xuất (cần chủ site đồng ý, sửa trong code theme): trang giới thiệu và trang dự án chung 1 bản ghi thì khai **một** canonical. Ưu tiên trỏ về trang dự án (có căn đang cho thuê, nằm trong sitemap và trang "Tất cả tòa nhà"); trang giới thiệu vẫn mở được, không 404, không đổi đường dẫn. Làm thử với vài tòa, đo 2–4 tuần rồi mới áp dụng rộng.
