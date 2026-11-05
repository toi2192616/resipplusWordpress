<?php
/**
 * Trang cam on sau khi gui form thanh cong: /Apartment/IssueLeaseApartmentSuccses (port send-mail-succes.component cua web chinh).
 *
 * VI SAO CAN (phat hien 5/11/2026): web chinh (Angular) sau MOI form thanh cong (tu van, dang ky dich vu, dang ky cho thue...) chuyen han
 * sang URL nay (window.location.href). Container GTM-WRVHCF8Q bat dung URL do lam "chuyen doi": 1 su kien GA4 "Dang ky form thanh cong" +
 * cac tag chuyen doi Google Ads (dieu kien: Page Path chua "IssueLeaseApartmentSuccses"). Resi giu quyet dinh da chot la KHONG chuyen trang
 * (hien thong bao ngay tai cho) nen truoc day KHONG co luot xem trang nay -> mat toan bo du lieu chuyen doi quang cao/GA4 ngay khi chuyen web,
 * ma khong ai thay duoc trong kiem tra SEO.
 *
 * CACH LAM: giu nguyen UX (khong chuyen trang) + nap trang nay trong 1 iframe an ngay sau khi gui thanh cong (assets/js/main.js:
 * pplusTrackFormSuccess). Iframe la 1 luot xem trang that, cung origin, nen GTM trong iframe tu bat dung trigger/tag cu - khong phai sua GTM.
 * Chi nap khi GTM duoc bat (PPLUS_CONVERSION_URL chi duoc in khi pplus_gtm_enabled()) -> server test/local khong gui du lieu gia.
 *
 * URL KHONG tien to ngon ngu (giong web chinh) de duong dan trong GA4 trung ban cu; "/vi|/en|/ja/Apartment/..." cung chay (tien to bi strip
 * boi inc/language-route.php) de nguoi mo truc tiep thay dung ngon ngu. Luon noindex (khong co link toi, khong dua vao sitemap).
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', function () {
    add_rewrite_rule('^Apartment/IssueLeaseApartmentSuccses/?$', 'index.php?pplus_form_success=1', 'top');
});

add_filter('query_vars', function (array $vars): array {
    $vars[] = 'pplus_form_success';
    return $vars;
});

/** Query var tu khong phai post/page nen WP coi la trang chu (is_home) -> header gan nham class trang chu; sua nhu inc/blog-routes.php. */
add_action('parse_query', function (WP_Query $query) {
    if ($query->is_main_query() && get_query_var('pplus_form_success')) {
        $query->is_home = false;
    }
});

add_filter('template_include', function (string $template): string {
    if (get_query_var('pplus_form_success')) {
        $custom = PPLUS_THEME_DIR . '/page-form-success.php';
        if (file_exists($custom)) {
            return $custom;
        }
    }
    return $template;
});

add_filter('pre_get_document_title', function (string $title): string {
    if (!get_query_var('pplus_form_success')) {
        return $title;
    }
    // Tieu de cua route Angular: 'Gửi hỗ trợ thành công, chúng tôi sẽ liên hệ sớm nhất với bạn!'
    return pplus_localize('Gửi hỗ trợ thành công, chúng tôi sẽ liên hệ sớm nhất với bạn!');
});

/** Luon noindex, ke ca khi site cho phep index (trang nay chi de do chuyen doi), va khong cho cache. */
add_filter('wp_robots', function (array $robots): array {
    if (get_query_var('pplus_form_success')) {
        return ['noindex' => true, 'nofollow' => true];
    }
    return $robots;
}, 99);

add_action('send_headers', function () {
    if (get_query_var('pplus_form_success')) {
        nocache_headers();
        header('X-Robots-Tag: noindex, nofollow', true);
    }
});

add_action('wp_enqueue_scripts', function () {
    if (!get_query_var('pplus_form_success')) {
        return;
    }
    wp_enqueue_style('pplus-form-success-component', PPLUS_THEME_URI . '/assets/css/form-success-component.css', [], pplus_asset_version('assets/css/form-success-component.css'));
}, 20);

add_action('init', function () {
    if (get_option('pplus_rewrite_flushed_form_success') !== '1') {
        flush_rewrite_rules();
        update_option('pplus_rewrite_flushed_form_success', '1');
    }
}, 20);

/**
 * URL cua trang cam on cho JS (pplusTrackFormSuccess). Rong khi GTM tat (noindex/server test/local) -> JS khong lam gi.
 */
function pplus_conversion_pixel_url(): string {
    if (!function_exists('pplus_gtm_enabled') || !pplus_gtm_enabled()) {
        return '';
    }
    // Chi lay PATH (khong host): luon cung origin voi trang dang xem (www/khong www, http/https) nen cookie GA/Ads cua iframe = cua trang.
    return (string) wp_parse_url(home_url('/Apartment/IssueLeaseApartmentSuccses'), PHP_URL_PATH);
}
