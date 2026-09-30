<?php
/**
 * A3/A4 - Landing theo tinh/quan/loai-can-ho (module Angular "reference-index"). 2 dang URL khac
 * nhau CUNG tro vao 1 template (xac nhan truc tiep qua app-routing.module.ts cua Resi - CA HAI
 * dung chung 1 goc "cho-thue-can-ho", KHONG co goc rieng rut gon nhu ban Van phong):
 *
 *   /cho-thue-can-ho/{province}           (2 doan, khong alias) -> goi Reference/GetIndexProvince
 *   /cho-thue-can-ho/{province}/{alias}   (3 doan)              -> goi Reference/GetIndex
 *
 * "alias" o dang 3 doan co the la quan/huyen (vd "quan-tay-ho") HOAC loai can ho (vd "studio",
 * "01-phong-ngu") - ca 2 deu di qua CUNG 1 API/template, chi khac field "loai" tra ve (xac nhan
 * qua API that: quan -> loai="1", loai can ho -> loai="9") - page-reference.php khong can biet
 * phan biet 2 loai nay, chi can dataSelected/filter la du de render.
 *
 * Khong dung Page tinh vi day la URL dong (menu/loai hinh phat sinh tu du lieu .NET). Rule 3-doan
 * CO loai tru rieng "page/{so}" (xem lookahead) de KHONG nuot mat URL phan trang - ke thua nguyen
 * bai hoc tu ban Van phong (cung 1 co che redirect_canonical() cua WP, cung 1 loai bug).
 *
 * Khong dang ky bien the "biet-thu-cho-thue" - san pham biet thu da bo khoi web Resi (quyet dinh
 * chien luoc 2026-09-13, xem front-page.php/parts/header.php).
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', function () {
    add_rewrite_rule(
        '^cho-thue-can-ho/([^/]+)/?$',
        'index.php?pplus_reference_index=1&pplus_ref_province=$matches[1]&pplus_ref_alias=',
        'top'
    );
    add_rewrite_rule(
        '^cho-thue-can-ho/(?!page/\d+/?$)([^/]+)/([^/]+)/?$',
        'index.php?pplus_reference_index=1&pplus_ref_province=$matches[1]&pplus_ref_alias=$matches[2]',
        'top'
    );
    // "/cho-thue-can-ho/{province}/{alias}/page/{so}/" - dang 4 doan WP tu sinh khi
    // redirect_canonical() chuan hoa "?paged=2" thanh duong dan dep (giong het co che da gap va
    // sua ben ban Van phong voi "cho-thue-van-phong").
    add_rewrite_rule(
        '^cho-thue-can-ho/([^/]+)/([^/]+)/page/(\d+)/?$',
        'index.php?pplus_reference_index=1&pplus_ref_province=$matches[1]&pplus_ref_alias=$matches[2]&paged=$matches[3]',
        'top'
    );

    // Dang URL EN that (xac nhan qua app-routing.module.ts cua Resi: 'en/rental-apartment/:province'
    // va 'en/rental-apartment/:province/:alias' - KHAC ban Van phong dung "office-for-rent"). Alias
    // trong URL EN cung KHAC ban VI (lay tu field "aliasEn" cua API, xem pplus_get_reference_index())
    // nen dat "pplus_ref_alias_lang=en" de ham do goi thang en/Reference/GetIndex, khong do tham qua vi.
    add_rewrite_rule(
        '^rental-apartment/(?!page/\d+/?$)([^/]+)/?$',
        'index.php?pplus_reference_index=1&pplus_ref_province=$matches[1]&pplus_ref_alias=&pplus_ref_alias_lang=en',
        'top'
    );
    add_rewrite_rule(
        '^rental-apartment/(?!page/\d+/?$)([^/]+)/([^/]+)/?$',
        'index.php?pplus_reference_index=1&pplus_ref_province=$matches[1]&pplus_ref_alias=$matches[2]&pplus_ref_alias_lang=en',
        'top'
    );
    add_rewrite_rule(
        '^rental-apartment/([^/]+)/([^/]+)/page/(\d+)/?$',
        'index.php?pplus_reference_index=1&pplus_ref_province=$matches[1]&pplus_ref_alias=$matches[2]&pplus_ref_alias_lang=en&paged=$matches[3]',
        'top'
    );
});

add_filter('query_vars', function (array $vars): array {
    $vars[] = 'pplus_reference_index';
    $vars[] = 'pplus_ref_province';
    $vars[] = 'pplus_ref_alias';
    $vars[] = 'pplus_ref_alias_lang';
    return $vars;
});

// Query var rieng KHONG duoc WP nhan dien la bat ky loai noi dung nao nen WP_Query::parse_query()
// mac dinh coi day la trang chu (is_home=true) - lam is_front_page() sai. Cung 1 loi da gap va sua
// o cac route file khac (inc/blog-routes.php/inc/author-route.php), sua tai goc bang parse_query.
add_action('parse_query', function (WP_Query $query) {
    if ($query->is_main_query() && get_query_var('pplus_reference_index')) {
        $query->is_home = false;
    }
});

// Query var rieng khong gan voi post/page that -> WP_Query chinh tra ve 0 post -> WP::handle_404()
// tu dat is_404=true that su (du template van render noi dung that tu API .NET). Ep lai 200 tren
// 'template_redirect' (chay SAU handle_404 nen khong bi ghi de) - ke thua bai hoc tu ban Van phong.
add_action('template_redirect', function () {
    if (get_query_var('pplus_reference_index')) {
        global $wp_query;
        $wp_query->is_404 = false;
        status_header(200);
    }
}, 0);

add_action('init', function () {
    if (get_option('pplus_rewrite_flushed_resi_ref_v1') !== '1') {
        flush_rewrite_rules();
        update_option('pplus_rewrite_flushed_resi_ref_v1', '1');
    }
}, 20);

add_filter('template_include', function (string $template): string {
    if (get_query_var('pplus_reference_index')) {
        $custom = PPLUS_THEME_DIR . '/page-reference.php';
        if (file_exists($custom)) {
            return $custom;
        }
    }
    return $template;
});

add_filter('pre_get_document_title', function (string $title): string {
    if (!get_query_var('pplus_reference_index')) {
        return $title;
    }
    $model = pplus_get_reference_index_from_query_vars();
    if (!$model || empty($model['dataSelected'])) {
        return $title;
    }
    return $model['dataSelected']['metaTitle'] ?? $model['dataSelected']['title'] ?? $title;
});

add_action('wp_head', function () {
    if (!get_query_var('pplus_reference_index')) {
        return;
    }
    $model = pplus_get_reference_index_from_query_vars();
    if (!$model || empty($model['dataSelected'])) {
        return;
    }
    $data = $model['dataSelected'];

    $description = $data['metaDescription'] ?? ($data['description'] ?? '');
    if ($description) {
        printf('<meta name="description" content="%s" />' . "\n", esc_attr($description));
    }

    $robots = ($data['isIndex'] === false) ? 'noindex, nofollow' : 'index, follow';
    printf('<meta name="robots" content="%s" />' . "\n", esc_attr($robots));
}, 1);

/**
 * Doi ngon ngu tren A3/A4 (nut chuyen VI/EN/JA o header + hreflang, xem inc/language-route.php).
 * Resi co 3 ngon ngu (vi/en/ja, khac ban Van phong chi vi/en) - alias JA CHUA xac nhan duoc rieng
 * (khong thay field "aliasJa" trong dataSelected khi kiem tra API that, chi co aliasVi/aliasEn),
 * nen ban JA tam dung chung alias EN (an toan hon la doan mot dang URL JA chua kiem chung, giong
 * cach ham pplus_t() cua theme nay cung khong co tham so rieng cho JA).
 */
add_filter('pplus_language_switch_url', function (string $default, string $target_lang) {
    if (!get_query_var('pplus_reference_index')) {
        return $default;
    }
    $province = sanitize_text_field(get_query_var('pplus_ref_province'));
    $alias    = sanitize_text_field(get_query_var('pplus_ref_alias'));
    if ($province === '' || $alias === '') {
        return $default;
    }

    $model    = pplus_get_reference_index_from_query_vars();
    $data     = $model['dataSelected'] ?? [];
    $alias_vi = (string) ($data['aliasVi'] ?? '');
    $alias_en = (string) ($data['aliasEn'] ?? '');

    if ($target_lang === 'en' || $target_lang === 'ja') {
        if ($alias_en === '') {
            return $default;
        }
        $prefix = $target_lang === 'ja' ? '/ja' : '/en';
        return home_url($prefix . '/rental-apartment/' . $province . '/' . $alias_en . '/');
    }

    $target_alias = $alias_vi !== '' ? $alias_vi : $alias;
    return home_url('/cho-thue-can-ho/' . $province . '/' . $target_alias . '/');
}, 10, 2);

/**
 * Wrapper doc query var hien tai va goi pplus_get_reference_index() - dung chung boi
 * title/meta filter va page-reference.php, static-cache trong 1 request.
 */
function pplus_get_reference_index_from_query_vars(): ?array {
    static $cached = null;
    static $has_run = false;
    if ($has_run) {
        return $cached;
    }
    $has_run = true;

    $province   = sanitize_text_field(get_query_var('pplus_ref_province'));
    $alias      = sanitize_text_field(get_query_var('pplus_ref_alias'));
    $alias_lang = get_query_var('pplus_ref_alias_lang') === 'en' ? 'en' : 'vi';
    if (!$province) {
        return null;
    }

    $cached = pplus_get_reference_index($province, $alias, $alias_lang);
    return $cached;
}
