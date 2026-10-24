<?php
/**
 * Chuan hoa URL cho KHOP 100% web chinh (propertyplus.com.vn - ban Angular dang chay).
 *
 * 24/10/2026 - doi chieu truc tiep voi web chinh (sitemap + render bang Chrome that + route Angular):
 *  1. MOI trang co tien to ngon ngu tuong minh: "/vi/...", "/en/...", "/ja/..." (Angular chon ngon ngu theo
 *     2 chu dau cua duong dan, app.component.ts SetLangBase()). Ban WP truoc day coi "khong tien to = tieng Viet"
 *     -> toan bo URL tieng Viet cua web chinh (/vi/cho-thue-can-ho/...) tra 404 tren WP.
 *  2. KHONG co dau "/" cuoi: canonical cua web chinh la "https://propertyplus.com.vn/vi/cho-thue-can-ho/ha-noi"
 *     (sitemap + link noi bo deu khong co "/" cuoi). WP mac dinh (permalink /%postname%/) them "/" -> moi URL lech 1 ky tu.
 *  3. Trang chu: "/" = trang chu TIENG ANH MAC DINH (khong tien to -> Angular mac dinh "en", canonical "/", la URL duy nhat
 *     co trong sitemap web chinh); "/vi" = tieng Viet, "/ja" = tieng Nhat. "/en" (web chinh van phuc vu y het "/") bi gop ve "/"
 *     bang 301 de khong trung noi dung; cac trang con van la "/en/...", "/vi/...", "/ja/..." nhu web chinh.
 *
 * File nay CHI chua ham thuan + 1 bo loc home_url - khong goi API, khong phu thuoc template. Duoc nap TRUOC
 * inc/language-route.php.
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Goc duong dan chi co o ban Viet (khong co ban EN/JA cung ten) -> khi thieu tien to thi gan "/vi". */
const PPLUS_ROOTS_VI = ['cho-thue-can-ho', 've-chung-toi', 'lien-he', 'so-sanh-van-phong', 'biet-thu-cho-thue'];
/** Goc duong dan cua ban EN/JA -> khi thieu tien to thi gan "/en" (hoac ngon ngu hien tai neu la ja). */
const PPLUS_ROOTS_EN = ['rental-apartment', 'about-us', 'contact', 'villa-for-rent', 'office-compare'];
/** Goc duong dan dung CHUNG ca 3 ngon ngu (chi doi phan sau) -> thieu tien to thi dung ngon ngu hien tai. */
const PPLUS_ROOTS_SHARED = ['blogs', 'blog', 'service', 'owner', 'danh-sach-yeu-thich', 'favourite', 'street', 'easy-living', 'author'];

/**
 * Duong dan chuan cua 1 duong dan noi bo cua site (chi PATH, khong co host/query).
 *
 * @param string $path         Duong dan bat dau bang "/" (co the co tien to ngon ngu, co the co "/" cuoi).
 * @param string $current_lang Ngon ngu cua request hien tai (dung khi thieu tien to o goc dung chung).
 * @return string|null Duong dan chuan, hoac null neu day KHONG phai route cua site (asset, wp-json, admin...) - khong dong vao.
 */
function pplus_canonical_path(string $path, string $current_lang = 'vi'): ?string {
    if ($path === '' || $path === '/') {
        return '/'; // trang chu tieng Anh (xem docblock)
    }
    $p = '/' . trim($path, '/');

    // Doan rong ("ha-noi//ten-du-an": du an thieu quan trong du lieu) -> web chinh (Angular) tu chuan hoa ve trang cha, canonical
    // = trang tinh. Cat tu cho "//" tro di (chi ap dung cho duong dan thuoc route cua site - da qua nhanh null o duoi).
    if (($pos = strpos($p, '//')) !== false) {
        $p = substr($p, 0, $pos);
        if ($p === '') {
            return '/';
        }
    }

    // "/en" (trang chu tieng Anh co tien to) trung het noi dung "/" - "/" moi la trang chu mac dinh (web chinh chi dua "/" vao sitemap,
    // site huong toi nguoi nuoc ngoai) -> gop ve "/" (301). Cac trang con van giu "/en/..." nhu web chinh.
    if ($p === '/en') {
        return '/';
    }

    // Co tien to ngon ngu tuong minh: chi bo "/" cuoi ("/vi/" -> "/vi").
    if (preg_match('#^/(vi|en|ja)(/|$)#', $p)) {
        return $p;
    }

    $root = explode('/', ltrim($p, '/'), 2)[0];
    if (in_array($root, PPLUS_ROOTS_VI, true)) {
        return '/vi' . $p;
    }
    if (in_array($root, PPLUS_ROOTS_EN, true)) {
        return '/' . ($current_lang === 'ja' ? 'ja' : 'en') . $p;
    }
    if (in_array($root, PPLUS_ROOTS_SHARED, true)) {
        return '/' . (in_array($current_lang, ['vi', 'en', 'ja'], true) ? $current_lang : 'vi') . $p;
    }
    return null;
}

/** Duong dan cua request GOC (truoc khi language-route.php strip tien to). */
function pplus_original_path(): string {
    $uri = $GLOBALS['pplus_original_request_uri'] ?? ($_SERVER['REQUEST_URI'] ?? '/');
    return parse_url($uri, PHP_URL_PATH) ?: '/';
}

/** Dang o trang chu goc "/" (tieng Anh) - khac "/vi", "/en", "/ja". */
function pplus_is_root_home(): bool {
    return pplus_original_path() === '/';
}

/**
 * Goc duong dan (doan dau sau tien to ngon ngu) co duoc phep o ngon ngu do khong? Web chinh (Angular) chi dang ky
 * "vi/cho-thue-can-ho", "en/rental-apartment", "vi/ve-chung-toi", "en/about-us"... - cac to hop CHEO ngon ngu
 * ("/en/cho-thue-can-ho/...", "/vi/about-us") roi vao trang 404 (noindex). WP truoc day van tra 200 (cung noi dung o 2 URL
 * -> trung noi dung). Goc la null/khong thuoc danh sach nao -> de WP tu quyet dinh.
 */
function pplus_root_allowed_for_lang(string $lang, string $path_without_prefix): bool {
    $root = explode('/', ltrim($path_without_prefix, '/'), 2)[0];
    if ($root === '') {
        return true;
    }
    if ($lang === 'vi') {
        return !in_array($root, PPLUS_ROOTS_EN, true);
    }
    return !in_array($root, PPLUS_ROOTS_VI, true);
}

/** Duong dan khong khop bat ky rewrite rule nao -> WordPress tra 404 that (dung khi to hop ngon ngu/goc khong hop le). */
const PPLUS_NOT_FOUND_PATH = '/pplus-wrong-language-path';

/**
 * Tach (lang, path-khong-tien-to) tu 1 duong dan da chuan hoa. "/" -> ['en', '/'].
 *
 * @return array{0:string,1:string}
 */
function pplus_split_lang_path(string $canonical_path): array {
    if (preg_match('#^/(vi|en|ja)(/.*)?$#', $canonical_path, $m)) {
        return [$m[1], ($m[2] ?? '') === '' ? '/' : $m[2]];
    }
    return ['en', '/'];
}

/**
 * "/vi/biet-thu-cho-thue/..." -> "/vi/cho-thue-can-ho/...", "/en|ja/villa-for-rent/..." -> "/en|ja/rental-apartment/..." (cung phan con lai).
 * Chi doi khi goc biet thu DUNG ngon ngu (cheo ngon ngu van la 404 nhu web chinh). Duong dan khac giu nguyen.
 */
function pplus_villa_root_to_apartment_root(string $canonical_path): string {
    if (preg_match('#^/vi/biet-thu-cho-thue(/.*)?$#', $canonical_path, $m)) {
        return '/vi/cho-thue-can-ho' . ($m[1] ?? '');
    }
    if (preg_match('#^/(en|ja)/villa-for-rent(/.*)?$#', $canonical_path, $m)) {
        return '/' . $m[1] . '/rental-apartment' . ($m[2] ?? '');
    }
    return $canonical_path;
}

/**
 * Neu request hien tai KHONG o dang chuan thi 301 sang dang chuan (giu nguyen query string). Chay RAT SOM (luc nap
 * theme, truoc khi WP parse request / goi API) nen khong ton chi phi render.
 * Chi ap dung: GET/HEAD, khong phai admin/ajax/cron/REST, duong dan la route cua site (pplus_canonical_path != null).
 */
function pplus_redirect_to_canonical_request(): void {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (($method !== 'GET' && $method !== 'HEAD') || PHP_SAPI === 'cli') {
        return;
    }
    if ((defined('WP_ADMIN') && WP_ADMIN) || (function_exists('wp_doing_ajax') && wp_doing_ajax()) || (defined('DOING_CRON') && DOING_CRON)) {
        return;
    }
    $uri  = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($uri, PHP_URL_PATH) ?: '/';
    // Tep tinh / WP noi bo / sitemap: co dau "." o doan cuoi (".xml", ".css"...) hoac bat dau wp-* / index.php -> bo qua.
    $last = basename($path);
    if (strpos($last, '.') !== false || preg_match('#^/(wp-|index\.php)#', $path)) {
        return;
    }
    $query = parse_url($uri, PHP_URL_QUERY);
    $tail  = $query ? '?' . $query : '';
    // URL CU da bi doi (bang LogUrlSite cua web chinh, xem inc/legacy-redirects.php) -> 301 thang sang URL moi (1 hop duy nhat).
    $legacy = function_exists('pplus_legacy_redirect_target') ? pplus_legacy_redirect_target($path) : null;
    if ($legacy !== null) {
        wp_redirect(home_url($legacy) . $tail, 301, 'PropertyPlus-Legacy');
        exit;
    }
    // Mac dinh ngon ngu cua site la TIENG ANH: duong dan dung chung khong tien to (/blogs, /service/...) -> /en/... (goc rieng cua tieng Viet van ra /vi/...).
    $canonical = pplus_canonical_path($path, 'en');
    if ($canonical !== null) {
        // Goc BIET THU ("biet-thu-cho-thue" / "villa-for-rent"): san pham biet thu da bo khoi web Resi (quyet dinh 13/9) nhung web chinh
        // van phuc vu ~280 URL kieu nay (sitemap_villa.xml), da so la TRANG CAN HO bi gan nham goc biet thu -> 301 sang trang tuong ung o
        // goc can ho (1 hop, khong can goi API). Biet thu that -> trang dich tu tra 404 (dung quyet dinh bo san pham).
        $canonical = pplus_villa_root_to_apartment_root($canonical);
    }
    if ($canonical === null || $canonical === $path) {
        return;
    }
    wp_redirect(home_url($canonical) . $tail, 301, 'PropertyPlus-URL');
    exit;
}

/**
 * Loc home_url(): moi URL noi bo dung home_url('/cho-thue-can-ho/...') (hoac co/thieu tien to, co/thieu "/" cuoi) deu
 * ra DUNG dang chuan. Day la luoi an toan cho ~60 cho sinh URL rai rac trong theme; cac cho chinh da duoc sua truc tiep.
 * Khong dong vao path khong phai route (wp-content, wp-json, trang chu...).
 */
add_filter('home_url', function ($url, $path) {
    if (!is_string($path) || $path === '' || $path === '/') {
        return $url;
    }
    $q    = strpos($path, '?');
    $hash = strpos($path, '#');
    $cut  = min($q === false ? PHP_INT_MAX : $q, $hash === false ? PHP_INT_MAX : $hash);
    $only = $cut === PHP_INT_MAX ? $path : substr($path, 0, $cut);
    $tail = $cut === PHP_INT_MAX ? '' : substr($path, $cut);
    $only = '/' . ltrim($only, '/');
    $lang = defined('PPLUS_LANG') ? PPLUS_LANG : 'vi';
    $canonical = pplus_canonical_path($only, $lang);
    if ($canonical === null) {
        return $url;
    }
    $suffix = '/' . ltrim($path, '/');
    $base   = substr($url, 0, strlen($url) - strlen($suffix));
    return $base . $canonical . $tail;
}, 99, 2);
