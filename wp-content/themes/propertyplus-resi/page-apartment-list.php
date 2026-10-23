<?php
/**
 * A1 - Danh sach can ho TONG QUAT (khong loc theo tinh/quan): /cho-thue-can-ho/. Route dang ky
 * trong inc/apartment-list-route.php. Day la dich cua nut "Tìm kiếm" tren form tim kiem trang chu
 * (front-page.php) - bug thuc te nguoi dung bao cao (29/9): "Nhấn nút tìm kiếm ở destop không tìm
 * được sản phẩm" (URL nay 404 vi chua tung dang ky - xem docblock file route).
 *
 * Port rut gon tu search-base.component.html nhanh "Model == undefined" (dong 91-110/107-109):
 * khong co province/alias nao dang xem nen breadcrumb CHI con "Trang chủ" (khong co doan "Hà Nội"
 * - doan do o ban that CUNG dieu kien theo "Model != undefined"), h1 co dinh "Tìm căn hộ cho thuê".
 * Cau truc con lai (search-tab tabs, sort, luoi + ban do, GroupMoblieActive, box-list-body-mobile)
 * GIONG HET page-reference.php (A3/A4) - xem file do cho comment chi tiet ve tung phan, o day chi
 * ghi chu diem KHAC BIET.
 *
 * TODO (giong page-reference.php, khong lap lai toan bo, chi khac 1 diem):
 * - $_GET['search_key']/'detail' (2 truong tren form tim kiem trang chu) KHONG duoc dung de loc -
 *   Apartment/_GetApartments KHONG co field free-text (giong OfficeFilter.cs ben Van phong, xem
 *   docs/PORTING_NOTES.md) - nut "Tìm kiếm" tren trang chu THUC CHAT chi dieu huong sang day va
 *   hien TOAN BO can ho, dung y het hanh vi that cua Angular goc khi nguoi dung KHONG chon 1 goi y
 *   autocomplete cu the (submitSearchHome() gui "searchHome.filter" nguyen van qua queryParams,
 *   nhung server that cung khong loc theo no - xac nhan gian tiep qua chinh OfficeFilter.cs).
 * - Cac field loc CO CAU TRUC (gia/phong ngu/phong tam/dien tich/loai can ho) VAN nhan tu $_GET,
 *   giong het page-reference.php - cho phep xay link loc tu noi khac (vd tuong lai) du form trang
 *   chu hien khong gui cac field nay.
 */
if (!defined('ABSPATH')) exit;

$lang_prefix = pplus_lang_prefix();

$filter = [];
foreach (['price_from', 'price_to', 'bedroom_qty', 'bathroom_qty', 'area_from', 'area_to', 'floor_from', 'floor_to'] as $numeric_key) {
    if (isset($_GET[$numeric_key]) && $_GET[$numeric_key] !== '') {
        $filter[$numeric_key] = (float) $_GET[$numeric_key];
    }
}
if (isset($_GET['apartment_type']) && $_GET['apartment_type'] !== '') {
    $filter['apartment_type'] = sanitize_text_field($_GET['apartment_type']);
}
if (isset($_GET['has_bathtub'])) {
    $filter['has_bathtub'] = ($_GET['has_bathtub'] === '1');
}
// Lua chon tu goi y khi go o tim kiem (assets/js/pplus-search-suggest.js): 1 du an (project_id) hoac 1 khung
// dia diem Google (ne/sw - cung ten field loc theo vung ma Apartment/_GetApartments nhan, xac nhan qua goi API).
if (!empty($_GET['project_id'])) {
    $filter['project_id'] = (int) $_GET['project_id'];
}
if (isset($_GET['ne_lat'], $_GET['ne_lng'], $_GET['sw_lat'], $_GET['sw_lng'])) {
    foreach (['ne_lat', 'ne_lng', 'sw_lat', 'sw_lng'] as $bound_key) {
        $filter[$bound_key] = (float) $_GET[$bound_key];
    }
}

$sort_options = [
    1 => pplus_localize('Giá tăng dần'),
    2 => pplus_localize('Giá giảm dần'),
    3 => pplus_localize('Căn hộ mới nhất'),
    4 => pplus_localize('Vị trí căn hộ'),
];
$current_sort = isset($_GET['sort']) ? (int) $_GET['sort'] : 2;
if (!isset($sort_options[$current_sort])) {
    $current_sort = 2;
}

// 18/trang, cung so voi page-reference.php (3 cot x 6 hang) - xem docblock pplus_get_apartment_list()
// cho ly do can goi API nhieu lan (backend co dinh 12/lan).
$per_page = 18;
$paged    = max(1, (int) (get_query_var('paged') ?: ($_GET['paged'] ?? 1)));
$listing  = pplus_get_apartment_list($filter, ($paged - 1) * $per_page, $current_sort, $per_page);
$listApartments = $listing['listApartments'];
$total_count    = $listing['total_count'];
$total_pages    = $total_count > 0 ? (int) ceil($total_count / $per_page) : 0;

$map_markers = [];
foreach ($listApartments as $apt) {
    if (empty($apt['lat']) || empty($apt['lng'])) {
        continue;
    }
    $map_markers[] = [
        'lat'   => (float) $apt['lat'],
        'lng'   => (float) $apt['lng'],
        'price' => (int) round((float) ($apt['price'] ?? 0)),
        'title' => $apt['title'] ?? ($apt['projectName'] ?? ''),
        // SUA 30/9 (cung 1 bug voi parts/apartment-card.php - xem ghi chu day du o do): thieu
        // pplus_lang_prefix() khien click marker tren ban EN/JA roi thang ve URL VI.
        'url'   => home_url(pplus_lang_prefix() . '/' . ltrim(pplus_office_url($apt['urlHerf'] ?? ''), '/')),
        'id'    => $apt['apartmentId'] ?? ($apt['projectId'] ?? ''),
    ];
}

get_header();
?>

<?php // "Tất cả" active - trang nay LA chinh trang "Tất cả" (khong loc theo quan/loai nao). ?>
<?php pplus_render_suggest_header(''); ?>

<div class="container-aparment">
    <div class="map-office">
        <div class="box-content row">
            <?php // Nut "Tìm kiếm khu vực này" - giong het page-reference.php (xem ghi chu day du o do). ?>
            <button type="button" class="searching-tool-button" id="pplus-search-area" style="display:none;">
                <i>
                    <svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="redo" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" class="svg-inline--fa fa-redo fa-w-16 fa-2x"><path fill="currentColor" d="M500.33 0h-47.41a12 12 0 0 0-12 12.57l4 82.76A247.42 247.42 0 0 0 256 8C119.34 8 7.9 119.53 8 256.19 8.1 393.07 119.1 504 256 504a247.1 247.1 0 0 0 166.18-63.91 12 12 0 0 0 .48-17.43l-34-34a12 12 0 0 0-16.38-.55A176 176 0 1 1 402.1 157.8l-101.53-4.87a12 12 0 0 0-12.57 12v47.41a12 12 0 0 0 12 12h200.33a12 12 0 0 0 12-12V12a12 12 0 0 0-12-12z"></path></svg>
                </i> <?php echo esc_html(pplus_localize('Tìm kiếm khu vực này')); ?>
            </button>
            <div class="box-list init col-md-7">
                <div class="col-md-12 box-list-header pull-left">
                    <div class="url-content">
                        <div class="url-content-list">
                            <a href="<?php echo esc_url(home_url('/')); ?>"><?php echo esc_html(pplus_localize('Trang chủ')); ?></a>
                        </div>
                    </div>
                    <div class="text-find" id="text-find">
                        <h1 class="title-search"><?php echo esc_html(pplus_localize('Tìm căn hộ cho thuê')); ?></h1>
                        <div class="group-oder">
                            <div class="order-right search-form active">
                                <label><?php echo esc_html(pplus_localize('Căn hộ')); ?></label>
                            </div>
                            <?php // "Dự án" - chi hien giao dien, xem TODO page-reference.php (cung 1 gap,
                            // dung 1 service/component Angular khac chua lam). ?>
                            <div class="order-right search-form">
                                <label><?php echo esc_html(pplus_localize('Dự án')); ?></label>
                            </div>
                            <div class="order-right order-right-select">
                                <span class="order-label"><?php echo esc_html(pplus_localize('Lọc theo')); ?></span>
                                <form method="get" style="display:inline-block;">
                                    <?php pplus_render_preserved_query_inputs(); ?>
                                    <select name="sort" class="order-control" onchange="this.form.submit()">
                                        <?php foreach ($sort_options as $value => $label) : ?>
                                            <option value="<?php echo (int) $value; ?>" <?php selected($current_sort, $value); ?>><?php echo esc_html($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="box-list-body pull-left">
                    <div class="result-box" id="result-box">
                    <?php if (empty($listApartments)) : ?>
                        <div class="pplus-notice"><?php echo esc_html(pplus_localize('Không tìm thấy căn hộ phù hợp.')); ?></div>
                    <?php else : ?>
                        <div class="list-product-office">
                            <?php foreach ($listApartments as $i => $item) : ?>
                                <?php get_template_part('parts/apartment-card', null, ['item' => $item, 'index' => $i]); ?>
                            <?php endforeach; ?>
                        </div>

                        <?php
                        // SUA 30/9 (cung 1 bug voi page-reference.php - xem docblock pplus_current_url()
                        // trong inc/api-client.php): phai truyen $base tuong minh, khong de add_query_arg()
                        // tu doc REQUEST_URI (da bi mat tien to ngon ngu tu truoc).
                        $pagination_url_format = esc_url_raw(add_query_arg(['sort' => $current_sort, 'paged' => '%d'], pplus_current_url()));
                        pplus_render_pagination($paged, $total_pages, $pagination_url_format);
                        ?>

                        <div class="total-count text-center"><?php echo (int) $total_count; ?> <?php echo esc_html(pplus_localize('chỗ ở')); ?></div>
                    <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-5 box-map invision-map">
                <div class="action-close-map" data-pplus-toggle="map-view" title="<?php echo esc_attr(pplus_localize('Đóng bản đồ')); ?>">
                    <i class="fa fa-angle-right" aria-hidden="true"></i>
                </div>
                <?php // Nut "Quay lại" (giong het page-reference.php) - goc duoi-phai, ngay tren dai the san pham. ?>
                <div class="action-close-map-label" data-pplus-toggle="map-view" role="button" title="<?php echo esc_attr(pplus_localize('Quay lại')); ?>">
                    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/reply-arrow-white.svg'); ?>" alt="" width="16" height="16" />
                    <span><?php echo esc_html(pplus_localize('Quay lại')); ?></span>
                </div>
                <div id="pplus-reference-map" data-markers="<?php echo esc_attr(wp_json_encode($map_markers)); ?>" style="width:100%;height:100%;min-height:500px;"></div>
            </div>
            <?php if (!empty($listApartments)) : ?>
                <div class="box-list-body-mobile">
                    <div class="list-product-office">
                        <?php foreach ($listApartments as $item) : ?>
                            <?php get_template_part('parts/apartment-card', null, ['item' => $item]); ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <div class="GroupMoblieActive">
            <div class="btnBackToMap-gr" title="<?php echo esc_attr(pplus_localize('Bản đồ')); ?>">
                <i>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M6.48698 22.011L0.75 24V8.13201L6.48698 6.23103V22.011ZM12.9734 23.8064L7.23641 21.923V6.055L12.9734 8.04401V23.8064ZM19.7268 11.802V21.8262L13.8003 23.7976V11.2387C14.076 11.5115 14.4378 11.7052 14.8168 11.8988C15.5576 12.1716 16.3846 12.2772 17.1254 12.2772C17.9696 12.2772 18.7965 12.1716 19.5287 11.8988C19.5919 11.8284 19.6551 11.7932 19.7182 11.7932L19.7268 11.802ZM18.1505 6.51265L19.7268 6.0462V8.88009C19.6637 8.88009 19.6005 8.85075 19.5373 8.79207C19.0808 8.68646 18.6156 8.59845 18.1505 8.50164V6.51265ZM16.9531 0H17.134C17.5044 0 17.8748 0.378431 17.8748 0.844877C17.8748 1.0385 17.7801 1.32893 17.5905 1.41694V9.07371C18.2366 9.07371 18.8913 9.16172 19.3564 9.34653C19.9077 9.63696 20.2781 9.91859 20.2781 10.385C20.2781 10.7635 19.9077 11.0451 19.3564 11.3355C18.7965 11.5203 18.0557 11.626 17.1254 11.626C16.2898 11.626 15.5576 11.5203 14.9977 11.3355C14.4378 11.0451 14.0674 10.7635 14.0674 10.385C14.0674 9.91859 14.4378 9.62816 14.9977 9.34653C15.3681 9.25853 15.928 9.17052 16.4707 9.07371V1.41694C16.2898 1.32893 16.1003 1.0385 16.1003 0.844877C16.1003 0.378431 16.4707 0 16.9445 0H16.9531ZM18.5209 1.51375L23.1552 3.30913H23.25L23.1552 3.39714L18.5209 5.47415C18.34 5.47415 18.2452 5.47415 18.1505 5.38614C18.0643 5.29813 18.0643 5.19251 17.9696 5.00769V2.08581C17.9696 1.79538 18.0557 1.70736 18.1505 1.60175C18.2452 1.51375 18.3314 1.51375 18.5209 1.51375ZM17.134 11.3355C17.9782 11.3355 18.7104 11.2475 19.1669 10.9571C19.7268 10.8691 20.0111 10.5787 20.0111 10.385C20.0111 10.1122 19.7268 9.83058 19.1669 9.72497C18.7965 9.54015 18.2452 9.43454 17.5992 9.34653V10.209C17.5992 10.297 17.5044 10.4818 17.4183 10.4818C17.3235 10.5875 17.2374 10.6755 17.134 10.6755H17.0479C16.867 10.6755 16.7636 10.5875 16.6775 10.4818C16.4879 10.3938 16.4879 10.297 16.4879 10.121V9.35533C15.9453 9.44334 15.4715 9.54896 15.1011 9.73378C14.6359 9.83939 14.3603 10.1298 14.3603 10.3938C14.3603 10.5875 14.6359 10.8779 15.1011 10.9659C15.661 11.2563 16.4018 11.3443 17.134 11.3443V11.3355ZM13.809 8.0352L15.8419 7.26953V8.50164C15.4629 8.58965 15.0925 8.68646 14.8254 8.79207C14.4464 8.97689 14.076 9.17052 13.809 9.44335V8.04401V8.0352Z" fill="white" style="fill:white;fill-opacity:1;" />
                    </svg>
                </i>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>
