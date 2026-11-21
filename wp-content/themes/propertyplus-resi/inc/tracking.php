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
// 13/11/2026 GTM bi TAM TAT theo yeu cau (giam ~650 KB JS cua 4 thu vien Google tren moi trang) -> 8 ngay GA4 (G-DX9EDKC16Y) khong ghi luot truy cap/su kien va
// Google Ads (AW-16624719167, AW-741015465) khong ghi chuyen doi/remarketing.
// 21/11/2026 BAT LAI, kieu NAP TRE de giu phan lon loi ich toc do:
//   - mac dinh: nap GTM o lan tuong tac dau tien (cuon/cham/phim/chuot) HOAC ~3,5 s sau khi trang tai xong (cai nao den truoc);
//   - nap NGAY (khong tre) khi khach den tu quang cao/chien dich (URL co gclid, gbraid, wbraid, fbclid, msclkid, utm_*) - de khong mat gan nguon/chuyen doi -
//     va tren trang cam on sau khi gui form (pplus_form_success) - de khong mat chuyen doi.
// wp-config.php: define('PPLUS_GTM_DELAY', false); = nap ngay nhu truoc; define('PPLUS_GTM_ID', ''); = tat han (Search Console van giu the xac minh duoi).
if (!defined('PPLUS_GTM_ID')) {
    define('PPLUS_GTM_ID', 'GTM-WRVHCF8Q');
}
if (!defined('PPLUS_GTM_DELAY')) {
    define('PPLUS_GTM_DELAY', true);
}

/**
 * Doan nap GTM (ban rut gon cua doan chuan Google, them che do tre). Tach thanh ham de kiem thu (tools: scratchpad gtm_unit).
 *
 * @param bool $delay     true = nap tre (tuong tac dau tien hoac ~3,5 s sau khi tai xong)
 * @param bool $immediate true = nap ngay du dang o che do tre (trang cam on form)
 */
function pplus_gtm_head_script(string $id, bool $delay, bool $immediate): string {
    $js = "(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});"
        . "function go(){if(go.d)return;go.d=1;var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);}";
    if (!$delay || $immediate) {
        $js .= 'go();';
    } else {
        $js .= "if(/[?&](gclid|gbraid|wbraid|fbclid|msclkid|utm_[a-z_]+)=/i.test(location.search)){go();return;}"
            . "var ev=['pointerdown','keydown','scroll','touchstart','mousemove'];"
            . "function once(){ev.forEach(function(e){w.removeEventListener(e,once)});go();}"
            . "ev.forEach(function(e){w.addEventListener(e,once,{passive:true})});"
            . "function later(){setTimeout(go,3500);}"
            . "d.readyState==='complete'?later():w.addEventListener('load',later);";
    }
    return $js . "})(window,document,'script','dataLayer','" . esc_js($id) . "');";
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
        // Doan nap GTM (async; che do tre/ngay xem pplus_gtm_head_script()). Trang cam on form luon nap ngay de khong mat chuyen doi.
        echo '<script>' . pplus_gtm_head_script(PPLUS_GTM_ID, (bool) PPLUS_GTM_DELAY, (bool) get_query_var('pplus_form_success')) . "</script>\n";
    }
}, 2);

add_action('wp_body_open', function () {
    if (pplus_gtm_enabled()) {
        printf('<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=%s" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>' . "\n", esc_attr(PPLUS_GTM_ID));
    }
}, 1);
