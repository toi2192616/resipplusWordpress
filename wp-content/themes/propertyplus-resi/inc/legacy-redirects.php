<?php
/**
 * Seed 301 redirect cho URL cu (tu cac lan doi domain-serving-layer TRUOC DAY, khong rieng gi
 * Angular->WordPress lan nay) tu API .NET that "Home/GetLogUrlSite" - doi chieu tu
 * home.service.ts GetLogUrlSite() + app.component.ts GetListRedirect() (Angular tu goi API nay
 * luc khoi dong, so khop URL hien tai voi bang "LogUrlSite" trong SQL Server de tu redirect).
 *
 * 2 DIEU KHAC BIET QUAN TRONG DA XAC NHAN QUA TEST THAT (khong doan):
 * 1. Endpoint nay KHONG co tien to "{lang}/" nhu MOI endpoint khac trong theme (home.service.ts
 *    goi thang "Home/GetLogUrlSite", khong phai "{lang}/Home/GetLogUrlSite") - goi co tien to lang
 *    se bi redirect sang trang dang nhap admin (khop 1 route MVC admin khac, khong phai loi that).
 * 2. Response CO bao trong { result: [...] } giong da so endpoint khac (xac nhan lai qua
 *    pplus_api_get() that - lan curl thu cong dau tien nhin nham vi terminal cat bot dong dau).
 *    Tung phan tu co dang {alias, aliasRedirect, lang, isActive, ...}. "alias" la URL CU (path),
 *    "aliasRedirect" la URL nen tro toi.
 *
 * Dung lai Ppwh_Redirect_Manager (plugin ppplus-seo-sync, DA hoat dong that qua webhook cho cac
 * lan doi slug SAU NAY) lam noi luu tru + noi thuc hien redirect - CHI seed du lieu vao day, khong
 * viet lai co che redirect. Chay qua WP-Cron (giong inc/sitemap.php) de tranh goi API luc co
 * request that (endpoint nay chi 1 request/khong phan trang nen it rui ro timeout hon sitemap,
 * nhung van giu nguyen tac "khong goi API ngoai luc dang phuc vu Googlebot/nguoi dung that").
 */

if (!defined('ABSPATH')) {
    exit;
}

const PPLUS_LEGACY_REDIRECT_CRON_HOOK = 'pplus_legacy_redirect_seed';

/**
 * Chay that trong WP-Cron: goi API, ghi tung cap alias/aliasRedirect vao Ppwh_Redirect_Manager.
 * Neu plugin ppplus-seo-sync chua active (lop Ppwh_Redirect_Manager khong ton tai), thoat som,
 * khong loi.
 */
function pplus_legacy_redirect_seed(): void {
    if (!class_exists('Ppwh_Redirect_Manager')) {
        return;
    }

    $response = pplus_api_get('Home/GetLogUrlSite', [], 0); // cache_seconds=0: khong can cache rieng, da co WP-Cron dieu tiet tan suat goi
    $data     = $response['result'] ?? null;
    if (!is_array($data)) {
        return;
    }

    $manager = Ppwh_Redirect_Manager::instance();
    $seeded  = 0;
    foreach ($data as $item) {
        if (empty($item['isActive']) || empty($item['alias']) || empty($item['aliasRedirect'])) {
            continue;
        }
        $old_path = home_url($item['alias']);
        $new_url  = home_url($item['aliasRedirect']);
        $manager->record_alias_change($old_path, $new_url);
        $seeded++;
    }

    update_option('pplus_legacy_redirect_seeded_count', $seeded, false);
    update_option('pplus_legacy_redirect_seeded_at', time(), false);
}
add_action(PPLUS_LEGACY_REDIRECT_CRON_HOOK, 'pplus_legacy_redirect_seed');

/**
 * Dang ky cron: chay 1 lan dau 15s sau khi active (khong chan request hien tai), sau do lai 1
 * lan/ngay (danh sach nay hiem khi doi, khac voi office/news moi lien tuc them - khong can 6
 * tieng/lan nhu sitemap).
 */
add_action('init', function () {
    if (wp_next_scheduled(PPLUS_LEGACY_REDIRECT_CRON_HOOK)) {
        return;
    }
    wp_schedule_single_event(time() + 15, PPLUS_LEGACY_REDIRECT_CRON_HOOK);
    wp_schedule_event(time() + 3600, 'daily', PPLUS_LEGACY_REDIRECT_CRON_HOOK);
});
