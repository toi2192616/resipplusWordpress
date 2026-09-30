<?php
/**
 * A2 - Chi tiet 1 can ho: /cho-thue-can-ho/{province}/{district}/{project}/{id}/ (VI) hoac
 * /rental-apartment/{province}/{district}/{project}/{id}/ (EN/JA, tien to da bi strip boi
 * inc/language-route.php). 4 doan URL RIENG BIET (khac han A2 cua ban Van phong: alias-id GHEP
 * chung 1 doan bang dau "-") - xac nhan truc tiep tu app-routing.module.ts:
 *   'vi/cho-thue-can-ho/:province/:district/:project/:id' -> DetailComponent
 * va tu chinh URL that API tra ve (apartmentDetail.alasUrlHref): "cho-thue-can-ho/ha-noi/
 * quan-tay-ho/somerset-west-point-hanoi/3267" - 4 doan tach roi, id la doan CUOI CUNG rieng,
 * KHONG noi voi alias qua dau "-" nhu inc/office-detail-route.php (file do la ban COPY tu theme
 * Van phong, dung SAI dinh dang cho can ho - xem ghi chu file do, VAN GIU NGUYEN khong xoa vi
 * page-office-detail.php van dung rieng cho URL /cho-thue-van-phong/..., chi la KHONG lien quan
 * gi den route nay).
 *
 * Bug thuc te nguoi dung bao cao (29/9): "Ở trang trên menu click vào 1 sản phẩm không ra chi
 * tiết sản phẩm" - moi link the can ho (parts/apartment-card.php, mega-menu, trang danh sach)
 * deu tro toi dung URL 4 doan nay, nhung CHUA TUNG co rewrite rule/template nao dang ky cho no
 * (chi co inc/office-detail-route.php - SAI dinh dang, khong khop) -> 404 100% cac lan bam vao
 * 1 the can ho tren toan site. File nay la fix goc.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', function () {
    add_rewrite_rule(
        '^cho-thue-can-ho/([^/]+)/([^/]+)/([^/]+)/([^/]+)/?$',
        'index.php?pplus_apartment_detail=1&pplus_ap_province=$matches[1]&pplus_ap_district=$matches[2]&pplus_ap_project=$matches[3]&pplus_ap_id=$matches[4]',
        'top'
    );
    add_rewrite_rule(
        '^rental-apartment/([^/]+)/([^/]+)/([^/]+)/([^/]+)/?$',
        'index.php?pplus_apartment_detail=1&pplus_ap_province=$matches[1]&pplus_ap_district=$matches[2]&pplus_ap_project=$matches[3]&pplus_ap_id=$matches[4]',
        'top'
    );
});

add_filter('query_vars', function (array $vars): array {
    $vars[] = 'pplus_apartment_detail';
    $vars[] = 'pplus_ap_province';
    $vars[] = 'pplus_ap_district';
    $vars[] = 'pplus_ap_project';
    $vars[] = 'pplus_ap_id';
    return $vars;
});

// Cung 1 loi da gap va sua o MOI route file khac trong theme nay (blog/reference/about/office-
// detail...): query var rieng khong duoc WP nhan dien -> WP_Query::parse_query() coi day la trang
// chu (is_home=true) -> header.php gan nham class rong cho anh hero trang chu.
add_action('parse_query', function (WP_Query $query) {
    if ($query->is_main_query() && get_query_var('pplus_apartment_detail')) {
        $query->is_home = false;
    }
});

// Query var rieng khong gan voi post/page that -> WP_Query tra ve 0 post -> WP::handle_404() tu
// dat is_404=true du template van render noi dung that tu API .NET. Ep lai 200 tren
// 'template_redirect' (chay SAU handle_404 nen khong bi ghi de).
add_action('template_redirect', function () {
    if (get_query_var('pplus_apartment_detail')) {
        global $wp_query;
        $wp_query->is_404 = false;
        status_header(200);
    }
}, 0);

add_action('init', function () {
    if (get_option('pplus_rewrite_flushed_ap_detail_v1') !== '1') {
        flush_rewrite_rules();
        update_option('pplus_rewrite_flushed_ap_detail_v1', '1');
    }
}, 20);

add_filter('template_include', function (string $template): string {
    if (get_query_var('pplus_apartment_detail')) {
        $custom = PPLUS_THEME_DIR . '/page-apartment-detail.php';
        if (file_exists($custom)) {
            return $custom;
        }
    }
    return $template;
});

/**
 * Tach id o cuoi URL (so nguyen thuan tuy - KHAC office-detail-route.php: o day id la 1 DOAN
 * RIENG, khong noi voi alias, nen chi can (int) ep kieu truc tiep, khong can regex tach nhu
 * pplus_parse_office_alias_id()).
 */
function pplus_get_apartment_detail_from_query_vars(): ?array {
    static $cached = null;
    static $has_run = false;
    if ($has_run) {
        return $cached;
    }
    $has_run = true;

    $id = (int) get_query_var('pplus_ap_id');
    if ($id <= 0) {
        return null;
    }
    $province = sanitize_text_field(get_query_var('pplus_ap_province'));
    $district = sanitize_text_field(get_query_var('pplus_ap_district'));
    $project  = sanitize_text_field(get_query_var('pplus_ap_project'));

    $cached = pplus_get_apartment_detail($id, $province, $district, $project);
    return $cached;
}

add_filter('pre_get_document_title', function (string $title): string {
    if (!get_query_var('pplus_apartment_detail')) {
        return $title;
    }
    $model = pplus_get_apartment_detail_from_query_vars();
    $detail = $model['apartmentDetail'] ?? null;
    if (!$detail) {
        return $title;
    }
    return $model['dataSelected']['metaTitle'] ?? $detail['title'] ?? $title;
});

add_action('wp_head', function () {
    if (!get_query_var('pplus_apartment_detail')) {
        return;
    }
    $model = pplus_get_apartment_detail_from_query_vars();
    $detail = $model['apartmentDetail'] ?? null;
    if (!$detail) {
        return;
    }
    $description = $model['dataSelected']['metaDescription'] ?? $detail['title'] ?? '';
    if ($description) {
        printf('<meta name="description" content="%s" />' . "\n", esc_attr($description));
    }
}, 1);

/**
 * Doi ngon ngu (header.php, nut EN/VI/JA) - URL vi/en/ja cua CUNG 1 can ho KHAC HAN nhau (alasUrlBase
 * la duong dan EN/JA that, alasUrlBaseVi la duong dan VI that - ca 2 deu tra ve SAN tu chinh API,
 * khong can tu ghep/doan nhu A3/A4).
 */
add_filter('pplus_language_switch_url', function (string $default, string $target_lang) {
    if (!get_query_var('pplus_apartment_detail')) {
        return $default;
    }
    $model  = pplus_get_apartment_detail_from_query_vars();
    $detail = $model['apartmentDetail'] ?? null;
    if (!$detail) {
        return $default;
    }
    if ($target_lang === 'en' || $target_lang === 'ja') {
        $base = $detail['alasUrlBase'] ?? '';
        if ($base === '') {
            return $default;
        }
        $prefix = $target_lang === 'ja' ? '/ja' : '/en';
        return home_url($prefix . '/rental-apartment' . $base);
    }
    $base_vi = $detail['alasUrlBaseVi'] ?? '';
    if ($base_vi === '') {
        return $default;
    }
    return home_url('/cho-thue-can-ho' . $base_vi);
}, 10, 2);
