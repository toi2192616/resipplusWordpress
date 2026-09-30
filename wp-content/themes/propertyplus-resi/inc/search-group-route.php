<?php
/**
 * Goi y "Tìm theo tên dự án" khi go vao o "Bạn muốn đi đâu" (#searchModal, panel mobile, o tim kiem trang chu).
 * Port findNameGroupToBack() cua search-base/search-home.component.ts: GET {lang}/Apartment/_GetFilterGroupList
 * ?FilterText=... - chi dung projectNameList (template goc chi hien nhom nay + nhom Google, streetList/districtList
 * khong duoc render).
 *
 * Khac ban goc co chu dich: search-base.component.ts truyen CHUOI thay vi {textSearch} nen API khong nhan
 * FilterText va LUON tra ve cung 5 du an bat ke go gi (xac nhan tren propertyplus.com.vn 30/9/2026: go
 * "somerset" van ra Vinhomes Metropolis/Royal City...). O day gui dung FilterText nhu search-home.component.ts.
 *
 * "lang" lay tu tham so (route /wp-json/ khong co tien to /en /ja nen pplus_current_lang() luon ra 'vi').
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', function () {
    register_rest_route('pplus/v1', '/search-group', [
        'methods'             => 'GET',
        'callback'            => 'pplus_handle_search_group',
        'permission_callback' => '__return_true',
    ]);
});

function pplus_handle_search_group(WP_REST_Request $request): WP_REST_Response {
    $q    = trim(sanitize_text_field((string) $request->get_param('q')));
    $lang = in_array($request->get_param('lang'), ['en', 'ja'], true) ? $request->get_param('lang') : 'vi';

    $projects = [];
    if ($q !== '') {
        // 1 gio: du an it thay doi, API .NET ~1s/lan goi.
        $data = pplus_api_get("{$lang}/Apartment/_GetFilterGroupList", ['FilterText' => $q], HOUR_IN_SECONDS);
        $seen = [];
        foreach ((array) ($data['result']['projectNameList'] ?? []) as $item) {
            $id   = (int) ($item['projectId'] ?? 0);
            $name = trim((string) ($item['projectName'] ?? ''));
            if ($id <= 0 || $name === '' || isset($seen[$id])) {
                continue;
            }
            $seen[$id]  = true;
            $projects[] = ['id' => $id, 'name' => $name];
        }
    }

    $response = new WP_REST_Response(['projects' => $projects], 200);
    $response->header('Cache-Control', 'public, max-age=600');
    return $response;
}
