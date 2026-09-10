<?php
/**
 * A5 - Danh sach duong theo tinh (module Angular "list-street"). URL dong 1 doan:
 *   /street/{province-alias}   vd /street/ha-noi
 *
 * Khong dung Page tinh vi day la du lieu dong tu API, khong phai bai viet content co dinh
 * nhu A3. Trang nay LUON noindex (giong y het list-street.component.ts dong 34 - goi
 * meta.updateTag robots=noindex,nofollow vo dieu kien, khong phu thuoc du lieu API).
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', function () {
    add_rewrite_rule(
        '^street/([^/]+)/?$',
        'index.php?pplus_street_list=1&pplus_street_province=$matches[1]',
        'top'
    );
});

add_filter('query_vars', function (array $vars): array {
    $vars[] = 'pplus_street_list';
    $vars[] = 'pplus_street_province';
    return $vars;
});

// Query var rieng KHONG duoc WP nhan dien la bat ky loai noi dung nao nen WP_Query::parse_query()
// mac dinh coi day la trang chu (is_home=true) - lam is_front_page() sai. Cung 1 loi da gap va
// sua o inc/blog-routes.php/inc/author-route.php/inc/reference-index-route.php.
add_action('parse_query', function (WP_Query $query) {
    if ($query->is_main_query() && get_query_var('pplus_street_list')) {
        $query->is_home = false;
    }
});

add_action('init', function () {
    if (get_option('pplus_rewrite_flushed_v3') !== '1') {
        flush_rewrite_rules();
        update_option('pplus_rewrite_flushed_v3', '1');
    }
}, 20);

add_filter('template_include', function (string $template): string {
    if (get_query_var('pplus_street_list')) {
        $custom = PPLUS_THEME_DIR . '/page-street.php';
        if (file_exists($custom)) {
            return $custom;
        }
    }
    return $template;
});

add_filter('pre_get_document_title', function (string $title): string {
    if (!get_query_var('pplus_street_list')) {
        return $title;
    }
    $model = pplus_get_street_list_from_query_vars();
    if (!$model || empty($model['menuData'])) {
        return $title;
    }
    return 'Tìm văn phòng theo đường tại ' . ($model['menuData']['subTitle'] ?? '') . ' - PropertyPlus';
});

// Robots luon noindex tren trang nay (giong Angular that, khong dieu kien theo du lieu).
add_action('wp_head', function () {
    if (!get_query_var('pplus_street_list')) {
        return;
    }
    echo '<meta name="robots" content="noindex, nofollow" />' . "\n";
}, 1);

function pplus_get_street_list_from_query_vars(): ?array {
    static $cached = null;
    static $has_run = false;
    if ($has_run) {
        return $cached;
    }
    $has_run = true;

    $province = sanitize_text_field(get_query_var('pplus_street_province'));
    if (!$province) {
        return null;
    }

    $cached = pplus_get_province_streets($province);
    return $cached;
}
