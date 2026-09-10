<?php
/**
 * B2 + B3 - /blogs (danh sach) va /blogs/{alias}-{id} (chi tiet). Dung cung 1 co che voi
 * A2 (inc/office-detail-route.php): tu dang ky rewrite rule + query var + template_include,
 * KHONG dung CPT 'news' (inc/cpt-news.php, slug rieng "tin-tuc") - CPT do khong duoc lien ket
 * toi tu bat cu dau trong theme (header.php, front-page.php, sitemap_posts.xml deu tro thang
 * ve /blogs/{alias}-{id}), nen khong dung lam URL cong khai o day de tranh 2 nguon su that.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', function () {
    // /blogs/{alias}-{id} phai dang ky TRUOC /blogs de khop dung (2 rule khong de doi trung
    // pattern vi ([^/]+) bat buoc it nhat 1 ky tu, nhung dat chi tiet truoc cho ro rang).
    add_rewrite_rule('^blogs/([^/]+)/?$', 'index.php?pplus_blog_detail=1&pplus_alias_id=$matches[1]', 'top');
    add_rewrite_rule('^blogs/?$', 'index.php?pplus_blog_list=1', 'top');
    add_rewrite_rule('^en/blogs/([^/]+)/?$', 'index.php?pplus_blog_detail=1&pplus_alias_id=$matches[1]', 'top');
    add_rewrite_rule('^en/blogs/?$', 'index.php?pplus_blog_list=1', 'top');
});

add_filter('query_vars', function (array $vars): array {
    $vars[] = 'pplus_blog_list';
    $vars[] = 'pplus_blog_detail';
    $vars[] = 'pplus_alias_id';
    return $vars;
});

/**
 * Query var tu (pplus_blog_list/pplus_blog_detail) KHONG duoc WordPress nhan dien la bat ky
 * loai noi dung nao (post/page/archive...), nen WP_Query::parse_query() mac dinh coi day la
 * trang chu (is_home=true vi show_on_front='posts') - lam is_front_page() sai, keo theo
 * parts/header.php gan nham class "headerHome" (an anh nen + doi mau chu sang trang, chi dung
 * cho trang chu that) thay vi ":not(.headerHome)" (co anh nen, dung cho trang con) - trieu chung
 * la ca menu header bien mat (chu trang tren nen trang). Sua tai goc bang parse_query.
 */
add_action('parse_query', function (WP_Query $query) {
    if ($query->is_main_query() && (get_query_var('pplus_blog_list') || get_query_var('pplus_blog_detail'))) {
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
    if (get_query_var('pplus_blog_list')) {
        $custom = PPLUS_THEME_DIR . '/archive-news.php';
        if (file_exists($custom)) {
            return $custom;
        }
    }
    if (get_query_var('pplus_blog_detail')) {
        $custom = PPLUS_THEME_DIR . '/single-news.php';
        if (file_exists($custom)) {
            return $custom;
        }
    }
    return $template;
});

add_filter('pre_get_document_title', function (string $title): string {
    if (!get_query_var('pplus_blog_list')) {
        return $title;
    }
    return pplus_t('Tin tức', 'Blog') . ' - PropertyPlus';
});

add_action('wp_head', function () {
    if (!get_query_var('pplus_blog_list')) {
        return;
    }
    printf('<meta name="robots" content="%s" />' . "\n", esc_attr('index, follow'));
}, 1);

/**
 * Wrapper doc query var hien tai va goi pplus_get_blog_detail() - dung chung boi title/meta
 * filter va single-news.php, static-cache trong 1 request (giong pattern A2/A4).
 */
function pplus_get_blog_detail_from_query_vars(): ?array {
    static $cached = null;
    static $has_run = false;
    if ($has_run) {
        return $cached;
    }
    $has_run = true;

    $alias_id = sanitize_text_field(get_query_var('pplus_alias_id'));
    if (!$alias_id) {
        return null;
    }
    $parsed = pplus_parse_alias_id($alias_id);
    if ($parsed['id'] <= 0) {
        return null;
    }
    $cached = pplus_get_blog_detail($parsed['id'], $parsed['alias']);
    return $cached;
}

add_filter('pre_get_document_title', function (string $title): string {
    if (!get_query_var('pplus_blog_detail')) {
        return $title;
    }
    $data = pplus_get_blog_detail_from_query_vars();
    $selected = $data['result']['dataSelected'] ?? null;
    if (!$selected) {
        return $title;
    }
    return $selected['metaTitle'] ?? ($selected['title'] ?? $title);
});

add_action('wp_head', function () {
    if (!get_query_var('pplus_blog_detail')) {
        return;
    }
    $data = $data_model = pplus_get_blog_detail_from_query_vars();
    $selected = $data['result']['dataSelected'] ?? null;
    if (!$selected) {
        return;
    }
    $description = $selected['metaDescription'] ?? $selected['subTitle'] ?? '';
    if ($description) {
        printf('<meta name="description" content="%s" />' . "\n", esc_attr($description));
    }
    $robots = ($selected['isIndex'] === false) ? 'noindex, nofollow' : 'index, follow';
    printf('<meta name="robots" content="%s" />' . "\n", esc_attr($robots));
}, 1);
