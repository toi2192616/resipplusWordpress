<?php
/**
 * C1 - Trang "Dich vu" (4 URL co dinh, KHONG PHAI 1 alias tu do nhu A3/A4). Xac nhan qua doc day du
 * app-routing.module.ts (Resi that): ca 4 URL deu tro vao CUNG 1 component (ListServiceComponent),
 * phan biet nhau chi boi 1 gia tri tinh "TypeSite" (1-4) gan san trong route data - KHONG co goi API
 * nao de "giai ma" alias nhu Reference/GetIndex ben trang quan/loai-can-ho.
 *
 *   TypeSite 1: dich-vu-quan-ly-cua-property-plus-1  (EN: the-management-of-property-plus-1)
 *   TypeSite 2: dich-vu-don-phong-2                  (EN: housekeeping-service-2)
 *   TypeSite 3: dich-vu-ky-thuat-bao-duong-3          (EN: technical-maintenance-service-3)
 *   TypeSite 4: dang-ky-dich-vu-4                     (EN: register-service-4)
 *
 * JA dung LAI alias EN (xac nhan qua chinh app-routing.module.ts - ca 2 khoi route en/ja co cung
 * chuoi alias). Tien to /en, /ja da duoc inc/language-route.php go khoi URL va luu vao PPLUS_LANG
 * TRUOC KHI rewrite rule nay chay (giong moi route khac trong theme), nen 1 rewrite rule DUY NHAT
 * ("service/{alias}") dung cho ca 3 ngon ngu - KHAC voi inc/reference-index-route.php phai dang ky
 * THEM 1 rule rieng cho EN vi goc URL do ("rental-apartment") khac han goc VI ("cho-thue-can-ho");
 * o day goc "service" giong het nhau giua 3 ngon ngu, chi phan alias sau no khac.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 4 alias goc (chua ghep hau to "-N" va chua dich) - dung chung boi ham resolve alias->TypeSite
 * duoi day VA parts/header.php (menu "Dich vu") de tranh 2 noi lech nhau. Thu tu = TypeSite.
 */
function pplus_service_type_bases(): array {
    return [
        1 => 'dich-vu-quan-ly-cua-property-plus',
        2 => 'dich-vu-don-phong',
        3 => 'dich-vu-ky-thuat-bao-duong',
        4 => 'dang-ky-dich-vu',
    ];
}

/** Tieu de tieng Viet cua tung TypeSite - dung cho <title>/breadcrumb, khop dung text that. */
function pplus_service_type_titles(): array {
    return [
        1 => 'Dịch vụ Quản lý của Property Plus',
        2 => 'Dịch vụ dọn phòng',
        3 => 'Dịch vụ kỹ thuật - bảo dưỡng',
        4 => 'Đăng ký dịch vụ',
    ];
}

add_action('init', function () {
    add_rewrite_rule(
        '^service/([^/]+)/?$',
        'index.php?pplus_service=1&pplus_service_alias=$matches[1]',
        'top'
    );
});

add_filter('query_vars', function (array $vars): array {
    $vars[] = 'pplus_service';
    $vars[] = 'pplus_service_alias';
    return $vars;
});

// Cung 1 bug/co che da gap o reference-index-route.php: query var rieng khong duoc WP nhan dien
// khien parse_query() mac dinh coi day la trang chu.
add_action('parse_query', function (WP_Query $query) {
    if ($query->is_main_query() && get_query_var('pplus_service')) {
        $query->is_home = false;
    }
});

add_action('template_redirect', function () {
    if (get_query_var('pplus_service')) {
        global $wp_query;
        $wp_query->is_404 = false;
        status_header(200);
    }
}, 0);

add_action('init', function () {
    if (get_option('pplus_rewrite_flushed_resi_service_v1') !== '1') {
        flush_rewrite_rules();
        update_option('pplus_rewrite_flushed_resi_service_v1', '1');
    }
}, 20);

add_filter('template_include', function (string $template): string {
    if (get_query_var('pplus_service')) {
        $custom = PPLUS_THEME_DIR . '/page-service.php';
        if (file_exists($custom)) {
            return $custom;
        }
    }
    return $template;
});

/**
 * Doi chieu 1 alias tu URL sang TypeSite (1-4), theo dung ngon ngu HIEN TAI (PPLUS_LANG) - so khop
 * "pplus_localize($base) . '-' . $n" (CHINH XAC cach parts/header.php dang tu xay link) voi alias
 * nhan duoc. Tra ve 0 neu khong khop muc nao (404).
 */
function pplus_resolve_service_type(string $alias): int {
    foreach (pplus_service_type_bases() as $type => $base) {
        if ($alias === pplus_localize($base) . '-' . $type) {
            return $type;
        }
    }
    return 0;
}

/**
 * Doc query var hien tai, doi chieu ra TypeSite - dung chung boi page-service.php va cac filter
 * title/meta duoi day. Static-cache trong 1 request (cung quy uoc voi
 * pplus_get_reference_index_from_query_vars()).
 *
 * @return array{type_site:int, alias:string}|null null = khong khop TypeSite nao (404).
 */
function pplus_get_service_from_query_vars(): ?array {
    static $cached  = null;
    static $has_run = false;
    if ($has_run) {
        return $cached;
    }
    $has_run = true;

    $alias = sanitize_text_field((string) get_query_var('pplus_service_alias'));
    if ($alias === '') {
        return null;
    }
    $type_site = pplus_resolve_service_type($alias);
    if ($type_site === 0) {
        return null;
    }

    $cached = ['type_site' => $type_site, 'alias' => $alias];
    return $cached;
}

add_filter('pre_get_document_title', function (string $title): string {
    $model = pplus_get_service_from_query_vars();
    if (!$model) {
        return $title;
    }
    $titles = pplus_service_type_titles();
    $vi_title = $titles[$model['type_site']] ?? '';
    return $vi_title !== '' ? pplus_localize($vi_title) : $title;
});

/**
 * Doi ngon ngu tren trang Dich vu (nut chuyen VI/EN/JA o header + hreflang) - tinh lai DUNG alias
 * cua ngon ngu dich (khong dung nguyen alias hien tai nhu mac dinh cua pplus_language_switch_url(),
 * vi 3 ngon ngu dung 3 chuoi alias KHAC nhau cho cung 1 TypeSite). Tinh tap trung o DAY (thay vi
 * moi component Angular tu khai bao rieng) con SUA LUON 1 bug thuc te co trong chinh Angular goc:
 * housekeeping.component.ts/the-management.component.ts khai bao ListLanguageChange BI DAO NGUOC
 * cho nhau (TypeSite 1 tro sang URL cua TypeSite 2 va nguoc lai) - khong co ly do nghiep vu nao cho
 * viec nay, ro rang la loi copy-paste khi viet 2 component gan giong nhau, nen KHONG sao chep lai.
 */
add_filter('pplus_language_switch_url', function (string $default, string $target_lang) {
    $model = pplus_get_service_from_query_vars();
    if (!$model) {
        return $default;
    }

    $base = pplus_service_type_bases()[$model['type_site']] ?? '';
    if ($base === '') {
        return $default;
    }

    if ($target_lang === 'vi') {
        $target_alias = $base;
    } else {
        $dict         = pplus_lang_dictionary($target_lang);
        $target_alias = $dict[$base] ?? $base;
    }
    $target_alias .= '-' . $model['type_site'];

    $prefix = ($target_lang === 'en' || $target_lang === 'ja') ? ('/' . $target_lang) : '';
    return home_url($prefix . '/service/' . $target_alias . '/');
}, 10, 2);
