<?php
/**
 * Trang con /ve-chung-toi/{alias} (B5, port tu about-new.component - xem TODO trong
 * page-about.php dong 17-18). KHAC voi /ve-chung-toi (khong alias) la 1 WordPress Page that
 * (Template Name "Ve chung toi") - trang NAY la URL 2 doan dong, KHONG khop 1 Page tinh nao,
 * nen phai tu dang ky rewrite rule + query var + template_include, dung 1 co che voi A2
 * (inc/office-detail-route.php) va B2/B3 (inc/blog-routes.php).
 *
 * Cac alias thuc te (chinh-sach-bao-mat-thong-tin, dieu-khoan-su-dung, chinh-sach-thue-van-phong)
 * lay tu $footer['myHistories'] (Home/FooterInvokeAsync, xem parts/footer.php) - link footer
 * "Chính sách" tro toi day, truoc day 404 vi trang con nay chua ton tai.
 *
 * Vi du: /ve-chung-toi/chinh-sach-bao-mat-thong-tin -> alias=chinh-sach-bao-mat-thong-tin
 *   (KHONG co "-{id}" o cuoi nhu A2/B3, xem about-new.component.ts: chi doc params.alias truc
 *   tiep, khong tach id).
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', function () {
    add_rewrite_rule('^ve-chung-toi/([^/]+)/?$', 'index.php?pplus_about_subpage=1&pplus_about_alias=$matches[1]', 'top');
    add_rewrite_rule('^en/ve-chung-toi/([^/]+)/?$', 'index.php?pplus_about_subpage=1&pplus_about_alias=$matches[1]', 'top');
});

add_filter('query_vars', function (array $vars): array {
    $vars[] = 'pplus_about_subpage';
    $vars[] = 'pplus_about_alias';
    return $vars;
});

// Cung 1 loi da gap va sua o inc/blog-routes.php/inc/author-route.php/inc/reference-index-route.php/
// inc/office-detail-route.php: query var rieng khong duoc WP nhan dien nen WP_Query::parse_query()
// mac dinh coi day la trang chu (is_home=true) -> is_front_page() sai -> header.php gan nham class
// "headerHome" (nen trong suot cho anh hero trang chu) khien menu chu trang vo hinh tren nen trang
// cua trang con.
add_action('parse_query', function (WP_Query $query) {
    if ($query->is_main_query() && get_query_var('pplus_about_subpage')) {
        $query->is_home = false;
    }
});

add_action('init', function () {
    if (get_option('pplus_rewrite_flushed_v5') !== '1') {
        flush_rewrite_rules();
        update_option('pplus_rewrite_flushed_v5', '1');
    }
}, 20);

add_filter('template_include', function (string $template): string {
    if (get_query_var('pplus_about_subpage')) {
        $custom = PPLUS_THEME_DIR . '/page-about-subpage.php';
        if (file_exists($custom)) {
            return $custom;
        }
    }
    return $template;
});

/**
 * Wrapper doc query var hien tai va goi pplus_get_about_subpage() - dung chung boi title/meta
 * filter va page-about-subpage.php, static-cache trong 1 request (giong pattern A2/B3).
 */
function pplus_get_about_subpage_from_query_vars(): ?array {
    static $cached = null;
    static $has_run = false;
    if ($has_run) {
        return $cached;
    }
    $has_run = true;

    $alias = sanitize_text_field(get_query_var('pplus_about_alias'));
    if (!$alias) {
        return null;
    }
    $cached = pplus_get_about_subpage($alias);
    return $cached;
}

add_filter('pre_get_document_title', function (string $title): string {
    if (!get_query_var('pplus_about_subpage')) {
        return $title;
    }
    $data = pplus_get_about_subpage_from_query_vars();
    if (!$data) {
        return $title;
    }
    return ($data['metaTitle'] ?? '') ?: ($data['title'] ?? $title);
});

add_action('wp_head', function () {
    if (!get_query_var('pplus_about_subpage')) {
        return;
    }
    $data = pplus_get_about_subpage_from_query_vars();
    if (!$data) {
        return;
    }
    $description = $data['metaDescription'] ?? '';
    if ($description) {
        printf('<meta name="description" content="%s" />' . "\n", esc_attr($description));
    }
    $robots = ($data['isIndex'] === false) ? 'noindex, nofollow' : 'index, follow';
    printf('<meta name="robots" content="%s" />' . "\n", esc_attr($robots));
}, 1);
