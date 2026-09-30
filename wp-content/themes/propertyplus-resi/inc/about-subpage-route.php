<?php
/**
 * Trang con /ve-chung-toi/{alias}-{id} (B5, port tu about-new.component). KHAC voi /ve-chung-toi
 * (khong alias) la 1 WordPress Page that (Template Name "Ve chung toi") - trang NAY la URL 2 doan
 * dong, KHONG khop 1 Page tinh nao, nen phai tu dang ky rewrite rule + query var +
 * template_include, dung 1 co che voi A2 (inc/office-detail-route.php) va B2/B3
 * (inc/blog-routes.php).
 *
 * Cac alias thuc te (chinh-sach-bao-mat-thong-tin, dieu-khoan-su-dung, chinh-sach-thue-van-phong)
 * lay tu $footer['myHistories'] (Home/FooterInvokeAsync, xem parts/footer.php) - link footer
 * "Chính sách" tro toi day.
 *
 * Vi du: /ve-chung-toi/chinh-sach-bao-mat-thong-tin-12 -> id=12, alias=chinh-sach-bao-mat-thong-tin
 *   (CO "-{id}" o cuoi, GIONG HET A2/B3 - bug thuc te da sua 14/9: doc lai truc tiep
 *   about-new.component.ts dong 26-30 xac nhan URL segment DUOC TACH thanh id/alias truoc khi goi
 *   API, docblock cu o day tung khang dinh SAI la "khong tach id" dan den pplus_get_about_subpage()
 *   chi gui "alias" nguyen ca "-{id}" ma khong bao gio gui "id" - dung lai
 *   pplus_parse_alias_id() da co san (inc/api-client.php, dung chung voi A2/B3) thay vi tu suy
 *   dien lai tu comment cu.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', function () {
    add_rewrite_rule('^ve-chung-toi/([^/]+)/?$', 'index.php?pplus_about_subpage=1&pplus_about_alias=$matches[1]', 'top');
    // SUA 30/9 (kiem tra da ngon ngu theo yeu cau nguoi dung: MOI trang con "Về chúng tôi" 404 tren
    // ban EN/JA): "about-us" la alias EN/JA THAT cua "ve-chung-toi" (xem inc/about-route.php - CHI
    // dang ky cho trang GOC "/about-us", chua bao gio dang ky cho dang "/about-us/{alias}-{id}") -
    // trong khi footer/header.php DA tu xay dung link theo dung slug nay (qua
    // pplus_localize('ve-chung-toi')), nen bam vao link Chính sách/menu con tren EN/JA la 404 100%,
    // KE CA khi "$matches[1]" hop le (khong lien quan bug alias rong da sua rieng trong footer.php).
    // "^en/ve-chung-toi/..." (dong cu ben duoi, DA XOA) tung la 1 rule CHET tu truoc gio: inc/
    // language-route.php da tu STRIP tien to "/en" khoi REQUEST_URI truoc khi WordPress khop rewrite
    // rule, nen KHONG BAO GIO co URL nao thuc su bat dau bang "en/..." di toi buoc nay ca.
    add_rewrite_rule('^about-us/([^/]+)/?$', 'index.php?pplus_about_subpage=1&pplus_about_alias=$matches[1]', 'top');
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
    // v5 -> v6 (30/9): bump lai de FLUSH THAT rule moi "^about-us/..." vao bang rewrite rules dang
    // song - key cu "v5" da tung chay/danh dau "xong" tu truoc voi bo rule THIEU rule nay.
    if (get_option('pplus_rewrite_flushed_v6') !== '1') {
        flush_rewrite_rules();
        update_option('pplus_rewrite_flushed_v6', '1');
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

    $alias_id = sanitize_text_field(get_query_var('pplus_about_alias'));
    if (!$alias_id) {
        return null;
    }
    $parsed = pplus_parse_alias_id($alias_id);
    $cached = pplus_get_about_subpage($parsed['id'], $parsed['alias']);
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
