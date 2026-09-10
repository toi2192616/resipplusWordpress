<?php
/**
 * Template Name: Danh sach van phong
 * Trang /cho-thue-van-phong - port rut gon (ban SEO co ban, khong Google Maps/AJAX/roaming search)
 * tu src/app/partials/Search/search-base/search-base.component.html + office-list.component.html.
 *
 * Nguon du lieu: Office/_GetOffices (POST JSON) - xem inc/api-client.php: pplus_get_office_list().
 * Field payload da doi chieu truc tiep code .NET that (OfficeController.cs, OfficeFilter.cs,
 * ApiGetAllProjectInput.cs), khong doan qua Angular.
 *
 * TODO (ghi ro, khong chan hien thi - scope da chot la "SEO co ban truoc"):
 * - Co Google Maps (xem inc/maps.php + assets/js/pplus-maps.js) NHUNG chi hien marker cho cac
 *   van phong DA CO SAN o trang hien tai (server-side) - KHONG tu dong fetch lai khi keo/zoom map
 *   nhu search-base.component that (can 1 REST endpoint moi nhan ne_lat/ne_lng/sw_lat/sw_lng,
 *   chua co). Cung KHONG co tim theo ban kinh/khoanh ve tay (roaming/polygon search).
 * - Nut "Yeu thich"/"So sanh" chua noi (giu ben Angular theo quyet dinh da chot).
 */
if (!defined('ABSPATH')) exit;

get_header();

$lang_prefix = (pplus_current_lang() === 'en') ? '/en' : '';

$province      = sanitize_text_field($_GET['province'] ?? 'Hà Nội');
$district      = sanitize_text_field($_GET['district'] ?? '');
$ward          = sanitize_text_field($_GET['ward'] ?? '');
$price_from    = isset($_GET['price_from']) ? (float) $_GET['price_from'] : null;
$price_to      = isset($_GET['price_to']) ? (float) $_GET['price_to'] : null;
$project_type  = isset($_GET['project_type']) ? (int) $_GET['project_type'] : 2;
// "office_type" (4 loai con cua Van phong tron goi) CHI co y nghia khi project_type=1 (dich vu) -
// tranh gui nham OfficeType cu con luu trong query string khi nguoi dung da doi sang Van phong
// truyen thong (project_type=2), se lam Office/_GetOffices loc SAI (OfficeType chi ap dung cho
// dich vu, gui kem project_type=2 co the bi backend hieu nham).
$office_type   = ($project_type === 1 && isset($_GET['office_type'])) ? (int) $_GET['office_type'] : '';
$rating_level  = sanitize_text_field($_GET['rating_level'] ?? '');
// Doc qua get_query_var() (KHONG chi $_GET) - bug thuc te nguoi dung bao cao (12/8/2026): WordPress
// TU DONG redirect "?paged=2" sang dang duong dan "/cho-thue-van-phong/page/2/" (redirect_canonical(),
// hanh vi mac dinh khi permalink dang "dep" - xac nhan qua curl -I tren domain test: 301 -> .../page/2/).
// O dang duong dan nay, "2" nam trong URL PATH (qua rewrite rule goc cua WP cho Page), KHONG con
// trong query string nua nen $_GET['paged'] khong ton tai - chi doc $_GET se LUON ra $paged=1 du
// <title> va URL deu dung la "trang 2", khien trang 2 hien lai Y HET du lieu trang 1 (khong loi ro
// rang, chi la noi dung trung lap - de nham la "khong sang trang duoc"). get_query_var('paged') doc
// dung ca 2 dang (query string LAN duong dan, vi "paged" la public query var WP tu nhan dien).
$paged         = max(1, (int) get_query_var('paged', $_GET['paged'] ?? 1));

// Chon 1 goi y tu autocomplete "Địa điểm" (xem inc/location-search.php + assets/js/main.js):
// "project_id" (goi y Tên dự án) di THANG vao payload, khong can xu ly gi them. "street_name"
// (goi y Tên đường) la TEXT hien thi (vd "Kim Mã") - Office/_GetOffices chi nhan "StreetCode"
// (vd "D00129", xem pplus_get_office_list()), nen phai tu resolve 1 lan o day truoc khi loc
// (pplus_resolve_street_code(), co cache transient rieng, khong lap lai moi request).
$project_id  = isset($_GET['project_id']) ? (int) $_GET['project_id'] : 0;
$street_name = sanitize_text_field($_GET['street_name'] ?? '');
$street_code = ($street_name !== '') ? pplus_resolve_street_code($province, $street_name) : null;

// Vung ban do (ne_lat/ne_lng/sw_lat/sw_lng) - xem ghi chu pplus_get_office_list() trong
// inc/api-client.php: mot so tinh/thanh (vd Ho Chi Minh, khac Ha Noi) Office/_GetOffices se tra
// ve total_count=0 neu thieu bounds nay, du van phong that su ton tai. Trang nay (A1) truoc gio
// chua gui bounds (chi co page-reference.php/A4 lam) - la nguyen nhan loc theo TP.HCM luon
// "Khong tim thay" bat ke loai van phong/gia.
//
// UU TIEN bounds cap QUAN (khi co $district) thay vi CA TINH - bug thuc te da gap (nguoi dung bao
// cao): dung bounds ca tinh (vd toan bo Ha Noi) giao voi 1 quan XA TRUNG TAM (vd Hoang Mai) se loai
// SAI mot phan van phong that su nam trong quan do nhung ngoai vung bounds hep cua tinh - xac nhan
// truc tiep qua goi API that (Office/_GetOffices, District=Hoang Mai, gia 0-80): bounds ca tinh
// (tu Reference/GetIndexProvince) chi ra 4 ket qua, trong khi bounds DUNG QUAN (tu Reference/
// GetIndex voi alias cua chinh quan do, giong het cach page-reference.php/A4 lam cho trang quan
// rieng) hoac KHONG gui bounds nao ca deu ra dung 17 ket qua (khop nhau, xac nhan day la con so
// dung). page-reference.php (A4) khong bi loi nay vi MOI trang quan la 1 URL rieng, tu nhien da
// dung dung filter.zone_stringify cua CHINH quan do - A1 (trang chung, quan chi la 1 tham so loc)
// truoc gio luon dung bounds ca tinh cho MOI truong hop nen sai voi cac quan xa trung tam.
// "Tim theo ban kinh" (nut canh "So sanh", assets/js/radius-search.js) - nguoi dung chon 1 diem +
// khoang cach tren ban do, JS tu tinh san 1 hinh vuong noi tiep hinh tron do lam bounds MOI (dung
// dung thuat toan MapFilter() ben Angular that: innerRadius = radius/sqrt(2), metersPerDegLat =
// 111320...) roi dieu huong lai CHINH trang nay voi 4 tham so nay. UU TIEN HON han bounds theo
// quan/tinh mac dinh (nguoi dung da chu dong chon vung tim kiem cu the), nhung VAN GIU nguyen
// province/district/gia... trong URL - giong dung Angular that (MapFilter() chi ghi de 4 key
// ne_lat/ne_lng/sw_lat/sw_lng trong searchBase, KHONG xoa cac dieu kien loc khac dang co).
$roam_ne_lat = isset($_GET['ne_lat']) ? (float) $_GET['ne_lat'] : null;
$roam_ne_lng = isset($_GET['ne_lng']) ? (float) $_GET['ne_lng'] : null;
$roam_sw_lat = isset($_GET['sw_lat']) ? (float) $_GET['sw_lat'] : null;
$roam_sw_lng = isset($_GET['sw_lng']) ? (float) $_GET['sw_lng'] : null;
$is_roam_bounds = ($roam_ne_lat !== null && $roam_ne_lng !== null && $roam_sw_lat !== null && $roam_sw_lng !== null);

$map_bounds = null;
if ($is_roam_bounds) {
    $map_bounds = ['ne_lat' => $roam_ne_lat, 'ne_lng' => $roam_ne_lng, 'sw_lat' => $roam_sw_lat, 'sw_lng' => $roam_sw_lng];
} elseif ($province !== '') {
    // LUON lay bounds o CAP TINH/THANH (pplus_province_bounds(), xem ghi chu day du trong ham do -
    // inc/api-client.php), KHONG con goi rieng Reference/GetIndex cho quan nua - bounds cua RIENG 1
    // quan (zone_stringify cua no) qua hep, loai bo nham van phong o ria quan - bug thuc te da gap
    // (15/8/2026): "/cho-thue-van-phong/?district=Hoàn+Kiếm" tren local ra it hon han ban chinh cho
    // cung dieu kien. Viec loc THEO QUAN van dung nguyen $district (chuoi text, gui thang cho
    // Office/_GetOffices ben duoi) - bounds chi la lop bao ve tranh total_count=0 sai o 1 so tinh/
    // thanh (TPHCM), khong can chinh xac toi cap quan.
    $province_alias = pplus_find_province_alias_by_name($province);
    if ($province_alias !== '') {
        $map_bounds = pplus_province_bounds($province_alias);
    }
}

if ($project_id > 0) {
    // Chon 1 goi y "Tên dự án" tu autocomplete "Địa điểm" - loc CON DUNG 1 toa do (yeu cau nguoi
    // dung), phai BO HET moi dieu kien khac (Province/District/RatingLevel/gia/bounds...), KHONG
    // chi bounds. Da xac nhan qua goi API that: project_id VAN bi RatingLevel/bounds "giao" voi no
    // lam RONG ket qua neu du an do khong khop dung nhung dieu kien da chon TU TRUOC (vd dang o
    // trang "Hạng A" nhung du an duoc chon lai la Hạng C - bug thuc te da gap: chon dung du an tu
    // goi y nhung ket qua bao "Không tìm thấy"). Object rong ({}) that su O DAY dung nghia "khong
    // dieu kien nao khac", KHONG phai default_type=2 nhu binh thuong.
    //
    // Bug thuc te khac (16/8/2026): 1 SO du an (vd project_id=2043 Pearl 5 Building, TPHCM) can
    // THEM $map_bounds moi tra ve ket qua (KHONG bounds -> total_count=0/rong; CO bounds TPHCM ->
    // total_count=1 dung). NHUNG bug NGUOC LAI cung co that (17/8/2026, vd project_id=284 Keangnam
    // Landmark Ha Noi Tower): toa do THAT cua du an nam NGOAI bounds cap tinh tinh tu zone_stringify
    // (KHONG bounds -> total_count=1 dung; CO bounds Ha Noi -> total_count=0 SAI) - bounds cap tinh
    // KHONG du rong bao trum HET moi du an that. Dung ham fallback (thu KHONG bounds truoc, CHI
    // them bounds neu rong) de an toan ca 2 chieu - xem ghi chu day du trong
    // pplus_get_office_list_with_bounds_fallback() (inc/api-client.php).
    $result = pplus_get_office_list_with_bounds_fallback(['project_id' => $project_id, 'page' => $paged], $map_bounds);
} elseif ($street_code !== null) {
    // Chon 1 goi y "Tên đường" - loc theo TAT CA du an gan ma duong do, giu lai project_type (mac
    // dinh 2, giong het nhanh $is_street cua page-reference.php), KHONG bo Gia/Hang (xem ghi chu
    // price_from/price_to/rating_level ben duoi - da doi lai tu "bo het" ngay ban dau).
    // "office_type" (4 loai con cua Van phong tron goi, vd "VP riêng") KHONG duoc coi la 1
    // "dieu kien loc cu" can bo - no la 1 PHAN CUA "loai van phong" dang chon CUNG LUC voi duong,
    // khong phai dieu kien rieng biet truoc do (khac han khu vuc/gia/hang) - bug thuc te nguoi
    // dung bao cao: chon Duong + Van phong tron goi + "VP riêng" van ra ca 4 loai hinh, vi
    // office_type bi rot mat o day (chi con lai project_type=1, khop CA 4 loai con).
    //
    // "price_from"/"price_to"/"rating_level" GIU LAI (KHAC han nhanh project_id o tren) - bug thuc
    // te nguoi dung bao cao (12/8/2026): go "Duy Tân" vao o Dia diem, dat gia 10$-15$, bam Tim kiem
    // van ra ca toa gia $23 (Epic Tower). Ly do: chon 1 DUONG qua goi y la 1 TAP HOP van phong
    // (giong het nhap thang URL /duy-tan/, xem nhanh $is_street cua page-reference.php), khac han
    // chon 1 DU AN CU THE qua project_id (1 toa nha DUY NHAT, gia/hang cua bo loc co the "giao
    // rong" nham thanh 0 ket qua nhu comment nhanh project_id o tren da giai thich) - nen gia/hang
    // VAN can loc tiep tren tap hop van phong cua duong do, khong duoc bo nhu project_id.
    //
    // Bounds them 16/8/2026 - CUNG 1 nguyen nhan voi nhanh project_id o tren: da xac nhan qua goi
    // that street_code=D00468 ("Nguyễn Thị Minh Khai", TPHCM) KHONG bounds -> total_count=0/rong;
    // CUNG street_code do + bounds TPHCM -> total_count=35 dung (item dau "ThaiSquare The Merit").
    // NHUNG cung co the LAM HONG duong khac neu toa do nam ngoai bounds cap tinh (cung 1 rui ro voi
    // project_id, xem ghi chu day du trong pplus_get_office_list_with_bounds_fallback()) - dung ham
    // fallback thay vi ep bounds thang.
    $result = pplus_get_office_list_with_bounds_fallback([
        'street_code'  => $street_code,
        'project_type' => $project_type,
        'office_type'  => $office_type,
        'price_from'   => $price_from,
        'price_to'     => $price_to,
        'rating_level' => $rating_level,
        'page'         => $paged,
    ], $map_bounds);
} else {
    // "Tim theo ban kinh" dang hoat dong (co ne_lat.. tu radius-search.js, xem ghi chu $is_roam_bounds
    // o tren) - BO HAN Province/District/Ward (du $_GET co gia tri gi, ke ca "Hà Nội" mac dinh cua
    // $province), CHI loc theo vung ban do da chon. Nguoi dung bao cao thuc te: dang o 1 quan, chon
    // diem/duong o quan KHAC tren ban do van bi giu nguyen dieu kien quan cu -> giao voi vung ban do
    // moi ra RONG (khong tim thay du an, du du an that su ton tai trong vung do). radius-search.js
    // (nut "Tim kiem" trong panel) cung DA doi huong ve URL tong quat "/cho-thue-van-phong/" (khong
    // con o URL rieng cua 1 quan/huyen nua) truoc khi them ne_lat.., nen thuc te $province/$district
    // se rong san - dong nay la lop bao ve THEM (phong khi ai do tu ghep URL co ca ne_lat.. LAN
    // province/district).
    $result = pplus_get_office_list(array_merge([
        'province'     => $is_roam_bounds ? '' : $province,
        'district'     => $is_roam_bounds ? '' : $district,
        'ward'         => $is_roam_bounds ? '' : $ward,
        'price_from'   => $price_from,
        'price_to'     => $price_to,
        'project_type' => $project_type,
        'office_type'  => $office_type,
        'rating_level' => $rating_level,
        'page'         => $paged,
    ], $map_bounds ?? []));
}

$page_count  = 18;
$total_count = (int) ($result['total_count'] ?? 0);
$total_pages = max(1, (int) ceil($total_count / $page_count));

// Du lieu marker cho Google Maps (assets/js/pplus-maps.js renderListMap()) - chi cac van phong
// da co san o trang hien tai (khong fetch lai khi pan/zoom, xem TODO tren dau file). "urlHerf" da
// la URL dung duoc thang (giong cach parts/office-card.php dung, khong can home_url()/lang_prefix).
$map_markers = array_map(static function (array $item): array {
    return [
        'id'      => 'office-' . ($item['projectId'] ?? ''),
        'lat'     => isset($item['lat']) ? (float) $item['lat'] : null,
        'lng'     => isset($item['lng']) ? (float) $item['lng'] : null,
        'title'   => $item['projectName'] ?? '',
        'address' => $item['address'] ?? '',
        'rating'  => $item['ratingLevel'] ?? '',
        'price'   => $item['itemPrice'] ?? '',
        'photo'   => !empty($item['photoList'][0]) ? pplus_admin_asset_url($item['photoList'][0]) : '',
        'url'     => pplus_office_url($item['urlHerf'] ?? ''),
    ];
}, $result['listOffice'] ?? []);
?>
<?php get_template_part('parts/office-filter-panel', null, [
    'current' => [
        'province'     => $province,
        'district'     => $district,
        'ward'         => $ward,
        'project_type' => $project_type,
        'office_type'  => $office_type,
        'rating_level' => $rating_level,
        // Gia: chi hien lai trong panel khi CO submit that qua $_GET (isset), tranh de gia mac
        // dinh cua rieng trang nay (vd $price_from=null luc chua co $_GET) lam panel tu ap dat
        // 0-80/rong sai boi vi office-filter-panel.php da tu xu ly gia mac dinh dung theo
        // project_type (xem ghi chu $current['price_from'] o do) khi KHONG truyen 2 field nay.
        'price_from'   => isset($_GET['price_from']) ? $price_from : '',
        'price_to'     => isset($_GET['price_to']) ? $price_to : '',
    ],
]); ?>

<div class="map-container">
    <div class="map-office">
        <div class="box-content row">
            <div class="col-md-12 box-list-header pull-left">
                <div class="url-content">
                    <div class="url-content-list">
                        <a href="<?php echo esc_url(home_url('/')); ?>"><?php echo esc_html(pplus_t('Trang chủ', 'Home')); ?></a>
                        &nbsp;&nbsp;
                        <a href="<?php echo esc_url(home_url($lang_prefix . '/' . pplus_office_list_base_slug() . '/')); ?>"><?php echo esc_html(pplus_t('Cho thuê văn phòng', 'Office for rent')); ?></a>
                    </div>
                </div>
                <div class="text-find" id="text-find">
                    <div class="title-search">
                        <?php echo esc_html(pplus_t('Tìm văn phòng cho thuê', 'Find offices for rent')); ?> | <?php echo esc_html(pplus_t('Cập nhật', 'Update')); ?> <?php echo esc_html(date_i18n('m/Y')); ?>
                    </div>
                    <div class="action-search">
                        <?php // Nut "Tim kiem" doi giao dien (20/8/2026) - xem ghi chu day du trong
                        // page-reference.php (cung 1 khoi giong het, chi khac ngu canh trang). ?>
                        <?php // Dung LAI y het nut "Tìm kiếm văn phòng" (parts/url-box-content.php) - xem
                        // ghi chu day du trong page-reference.php (cung 1 khoi giong het). ?>
                        <button type="button" class="btn btn-action-search-base" id="btn-action-filter" data-pplus-toggle="search">
                            <i><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/search-filter-white.png'); ?>" loading="lazy" alt="" /></i>
                            <?php echo esc_html(pplus_t('Tìm kiếm văn phòng', 'Search for offices')); ?>
                        </button>
                        <?php // Mo/dong panel "Tim theo ban kinh" (assets/js/radius-search.js) - port
                        // actionCustomMap() (search-base.component.ts). ?>
                        <button type="button" class="btn-action-search btn-action-map" id="pplus-roam-toggle">
                            <i><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M105.6 83.2v86.177a115.52 115.52 0 0 0-22.4-2.176c-47.914 0-83.2 35.072-83.2 92 0 45.314 48.537 57.002 78.784 75.707 12.413 7.735 23.317 16.994 33.253 25.851l.146.131.148.129C129.807 376.338 136 384.236 136 391.2v2.679c-4.952 5.747-8 13.536-8 22.12v64c0 17.673 12.894 32 28.8 32h230.4c15.906 0 28.8-14.327 28.8-32v-64c0-8.584-3.048-16.373-8-22.12V391.2c0-28.688 40-67.137 40-127.2v-21.299c0-62.542-38.658-98.8-91.145-99.94-17.813-12.482-40.785-18.491-62.791-15.985A93.148 93.148 0 0 0 272 118.847V83.2C272 37.765 234.416 0 188.8 0c-45.099 0-83.2 38.101-83.2 83.2zm118.4 0v91.026c14.669-12.837 42.825-14.415 61.05 4.95 19.646-11.227 45.624-1.687 53.625 12.925 39.128-6.524 61.325 10.076 61.325 50.6V264c0 45.491-35.913 77.21-39.676 120H183.571c-2.964-25.239-21.222-42.966-39.596-59.075-12.65-11.275-25.3-21.725-39.875-30.799C80.712 279.645 48 267.994 48 259.2c0-23.375 8.8-44 35.2-44 35.2 0 53.075 26.4 70.4 26.4V83.2c0-18.425 16.5-35.2 35.2-35.2 18.975 0 35.2 16.225 35.2 35.2zM352 424c13.255 0 24 10.745 24 24s-10.745 24-24 24-24-10.745-24-24 10.745-24 24-24z"/></svg></i>
                            <?php echo esc_html(pplus_t('Tìm theo bán kính', 'Search by radius')); ?>
                        </button>
                        <?php // Mo lai modal danh sach mini (parts/compare-modal.php #modal-compare-list) voi danh
                        // sach DANG CO (khong them gi ca) - xem assets/js/compare.js
                        // ".js-open-compare-list". ?>
                        <button type="button" class="btn-action-search js-open-compare-list">
                            <i><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/icon-kitchen.png'); ?>" alt="Icon Compare" loading="lazy" /></i>
                            <?php echo esc_html(pplus_t('So sánh', 'Compare')); ?>
                        </button>
                        <button type="button" class="btn-action-search" id="btn-action-map">
                            <i><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/icon-map.png'); ?>" alt="Icon Map" loading="lazy" /></i>
                            <?php echo esc_html(pplus_t('Bản đồ', 'Map')); ?>
                        </button>
                    </div>
                </div>
            </div>
            <div class="box-list init col-md-7">
                <div class="box-list-body">
                    <?php // Panel "Tim theo ban kinh" (an mac dinh, JS hien khi bam nut tren) - port
                    // ".from-search-roam" (search-base.component.html dong 624-712). CSS da co san
                    // tu truoc (search-base-component.css), o day chi can dung markup. Gia tri
                    // price_from/price_to lay tu bo loc HIEN TAI ($_GET) de dong bo, nguoi dung co
                    // the sua truc tiep trong panel nay truoc khi bam "Tim kiem". ?>
                    <?php // "data-project-type"/"data-office-type"/"data-rating-level" - de radius-search.js
                    // mang theo dung dieu kien LOAI van phong/hang (KHONG lien quan khu vuc dia ly) khi
                    // dieu huong sang URL tong quat "/cho-thue-van-phong/" (xem ghi chu $is_roam_bounds
                    // o dau file: chi Province/District/Ward bi BO, cac dieu kien khac giu nguyen, giong
                    // dung Angular that MapFilter() chi ghi de 4 key bounds). ?>
                    <div class="from-search-roam" id="pplus-roam-panel" style="display:none;" data-project-type="<?php echo esc_attr($project_type); ?>" data-office-type="<?php echo esc_attr($office_type); ?>" data-rating-level="<?php echo esc_attr($rating_level); ?>">
                        <div class="row col-findter">
                            <div class="col-md-5 pplus-show-group-search">
                                <input type="text" class="input-search-google-map form-control" id="pplus-roam-address" placeholder="<?php echo esc_attr(pplus_t('Tìm địa chỉ hoặc chỉ vào 1 vị trí trên bản đồ', 'Search an address or click a point on the map')); ?>" autocomplete="off" />
                                <div class="pplus-show-group">
                                    <div class="text-data">
                                        <?php // Ban .webp (13/8/2026) qua image-set() - xem parts/header.php. ?>
                                        <?php $pplus_logo_png = esc_url(get_template_directory_uri() . '/assets/officebase/Common/Images/Home/logo_gold-text.png'); ?>
                                        <?php $pplus_logo_webp = esc_url(get_template_directory_uri() . '/assets/officebase/Common/Images/Home/logo_gold-text.webp'); ?>
                                        <span class="locations-ico" style="background-image:url('<?php echo $pplus_logo_png; ?>');background-image:image-set(url('<?php echo $pplus_logo_webp; ?>') type('image/webp'), url('<?php echo $pplus_logo_png; ?>') type('image/png'));"></span>
                                        <?php echo esc_html(pplus_t('Tìm theo định vị google', 'Search by Google location')); ?>
                                    </div>
                                    <div class="content-data" id="pplus-roam-suggestions"></div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="input-group">
                                    <input type="text" class="input-roam-distance form-control" id="pplus-roam-price-from" placeholder="<?php echo esc_attr(pplus_t('Giá từ ...', 'Price from ...')); ?>" value="<?php echo esc_attr($price_from ?? ''); ?>" inputmode="numeric" />
                                    <span class="input-group-text">$</span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="input-group">
                                    <input type="text" class="input-roam-distance form-control" id="pplus-roam-price-to" placeholder="<?php echo esc_attr(pplus_t('Giá đến ...', 'Price to ...')); ?>" value="<?php echo esc_attr($price_to ?? ''); ?>" inputmode="numeric" />
                                    <span class="input-group-text">$</span>
                                </div>
                            </div>
                            <div class="col-md-1">
                                <div class="distance-wrapper">
                                    <div class="input-group">
                                        <input type="number" class="input-roam-distance form-control" id="pplus-roam-distance" placeholder="<?php echo esc_attr(pplus_t('Nhập khoảng cách', 'Enter distance')); ?>" value="2" autocomplete="off" min="0" step="1" />
                                        <span class="input-group-text">km</span>
                                    </div>
                                    <ul class="distance-options">
                                        <li data-value="1">1 km</li>
                                        <li data-value="2">2 km</li>
                                        <li data-value="5">5 km</li>
                                        <li data-value="10">10 km</li>
                                        <li data-value="20">20 km</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-action">
                            <button type="button" class="btn btn-outline-primary" id="pplus-roam-search"><i class="fa fa-search"></i> <?php echo esc_html(pplus_t('Tìm kiếm', 'Search')); ?></button>
                            <button type="button" class="btn btn-outline-secondary ml-5" id="pplus-roam-cancel"><?php echo esc_html(pplus_t('Hủy', 'Cancel')); ?></button>
                        </div>
                    </div>
                    <div class="result-box" id="result-box">
                    <?php if ($result === null) : ?>
                        <?php pplus_render_api_error(); ?>
                    <?php elseif (empty($result['listOffice'])) : ?>
                        <div class="pplus-notice"><?php echo esc_html(pplus_t('Không tìm thấy văn phòng phù hợp.', 'No matching offices found.')); ?></div>
                    <?php else : ?>
                        <div class="list-product-office">
                            <?php foreach ($result['listOffice'] as $i => $item) : ?>
                                <?php get_template_part('parts/office-card', null, ['item' => $item, 'index' => $i]); ?>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($total_pages > 1) : ?>
                            <nav class="pplus-pagination" style="margin: 20px 0; text-align:center;">
                                <?php
                                echo paginate_links([
                                    'base'      => esc_url_raw(add_query_arg('paged', '%#%')),
                                    'format'    => '',
                                    'current'   => $paged,
                                    'total'     => $total_pages,
                                    'prev_text' => '«',
                                    'next_text' => '»',
                                    'type'      => 'list',
                                ]);
                                ?>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php
            $map_fallback_lat = $map_bounds ? (($map_bounds['ne_lat'] + $map_bounds['sw_lat']) / 2) : null;
            $map_fallback_lng = $map_bounds ? (($map_bounds['ne_lng'] + $map_bounds['sw_lng']) / 2) : null;
            ?>
            <?php // Mobile: mac dinh o che do "Noi dung" (danh sach), ban do an di qua class
            // "invision-map" (CSS co san trong layout.css, opacity:0;position:absolute;z-index:-3)
            // - port dung theo search-base.component.ts (fullContent=true mac dinh tren mobile).
            // 2 nut noi goc duoi-phai (.GroupMoblieActive, chi "Bản đồ" - trang nay khong co bai
            // viet SEO nen khong can nut "Hiển thị thông tin") de chuyen sang xem full ban do; nut
            // tron "<" (.action-close-map, nam TRONG .box-map nen tu dong an theo cung opacity)
            // de quay lai. Truoc day thieu ca 2, khien ban do luon o trang thai fixed-fullscreen
            // (khong tat duoc) - da tam vao bang z-index o .box-list-header, gio moi la fix goc. ?>
            <div class="col-md-5 box-map invision-map">
                <div class="action-close-map-label" data-pplus-toggle="map-view" title="<?php echo esc_attr(pplus_t('Đóng bản đồ', 'Close map')); ?>">
                    <i class="fa fa-reply" aria-hidden="true"></i> <?php echo esc_html(pplus_t('Quay lại', 'Back')); ?>
                </div>
                <?php // Nut "ban tay" bat/tat che do bam chon tam diem tren ban do - CHI hien khi panel
                // "Tim theo ban kinh" dang mo (assets/js/radius-search.js dieu khien display), port
                // ClickActiveRoaMingMap()/btn-action-roamingclick (search-base.component.html dong
                // 758-765). ?>
                <button type="button" class="btn-action-roamingclick" id="pplus-roam-hand" style="display:none;" title="<?php echo esc_attr(pplus_t('Bấm để chọn tâm điểm trên bản đồ', 'Click to pick a center point on the map')); ?>">
                    <i class="hand-container">
                        <svg class="hand-icon" id="pplus-roam-hand-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
                            <path d="M358.182 179.361c-19.493-24.768-52.679-31.945-79.872-19.098-15.127-15.687-36.182-22.487-56.595-19.629V67c0-36.944-29.736-67-66.286-67S89.143 30.056 89.143 67v161.129c-19.909-7.41-43.272-5.094-62.083 8.872-29.355 21.795-35.793 63.333-14.55 93.152l109.699 154.001C134.632 501.59 154.741 512 176 512h178.286c30.802 0 57.574-21.5 64.557-51.797l27.429-118.999A67.873 67.873 0 0 0 448 326v-84c0-46.844-46.625-79.273-89.818-62.639zM80.985 279.697l27.126 38.079c8.995 12.626 29.031 6.287 29.031-9.283V67c0-25.12 36.571-25.16 36.571 0v175c0 8.836 7.163 16 16 16h6.857c8.837 0 16-7.164 16-16v-35c0-25.12 36.571-25.16 36.571 0v35c0 8.836 7.163 16 16 16H272c8.837 0 16-7.164 16-16v-21c0-25.12 36.571-25.16 36.571 0v21c0 8.836 7.163 16 16 16h6.857c8.837 0 16-7.164 16-16 0-25.121 36.571-25.16 36.571 0v84c0 1.488-.169 2.977-.502 4.423l-27.43 119.001c-1.978 8.582-9.29 14.576-17.782 14.576H176c-5.769 0-11.263-2.878-14.697-7.697l-109.712-154c-14.406-20.223 14.994-42.818 29.394-22.606z"/>
                        </svg>
                        <span class="spark"></span>
                    </i>
                </button>
                <div id="map" data-markers="<?php echo esc_attr(wp_json_encode($map_markers)); ?>" data-fallback-lat="<?php echo esc_attr($map_fallback_lat ?? ''); ?>" data-fallback-lng="<?php echo esc_attr($map_fallback_lng ?? ''); ?>"></div>
            </div>
            <?php // Dai the san pham noi tren ban do (mobile, che do xem ban do) - port
            // ".box-list-body-mobile" (search-base.component.html dong 780-782, <app-office-list>
            // ben trong). CSS (".box-list-body-mobile .item-office", search-base-component.css) da
            // co san tu truoc chi thieu markup - dung LAI office-card.php (cung 1 class ".item-office",
            // cung du lieu $result['listOffice']), khong can partial rieng. Bam marker tren ban do se
            // cuon toi dung the tuong ung o day (assets/js/pplus-maps.js, thay cho InfoWindow popup
            // kieu PC tren mobile) - khop qua "data-id" (".item-office[data-id]" <-> marker item.id,
            // deu la "office-{projectId}"). ?>
            <?php if (!empty($result['listOffice'])) : ?>
                <div class="box-list-body-mobile">
                    <div class="list-product-office">
                        <?php foreach ($result['listOffice'] as $item) : ?>
                            <?php get_template_part('parts/office-card', null, ['item' => $item]); ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            <div class="GroupMoblieActive">
                <div class="btnBackToMap-gr" data-pplus-toggle="map-view" title="<?php echo esc_attr(pplus_t('Bản đồ', 'Map')); ?>">
                    <i>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.48698 22.011L0.75 24V8.13201L6.48698 6.23103V22.011ZM12.9734 23.8064L7.23641 21.923V6.055L12.9734 8.04401V23.8064ZM19.7268 11.802V21.8262L13.8003 23.7976V11.2387C14.076 11.5115 14.4378 11.7052 14.8168 11.8988C15.5576 12.1716 16.3846 12.2772 17.1254 12.2772C17.9696 12.2772 18.7965 12.1716 19.5287 11.8988C19.5919 11.8284 19.6551 11.7932 19.7182 11.7932L19.7268 11.802ZM18.1505 6.51265L19.7268 6.0462V8.88009C19.6637 8.88009 19.6005 8.85075 19.5373 8.79207C19.0808 8.68646 18.6156 8.59845 18.1505 8.50164V6.51265ZM16.9531 0H17.134C17.5044 0 17.8748 0.378431 17.8748 0.844877C17.8748 1.0385 17.7801 1.32893 17.5905 1.41694V9.07371C18.2366 9.07371 18.8913 9.16172 19.3564 9.34653C19.9077 9.63696 20.2781 9.91859 20.2781 10.385C20.2781 10.7635 19.9077 11.0451 19.3564 11.3355C18.7965 11.5203 18.0557 11.626 17.1254 11.626C16.2898 11.626 15.5576 11.5203 14.9977 11.3355C14.4378 11.0451 14.0674 10.7635 14.0674 10.385C14.0674 9.91859 14.4378 9.62816 14.9977 9.34653C15.3681 9.25853 15.928 9.17052 16.4707 9.07371V1.41694C16.2898 1.32893 16.1003 1.0385 16.1003 0.844877C16.1003 0.378431 16.4707 0 16.9445 0H16.9531ZM18.5209 1.51375L23.1552 3.30913H23.25L23.1552 3.39714L18.5209 5.47415C18.34 5.47415 18.2452 5.47415 18.1505 5.38614C18.0643 5.29813 18.0643 5.19251 17.9696 5.00769V2.08581C17.9696 1.79538 18.0557 1.70736 18.1505 1.60175C18.2452 1.51375 18.3314 1.51375 18.5209 1.51375ZM17.134 11.3355C17.9782 11.3355 18.7104 11.2475 19.1669 10.9571C19.7268 10.8691 20.0111 10.5787 20.0111 10.385C20.0111 10.1122 19.7268 9.83058 19.1669 9.72497C18.7965 9.54015 18.2452 9.43454 17.5992 9.34653V10.209C17.5992 10.297 17.5044 10.4818 17.4183 10.4818C17.3235 10.5875 17.2374 10.6755 17.134 10.6755H17.0479C16.867 10.6755 16.7636 10.5875 16.6775 10.4818C16.4879 10.3938 16.4879 10.297 16.4879 10.121V9.35533C15.9453 9.44334 15.4715 9.54896 15.1011 9.73378C14.6359 9.83939 14.3603 10.1298 14.3603 10.3938C14.3603 10.5875 14.6359 10.8779 15.1011 10.9659C15.661 11.2563 16.4018 11.3443 17.134 11.3443V11.3355ZM13.809 8.0352L15.8419 7.26953V8.50164C15.4629 8.58965 15.0925 8.68646 14.8254 8.79207C14.4464 8.97689 14.076 9.17052 13.809 9.44335V8.04401V8.0352Z" fill="white"/></svg>
                    </i>
                </div>
            </div>
        </div>
    </div>
</div>
<?php get_footer(); ?>
