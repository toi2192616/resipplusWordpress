<?php
/**
 * URL that cua production cho A1 (danh sach tim kiem, Page "cho-thue-van-phong" that, ID=6) va A3
 * (landing tinh/thanh, 2 Page con "ha-noi"/"ho-chi-minh" cua Page do) o ban tieng Anh: "office-for-
 * rent" thay vi "cho-thue-van-phong" - xac nhan qua doi chieu hreflang truc tiep tren propertyplus.vn
 * (17/8/2026): "/cho-thue-van-phong" (vi) <-> "/en/office-for-rent" (en); "/cho-thue-van-phong/ha-
 * noi" (vi) <-> "/en/office-for-rent/ha-noi" (en, "ha-noi"/"ho-chi-minh" GIONG NHAU ca 2 ngon ngu,
 * khong can doi).
 *
 * A1/A3 la Page THAT (khong phai route ao nhu A4/A2) nen KHONG the doi slug that trong DB theo ngon
 * ngu (1 Page chi co 1 post_name co dinh) - dung "pagename" (query var chuan cua WordPress, ho tro
 * duong dan cha/con noi = "/" giong het cau truc that trong DB) lam DICH cua rewrite rule, de WP tu
 * tim DUNG Page goc (van la "cho-thue-van-phong"/"cho-thue-van-phong/ha-noi" trong DB) bang gia tri
 * NAY - KHONG lien quan gi den URL nguoi dung go, van dung y het co che Page/template hien co (khong
 * can query var/template_include rieng nhu A4).
 *
 * "(?!page/\d+/?$)" o rule 1-doan (A3) - cung 1 ly do voi A4 (xem inc/reference-index-route.php):
 * tranh nuot mat URL phan trang that cua chinh A1 ("/office-for-rent/page/2/", WordPress tu sinh
 * khi redirect_canonical() "?paged=2" tren Page "office-for-rent" - hanh vi mac dinh cho MOI Page
 * permalink dang dep, ke ca Page "ao" duoc tro toi qua rewrite rule nhu o day).
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', function () {
    // A1 (0 doan sau "office-for-rent") - danh sach tim kiem.
    add_rewrite_rule(
        '^office-for-rent/?$',
        'index.php?pagename=cho-thue-van-phong',
        'top'
    );
    add_rewrite_rule(
        '^office-for-rent/page/(\d+)/?$',
        'index.php?pagename=cho-thue-van-phong&paged=$matches[1]',
        'top'
    );
    // A3 (1 doan sau "office-for-rent") - landing theo tinh/thanh.
    add_rewrite_rule(
        '^office-for-rent/(?!page/\d+/?$)([^/]+)/?$',
        'index.php?pagename=cho-thue-van-phong/$matches[1]',
        'top'
    );
});

add_action('init', function () {
    if (get_option('pplus_rewrite_flushed_office_list_en_v1') !== '1') {
        flush_rewrite_rules();
        update_option('pplus_rewrite_flushed_office_list_en_v1', '1');
    }
}, 20);

/**
 * Doi ngon ngu tren A1/A3 (nut chuyen VI/EN o header + hreflang, xem inc/language-route.php goi
 * apply_filters('pplus_language_switch_url', ...)) - KHAC A2/A4 (can du lieu aliasVi/aliasEn tu
 * API), A1/A3 CHI can doi dung 1 doan goc "cho-thue-van-phong" <-> "office-for-rent" (province con
 * lai "ha-noi"/"ho-chi-minh" GIONG NHAU ca 2 ngon ngu, khong can tra cuu gi them - xem doc block dau
 * file). Mac dinh cua pplus_language_switch_url() (chi doi tien to /en) se giu nguyen doan goc VI
 * duoi /en/, sai voi URL that cua production.
 */
add_filter('pplus_language_switch_url', function (string $default, string $target_lang) {
    if (!is_page_template('page-office-list.php') && !is_page_template('page-landing.php')) {
        return $default;
    }
    $path        = pplus_current_path();
    $target_slug = ($target_lang === 'en') ? 'office-for-rent' : 'cho-thue-van-phong';
    $rest        = preg_replace('#^/(cho-thue-van-phong|office-for-rent)#', '', $path, 1);
    $prefix      = ($target_lang === 'en') ? '/en' : '';
    return home_url($prefix . '/' . $target_slug . $rest);
}, 10, 2);
