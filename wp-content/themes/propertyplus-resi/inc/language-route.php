<?php
/**
 * Da ngon ngu cho ban Resi: vi / en / ja (khac ban Van phong cu chi co vi/en).
 *
 * Angular Resi dinh tuyen MOI route co tien to ngon ngu tuong minh (`vi/...`, `en/...`, `ja/...`) -
 * xem app-routing.module.ts. Ban WordPress dung quy uoc: KHONG tien to = 'vi' (mac dinh), con
 * '/en' va '/ja' la tien to that. File nay phat hien + STRIP tien to '/en' hoac '/ja' khoi
 * REQUEST_URI/PATH_INFO SOM NHAT (truoc khi WP parse_request() khop rewrite rule), luu ngon ngu
 * vao hang so PPLUS_LANG. Sau khi strip, MOI route (trang chu, Page that, rewrite rule rieng) tu
 * dong hoat dong y het nhu khong co tien to - khong can nhan doi route.
 *
 * PHAI duoc require DAU TIEN trong functions.php.
 *
 * Vi sao phai sua ca PATH_INFO (khong chi REQUEST_URI): WP_Class::parse_request() uu tien doc
 * $_SERVER['PATH_INFO'] neu khong rong de tim "requested_path" khop rewrite - neu chi strip
 * REQUEST_URI thi '/en' van con trong PATH_INFO va WP khong bao gio khop route dung.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Tach tien to ngon ngu ('/en' hoac '/ja') khoi 1 duong dan. Tra ve [lang|null, path_da_strip].
 */
function pplus_detect_lang_prefix(?string $value): array {
    if ($value === null || $value === '') {
        return [null, $value];
    }
    if (preg_match('#^/(en|ja)(/.*)?$#i', $value, $m)) {
        $lang     = strtolower($m[1]);
        $stripped = $m[2] ?? '/';
        if ($stripped === '') {
            $stripped = '/';
        }
        return [$lang, $stripped];
    }
    return [null, $value];
}

$pplus_request_lang = 'vi';

// REQUEST_URI: giu query string, chi strip phan path.
$pplus_uri_path  = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
$pplus_uri_query = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY);
[$pplus_uri_lang, $pplus_uri_new_path] = pplus_detect_lang_prefix($pplus_uri_path);
if ($pplus_uri_lang !== null) {
    $pplus_request_lang = $pplus_uri_lang;
    $_SERVER['REQUEST_URI'] = $pplus_uri_new_path . ($pplus_uri_query ? '?' . $pplus_uri_query : '');
}

// PATH_INFO + ORIG_PATH_INFO: WP uu tien PATH_INFO hon REQUEST_URI khi non-empty.
foreach (['PATH_INFO', 'ORIG_PATH_INFO'] as $pplus_srv_key) {
    if (!empty($_SERVER[$pplus_srv_key])) {
        [$pplus_pi_lang, $pplus_pi_new] = pplus_detect_lang_prefix($_SERVER[$pplus_srv_key]);
        if ($pplus_pi_lang !== null) {
            $pplus_request_lang = $pplus_pi_lang;
            $_SERVER[$pplus_srv_key] = $pplus_pi_new;
        }
    }
}

define('PPLUS_LANG', $pplus_request_lang);

/**
 * Gan lai tien to '/en' hoac '/ja' vao MOI Location header cua redirect_canonical() khi request goc
 * co tien to do - neu khong, khi WP tu 301 them dau '/' cuoi (permalink dep) se lam MAT tien to
 * ngon ngu -> trang dich hien sai ngon ngu (bug da gap o ban cu voi /en).
 */
if (PPLUS_LANG !== 'vi') {
    add_filter('redirect_canonical', function (string $redirect_url) {
        $prefix = '/' . PPLUS_LANG;
        $path   = parse_url($redirect_url, PHP_URL_PATH) ?: '/';
        if (strpos($path, $prefix) === 0) {
            return $redirect_url;
        }
        $query    = parse_url($redirect_url, PHP_URL_QUERY);
        $fragment = parse_url($redirect_url, PHP_URL_FRAGMENT);
        $new_url  = home_url($prefix . $path);
        if ($query) {
            $new_url .= '?' . $query;
        }
        if ($fragment) {
            $new_url .= '#' . $fragment;
        }
        return $new_url;
    }, 10, 1);
}

/**
 * hreflang cho toan site (vi/en/ja + x-default=vi). pplus_language_switch_url() tu tinh URL
 * "trang nay o ngon ngu kia" theo dung co che tien to o tren.
 */
add_action('wp_head', function () {
    if (is_admin()) {
        return;
    }
    $vi_url = pplus_language_switch_url('vi');
    printf('<link rel="alternate" hreflang="vi" href="%s" />' . "\n", esc_url($vi_url));
    printf('<link rel="alternate" hreflang="en" href="%s" />' . "\n", esc_url(pplus_language_switch_url('en')));
    printf('<link rel="alternate" hreflang="ja" href="%s" />' . "\n", esc_url(pplus_language_switch_url('ja')));
    printf('<link rel="alternate" hreflang="x-default" href="%s" />' . "\n", esc_url($vi_url));
}, 5);
