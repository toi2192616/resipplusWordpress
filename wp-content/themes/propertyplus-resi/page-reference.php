<?php
/**
 * A3/A4 - Landing theo tinh/quan/loai-can-ho (module Angular "reference-index"). Route dang ky
 * trong inc/reference-index-route.php (rewrite rule, KHONG phai Page).
 *
 * Ban truoc day cua file nay (710 dong) la nguyen ban trang danh sach VAN PHONG theo theme Van
 * phong copy sang (Office/_GetOffices, filter.officeType/ratingLevel khong ton tai o API can ho,
 * cung toan bo he thong ban do/tim-theo-ban-kinh/panel-bo-loc rieng cua Office) - toan bo da duoc
 * thay the. Doi chieu truc tiep voi propertyplus.com.vn/vi/cho-thue-can-ho/ha-noi/quan-tay-ho.
 *
 * Pham vi lam trong lan nay (xem plan): danh sach can ho + phan trang + sap xep (GET don gian,
 * khong AJAX) + bai viet SEO/Muc luc (dung lai nguyen ham co san trong inc/render-helpers.php -
 * ham nay ĐÃ la ban port dung 1:1 tu TableOfContentsComponent that, khong phai code Office).
 *
 * TODO chua lam (ghi ro de khong nham da xong, giong quy uoc da dung o front-page.php):
 * - Ban do Google Maps + popup marker + "Tim theo ban kinh": KHONG lam trong lan nay - day la
 *   phan nang nhat cua search-base.component.ts that (~1750 dong, marker OverlayView tu viet tay,
 *   khong dung Marker/clustering co san) - de sau thanh 1 viec rieng.
 *   Tab "Dự án" (đổi sang xem theo tòa nhà thay vì căn hộ): dung 1 service/component Angular khac
 *   (ProjectService.GetProject) - chua lam, chi co tab "Căn hộ".
 * - Trang du an cu the (URL 3 doan .../quan/du-an-id, component GetDetail() goi Project/Detail khac
 *   han Reference/GetIndex): DA XONG 30/9 - xem inc/project-detail-route.php + page-project-detail.php
 *   (route/API rieng, KHONG dung chung file nay).
 * - Nut yeu thich + So sanh tren card: giong front-page.php, chi hien icon, chua noi logic that
 *   (Angular ban goc 2 tinh nang nay la localStorage/mang tam, khong goi API backend).
 * - Form lien he ben phai (".contact-card" duoi day): chi la giao dien, CHUA submit that len
 *   Owner/IssueNewApartment (Angular IssueNewApartment()) - can lam cung dot voi C2 (Chu nha).
 * - Khoi FAQ cua Angular goc (QuestionListTab1/2/3): xac nhan la code CHET trong chinh Angular
 *   (class "hidden" cung, 3 mang cau hoi luon rong, khong noi dau khac) - KHONG port.
 */
if (!defined('ABSPATH')) exit;

$model = pplus_get_reference_index_from_query_vars();

if (empty($model['dataSelected'])) {
    // Biet thu (loai 8): san pham da bo khoi web Resi -> 410 Gone (Google go khoi index nhanh hon 404). Con lai: 404 / thong bao loi API.
    $gone = !empty($GLOBALS['pplus_gone']);
    status_header($gone ? 410 : 404);
    get_header();
    echo '<div class="container" style="padding:40px 0;">';
    if ($gone) {
        echo '<div class="pplus-notice">' . esc_html(pplus_t('Trang này không còn tồn tại.', 'This page is no longer available.')) . '</div>';
    } else {
        pplus_render_api_error();
    }
    echo '</div>';
    get_footer();
    return;
}

$data           = $model['dataSelected'];
$filter         = $model['filter'] ?? [];
$lang           = pplus_current_lang();
$lang_prefix    = pplus_lang_prefix($lang);
$province_alias = sanitize_text_field(get_query_var('pplus_ref_province'));
$header         = pplus_get_header_data();

// Sap xep: 5 lua chon that cua Angular (search-base.component.ts listOrderValue) - "0" la placeholder
// "Giá" (chua chon), 1-4 la gia tri that gui thang lam filter.price_sort_type. Mac dinh "2" (Giá
// giảm dần) khop dung gia tri filter goc ma Reference/GetIndex da tra ve.
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

// Cac truong loc tu panel "Chi tiết" cua #searchModal (them 17/9, xem parts/search-modal.php +
// assets/js/pplus-search-modal.js) - ghi de len $filter (nguyen ban tra ve tu
// pplus_get_reference_index(), cac truong nay da CO SAN trong do voi gia tri mac dinh null/0/false -
// xac nhan qua goi API That Reference/GetIndex, KHONG bia them field moi cho Apartment/_GetApartments)
// CHI KHI nguoi dung thuc su dien (GET param ton tai va khac rong) - giu nguyen gia tri goc cua
// $filter cho MOI truong nguoi dung KHONG cham toi, dung y het cach "sort"/"paged" o duoi da lam.
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

// SUA 22/9 (theo yeu cau nguoi dung): 12 -> 18 can/trang (3 cot x 6 hang, khop dung luoi
// ".list-product-office" 3 cot that). API THAT co dinh 12/lan goi bat ke gui gi (xac nhan qua goi
// that: "take"/"pageSize"/"limit" deu bi bo qua) nen pplus_get_apartment_list() tu goi NHIEU LAN
// hon de gom du 18 can sau khi loc trung du an - xem docblock ham do (inc/api-client.php).
$per_page = 18;
$paged    = max(1, (int) ($_GET['paged'] ?? 1));
$listing  = pplus_get_apartment_list($filter, ($paged - 1) * $per_page, $current_sort, $per_page);
$listApartments = $listing['listApartments'];
$total_count    = $listing['total_count'];
$total_pages    = $total_count > 0 ? (int) ceil($total_count / $per_page) : 0;

$toc = pplus_generate_toc((string) ($data['content'] ?? ''));

// Marker gia cho ban do (xem TODO dau file / assets/js/reference-map-simple.js) - chi cac can
// dang hien tren trang nay (khop dung danh sach, KHONG tu dong tai them khi pan/zoom nhu ban that).
$map_markers = [];
foreach ($listApartments as $apt) {
    if (empty($apt['lat']) || empty($apt['lng'])) {
        continue;
    }
    $map_markers[] = [
        'lat'   => (float) $apt['lat'],
        'lng'   => (float) $apt['lng'],
        // So gia tho (khong dau cham phan cach nghin) - doi chieu voi ban that: the san pham dung
        // "priceString" (co dau cham, vd "5.150"), nhung marker tren ban do lai la so tho (vd
        // "$4000", khong phai "$4.000") - 2 noi hien thi khac dinh dang nhau trong chinh ban goc.
        'price' => (int) round((float) ($apt['price'] ?? 0)),
        'title' => $apt['title'] ?? ($apt['projectName'] ?? ''),
        // SUA 30/9 (cung 1 bug voi parts/apartment-card.php - xem ghi chu day du o do): thieu
        // pplus_lang_prefix() khien click marker tren ban EN/JA roi thang ve URL VI.
        'url'   => home_url(pplus_lang_prefix() . '/' . ltrim(pplus_office_url($apt['urlHerf'] ?? ''), '/')),
        // "id" (them vao, khong co trong ban goc): khop voi "data-marker-id" tren the can ho
        // tuong ung (parts/apartment-card.php), de reference-map-simple.js phong to dung marker
        // khi tro chuot vao the trong danh sach - yeu cau nguoi dung (14/9).
        'id'    => $apt['apartmentId'] ?? ($apt['projectId'] ?? ''),
    ];
}

get_header();
?>

<?php // Hang goi y "hot" tren cung - dung ham chung pplus_render_suggest_header() (tach ra
// inc/render-helpers.php ngay 14/9 vi phat hien day la thanh phan CHUNG, cung xuat hien nguyen
// ven tren ca trang Dich vu/page-service.php, khong rieng gi trang nay). ?>
<?php pplus_render_suggest_header($data['alias'] ?? ''); ?>

<?php // Cau truc container tu day tro xuong port dung tu search-base.component.html dong 57-190
// (nhanh "fullContent=false", co ban do canh ben - col-md-7 list + col-md-5 map, dung y het class
// that cua Angular). Ban do la ban RUT GON (xem assets/js/reference-map-simple.js + TODO dau file). ?>
<div class="container-aparment">
    <div class="map-office">
        <div class="box-content row">
            <?php // Nut "Tìm kiếm khu vực này" (search-base.component.html dong 59-71) - CSS goc CHI
            // hien tren mobile (<=768px, layout.less ".searching-tool-button"); an mac dinh, JS
            // (reference-map-simple.js) chi hien SAU KHI nguoi dung keo/zoom ban do (giong
            // "isShowToolSearchMobile" that trong mapIdle()) va an lai sau khi bam. ?>
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
                            <?php // "Du an" - chi hien giao dien, CHUA lam chuc nang (dung 1 service/component
                            // Angular khac, ProjectService.GetProject - xem TODO dau file). ?>
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
        <?php // KHONG ".full-content" (mac dinh 3 cot, vi trang nay CO ban do canh ben - xem class
        // "box-list init col-md-7" o tren, ".full-content" 4 cot chi dung khi khong co map). Chuoi
        // cha ".box-content .box-list .box-list-body" da co san tu khung ngoai (khong can boc them),
        // CSS grid that cua ".list-product-office" (search-base-component.css, compile tu chinh
        // search-base.component.less cua Resi) chi khop khi nam trong dung 3 lop nay. ?>
        <div class="list-product-office">
            <?php foreach ($listApartments as $i => $item) : ?>
                <?php get_template_part('parts/apartment-card', null, ['item' => $item, 'index' => $i]); ?>
            <?php endforeach; ?>
        </div>

        <?php
        // SUA 22/9 (theo yeu cau nguoi dung, phat hien qua anh chup): thay paginate_links() mac
        // dinh cua WordPress (tu sinh class ".page-numbers"/".current" rieng, KHONG khop voi CSS
        // that ".paging-btn"/".threedot"/".current-page" da co san tu truoc trong layout.less) bang
        // pplus_render_pagination() (inc/render-helpers.php) - port DUNG class that cua
        // <ngb-pagination> (search-base.component.html), sua dung nguyen nhan "CSS that co san
        // nhung markup dung sai ten class" (khong phai thieu CSS).
        //
        // SUA 30/9 (bug thuc te nguoi dung bao cao: "bấm trang 2 chuyển trang thì lỗi" tren ban EN):
        // PHAI truyen $base = pplus_current_url() TUONG MINH - add_query_arg() khong truyen $base se
        // tu doc $_SERVER['REQUEST_URI'], nhung bien nay DA bi inc/language-route.php strip mat tien
        // to "/en"/"/ja" tu truoc do rat lau - xem docblock pplus_current_url().
        $pagination_url_format = esc_url_raw(add_query_arg(['sort' => $current_sort, 'paged' => '%d'], pplus_current_url()));
        pplus_render_pagination($paged, $total_pages, $pagination_url_format);
        ?>

        <div class="total-count text-center"><?php echo (int) $total_count; ?> <?php echo esc_html(pplus_localize('chỗ ở')); ?></div>
    <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php // Chieu cao KHONG dat co dinh (px) - ".box-content.row" la hang flex cua Bootstrap
            // (align-items:stretch mac dinh), ".box-map" tu gian bang dung chieu cao cua ".box-list"
            // ben canh (dung y het hanh vi that: ban do cao ngan theo so luong the dang hien, khong
            // phai luon cao bang 1 khung man hinh co dinh) - chi can height:100% de "keo day" theo
            // chieu cao da duoc flex tinh san, kem min-height phong khi danh sach qua ngan/rong. ?>
            <?php // SUA 22/9: them class "invision-map" MAC DINH - dung y het page-office-list.php
            // (trang A1 tuong duong, da co san co che nay hoat dong), xac nhan qua doc chinh comment
            // trong main.js dong 200-201: "'invision-map' dang co mat = dang o che do danh sach (ban
            // do an)" - THIEU class nay truoc day (chi "col-md-5 box-map") nghia la trang luon o
            // TRANG THAI SAI NGUOC (ban do hien fullscreen tren mobile theo CSS @media(max-width:768px)
            // vo dieu kien cua ".box-map", KHONG bi opacity:0 che vi thieu class) ngay tu dau, bam nut
            // "Bản đồ" cang lam sai them (chuyen sang trang thai "an ca 2" thay vi "hien ban do") - loi
            // thuc te phat hien khi tu kiem tra luong bam (".box-map" co opacity:0 SAU khi bam, dung
            // ra PHAI la TRUOC khi bam). ?>
            <div class="col-md-5 box-map invision-map">
                <?php // SUA 22/9 LAN 2 (doi chieu truc tiep voi theme Van phong that,
                // D:\SoureceCode\PropertyOfficeWordPree\wp-content\themes\propertyplus\page-reference.php
                // dong 498-500 VA page-office-list.php dong 205-206 - CA HAI trang A1 lan A4 that deu
                // dung ".action-close-map" (nut tron "<" don gian), KHONG PHAI ".action-close-map-label"
                // nhu ban sua truoc - ghi chu cu trong search-base.less (dong 109-112, "action-close-
                // map-label CHI dung cho A1/A4") la ghi chu CU/SAI, chua duoc cap nhat theo dung markup
                // that hien tai (".action-close-map-label" thuc te la CSS MO COI, khong con noi nao
                // trong ca 2 theme dung toi). Doi lai dung ".action-close-map" cho khop chinh xac ban
                // that, tranh dung nham 1 class da bi "chet" trong codebase. ?>
                <div class="action-close-map" data-pplus-toggle="map-view" title="<?php echo esc_attr(pplus_localize('Đóng bản đồ')); ?>">
                    <i class="fa fa-angle-right" aria-hidden="true"></i>
                </div>
                <?php // Nut "Quay lại" (them 24/9 theo yeu cau nguoi dung, icon reply-arrow-white.svg do ho cung cap) - goc duoi-phai, ngay tren dai the san pham; bam de thoat che do ban do ve danh sach san pham (cung handler voi ".action-close-map", main.js). Nhan qua pplus_localize() nen ra dung 3 ngon ngu (vi "Quay lại" / en "Come back" / ja "戻る", tu tu dien Home/GetLangApi). ?>
                <div class="action-close-map-label" data-pplus-toggle="map-view" role="button" title="<?php echo esc_attr(pplus_localize('Quay lại')); ?>">
                    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/reply-arrow-white.svg'); ?>" alt="" width="16" height="16" />
                    <span><?php echo esc_html(pplus_localize('Quay lại')); ?></span>
                </div>
                <div id="pplus-reference-map" data-markers="<?php echo esc_attr(wp_json_encode($map_markers)); ?>" style="width:100%;height:100%;min-height:500px;"></div>
            </div>
            <?php // Dai the san pham noi tren ban do (mobile, che do xem ban do) - port
            // ".box-list-body-mobile" (search-base.component.html dong 192-201). CSS co san
            // (search-base.less), tu an khi ".box-map.invision-map" (che do danh sach) qua selector ~.
            // KHONG lam thanh thong ke "N kết quả" (.toggleTopGroup) o chan man hinh - theo yeu cau
            // nguoi dung 24/9 ("bo o trang chan man hinh liet ke so luong can ho"). ?>
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
        <?php // .GroupMoblieActive (2 nut noi goc duoi-phai: Ban do + Hien thi thong tin) - dat NGOAI
        // ".box-content.row" (dung y het search-base.component.html dong 204: anh em cua ".map-office"),
        // KHONG trong .row: Bootstrap ".row > *{width:100%}" se keo gian no ra full man hinh. main.js tim
        // no qua ".map-office". ?>
        <div class="GroupMoblieActive">
            <div class="btnBackToMap-gr" title="<?php echo esc_attr(pplus_localize('Bản đồ')); ?>">
                <i>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M6.48698 22.011L0.75 24V8.13201L6.48698 6.23103V22.011ZM12.9734 23.8064L7.23641 21.923V6.055L12.9734 8.04401V23.8064ZM19.7268 11.802V21.8262L13.8003 23.7976V11.2387C14.076 11.5115 14.4378 11.7052 14.8168 11.8988C15.5576 12.1716 16.3846 12.2772 17.1254 12.2772C17.9696 12.2772 18.7965 12.1716 19.5287 11.8988C19.5919 11.8284 19.6551 11.7932 19.7182 11.7932L19.7268 11.802ZM18.1505 6.51265L19.7268 6.0462V8.88009C19.6637 8.88009 19.6005 8.85075 19.5373 8.79207C19.0808 8.68646 18.6156 8.59845 18.1505 8.50164V6.51265ZM16.9531 0H17.134C17.5044 0 17.8748 0.378431 17.8748 0.844877C17.8748 1.0385 17.7801 1.32893 17.5905 1.41694V9.07371C18.2366 9.07371 18.8913 9.16172 19.3564 9.34653C19.9077 9.63696 20.2781 9.91859 20.2781 10.385C20.2781 10.7635 19.9077 11.0451 19.3564 11.3355C18.7965 11.5203 18.0557 11.626 17.1254 11.626C16.2898 11.626 15.5576 11.5203 14.9977 11.3355C14.4378 11.0451 14.0674 10.7635 14.0674 10.385C14.0674 9.91859 14.4378 9.62816 14.9977 9.34653C15.3681 9.25853 15.928 9.17052 16.4707 9.07371V1.41694C16.2898 1.32893 16.1003 1.0385 16.1003 0.844877C16.1003 0.378431 16.4707 0 16.9445 0H16.9531ZM18.5209 1.51375L23.1552 3.30913H23.25L23.1552 3.39714L18.5209 5.47415C18.34 5.47415 18.2452 5.47415 18.1505 5.38614C18.0643 5.29813 18.0643 5.19251 17.9696 5.00769V2.08581C17.9696 1.79538 18.0557 1.70736 18.1505 1.60175C18.2452 1.51375 18.3314 1.51375 18.5209 1.51375ZM17.134 11.3355C17.9782 11.3355 18.7104 11.2475 19.1669 10.9571C19.7268 10.8691 20.0111 10.5787 20.0111 10.385C20.0111 10.1122 19.7268 9.83058 19.1669 9.72497C18.7965 9.54015 18.2452 9.43454 17.5992 9.34653V10.209C17.5992 10.297 17.5044 10.4818 17.4183 10.4818C17.3235 10.5875 17.2374 10.6755 17.134 10.6755H17.0479C16.867 10.6755 16.7636 10.5875 16.6775 10.4818C16.4879 10.3938 16.4879 10.297 16.4879 10.121V9.35533C15.9453 9.44334 15.4715 9.54896 15.1011 9.73378C14.6359 9.83939 14.3603 10.1298 14.3603 10.3938C14.3603 10.5875 14.6359 10.8779 15.1011 10.9659C15.661 11.2563 16.4018 11.3443 17.134 11.3443V11.3355ZM13.809 8.0352L15.8419 7.26953V8.50164C15.4629 8.58965 15.0925 8.68646 14.8254 8.79207C14.4464 8.97689 14.076 9.17052 13.809 9.44335V8.04401V8.0352Z" fill="white" style="fill:white;fill-opacity:1;" />
                    </svg>
                </i>
            </div>
            <?php // Nut "Hiển thị thông tin" - mo panel bai viet SEO/form lien he "#web-content"
            // (dung y het search-base.component.html dong 214-216 that: ".btnShowThongTin", CHI
            // hien khi "isShowThongTin=true" - trang A3/A4 nay LUON truyen true vi luon co bai
            // viet SEO, khac page-office-list.php khong co nut nay). Click handler CO SAN tu truoc
            // trong main.js (dong ~221, dung chung "data-pplus-toggle=\"thong-tin\"" voi nut dong
            // ".btnBackOffThongTin" cuoi trang). ?>
            <div class="btnShowThongTin" title="<?php echo esc_attr(pplus_localize('Hiển thị thông tin')); ?>" data-pplus-toggle="thong-tin">
                <i class="fa fa-file-o" aria-hidden="true"></i>
            </div>
        </div>
    </div>
</div>


<?php // SUA 22/9 LAN 2 (nguoi dung phat hien qua vuot thu that: thanh menu/tim kiem "chay theo" luc
// cuon): goc re that su KHONG PHAI rieng anh khong responsive (da sua o duoi), ma la CA KHOI bai
// viet SEO + form lien he nay LE RA phai nam trong panel "#web-content" (position:fixed; an ngoai
// man hinh mac dinh tren mobile, xem reference-index.component.html dong 2 that: "<div class=
// \"datalist container\" id=\"web-content\">" boc CA ".data-content" LAN form lien he ben canh,
// dung y het page-landing.php dong 81-152 - tham chieu WP DA CO SAN, hoat dong dung) CHU KHONG phai
// nam trong luong cuon binh thuong nhu truoc gio - xac nhan qua do truc tiep production that:
// "document.body.scrollHeight" CHI ~1680px (bai viet SEO ~10500px KHONG tinh vao, vi dang nam
// ngoai man hinh) trong khi local truoc sua la ~23000px (toan bo bai viet + form nam LUON trong
// luong cuon). Trang qua dai bat thuong nhu vay rat co the la nguyen nhan khien Chrome (ca that lan
// gia lap DevTools) tinh sai vung chua cho cac phan tu "position:fixed" trong luc cuon (bug thuc te
// nguoi dung bao cao, da thu sua rieng le tung phan tu o buoc truoc nhung khong triet de - goc re
// that ra la o day). Nut mo panel (".btnShowThongTin", trong ".GroupMoblieActive") + nut dong
// (".btnBackOffThongTin", cuoi panel) DEU da co san click handler dung chung 1 ham (main.js dong
// ~221: "$('[data-pplus-toggle=\"thong-tin\"]').on('click',...)"), chi can dat DUNG class/attribute. ?>
<div class="datalist" id="web-content">
<div class="container" style="padding: 24px 0 40px;">
    <div class="row">
        <div class="col-md-8">
            <?php // Anh trong bai viet SEO tu CKEditor co "style=\"width:800px;height:600px\"" INLINE
            // (khong responsive) - CSS "img{width:100%!important;height:auto!important}" cho man hinh
            // nho DA CO SAN tu truoc (reference-index.less dong 151-162) nhung CHI khop selector
            // ".datalist .data-content img" (dung y het page-landing.php) - gio da co ".datalist" bao
            // ngoai (dong tren) nen tu dong ap dung dung, khong can sua gi them o day. ?>
            <div class="data-content">
                <?php // pplus_render_toc_nested() TU IN het khung + tieu de "Muc Luc" rieng (xem
                // inc/render-helpers.php dong 274-275) - KHONG boc them div/tieu de nao o day nua,
                // truoc do boc them 1 lop ngoai bi lap 2 lan "Muc Luc" + 2 the id="bg-toc" trung
                // nhau (bug thuc te nguoi dung bao cao: "Muc luc co 2 khung"). ?>
                <?php pplus_render_toc_nested($toc['items']); ?>
                <div class="content-1 table-mg blog-p-no-margin" id="content-ckedit">
                    <?php // Bai viet SEO nam duoi danh sach the - hoan anh toi load+1s (xem pplus_defer_lazy_imgs()). ?>
                    <?php echo pplus_defer_lazy_imgs(pplus_kses_content($toc['html'])); ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <?php // Port dung tu reference-index.component.html dong 37-70 - xem TODO dau file
            // (chua noi that len Owner/IssueNewApartment, moi la giao dien). ?>
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
    <?php // Nut dong panel - dung y het reference-index.component.html dong 186-188 that
    // ("btnBackOffThongTin", CUNG goi showThongTin(), chi toggle class "active" tren "#web-content" -
    // main.js dong ~221 da dung chung 1 handler cho ca nut mo (".btnShowThongTin") lan nut dong nay
    // qua attribute "data-pplus-toggle=\"thong-tin\"", khong can code JS rieng). CSS "display:none"
    // mac dinh tren desktop (>880px, xem reference-index.less) - nut nay CHI hien tren mobile, dung y
    // het that. ?>
    <button class="btn btn-primary btnBackOffThongTin" type="button" data-pplus-toggle="thong-tin">
        <i class="fa fa-reply" aria-hidden="true"></i> <?php echo esc_html(pplus_localize('Quay lại')); ?>
    </button>
</div>

<?php get_footer(); ?>
