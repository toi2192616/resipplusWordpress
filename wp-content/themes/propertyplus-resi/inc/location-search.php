<?php
/**
 * "Địa điểm" autocomplete cho o input search_key (parts/office-filter-panel.php +
 * front-page.php) - goi y "Tên dự án"/"Tên đường" khi go text, theo yeu cau nguoi dung.
 *
 * KHONG dung Office/_GetOffices (khong co field free-text ten du an - xem TODO cu trong
 * office-filter-panel.php). Dung Office/_GetFilterGroupList (endpoint THAT Angular goc dung cho
 * chinh popup "Địa điểm" cua no, xem search-base.component.ts findNameGroupToBack()) - xac nhan
 * qua goi truc tiep API that (KHONG chi co trong Angular repo, response THUC TE co ca field
 * "streetList" ma Angular chua bao gio dung toi, dung duoc luon cho nhom "Tên đường").
 *
 * Bam SAT dung ban Angular goc (yeu cau nguoi dung 21/8/2026: "gốc nguyên bản từ ban đầu trong
 * code gốc ấy, như trên bản chính propertyplus.vn bây giờ ấy" - xem findNameGroupToBack():
 * "textSearch: this.searchBase.filter" - GUI NGUYEN VAN ca cau go, CHI 1 LAN GOI DUY NHAT, KHONG
 * tach tu/goi lai/goi bo sung). Ban WP truoc day (18-21/8/2026) tung them tach tu + goi "cuu" full-
 * text + tim tren danh sach duong day du rieng de sua 2 bug thuc te (goi y bo sot khi go co dau
 * "-", va bo sot duong trung ten o tinh khac vd "Hoàng Hoa Thám") - NHUNG moi lan them 1 lan goi la
 * cong don them ~1000ms (.NET LUON ~1000ms/lan goi, xem pplus_location_search_fetch()), khien
 * nguoi dung thay ro ret cham han ban Angular goc. Theo yeu cau nguoi dung, CHAP NHAN quay lai
 * dung 2 bug tren (giong y het hanh vi cua propertyplus.vn hien tai) de doi lay dung 1 lan goi/
 * luot go, dung toc do voi ban goc.
 *
 * Da xac nhan qua nhieu lan goi API that (curl truc tiep):
 * - ProvinceId KHONG loc gi ca (test FilterText="Nguyễn Huệ" voi ProvinceId=1 va 2 tra ve HET
 *   y het nhau, ca 2 deu co ket qua TPHCM) - bo qua param nay, khong can tra ProvinceId dung.
 * - Backend tu gioi han ~6 ket qua/nhom, ~1000ms/lan goi, khong phu thuoc do dai FilterText.
 * - Ten du an (projectName) KHONG doi theo ngon ngu (test /vi/ va /en/ cung 1 tu khoa tra ve
 *   CHINH XAC cung chuoi projectName) - ten rieng, khong dich. Van goi dung tien to {lang} de tuan
 *   thu dung hop dong API (phong khi cac field khac co the khac nhau).
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', function () {
    register_rest_route('pplus/v1', '/location-search', [
        'methods'             => 'GET',
        'callback'            => 'pplus_handle_location_search',
        'permission_callback' => '__return_true',
    ]);
});

/**
 * Vietnamese-safe: remove_accents() cua WordPress core da co san bang doi Vietnamese day du
 * (a/á/à/ả/ã/ạ/ă/â/... -> a, d/đ -> d,...), khong can tu viet lai bang doi rieng. Dung boi
 * pplus_resolve_street_code() (inc/api-client.php) de khop ten duong khong phan biet dau/hoa
 * thuong khi doi ten duong -> streetCode luc submit.
 */
function pplus_location_search_normalize(string $text): string {
    return mb_strtolower(remove_accents($text), 'UTF-8');
}

/**
 * Nhan "Hà Nội"/"TP. Hồ Chí Minh" tu locationLat cua 1 item (projectNameList/streetList deu co san
 * field nay, xem raw response Office/_GetFilterGroupList) - theo yeu cau nguoi dung (15/8/2026):
 * hien them "- Tỉnh/Thành" sau ten duong/du an trong goi y "Địa điểm", vi 2 duong CUNG TEN o 2 tinh
 * khac nhau (vd ca Ha Noi lan TPHCM deu co the co duong trung ten) truoc day khong phan biet duoc,
 * de nham chon nham duong o tinh khac dang xem. 2 tinh nay CACH XA nhau ve vi do (Ha Noi ~20.9-21.1,
 * TPHCM ~10.3-11.0) nen chi can 1 nguong don gian (15) la du phan biet chinh xac, khong can goi API
 * tra cuu tinh rieng (site hien chi co 2 tinh/thanh nay, xem header.php - "Hà Nội"/"Thành phố Hồ Chí
 * Minh" la 2 lua chon duy nhat). Suy ra tu response CO SAN, KHONG ton them lan goi API nao.
 *
 * "$lang" PHAI truyen vao tu tham so request (KHONG dung pplus_t()/pplus_current_lang() truc tiep
 * trong file nay) - cung 1 ly do da ghi trong inc/advise-form.php: route REST nay luon la
 * "/wp-json/...", khong bao gio co tien to "/en", nen 2 ham do se LUON ra 'vi' du trang goc dang la
 * ban EN.
 */
function pplus_location_search_province_label($location_lat, string $lang): string {
    $lat = (float) $location_lat;
    if ($lat <= 0) {
        return '';
    }
    if ($lat >= 15) {
        return $lang === 'en' ? 'Hanoi' : 'Hà Nội';
    }
    return $lang === 'en' ? 'Ho Chi Minh City' : 'TP. Hồ Chí Minh';
}

/**
 * "ha-noi"/"ho-chi-minh" (cung nguong vi do voi pplus_location_search_province_label() o tren,
 * KHONG lap lai bang chu, dung CHUNG 1 nguon) - can them (15/8/2026, khac lan sua truoc chi hien
 * "- Tỉnh/Thành" de XEM): bug thuc te tiep theo nguoi dung bao cao - chon 1 goi y duong/du an
 * THUOC TINH KHAC voi tab tinh dang active tren panel "Bo loc" (vd dang o tab "Hà Nội", chon
 * duong "Nguyễn Thị Minh Khai" TPHCM tu goi y), submit VAN giu nguyen radio province="Hà Nội" cu
 * (JS truoc gio chi dien search_key/street_name/project_id, KHONG dong bo lai tab tinh) -> ket
 * qua loc SAI hoan toan sang van phong Ha Noi. Alias nay dung de JS (main.js) tu dong CLICK lai
 * dung tab tinh tuong ung (".pplus-province-tab[data-province-alias=...]", xem parts/office-
 * filter-panel.php) truoc khi dien cac field con lai, dam bao radio "province" luon khop dung
 * tinh THAT SU cua goi y vua chon.
 */
function pplus_location_search_province_alias($location_lat): string {
    $lat = (float) $location_lat;
    if ($lat <= 0) {
        return '';
    }
    return $lat >= 15 ? 'ha-noi' : 'ho-chi-minh';
}

/**
 * Goi Office/_GetFilterGroupList voi NGUYEN VAN cau go (dung y het "textSearch: this.searchBase.
 * filter" cua Angular goc, xem docblock dau file) - CHI 1 LAN GOI DUY NHAT, tra ve mang tho
 * {projectNameList, streetList} (co the rong neu loi/khong co du lieu).
 */
function pplus_location_search_fetch(string $lang, string $filter_text): array {
    // Cache 600s - Office/_GetFilterGroupList (.NET) LUON ~1000ms/lan goi BAT KE FilterText. Du
    // an/duong KHONG doi thuong xuyen trong 1 gio (du lieu backend/admin, tan suat cap nhat thap)
    // nen cache lai, tang ty le cache-hit cho tu khoa lap lai (nguoi dung go lai, xoa go lai, hoac
    // nguoi dung KHAC go cung tu) ma khong lam du lieu cu qua muc chap nhan duoc cho 1 o goi y.
    $data = pplus_api_get("{$lang}/Office/_GetFilterGroupList", [
        'FilterText' => $filter_text,
    ], 600);

    return [
        'projectNameList' => (array) ($data['result']['data']['projectNameList'] ?? []),
        'streetList'      => (array) ($data['result']['data']['streetList'] ?? []),
    ];
}

function pplus_handle_location_search(WP_REST_Request $request) {
    $q = trim(sanitize_text_field((string) $request->get_param('q')));

    // Duoi 2 ky tu ("d", "q"...) - qua ngan de loc co ich (backend chi tra ~6 ket qua/nhom nen 1 ky
    // tu ra toan ket qua khong lien quan) MA VAN ton dung 1 lan goi ~1000ms toi backend cham - chan
    // som o day (KHONG chi chan o JS, xem "q.length < 2" trong assets/js/main.js) de an toan ke ca
    // khi co client khac goi thang route nay sau nay.
    if (mb_strlen($q, 'UTF-8') < 2) {
        return pplus_location_search_response(['projects' => [], 'streets' => []]);
    }

    // KHONG dung pplus_current_lang(): ham do doc tien to "/en" tu URL cua CHINH request nay
    // ("/wp-json/...", khong bao gio co "/en"), se luon ra 'vi' du trang goc dang la ban EN - JS
    // (functions.php, wp_add_inline_script PPLUS_LOCATION_LANG) da tu xac dinh dung ngon ngu cua
    // trang luc render va truyen qua tham so "lang" o day.
    $lang = ($request->get_param('lang') === 'en') ? 'en' : 'vi';
    $raw  = pplus_location_search_fetch($lang, $q);

    $projects = [];
    $seen_projects = [];
    foreach ($raw['projectNameList'] as $item) {
        $name = trim((string) ($item['projectName'] ?? ''));
        $id   = (int) ($item['projectId'] ?? 0);
        // LUU Y: da thu loc theo "isActive" o day nhung BO - xac nhan qua goi API that field nay
        // LUON false cho MOI du an tra ve tu chinh endpoint nay (ke ca du an CHAC CHAN co van
        // phong that, vd projectId=242 "Hoàng Linh Building" van isActive:false nhung Office/
        // _GetOffices{project_id:242} tra ve dung 1 ket qua) - khong phai tin hieu dang tin cay,
        // loc theo no se xoa SACH moi goi y du an. Truong hop hiem 1 du an ton tai nhung khong co
        // van phong nao gan (vd projectId=113) la thuc te du lieu, khong co tin hieu ro rang de
        // loai truoc - de UI "Không tìm thấy văn phòng phù hợp" (page-office-list.php) xu ly binh
        // thuong sau khi submit, khong co xu ly rieng o day.
        if ($name === '' || $id <= 0 || isset($seen_projects[$id])) {
            continue;
        }
        $seen_projects[$id] = true;
        $lat = $item['locationLat'] ?? 0;
        $projects[] = [
            'name'          => $name,
            'id'            => $id,
            'province'      => pplus_location_search_province_label($lat, $lang),
            'provinceAlias' => pplus_location_search_province_alias($lat),
        ];
    }

    $streets = [];
    $seen_streets = [];
    foreach ($raw['streetList'] as $item) {
        $name = trim((string) ($item['street'] ?? ''));
        if ($name === '') {
            continue;
        }
        $lat = $item['locationLat'] ?? 0;
        $province = pplus_location_search_province_label($lat, $lang);
        // Khoa dedup gom CA ten LAN tinh/thanh (khong chi ten) - 2 duong TRUNG TEN nhau o 2 tinh
        // khac nhau (yeu cau nguoi dung 15/8/2026: hien them "- Tỉnh/Thành" de phan biet truong hop
        // nay) PHAI duoc giu lai CA HAI, khong duoc dedup nham thanh 1 goi y duy nhat.
        $dedup_key = $name . '|' . $province;
        if (isset($seen_streets[$dedup_key])) {
            continue;
        }
        $seen_streets[$dedup_key] = true;
        $streets[] = [
            'name'          => $name,
            'province'      => $province,
            'provinceAlias' => pplus_location_search_province_alias($lat),
        ];
    }

    return pplus_location_search_response([
        // "projects": {name, id, province} - id (projectId) can cho JS dien vao hidden input
        // "project_id" khi nguoi dung bam chon (xem assets/js/main.js), de submit loc DUNG 1 du an
        // do (xem pplus_get_office_list() trong inc/api-client.php). "streets": {name, province} -
        // "name" moi la gia tri THAT submit vao hidden input "street_name" (doi ten duong ->
        // streetCode chi lam 1 LAN o thoi diem submit, pplus_resolve_street_code(), khong lam o day
        // de khong lam cham moi phim go). "province" (ca 2 nhom) CHI de JS hien thi them "- Tỉnh/
        // Thành" sau ten trong dropdown goi y (theo yeu cau nguoi dung 15/8/2026) - KHONG duoc dua
        // vao gia tri submit (se lam pplus_resolve_street_code() khop that bai, ham do can dung
        // CHINH XAC ten duong tu du lieu that, khong co hau to nao them).
        // "provinceAlias" ('ha-noi'/'ho-chi-minh') - dung boi JS de tu dong doi tab tinh/thanh
        // dung khop luc chon 1 goi y (xem ghi chu pplus_location_search_province_alias() o tren).
        'projects' => array_slice($projects, 0, 3),
        'streets'  => array_slice($streets, 0, 3),
    ]);
}

/**
 * Them "Cache-Control: public, max-age=600" (dung KHOP 600s voi transient cache phia PHP goi
 * .NET, xem pplus_location_search_fetch()) - de CHINH TRINH DUYET (va bat ky proxy/CDN trung gian
 * nao) tu cache lai response GET nay theo dung querystring ("q"+"lang"), tranh phai di het vong
 * PHP (boot WordPress + doc transient tu DB) cho 1 tu khoa VUA moi tim thanh cong o LAN TRUOC -
 * nhanh hon ca cache-hit phia server (khong ton round-trip HTTP nao). WordPress REST API mac dinh
 * KHONG gui header cache cho request GET thuong (chi cache header rieng cho request co
 * X-WP-Nonce/dang nhap) nen phai tu gan o day, khong bi ghi de boi nocache_headers() toan site (ham
 * do chi ap dung cho trang HTML qua 'template_redirect', khong dong toi REST response).
 */
function pplus_location_search_response(array $body): WP_REST_Response {
    $response = new WP_REST_Response($body, 200);
    $response->header('Cache-Control', 'public, max-age=600');
    return $response;
}
