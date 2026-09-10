<?php
/**
 * Danh sach yeu thich (port module Angular "favourite"). URL:
 *   /office-favourite        (vi)
 *   /en/office-favourite     (en, tien to /en da duoc inc/language-route.php strip tu truoc)
 *
 * KHAC voi cac trang khac: du lieu bo suu tap yeu thich luu HOAN TOAN o localStorage cua trinh
 * duyet (giong Angular - khong can login/backend). Trang nay chi la "vo": server render layout +
 * modal, con JS (assets/js/pplus-favourite.js) doc localStorage -> goi REST proxy
 * (inc/favourite-rest.php -> Office/_GetOfficesInputDto) render luoi van phong + map client-side.
 *
 * LUON noindex, nofollow (giong favourite.component.ts dong ngOnInit: meta robots noindex vo dieu kien).
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', function () {
    add_rewrite_rule('^office-favourite/?$', 'index.php?pplus_favourite=1', 'top');
    add_rewrite_rule('^en/office-favourite/?$', 'index.php?pplus_favourite=1', 'top');
});

add_filter('query_vars', function (array $vars): array {
    $vars[] = 'pplus_favourite';
    return $vars;
});

// Query var rieng KHONG duoc WP nhan dien nen WP_Query mac dinh coi la trang chu (is_home) - tat di
// (cung 1 xu ly nhu inc/street-list-route.php).
add_action('parse_query', function (WP_Query $query) {
    if ($query->is_main_query() && get_query_var('pplus_favourite')) {
        $query->is_home = false;
    }
});

// Bump version de flush lai rewrite khi them route moi nay (local). Tren SERVER phai vao
// Settings > Permalinks > Save 1 lan sau khi deploy de dang ky rule nay.
add_action('init', function () {
    if (get_option('pplus_rewrite_flushed_v4') !== '1') {
        flush_rewrite_rules();
        update_option('pplus_rewrite_flushed_v4', '1');
    }
}, 20);

add_filter('template_include', function (string $template): string {
    if (get_query_var('pplus_favourite')) {
        $custom = PPLUS_THEME_DIR . '/page-favourite.php';
        if (file_exists($custom)) {
            return $custom;
        }
    }
    return $template;
});

add_filter('pre_get_document_title', function (string $title): string {
    if (!get_query_var('pplus_favourite')) {
        return $title;
    }
    return pplus_t('Danh sách yêu thích', 'Favourite list') . ' - PropertyPlus';
});

add_action('wp_head', function () {
    if (!get_query_var('pplus_favourite')) {
        return;
    }
    echo '<meta name="robots" content="noindex, nofollow" />' . "\n";
}, 1);
