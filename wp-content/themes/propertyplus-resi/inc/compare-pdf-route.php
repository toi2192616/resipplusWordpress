<?php
/**
 * 3 REST route phu tro cho tinh nang "So sanh" (parts/compare-modal.php + assets/js/compare.js):
 *
 * 1) POST pplus/v1/compare-pdf - "Xuat PDF" trong modal #modal-compare-table, thay the
 *    officeService.DowloadPdfCompare() ben Angular goc (product-compare.component.ts ExportPDF())
 *    -> POST {lang}/Favourite/DowloadPdfCompare, tra ve file PDF dang blob.
 *
 * 2) GET pplus/v1/office-park-fees - "Phi gui o to"/"Phi gui xe may". 2 truong nay LUON null tren
 *    Office/_GetOffices (endpoint danh sach dung de dung the van phong + data-compare, xem
 *    parts/office-card.php) - xac nhan qua goi API that. Du lieu THAT chi co trong
 *    Office/_GetDetail (endpoint trang chi tiet A2) o mang "serviceIncludedList", loc theo
 *    "serviceCode" ON DINH qua ca 2 ngon ngu: "5OTO" (Phi gui o to) / "7XEMAY" (Phi gui xe may) -
 *    xac nhan qua doi chieu truc tiep 1 van phong that o ca /vi/ lan /en/. Vi goi Office/_GetDetail
 *    cho MOI the tren trang danh sach se rat ton hieu nang (N request/trang), route nay CHI duoc
 *    goi LUOI tu compare.js luc 1 item duoc THEM vao danh sach so sanh (khong phai luc render
 *    trang) - dung pplus_get_office_detail() co san (da cache transient 600s trong pplus_api_get()).
 *
 * 3) GET pplus/v1/compare-item - lam moi lai TOAN BO 1 item (dung pplus_build_compare_payload(),
 *    inc/render-helpers.php) sang ngon ngu HIEN TAI. Danh sach "So sánh" song trong localStorage
 *    (xem ghi chu dau assets/js/compare.js) nen 1 item duoc THEM luc dang xem ban tieng Viet van
 *    con nguyen van tieng Viet MAI MAI trong may nguoi dung, ke ca sau khi ho doi site sang tieng
 *    Anh - bug thuc te nguoi dung bao cao ("Màn so sánh khi ở tiếng anh... cần lấy nội dung tiếng
 *    anh"): bang so sanh hien nhan tieu chi tieng Anh (dich tinh, render luc tai trang) nhung GIA
 *    TRI (vd "quản lý bởi chủ sở hữu", "16 thang máy Schindler") van tieng Viet vi la du lieu THAT
 *    da luu san tu luc them, khong lien quan lang cua trang dang xem. assets/js/compare.js
 *    ("ensureCorrectLang") goi route nay cho tung item co "item.lang" khac ngon ngu hien tai TRUOC
 *    khi render bang, dung "project_id" (Office/_GetOffices chap nhan loc rieng field nay, BO QUA
 *    moi dieu kien khac - xem ghi chu pplus_get_office_list() trong inc/api-client.php) de lay lai
 *    CHINH XAC 1 toa do o ngon ngu moi.
 *
 * CA 3 proxy qua REST route WordPress giong nguyen tac cua inc/advise-form.php (goi API .NET tu
 * server PHP, tranh phu thuoc CORS cua .NET). Rieng #1 tra ve file NHI PHAN (PDF) nen tu xuat
 * header + echo byte roi exit() truc tiep (bo qua tang JSON-encode mac dinh cua REST server) thay
 * vi return WP_REST_Response - #2/#3 van la JSON binh thuong.
 *
 * Payload gui len .NET cho #1 GIU NGUYEN dung hinh dang Angular goc (xem office.service.ts
 * DowloadPdfCompare + product-compare.component.ts ExportPDF): body la {JsonInput: "<chuoi JSON
 * da stringify cua {Obj:{FavouriteName}, Ids:'1,2,3'}>"} - 2 lop JSON long nhau, KHONG doan lai,
 * .NET (FavouriteController) doc dung dinh dang nay.
 *
 * QUAN TRONG (ca 3 route): KHONG dung pplus_current_lang() truc tiep - ham do doc tien to "/en" tu
 * URL cua CHINH request nay (xem inc/language-route.php), nhung request AJAX/REST goi toi day LUON
 * la "/wp-json/..." (khong bao gio co "/en"), nen se LUON ra 'vi' du trang dang xem la ban EN - bug
 * da tung gap voi route khac, xem ghi chu tuong tu trong inc/location-search.php. Phai doc tham so
 * "lang" gui len tu JS (xac dinh dung luc PHP render trang, truyen qua PPLUS_COMPARE_LANG - xem
 * functions.php) roi tam GAN QUA FILTER "pplus_current_lang" (co san, pplus_current_lang() tu doc
 * filter nay) TRUOC khi goi cac ham dung chung (pplus_get_office_detail()/pplus_get_office_list())
 * von khong nhan tham so lang rieng - tranh phai doi signature cac ham do chi vi 1 nhanh goi hiem.
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Doc tham so "lang" tu request AJAX/REST (KHONG doan qua pplus_current_lang(), xem ghi chu dau file). */
function pplus_compare_request_lang(WP_REST_Request $request): string {
    return $request->get_param('lang') === 'en' ? 'en' : 'vi';
}

add_action('rest_api_init', function () {
    register_rest_route('pplus/v1', '/compare-pdf', [
        'methods'             => 'POST',
        'callback'            => 'pplus_handle_compare_pdf',
        'permission_callback' => '__return_true', // public, giong endpoint .NET goc khong yeu cau auth
    ]);
    register_rest_route('pplus/v1', '/office-park-fees', [
        'methods'             => 'GET',
        'callback'            => 'pplus_handle_office_park_fees',
        'permission_callback' => '__return_true',
    ]);
    register_rest_route('pplus/v1', '/compare-item', [
        'methods'             => 'GET',
        'callback'            => 'pplus_handle_compare_item',
        'permission_callback' => '__return_true',
    ]);
});

function pplus_handle_compare_pdf(WP_REST_Request $request) {
    $ids  = sanitize_text_field((string) $request->get_param('ids'));
    // "$lang" (tham so request, KHONG phai pplus_current_lang() - xem ghi chu dau file) dung cho CA
    // URL goi .NET LAN 2 thong diep pplus_t() ben duoi (thay ternary truc tiep, tranh phai dung
    // filter override chi vi 2 chuoi tinh don gian).
    $lang = pplus_compare_request_lang($request);

    // Chi chap nhan danh sach so nguyen cach nhau boi dau phay (projectId that) - chan moi gia tri
    // la gi khac truoc khi ghep vao payload gui sang .NET.
    if ($ids === '' || !preg_match('/^\d+(,\d+)*$/', $ids)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => $lang === 'en' ? 'Could not export PDF, no offices found!' : 'Không tải được file PDF, không thấy văn phòng nào!',
        ], 200);
    }

    $url = pplus_api_base_url() . '/' . $lang . '/Favourite/DowloadPdfCompare';

    $inner = wp_json_encode([
        'Obj' => ['FavouriteName' => $lang === 'en' ? 'Office comparison' : 'So sánh văn phòng'],
        'Ids' => $ids,
    ]);

    $response = wp_remote_post($url, [
        'timeout' => 20,
        'headers' => ['Content-Type' => 'application/json; charset=utf-8', 'Accept' => 'application/pdf, application/json'],
        'body'    => wp_json_encode(['JsonInput' => $inner]),
    ]);

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) >= 400) {
        return new WP_REST_Response([
            'success' => false,
            'message' => $lang === 'en' ? 'Could not export PDF!' : 'Không tải được file PDF!',
        ], 200);
    }

    $content_type = wp_remote_retrieve_header($response, 'content-type');
    $body         = wp_remote_retrieve_body($response);

    // .NET tra ve JSON loi (thay vi PDF that) khi Ids khong khop van phong nao ca - phat hien qua
    // content-type de khong "tai xuong" nham 1 file .pdf rong/hong.
    if (empty($body) || (is_string($content_type) && stripos($content_type, 'pdf') === false)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => $lang === 'en' ? 'Could not export PDF!' : 'Không tải được file PDF!',
        ], 200);
    }

    $filename = 'SoSanhVanPhong-' . gmdate('d-m-Y') . '.pdf';

    // Bo qua tang JSON-encode mac dinh cua REST server - xuat thang byte nhi phan roi ket thuc.
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($body));
    echo $body;
    exit;
}

/**
 * href dang "/cho-thue-van-phong/ha-noi/quan-hoan-kiem/leadvisors-place-467/" (VI) hoac
 * "/en/office-for-rent/ha-noi/hoan-kiem-district/leadvisors-place-467/" (EN, co them tien to
 * ngon ngu "/en" TRUOC "office-for-rent") - LUON lay dung 3 doan CUOI CUNG (province/district/
 * alias-id), bo qua may doan dau (tien to loai URL + ngon ngu), khop voi ca 2 rewrite rule dang ky
 * trong inc/office-detail-route.php (khong doan lai logic tach alias/id, dung lai
 * pplus_parse_office_alias_id() da co san o do).
 */
function pplus_handle_office_park_fees(WP_REST_Request $request) {
    $href     = sanitize_text_field((string) $request->get_param('href'));
    $segments = array_values(array_filter(explode('/', wp_parse_url($href, PHP_URL_PATH) ?: $href)));
    $count    = count($segments);

    if ($count < 3) {
        return new WP_REST_Response(['success' => false], 200);
    }

    $province = sanitize_text_field($segments[$count - 3]);
    $district = sanitize_text_field($segments[$count - 2]);
    $parsed   = pplus_parse_office_alias_id(sanitize_text_field($segments[$count - 1]));

    if ($parsed['id'] <= 0) {
        return new WP_REST_Response(['success' => false], 200);
    }

    // pplus_get_office_detail() tu doc pplus_current_lang() BEN TRONG, khong nhan tham so lang
    // rieng - tam gan qua filter (xem ghi chu dau file) thay vi sua signature ham dung chung, roi
    // go ngay sau khi dung xong.
    $lang     = pplus_compare_request_lang($request);
    $override = function () use ($lang) { return $lang; };
    add_filter('pplus_current_lang', $override, 20);
    $model = pplus_get_office_detail($parsed['id'], $province, $district, $parsed['alias']);
    remove_filter('pplus_current_lang', $override, 20);

    if (!$model) {
        return new WP_REST_Response(['success' => false], 200);
    }

    $car_park   = '';
    $motor_park = '';
    foreach ((array) ($model['serviceIncludedList'] ?? []) as $service) {
        $code = $service['serviceCode'] ?? '';
        if ($code === '5OTO') {
            $car_park = $service['value'] ?? '';
        } elseif ($code === '7XEMAY') {
            $motor_park = $service['value'] ?? '';
        }
    }

    return new WP_REST_Response([
        'success'       => true,
        'projectId'     => $parsed['id'],
        'carParkInfo'   => $car_park,
        'motorParkInfo' => $motor_park,
    ], 200);
}

/**
 * Lam moi TOAN BO 1 item trong danh sach "So sánh" sang ngon ngu hien tai - xem ghi chu #3 dau
 * file. "project_id" dung Office/_GetOffices (KHONG phai Office/_GetDetail nhu 2 ham tren) de tra
 * ve DUNG shape ma pplus_build_compare_payload() da xu ly san (dung chung code voi luc render the
 * lan dau, xem parts/office-card.php).
 */
function pplus_handle_compare_item(WP_REST_Request $request) {
    $project_id = (int) $request->get_param('project_id');
    if ($project_id <= 0) {
        return new WP_REST_Response(['success' => false], 200);
    }

    $lang     = pplus_compare_request_lang($request);
    $override = function () use ($lang) { return $lang; };
    add_filter('pplus_current_lang', $override, 20);
    $result = pplus_get_office_list(['project_id' => $project_id]);
    remove_filter('pplus_current_lang', $override, 20);

    $item = $result['listOffice'][0] ?? null;
    if (!$item) {
        return new WP_REST_Response(['success' => false], 200);
    }

    return new WP_REST_Response([
        'success' => true,
        'item'    => pplus_build_compare_payload($item),
    ], 200);
}
