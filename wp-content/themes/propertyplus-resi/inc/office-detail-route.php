<?php
/**
 * URL chi tiet van phong: /cho-thue-van-phong/{province}/{district}/{alias}-{id}
 * Day la URL 3 doan dong, KHONG khop voi 1 WordPress Page tinh (khac voi A1 - trang danh sach
 * la 1 Page + template). Phai tu dang ky rewrite rule + query var + template_include.
 *
 * Vi du: /cho-thue-van-phong/ha-noi/quan-hoan-kiem/cornerstone-building-142
 *   -> province=ha-noi, district=quan-hoan-kiem, alias-id=cornerstone-building-142
 *   -> id=142 (so cuoi cung sau dau "-"), alias=cornerstone-building (bo doan "-142")
 * Logic tach id/alias giong het Angular (detail.component.ts dong 102-103).
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', function () {
    add_rewrite_rule(
        '^cho-thue-van-phong/([^/]+)/([^/]+)/([^/]+)/?$',
        'index.php?pplus_office_detail=1&pplus_province=$matches[1]&pplus_district=$matches[2]&pplus_alias_id=$matches[3]',
        'top'
    );
    // "office-for-rent/..." la URL that cua production cho ban tieng Anh (KHAC han "cho-thue-van-phong"
    // dung cho ban tieng Viet) - xac nhan qua "urlHerf" API tra ve that su khi lang=en (vd "/en/office-
    // for-rent/ha-noi/hoan-kiem-district/63-ly-thai-to-building-465"). Da thu ep ve chung 1 tien to
    // "cho-thue-van-phong" (giong cach lam voi A3/A4) NHUNG khong duoc: da kiem chung truc tiep qua
    // API - Office/_GetDetail doi hoi DUNG BO province/district/alias theo tung ngon ngu (vd truyen
    // province/district/alias kieu Viet nhung lang=en se tra ve rong, KHONG chi loc theo id), nen phai
    // dang ky THEM 1 rewrite rule rieng khop dung URL that cua tieng Anh, dung chung template + logic
    // tach id/alias voi ban tieng Viet (khong can "do tham" nhu A4 vi URL da mang dung gia tri EN).
    add_rewrite_rule(
        '^office-for-rent/([^/]+)/([^/]+)/([^/]+)/?$',
        'index.php?pplus_office_detail=1&pplus_province=$matches[1]&pplus_district=$matches[2]&pplus_alias_id=$matches[3]',
        'top'
    );
});

add_filter('query_vars', function (array $vars): array {
    $vars[] = 'pplus_office_detail';
    $vars[] = 'pplus_province';
    $vars[] = 'pplus_district';
    $vars[] = 'pplus_alias_id';
    return $vars;
});

// Cung 1 loi da gap va sua o inc/blog-routes.php/inc/author-route.php/inc/reference-index-route.php:
// query var rieng khong duoc WP nhan dien nen WP_Query::parse_query() mac dinh coi day la trang
// chu (is_home=true) -> is_front_page() sai -> header.php gan nham class "headerHome" (nen trong
// suot cho anh hero trang chu) khien menu chu trang (rgba(255,255,255,0.8)) vo hinh tren nen
// trang cua cac trang con (xem ghi chu trong header.less dong 20-26).
add_action('parse_query', function (WP_Query $query) {
    if ($query->is_main_query() && get_query_var('pplus_office_detail')) {
        $query->is_home = false;
    }
});

// Flush 1 lan sau khi rewrite rule duoc dang ky (khong flush moi request - rat ton hieu nang).
add_action('init', function () {
    if (get_option('pplus_rewrite_flushed_v1') !== '1') {
        flush_rewrite_rules();
        update_option('pplus_rewrite_flushed_v1', '1');
    }
}, 20);

add_filter('template_include', function (string $template): string {
    if (get_query_var('pplus_office_detail')) {
        $custom = PPLUS_THEME_DIR . '/page-office-detail.php';
        if (file_exists($custom)) {
            return $custom;
        }
    }
    return $template;
});

// Title tag SEO cho trang chi tiet (dataSelected.metaTitle/metaDescription, giong Angular
// detail.component.ts dong 273-291). Goi lai pplus_get_office_detail() nhung da co cache
// transient trong pplus_api_get() nen khong ton them 1 request that.
add_filter('pre_get_document_title', function (string $title): string {
    if (!get_query_var('pplus_office_detail')) {
        return $title;
    }
    $model = pplus_get_office_detail_from_query_vars();
    if (!$model) {
        return $title;
    }
    return $model['dataSelected']['metaTitle'] ?? ($model['projectName'] ?? $title);
});

add_action('wp_head', function () {
    if (!get_query_var('pplus_office_detail')) {
        return;
    }
    $model = pplus_get_office_detail_from_query_vars();
    if (!$model) {
        return;
    }
    $description = $model['dataSelected']['metaDescription'] ?? $model['projectName'] ?? '';
    if ($description) {
        printf('<meta name="description" content="%s" />' . "\n", esc_attr($description));
    }
}, 1);

/**
 * Wrapper goi pplus_get_office_detail() dua tren query var hien tai - dung chung boi
 * title/meta filter va page-office-detail.php, tranh lap lai logic tach alias/id.
 */
function pplus_get_office_detail_from_query_vars(): ?array {
    $alias_id = sanitize_text_field(get_query_var('pplus_alias_id'));
    if (!$alias_id) {
        return null;
    }
    $parsed = pplus_parse_office_alias_id($alias_id);
    if ($parsed['id'] <= 0) {
        return null;
    }
    return pplus_get_office_detail(
        $parsed['id'],
        sanitize_text_field(get_query_var('pplus_province')),
        sanitize_text_field(get_query_var('pplus_district')),
        $parsed['alias']
    );
}

/**
 * Tach id + alias tu doan cuoi URL, giong het logic trong detail.component.ts (dong 102-103):
 * alias-id -> id la phan sau dau "-" cuoi cung, alias la phan con lai.
 *
 * @return array{id:int, alias:string}
 */
function pplus_parse_office_alias_id(string $alias_id): array {
    $parts = explode('-', $alias_id);
    $id    = (int) end($parts);
    $alias = preg_replace('/-' . preg_quote((string) $id, '/') . '$/', '', $alias_id);
    return ['id' => $id, 'alias' => $alias];
}
