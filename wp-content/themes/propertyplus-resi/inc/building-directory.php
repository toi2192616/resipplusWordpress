<?php
/**
 * Trang "Tat ca toa nha" /{vi|en|ja}/buildings (21/11/2026) - danh sach MOI toa nha (du an) dang co can cho thue, xep theo quan.
 *
 * VI SAO: menu chi liet ke 12 toa tuyen chon (Admin: bai viet du an co "Hien thi Menu = Theo toa nha"); ~840 trang du an con lai hau nhu khong co
 * link noi bo nao -> Google "da phat hien, chua lap chi muc" (~2.400 URL). Trang nay (co link o menu tren MOI trang) cho moi toa nha 1 link that.
 *
 * DU LIEU: file tinh data/building-directory.json (tao bang `php tools/gen-building-directory.php`, ~5-8 phut, chi doc API cong khai).
 * Khong goi API luc co request (server that cham + WP-Cron nhay cam - xem inc/sitemap-admin.php, 16/11). Chi gom du an co >= 1 can ho dang dang
 * (du an "vo" tu noindex - inc/hidden-projects.php - khong dua vao danh sach). File thieu/rong -> trang hien thong bao + noindex, khong loi.
 *
 * URL dung chung 3 ngon ngu (goc "buildings" nam trong PPLUS_ROOTS_SHARED, inc/url-canonical.php) nen hreflang/canonical tu dung.
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Chon van ban theo ngon ngu hien tai: [vi, en, ja]. */
function pplus_bd_t(array $t): string {
    $lang = pplus_current_lang();
    if ($lang === 'ja') {
        return (string) ($t[2] ?? $t[1] ?? $t[0]);
    }
    return (string) ($lang === 'en' ? ($t[1] ?? $t[0]) : $t[0]);
}

add_action('init', function () {
    add_rewrite_rule('^buildings/?$', 'index.php?pplus_building_dir=1', 'top');
});

add_filter('query_vars', function (array $vars): array {
    $vars[] = 'pplus_building_dir';
    return $vars;
});

// Cung loi is_home()/is_front_page() sai nhu cac route rieng khac (xem inc/blog-routes.php).
add_action('parse_query', function (WP_Query $query) {
    if ($query->is_main_query() && get_query_var('pplus_building_dir')) {
        $query->is_home = false;
    }
});

// Trang tu quyet dinh 200 (khong co bai viet that).
add_action('template_redirect', function () {
    if (get_query_var('pplus_building_dir')) {
        global $wp_query;
        $wp_query->is_404 = false;
        status_header(200);
    }
}, 0);

add_action('init', function () {
    if (get_option('pplus_rewrite_flushed_buildings_v1') !== '1') {
        flush_rewrite_rules();
        update_option('pplus_rewrite_flushed_buildings_v1', '1');
    }
}, 20);

add_filter('template_include', function (string $template): string {
    if (get_query_var('pplus_building_dir')) {
        $custom = PPLUS_THEME_DIR . '/page-building-directory.php';
        if (file_exists($custom)) {
            return $custom;
        }
    }
    return $template;
});

/** Noi dung file du lieu (mang rong neu thieu/hong). */
function pplus_building_directory_raw(): array {
    static $data = null;
    if ($data !== null) {
        return $data;
    }
    $data = [];
    $file = PPLUS_THEME_DIR . '/data/building-directory.json';
    if (is_readable($file)) {
        $j = json_decode((string) file_get_contents($file), true);
        if (is_array($j)) {
            $data = $j;
        }
    }
    $data = (array) apply_filters('pplus_building_directory_data', $data); // cho kiem thu
    return $data;
}

/**
 * Cac nhom theo quan cua 1 ngon ngu: quan nhieu toa nhat dung truoc; trong quan xep theo ten (khong dau).
 *
 * @return array<int,array{label:string,items:array<int,array{id:int,name:string,url:string}>}>
 */
function pplus_building_directory_groups(?string $lang = null): array {
    $lang   = $lang ?: pplus_current_lang();
    $rows   = (array) (pplus_building_directory_raw()[$lang] ?? []);
    $other  = pplus_bd_t(['Khác', 'Other', 'その他']);
    $groups = [];
    foreach ($rows as $r) {
        if (!is_array($r)) {
            continue;
        }
        $id   = (int) ($r[0] ?? 0);
        $name = trim((string) ($r[1] ?? ''));
        $dist = trim((string) ($r[2] ?? ''));
        $href = trim((string) ($r[3] ?? ''), '/');
        if ($id <= 0 || $name === '' || $href === '' || (function_exists('pplus_project_is_hidden') && pplus_project_is_hidden($id))) {
            continue;
        }
        $groups[$dist !== '' ? $dist : $other][] = ['id' => $id, 'name' => $name, 'url' => home_url(pplus_lang_prefix($lang) . '/' . $href)];
    }
    foreach ($groups as &$items) {
        usort($items, static fn(array $a, array $b): int => strcasecmp(remove_accents($a['name']), remove_accents($b['name'])));
    }
    unset($items);
    uksort($groups, static function (string $a, string $b) use ($groups, $other): int {
        if ($a === $other || $b === $other) {
            return $a === $other ? 1 : -1; // "Khac" luon o cuoi
        }
        return count($groups[$b]) <=> count($groups[$a]) ?: strnatcasecmp(remove_accents($a), remove_accents($b));
    });
    $out = [];
    foreach ($groups as $label => $items) {
        $out[] = ['label' => (string) $label, 'items' => $items];
    }
    return $out;
}

function pplus_building_directory_count(?string $lang = null): int {
    $n = 0;
    foreach (pplus_building_directory_groups($lang) as $g) {
        $n += count($g['items']);
    }
    return $n;
}

/** Tieu de, mo ta, H1, doan mo dau (3 ngon ngu; tieng Nhat dung kinh ngu). */
function pplus_building_directory_texts(int $n): array {
    return [
        'title' => pplus_bd_t([
            'Danh Sách Tòa Nhà Cho Thuê Căn Hộ Tại Hà Nội | Property Plus',
            'All Apartment Buildings for Rent in Hanoi | Property Plus',
            'ハノイの賃貸アパート・マンション建物一覧｜Property Plus',
        ]),
        'h1' => pplus_bd_t([
            'Danh sách tòa nhà, chung cư cho thuê tại Hà Nội',
            'Apartment Buildings for Rent in Hanoi',
            'ハノイの賃貸アパート・マンション建物一覧',
        ]),
        'description' => pplus_bd_t([
            sprintf('Danh sách %d tòa nhà, dự án căn hộ đang có căn cho thuê tại Hà Nội, xếp theo quận. Bấm vào tên tòa nhà để xem giá thuê và các căn hộ đang trống.', $n),
            sprintf('Browse %d apartment buildings and projects with apartments for rent in Hanoi, grouped by district. Select a building to see rental prices and available apartments.', $n),
            sprintf('%d棟の賃貸アパート・マンションを区ごとにご紹介しております。建物名を選ぶと、家賃や空室のお部屋をご覧いただけます。', $n),
        ]),
        'intro' => pplus_bd_t([
            'Dưới đây là toàn bộ tòa nhà và dự án có căn hộ đang cho thuê tại Property Plus, xếp theo quận. Chọn một tòa nhà để xem căn hộ, giá thuê và thông tin chi tiết.',
            'Below is every building and project that currently has apartments for rent with Property Plus, grouped by district. Select a building to see its apartments, rental prices and details.',
            'Property Plusで現在ご紹介できる空室のある建物を、区ごとに一覧にしました。建物名をクリックすると、お部屋の情報や家賃、詳細をご覧いただけます。',
        ]),
    ];
}

add_filter('pre_get_document_title', function (string $title): string {
    if (!get_query_var('pplus_building_dir')) {
        return $title;
    }
    return pplus_building_directory_texts(pplus_building_directory_count())['title'];
});

add_action('wp_head', function () {
    if (!get_query_var('pplus_building_dir')) {
        return;
    }
    $n = pplus_building_directory_count();
    if ($n < 1) {
        return; // khong co du lieu -> noindex qua bo loc wp_robots ben duoi, khong in mo ta
    }
    printf('<meta name="description" content="%s" />' . "\n", esc_attr(pplus_building_directory_texts($n)['description']));
}, 1);

// Khong co du lieu (file thieu/rong) -> noindex, khong de Google luu trang rong.
add_filter('wp_robots', static function (array $robots): array {
    if (get_query_var('pplus_building_dir') && pplus_building_directory_count() < 1) {
        unset($robots['index']);
        $robots['noindex'] = true;
        $robots['follow']  = true;
    }
    return $robots;
}, 99);

/** Them 3 URL cua trang vao sitemap con "pages" (noi dung lay tu Admin, inc/sitemap-admin.php) neu chua co. */
function pplus_sitemap_add_building_directory(string $xml): string {
    if (strpos($xml, '</urlset>') === false || !pplus_building_directory_raw()) {
        return $xml;
    }
    $built = (string) (pplus_building_directory_raw()['built'] ?? '');
    $add   = '';
    foreach (['vi', 'en', 'ja'] as $lang) {
        $loc = home_url('/' . $lang . '/buildings');
        if (strpos($xml, '<loc>' . $loc . '</loc>') !== false || !pplus_building_directory_groups($lang)) {
            continue;
        }
        $add .= "  <url>\n    <loc>" . esc_url($loc) . "</loc>\n" . ($built !== '' ? '    <lastmod>' . esc_html($built) . "</lastmod>\n" : '') . "    <priority>0.7</priority>\n  </url>\n";
    }
    return $add === '' ? $xml : str_replace('</urlset>', $add . '</urlset>', $xml);
}
