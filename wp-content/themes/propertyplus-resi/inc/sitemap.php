<?php
/**
 * Sitemap XML cho Resi (viet lai 30/9/2026 - ban cu la ban Office: van phong/tin tuc, khong hop
 * Resi va CHUA TUNG duoc require trong functions.php).
 *
 * WordPress core Sitemaps API (/wp-sitemap.xml, khong can plugin) chi biet WP Page that (Ve chung
 * toi, Lien he...). Phan lon URL cua Resi khong nam trong wp_posts, nen file nay them 3 provider:
 *   - pplusareas      : trang tinh/quan/loai can ho/du an/dich vu/chu nha - THEO DUONG DAN MENU that
 *                       (harvest link noi bo tu HTML trang chu VI + EN da render, khong doan URL).
 *   - pplusapartments : ~2.1k trang chi tiet can ho, VI + EN (tu Apartment/_GetApartments,
 *                       apartmentTypeList="1,2" = KHONG biet thu, dung y site). Bo JA: urlHerf JA tu API
 *                       co "--" thay alias du an (link hong) - hreflang van tro toi ban JA cua trang.
 *   - pplusblogs      : bai blog VI + EN.
 *
 * KIEN TRUC: tinh san qua WP-Cron, KHONG tinh luc co request (2.1k can x 2 ngon ngu ~ 360 lan goi API
 * .NET, gioi han 12 ket qua/lan - lam trong 1 request web se vuot 30s). Cron chay THEO TUNG DOT NHO
 * (~20s/dot, nghi 30s giua cac dot de khong don dap API that - bai hoc tu co che "ham nong cache" cua
 * Office da tat vi qua tai server), luu tien do vao 1 option, xong moi hoan doi vao option cong khai
 * (sitemap khong bao gio bi rong/nua chung trong luc dang tinh lai). Tinh lai moi 24 tieng.
 *
 * Bo cac provider khong dung (users, taxonomies) + loai trang "Danh sach yeu thich" (noindex).
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_Sitemaps_Provider')) {
    return;
}

const PPLUS_SITEMAP_STEP_HOOK  = 'pplus_sitemap_step';
const PPLUS_SITEMAP_START_HOOK = 'pplus_sitemap_start';
const PPLUS_SITEMAP_STATE_OPT  = 'pplus_sitemap_state';
const PPLUS_SITEMAP_LANGS      = ['vi', 'en'];

function pplus_sitemap_urls(string $set): array {
    $urls = get_option('pplus_sitemap_set_' . $set, []);
    return is_array($urls) ? $urls : [];
}

/** Harvest link noi bo tu HTML da render cua trang chu (header + footer + noi dung deu chua link that). */
function pplus_sitemap_harvest_area_urls(string $lang): array {
    $prefix = pplus_lang_prefix($lang);
    $resp   = wp_remote_get(home_url($prefix . '/'), ['timeout' => 30, 'redirection' => 3, 'headers' => ['Accept' => 'text/html']]);
    if (is_wp_error($resp) || wp_remote_retrieve_response_code($resp) !== 200) {
        return [];
    }
    $html = wp_remote_retrieve_body($resp);
    if (!preg_match_all('#<a\b[^>]*\shref=["\']([^"\'\#?]+)["\']#i', $html, $m)) {
        return [];
    }
    $home_host = wp_parse_url(home_url(), PHP_URL_HOST);
    $urls      = [];
    foreach ($m[1] as $href) {
        $href = html_entity_decode($href, ENT_QUOTES, 'UTF-8');
        if (strpos($href, 'http') !== 0) {
            $href = home_url($href);
        }
        $p = wp_parse_url($href);
        if (($p['host'] ?? '') !== $home_host) {
            continue;
        }
        $path = $p['path'] ?? '/';
        // Chi lay link cung ngon ngu dang harvest (EN: /en/..., VI: khong tien to /en /ja).
        $is_en = (bool) preg_match('#^/en(/|$)#', $path);
        $is_ja = (bool) preg_match('#^/ja(/|$)#', $path);
        if (($lang === 'vi' && ($is_en || $is_ja)) || ($lang === 'en' && !$is_en)) {
            continue;
        }
        if (preg_match('#/(wp-content|wp-includes|wp-json|wp-admin|feed)(/|$)|danh-sach-yeu-thich|favourite|\.[a-z0-9]{2,5}$#i', $path)) {
            continue;
        }
        // Bo trang chu ('/' hoac '/en/') - da co trong provider "pages" mac dinh cua WP.
        if (rtrim($path, '/') === '' || rtrim($path, '/') === '/en') {
            continue;
        }
        $urls[] = home_url(trailingslashit($path));
    }
    return array_values(array_unique($urls));
}

/** 1 dot xu ly: lam viec toi khi het ~20s, luu tien do, tu len lich dot tiep. */
function pplus_sitemap_step(): void {
    if (function_exists('set_time_limit')) {
        @set_time_limit(60);
    }
    $deadline = time() + 20;
    $state    = get_option(PPLUS_SITEMAP_STATE_OPT);
    // Chi chay khi 1 dot tinh lai DANG mo (pplus_sitemap_start() tao state) - tranh su kien cron thua
    // con sot lai (vd sau khi dot da xong) bat dau tinh lai tu dau ngoai y muon.
    if (!is_array($state)) {
        return;
    }

    while (time() < $deadline && $state['phase'] !== 'done') {
        $lang = PPLUS_SITEMAP_LANGS[$state['lang_i']] ?? null;

        if ($state['phase'] === 'areas') {
            foreach (PPLUS_SITEMAP_LANGS as $l) {
                $state['sets']['areas'] = array_merge($state['sets']['areas'], pplus_sitemap_harvest_area_urls($l));
            }
            $state['phase']  = 'apartments';
            $state['lang_i'] = 0;
            $state['skip']   = 0;
            continue;
        }

        if ($lang === null) { // het ngon ngu cua phase hien tai -> sang phase ke
            $state['phase']  = ($state['phase'] === 'apartments') ? 'blogs' : 'done';
            $state['lang_i'] = 0;
            $state['skip']   = 0;
            $state['page']   = 1;
            continue;
        }

        if ($state['phase'] === 'apartments') {
            $data  = pplus_api_post_json("{$lang}/Apartment/_GetApartments", [
                'skip'              => (int) $state['skip'],
                'price_sort_type'   => 2,
                'apartmentTypeList' => '1,2',
            ], 0);
            $items = $data['result']['listApartments'] ?? [];
            $total = (int) ($data['result']['total_count'] ?? 0);
            foreach ($items as $it) {
                if (!empty($it['urlHerf']) && strpos((string) $it['urlHerf'], '--') === false) {
                    $state['sets']['apartments'][] = home_url(pplus_lang_prefix($lang) . '/' . trim((string) $it['urlHerf'], '/') . '/');
                }
            }
            $state['skip'] += 12;
            if (!$items || $state['skip'] >= $total || $state['skip'] > 6000) {
                $state['lang_i']++;
                $state['skip'] = 0;
            }
            continue;
        }

        if ($state['phase'] === 'blogs') {
            $data  = pplus_api_get("{$lang}/Blogs/GetIndex", ['catalogid' => '', 'page' => (int) $state['page']], 0);
            $items = $data['result']['listBlog'] ?? [];
            $total = (int) ($data['result']['totalItem'] ?? 0);
            foreach ($items as $it) {
                if (!empty($it['alias']) && isset($it['id'])) {
                    $state['sets']['blogs'][] = home_url(pplus_lang_prefix($lang) . '/blog/' . $it['alias'] . '-' . (int) $it['id'] . '/');
                }
            }
            $state['page']++;
            if (!$items || ($state['page'] - 1) * 16 >= $total || $state['page'] > 200) {
                $state['lang_i']++;
                $state['page'] = 1;
            }
            continue;
        }
    }

    if ($state['phase'] === 'done') {
        foreach ($state['sets'] as $set => $urls) {
            $urls = array_values(array_unique($urls));
            // Khong ghi de bang danh sach rong (API/loopback loi) - giu ban cu con dung duoc.
            if ($urls) {
                update_option('pplus_sitemap_set_' . $set, $urls, false);
            }
        }
        update_option('pplus_sitemap_built_at', time(), false);
        delete_option(PPLUS_SITEMAP_STATE_OPT);
        return;
    }

    update_option(PPLUS_SITEMAP_STATE_OPT, $state, false);
    wp_schedule_single_event(time() + 30, PPLUS_SITEMAP_STEP_HOOK);
}
add_action(PPLUS_SITEMAP_STEP_HOOK, 'pplus_sitemap_step');

/** Bat dau 1 dot tinh lai tu dau (goi moi 24h). */
function pplus_sitemap_start(): void {
    wp_clear_scheduled_hook(PPLUS_SITEMAP_STEP_HOOK);
    update_option(PPLUS_SITEMAP_STATE_OPT, ['phase' => 'areas', 'lang_i' => 0, 'skip' => 0, 'page' => 1, 'sets' => ['areas' => [], 'apartments' => [], 'blogs' => []]], false);
    pplus_sitemap_step();
}
add_action(PPLUS_SITEMAP_START_HOOK, 'pplus_sitemap_start');

add_action('init', function () {
    if (!wp_next_scheduled(PPLUS_SITEMAP_START_HOOK)) {
        wp_schedule_event(time() + 60, 'daily', PPLUS_SITEMAP_START_HOOK);
    }
    // Lan dau (chua tung co du lieu) va khong co dot nao dang cho: chay som thay vi doi 24h.
    if (!get_option('pplus_sitemap_built_at') && !get_option(PPLUS_SITEMAP_STATE_OPT) && !wp_next_scheduled(PPLUS_SITEMAP_STEP_HOOK)) {
        wp_schedule_single_event(time() + 15, PPLUS_SITEMAP_START_HOOK);
    }
});

/** Provider dung chung: chi DOC mang URL da tinh san, tu chia file con theo wp_sitemaps_get_max_urls(). */
class PPlus_Sitemap_Provider extends WP_Sitemaps_Provider {
    private string $set;

    public function __construct(string $name, string $set) {
        $this->name        = $name;
        $this->object_type = $name;
        $this->set         = $set;
    }

    public function get_url_list($page_num, $object_subtype = '') {
        $per_page = wp_sitemaps_get_max_urls($this->object_type);
        $list     = [];
        foreach (array_slice(pplus_sitemap_urls($this->set), ($page_num - 1) * $per_page, $per_page) as $url) {
            $list[] = ['loc' => $url];
        }
        return $list;
    }

    public function get_max_num_pages($object_subtype = '') {
        $per_page = max(1, wp_sitemaps_get_max_urls($this->object_type));
        return (int) max(1, ceil(count(pplus_sitemap_urls($this->set)) / $per_page));
    }
}

add_action('wp_sitemaps_init', function () {
    $server = wp_sitemaps_get_server();
    // Ten provider KHONG duoc co dau gach ngang (rewrite rule core chi cho [a-z] o phan ten).
    $server->registry->add_provider('pplusareas', new PPlus_Sitemap_Provider('pplusareas', 'areas'));
    $server->registry->add_provider('pplusapartments', new PPlus_Sitemap_Provider('pplusapartments', 'apartments'));
    $server->registry->add_provider('pplusblogs', new PPlus_Sitemap_Provider('pplusblogs', 'blogs'));
});

add_filter('wp_sitemaps_add_provider', function ($provider, string $name) {
    return in_array($name, ['users', 'taxonomies'], true) ? false : $provider;
}, 10, 2);

// Trang "Danh sach yeu thich" la du lieu rieng tu nguoi dung, da noindex; "sample-page" la trang mac dinh cua WP - khong dua vao sitemap.
add_filter('wp_sitemaps_posts_query_args', function (array $args): array {
    foreach (['danh-sach-yeu-thich', 'sample-page'] as $slug) {
        $page = get_page_by_path($slug);
        if ($page) {
            $args['post__not_in'] = array_merge($args['post__not_in'] ?? [], [(int) $page->ID]);
        }
    }
    return $args;
});

add_action('init', function () {
    if (get_option('pplus_rewrite_flushed_sitemap_resi_v1') !== '1') {
        flush_rewrite_rules();
        update_option('pplus_rewrite_flushed_sitemap_resi_v1', '1');
    }
}, 20);
