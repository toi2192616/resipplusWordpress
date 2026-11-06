<?php
/**
 * Sitemap lay tu ADMIN (backend .NET) - thay cho bo sinh trong inc/sitemap.php (WP-Cron). 5/11/2026, theo Dev A: "Admin da co route
 * sitemap roi, khong tu sinh".
 *
 * Admin phuc vu san (da kiem 5/11/2026):
 *   GET {api-base}/sitemap.xml                          <urlset> liet ke 6 file con (dung dinh dang web chinh cu, KHONG phai <sitemapindex>)
 *   GET {api-base}/sitemaps/sitemap_{products,posts,pages,quan,type,villa}.xml
 * Moi <loc> trong do da la URL cua domain chinh (https://propertyplus.com.vn/...) nen khong can viet lai.
 *
 * Theme chi PROXY noi dung do ra dung cac URL cu tren DOMAIN CHINH: /sitemap.xml va /sitemaps/sitemap_*.xml. KHONG tro robots.txt sang domain
 * Admin va KHONG redirect: Search Console da nop tung file /sitemaps/sitemap_*.xml cua propertyplus.com.vn (Google doc thanh cong, vd products
 * 2.550 URL) - doi URL hay chuyen host se mat lien tuc va sitemap host khac chi duoc chap nhan khi xac minh cheo. (Cac file tren domain chinh
 * hien la ban chup tinh cu cua web Angular: products 2.550 URL trong khi Admin dang co 2.562 - proxy lay luon ban moi.)
 *
 * Khong phu thuoc WP-Cron/loopback (diem yeu cua bo sinh cu: cron khong chay thi sitemap rong). Cache transient: tuoi < 30 phut dung ngay;
 * qua han thi lay lai tu Admin; Admin loi thi phuc vu ban cu (toi da 7 ngay); chua co ban nao -> 503 + Retry-After (Google hieu la tam thoi).
 * Giu nguyen nhu Admin, ke ca danh sach khong "sach" (villa 301/410, vai URL rac trong "pages"): sua o nguon (Admin), khong lam lech giua
 * hai noi. Bo sinh cu van con trong inc/sitemap.php: dat define('PPLUS_SITEMAP_SOURCE', 'theme') trong wp-config.php de bat lai.
 */

if (!defined('ABSPATH')) {
    exit;
}

const PPLUS_ADMIN_SITEMAP_TTL = 1800; // giay: tuoi toi da cua ban cache duoc coi la "moi"

// Khong de WordPress them mot sitemap thu hai (/wp-sitemap.xml) canh tranh voi sitemap cua Admin.
add_filter('wp_sitemaps_enabled', '__return_false');

add_action('init', function () {
    add_rewrite_rule('^sitemap\.xml$', 'index.php?pplus_admin_sitemap=index', 'top');
    // Chi 6 ten file con cua web chinh (Admin them file moi thi bo sung o day): ten la thi 404 gon cua WP thay vi do Admin tra gi cung phuc vu theo.
    add_rewrite_rule('^sitemaps/sitemap_(products|posts|pages|quan|type|villa)\.xml$', 'index.php?pplus_admin_sitemap=$matches[1]', 'top');
});

add_filter('query_vars', function (array $vars): array {
    $vars[] = 'pplus_admin_sitemap';
    return $vars;
});

add_action('init', function () {
    if (get_option('pplus_rewrite_flushed_sitemap_admin_v1') !== '1') {
        flush_rewrite_rules();
        update_option('pplus_rewrite_flushed_sitemap_admin_v1', '1');
    }
    // Bo sinh cu (neu truoc day da len lich tren server nay) khong con chay: go lich de khong con su kien mo coi trong bang cron.
    foreach (['pplus_sitemap_start', 'pplus_sitemap_step'] as $hook) {
        if (wp_next_scheduled($hook)) {
            wp_clear_scheduled_hook($hook);
        }
    }
}, 20);

/**
 * Lay noi dung 1 sitemap cua Admin (qua cache). $name = 'index' hoac ten file con (products, posts, pages, quan, type, villa).
 *
 * @return array{0:?string,1:string} [noi dung hoac null, trang thai: hit | miss | stale | notfound | none]
 */
function pplus_admin_sitemap_get(string $name): array {
    $key    = 'pplus_admsm_' . $name;
    $cached = get_transient($key);
    $has    = is_array($cached) && isset($cached['at'], $cached['body']) && $cached['body'] !== '';
    if ($has && (time() - (int) $cached['at']) < PPLUS_ADMIN_SITEMAP_TTL) {
        return [(string) $cached['body'], 'hit'];
    }

    $path = $name === 'index' ? '/sitemap.xml' : '/sitemaps/sitemap_' . $name . '.xml';
    $resp = wp_remote_get(pplus_api_base_url() . $path, ['timeout' => 25, 'headers' => ['Accept' => 'application/xml, text/xml']]);
    $code = is_wp_error($resp) ? 0 : (int) wp_remote_retrieve_response_code($resp);
    if ($code === 404) {
        return [null, 'notfound'];
    }
    // Bo BOM UTF-8 (Admin tra "\xEF\xBB\xBF<?xml ...") - hop le nhung khong can thiet, de sach cho trinh phan tich nghiem.
    $body = $code === 200 ? ltrim((string) wp_remote_retrieve_body($resp), "\xEF\xBB\xBF \t\r\n") : '';
    if ($body !== '' && preg_match('#<(urlset|sitemapindex)\b#', substr($body, 0, 2000))) {
        set_transient($key, ['body' => $body, 'at' => time()], 7 * DAY_IN_SECONDS);
        return [$body, 'miss'];
    }
    if ($has) {
        return [(string) $cached['body'], 'stale']; // Admin loi/cham: van phuc vu ban cu con hon tra loi rong
    }
    return [null, 'none'];
}

add_action('template_redirect', function () {
    $which = (string) get_query_var('pplus_admin_sitemap');
    if ($which === '') {
        return;
    }
    [$body, $state] = pplus_admin_sitemap_get($which);
    header('X-Pplus-Sitemap-Source: admin');
    header('X-Pplus-Sitemap-Cache: ' . $state);
    if ($body === null) {
        $notfound = $state === 'notfound';
        status_header($notfound ? 404 : 503);
        if (!$notfound) {
            header('Retry-After: 300');
        }
        header('Content-Type: text/plain; charset=UTF-8');
        header('Cache-Control: no-store');
        echo $notfound ? 'Not found' : 'Sitemap temporarily unavailable';
        exit;
    }
    status_header(200);
    header('Content-Type: application/xml; charset=UTF-8');
    header('Cache-Control: public, max-age=600');
    echo $body;
    exit;
}, 0);
