<?php
/**
 * Template Name: Landing tinh (Reference Main)
 * Trang /cho-thue-van-phong/{province-alias} (vd /cho-thue-van-phong/ha-noi) - A3.
 * Port rut gon tu src/app/modules/reference/reference-main/reference-main.component.html.
 *
 * Day la 1 WordPress Page THAT (con cua Page "Cho thue van phong" - A1), KHONG phai rewrite rule
 * rieng nhu A2, vi URL chi co 1 doan dong (province) va so luong tinh it/on dinh (Ha Noi, HCM) -
 * content team co the tu tao Page con moi khi mo rong sang tinh khac.
 *
 * Field "_pplus_province_slug" (da co san tu Phase 0, xem inc/cpt-landing.php) la alias goi API,
 * mac dinh lay tu slug cua Page neu chua dien.
 *
 * TODO (khong chan hien thi):
 * - Khong co Google Maps o search-base (isMapshow=false giong Angular that).
 * - Khong co FAQ (Angular cung an FAQ bang class "hidden" - dung hanh vi that).
 */
if (!defined('ABSPATH')) exit;

$model = pplus_get_current_landing_data();

if (empty($model['dataSelected'])) {
    status_header(404);
    get_header();
    echo '<div class="container" style="padding:40px 0;">';
    pplus_render_api_error();
    echo '</div>';
    get_footer();
    return;
}

$data        = $model['dataSelected'];
$lang_prefix = (pplus_current_lang() === 'en') ? '/en' : '';
// "district.alias" tu Reference/GetIndexProvinceProduct CUNG khac nhau theo ngon ngu dang goi
// (giong het bug o header.php), can doi lai ve alias VI truoc khi dung lam URL - xem ghi chu chi
// tiet trong pplus_get_header_vi_alias_map() (inc/api-client.php).
$pplus_vi_alias_map = (pplus_current_lang() === 'en') ? pplus_get_header_vi_alias_map() : [];

// Muc luc ("Mục Lục") - port generateTOC() tu table-of-contents.component.ts, xem
// inc/render-helpers.php pplus_generate_toc(). $toc['html'] da duoc gan id vao tung heading.
$toc = pplus_generate_toc($data['content'] ?? '');

// H1 dung subTitle khi loai=='6', nguoc lai dung title - dung y het reference-main.component.html
// dong 46-51. Truoc day thieu nhanh nay (chi dung "title"), khac voi A4 (page-reference.php) da
// port dung tu truoc - phat hien qua audit *ngIf doi chieu voi Angular that.
$h1 = ($data['loai'] ?? '') === '6' ? ($data['subTitle'] ?? '') : ($data['title'] ?? '');

get_header();
?>
<div class="container">
    <div class="url-box-content">
        <div class="container">
            <div class="url-content">
                <div class="url-content-list">
                    <a href="<?php echo esc_url(home_url('/')); ?>"><?php echo esc_html(pplus_t('Trang chủ', 'Home')); ?></a>
                    &nbsp;&nbsp;
                    <a href="<?php echo esc_url(home_url($lang_prefix . '/' . pplus_office_list_base_slug() . '/')); ?>"><?php echo esc_html(pplus_t('Cho thuê văn phòng', 'Office for rent')); ?></a>
                    &nbsp;&nbsp;
                    <?php echo esc_html($data['title'] ?? ''); ?>
                </div>
            </div>
            <button type="button" class="btn btn-action-search-base" id="btn-action-filter" data-pplus-toggle="search">
                <i><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/search-filter-white.png'); ?>" loading="lazy" alt="" /></i>
                <?php echo esc_html(pplus_t('Tìm kiếm văn phòng', 'Search for offices')); ?>
            </button>
        </div>
    </div>
</div>

<?php get_template_part('parts/office-filter-panel', null, [
    // Trang nay khong doc $_GET de loc lai (xem ghi chu trong page-reference.php: khong co
    // "#result-box" that, chi xem/redirect ve trang tim kiem chung khi bam "Tim kiem") - nhung
    // van truyen 'province' de MO panel dung san tinh dang xem (vd vao tu /cho-thue-van-phong/
    // ha-noi/ thi panel mo len da o tinh "Hà Nội"), theo yeu cau nguoi dung ap dung cho MOI trang
    // co dieu kien loc ham y, khong rieng gi A1/A4.
    'current' => [
        'province' => $model['filter']['province'] ?? '',
    ],
]); ?>

<div class="datalist" id="web-content">
    <?php if (!empty($model['dataList'])) : ?>
        <div class="container">
            <div class="box-content">
                <div class="box-list">
                    <div class="box-list-body">
                        <?php foreach ($model['dataList'] as $district) : if (empty($district['listOffice'])) continue; ?>
                            <div class="district-office-block">
                                <div class="district-head">
                                    <h2 class="district-title"><?php echo esc_html($district['title'] ?? ''); ?></h2>
                                    <a class="district-viewall" href="<?php echo esc_url(home_url($lang_prefix . '/' . pplus_office_list_base_slug() . '/' . ($data['alias'] ?? '') . '/' . pplus_header_alias($district, $pplus_vi_alias_map) . '/')); ?>">
                                        <?php echo esc_html(pplus_t('Xem tất cả', 'View all')); ?>
                                    </a>
                                </div>
                                <?php // KHONG dat id="result-box" o day (khac A1/A4) - div nay nam trong 1
                                // vong lap (foreach $district), co the lap lai nhieu lan tren cung 1 trang
                                // (moi id CHI duoc dung 1 lan/trang theo chuan HTML) VA trang nay khong
                                // doc $_GET de loc lai (chi hien preview tinh theo tung quan, xem dau file
                                // - "isMapshow=false", khong co Google Maps/bo loc that nhu A1/A4). Neu co
                                // id="result-box" o day, JS ($('#result-box').length trong assets/js/
                                // main.js) se nham tuong trang nay "co san pham loc duoc" giong A1/A4, dan
                                // toi bam "Tim kiem" tren panel Bo loc GIU NGUYEN URL (khong dieu huong ve
                                // trang tim kiem chung) nhung PHP o day khong doc $_GET nen KHONG co gi
                                // thay doi ca - bug thuc te da gap ("trang Hà Nội... bấm tìm kiếm phải bật
                                // sang trang tìm kiếm tổng" nhung lai khong bat). ?>
                                <div class="result-box">
                                    <div class="list-product-office full-content part-content">
                                        <?php foreach (array_slice($district['listOffice'], 0, 4) as $office) : ?>
                                            <?php get_template_part('parts/office-card', null, ['item' => $office]); ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="container mb-30">
        <div class="row">
            <div class="col-md-8">
                <div class="data-content">
                    <h1 class="data-title-5"><?php echo esc_html($h1); ?></h1>
                    <?php if (!empty($data['content'])) : ?>
                        <?php pplus_render_toc_nested($toc['items']); ?>
                        <div class="content-1 table-mg blog-p-no-margin" id="content-ckedit">
                            <?php echo pplus_kses_content($toc['html']); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-4">
                <div class="contact-us">
                    <div class="contact-head">
                        <div class="title"><?php echo esc_html(pplus_t('Bạn đang quan tâm', 'You are interested in')); ?></div>
                        <div class="name"><?php echo esc_html($data['title'] ?? ''); ?></div>
                    </div>
                    <form class="pplus-advise-form">
                        <div class="mb-3"><input class="form-control" type="text" name="OwnerName" placeholder="<?php echo esc_attr(pplus_t('Họ và tên của bạn ...', 'Your full name ...')); ?>" /></div>
                        <div class="mb-3"><input class="form-control" type="email" name="OwnerEmail" placeholder="<?php echo esc_attr(pplus_t('Email của bạn ...', 'Your email ...')); ?>" /></div>
                        <div class="mb-3"><input class="form-control" type="tel" name="OwnerMobile" placeholder="<?php echo esc_attr(pplus_t('Số điện thoại ...', 'Your phone number ...')); ?>" /></div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="pplusOwnerCheck" />
                            <label class="form-check-label" for="pplusOwnerCheck"><?php echo esc_html(pplus_t('Tôi muốn nhận thông tin từ Property Plus', 'I want to receive information from Property Plus')); ?></label>
                        </div>
                        <?php // Icon "Group-5.png" (dong 70/74/78 reference-index.component.html, giong het
                        // ".icon-from-search" cua 5 o tim kiem trang chu) - truoc day thieu, chi con chu. ?>
                        <p><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/Group-5.png'); ?>" class="icon-from-search" alt="Icon" width="29" height="24" /><?php echo esc_html(pplus_t('Tư vấn miễn phí', 'Free consultation')); ?></p>
                        <p><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/Group-5.png'); ?>" class="icon-from-search" alt="Icon" width="29" height="24" /><?php echo esc_html(pplus_t('Giá thuê tốt nhất', 'Best rental price')); ?></p>
                        <p><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/Group-5.png'); ?>" class="icon-from-search" alt="Icon" width="29" height="24" /><?php echo esc_html(pplus_t('Gửi báo giá nhanh chóng', 'Send quotes quickly')); ?></p>
                        <button type="submit" class="btn btn-primary mb-3"><?php echo esc_html(pplus_t('Gửi yêu cầu tư vấn', 'Submit request')); ?></button>
                        <a href="tel:0865364866" class="btn btn-default phone-contact-1"><?php echo esc_html(pplus_t('Hoặc gọi', 'Or call')); ?> 0865.364.866</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php get_footer(); ?>
