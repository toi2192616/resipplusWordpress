<?php
/**
 * REST proxy cho Danh sach yeu thich (JS pplus-favourite.js goi). Vi du lieu yeu thich nam o
 * localStorage (client), server khong biet truoc id nao - JS gui len danh sach projectId, cac
 * endpoint duoi proxy sang API .NET that (giu nguyen guard API base + chuan hoa URL anh ve domain
 * qua pplus_admin_asset_url, KHONG lo port noi bo ra trinh duyet khach - xem inc/api-client.php).
 *
 * CANH BAO 22/9 (CHUA SUA - file nay hien KHONG duoc require o functions.php, xem
 * page-favourite.php doc dau du ve pham vi sua lan nay): 2 endpoint "offices-by-ids"/
 * "office-markers-by-ids" duoi day goi "{lang}/Office/_GetOfficesInputDto"/"{lang}/Office/
 * _OfficeMarkerByInputDto" - DAY LA SAI cho Resi, tan du copy tu theme Van phong. Doi chieu THAT
 * qua office.service.ts (PPlusAngularResi, ham nay DUNG CHUNG ten class "officeService" giua 2
 * vertical nhung tro endpoint khac nhau):
 *   GetInputDto(ids)       -> POST {lang}/Apartment/_GetApartmentInputDto?isdislike=1&inputstring={ids}
 *   MarkerByInputDto(ids)  -> POST {lang}/Apartment/_ApartmentMarkerByInputDto?isdislike=1&inputstring={ids}
 *   DowloadPdfFavourite(data) -> POST {lang}/Favourite/DowloadPdf (body JsonInput, tra ve blob PDF) - CAI NAY DUNG, khong doi.
 * Se can sua 2 endpoint tren (+ doi ten bien/response tu "office"/"projectId" sang "apartment"/
 * "apartmentId" cho dung ngu canh) khi xay favourite-detail.component that (trang RIENG
 * "/favourite/favourite-detail", chua lam - xem page-favourite.php).
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', function () {
    register_rest_route('pplus/v1', '/offices-by-ids', [
        'methods'             => 'GET',
        'callback'            => 'pplus_rest_offices_by_ids',
        'permission_callback' => '__return_true',
        'args'                => [
            'ids'  => ['required' => true],
            'lang' => ['default' => 'vi'],
        ],
    ]);

    register_rest_route('pplus/v1', '/office-markers-by-ids', [
        'methods'             => 'GET',
        'callback'            => 'pplus_rest_office_markers_by_ids',
        'permission_callback' => '__return_true',
        'args'                => [
            'ids'  => ['required' => true],
            'lang' => ['default' => 'vi'],
        ],
    ]);

    register_rest_route('pplus/v1', '/favourite-pdf', [
        'methods'             => 'POST',
        'callback'            => 'pplus_rest_favourite_pdf',
        'permission_callback' => '__return_true',
    ]);
});

/** Chuan hoa "1,2,3" -> chi giu so + dau phay, chong injection vao query string API. */
function pplus_sanitize_id_list($raw): string {
    $ids = array_filter(array_map('intval', explode(',', (string) $raw)), fn($n) => $n > 0);
    return implode(',', array_unique($ids));
}

function pplus_favourite_lang($request): string {
    $lang = strtolower((string) $request->get_param('lang'));
    return $lang === 'en' ? 'en' : 'vi';
}

/**
 * POST {lang}/Office/_GetOfficesInputDto?isdislike=1&inputstring={ids} (body rong, giong Angular).
 * Tra ve listOffice DA gan PhotoListBaSE (URL anh dai day du tro domain public).
 */
function pplus_rest_offices_by_ids(WP_REST_Request $request) {
    $ids = pplus_sanitize_id_list($request->get_param('ids'));
    if ($ids === '') {
        return new WP_REST_Response(['listOffice' => []], 200);
    }
    $lang = pplus_favourite_lang($request);
    $url  = pplus_api_base_url() . '/' . $lang . '/Office/_GetOfficesInputDto?isdislike=1&inputstring=' . rawurlencode($ids);

    $response = wp_remote_post($url, [
        'timeout' => 12,
        'headers' => ['Accept' => 'application/json', 'Content-Type' => 'application/json; charset=utf-8'],
        'body'    => '',
    ]);
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) >= 400) {
        return new WP_REST_Response(['listOffice' => [], 'error' => 'upstream'], 200);
    }
    $body    = json_decode(wp_remote_retrieve_body($response), true);
    $offices = $body['result']['listOffice'] ?? [];

    // Chuan hoa URL anh ve domain public (KHONG de lo port noi bo) - xem pplus_admin_asset_url.
    foreach ($offices as &$o) {
        $first = $o['photoList'][0] ?? '';
        $o['PhotoListBaSE'] = $first !== '' ? pplus_admin_asset_url($first) : '';
    }
    unset($o);

    return new WP_REST_Response(['listOffice' => $offices], 200);
}

/** POST {lang}/Office/_OfficeMarkerByInputDto?isdislike=1&inputstring={ids} -> markers (lat/lng...). */
function pplus_rest_office_markers_by_ids(WP_REST_Request $request) {
    $ids = pplus_sanitize_id_list($request->get_param('ids'));
    if ($ids === '') {
        return new WP_REST_Response(['markers' => []], 200);
    }
    $lang = pplus_favourite_lang($request);
    $url  = pplus_api_base_url() . '/' . $lang . '/Office/_OfficeMarkerByInputDto?isdislike=1&inputstring=' . rawurlencode($ids);

    $response = wp_remote_post($url, [
        'timeout' => 12,
        'headers' => ['Accept' => 'application/json', 'Content-Type' => 'application/json; charset=utf-8'],
        'body'    => '',
    ]);
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) >= 400) {
        return new WP_REST_Response(['markers' => [], 'error' => 'upstream'], 200);
    }
    $body    = json_decode(wp_remote_retrieve_body($response), true);
    $markers = $body['result'] ?? [];
    foreach ($markers as &$m) {
        $img = $m['img'] ?? ($m['photoList'][0] ?? '');
        if ($img !== '') {
            $m['imgUrl'] = pplus_admin_asset_url($img);
        }
    }
    unset($m);

    return new WP_REST_Response(['markers' => $markers], 200);
}

/**
 * POST {lang}/Favourite/DowloadPdf (body {JsonInput}) -> tra ve blob PDF. Proxy nguyen binary ve
 * cho trinh duyet tai. JS gui { name, ids } -> ta dung lai dung format Angular:
 * JsonInput = JSON.stringify({ Obj: { FavouriteName }, Ids }).
 */
function pplus_rest_favourite_pdf(WP_REST_Request $request) {
    $params = $request->get_json_params();
    $name   = sanitize_text_field($params['name'] ?? 'Văn phòng yêu thích');
    $ids    = pplus_sanitize_id_list($params['ids'] ?? '');
    if ($ids === '') {
        return new WP_REST_Response(['error' => 'no-ids'], 400);
    }
    $lang = (strtolower((string) ($params['lang'] ?? 'vi')) === 'en') ? 'en' : 'vi';

    $json_input = wp_json_encode([
        'Obj' => ['FavouriteName' => $name],
        'Ids' => $ids,
    ]);
    $url = pplus_api_base_url() . '/' . $lang . '/Favourite/DowloadPdf';

    // Tao PDF phia .NET cham (do thuc te: hang chuc giay); PHP mac dinh dung o 30s (max_execution_time) va bao "critical
    // error" thay vi tra loi JSON -> noi han muc rieng cho request nay, cho API toi da 45s roi tra loi ro rang.
    if (function_exists('set_time_limit')) {
        @set_time_limit(60);
    }
    $response = wp_remote_post($url, [
        'timeout'     => 45,
        'redirection' => 0,
        'headers'     => ['Content-Type' => 'application/json; charset=utf-8'],
        'body'        => wp_json_encode(['JsonInput' => $json_input]),
    ]);
    $pdf = is_wp_error($response) ? '' : (string) wp_remote_retrieve_body($response);

    // 24/10/2026 (port ban Van phong c09df2f): CHI tra file khi than response THAT la PDF (bat dau "%PDF").
    // Do thuc te API Resi: id khong ton tai -> 301 (sang trang dang nhap admin, HTML); id that hien tai -> cho
    // 122s roi 502 (HTML nginx). Truoc day moi response 200 deu bi gan "application/pdf" -> nguoi dung tai ve
    // file .pdf HONG ma khong biet vi sao. Gio tra JSON {success:false, message} de JS hien dung thong bao.
    if (wp_remote_retrieve_response_code($response) !== 200 || strncmp($pdf, '%PDF', 4) !== 0) {
        return new WP_REST_Response([
            'success' => false,
            'message' => $lang === 'en' ? 'Could not export PDF, please try again later!' : 'Không tải được file PDF, vui lòng thử lại sau!',
        ], 502);
    }
    // Tra binary PDF truc tiep (khong qua JSON) de JS tao blob tai file.
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="favourite.pdf"');
    header('Content-Length: ' . strlen($pdf));
    echo $pdf;
    exit;
}
