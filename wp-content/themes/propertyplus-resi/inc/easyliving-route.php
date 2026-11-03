<?php
/**
 * C3 - Trang tien ich "Easy living" /{lang}/easy-living/{id} - port easy-living.component (Angular), API {lang}/EasyLiving/_Detail?id=.
 *
 * 3/11/2026 (SEO): ~35 URL kieu nay co backlink tren web chinh (Ahrefs "Best by links": /vi/easy-living/24, /355, /406...) va tung co
 * tu khoa xep hang ("japanese hair salon" ~700 luot/thang, "camelia lounge hanoi"), nhung Resi chua co route nen sau khi chuyen web
 * tat ca 404. Route nay giu lai trang + backlink. Chua co trang danh sach (web chinh cung chi co trang chi tiet; danh sach nam
 * trong khoi "Mọi người cũng tìm kiếm" cua tung trang va tren ban do trang chu).
 *
 * Tien to ngon ngu da bi strip boi inc/language-route.php nen chi can 1 rule khong tien to (giong inc/project-detail-route.php).
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', function () {
    add_rewrite_rule('^easy-living/([0-9]+)/?$', 'index.php?pplus_easy_living=$matches[1]', 'top');
});

add_filter('query_vars', function (array $vars): array {
    $vars[] = 'pplus_easy_living';
    return $vars;
});

// Cung loi is_home()/is_front_page() sai nhu inc/author-route.php - xem ghi chu chi tiet o inc/blog-routes.php.
add_action('parse_query', function (WP_Query $query) {
    if ($query->is_main_query() && get_query_var('pplus_easy_living')) {
        $query->is_home = false;
    }
});

// Trang tu quyet dinh 200 / 503 / 404 (khong co bai viet that) - xem pplus_render_api_error() va inc/response-headers.php.
add_action('template_redirect', function () {
    if (get_query_var('pplus_easy_living')) {
        global $wp_query;
        $wp_query->is_404 = false;
        status_header(200);
    }
}, 0);

add_action('init', function () {
    if (get_option('pplus_rewrite_flushed_easyliving_v1') !== '1') {
        flush_rewrite_rules();
        update_option('pplus_rewrite_flushed_easyliving_v1', '1');
    }
}, 20);

add_filter('template_include', function (string $template): string {
    if (get_query_var('pplus_easy_living')) {
        $custom = PPLUS_THEME_DIR . '/page-easy-living.php';
        if (file_exists($custom)) {
            return $custom;
        }
    }
    return $template;
});

/**
 * Chi tiet 1 dia diem tien ich. API tra {detail, anotherLocationList, detailMarker}; id khong ton tai -> backend 302 ve trang dang nhap
 * (pplus_api_get tra null) nen "khong co" va "API loi" khong phan biet duoc - cung quy uoc voi cac route khac (inc/response-headers.php).
 *
 * @return array|null result cua API, hoac null neu khong co du lieu
 */
function pplus_get_easy_living(int $id): ?array {
    static $cache = [];
    if (array_key_exists($id, $cache)) {
        return $cache[$id];
    }
    $data   = pplus_api_get(pplus_current_lang() . '/EasyLiving/_Detail', ['id' => $id], 600);
    $result = $data['result'] ?? null;
    return $cache[$id] = (is_array($result) && !empty($result['detail']['name'])) ? $result : null;
}

function pplus_easy_living_current(): ?array {
    $id = (int) get_query_var('pplus_easy_living');
    return $id > 0 ? pplus_get_easy_living($id) : null;
}

/** Loai dia diem: backend tra kem so thu tu ("3.Nhà hàng") - bo tien to so khi hien thi / dua vao title, meta. */
function pplus_easy_living_type(array $d): string {
    return trim((string) preg_replace('/^\s*\d+\s*[.\-)]\s*/u', '', (string) ($d['typeDisp'] ?? '')));
}

/** Mo ta ngan cho <meta description>: noi dung mo ta that neu co, nguoc lai ghep tu loai + ten + dia chi. */
function pplus_easy_living_description(array $d): string {
    $text = trim(wp_strip_all_tags((string) ($d['description'] ?? '')));
    if (mb_strlen($text) >= 40) {
        return wp_html_excerpt($text, 155, '...');
    }
    $parts = array_filter([pplus_easy_living_type($d), (string) ($d['name'] ?? ''), (string) ($d['address'] ?? '')]);
    return wp_html_excerpt(implode(' - ', $parts), 155, '...');
}

add_filter('pre_get_document_title', function (string $title): string {
    if (!get_query_var('pplus_easy_living')) {
        return $title;
    }
    $d = pplus_easy_living_current()['detail'] ?? null;
    if (!$d) {
        return $title;
    }
    $type = pplus_easy_living_type($d);
    return $type !== '' ? $d['name'] . ' - ' . $type : $d['name'];
});

add_action('wp_head', function () {
    if (!get_query_var('pplus_easy_living')) {
        return;
    }
    $d = pplus_easy_living_current()['detail'] ?? null;
    if (!$d) {
        return;
    }
    printf('<meta name="description" content="%s" />' . "\n", esc_attr(pplus_easy_living_description($d)));
}, 1);

/** JSON-LD LocalBusiness cho dia diem (ten, dia chi, toa do, dien thoai, anh) - chi dua cac truong co that. */
add_action('wp_head', function () {
    if (!get_query_var('pplus_easy_living')) {
        return;
    }
    $d = pplus_easy_living_current()['detail'] ?? null;
    if (!$d) {
        return;
    }
    $photo = '';
    foreach ((array) ($d['photoList'] ?? []) as $p) {
        if (is_string($p) && $p !== '') {
            $photo = pplus_admin_asset_url($p);
            break;
        }
    }
    $ld = array_filter([
        '@context'  => 'https://schema.org',
        '@type'     => 'LocalBusiness',
        'name'      => $d['name'],
        'image'     => $photo ?: null,
        'telephone' => !empty($d['tel']) ? (string) $d['tel'] : null,
        'url'       => !empty($d['website']) ? (string) $d['website'] : null,
        'address'   => !empty($d['address']) ? array_filter([
            '@type'           => 'PostalAddress',
            'streetAddress'   => (string) $d['address'],
            'addressLocality' => (string) ($d['districtName'] ?? ''),
            'addressRegion'   => (string) ($d['provinceName'] ?? ''),
            'addressCountry'  => 'VN',
        ]) : null,
        'geo'       => (!empty($d['locationLat']) && !empty($d['locationLng'])) ? [
            '@type'     => 'GeoCoordinates',
            'latitude'  => (float) $d['locationLat'],
            'longitude' => (float) $d['locationLng'],
        ] : null,
    ]);
    printf('<script type="application/ld+json">%s</script>' . "\n", wp_json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}, 20);
