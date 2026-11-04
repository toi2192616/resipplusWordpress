<?php
/**
 * Danh sach yeu thich (port module Angular "favourite"). URL THAT (xac nhan truc tiep qua
 * favourite.component.ts dong 47-60, GlobalConstants.ListLanguageChange):
 *   /danh-sach-yeu-thich        (vi)
 *   /en/danh-sach-yeu-thich     (en, tien to /en da duoc inc/language-route.php strip tu truoc)
 *   /ja/danh-sach-yeu-thich     (ja, cung alias voi en)
 *
 * SUA 22/9: URL cu "office-favourite" la tan du copy tu theme Van phong (chua tung sua), khong
 * khop URL that cua Resi ma nguoi dung thao truc tiep - xem docblock day du trong page-favourite.php
 * ve toan bo pham vi sua (route/REST/JS/CSS, khong chi URL nay).
 *
 * KHAC voi cac trang khac: du lieu bo suu tap yeu thich luu HOAN TOAN o localStorage cua trinh
 * duyet (giong Angular - khong can login/backend). Trang favourite-list nay (KHAC favourite-detail,
 * xem page-favourite.php) khong goi API/REST nao ca de render - JS (assets/js/pplus-favourite.js)
 * chi doc localStorage.
 *
 * LUON noindex, nofollow (giong favourite.component.ts dong ngOnInit: meta robots noindex vo dieu kien).
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', function () {
    add_rewrite_rule('^danh-sach-yeu-thich/?$', 'index.php?pplus_favourite=1', 'top');
    add_rewrite_rule('^en/danh-sach-yeu-thich/?$', 'index.php?pplus_favourite=1', 'top');
    add_rewrite_rule('^ja/danh-sach-yeu-thich/?$', 'index.php?pplus_favourite=1', 'top');
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
// Settings > Permalinks > Save 1 lan sau khi deploy de dang ky rule nay. "_v5" (tang tu "_v4") vi
// PATTERN rewrite da doi ("office-favourite" -> "danh-sach-yeu-thich") - khoa cu se KHONG tu flush
// lai (WordPress van khop theo bang rule CACHED cu neu khong doi khoa).
add_action('init', function () {
    if (get_option('pplus_rewrite_flushed_v5') !== '1') {
        flush_rewrite_rules();
        update_option('pplus_rewrite_flushed_v5', '1');
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

/* ======================================================================================================================================
 * 4/11/2026 - TRANG CHI TIET 1 DANH SACH: /{lang}/favourite/favourite-detail?fshare_link={id}  (danh sach tren THIET BI nay)
 *                                         /{lang}/favourite/favourite-detail?ids=1,2&IdProject=3  (link chia se)
 * Port favourite-detail.component (Angular, PPlusAngularResi). Truoc day CHUA xay: the bo suu tap tren /danh-sach-yeu-thich da tro toi URL nay
 * (CFG.detailUrl) nen bam vao la 404 (nguoi dung bao 4/11). Du lieu bo suu tap van o localStorage nen server chi render KHUNG - JS
 * (assets/js/pplus-favourite-detail.js) doc localStorage roi goi REST pplus/v1/favourite-items (inc/favourite-rest.php) lay the + toa do.
 * Tien to ngon ngu da bi inc/language-route.php strip nen chi can 1 rule khong tien to. LUON noindex (du lieu ca nhan, nhu trang danh sach).
 * ====================================================================================================================================== */
add_action('init', function () {
    add_rewrite_rule('^favourite/favourite-detail/?$', 'index.php?pplus_favourite_detail=1', 'top');
});

add_filter('query_vars', function (array $vars): array {
    $vars[] = 'pplus_favourite_detail';
    return $vars;
});

add_action('parse_query', function (WP_Query $query) {
    if ($query->is_main_query() && get_query_var('pplus_favourite_detail')) {
        $query->is_home = false;
    }
});

add_action('template_redirect', function () {
    if (get_query_var('pplus_favourite_detail')) {
        global $wp_query;
        $wp_query->is_404 = false;
        status_header(200);
    }
}, 0);

add_action('init', function () {
    if (get_option('pplus_rewrite_flushed_favdetail_v1') !== '1') {
        flush_rewrite_rules();
        update_option('pplus_rewrite_flushed_favdetail_v1', '1');
    }
}, 20);

add_filter('template_include', function (string $template): string {
    if (get_query_var('pplus_favourite_detail')) {
        $custom = PPLUS_THEME_DIR . '/page-favourite-detail.php';
        if (file_exists($custom)) {
            return $custom;
        }
    }
    return $template;
});

add_filter('pre_get_document_title', function (string $title): string {
    if (!get_query_var('pplus_favourite_detail')) {
        return $title;
    }
    return pplus_localize('Chi tiết yêu thích') . ' - PropertyPlus';
});

add_action('wp_head', function () {
    if (!get_query_var('pplus_favourite_detail')) {
        return;
    }
    echo '<meta name="robots" content="noindex, nofollow" />' . "\n";
}, 1);
