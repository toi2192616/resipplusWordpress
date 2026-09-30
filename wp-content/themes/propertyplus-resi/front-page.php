<?php
/**
 * Trang chu - port tu src/app/modules/home/home.component.html (PPlusAngularResi), doi chieu truc
 * tiep voi propertyplus.com.vn. Nguon du lieu: {lang}/Home/GetIndex (pplus_get_home_data()) +
 * {lang}/Home/_GetApartments cho tung nhom can ho/biet thu (pplus_get_home_apartment_groups(),
 * inc/api-client.php) - GetIndex chi tra ve "nhom" (tieu de + bo loc jsonSearch), KHONG kem san
 * danh sach, phai goi rieng tung nhom.
 *
 * Ban truoc day cua file nay la nguyen ban trang chu theme Van phong copy sang (field du lieu
 * listOffice/dataList/listBlogNews KHONG ton tai o API Resi, CSS la CSS bien dich cua theme Van
 * phong chua tung duoc thay bang home.component.less that cua Resi) - toan bo da duoc thay the.
 *
 * TODO chua lam (ghi ro de khong nham da xong):
 * - Form tim kiem (hero) chi lam ban tinh 2 truong (Dia diem/Chi tiet), khong co popup goi y ten du
 *   an/quan/gia/phong nhu Angular that (search-home.component.html) - lam cung luc voi trang danh
 *   sach can ho (A1, chua lam) vi 2 phan dung chung logic filter, giong cach ban Van phong da lam
 *   voi ly do tuong tu.
 * - Nut yeu thich (.item-like) tren card can ho: moi hien icon, chua noi API luu yeu thich that
 *   (Resi chua co endpoint/JS tuong duong assets/js/pplus-favourite.js cho can ho).
 * - "Xem tất cả" cua tung nhom can ho tro toi /cho-thue-can-ho/ha-noi/{alias} - route nay thuoc
 *   A1/A4, CHUA duoc dung (se 404 cho toi khi lam A1), giong cach ban Van phong da lam (link
 *   truoc, dung route sau).
 *
 * KHONG con muc "Biet thu cho thue" (menu + khoi trang chu) - quyet dinh chien luoc cua nguoi
 * dung 2026-09-13, bo hoan toan san pham biet thu khoi web Resi. Xem cung quyet dinh trong
 * parts/header.php.
 */
if (!defined('ABSPATH')) exit;

get_header();

$home        = pplus_get_home_data();
$lang        = pplus_current_lang();
$lang_prefix = pplus_lang_prefix($lang);

if (!empty($home['datalistApartment'])) {
    $home['datalistApartment'] = pplus_get_home_apartment_groups($home['datalistApartment']);
}
// datalistApartmentVila (biet thu) KHONG con goi/hien thi nua - quyet dinh chien luoc cua nguoi
// dung (2026-09-13): bo hoan toan san pham biet thu khoi web Resi (menu + khoi trang chu). Neu can
// khoi phuc: xem lich su git file nay + parts/header.php cung ngay.

/**
 * In 1 khoi "nhom can ho" (tieu de + carousel).
 */
function pplus_render_home_apartment_groups(array $groups, string $list_slug, string $lang_prefix): void {
    foreach ($groups as $group) {
        $data = $group['data'] ?? [];
        if (empty($group['listApartments'])) {
            continue;
        }
        ?>
        <div class="col-md-12 p-0">
            <div class="_interesting-title-data">
                <h3><?php echo esc_html($data['title'] ?? ''); ?></h3>
                <a class="see-all" href="<?php echo esc_url(home_url($lang_prefix . '/' . $list_slug . '/ha-noi/' . ($data['alias'] ?? ''))); ?>">
                    <?php echo esc_html(pplus_localize('Xem tất cả')); ?> &gt;&gt;
                </a>
            </div>
            <div class="_interesting-silder lazy-img-loading">
                <div class="offices-col stick-custom-css">
                    <?php // "apt-card-slide" boc ngoai la lop VO TRONG SUOT rieng cho Slick - .item-office
                    // (the trang tri: nen trang/bo goc/do bong) khong duoc dung lam slide truc tiep, vi
                    // Slick GAN class "slick-slide" + khoang cach (padding) THANG len phan tu no nhan lam
                    // slide; neu do la .item-office thi padding chi thu nho ANH ben trong, con khung nen
                    // trang/bong van dinh sat khung ben canh (bug thuc te da gap, doi chieu voi ban that:
                    // ca khoi trang di chuyen cung nhau, co khoang cach that giua 2 khoi). ?>
                    <?php foreach ($group['listApartments'] as $item) : ?>
                        <div class="apt-card-slide">
                            <?php get_template_part('parts/apartment-card', null, ['item' => $item]); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
    }
}
?>

<?php if ($home) : ?>

<div class="langdingpage-header">
    <div class="bg-img">
        <picture>
            <source media="(max-width: 800px)" type="image/webp" srcset="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/home-600x1000.webp'); ?>" />
            <source media="(max-width: 800px)" srcset="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/home-600x1000.png'); ?>" />
            <source type="image/webp" srcset="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/home-1920x1080.webp'); ?>" />
            <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/home-1920x1080.png'); ?>" alt="<?php echo esc_attr(pplus_t('Căn hộ cho thuê cao cấp tại Hà Nội', 'Luxury apartments for rent in Hanoi')); ?>" width="1920" height="1080" fetchpriority="high" decoding="async" />
        </picture>
    </div>
    <div class="bg-search">
        <div class="container">
            <div class="search-tab" id="search-container">
                <h1 class="content-1"><?php echo esc_html(pplus_localize('Property Plus at home anywhere')); ?></h1>
                <div class="content-2"><?php echo esc_html(pplus_localize('TRẢI NGHIỆM SỐNG TUYỆT VỜI')); ?></div>
                <div class="content-3"><?php echo esc_html(pplus_localize('Như một quản gia riêng của bạn, tất cả các căn hộ mà chúng tôi cung cấp dịch vụ, dù ở bất kỳ đâu cũng đều trở thành căn hộ tiêu chuẩn 05 sao, giúp bạn dễ dàng tận hưởng cuộc sống như người bản địa.')); ?></div>

                <?php // Ban tinh gian luoc (2 truong) - xem TODO dau file. ?>
                <div class="form-search-destop">
                    <div class="tab-content">
                        <div class="tab-pane box-search-pane fade show active">
                            <div class="searching-bar">
                                <form action="<?php echo esc_url(home_url($lang_prefix . '/cho-thue-can-ho/')); ?>" method="get" autocomplete="off" id="office-form" class="office-form">
                                    <div class="box-search row">
                                        <div class="col-md-5 search-input-group">
                                            <div class="search-option">
                                                <div class="search-controls">
                                                    <div class="control">
                                                        <label class="search-label" for="pplus-home-search-key"><?php echo esc_html(pplus_localize('Nhập địa điểm')); ?>...</label>
                                                        <input class="search-input form-control" id="pplus-home-search-key" type="text" name="search_key" placeholder="<?php echo esc_attr(pplus_localize('Bạn muốn đi đâu')); ?>" />
                                                    </div>
                                                </div>
                                                <?php // Goi y khi go (pplus-search-suggest.js dien noi dung + hien/an). ?>
                                                <div class="focus-popup first-popup" id="pplus-home-suggest" style="display:none"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-5 search-input-group">
                                            <div class="search-option">
                                                <div class="search-controls">
                                                    <div class="control">
                                                        <label class="search-label" for="pplus-home-search-detail"><?php echo esc_html(pplus_localize('Chi tiết')); ?></label>
                                                        <input class="search-input form-control" id="pplus-home-search-detail" type="text" name="detail" placeholder="<?php echo esc_attr(pplus_localize('Chi tiết căn hộ của bạn')); ?>" />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <button type="submit" class="btn btn-primary btn-search pull-right" aria-label="Search">
                                                <i class="fa fa-search" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($home['datalistApartment'])) : ?>
<div class="offices-engine backgroup-style">
    <div class="container">
        <div class="row _interesting">
            <div class="col-md-12">
                <h2 class="_interesting-title"><?php echo esc_html(pplus_localize('Căn hộ cho thuê ở Hà Nội')); ?></h2>
            </div>
            <hr class="_interesting-hr-1" />
            <?php pplus_render_home_apartment_groups($home['datalistApartment'], 'cho-thue-can-ho', $lang_prefix); ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="why-choose-us">
    <div class="container">
        <h2 class="a-u-cus-t1"><?php echo esc_html(pplus_localize('Property Plus có gì khác biệt?')); ?></h2>
        <hr class="a-u-cus-hr" />
        <div class="row col-md-12">
            <?php
            $difference_items = [
                ['Group12.png', pplus_localize('Mạng lưới căn hộ đa dạng'), pplus_localize('Chúng tôi quản lý mạng lưới căn hộ rộng khắp Hà Nội, đa dạng về loại hình (biệt thự, căn hộ dịch vụ, chung cư, ...), đa dạng mức ngân sách, nhằm đáp ứng nhu cầu của nhiều nhóm khách hàng.')],
                ['Group10.png', pplus_localize('Cá nhân hóa dịch vụ tiêu chuẩn 05 sao'), pplus_localize('Với sự am hiểu về văn hóa của từng quốc gia, cá nhân hóa dịch vụ tới từng khách hàng. Property Plus đảm bảo mang đến chất lượng dịch vụ 5 sao làm hài lòng tất cả các khách hàng.')],
                ['Group14.png', pplus_localize('Ứng dụng công nghệ cao'), pplus_localize('Property Plus phát triển nền tảng công nghệ tích hợp trong quản lý số lượng lớn căn hộ (dịch vụ, căn hộ chung cư) với tốc độ và hiệu suất tối ưu.')],
                ['Group11.png', pplus_localize('Văn hóa phục vụ tận tâm'), pplus_localize('Ở Property Plus, chúng tôi luôn dành trọn sự tận tâm đến từng khách hàng.')],
            ];
            foreach ($difference_items as $i => $feature) : ?>
                <div class="col-md-3 a-u-c-1-item">
                    <img class="lazy" src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/ImgAboutUs/' . $feature[0]); ?>" width="100" height="100" alt="img home" loading="lazy" />
                    <div class="a-u-cus-t3 text-center"><?php echo ($i + 1) . '. ' . esc_html($feature[1]); ?></div>
                    <div class="a-u-cus-t4"><?php echo esc_html($feature[2]); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="achievement-box">
    <div class="container">
        <h2 class="a-u-cus-t1"><?php echo esc_html(pplus_localize('Tầm nhìn - Phương châm - Sứ mệnh')); ?></h2>
        <hr class="a-u-cus-hr" />
        <div class="row col-md-12">
            <?php
            $mission_items = [
                [pplus_localize('Tầm nhìn'), pplus_localize('Property Plus nỗ lực để trở thành công ty số 01 Việt Nam chuyên cung cấp giải pháp toàn diện về nhà ở cho người nước ngoài (tại Việt nam) với dịch vụ quản gia chuyên nghiệp tiêu chuẩn 05 sao.')],
                [pplus_localize('Phương châm'), pplus_localize('Property Plus cố gắng đem đến cho những người bạn quốc tế không gian sống đầy đủ tiện nghi, thoải mái và cảm giác được đón chào ngay từ khoảnh khắc bước qua cánh cửa.')],
                [pplus_localize('Sứ mệnh'), pplus_localize('Property Plus tâm huyết mang lại lợi ích cho cả chủ thuê và khách thuê, đồng thời nâng cao thu nhập cho người lao động, góp phần phát triển kinh tế Việt Nam.')],
            ];
            foreach ($mission_items as $i => $mission) : ?>
                <div class="col-md-4 a-u-c-2-item<?php echo $i === 0 ? ' ml-0' : ''; ?>">
                    <div class="a-u-step"><?php echo (int) ($i + 1); ?></div>
                    <div class="a-u-cus-t3 font-size20 text-center"><?php echo esc_html($mission[0]); ?></div>
                    <div class="a-u-cus-t4"><?php echo esc_html($mission[1]); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="achievement-box-2">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h2 class="srv-title1"><?php echo esc_html(pplus_localize('Dịch vụ chất lượng 5 sao')); ?></h2>
                <div class="srv-hr-1"></div>
                <div class="srv-title3"><?php echo esc_html(pplus_localize('Với dịch vụ của Property Plus, bạn sẽ được tận hưởng trải nghiệm sống sang trọng, tùy chỉnh với các dịch vụ tiêu chuẩn 05 sao trong chính không gian sống bản địa - nơi cân bằng hoàn hảo giữa công việc và cuộc sống.')); ?></div>

                <div class="col-md-4 col-sm-4 srv-item">
                    <div class="srv-box"><img class="lazy" src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/ImgHome/Group-2.svg'); ?>" alt="imgicon" width="100" height="100" loading="lazy" /></div>
                    <div class="srv-content"><?php echo esc_html(pplus_localize('Dịch vụ quản lý của')); ?><br /><?php echo esc_html(pplus_localize('Property Plus')); ?></div>
                </div>
                <div class="col-md-4 col-sm-4 srv-item">
                    <div class="srv-box"><img class="lazy" src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/ImgHome/Frame.svg'); ?>" alt="imgicon" width="100" height="100" loading="lazy" /></div>
                    <div class="srv-content"><?php echo esc_html(pplus_localize('Dịch vụ kỹ thuật - bảo dưỡng')); ?></div>
                </div>
                <div class="col-md-4 col-sm-4 srv-item">
                    <div class="srv-box"><img class="lazy" src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/ImgHome/Group-1.svg'); ?>" alt="imgicon" width="100" height="100" loading="lazy" /></div>
                    <div class="srv-content"><?php echo esc_html(pplus_localize('Dịch vụ dọn phòng')); ?></div>
                </div>
            </div>
            <div class="col-md-6">
                <img class="img-right lazy" src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/ImgHome/Group 1080.png'); ?>" alt="imgicon" width="100" height="100" loading="lazy" />
            </div>
        </div>
    </div>
</div>

<?php if (!empty($home['danhSachNhanVien'])) : ?>
<div class="nhanvien-box">
    <div class="container">
        <div class="row">
            <div class="col-md-12 data-header">
                <h2 class="title-1"><?php echo esc_html(pplus_localize('Đội ngũ nhân viên')); ?></h2>
                <hr />
            </div>
            <div class="col-md-12">
                <div class="silder-nhanvien stick-custom-css">
                    <div class="silder-nhanvien-track">
                        <?php foreach ($home['danhSachNhanVien'] as $person) : ?>
                            <div class="item-nhanvien">
                                <img class="lazy" src="<?php echo esc_url(pplus_admin_asset_url($person['img'] ?? '')); ?>" alt="<?php echo esc_attr($person['title'] ?? ''); ?>" loading="lazy" />
                                <div class="data-content">
                                    <div class="title-1"><?php echo esc_html($person['title'] ?? ''); ?></div>
                                    <div class="title-2"><?php echo esc_html($person['content'] ?? ''); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($home['listBaoChis'])) : ?>
<div class="newspapers-box">
    <div class="container">
        <div class="row">
            <div class="col-md-12 data-header">
                <h2 class="title-1"><?php echo esc_html(pplus_localize('Báo chí nói về Property Plus')); ?></h2>
                <hr />
            </div>
            <div class="col-md-12">
                <div class="silder-newspapers stick-custom-css">
                    <?php foreach ($home['listBaoChis'] as $item) : ?>
                        <div class="item-newspapers">
                            <img src="<?php echo esc_url(pplus_admin_asset_url($item['img'] ?? '')); ?>" alt="<?php echo esc_attr($item['title'] ?? ''); ?>" loading="lazy" />
                            <div class="data-action"><?php echo esc_html($item['title'] ?? ''); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
// Logo doi tac/khach hang: mang co san (KHONG tu API), port thang tu listDoiTac/listkhachang trong
// home.component.ts. Duong dan/duoi file giu dung tung file that (khong loop so nguyen) vi 2 thu
// muc nay tron .jpg/.png khong theo quy luat.
$partner_logos = ['1.jpg', '2.jpg', '3.jpg', '4.jpg', '5.png', '6.jpg', '7.jpg', '8.jpg', '9.jpg', '10.jpg', '11.jpg', '12.png', '13.jpg', '14.jpg', '15.jpg', '16.jpg', '17.jpg'];
$customer_logos = ['1.jpg', '2.jpg', '3.jpg', '4.jpg', '5.jpg', '6.jpg', '7.jpg', '8.png', '9.jpg', '10.jpg', '11.png', '12.png', '13.png', '14.jpg', '15.jpg', '16.png', '17.jpg', '18.jpg', '19.jpg', '20.png', '21.jpg', '22.jpg', '23.jpg'];
?>
<div class="story-project-client">
    <div class="container">
        <div class="_partner">
            <div class="col-md-12">
                <h2 class="_partner-title"><?php echo esc_html(pplus_localize('Đối tác')); ?></h2>
                <div class="hr-custom-1"></div>
                <div class="_partner-title-2"><?php echo esc_html(pplus_localize('Đối tác chiến lược của Property Plus là các chủ đầu tư uy tín, các thương hiệu quản lý và kinh doanh bất động sản hàng đầu trên thế giới hiện đang có mặt tại Việt Nam.')); ?></div>
            </div>
            <div class="_partner-silder lazy-img-loading">
                <div class="partner-silder-track">
                    <?php // "logo-card-slide" boc ngoai - cung ly do voi apt-card-slide o tren:
                    // ._partner-item co nen trang/bong rieng, khong the dung lam slide Slick truc tiep. ?>
                    <?php foreach ($partner_logos as $file) : ?>
                        <div class="logo-card-slide">
                            <div class="_partner-item">
                                <img class="_partner-img" src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/ImgHome/ImgDoiTac/' . $file); ?>" alt="Doi tac" loading="lazy" />
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <hr class="_partner-hr-1" />

        <div class="_partner">
            <div class="col-md-12">
                <h2 class="_partner-title"><?php echo esc_html(pplus_localize('Khách hàng')); ?></h2>
                <div class="hr-custom-1"></div>
                <div class="_partner-title-2"><?php echo esc_html(pplus_localize('Khách hàng của chúng tôi đến từ các tập đoàn và nhiều quốc gia trên khắp thế giới, hơn 1000 khách hàng đã và đang tin dùng dịch vụ của Property Plus.')); ?></div>
            </div>
            <div class="_partner-silder lazy-img-loading">
                <div class="customer-silder-track">
                    <?php foreach ($customer_logos as $file) : ?>
                        <div class="logo-card-slide">
                            <div class="_partner-item">
                                <img class="_partner-img" src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/ImgHome/ImgKhachHang/' . $file); ?>" alt="Khach hang" loading="lazy" />
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($home['khamPhaVietNamList'])) : ?>
<div class="blog-box">
    <div class="container">
        <div class="vietnamese-cuisine">
            <div class="col-md-12">
                <h2 class="cuisine-title"><?php echo esc_html(pplus_localize('Khám phá đất nước của chúng tôi')); ?></h2>
                <p class="cuisine-title-2"><?php echo esc_html(pplus_localize('Bạn đã và muốn biết gì về đất nước của chúng tôi? Hãy cùng hòa nhịp với trái tim Việt Nam!')); ?></p>
            </div>
            <div class="cuisines">
                <div class="cuisines-silder-track">
                    <?php foreach ($home['khamPhaVietNamList'] as $item) : ?>
                        <?php // Div boc ngoai: Slick gan padding khoang cach len CHINH the slide - neu slide la <a position:relative> thi lop chu absolute (.khampha-2-content) bi tran ra ngoai the 7.5px moi ben. ?>
                        <div class="cuisine-slide">
                        <a href="<?php echo esc_url(pplus_blog_detail_url($item['alias'] ?? '', (int) ($item['id'] ?? 0))); ?>" class="cuisine-item cuisine-img">
                            <img class="lazy" src="<?php echo esc_url(pplus_admin_asset_url($item['img'] ?? '')); ?>" alt="<?php echo esc_attr($item['title'] ?? ''); ?>" loading="lazy" />
                            <div class="khampha-2-content">
                                <div class="khampha-2-t-1"><?php echo esc_html($item['title'] ?? ''); ?></div>
                                <div class="khampha-2-t-2"><?php echo esc_html($item['subTitle'] ?? ''); ?></div>
                            </div>
                        </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php else : ?>
<div class="container" style="padding: 40px 0;">
    <?php pplus_render_api_error(); ?>
</div>
<?php endif; ?>

<?php get_footer(); ?>
