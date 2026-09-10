<?php
/**
 * A4 - Landing theo tinh/alias-loai-hinh (module Angular "reference-index").
 * Route dang ky trong inc/reference-index-route.php (rewrite rule, KHONG phai Page).
 * Port rut gon tu src/app/modules/reference/reference-index/reference-index.component.html.
 *
 * QUAN TRONG: Angular goc dong 1 cua template nay LUON nhung <app-search-base> (khong ngIf, khong
 * dieu kien gi ca) - toolbar+luoi san pham+Google Maps LUON hien tren MOI trang A4, khong rieng gi
 * trang quan/huyen. Truoc day hieu nham "filter.district rong => trang loai=3 (dich vu) chi la bai
 * viet, an het khoi nay" - SAI, da xac nhan lai qua production that (vd cho-thue-cho-ngoi-co-dinh
 * VAN hien du breadcrumb+toolbar+luoi+map y het trang quan/huyen, chi khac o cho no loc theo
 * ProjectType=1 (dich vu) + OfficeType (vd 2="chỗ ngồi cố định") THAY VI theo District - xac nhan
 * truc tiep qua API (filter.district rong nhung filter.projectType=1 va filter.officeType=2 cho
 * trang nay, dung 2 truong do goi Office/_GetOffices se ra dung 116 ket qua, dung item dau tien
 * "HQ Hà Đông Millennium" khop y het production).
 */
if (!defined('ABSPATH')) exit;

$model = pplus_get_reference_index_from_query_vars();

if (empty($model['dataSelected'])) {
    status_header(404);
    get_header();
    echo '<div class="container" style="padding:40px 0;">';
    pplus_render_api_error();
    echo '</div>';
    get_footer();
    return;
}

$data          = $model['dataSelected'];
$h1            = ($data['loai'] ?? '') === '6' ? ($data['subTitle'] ?? '') : ($data['title'] ?? '');
$lang_prefix   = (pplus_current_lang() === 'en') ? '/en' : '';
$filter        = $model['filter'] ?? [];
// Loai quyet dinh CHINH XAC kieu trang nay (doi chieu truc tiep dataSelected.loai == typeRerence
// qua API that, KHONG doan qua filter.district/officeType co rong hay khong nhu truoc - cach cu bo
// sot han 2 loai trang "Tim theo duong" (loai=6, filter.street/streetCode co gia tri, district/
// officeType deu rong) va "Tim theo hang" (loai=4, filter.ratingLevel co gia tri, district/
// officeType/street deu rong), khien show_office_search sai thanh false, an mat toan bo
// toolbar+luoi+map tren 2 loai trang do - xem app-component-base.ts SetBaseReferenceTypeBase()
// (type 1/8=quan huyen, 3=dich vu, 4=hang, 6=duong).
$loai             = (string) ($data['loai'] ?? '');
$is_district      = in_array($loai, ['1', '8'], true);
$is_service_type  = $loai === '3';
$is_rank          = $loai === '4';
$is_street        = $loai === '6';
$show_office_search  = $is_district || $is_service_type || $is_rank || $is_street;
$province_alias = sanitize_text_field(get_query_var('pplus_ref_province'));
// "province" LUON co mat trong query string moi lan panel "Bo loc" submit (xem ghi chu chi tiet
// o gan $office_args ben duoi) - dung lam co bao hieu day la 1 lan loc that tu nguoi dung, can
// truoc ca buoc tinh $map_bounds (ben duoi) vi Ho Chi Minh CAN dung bounds cua chinh no moi ra
// ket qua, khong the dung tam bounds cua tinh khac trang dang hien (vd dang o trang Ha Noi).
$is_filter_submit = isset($_GET['province']);
$filter_province   = $is_filter_submit ? sanitize_text_field($_GET['province']) : '';

// Hang "pill" goi y ngay duoi H1 (".url-content-list-children") TUNG la danh sach quan/phuong/
// hang/loai-dich-vu lien quan (sibling cua trang dang xem) - theo yeu cau nguoi dung, doi lai
// THANH BO LOC GIA NHANH (5 khoang gia co dinh $/m2) CHO 4 loai trang Quan/Phuong/Hang/Duong (xem
// $pplus_price_quick_options + markup gan bien $office_filter_current ben duoi). RIENG trang DICH
// VU (loai=3, $is_service_type): theo yeu cau nguoi dung, GIU NGUYEN hang goi y CU (cac loai dich
// vu khac CUNG TINH, vd tu "Chỗ ngồi cố định" goi y sang "Văn phòng ảo"/"Trọn gói"/"Chỗ ngồi linh
// hoạt") - KHONG doi sang bo loc gia $/m2, vi dich vu niem yet gia theo goi (VND/thang, xem
// office-card.php $is_traditional) chu khong theo $/m2 nhu van phong truyen thong, bo loc gia $/m2
// khong hop ngu canh trang nay.
$dich_vu_siblings = [];
$pplus_vi_alias_map_dv = (pplus_current_lang() === 'en') ? pplus_get_header_vi_alias_map() : [];
if ($is_service_type && !empty($province_alias)) {
    $header_for_dv = pplus_get_header_data();
    foreach ((array) ($header_for_dv['listMenu'] ?? []) as $itemMenu) {
        $dv_menu = $itemMenu['menu'] ?? [];
        if (($dv_menu['alias'] ?? '') !== $province_alias || empty($itemMenu['dataListVPDichVu'])) {
            continue;
        }
        foreach ($itemMenu['dataListVPDichVu'] as $dv_item) {
            if (($dv_item['alias'] ?? '') === ($data['alias'] ?? '')) {
                continue; // bo qua chinh loai dang xem
            }
            $dich_vu_siblings[] = $dv_item;
        }
        break;
    }
}

// Vung ban do cua khu vuc dang tim (4 goc chu nhat, filter.zone_stringify) - can truoc buoc goi
// pplus_get_office_list() ben duoi. XAC NHAN QUA DOI CHIEU TRUC TIEP API THAT: search-base.component.ts
// (Angular) KHONG BAO GIO goi GetOffices chi voi Province/District dang chuoi TEXT - luon gui KEM
// bounds nay (MapFilter(), doc tu Google Map dang hien). Thieu bounds, mot so tinh/thanh (da kiem
// chung: TPHCM, khac Ha Noi) tra ve total_count=0 du van phong that su ton tai trong DB (bug thuc te
// gay ra "Không tìm thấy văn phòng phù hợp" sai o MOI trang quan/huyen TPHCM) - vi Province/District
// dang la text tu do, khop khong dang tin cay bang toa do dia ly that.
// "Tim theo ban kinh" (nut canh "So sanh", assets/js/radius-search.js) - UU TIEN HON han bounds
// theo quan/tinh mac dinh, xem ghi chu day du trong page-office-list.php (cung 1 co che, chi khac
// noi $map_bounds duoc dung).
$roam_ne_lat = isset($_GET['ne_lat']) ? (float) $_GET['ne_lat'] : null;
$roam_ne_lng = isset($_GET['ne_lng']) ? (float) $_GET['ne_lng'] : null;
$roam_sw_lat = isset($_GET['sw_lat']) ? (float) $_GET['sw_lat'] : null;
$roam_sw_lng = isset($_GET['sw_lng']) ? (float) $_GET['sw_lng'] : null;
$is_roam_bounds = ($roam_ne_lat !== null && $roam_ne_lng !== null && $roam_sw_lat !== null && $roam_sw_lng !== null);

$map_bounds = null;
if ($is_roam_bounds) {
    $map_bounds = ['ne_lat' => $roam_ne_lat, 'ne_lng' => $roam_ne_lng, 'sw_lat' => $roam_sw_lat, 'sw_lng' => $roam_sw_lng];
} else {
    // Uu tien zone RIENG cua khu vuc dang xem (filter.zone_stringify tu GetIndex) lam bounds - khop
    // dung cach Angular hoat dong (map fit vao chinh khu vuc dang xem roi gui viewport do len
    // _GetOffices), va TRANH bug zone cap tinh Ha Noi bi lech ve dong (sw_lng=105.791 khong phu tay
    // HN: Cau Giay/Nam Tu Liem/Ha Dong ~105.78) lam trang tay HN thieu/rong ket qua. Xac nhan qua API
    // that (18/8/2026): Cau Giay zone-tinh=64 vs zone-rieng=113 (dung); duong Duy Tan zone-tinh=0 vs
    // khong-bounds=36. Angular lay du du lieu chinh vi no dung zone rieng cua khu vuc, KHONG dung zone
    // tinh nhu code cu o day.
    //
    // Danh doi (theo yeu cau nguoi dung, chap nhan): zone rieng cua quan co the HEP hon 1 chut o ria
    // quan (dung bug 15/8/2026 tung khien doi sang zone tinh) - doi lai dung duoc tay Ha Noi.
    //
    // Ngoai le: khi doi TINH qua panel "Bo loc" (tinh khac trang dang xem, vd dang "Ha Noi > Hoan Kiem"
    // vua chon "TP.HCM"), zone rieng $filter la cua tinh CU -> sai; luc do fallback ve zone CAP TINH
    // cua tinh MOI chon (khong co zone rieng cua khu vuc moi o day).
    if ($is_filter_submit && $filter_province !== '' && $filter_province !== ($filter['province'] ?? '')) {
        $filter_province_alias = pplus_find_province_alias_by_name($filter_province);
        $map_bounds = $filter_province_alias !== '' ? pplus_province_bounds($filter_province_alias) : null;
    } else {
        $map_bounds = pplus_zone_to_bounds($filter['zone_stringify'] ?? null);
        // Khu vuc khong co zone rieng (hiem) -> zone cap tinh cua chinh trang lam luoi an toan.
        if ($map_bounds === null && $province_alias !== '') {
            $map_bounds = pplus_province_bounds($province_alias);
        }
    }
}
// Tam ban do khi KHONG co van phong nao (0 marker) - truyen qua data-fallback-lat/-lng cho
// renderListMap() trong pplus-maps.js. Truoc day khi 0 marker JS bo qua luon khong tao Google Map,
// de lai #map trang hoan toan (khong phai loi CSS).
$map_fallback_center = $map_bounds ? [
    'lat' => ($map_bounds['ne_lat'] + $map_bounds['sw_lat']) / 2,
    'lng' => ($map_bounds['ne_lng'] + $map_bounds['sw_lng']) / 2,
] : null;

$office_result = null;
$map_markers   = [];
if ($show_office_search) {
    $paged = max(1, (int) ($_GET['paged'] ?? 1));
    // MOI truong loc (province/district/ward/project_type/price/rating) co the den tu 2 nguon:
    // filter goc cua chinh trang (API tra ve theo alias trong URL) HOAC tu chinh panel "Bo loc"
    // (parts/office-filter-panel.php) khi nguoi dung doi dieu kien ngay tren trang nay - xem ghi
    // chu trong assets/js/main.js: MOI truong deu la 1 dieu kien loc thuan tuy, submit se GIU
    // NGUYEN URL (path) hien tai, chi doi/them query string, KHONG dieu huong sang trang khac.
    //
    // QUAN TRONG: "province" LUON co mat trong query string moi lan panel submit (kê ca la field
    // dau tien, luon co gia tri - xem $form.serialize() trong main.js), nen dung isset($_GET
    // ['province']) lam CO cho biet "day la 1 lan submit that tu panel" - khi do PHAI doc TOAN BO
    // cac truong con lai TU $_GET (rong neu nguoi dung khong chon, KHONG fallback ve $filter cua
    // trang goc) thay vi tron 2 nguon. Bug thuc te da gap khi con tron: dang o trang "Quận Hoàn
    // Kiếm" (filter.district="Hoàn Kiếm"), doi Tinh sang "TP.HCM" nhung KHONG dong thoi doi Quan
    // (vi UI khong bat nguoi dung phai chon lai) -> $_GET chi co province moi, district van fallback
    // ve $filter['district']="Hoàn Kiếm" (mot quan cua Ha Noi) -> loc theo "TP.HCM" + "Hoàn Kiếm"
    // (to hop khong ton tai) -> total_count=0, nhin nhu trang loi.
    $get_project_type = isset($_GET['project_type']) && $_GET['project_type'] !== '' ? (int) $_GET['project_type'] : null;

    // Chon 1 goi y tu autocomplete "Địa điểm" (xem inc/location-search.php + assets/js/main.js) -
    // kiem tra TRUOC, tach rieng khoi toan bo logic $office_args ben duoi. Da xac nhan qua goi API
    // that: "project_id"/"street_code" (chon TU AUTOCOMPLETE) KHONG chi bi bounds "giao" nham (xem
    // ghi chu cu) MA CON bi RatingLevel/gia/District cua CHINH TRANG dang xem lam RONG ket qua neu
    // du an/duong duoc chon khong khop dung dieu kien do (vd dang o trang "Hạng A" nhung du an
    // duoc chon lai la Hạng C - bug thuc te da gap: chon dung du an van bao "Không tìm thấy"). Chi
    // "province" + "project_type" (Van phong truyen thong/tron goi) la giu lai duoc - 2 truong nay
    // it kha nang xung dot vi API tu bo qua Province khi co project_id, con project_type la loai
    // hinh RONG (truyen thong/tron goi), khong phai 1 "dac diem" hep nhu Hang/Gia/Quan.
    $pplus_suggest_project_id = !empty($_GET['project_id']) ? (int) $_GET['project_id'] : 0;
    $pplus_suggest_street_name = sanitize_text_field($_GET['street_name'] ?? '');
    $pplus_suggest_default_type = $is_service_type ? ($filter['projectType'] ?? 1) : ($filter['projectType'] ?? 2);
    $pplus_suggest_project_type = $get_project_type ?? $pplus_suggest_default_type;
    // Danh dau rieng (KHONG dung chung voi $office_args['street_code']): trang duong binh thuong
    // (loai=6, $is_street) CUNG dat street_code nhung KHONG phai lua chon tu autocomplete, van
    // can giu bounds nhu truoc (khong doi hanh vi da co san).
    $pplus_suggest_active = false;

    if ($pplus_suggest_project_id > 0) {
        $office_args = ['project_id' => $pplus_suggest_project_id, 'page' => $paged];
        $pplus_suggest_active = true;
    } elseif ($pplus_suggest_street_name !== '' && ($pplus_suggest_street_code = pplus_resolve_street_code(
        $is_filter_submit ? $filter_province : ($filter['province'] ?? ''),
        $pplus_suggest_street_name
    )) !== null) {
        // "office_type" (4 loai con cua Van phong tron goi, vd "VP riêng") KHONG duoc coi la 1
        // "dieu kien loc cu" can bo cung voi khu vuc/gia/hang - no la 1 PHAN CUA "loai van phong"
        // dang chon CUNG LUC voi duong. Thieu dong nay se lam bug thuc te: chon Duong + Van phong
        // tron goi + "VP riêng" van ra ca 4 loai hinh (chi con project_type=1, khop ca 4 loai con).
        //
        // "price_from"/"price_to"/"rating_level" GIU LAI (KHAC han nhanh project_id o tren) - bug
        // thuc te nguoi dung bao cao (12/8/2026): go "Duy Tân" vao o Dia diem, dat gia 10$-15$, bam
        // Tim kiem van ra ca toa gia $23 (Epic Tower). Ly do: chon 1 DUONG qua goi y la 1 TAP HOP
        // van phong (giong het duyet thang toi trang /duy-tan/, $is_street ben duoi), khac han chon
        // 1 DU AN CU THE qua project_id (1 toa nha DUY NHAT, luc do gia/hang cua trang khac co the
        // "giao rong" nham thanh 0 ket qua nhu comment o tren da giai thich) - nen gia/hang VAN can
        // loc tiep tren tap hop van phong cua duong do, khong duoc bo nhu project_id.
        $office_args = [
            'street_code'  => $pplus_suggest_street_code,
            'project_type' => $pplus_suggest_project_type,
            'office_type'  => $is_filter_submit ? sanitize_text_field($_GET['office_type'] ?? '') : ($filter['officeType'] ?? ''),
            'price_from'   => $is_filter_submit
                ? (isset($_GET['price_from']) && $_GET['price_from'] !== '' ? (float) $_GET['price_from'] : null)
                : ($filter['price_from'] ?? null),
            'price_to'     => $is_filter_submit
                ? (isset($_GET['price_to']) && $_GET['price_to'] !== '' ? (float) $_GET['price_to'] : null)
                : ($filter['price_to'] ?? null),
            'rating_level' => $is_filter_submit ? sanitize_text_field($_GET['rating_level'] ?? '') : ($filter['ratingLevel'] ?? ''),
            'page'         => $paged,
        ];
        $pplus_suggest_active = true;
    } else {
        $office_args = [
            'province'     => $is_filter_submit ? $filter_province : ($filter['province'] ?? ''),
            'price_from'   => $is_filter_submit
                ? (isset($_GET['price_from']) && $_GET['price_from'] !== '' ? (float) $_GET['price_from'] : null)
                : ($filter['price_from'] ?? null),
            'price_to'     => $is_filter_submit
                ? (isset($_GET['price_to']) && $_GET['price_to'] !== '' ? (float) $_GET['price_to'] : null)
                : ($filter['price_to'] ?? null),
            'rating_level' => $is_filter_submit ? sanitize_text_field($_GET['rating_level'] ?? '') : ($filter['ratingLevel'] ?? ''),
            // "office_type" (4 loai con cua Van phong tron goi, xem office-filter-panel.php) - dat o
            // day (KHONG rieng trong nhanh $is_service_type ben duoi) vi tu khi co panel Bo loc, nguoi
            // dung co the doi sang Van phong tron goi + chon 1 loai con NGAY CA khi dang o 1 trang
            // quan/huyen (loai=1,8) - khong con gioi han rieng cho trang dich vu (loai=3) nua.
            'office_type'  => $is_filter_submit ? sanitize_text_field($_GET['office_type'] ?? '') : ($filter['officeType'] ?? ''),
            'page'         => $paged,
        ];
        if ($is_district) {
            $office_args['district']     = $is_filter_submit ? sanitize_text_field($_GET['district'] ?? '') : ($filter['district'] ?? '');
            $office_args['ward']         = $is_filter_submit ? sanitize_text_field($_GET['ward'] ?? '') : ($filter['wardName'] ?? '');
            $office_args['project_type'] = $get_project_type ?? ($filter['projectType'] ?? 2);
            // Quan HCM: .NET tra filter.province/district RONG, CHI project_ids loc dung toa nha cua
            // quan (vd quan-2 = 85 id). Angular gui nguyen filter nen dung; WP truoc bo qua -> trang
            // quan HCM ro ca tinh (bug #3, 26/8). LUON lay tu $filter (danh tinh cua trang, khong phai
            // dieu kien user chinh) - quan HN co project_ids RONG (dung district text) nen chi anh huong
            // HCM. Xem pplus_get_office_list(): project_ids can kem bounds (fallback da xu ly).
            $office_args['project_ids'] = $filter['project_ids'] ?? '';
        } elseif ($is_service_type) {
            // Trang dich vu (loai=3): loc theo ProjectType=1 + OfficeType thay vi District.
            $office_args['project_type'] = $get_project_type ?? ($filter['projectType'] ?? 1);
        } elseif ($is_street) {
            // Trang duong (loai=6): loc theo StreetCode (khong phai ten duong dang text - da xac nhan
            // qua doi chieu truc tiep API that: gui "Street"=ten duong tra ve total_count sai/qua nhieu,
            // phai dung "StreetCode" moi khop dung y het production, vd Duy Tan = 36 ket qua).
            $office_args['project_type'] = $get_project_type ?? ($filter['projectType'] ?? 2);
            $office_args['street_code']  = $filter['streetCode'] ?? '';
        } elseif ($get_project_type !== null) {
            // Trang hang (loai=4): khong co project_type rieng trong $filter, chi ap dung khi nguoi
            // dung tu doi qua panel Bo loc (mac dinh 2 da xu ly san trong pplus_get_office_list()).
            $office_args['project_type'] = $get_project_type;
        }
        // Trang hang (loai=4): khong can them dieu kien rieng - da co san 'rating_level' o tren cung
        // moi trang, chi can project_type=2 mac dinh (khong roi vao 2 nhanh tren).
    }

    // Thu KHONG bounds TRUOC cho MOI truong hop khu vuc (quan/duong/hang/dich vu + autocomplete),
    // chi fallback sang bounds neu ket qua RONG. Ly do (xac nhan qua REQUEST ANGULAR THAT + goi API
    // 18/8/2026): Angular gui bounds = VIEWPORT Google Map, RONG hon nhieu cai zone_stringify, gan nhu
    // KHONG siet ket qua. Vd Cau Giay: Angular viewport gui len -> total_count 157; District-text
    // KHONG bounds = 158 (khop); con zone_stringify rectangle HEP chi ra 113 (cat VP o ria quan - dung
    // canh bao "ria quan" cua comment cu). Ket luan: District/StreetCode text DA du khop production,
    // bounds zone chi lam THIEU. Fallback (thu text truoc, chi them bounds neu rong) van an toan cho
    // truong hop hiem text ra 0. Xac nhan them: duong Duy Tan text=36 dung; Hoan Kiem 77 du co/khong
    // bounds. (Truoc day comment o day tin "Hoan Kiem thieu bounds chi 22/77" - do lai bang API that
    // 18/8 KHONG con tai hien, District text .NET gio da dang tin, nen bo bounds mac dinh.)
    //
    // NGOAI LE - radius search ($is_roam_bounds): bounds la ban kinh nguoi dung CHU DONG dat, PHAI ap
    // dung TRUC TIEP (khong fallback bo qua, neu khong se tra ca khu vuc thay vi trong ban kinh).
    if ($is_roam_bounds) {
        $office_result = pplus_get_office_list(array_merge($office_args, $map_bounds ?? []));
    } else {
        $office_result = pplus_get_office_list_with_bounds_fallback($office_args, $map_bounds);
    }
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
    }, $office_result['listOffice'] ?? []);
    $page_count  = 18;
    $total_count = (int) ($office_result['total_count'] ?? 0);
    $total_pages = max(1, (int) ceil($total_count / $page_count));
}

// Muc luc ("Mục Lục") - port generateTOC() tu table-of-contents.component.ts, xem
// inc/render-helpers.php pplus_generate_toc(). $toc['html'] da duoc gan id vao tung heading.
$toc = pplus_generate_toc($data['content'] ?? '');

// Bo loc gia nhanh (5 khoang gia $/m2 co dinh) hien ngay duoi H1, thay cho hang "pill" sibling
// quan/phuong/hang/dich-vu cu (da xoa) - theo yeu cau nguoi dung, AP DUNG CHUNG cho toan bo 4 loai
// trang (Quan/Phuong/Hang/Duong, ca Ha Noi + TPHCM, khong phan biet loai). Tinh san 1 mang dieu
// kien loc HIEN TAI (KHONG gom gia) de dung LAI cho ca panel "Bo loc" (gan lam 'current') LAN xay
// href cho tung nut gia - bam 1 nut gia phai GIU NGUYEN moi dieu kien khac dang loc, CHI doi price_
// from/price_to. Luon co du field province/district/... (ke ca khi rong) trong query string de bam
// vao la thanh 1 lan "submit that" (isset($_GET['province'])===true) - xem ghi chu chi tiet
// $is_filter_submit/$office_args o tren: thieu "province" se khien PHP tuong day la lan tai trang
// dau (chua submit) va boi thuong bang $filter mac dinh cua trang, phot lo ca price_from/price_to
// vua bam.
// "$office_args['province'] ?? ($filter['province'] ?? '')" (khong chi "?? ''") - phong truong hop
// dang o nhanh $pplus_suggest_active (chon 1 du an/duong cu the tu autocomplete "Địa điểm" trong
// panel Bo loc), luc do $office_args KHONG co key 'province' (xem nhanh project_id/street_code o
// tren) - neu de rong het se mat het ngu canh tinh/thanh khi bam nut gia (rong ca gia lan tinh),
// fallback ve dung tinh/thanh cua trang ($filter['province']) de it nhat van loc dung pham vi
// tinh/thanh + gia (mat lua chon du an/duong cu the, chap nhan duoc vi day la 2 muc dich loc khac
// nhau: 1 du an cu the vs. 1 khoang gia).
$office_filter_current = $show_office_search ? [
    'province'     => $office_args['province'] ?? ($filter['province'] ?? ''),
    'district'     => $office_args['district'] ?? '',
    'ward'         => $office_args['ward'] ?? '',
    'project_type' => $office_args['project_type'] ?? 2,
    'office_type'  => $office_args['office_type'] ?? '',
    'rating_level' => $office_args['rating_level'] ?? '',
] : [];
$pplus_price_quick_options = [
    ['from' => 10, 'to' => 20],
    ['from' => 20, 'to' => 30],
    ['from' => 30, 'to' => 40],
    ['from' => 40, 'to' => 50],
    ['from' => 50, 'to' => 80],
];
// Gia THAT SU dang loc (khac $is_filter_submit-gated 'current' truyen cho panel Bo loc o duoi) - o
// day can biet dung gia dang AP DUNG de to active dung mau, ke ca khi la gia mac dinh rieng cua
// trang (chua qua 1 lan submit nao).
$pplus_price_active_from = $show_office_search ? ($office_args['price_from'] ?? null) : null;
$pplus_price_active_to   = $show_office_search ? ($office_args['price_to'] ?? null) : null;
// Nut "Xóa lọc giá" o cuoi hang - CHI hien khi NGUOI DUNG DA THAT SU submit 1 khoang gia
// ($is_filter_submit, KHONG phai $pplus_price_active_from/to o tren - 2 bien do phan anh gia DANG
// AP DUNG ke ca khi la gia mac dinh RIENG CUA TRANG, vd "0-50 rieng cho trang Hoan Kiem" tu $filter,
// hien nut Xoa ngay tu lan tai trang dau se gay hieu lam "minh dang loc" trong khi chua bam gi ca -
// cung 1 nguyen tac voi gia PRE-FILL cua panel "Bo loc" o duoi, xem ghi chu $is_filter_submit tai
// do). Ap dung ke ca gia KHONG khop dung 1 trong 5 nut (vd nguoi dung tu go tay 1 khoang gia khac
// qua panel "Bo loc") - theo yeu cau nguoi dung.
$pplus_price_clear_active = $is_filter_submit
    && (($office_args['price_from'] ?? null) !== null || ($office_args['price_to'] ?? null) !== null);

get_header();
?>
<?php if ($show_office_search) : ?>
    <?php get_template_part('parts/office-filter-panel', null, [
        // Gia: CHI hien lai khi da co 1 lan submit that qua panel ($is_filter_submit) - khong dung
        // gia mac dinh rieng cua trang ($filter['price_from']/['price_to'], vd 0-50 rieng cho trang
        // Hoan Kiem) lam panel tuong nham la "gia nguoi dung dang chon" (gay kho hieu - phan hoi
        // thuc te tu nguoi dung). office-filter-panel.php tu dat gia mac dinh dung theo project_type
        // (0-80 cho Truyen thong, rong cho Tron goi) khi KHONG truyen 2 field nay, giong het hanh vi
        // Angular goc (initPriceBase()).
        'current' => array_merge($office_filter_current, [
            'price_from' => $is_filter_submit ? ($office_args['price_from'] ?? '') : '',
            'price_to'   => $is_filter_submit ? ($office_args['price_to'] ?? '') : '',
        ]),
    ]); ?>

    <div class="map-container">
        <div class="map-office">
            <div class="box-content row">
                <div class="col-md-12 box-list-header pull-left">
                    <div class="url-content">
                        <div class="url-content-list">
                            <a href="<?php echo esc_url(home_url('/')); ?>"><?php echo esc_html(pplus_t('Trang chủ', 'Home')); ?></a>
                            &nbsp;
                            <i><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/arrow-29.png'); ?>" alt="Icon" loading="lazy" /></i>
                            &nbsp;
                            <?php // "filter.province" LUON tra ve TEN TIENG VIET du dang goi ngon ngu
                            // nao (xac nhan qua API that: filter.province="Hà Nội" ca khi lang=en) -
                            // "menuItem.subTitle" moi thuc su doi theo ngon ngu ("Hà Nội" vi/"Office
                            // For Lease in Hanoi" en), dung dung field nay cho breadcrumb. ?>
                            <a href="<?php echo esc_url(home_url($lang_prefix . '/' . pplus_office_list_base_slug() . '/' . $province_alias . '/')); ?>"><?php echo esc_html($model['menuItem']['subTitle'] ?? $filter['province'] ?? ''); ?></a>
                            &nbsp;
                            <i><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/arrow-29.png'); ?>" alt="Icon" loading="lazy" /></i>
                            &nbsp;
                            <?php echo esc_html($h1); ?>
                        </div>
                    </div>
                    <div class="text-find" id="text-find">
                        <div class="title-search">
                            <?php echo esc_html($h1); ?> | <?php echo esc_html(pplus_t('Cập nhật', 'Update')); ?> <?php echo esc_html(date_i18n('m/Y')); ?>
                        </div>
                        <div class="action-search">
                            <?php // Nut "Tim kiem" doi giao dien (20/8/2026, theo yeu cau nguoi dung: khach hang
                            // phan hoi nut cu (icon nho + chu) khong ro la cho tim kiem) - GIU NGUYEN hanh vi
                            // click cu het (data-pplus-toggle="search", van mo panel "Bo loc" y het truoc), CHI
                            // doi giao dien cho GIONG 1 thanh tim kiem that (placeholder xam + nut tron kinh lup
                            // xanh) de de nhan biet hon - VAN la <button>, KHONG phai <input> that (nguoi dung
                            // xac nhan rieng: neu de input that go duoc chu nhung bam lai chi mo popup cu (khong
                            // tim theo chu vua go) se gay hieu lam moi "go ma khong tim duoc gi" - <button> voi
                            // con tro "pointer" (khong phai con tro go chu) tranh dung ky vong sai nay). Dat
                            // TRUOC (khong phai sau) 2 nut "Tim theo ban kinh"/"So sanh" - noi bat nhat trong
                            // hang, dung thu tu trong mockup nguoi dung ve. ?>
                            <?php // Doi sang DUNG LAI y het nut "Tìm kiếm văn phòng" (parts/url-box-content.php,
                            // dung tren A2/blog/lien he/ve chung toi/yeu thich) - theo yeu cau nguoi dung
                            // (21/8/2026): dong bo 1 kieu nut tim kiem DUY NHAT tren toan site (nen xanh #3e5fb4,
                            // chu trang, icon trang), khong con giao dien rieng (o placeholder xam) cho trang
                            // nay nua. GIU NGUYEN id/data-pplus-toggle (hanh vi mo panel "Bo loc" khong doi). ?>
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
                            // sach DANG CO (khong them gi ca) - xem assets/js/compare.js ".js-open-compare-list". ?>
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
                <?php // ".url-content-list-children" PHAI nam BEN TRONG ".box-list.init" (giong dung
                // search-base.component.html dong 559-564, KHONG phai 1 hang rieng ".col-md-12" truoc
                // ca 2 cot) - truoc day dat ngoai lam no chiem full-width, day CA ".box-map" (position:
                // sticky, phu thuoc luong tai lieu de tinh diem bat dau) xuong thap hon thiet ke that
                // (bug da gap: "bản đồ chưa cao lên như trang gốc"). Dat trong ".box-list" thi CHI cot
                // noi dung (col-md-7) bi day xuong, cot ban do (col-md-5) van giu nguyen vi tri cao. ?>
                <div class="box-list init col-md-7">
                    <?php if ($is_service_type) : ?>
                        <?php // Trang DICH VU: GIU NGUYEN hang goi y "cac loai dich vu khac" (theo yeu
                        // cau nguoi dung) - xem ghi chu day du $dich_vu_siblings o dau file. ?>
                        <?php if (!empty($dich_vu_siblings)) : ?>
                            <div class="url-content-list-children">
                                <?php foreach ($dich_vu_siblings as $dv_item) : ?>
                                    <div class="district-name">
                                        <a href="<?php echo esc_url(home_url($lang_prefix . '/' . pplus_office_list_base_slug() . '/' . $province_alias . '/' . pplus_header_alias($dv_item, $pplus_vi_alias_map_dv) . '/')); ?>">
                                            <?php echo esc_html(pplus_header_label($dv_item, $pplus_vi_alias_map_dv, 'subTitle')); ?>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php else : ?>
                        <?php // Bo loc gia nhanh (5 khoang gia $/m2 co dinh) - thay hang "pill" sibling
                        // quan/phuong/hang cu (theo yeu cau nguoi dung, CHI ap dung Quan/Phuong/Hang/
                        // Duong - xem ghi chu $is_service_type o tren). Dung LAI class ".url-content-
                        // list-children"/".district-name" (border-radius pill co san) + them class rieng
                        // "pplus-price-pill" de CSS to active nen xanh/chu trang (khac active mac dinh
                        // cua ".district-name" - nen xanh NHAT/chu xanh, xem search-base-component.css)
                        // ma KHONG anh huong cac noi khac dang dung chung class nay. ?>
                        <div class="url-content-list-children">
                            <?php
                            // "home_url($lang_prefix . pplus_current_path())" lam URL GOC (KHONG dung
                            // add_query_arg() khong truyen URL - mac dinh se doc $_SERVER['REQUEST_URI'],
                            // nhung inc/language-route.php da CO CHU DICH strip het tien to "/en" khoi bien
                            // nay tu truoc do (de khop rewrite rule dang ky khong /en) - dung thang se ra
                            // link ban EN nhung THIEU "/en" (bug thuc te da gap luc rà soát: bam nut gia
                            // tren trang EN nhay ve URL tieng Viet). pplus_current_path() (inc/api-client.php)
                            // da xu ly dung dieu nay cho link doi ngon ngu, dung LAI o day. ?>
                            <?php $pplus_price_pill_base = home_url($lang_prefix . pplus_current_path()); ?>
                            <?php foreach ($pplus_price_quick_options as $price_opt) :
                                $is_price_active = ($pplus_price_active_from !== null && $pplus_price_active_to !== null
                                    && (float) $pplus_price_active_from === (float) $price_opt['from']
                                    && (float) $pplus_price_active_to === (float) $price_opt['to']);
                                $price_pill_url = add_query_arg(array_merge($office_filter_current, [
                                    'price_from' => $price_opt['from'],
                                    'price_to'   => $price_opt['to'],
                                    'paged'      => 1,
                                ]), $pplus_price_pill_base);
                            ?>
                                <div class="district-name pplus-price-pill<?php echo $is_price_active ? ' active' : ''; ?>">
                                    <a href="<?php echo esc_url($price_pill_url); ?>">$<?php echo (int) $price_opt['from']; ?> - $<?php echo (int) $price_opt['to']; ?></a>
                                </div>
                            <?php endforeach; ?>
                            <?php // Nut "Xóa lọc giá" cuoi hang - CHI hien khi dang co 1 khoang gia ap
                            // dung ($pplus_price_clear_active, tinh o tren cung 1 cho voi $office_filter_
                            // current). Icon "fa-times" (FontAwesome, da nap san toan site - xem cac nut
                            // ".from-search-roam"/".action-close-map-label" khac dang dung chung font nay)
                            // + chu "Xóa lọc giá"/"Clear price filter" - ngan gon, du y "huy bo loc nhanh". ?>
                            <?php if ($pplus_price_clear_active) :
                                $price_clear_url = add_query_arg(array_merge($office_filter_current, [
                                    'price_from' => '',
                                    'price_to'   => '',
                                    'paged'      => 1,
                                ]), $pplus_price_pill_base);
                            ?>
                                <div class="district-name pplus-price-pill-clear">
                                    <a href="<?php echo esc_url($price_clear_url); ?>"><i class="fa fa-times" aria-hidden="true"></i> <?php echo esc_html(pplus_t('Xóa lọc giá', 'Clear price filter')); ?></a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <div class="box-list-body">
                        <?php // Panel "Tim theo ban kinh" (an mac dinh, JS hien khi bam nut tren) - port
                        // ".from-search-roam" (search-base.component.html dong 624-712), xem ghi chu day
                        // du trong page-office-list.php (cung 1 markup). ?>
                        <?php // "data-project-type"/"data-office-type"/"data-rating-level" - de radius-search.js
                        // mang theo dung dieu kien LOAI van phong/hang khi dieu huong sang URL tong quat
                        // "/cho-thue-van-phong/" (xem ghi chu day du trong page-office-list.php). Doc tu
                        // $office_args (da tinh san o tren, cung 1 gia tri se gui cho API) - fallback an
                        // toan neu $office_args dang o nhanh project_id/street_code (khong co 2 key nay). ?>
                        <div class="from-search-roam" id="pplus-roam-panel" style="display:none;" data-project-type="<?php echo esc_attr($office_args['project_type'] ?? 2); ?>" data-office-type="<?php echo esc_attr($office_args['office_type'] ?? ''); ?>" data-rating-level="<?php echo esc_attr($office_args['rating_level'] ?? ''); ?>">
                            <div class="row col-findter">
                                <div class="col-md-5 pplus-show-group-search">
                                    <input type="text" class="input-search-google-map form-control" id="pplus-roam-address" placeholder="<?php echo esc_attr(pplus_t('Tìm địa chỉ hoặc chỉ vào 1 vị trí trên bản đồ', 'Search an address or click a point on the map')); ?>" autocomplete="off" />
                                    <div class="pplus-show-group">
                                        <div class="text-data">
                                            <?php // Ban .webp (13/8/2026) qua image-set() - fallback url() truoc cho trinh duyet cu, xem parts/header.php. ?>
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
                                        <input type="text" class="input-roam-distance form-control" id="pplus-roam-price-from" placeholder="<?php echo esc_attr(pplus_t('Giá từ ...', 'Price from ...')); ?>" value="<?php echo esc_attr($_GET['price_from'] ?? ''); ?>" inputmode="numeric" />
                                        <span class="input-group-text">$</span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="input-group">
                                        <input type="text" class="input-roam-distance form-control" id="pplus-roam-price-to" placeholder="<?php echo esc_attr(pplus_t('Giá đến ...', 'Price to ...')); ?>" value="<?php echo esc_attr($_GET['price_to'] ?? ''); ?>" inputmode="numeric" />
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
                            <?php if ($office_result === null) : ?>
                                <?php pplus_render_api_error(); ?>
                            <?php elseif (empty($office_result['listOffice'])) : ?>
                                <div class="pplus-notice"><?php echo esc_html(pplus_t('Không tìm thấy văn phòng phù hợp.', 'No matching offices found.')); ?></div>
                            <?php else : ?>
                                <div class="list-product-office">
                                    <?php foreach ($office_result['listOffice'] as $i => $item) : ?>
                                        <?php get_template_part('parts/office-card', null, ['item' => $item, 'index' => $i]); ?>
                                    <?php endforeach; ?>
                                </div>

                                <?php if ($total_pages > 1) : ?>
                                    <nav class="pplus-pagination" style="margin: 20px 0; text-align:center;">
                                        <?php
                                        // Bug thuc te nguoi dung bao cao (12/8/2026): bam sang trang 2/3 lam
                                        // MAT het dieu kien loc (gia/hang/quan/loai van phong) dang ap dung,
                                        // hien ket qua khac han trang 1. Nguyen nhan: base URL truoc day CHI
                                        // co "home_url()+path+paged", KHONG mang theo $office_filter_current/
                                        // gia (khac han $pplus_price_pill_base cua cac nut gia $ o tren, DA
                                        // lam dung tu truoc) - link trang 2 thieu "province" trong query string
                                        // khien $is_filter_submit (dong ~53, dieu kien la isset($_GET['province']))
                                        // tro thanh false, PHP tuong day la lan tai trang dau (chua submit gi)
                                        // nen quay ve loc mac dinh cua rieng trang, bo qua het lua chon nguoi
                                        // dung vua ap dung. Sua bang cach dung LAI dung 1 pattern voi cac nut
                                        // gia $ ben tren ($office_filter_current + $pplus_price_pill_base).
                                        //
                                        // Bug thuc te khac (15/8/2026): sua tren CHUA du cho truong hop chon 1
                                        // "Tên đường"/"Tên dự án" tu autocomplete "Địa điểm" (nhanh $pplus_suggest_
                                        // active, dong ~166) roi phan trang - $office_filter_current KHONG mang
                                        // "street_name"/"project_id" (CO CHU DICH, xem ghi chu dong ~286: nut gia
                                        // $ nen BO du an/duong cu the vi la 2 muc dich loc khac nhau). Nhung phan
                                        // trang (bam "trang 2") KHONG phai 1 lan doi dieu kien loc nhu bam nut gia
                                        // - phai GIU NGUYEN street_name/project_id, neu khong trang 2 roi vao
                                        // nhanh "else" (dong ~208, loc theo tinh/quan rong hon) trong khi total_
                                        // pages van tinh tu ket qua HEP cua duong/du an o trang 1, thuong it hon
                                        // 18*2 => trang 2 rong "khong tim thay san pham" (bug thuc te nguoi dung
                                        // bao cao: loc theo quan/duong, sang trang 2 khong thay san pham).
                                        ?>
                                        <?php echo paginate_links([
                                            'base'      => esc_url_raw(add_query_arg(array_merge($office_filter_current, [
                                                'price_from'  => $pplus_price_active_from ?? '',
                                                'price_to'    => $pplus_price_active_to ?? '',
                                                'street_name' => $pplus_suggest_street_name,
                                                'project_id'  => $pplus_suggest_project_id > 0 ? $pplus_suggest_project_id : '',
                                                'paged'       => '%#%',
                                            ]), $pplus_price_pill_base)),
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

                <?php // Mobile: mac dinh o che do "Noi dung", ban do an qua class "invision-map" -
                // xem ghi chu chi tiet trong page-office-list.php (cung co che, dung y het o day).
                // Trang nay CO bai viet SEO (#content-ckedit o duoi) nen co them nut "Hiển thị
                // thông tin" (.btnShowThongTin) ma A1 khong co. ?>
                <div class="col-md-5 box-map invision-map">
                    <div class="action-close-map-label" data-pplus-toggle="map-view" title="<?php echo esc_attr(pplus_t('Đóng bản đồ', 'Close map')); ?>">
                        <i class="fa fa-reply" aria-hidden="true"></i> <?php echo esc_html(pplus_t('Quay lại', 'Back')); ?>
                    </div>
                    <?php // Nut "ban tay" bat/tat che do bam chon tam diem tren ban do - xem ghi chu day
                    // du trong page-office-list.php (cung 1 markup). ?>
                    <button type="button" class="btn-action-roamingclick" id="pplus-roam-hand" style="display:none;" title="<?php echo esc_attr(pplus_t('Bấm để chọn tâm điểm trên bản đồ', 'Click to pick a center point on the map')); ?>">
                        <i class="hand-container">
                            <svg class="hand-icon" id="pplus-roam-hand-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
                                <path d="M358.182 179.361c-19.493-24.768-52.679-31.945-79.872-19.098-15.127-15.687-36.182-22.487-56.595-19.629V67c0-36.944-29.736-67-66.286-67S89.143 30.056 89.143 67v161.129c-19.909-7.41-43.272-5.094-62.083 8.872-29.355 21.795-35.793 63.333-14.55 93.152l109.699 154.001C134.632 501.59 154.741 512 176 512h178.286c30.802 0 57.574-21.5 64.557-51.797l27.429-118.999A67.873 67.873 0 0 0 448 326v-84c0-46.844-46.625-79.273-89.818-62.639zM80.985 279.697l27.126 38.079c8.995 12.626 29.031 6.287 29.031-9.283V67c0-25.12 36.571-25.16 36.571 0v175c0 8.836 7.163 16 16 16h6.857c8.837 0 16-7.164 16-16v-35c0-25.12 36.571-25.16 36.571 0v35c0 8.836 7.163 16 16 16H272c8.837 0 16-7.164 16-16v-21c0-25.12 36.571-25.16 36.571 0v21c0 8.836 7.163 16 16 16h6.857c8.837 0 16-7.164 16-16 0-25.121 36.571-25.16 36.571 0v84c0 1.488-.169 2.977-.502 4.423l-27.43 119.001c-1.978 8.582-9.29 14.576-17.782 14.576H176c-5.769 0-11.263-2.878-14.697-7.697l-109.712-154c-14.406-20.223 14.994-42.818 29.394-22.606z"/>
                            </svg>
                            <span class="spark"></span>
                        </i>
                    </button>
                    <div id="map" data-markers="<?php echo esc_attr(wp_json_encode($map_markers)); ?>"
                        <?php // Trang DUONG (loai=6): moi VP tren cung 1 con duong -> fitBounds zoom qua sat,
                        // dat max-zoom=14 de lui ra thay ten duong xung quanh (phan hoi nguoi dung). Cac
                        // trang khac khong dat -> pplus-maps.js mac dinh 16 nhu cu. ?>
                        <?php if ($is_street) : ?>data-max-zoom="14"<?php endif; ?>
                        <?php if ($map_fallback_center) : ?>
                            data-fallback-lat="<?php echo esc_attr($map_fallback_center['lat']); ?>"
                            data-fallback-lng="<?php echo esc_attr($map_fallback_center['lng']); ?>"
                        <?php endif; ?>
                    ></div>
                </div>
                <?php // Dai the san pham noi tren ban do (mobile, che do xem ban do) - xem ghi chu
                // chi tiet trong page-office-list.php (cung co che, dung y het o day). ?>
                <?php if (!empty($office_result['listOffice'])) : ?>
                    <div class="box-list-body-mobile">
                        <div class="list-product-office">
                            <?php foreach ($office_result['listOffice'] as $item) : ?>
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
                    <div class="btnShowThongTin" data-pplus-toggle="thong-tin" title="<?php echo esc_attr(pplus_t('Hiển thị thông tin', 'Show information')); ?>">
                        <i class="fa fa-file-o" aria-hidden="true"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
<div class="datalist" id="web-content">
    <div class="container mb-30">
        <div class="row">
            <div class="col-md-8">
                <div class="data-content">
                    <h1 class="data-title-5"><?php echo esc_html($h1); ?></h1>
                    <?php pplus_render_toc_nested($toc['items']); ?>
                    <div class="content-1 table-mg blog-p-no-margin" id="content-ckedit">
                        <?php echo pplus_kses_content($toc['html']); ?>
                    </div>
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
    <?php // Nut "Quay lai" - CHI hien khi "#web-content.active" (dong y het Angular goc: dong 204-
    // 206 reference-index.component.html). CSS da co san tu truoc (display:none mac dinh,
    // display:block khi .active, xem reference-index-component.css) nhung thieu markup nay. ?>
    <button type="button" class="btn btn-primary btnBackOffThongTin" data-pplus-toggle="thong-tin">
        <i class="fa fa-reply" aria-hidden="true"></i> <?php echo esc_html(pplus_t('Quay lại', 'Back')); ?>
    </button>
</div>
<?php get_footer(); ?>
