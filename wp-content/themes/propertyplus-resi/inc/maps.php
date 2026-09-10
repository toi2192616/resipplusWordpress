<?php
/**
 * Google Maps cho A1 (danh sach van phong) va A2 (chi tiet van phong). Dung LAI key that dang
 * chay tren Angular production (GlobalConstants.ts `GoogleMapKey`) - da duoc user xac nhan dung
 * truc tiep, khong tu tao key moi. Angular chon NGAU NHIEN 1 key trong mang moi lan load trang de
 * chia tai qua nhieu key (google-maps-loader.service.ts `getRandomGoogleMapKey`) - lam dung y het
 * o day (pplus_google_map_key()).
 *
 * Luu trong 1 WordPress option (textarea, moi dong 1 key) thay vi hard-code cung, de doi/xoay key
 * sau nay khong can sua code - xem inc/settings-page.php.
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Gia tri mac dinh = dung y het mang GoogleMapKey dang chay that tren Angular production. */
function pplus_google_map_default_keys(): string {
    return implode("\n", [
        'AIzaSyAcvWzdK0pQxIsul9Hrumcxd8-uKJUjN1U',
        'AIzaSyDB8OkzP_BJxVISliEpKIfBP_OHpXhOG5s',
        'AIzaSyDF_mWm-bCUnjaw2K8_RGsWOeaDUKwLXss',
        'AIzaSyB5jMYDlOqzqQ7mw4Pj19goD-ZISR0K1G4',
        'AIzaSyCwGLV-FO761wE0lY6Gii33-0op8gQYAt8',
    ]);
}

add_action('admin_init', function () {
    register_setting('pplus_settings', 'pplus_google_map_keys', [
        'sanitize_callback' => 'sanitize_textarea_field',
        'default'           => pplus_google_map_default_keys(),
    ]);
});

/** @return string[] */
function pplus_google_map_keys(): array {
    $raw  = (string) get_option('pplus_google_map_keys', pplus_google_map_default_keys());
    $keys = array_filter(array_map('trim', preg_split('/[\r\n,]+/', $raw)));
    return array_values($keys);
}

/**
 * Local dev (chay qua `php -S localhost:8080` + router.php) dung CO DINH 1 key rieng - do user
 * chi dinh truc tiep ("AIzaSyCwGLV-FO761wE0lY6Gii33-0op8gQYAt8"), vi cac key con lai rat co the
 * bi gioi han HTTP-referrer chi cho phep domain that (propertyplus.vn) nen khong hien tile khi
 * test tren localhost (da xac nhan qua test map rong khong config gi cung khong hien tile).
 * Tren server that (domain khac localhost/127.0.0.1) van chon NGAU NHIEN trong ca 5 key, giong
 * y het getRandomGoogleMapKey() ben Angular production - KHONG doi hanh vi khi len server that.
 */
function pplus_is_local_dev_host(): bool {
    $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
    return (bool) preg_match('/^(localhost|127\.0\.0\.1)(:\d+)?$/i', $host);
}

function pplus_google_map_key(): string {
    if (pplus_is_local_dev_host()) {
        return 'AIzaSyCwGLV-FO761wE0lY6Gii33-0op8gQYAt8';
    }

    $keys = pplus_google_map_keys();
    if (empty($keys)) {
        return '';
    }
    return $keys[array_rand($keys)];
}

/**
 * Nap script Google Maps JS API + truyen key/ngon ngu cho assets/js/pplus-maps.js qua bien global
 * PPLUS_MAP_CONFIG. Goi ham nay tu functions.php, chi tren trang can map (A1/A2) - KHONG enqueue
 * toan site vi day la 1 request ngoai (toi Google) chi nen tai khi that su dung den.
 */
function pplus_enqueue_google_maps(): void {
    $key = pplus_google_map_key();
    if ($key === '') {
        return;
    }
    // Preconnect - CHI tren trang thuc su dung Maps (ham nay chi duoc goi co dieu kien tu
    // functions.php, xem ghi chu goi ham), tranh lang phi hint tren trang khong lien quan.
    // Ban than Google Maps JS API duoc pplus-maps.js TU CHEN <script> luc can (lazy-load, xem
    // pplusDeferIdle()/pplusLoadWhenVisible()), nhung preconnect van co ich vi giam do tre DNS+TLS
    // cho lan goi THAT su ket noi toi sau nay, du co bi hoan lai vai giay.
    add_action('wp_head', function () {
        printf('<link rel="preconnect" href="https://maps.googleapis.com" />' . "\n");
        printf('<link rel="preconnect" href="https://maps.gstatic.com" crossorigin />' . "\n");
    }, 1);
    wp_enqueue_script('pplus-maps', PPLUS_THEME_URI . '/assets/js/pplus-maps.min.js', ['pplus-ob-script-0'], pplus_asset_version('assets/js/pplus-maps.min.js'), true);
    wp_add_inline_script('pplus-maps', 'var PPLUS_MAP_CONFIG = ' . wp_json_encode([
        'key'      => $key,
        'lang'     => pplus_current_lang(),
        // Can cho marker van phong tuy chinh (OfficeMarkerOverlay) doc icon
        // Common/Icons/officeRate{A,B,C}.png - xem assets/js/pplus-maps.js.
        'themeUrl' => PPLUS_THEME_URI,
    ]) . ';', 'before');
}
