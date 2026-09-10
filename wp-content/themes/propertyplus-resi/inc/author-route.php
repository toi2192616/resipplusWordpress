<?php
/**
 * B4 - Trang tac gia /author/{alias} (module Angular "detai-tacgia"). URL 2 doan don gian,
 * khong co id o cuoi (khac B3) - dung y het routerLink trong detai-tacgia.component.ts/html.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', function () {
    add_rewrite_rule('^author/([^/]+)/?$', 'index.php?pplus_author=1&pplus_author_alias=$matches[1]', 'top');
    add_rewrite_rule('^en/author/([^/]+)/?$', 'index.php?pplus_author=1&pplus_author_alias=$matches[1]', 'top');
});

add_filter('query_vars', function (array $vars): array {
    $vars[] = 'pplus_author';
    $vars[] = 'pplus_author_alias';
    return $vars;
});

// Cung 1 loi is_home()/is_front_page() sai nhu inc/blog-routes.php - xem ghi chu chi tiet o do.
add_action('parse_query', function (WP_Query $query) {
    if ($query->is_main_query() && get_query_var('pplus_author')) {
        $query->is_home = false;
    }
});

add_action('init', function () {
    if (get_option('pplus_rewrite_flushed_v4') !== '1') {
        flush_rewrite_rules();
        update_option('pplus_rewrite_flushed_v4', '1');
    }
}, 20);

add_filter('template_include', function (string $template): string {
    if (get_query_var('pplus_author')) {
        $custom = PPLUS_THEME_DIR . '/page-author.php';
        if (file_exists($custom)) {
            return $custom;
        }
    }
    return $template;
});

function pplus_get_author_from_query_vars(): ?array {
    static $cached = null;
    static $has_run = false;
    if ($has_run) {
        return $cached;
    }
    $has_run = true;

    $alias = sanitize_text_field(get_query_var('pplus_author_alias'));
    if (!$alias) {
        return null;
    }
    $cached = pplus_get_blog_author($alias);
    return $cached;
}

add_filter('pre_get_document_title', function (string $title): string {
    if (!get_query_var('pplus_author')) {
        return $title;
    }
    $model = pplus_get_author_from_query_vars();
    $data  = $model['dataModel'] ?? null;
    if (!$data) {
        return $title;
    }
    return $data['metaTitle'] ?? ($data['title'] ?? $title);
});

add_action('wp_head', function () {
    if (!get_query_var('pplus_author')) {
        return;
    }
    $model = pplus_get_author_from_query_vars();
    $data  = $model['dataModel'] ?? null;
    if (!$data) {
        return;
    }
    $description = $data['metaDescription'] ?? $data['description'] ?? '';
    if ($description) {
        printf('<meta name="description" content="%s" />' . "\n", esc_attr(wp_strip_all_tags($description)));
    }
}, 1);
