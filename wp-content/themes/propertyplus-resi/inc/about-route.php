<?php
/**
 * "/en/about-us" va "/ja/about-us" - alias EN/JA THAT cua trang "Ve chung toi" (xac nhan qua doi
 * chieu truc tiep production LAN about-index.component.ts dong 45-58: GlobalConstants.
 * ListLanguageChange dung CHUNG 1 alias "about-us" cho CA EN LAN JA, khac han alias VI
 * "ve-chung-toi") - can 1 rewrite rule RIENG vi day la truong hop HIEM trong theme nay: cac trang
 * khac (Dich vu, Quan/Loai-can-ho...) la route dong tu viet (template_include + tu doi chieu alias
 * theo tung ngon ngu ngay trong route), nhung "Ve chung toi" (khong-alias) lai la 1 WordPress Page
 * THAT (page-about.php, Template Name "Ve chung toi", slug "ve-chung-toi") - WP tu dong khop DUNG
 * khi KHONG co tien to ngon ngu (ban VI), nhung /en, /ja da bi inc/language-route.php strip tien
 * to SOM (truoc khi WP parse_request()), con lai "about-us" thi WP KHONG THE tu khop (khong co
 * Page nao slug "about-us" trong DB) -> 404 THAT SU tren ban EN/JA.
 *
 * Bug thuc te phat hien 17/9 khi doi chieu menu header voi ban chinh: link header da xay dung
 * DUNG alias "about-us" (qua pplus_localize('ve-chung-toi'), dictionary GetLangApi that co ban
 * dich nay), nhung bam vao thi 404 - vi KHONG co gi tro "about-us" nguoc lai ve Page "ve-chung-toi"
 * ca. Chi can tro thang ve query var "pagename" chuan cua WP (KHONG can template_include rieng
 * nhu cac route khac - WP tu load dung Page + template da gan san tu "pagename").
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', function () {
    add_rewrite_rule('^about-us/?$', 'index.php?pagename=ve-chung-toi', 'top');
});

add_action('init', function () {
    if (get_option('pplus_rewrite_flushed_about_en_v1') !== '1') {
        flush_rewrite_rules();
        update_option('pplus_rewrite_flushed_about_en_v1', '1');
    }
}, 20);
