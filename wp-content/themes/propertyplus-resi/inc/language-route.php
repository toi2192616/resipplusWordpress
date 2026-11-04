<?php
/**
 * Da ngon ngu cho ban Resi: vi / en / ja (khac ban Van phong cu chi co vi/en).
 *
 * Angular Resi dinh tuyen MOI route co tien to ngon ngu tuong minh (`vi/...`, `en/...`, `ja/...`) -
 * xem app-routing.module.ts. Ban WordPress GIU DUNG quy uoc do (24/10/2026 - truoc day "khong tien to = vi" lam
 * toan bo URL tieng Viet cua web chinh tra 404, xem inc/url-canonical.php): '/vi', '/en', '/ja' deu la tien to that.
 * File nay phat hien + STRIP tien to khoi REQUEST_URI/PATH_INFO SOM NHAT (truoc khi WP parse_request() khop
 * rewrite rule), luu ngon ngu vao hang so PPLUS_LANG. Sau khi strip, MOI route (trang chu, Page that, rewrite
 * rule rieng) tu dong hoat dong y het nhu khong co tien to - khong can nhan doi route. Rieng "/" (khong tien to)
 * = trang chu TIENG ANH, giong web chinh. URL khong o dang chuan (thieu tien to, co "/" cuoi) bi 301 ngay tai day.
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
 * Tach tien to ngon ngu ('/vi', '/en' hoac '/ja') khoi 1 duong dan. Tra ve [lang|null, path_da_strip].
 */
function pplus_detect_lang_prefix(?string $value): array {
    if ($value === null || $value === '') {
        return [null, $value];
    }
    if (preg_match('#^/(vi|en|ja)(/.*)?$#i', $value, $m)) {
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

// 4/11/2026 - IIS + PHP FastCGI (server test loikaka): REQUEST_URI duoc doi sang bang ma ANSI cua may chu nen moi ky tu khong phai ASCII
// (alias tieng Nhat cua trang landing, vd "/ja/rental-apartment/ha-noi/ハノイ...") bi thay bang "?" -> phan sau dau "?" dau tien bi coi la query
// string, trang 404 hoac 301 sang URL toan dau "?" (13/14 URL tieng Nhat trong sitemap loi, local/nginx/Apache khong loi). Neu IIS cung cap ban
// URL goc khong bi doi ma thi dung ban do. Khong co tac dung (khong doi gi) khi cac bien nay khong ton tai (nginx/Apache/Laragon).
if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '?') !== false) {
    foreach (['UNENCODED_URL', 'HTTP_X_ORIGINAL_URL', 'HTTP_X_REWRITE_URL'] as $pplus_iis_key) {
        $pplus_iis_alt = (string) ($_SERVER[$pplus_iis_key] ?? '');
        if ($pplus_iis_alt !== '' && $pplus_iis_alt[0] === '/' && $pplus_iis_alt !== $_SERVER['REQUEST_URI'] && preg_match('/%[89A-Fa-f][0-9A-Fa-f]|[\x80-\xFF]/', $pplus_iis_alt)) {
            $_SERVER['REQUEST_URI'] = $pplus_iis_alt;
            break;
        }
    }
}

// Luu URL GOC (truoc khi strip tien to) - canonical/hreflang/redirect dung ban nay.
$GLOBALS['pplus_original_request_uri'] = $_SERVER['REQUEST_URI'] ?? '/';

// 301 sang dang chuan (co tien to, khong "/" cuoi) NGAY - truoc khi bat ky ham nao khac cua WP/theme chay.
pplus_redirect_to_canonical_request();

// REQUEST_URI: giu query string, chi strip phan path.
$pplus_uri_path  = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
$pplus_uri_query = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY);
[$pplus_uri_lang, $pplus_uri_new_path] = pplus_detect_lang_prefix($pplus_uri_path);
if ($pplus_uri_lang !== null) {
    $pplus_request_lang = $pplus_uri_lang;
    if (!pplus_root_allowed_for_lang($pplus_uri_lang, $pplus_uri_new_path)) {
        $pplus_uri_new_path = PPLUS_NOT_FOUND_PATH; // to hop cheo ngon ngu (vd /en/cho-thue-can-ho/...) -> 404 that, giong web chinh
    }
    $_SERVER['REQUEST_URI'] = $pplus_uri_new_path . ($pplus_uri_query ? '?' . $pplus_uri_query : '');
} elseif ($pplus_uri_path === '/' || $pplus_uri_path === '') {
    // "/" khong tien to = trang chu TIENG ANH (web chinh: Angular mac dinh CurrentLanguage="en").
    $pplus_request_lang = 'en';
}

// PATH_INFO + ORIG_PATH_INFO: WP uu tien PATH_INFO hon REQUEST_URI khi non-empty.
foreach (['PATH_INFO', 'ORIG_PATH_INFO'] as $pplus_srv_key) {
    if (!empty($_SERVER[$pplus_srv_key])) {
        [$pplus_pi_lang, $pplus_pi_new] = pplus_detect_lang_prefix($_SERVER[$pplus_srv_key]);
        if ($pplus_pi_lang !== null) {
            $pplus_request_lang = $pplus_pi_lang;
            $_SERVER[$pplus_srv_key] = pplus_root_allowed_for_lang($pplus_pi_lang, $pplus_pi_new) ? $pplus_pi_new : PPLUS_NOT_FOUND_PATH;
        }
    }
}

define('PPLUS_LANG', $pplus_request_lang);

/**
 * <html lang>: theo ngon ngu cua TRANG (vi / en / ja), khong theo locale WordPress cua site (truoc day moi trang deu ra 1 gia tri co dinh
 * - "en-US" o local, "vi" tren server test - ke ca trang tieng Anh/Nhat). Web chinh luon de lang="en" (loi); o day dung dung chuan
 * de trinh duyet/doc man hinh/cong cu dich/Google hieu dung ngon ngu noi dung.
 */
add_filter('language_attributes', function ($output) {
    return preg_replace('/lang="[^"]*"/', 'lang="' . PPLUS_LANG . '"', $output);
});

/**
 * Tat toan bo redirect "chuan hoa" cua WordPress: ta tu quan ly URL chuan (xem inc/url-canonical.php). redirect_canonical()
 * tu them "/" cuoi cho Page/route dep -> cai nhau voi dang KHONG co "/" cuoi cua web chinh (vong lap 301), con
 * redirect_guess_404_permalink() tu "doan" bai viet gan giong va 301 sang do (vd /vi/ve-chung-toi -> /ve-chung-toi/).
 */
add_filter('redirect_canonical', '__return_false');
add_filter('do_redirect_guess_404_permalink', '__return_false');
remove_action('template_redirect', 'wp_old_slug_redirect');

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
    // x-default = ban TIENG ANH (ngon ngu mac dinh cua site huong toi nguoi nuoc ngoai; trang chu = "/").
    printf('<link rel="alternate" hreflang="x-default" href="%s" />' . "\n", esc_url(pplus_language_switch_url('en')));
}, 5);
