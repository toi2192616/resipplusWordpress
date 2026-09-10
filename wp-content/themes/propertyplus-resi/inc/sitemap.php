<?php
/**
 * Sitemap XML cho cac trang KHONG phai WP Page/Post that (A2 chi tiet van phong, B3 tin tuc chi
 * tiet) - nhung trang nay khong nam trong wp_posts nen sitemap mac dinh cua WordPress core (tu
 * WP 5.5, /wp-sitemap.xml, tu quet wp_posts/wp_users/taxonomy) KHONG BIET chung ton tai.
 *
 * QUAN TRONG: file plugin cu `ppplus-seo-sync/includes/class-sitemap-provider.php` gia dinh se
 * goi 1 API tong hop `/api/seo/v1/sitemap-data` CHUA TUNG duoc xay (xem canh bao trong
 * inc/seo-hooks.php - cung 1 kien truc cu da bi bo) va CHI hoat dong khi RankMath active. File nay
 * dung lai HAN CHE DUNG: WordPress core Sitemaps API (co san tu WP 5.5, KHONG can RankMath), lay
 * du lieu truc tiep tu cac ham pplus_get_* DA CO SAN (cung nguon du lieu that cac trang dang dung),
 * dung dung kien truc "goi thang API Angular hien co" ma toan bo du an da chot tu dau.
 *
 * KIEN TRUC QUAN TRONG - TINH TRUOC QUA WP-CRON, KHONG TINH TRUC TIEP LUC CO REQUEST:
 * Liet ke het van phong/tin tuc can HANG CHUC lan goi API .NET tuan tu (server .NET tu gioi han
 * ~24 ket qua/lan bat ke page_count gui len, da xac nhan qua test that) - lam that bai voi loi
 * "Maximum execution time of 30 seconds exceeded" neu tinh truc tiep trong 1 request web binh
 * thuong (da gap that khi test). Nen: 1 WP-Cron job chay dinh ky (moi 6 tieng) tinh san danh sach
 * URL va luu vao option; provider sitemap CHI DOC option do (nhanh, khong cho API nao), KHONG BAO
 * GIO tu tinh toan luc co request.
 *
 * PHAM VI: A2 (van phong) + B3 (tin tuc) - 2 loai noi dung so luong lon nhat va quan trong nhat ve
 * SEO. CHUA bao gom A4 (quan/huyen/loai-hinh/duong/hang) va A5 (danh sach duong) - can 1 cach
 * enumerate rieng (khong co san 1 ham "liet ke tat ca" don gian nhu office/blog), de lam rieng sau.
 * A1/A3 (Page/Page-con that) + B1/B5/B6 (Page that) DA nam trong sitemap mac dinh cua WordPress
 * core roi (post_type 'page'), khong can code them o day.
 */

if (!defined('ABSPATH')) {
    exit;
}

// WP_Sitemaps_Provider chi co tu WordPress 5.5+ (va co the bi tat qua filter 'wp_sitemaps_enabled').
// Thoat som, khong dang ky gi ca, thay vi de PHP fatal error luc "class ... extends
// WP_Sitemaps_Provider" neu class chua ton tai.
if (!class_exists('WP_Sitemaps_Provider')) {
    return;
}

const PPLUS_SITEMAP_OFFICE_OPTION = 'pplus_sitemap_office_urls';
const PPLUS_SITEMAP_NEWS_OPTION   = 'pplus_sitemap_news_urls';
const PPLUS_SITEMAP_CRON_HOOK     = 'pplus_sitemap_rebuild';

/**
 * Doc danh sach URL DA TINH SAN (option, khong goi API o day) - dung boi provider sitemap. Neu
 * chua bao gio chay cron lan nao (option chua ton tai), tra ve mang rong (sitemap rong tam thoi,
 * KHONG bao gio treo request cho nguoi dung/Googlebot).
 *
 * @return string[]
 */
function pplus_sitemap_office_urls(): array {
    $urls = get_option(PPLUS_SITEMAP_OFFICE_OPTION, []);
    return is_array($urls) ? $urls : [];
}

function pplus_sitemap_news_urls(): array {
    $urls = get_option(PPLUS_SITEMAP_NEWS_OPTION, []);
    return is_array($urls) ? $urls : [];
}

/**
 * Goi that su den .NET, chay RIENG trong WP-Cron (khong bi gioi han 30s cua 1 request web thong
 * thuong) - day la noi DUY NHAT thuc su lam viec nang. set_time_limit(0) de phong truong hop host
 * van ap dung gioi han cho ca cron request.
 */
function pplus_sitemap_rebuild(): void {
    if (function_exists('set_time_limit')) {
        @set_time_limit(0);
    }

    update_option(PPLUS_SITEMAP_OFFICE_OPTION, pplus_sitemap_build_office_urls(), false);
    update_option(PPLUS_SITEMAP_NEWS_OPTION, pplus_sitemap_build_news_urls(), false);
}
add_action(PPLUS_SITEMAP_CRON_HOOK, 'pplus_sitemap_rebuild');

/**
 * Tinh toan THAT SU danh sach URL van phong (A2) - 2 tinh da biet (Ha Noi/TP.HCM) x 2 project_type
 * (truyen thong/tron goi). CHI duoc goi tu pplus_sitemap_rebuild() (trong WP-Cron), KHONG BAO GIO
 * goi truc tiep tu 1 request web.
 *
 * @return string[]
 */
function pplus_sitemap_build_office_urls(): array {
    $urls = [];
    foreach (['Hà Nội', 'Hồ Chí Minh'] as $province) {
        foreach ([2, 1] as $project_type) { // 2=truyen thong (mac dinh), 1=tron goi
            $page       = 1;
            $seen_count = 0;
            do {
                $result = pplus_get_office_list([
                    'province'     => $province,
                    'project_type' => $project_type,
                    'page'         => $page,
                ]);
                if (!$result) {
                    break;
                }
                $items = $result['listOffice'] ?? [];
                foreach ($items as $item) {
                    if (!empty($item['urlHerf'])) {
                        $urls[] = home_url($item['urlHerf']);
                    }
                }
                $seen_count += count($items);
                $total = (int) ($result['total_count'] ?? 0);
                $page++;
                // .NET tu gioi han so ket qua/lan (~24, khong theo page_count gui len) - dung
                // SO LUONG THAT SU tra ve moi lan (count($items)), khong gia dinh page_count da gui.
            } while (count($items) > 0 && $seen_count < $total && $page < 200); // gioi han an toan
        }
    }
    return array_values(array_unique($urls));
}

/**
 * Tinh toan THAT SU danh sach URL tin tuc (B3). CHI duoc goi tu pplus_sitemap_rebuild().
 *
 * @return string[]
 */
function pplus_sitemap_build_news_urls(): array {
    $urls       = [];
    $page       = 1;
    $seen_count = 0;
    do {
        $result = pplus_get_blog_list(['page' => $page]);
        if (!$result) {
            break;
        }
        $items = $result['listBlog'] ?? [];
        foreach ($items as $item) {
            if (!empty($item['alias']) && isset($item['id'])) {
                $urls[] = home_url('/blogs/' . $item['alias'] . '-' . (int) $item['id'] . '/');
            }
        }
        $seen_count += count($items);
        $total = (int) ($result['totalItem'] ?? 0);
        $page++;
    } while (count($items) > 0 && $seen_count < $total && $page < 200); // gioi han an toan

    return array_values(array_unique($urls));
}

/**
 * Dang ky lich cron 6 tieng/lan - lan DAU TIEN (chua tung dang ky) se chay ngay sau 10s (khong
 * chan request hien tai) thay vi doi du 6 tieng, de sitemap co du lieu som nhat co the. Cac lan
 * sau (recurring cron da ton tai) khong lam gi them - tranh xep chong nhieu single-event thua.
 */
add_action('init', function () {
    if (wp_next_scheduled(PPLUS_SITEMAP_CRON_HOOK)) {
        return; // da dang ky roi (recurring hoac single dang cho chay), khong lam lai
    }
    wp_schedule_single_event(time() + 10, PPLUS_SITEMAP_CRON_HOOK);
    wp_schedule_event(time() + 300, 'pplus_six_hours', PPLUS_SITEMAP_CRON_HOOK);
});

add_filter('cron_schedules', function (array $schedules): array {
    $schedules['pplus_six_hours'] = ['interval' => 6 * HOUR_IN_SECONDS, 'display' => 'Every 6 hours (PropertyPlus sitemap)'];
    return $schedules;
});

/**
 * Provider dung chung cho WordPress core Sitemaps API (WP_Sitemaps_Provider, co san tu WP 5.5,
 * KHONG can plugin SEO nao). Nhan 1 ham DOC (khong tinh toan gi) tra ve mang URL phang, tu chia
 * trang theo wp_sitemaps_get_max_urls() (mac dinh 2000 URL/file con).
 */
class PPlus_Sitemap_Provider extends WP_Sitemaps_Provider {
    /** @var callable */
    private $urls_callback;

    public function __construct(string $name, callable $urls_callback) {
        $this->name          = $name;
        $this->object_type   = $name;
        $this->urls_callback = $urls_callback;
    }

    public function get_url_list($page_num, $object_subtype = '') {
        $urls     = call_user_func($this->urls_callback);
        $per_page = wp_sitemaps_get_max_urls($this->object_type);
        $offset   = ($page_num - 1) * $per_page;

        $list = [];
        foreach (array_slice($urls, $offset, $per_page) as $url) {
            $list[] = ['loc' => $url];
        }
        return $list;
    }

    public function get_max_num_pages($object_subtype = '') {
        $urls     = call_user_func($this->urls_callback);
        $per_page = max(1, wp_sitemaps_get_max_urls($this->object_type));
        return (int) max(1, ceil(count($urls) / $per_page));
    }
}

add_action('wp_sitemaps_init', function () {
    $server = wp_sitemaps_get_server();
    // TEN PROVIDER KHONG DUOC CO DAU GACH NGANG: rewrite rule mac dinh cua WordPress cho URL
    // "khong subtype" la "^wp-sitemap-([a-z]+?)-(\d+?)\.xml$" - phan ten CHI cho phep [a-z], neu
    // co dau "-" (vd "pplus-offices") WP se hieu nham la dang "type-subtype" (rule khac,
    // "^wp-sitemap-([a-z]+?)-([a-z\d_-]+?)-(\d+?)\.xml$") va tach thanh type="pplus" +
    // subtype="offices" - khong khop provider nao ca, ra 404 (da xac nhan qua debug rewrite rule
    // that). Dung ten lien khong dau gach de tranh hoan toan.
    $server->registry->add_provider('pplusoffices', new PPlus_Sitemap_Provider('pplusoffices', 'pplus_sitemap_office_urls'));
    $server->registry->add_provider('pplusnews', new PPlus_Sitemap_Provider('pplusnews', 'pplus_sitemap_news_urls'));
});

// URL sitemap (vd wp-sitemap-pplusoffices-1.xml) chi hoat dong sau khi rewrite rule tuong ung
// duoc flush - dang ky provider MOI khong tu dong flush, giong quy luat da gap voi moi route rieng
// khac trong du an (xem inc/office-detail-route.php...). Flush 1 lan duy nhat, guard bang 1 option
// rieng (KHONG dung chung "pplus_rewrite_flushed_v1/v2/v3" da dung cho cac route khac).
add_action('init', function () {
    if (get_option('pplus_rewrite_flushed_sitemap_v2') !== '1') {
        flush_rewrite_rules();
        update_option('pplus_rewrite_flushed_sitemap_v2', '1');
    }
}, 20);
