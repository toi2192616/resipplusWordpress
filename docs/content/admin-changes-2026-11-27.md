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

## Dịch bổ sung blog (27/11, chiều tối) — 26 ô tóm tắt + 3 chỗ sửa lỗi

Kiểm qua API: 26/26 dòng đúng, chữ trong bài không đổi (trừ 1 câu ở #103). Khôi phục ô tóm tắt = xóa trống (trước đó đều trống). Nội dung mới: `blog-i18n-2026-11-27.json`.

| Bài | Ngôn ngữ | Ô | Cũ | Mới |
|---|---|---|---|---|
| 103 | en | tóm tắt | *(trống)* | Looking for an apartment to rent in Hanoi? Don't miss these six useful tips for foreigners. |
| 103 | en | 1 câu chú thích ảnh | Kết nối với Property Plus để tìm kiếm bất động sản phù hợp tại Hà Nội | Connect with Property Plus to find the right property in Hanoi |
| 103 | ja | tóm tắt | *(trống)* | ハノイで賃貸アパートをお探しの外国人の方に、ぜひ知っておいていただきたい6つのポイントをご紹介いたします。 |
| 104 | en | tóm tắt | *(trống)* | To choose the type of rental home in Hanoi that best suits your needs, explore the information below with Property Plus. |
| 104 | ja | tóm tắt | *(trống)* | ご希望に合った住まいの種類をお選びいただけるよう、ハノイで住宅を借りる際に知っておきたい情報をご紹介いたします。 |
| 106 | en | tóm tắt | *(trống)* | Where do most Koreans in Hanoi live, and what do these areas offer them? Find out here. |
| 106 | ja | tóm tắt | *(trống)* | ハノイにお住まいの韓国人の方は主にどのエリアに暮らし、そこにはどのような便利な施設があるのでしょうか。詳しくご紹介いたします。 |
| 107 | en | tóm tắt | *(trống)* | Which areas of Hanoi are ideal places to live, with many foreign residents? Property Plus answers in this article. |
| 108 | en | tóm tắt | *(trống)* | In Vietnam, can a tenant end a lease early on their own? Find out in this article. |
| 108 | ja | tóm tắt | *(trống)* | ベトナムでは、借主が賃貸借契約を期間の途中で一方的に解約することはできるのでしょうか。本記事でご説明いたします。 |
| 109 | en | tóm tắt | *(trống)* | Here are some areas of Hanoi with Japanese communities. Compare them and choose the place that suits you best. |
| 109 | ja | tóm tắt | *(trống)* | ハノイで日本人の方が多くお住まいのエリアをご紹介いたします。ご希望に合うお住まい探しのご参考になさってください。 |
| 110 | en | tóm tắt | *(trống)* | What should foreigners keep in mind to rent a Hanoi apartment with the right location, price and living environment? |
| 110 | ja | tóm tắt | *(trống)* | 立地や家賃、住環境がご希望に合うお部屋を借りるために、外国人の方に注意していただきたい点をご紹介いたします。 |
| 111 | en | tóm tắt | *(trống)* | A guide for foreigners to finding a rental apartment in Hanoi that meets their needs, without wasting time. |
| 111 | ja | tóm tắt | *(trống)* | 外国人の方がハノイでご希望に合う賃貸アパートをスムーズに見つけるための方法をご紹介いたします。 |
| 112 | en | tóm tắt | *(trống)* | A lease is a key document when renting an apartment. What should you check to protect your rights? |
| 112 | en | tiêu đề | How to find apartments in Hanoi for foreigners | Apartment Lease Contracts: What to Check to Protect Your Rights |
| 112 | ja | tóm tắt | *(trống)* | 賃貸借契約書は、お部屋を借りる際の大切な書類です。ご自身の権利を守るために確認すべき点をご紹介いたします。 |
| 112 | ja | tiêu đề | 福利を確保するためのマンション賃貸契約の注意点 | ご自身の権利を守るためのアパート賃貸借契約の注意点 |
| 113 | en | tóm tắt | *(trống)* | Finding a rental apartment that allows pets isn't easy. Here is how to go about finding one. |
| 113 | ja | tóm tắt | *(trống)* | ペットと一緒に暮らせる賃貸アパートを見つけるのは簡単ではありません。お部屋探しの方法をご紹介いたします。 |
| 217 | ja | tóm tắt | *(trống)* | 我が家のような快適さ、充実した設備、ホテルのようなサービスなど、サービスアパートがおすすめの5つの理由をご紹介いたします。 |
| 224 | ja | tóm tắt | *(trống)* | 金継ぎは、割れた陶磁器を漆で修復し、金や銀、プラチナの粉で装飾する伝統技法です。その魅力をご紹介いたします。 |
| 225 | ja | tóm tắt | *(trống)* | かまどの神様をお送りするベトナムの行事「オンコン・オンタオ」の由来と意味をご紹介いたします。 |
| 231 | ja | tóm tắt | *(trống)* | 新型コロナウイルス流行後、観光が再開された時期の情報をもとに、ベトナム旅行の安全性と注意点をご紹介いたします。 |
| 234 | en | tóm tắt | *(trống)* | Planning to move to Vietnam? What foreigners should know first, from safety, language and living costs to finding a home. |
| 236 | en | tóm tắt | *(trống)* | A look at some of the newest apartments for rent in Hanoi, with their locations and on-site amenities (2022). |
| 236 | ja | tóm tắt | *(trống)* | ハノイの新しい賃貸アパートの一部を、立地や敷地内の施設とあわせてご紹介いたします（2022年版）。 |

### Kết quả rà bài thiếu bản dịch (27/11)

- Blog: 63/63 bài có nội dung tiếng Anh và tiếng Nhật (7 bài JA bị máy gắn cờ là bài chỉ có link video hoặc có địa chỉ viết tiếng Anh).
- Trang khu vực: 142 trang EN/JA trong `sitemap_quan` + `sitemap_type` và 12 trang trên menu đều đúng ngôn ngữ.
- Bài viết dự án: lấy MẪU 60 tòa (5 tòa trên menu có mã dự án + 55 ngẫu nhiên): 7 tòa có bài tiếng Việt, cả 7 đủ EN/JA; 53 tòa chưa có bài ở tiếng nào. Chưa quét toàn bộ (~1.700 lượt đọc API, cần chủ site đồng ý). Trang khu vực chỉ có bản Việt sẽ không nằm trong sitemap EN/JA nên cách rà này không thấy.
- Trang căn hộ (mẫu 12 căn × EN/JA): chữ Việt còn lại là tên/địa chỉ địa điểm lân cận.
