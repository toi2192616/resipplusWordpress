<?php
/**
 * A3/A4 nhanh "Dự án cụ thể" - /cho-thue-can-ho/{province}/{district}/{project-alias}-{id}/. Route
 * dang ky trong inc/project-detail-route.php (rewrite rule, KHONG phai Page).
 *
 * Dung LAI GAN NHU NGUYEN VEN cau truc page-reference.php (trang "province/alias") - xem docblock
 * pplus_get_project_detail() (inc/api-client.php) cho ly do: ReferenceIndexComponent that dung
 * CHUNG 1 template cho ca 2 nhanh (khac nhau o cho GOI API nao, Reference/GetIndex vs Project/
 * Detail), CHI khac o "$filter" da co san "project_id" (loc dung 1 du an) thay vi loc theo quan/loai.
 * Khong gop chung 1 file voi page-reference.php de tranh 1 file qua dai voi 2 nhanh if/else lon -
 * xem page-reference.php cho comment chi tiet ve TUNG PHAN, o day chi ghi chu diem KHAC BIET.
 */
if (!defined('ABSPATH')) exit;

$model = pplus_get_project_detail_from_query_vars();

// "dataSelected" (bai viet SEO rieng) la 1 khoi TINH TUY CHON, KHONG PHAI tin hieu "du an co ton
// tai hay khong" - xac nhan qua doc chinh reference-index.component.ts (dong 122-186, nhanh du an):
// PHAN LON du an KHONG co bai viet rieng (dataSelected=null qua goi API that, vd projectId 182),
// nhung trang van hien BINH THUONG (danh sach can ho cua CHINH du an do), chi la KHONG co bai viet SEO
// o duoi + tieu de tu dong lay "projectDetail.projectName" thay cho "dataSelected.title" that su -
// dung y het 2 nhanh if/else cua chinh .ts do. Tin hieu 404 THAT SU la "projectDetail" rong (id
// khong ton tai/da xoa) - KHAC voi page-reference.php (trang "province/alias", o do "dataSelected"
// rong MOI dung la 404 vi day la nhanh khac, khong co "projectDetail" fallback).
if (empty($model['projectDetail'])) {
    status_header(404);
    get_header();
    echo '<div class="container" style="padding:40px 0;">';
    pplus_render_api_error();
    echo '</div>';
    get_footer();
    return;
}

$data = $model['dataSelected'] ?? [];
if (empty($data)) {
    $data = [
        'title'   => $model['projectDetail']['projectName'] ?? '',
        'content' => '',
    ];
}
// "filter" tra ve tu Project/Detail DA CO SAN "project_id" (loc dung 1 du an) - KHONG can tu them
// nhu cac truong hop khac, xem docblock pplus_get_project_detail().
$filter      = $model['filter'] ?? [];
$lang_prefix = pplus_lang_prefix();

$sort_options = [
    1 => pplus_localize('Giá tăng dần'),
    2 => pplus_localize('Giá giảm dần'),
    3 => pplus_localize('Căn hộ mới nhất'),
    4 => pplus_localize('Vị trí căn hộ'),
];
$current_sort = isset($_GET['sort']) ? (int) $_GET['sort'] : (int) ($filter['price_sort_type'] ?? 2);
if (!isset($sort_options[$current_sort])) {
    $current_sort = 2;
}

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

$per_page = 18;
$paged    = max(1, (int) ($_GET['paged'] ?? 1));
$listing  = pplus_get_apartment_list($filter, ($paged - 1) * $per_page, $current_sort, $per_page);
$listApartments = $listing['listApartments'];
$total_count    = $listing['total_count'];
$total_pages    = $total_count > 0 ? (int) ceil($total_count / $per_page) : 0;

$toc = pplus_generate_toc((string) ($data['content'] ?? ''));

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
        'url'   => home_url($lang_prefix . '/' . ltrim(pplus_office_url($apt['urlHerf'] ?? ''), '/')),
        'id'    => $apt['apartmentId'] ?? ($apt['projectId'] ?? ''),
    ];
}

get_header();
?>

<?php // Khong co alias quan/loai nao active - trang nay loc theo 1 du an cu the, ngoai he thong
// pill quan/loai cua suggest-header (giong quy uoc "null = khong active gi" da dung o A2 can ho). ?>
<?php pplus_render_suggest_header(null); ?>

<div class="container-aparment">
    <div class="map-office">
        <div class="box-content row">
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
                            <a href="<?php echo esc_url(home_url($lang_prefix . '/cho-thue-can-ho/ha-noi/')); ?>"><?php echo pplus_breadcrumb_arrow(); ?><?php echo esc_html(pplus_localize('Hà Nội')); ?></a>
                            <?php if (!empty($data['subTitle'])) : ?>
                                <a href="#"><?php echo pplus_breadcrumb_arrow(); ?><?php echo esc_html($data['subTitle']); ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="text-find" id="text-find">
                        <h1 class="title-search"><?php echo esc_html($data['title'] ?? ''); ?> | <?php echo esc_html(pplus_localize('Giá Thuê')); ?> <?php echo esc_html(date_i18n('m/Y')); ?></h1>
                        <div class="group-oder">
                            <div class="order-right search-form active">
                                <label><?php echo esc_html(pplus_localize('Căn hộ')); ?></label>
                            </div>
                            <div class="order-right search-form">
                                <label><?php echo esc_html(pplus_localize('Dự án')); ?></label>
                            </div>
                            <div class="order-right order-right-select">
                                <span class="order-label"><?php echo esc_html(pplus_localize('Lọc theo')); ?></span>
                                <form method="get" style="display:inline-block;">
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
            <div class="btnShowThongTin" title="<?php echo esc_attr(pplus_localize('Hiển thị thông tin')); ?>" data-pplus-toggle="thong-tin">
                <i class="fa fa-file-o" aria-hidden="true"></i>
            </div>
        </div>
    </div>
</div>

<div class="datalist" id="web-content">
<div class="container" style="padding: 24px 0 40px;">
    <div class="row">
        <div class="col-md-8">
            <div class="data-content">
                <?php pplus_render_toc_nested($toc['items']); ?>
                <div class="content-1 table-mg blog-p-no-margin" id="content-ckedit">
                    <?php // Bai viet SEO nam duoi danh sach the - hoan anh toi load+1s (xem pplus_defer_lazy_imgs()). ?>
                    <?php echo pplus_defer_lazy_imgs(pplus_kses_content($toc['html'])); ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="contact-us">
                <div class="contact-head">
                    <div class="title"><?php echo esc_html(pplus_localize('Bạn đang quan tâm')); ?></div>
                    <div class="name"><?php echo esc_html($data['title'] ?? ''); ?></div>
                </div>
                <form id="SupportCustomerForm" method="post" onsubmit="return false;">
                    <div class="mb-3">
                        <input class="form-control" name="OwnerName" type="text" maxlength="100" placeholder="<?php echo esc_attr(pplus_localize('Họ và tên')); ?> ..." />
                    </div>
                    <div class="mb-3">
                        <input class="form-control" name="OwnerEmail" type="email" maxlength="100" placeholder="<?php echo esc_attr(pplus_localize('Email')); ?> ..." />
                    </div>
                    <div class="mb-3">
                        <input class="form-control" name="OwnerMobile" type="tel" maxlength="100" placeholder="<?php echo esc_attr(pplus_localize('Số điện thoại')); ?> ..." />
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="pplus-owner-check" name="OwnerCheck" />
                        <label class="form-check-label" for="pplus-owner-check"><?php echo esc_html(pplus_localize('Tôi muốn nhận thông tin từ  Property Plus')); ?></label>
                    </div>
                    <p><?php echo esc_html(pplus_localize('Tư vấn miễn phí - Giá thuê tốt nhất - Gửi báo giá nhanh chóng')); ?></p>
                    <button type="submit" class="btn btn-primary mb-3"><?php echo esc_html(pplus_localize('Gửi yêu cầu')); ?></button>
                    <a href="tel:0865364866" class="btn btn-default phone-contact-1"><?php echo esc_html(pplus_localize('Hoặc gọi')); ?> 0865.364.866</a>
                </form>
            </div>
        </div>
    </div>
</div>
    <button class="btn btn-primary btnBackOffThongTin" type="button" data-pplus-toggle="thong-tin">
        <i class="fa fa-reply" aria-hidden="true"></i> <?php echo esc_html(pplus_localize('Quay lại')); ?>
    </button>
</div>

<?php get_footer(); ?>
