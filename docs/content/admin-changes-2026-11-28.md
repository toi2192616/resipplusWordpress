# Thay đổi dữ liệu trong Admin — 28/11/2026 (Dev B)

## 1. Rà bản dịch toàn bộ dự án

- Đọc 571 tòa (tòa đang có căn + 7 tòa không căn nhưng có bài), lần lượt từng lượt, 645 lượt đọc, 0 lỗi. Tiếng Anh/Nhật chỉ đọc khi tòa có bài tiếng Việt.
- 37 tòa có bài tiếng Việt: **tất cả đều có bài và tiêu đề tiếng Anh, tiếng Nhật** (3 tiêu đề Nhật dùng tên tòa chữ Latin — bình thường, giữ nguyên). Các tòa còn lại chưa có bài ở ngôn ngữ nào.
- Thiếu: ô "Giới thiệu ngắn" 16 bản Anh + 15 bản Nhật → đã dịch từ bản Việt (mục 2).
- Chưa phủ: trang khu vực chỉ có bản Việt (không nằm trong sitemap Anh/Nhật); bài viết từng căn hộ.

## 2. 31 ô "Giới thiệu ngắn" (trước đó đều trống → khôi phục = xóa trống)

Đã kiểm qua API: 31/31 đúng, mỗi bản ghi chỉ đổi đúng ô này. 5 câu Nhật đầu tiên ngắn hơn 60 ký tự (theme bỏ qua trên trang dự án) đã được viết dài thêm; bảng ghi bản cuối. Nội dung: `project-desc-2026-11-28.json`.

| Bản ghi | Tòa (id) | Ngôn ngữ | Mới |
|---|---|---|---|
| 91 | 2 | en | Vinhomes Royal City apartments have a modern European-style design, with a shopping mall, schools, swimming pools and many other amenities. |
| 91 | 2 | ja | ビンホームズ ロイヤルシティのアパートはモダンなヨーロッパ風のデザインで、ショッピングモールや学校、プールなど充実した施設がそろっております。 |
| 85 | 4 | en | Vinhomes Nguyen Chi Thanh: elegant, modern 5-star standard apartments for rent with many amenities, on one of Hanoi's main roads. |
| 85 | 4 | ja | ビンホームズ グエンチータインは、ハノイの主要道路沿いにある、充実した施設を備えた高級でモダンな5つ星基準の賃貸アパートでございます。 |
| 94 | 6 | en | Apartments for rent at Times City Park Hill have smart layouts and come fully furnished, for comfortable and convenient living. |
| 94 | 6 | ja | タイムズシティ パークヒルの賃貸アパートは、考え抜かれた設計で家具も完備しており、快適で便利な暮らしをお届けいたします。 |
| 86 | 11 | en | Vinhomes Gardenia apartments sit in the sought-after My Dinh urban area, with modern amenities for comfortable, high-quality living. |
| 86 | 11 | ja | ビンホームズ ガーデニアは人気のミーディン地区に位置し、モダンな施設がそろった快適で上質な暮らしをお楽しみいただけます。 |
| 56 | 15 | en | Ciputra Hanoi is a mixed-use urban area known for its high quality and international-standard public services. |
| 56 | 15 | ja | チプトラ ハノイは多機能な都市区で、国際基準の公共サービスを備えた質の高いエリアとして知られております。賃貸アパートをご紹介いたします。 |
| 129 | 16 | en | Apartments for rent at Discovery Complex offer many amenities and elegant, modern interiors, a great choice for international residents. |
| 129 | 16 | ja | ディスカバリー コンプレックスの賃貸アパートは、充実した施設と高級感のあるモダンな内装で、外国人の方におすすめでございます。 |
| 118 | 17 | en | Fully furnished 2- and 3-bedroom apartments for rent at Hong Kong Tower, 243A De La Thanh, Dong Da District, Hanoi. |
| 118 | 17 | ja | ホンコンタワー（ドンダー区デラタイン通り243A番地）の賃貸アパートは2～3寝室で、家具を完備しております。ぜひ一度ご覧ください。 |
| 103 | 32 | en | Indochina Plaza Hanoi, Cau Giay District: 2- to 4-bedroom apartments for rent, plus penthouses with their own indoor pool. |
| 103 | 32 | ja | カウザイ区のインドシナプラザハノイでは、2～4寝室の賃貸アパートのほか、室内プール付きのペントハウスもご用意しております。 |
| 55 | 38 | en | Oakwood Residence Hanoi in Tay Ho District: modern apartments fully fitted with elegant furniture, popular with foreign residents. |
| 55 | 38 | ja | タイホー区のオークウッド レジデンス ハノイは、モダンな設計に高級家具を完備したアパートで、多くの外国人の方に選ばれております。 |
| 57 | 42 | en | Hanoi Lake View has panoramic views over West Lake and Truc Bach Lake, is fully equipped and is popular with foreign guests. |
| 57 | 42 | ja | ハノイ レイクビューは西湖とチュックバック湖を一望できる好立地で、設備も充実しており、多くの外国人の方にご利用いただいております。 |
| 82 | 49 | en | Pan Pacific apartments on Thanh Nien Road, Ba Dinh, have a modern design that blends Asian style with French architecture. |
| 82 | 49 | ja | バーディン区タインニエン通りのパン パシフィックのアパートは、アジアのスタイルとフランス建築が調和したモダンなデザインでございます。 |
| 102 | 77 | en | Vinhomes Riverside is a large urban area in Long Bien, planned around green space with a wide range of premium amenities. |
| 102 | 77 | ja | ビンホームズ リバーサイドはロンビエン区にある大規模な住宅地で、緑豊かな街づくりと多彩な高級施設がそろっていることが特長でございます。 |
| 116 | 82 | en | Apartments for rent at Starlake Tay Ho Tay are a popular choice for foreigners living and working in Hanoi. |
| 108 | 148 | en | Apartments for rent at Vinhomes Skylake have modern designs and many on-site amenities, and are chosen by many foreign professionals. |
| 108 | 148 | ja | ビンホームズ スカイレイクの賃貸アパートは、モダンなデザインと充実した敷地内施設で、多くの外国人の専門職の方に選ばれております。 |
| 64 | 283 | en | Somerset West Point Hanoi: a convenient location, comfortable furnishings and West Lake views, designed to suit Asian and European guests. |
| 64 | 283 | ja | サマセット ウェスト ポイント ハノイは便利な立地と快適な家具、西湖の美しい眺めが魅力で、アジアとヨーロッパのお客様に合う設計でございます。 |
| 107 | 928 | en | Somerset Grand Hanoi apartments are in central Hanoi, close to well-known shopping and tourist areas, and popular with visitors. |
| 107 | 928 | ja | サマセット グランド ハノイはハノイの中心部にあり、有名な商業エリアや観光地に近く、多くのお客様にご好評いただいております。 |

## 3. Sửa lỗi nội dung

| Bản ghi | Chỗ sửa | Cũ | Mới |
|---|---|---|---|
| 82 (Pan Pacific) | Giới thiệu ngắn tiếng Việt | đường Thanh **Thiên** | đường Thanh **Niên** |
| 36 (Quận Ba Đình) vi | link "The Five Residences Hanoi (345 Đội Cấn)" | mở trang **Lotte** | `…/quan-ba-dinh/the-five-residences-hanoi-927` |
| 36 vi | 7 link dùng đường dẫn quận tiếng Anh | `/vi/…/ba-dinh-district/…` | `/vi/…/quan-ba-dinh/…` |
| 36 en | link "The Five Residences" (kèm 1 link rỗng liền trước) | mở trang **Lotte** | `…/ba-dinh-district/the-five-residences-hanoi-927` |

Form quận (bản ghi 36) không có ô toạ độ bản đồ (neLat…): đã đọc mã nguồn Admin (nhánh FontApiVer2, chỉ đọc) — nút Ghi chỉ gán từng ô, không đụng toạ độ; sau khi ghi đối chiếu: toạ độ, mọi trường khác giữ nguyên, bài viết đúng như dự tính. Bản tiếng Nhật không có link sai.

## 4. Đính chính về menu (nói ngày 27/11)

Link trang tổng quan Lotte nằm ở menu **Căn hộ tại Hà Nội → cột "Căn hộ Cao cấp"**, không phải cột "Tìm theo tòa nhà". Cả hai cột (mỗi cột 12 mục) đều dẫn tới trang tổng quan.
