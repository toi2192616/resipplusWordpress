<?php
/**
 * Xac minh Google + Google Tag Manager cua web chinh (propertyplus.com.vn) - 4/11/2026.
 *
 * Ban Angular dang chay tren web chinh co SAN 2 thu ma theme Resi chua tung xuat:
 *   1. <meta name="google-site-verification" content="_EwTj..."> - Search Console dung de xac minh quyen so huu property
 *      https://propertyplus.com.vn/ (tai khoan quan tri hien la "chu so huu duoc uy quyen", chu so huu goc co the xac minh bang the nay,
 *      bang GTM hoac bang DNS - khong biet duoc cach nao). Chuyen sang Resi ma khong co the nay thi co the MAT xac minh o lan kiem tra
 *      lai dinh ky -> mat toan bo du lieu/quyen Search Console cua site.
 *   2. Google Tag Manager GTM-WRVHCF8Q - toan bo theo doi truy cap/chuyen doi/remarketing. Khong co thi so lieu dung ngay luc chuyen web.
 *
 * Ghi de bang hang so trong wp-config.php: define('PPLUS_GTM_ID', 'GTM-XXXX'); define('PPLUS_GTM_ID', ''); de TAT; tuong tu
 * PPLUS_GOOGLE_SITE_VERIFICATION. GTM CHI nap khi site cho phep index (pplus_is_noindex() = false) de server thu nghiem (loikaka)
 * khong gui du lieu thu vao Analytics that.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('PPLUS_GOOGLE_SITE_VERIFICATION')) {
    define('PPLUS_GOOGLE_SITE_VERIFICATION', '_EwTjlz4bpbpsE8dEwY3bbIye3ejKDjCV1JlDZRfy1U');
}
if (!defined('PPLUS_GTM_ID')) {
    define('PPLUS_GTM_ID', 'GTM-WRVHCF8Q');
}

/** GTM co nap tren request nay khong (co ma hop le va khong o che do noindex/server thu nghiem). */
function pplus_gtm_enabled(): bool {
    return PPLUS_GTM_ID !== '' && preg_match('/^GTM-[A-Z0-9]+$/', PPLUS_GTM_ID)
        && !is_admin() && (!function_exists('pplus_is_noindex') || !pplus_is_noindex());
}

add_action('wp_head', function () {
    if (PPLUS_GOOGLE_SITE_VERIFICATION !== '') {
        printf('<meta name="google-site-verification" content="%s" />' . "\n", esc_attr(PPLUS_GOOGLE_SITE_VERIFICATION));
    }
    if (pplus_gtm_enabled()) {
        // Doan chuan cua Google (giong ban dang chay tren web chinh), nap async.
        printf("<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','%s');</script>\n", esc_js(PPLUS_GTM_ID));
    }
}, 2);

add_action('wp_body_open', function () {
    if (pplus_gtm_enabled()) {
        printf('<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=%s" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>' . "\n", esc_attr(PPLUS_GTM_ID));
    }
}, 1);
