<?php
/**
 * C2 - Trang "Chủ nhà" (owner-index.component real: 2 TypeSite co dinh, dung CHUNG 1 khung
 * ".owner-box" + tab nav ".info-list", giong het co che TypeSite cua C1 "Dich vu" - xem
 * inc/service-route.php). KHAC service-route.php o cho alias: owner KHONG ghep hau to so "-N",
 * ma dung THANG pplus_localize($vi_alias) - da xac nhan qua curl truc tiep {lang}/Home/GetLangApi
 * that: dictionary EN/JA co san ban dich CHINH XAC cho ca 2 chuoi alias VI duoi day
 * ("loi-ich-chu-nha" => "owner-benefit", "dang-ky-chu-nha" => "register-Leasing", ca EN lan JA
 * dung chung 1 gia tri) - giong y het cach parts/header.php da dung san cho menu "Chủ nhà"
 * (pplus_lang_url('owner/' . pplus_localize($alias))) truoc ca khi route nay ton tai.
 *
 * LICH SU PHAM VI: lan dau (22/9) CHI xay TypeSite 1 ("Lợi ích của chủ nhà"), TypeSite 2 ("Đăng
 * ký cho thuê nhà") 404 co y (nguoi dung chi hoi ve URL do). Nguoi dung bam thu tab thu 2, thay
 * trong -> yeu cau xay tiep TypeSite 2 (cung ngay) - da xay xong: parts/owner-register-leasing.php
 * (form nhieu truong + tai anh base64 + modal xac nhan lai) + inc/owner-register-route.php (REST
 * proxy toi {lang}/Owner/IssueNewApartment - endpoint .NET khac han Service/
 * IssueLeaseApartmentService cua TypeSite 4).
 */

if (!defined('ABSPATH')) {
    exit;
}

/** 2 alias goc (chua dich) - TypeSite 1 la CHINH cua file nay, TypeSite 2 chi de dung tab nav. */
function pplus_owner_type_bases(): array {
    return [
        1 => 'loi-ich-chu-nha',
        2 => 'dang-ky-chu-nha',
    ];
}

/** Tieu de tieng Viet - dung cho tab nav/<title>/breadcrumb, khop dung text that. */
function pplus_owner_type_titles(): array {
    return [
        1 => 'Lợi ích của chủ nhà',
        2 => 'Đăng ký cho thuê nhà',
    ];
}

/** TypeSite nao THAT SU da xay xong noi dung (partial ton tai) - xem docblock dau file. */
function pplus_owner_type_built(): array {
    return [1, 2];
}

add_action('init', function () {
    add_rewrite_rule(
        '^owner/([^/]+)/?$',
        'index.php?pplus_owner=1&pplus_owner_alias=$matches[1]',
        'top'
    );
});

add_filter('query_vars', function (array $vars): array {
    $vars[] = 'pplus_owner';
    $vars[] = 'pplus_owner_alias';
    return $vars;
});

// Cung bug da gap o service-route.php/reference-index-route.php: query var rieng khong duoc WP
// nhan dien khien parse_query() mac dinh coi day la trang chu.
add_action('parse_query', function (WP_Query $query) {
    if ($query->is_main_query() && get_query_var('pplus_owner')) {
        $query->is_home = false;
    }
});

add_action('template_redirect', function () {
    if (get_query_var('pplus_owner')) {
        global $wp_query;
        $wp_query->is_404 = false;
        status_header(200);
    }
}, 0);

add_action('init', function () {
    if (get_option('pplus_rewrite_flushed_resi_owner_v1') !== '1') {
        flush_rewrite_rules();
        update_option('pplus_rewrite_flushed_resi_owner_v1', '1');
    }
}, 20);

add_filter('template_include', function (string $template): string {
    if (get_query_var('pplus_owner')) {
        $custom = PPLUS_THEME_DIR . '/page-owner.php';
        if (file_exists($custom)) {
            return $custom;
        }
    }
    return $template;
});

/**
 * Doi chieu 1 alias tu URL sang TypeSite (1-2), theo dung ngon ngu HIEN TAI (PPLUS_LANG), dung
 * THANG pplus_localize() (xem docblock dau file - khac co che "+ so thu tu" cua service-route.php).
 * CHI tra ve TypeSite da xay xong (pplus_owner_type_built()) - TypeSite 2 co chu alias khop nhung
 * chua co partial nen van tra ve 0 (404), tranh trang trong/gay nham "da xong".
 */
function pplus_resolve_owner_type(string $alias): int {
    foreach (pplus_owner_type_bases() as $type => $base) {
        if (!in_array($type, pplus_owner_type_built(), true)) {
            continue;
        }
        if ($alias === pplus_localize($base)) {
            return $type;
        }
    }
    return 0;
}

/**
 * Doc query var hien tai, doi chieu ra TypeSite - dung chung boi page-owner.php va filter title
 * duoi day. Static-cache trong 1 request (cung quy uoc voi pplus_get_service_from_query_vars()).
 *
 * @return array{type_site:int, alias:string}|null null = khong khop TypeSite da xay (404).
 */
function pplus_get_owner_from_query_vars(): ?array {
    static $cached  = null;
    static $has_run = false;
    if ($has_run) {
        return $cached;
    }
    $has_run = true;

    $alias = sanitize_text_field((string) get_query_var('pplus_owner_alias'));
    if ($alias === '') {
        return null;
    }
    $type_site = pplus_resolve_owner_type($alias);
    if ($type_site === 0) {
        return null;
    }

    $cached = ['type_site' => $type_site, 'alias' => $alias];
    return $cached;
}

add_filter('pre_get_document_title', function (string $title): string {
    $model = pplus_get_owner_from_query_vars();
    if (!$model) {
        return $title;
    }
    $titles = pplus_owner_type_titles();
    $vi_title = $titles[$model['type_site']] ?? '';
    return $vi_title !== '' ? pplus_localize($vi_title) : $title;
});

/**
 * Doi ngon ngu tren trang Chu nha (nut chuyen VI/EN/JA o header + hreflang) - tinh lai DUNG alias
 * cua ngon ngu dich, cung ly do/cach lam nhu pplus_service_route's cung ten filter.
 */
add_filter('pplus_language_switch_url', function (string $default, string $target_lang) {
    $model = pplus_get_owner_from_query_vars();
    if (!$model) {
        return $default;
    }

    $base = pplus_owner_type_bases()[$model['type_site']] ?? '';
    if ($base === '') {
        return $default;
    }

    if ($target_lang === 'vi') {
        $target_alias = $base;
    } else {
        $dict         = pplus_lang_dictionary($target_lang);
        $target_alias = $dict[$base] ?? $base;
    }

    $prefix = ($target_lang === 'en' || $target_lang === 'ja') ? ('/' . $target_lang) : '';
    return home_url($prefix . '/owner/' . $target_alias . '/');
}, 10, 2);
