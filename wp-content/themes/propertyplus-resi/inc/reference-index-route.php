<?php
/**
 * A4 - Landing theo tinh/alias-loai-hinh (module Angular "reference-index", KHAC voi
 * "reference-main" cua A3). 2 URL dong khac nhau cung tro vao 1 template:
 *
 *   /thue-van-phong/{province}              (khong "cho-", 2 doan)  -> alias rong, dung
 *                                            $province lam alias goi Reference/GetIndexProvince
 *   /cho-thue-van-phong/{province}/{alias}  (3 doan)                -> goi Reference/GetIndex
 *
 * Khong dung Page tinh nhu A3 vi day la URL dong (menu/loai hinh phat sinh tu du lieu .NET,
 * khong co danh sach co dinh). Khong trung voi A2 (4 doan: province/district/alias-id) hay
 * A3 (Page that, 2 doan qua page hierarchy - rewrite 'top' o day chi khop dung 3 doan nen
 * khong anh huong A3).
 *
 * Rule 3-doan CO loai tru rieng "page/{so}" (xem lookahead trong rule) de KHONG nuot mat URL
 * phan trang that cua Page /cho-thue-van-phong/ (A1, "/cho-thue-van-phong/page/2/" - WordPress
 * tu sinh dang nay khi redirect_canonical() "?paged=2") - neu khong loai tru, rule 'top' o day
 * se thang rule phan trang goc cua WP, hieu nham "page" la ten tinh/thanh, tra ve 404 that.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', function () {
    add_rewrite_rule(
        '^thue-van-phong/([^/]+)/?$',
        'index.php?pplus_reference_index=1&pplus_ref_province=$matches[1]&pplus_ref_alias=',
        'top'
    );
    // "(?!page/\d+/?$)" - bug thuc te nguoi dung bao cao (12/8/2026): phan trang tren A1
    // (/cho-thue-van-phong/, page-office-list.php) sang trang 2/3 bi loi. Nguyen nhan: WordPress tu
    // dong chuyen "?paged=2" thanh URL dang "/cho-thue-van-phong/page/2/" (redirect_canonical(),
    // hanh vi mac dinh cua WP khi permalink dang "dep") - URL nay CUNG khop dung 2 doan cua rule
    // ben duoi ("page" lam $matches[1]=province, "2" lam $matches[2]=alias, rule 'top' nen thang
    // ca rule phan trang THAT cua chinh Page /cho-thue-van-phong/ do WP tu sinh), tra ve du lieu
    // Reference API voi province="page" (khong ton tai) -> 404 that. Da xac nhan qua curl -I truc
    // tiep tren ca 2 moi truong (local va domain test): "/cho-thue-van-phong/page/2/?..." → 404,
    // trong khi "/cho-thue-van-phong/ha-noi/hoan-kiem/" (dung 2 doan, khong phai "page/N") van 200
    // binh thuong. Them lookahead loai tru dung mau "page/{so}" (dung cho A4, khong dung cho A1 vi
    // A1 la Page that nen khong bi anh huong boi thay doi nay).
    add_rewrite_rule(
        '^cho-thue-van-phong/(?!page/\d+/?$)([^/]+)/([^/]+)/?$',
        'index.php?pplus_reference_index=1&pplus_ref_province=$matches[1]&pplus_ref_alias=$matches[2]',
        'top'
    );
    // Bug thuc te nguoi dung bao cao (17/8/2026): bam so trang 2/3 tren A4 (Quan/Duong/Hang) van
    // 404 THAT (khac loi 12/8 o tren, da fix roi) - qua curl -I xac nhan: link phan trang PHP sinh
    // ra dung dang query string "?...&paged=2" (200 neu goi thang), nhung trinh duyet luon GET
    // link do truoc, va redirect_canonical() cua WP tu dong 301 sang dang duong dan
    // "/cho-thue-van-phong/{province}/{alias}/page/{so}/" (hanh vi mac dinh khi permalink "dep",
    // ap dung cho MOI query var 'paged' du la route that hay route ao). Rule 3-doan o tren KHONG
    // khop dang 4-doan nay (page/{so} la doan thu 3, khong phai thu 1) nen WP khong tim thay rule
    // nao ca -> 404 that. Them rule rieng cho dang 4-doan, gan them paged=$matches[3].
    add_rewrite_rule(
        '^cho-thue-van-phong/([^/]+)/([^/]+)/page/(\d+)/?$',
        'index.php?pplus_reference_index=1&pplus_ref_province=$matches[1]&pplus_ref_alias=$matches[2]&paged=$matches[3]',
        'top'
    );

    // Dang URL that cua production cho ban tieng Anh (17/8/2026, xac nhan qua doi chieu hreflang
    // truc tiep tren propertyplus.vn): "office-for-rent" (KHONG phai "cho-thue-van-phong") lam goc,
    // alias CUNG khac ban VI (vd "hoan-kiem-district" thay vi "hoan-kiem" - lay tu field "aliasEn"
    // cua chinh API, xem pplus_get_reference_index()). Them "pplus_ref_alias_lang=en" de ham do biet
    // alias trong URL DA la ban tieng Anh san (goi thang en/Reference/GetIndex, KHONG do tham qua
    // vi truoc - da xac nhan qua goi API that: "en/Reference/GetIndex?alias=hoan-kiem-district" tra
    // ve dung du lieu). Dang cu (VI-form duoi tien to /en, "pplus_ref_alias_lang" mac dinh 'vi' o
    // rule tren) VAN duoc giu nguyen - tuong thich nguoc voi link/bookmark cu, khong bi 404.
    add_rewrite_rule(
        '^office-for-rent/(?!page/\d+/?$)([^/]+)/([^/]+)/?$',
        'index.php?pplus_reference_index=1&pplus_ref_province=$matches[1]&pplus_ref_alias=$matches[2]&pplus_ref_alias_lang=en',
        'top'
    );
    add_rewrite_rule(
        '^office-for-rent/([^/]+)/([^/]+)/page/(\d+)/?$',
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
// mac dinh coi day la trang chu (is_home=true) - lam is_front_page() sai (vd tieu de <title> bi
// ghi de thanh tieu de tieng Anh cua trang chu, header gan nham class "headerHome"). Cung 1 loi
// da gap va sua o inc/blog-routes.php/inc/author-route.php, sua tai goc bang parse_query.
add_action('parse_query', function (WP_Query $query) {
    if ($query->is_main_query() && get_query_var('pplus_reference_index')) {
        $query->is_home = false;
    }
});

// Bug thuc te nguoi dung bao cao (12/8/2026): bam phan trang sang trang 2/3 (vd sau khi loc gia/
// hang tren trang Quan/Duong/Hang) bi "loi". Nguyen nhan: query var 'pplus_reference_index' KHONG
// gan voi bat ky post/page THAT nao nen WP_Query chinh luon tra ve 0 post - WP::handle_404() (loi
// CHAY SAU parse_query, trong wp-includes/class-wp.php) mac dinh coi "co tham so paged nhung 0
// post" la trang khong ton tai, tu dat is_404=true + gui HTTP 404 that (xac nhan qua curl -I: trang
// 1 tra 200, y het URL do them "&paged=2" tra 404) - du template van render noi dung binh thuong
// (du lieu that su den tu API .NET, khong phai tu WP_Query). HTTP 404 lam Google KHONG index cac
// trang phan trang nay (SEO), va 1 so trinh duyet/proxy xu ly rieng response 404.
// Sua bang cach ep lai is_404=false + status 200 tren 'template_redirect' (chay SAU handle_404 nen
// khong bi ghi de lai) - CHI cho dung route nay, khong anh huong 404 that cua cac URL khac.
add_action('template_redirect', function () {
    if (get_query_var('pplus_reference_index')) {
        global $wp_query;
        $wp_query->is_404 = false;
        status_header(200);
    }
}, 0);

add_action('init', function () {
    // v3: doi regex (them lookahead loai tru "page/{so}") - can flush lai de WP nap rewrite rule
    // moi, khong chi tang ten bien cho co (da xac nhan flush that su can thiet: rewrite rule cu
    // van con hieu luc trong DB cho toi khi flush lai, du code PHP da doi).
    if (get_option('pplus_rewrite_flushed_v3') !== '1') {
        flush_rewrite_rules();
        update_option('pplus_rewrite_flushed_v3', '1');
    }
    // rieng: them rule 4-doan ".../page/{so}/" (17/8/2026, xem comment o rule phia tren). DUNG TEN
    // OPTION RIENG, KHONG dung lai "pplus_rewrite_flushed_v4" - ten do DA bi dung boi
    // favourite-route.php va author-route.php (2 file khac, khong lien quan) nen da luon = '1' tu
    // truoc, khien dieu kien flush o day khong bao gio chay that (bug thuc te da gap khi tu kiem
    // tra local: get_option() tra ve '1' ngay tu dau code nay moi them, rewrite rule moi khong
    // co trong DB). Moi route file phai dung 1 tien to option rieng, khong dung chung dai "v1/v2/.."
    // giua cac file khac nhau.
    if (get_option('pplus_rewrite_flushed_ref_paged_v1') !== '1') {
        flush_rewrite_rules();
        update_option('pplus_rewrite_flushed_ref_paged_v1', '1');
    }
    // rieng: them 2 rule EN-form "office-for-rent/..." (17/8/2026, xem comment o rule phia tren).
    if (get_option('pplus_rewrite_flushed_ref_en_v1') !== '1') {
        flush_rewrite_rules();
        update_option('pplus_rewrite_flushed_ref_en_v1', '1');
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
    return $model['dataSelected']['metaTitle'] ?? $title;
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

    $description = $data['metaDescription'] ?? '';
    if ($description) {
        printf('<meta name="description" content="%s" />' . "\n", esc_attr($description));
    }

    // Robots: giong het logic reference-index.component.ts dong 140-148 - co menuItem thi
    // theo isIndex, khong co menuItem (route /thue-van-phong/{province}) thi luon noindex.
    if (!empty($model['menuItem'])) {
        $robots = ($data['isIndex'] === false) ? 'noindex, nofollow' : 'index, follow';
    } else {
        $robots = 'noindex, nofollow';
    }
    printf('<meta name="robots" content="%s" />' . "\n", esc_attr($robots));
}, 1);

/**
 * Doi ngon ngu tren A4 (nut chuyen VI/EN o header + hreflang, xem inc/language-route.php goi
 * apply_filters('pplus_language_switch_url', ...)). KHAC A1/A3 (chi doi 1 doan goc co dinh), A4 con
 * can doi CA alias (vd "hoan-kiem" <-> "hoan-kiem-district") - dung TRUC TIEP aliasVi/aliasEn co
 * san trong $model DA fetch san cho trang hien tai (KHONG goi them API nao ca, xem
 * pplus_get_reference_index_from_query_vars() - model nay luon duoc goi 1 lan/request du la tu day
 * hay tu wp_head()/pre_get_document_title() nho static cache trong ham do).
 *
 * Truong hop $alias rong (dang "/thue-van-phong/{province}", khong "cho-") - CHUA co URL EN rieng
 * duoc xac nhan cho dang nay (khac A1/A3/A4-co-alias, chi kiem tra duoc 3 dang do qua doi chieu
 * hreflang truc tiep tren propertyplus.vn), giu nguyen hanh vi mac dinh (chi doi tien to /en) de an
 * toan - KHONG doan mo hinh URL chua kiem chung.
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

    $model     = pplus_get_reference_index_from_query_vars();
    $data      = $model['dataSelected'] ?? [];
    $alias_vi  = (string) ($data['aliasVi'] ?? '');
    $alias_en  = (string) ($data['aliasEn'] ?? '');

    if ($target_lang === 'en') {
        if ($alias_en === '') {
            return $default;
        }
        return home_url('/en/office-for-rent/' . $province . '/' . $alias_en . '/');
    }

    // Ve VI: uu tien aliasVi tu API (dung nhat), fallback ve $alias hien co trong URL neu API rong
    // (vd loi mang tam thoi khi fetch $model) - KHONG de trang trang hoan toan.
    $target_alias = $alias_vi !== '' ? $alias_vi : $alias;
    return home_url('/cho-thue-van-phong/' . $province . '/' . $target_alias . '/');
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
