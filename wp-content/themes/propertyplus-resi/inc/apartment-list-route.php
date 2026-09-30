<?php
/**
 * A1 - Danh sach can ho TONG QUAT (khong loc theo tinh/quan): /cho-thue-can-ho/ (VI) hoac
 * /rental-apartment/ (EN/JA, tien to da bi strip boi inc/language-route.php). Doi chieu
 * app-routing.module.ts: 'vi/cho-thue-can-ho' -> OfficeIndexComponent (component nay CHI wrap
 * <app-search-base isMapshow=true isShowUrlMenu=true>, khong co logic rieng - xem
 * office-index.component.ts/.html cua Resi).
 *
 * Day la dich cua nut "Tim kiem" o form tim kiem trang chu (front-page.php: <form action=
 * "{lang_prefix}/cho-thue-can-ho/">) - submitSearchHome() ben Angular that CUNG dieu huong ve
 * dung URL nay (khong co province). Bug thuc te nguoi dung bao cao (29/9): "Nhấn nút tìm kiếm ở
 * destop không tìm được sản phẩm" - URL nay CHUA TUNG duoc dang ky (chi co A3/A4, yeu cau BAT
 * BUOC co province trong URL, xem inc/reference-index-route.php) nen luon 404. File nay la fix goc.
 *
 * KHONG dung Page tinh (giong A3/A4) - URL dong, dang ky qua add_rewrite_rule() nhu moi route
 * khac trong theme nay. Trang nay KHONG loc theo o dia ly/tu khoa nhu ban Angular goc THAT SU co
 * the lam (submitSearchHome() gui kem searchHome.filter/province/... qua queryParams, nhung API
 * Apartment/_GetApartments KHONG co field free-text - xem TODO trong page-apartment-list.php)
 * - chap nhan pham vi "SEO co ban truoc" nhu A3/A4/A1-Van-phong da lam, CHI ap dung cac field
 * loc CO CAU TRUC (gia/phong ngu/phong tam/dien tich/loai can ho) tu $_GET, giong het cach
 * page-reference.php dang lam.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', function () {
    add_rewrite_rule('^cho-thue-can-ho/?$', 'index.php?pplus_apartment_list=1', 'top');
    add_rewrite_rule('^cho-thue-can-ho/page/(\d+)/?$', 'index.php?pplus_apartment_list=1&paged=$matches[1]', 'top');
    add_rewrite_rule('^rental-apartment/?$', 'index.php?pplus_apartment_list=1', 'top');
    add_rewrite_rule('^rental-apartment/page/(\d+)/?$', 'index.php?pplus_apartment_list=1&paged=$matches[1]', 'top');
});

add_filter('query_vars', function (array $vars): array {
    $vars[] = 'pplus_apartment_list';
    return $vars;
});

add_action('parse_query', function (WP_Query $query) {
    if ($query->is_main_query() && get_query_var('pplus_apartment_list')) {
        $query->is_home = false;
    }
});

add_action('template_redirect', function () {
    if (get_query_var('pplus_apartment_list')) {
        global $wp_query;
        $wp_query->is_404 = false;
        status_header(200);
    }
}, 0);

add_action('init', function () {
    if (get_option('pplus_rewrite_flushed_ap_list_v1') !== '1') {
        flush_rewrite_rules();
        update_option('pplus_rewrite_flushed_ap_list_v1', '1');
    }
}, 20);

add_filter('template_include', function (string $template): string {
    if (get_query_var('pplus_apartment_list')) {
        $custom = PPLUS_THEME_DIR . '/page-apartment-list.php';
        if (file_exists($custom)) {
            return $custom;
        }
    }
    return $template;
});

add_filter('pre_get_document_title', function (string $title): string {
    if (!get_query_var('pplus_apartment_list')) {
        return $title;
    }
    return pplus_localize('Tìm căn hộ cho thuê') . ' | Property Plus';
});

/**
 * Doi ngon ngu (header.php) - trang nay khong co alias/id rieng, chi doi tien to.
 */
add_filter('pplus_language_switch_url', function (string $default, string $target_lang) {
    if (!get_query_var('pplus_apartment_list')) {
        return $default;
    }
    if ($target_lang === 'en' || $target_lang === 'ja') {
        $prefix = $target_lang === 'ja' ? '/ja' : '/en';
        return home_url($prefix . '/rental-apartment/');
    }
    return home_url('/cho-thue-can-ho/');
}, 10, 2);
