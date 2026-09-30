<?php
/**
 * Nut "Tìm kiếm khu vực này" (.searching-tool-button) o che do xem ban do tren mobile - port
 * searchToolMobile() -> loadData() cua search-base.component.ts (Angular goc): sau khi nguoi dung
 * keo/zoom ban do, bam nut nay de tai lai danh sach can ho + marker cho DUNG vung ban do dang nhin
 * thay (ne/sw lat/lng cua map.getBounds()).
 *
 * Dung lai Apartment/_GetApartments (cung endpoint pplus_get_apartment_list()) voi 4 so bounds - da
 * xac nhan qua goi API that: ne_lat/ne_lng/sw_lat/sw_lng loc dung theo vung.
 *
 * "lang" PHAI truyen qua tham so request: route REST luon la "/wp-json/..." (khong co tien to
 * "/en") nen pplus_current_lang() se luon ra 'vi' - dat lai qua filter 'pplus_current_lang' cho rieng
 * request nay de card render (pplus_localize) + goi API dung ngon ngu cua trang goc.
 */
if (!defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', function () {
    register_rest_route('pplus/v1', '/map-area', [
        'methods'             => 'GET',
        'callback'            => 'pplus_handle_map_area_search',
        'permission_callback' => '__return_true',
    ]);
});

function pplus_handle_map_area_search(WP_REST_Request $request) {
    $lang = in_array($request->get_param('lang'), ['en', 'ja'], true) ? $request->get_param('lang') : 'vi';
    add_filter('pplus_current_lang', function () use ($lang) {
        return $lang;
    });

    $bounds = [];
    foreach (['ne_lat', 'ne_lng', 'sw_lat', 'sw_lng'] as $key) {
        $value = $request->get_param($key);
        if (!is_numeric($value)) {
            return new WP_REST_Response(['html' => '', 'markers' => [], 'count' => 0], 400);
        }
        $bounds[$key] = (float) $value;
    }

    $filter = array_merge($bounds, ['apartmentTypeList' => '1,2']);
    // 18 = cung so can/trang cua page-reference.php; price_sort_type 2 = "Giá giảm dần" (mac dinh).
    $listing = pplus_get_apartment_list($filter, 0, 2, 18);

    $markers = [];
    ob_start();
    foreach ($listing['listApartments'] as $apt) {
        get_template_part('parts/apartment-card', null, ['item' => $apt]);
        if (empty($apt['lat']) || empty($apt['lng'])) {
            continue;
        }
        $markers[] = [
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
    $html = ob_get_clean();

    return new WP_REST_Response([
        'html'    => $html,
        'markers' => $markers,
        'count'   => (int) $listing['total_count'],
    ], 200);
}
