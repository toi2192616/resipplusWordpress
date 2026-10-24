<?php
/**
 * 301 cho URL CU -> URL moi, KHOP web chinh (propertyplus.com.vn).
 *
 * Web chinh (Angular SSR, server.ts) co 1 middleware: moi request so khop `req.url` voi cot `alias` cua bang "LogUrlSite"
 * (API .NET `Home/GetLogUrlSite`, ~144 dong: /vi/du-an/..., /vi/du-ans/..., /en/projects/..., /ja/du-ans/... -> URL du an hien tai)
 * va tra 301 sang `aliasRedirect`. Cac URL cu nay Google DA index -> neu ban WP khong lam y het, chung roi vao 404 va mat thu hang.
 *
 * Cach lam o ban WP (khong phu thuoc plugin ppplus-seo-sync, khong goi API luc co request that):
 *  - Du lieu MAC DINH nam san trong inc/legacy-redirects-data.php (snapshot 24/10/2026) -> co hieu luc ngay tu request dau tien.
 *  - WP-Cron lam moi tu API 1 lan/ngay vao option `pplus_legacy_redirect_map` (uu tien hon file). API loi/rong -> giu nguyen ban cu.
 *  - Tra cuu chay RAT SOM (pplus_redirect_to_canonical_request() trong inc/url-canonical.php, truoc khi WP parse request).
 *
 * Luu y da xac nhan bang test that: endpoint `Home/GetLogUrlSite` KHONG co tien to "{lang}/" khi goi tu theme (goi voi tien to
 * thi ra trang dang nhap admin), response co bao { result: [...] }, moi dong {alias, aliasRedirect, lang, isActive}.
 * Khac web chinh: khong phan biet hoa/thuong, bo "/" cuoi, giu nguyen query string (utm...) khi chuyen huong.
 */

if (!defined('ABSPATH')) {
    exit;
}

const PPLUS_LEGACY_REDIRECT_CRON_HOOK = 'pplus_legacy_redirect_refresh';
const PPLUS_LEGACY_REDIRECT_OPTION    = 'pplus_legacy_redirect_map';
/** Dich SAI trong bang cua web chinh: alias du an "istay-hotel-apartment-5" bi hieu nham thanh id=5 (thuc te la ten, id du an = 1001 - xem sitemap web chinh). */
const PPLUS_LEGACY_REDIRECT_OVERRIDES = [
    '/vi/du-an/istay-hotel-apartment-5-dong-me-1001' => '/vi/cho-thue-can-ho/ha-noi/nam-tu-liem/istay-hotel-apartment-5-1001',
];

/** Khoa tra cuu cua 1 duong dan: giai ma %-encoding, chu thuong, bo "/" o 2 dau (vd "/vi/du-ans/abc/" -> "/vi/du-ans/abc"). */
function pplus_legacy_redirect_key(string $path): string {
    $path = strtolower(rawurldecode(trim($path)));
    return '/' . trim($path, '/');
}

/** Bang chuyen huong hien hanh: option (do cron cap nhat) neu co, khong thi snapshot di kem theme. */
function pplus_legacy_redirect_map(): array {
    static $map = null;
    if ($map !== null) {
        return $map;
    }
    $stored = get_option(PPLUS_LEGACY_REDIRECT_OPTION, []);
    if (is_array($stored) && $stored) {
        $map = $stored;
    } else {
        $bundled = include __DIR__ . '/legacy-redirects-data.php';
        $map     = is_array($bundled) ? $bundled : [];
    }
    // Sua tay nhung dich SAI trong bang cua web chinh (de nguyen thi 301 -> 404): ap dung ca cho ban cron lan snapshot.
    return $map = array_merge($map, array_intersect_key(PPLUS_LEGACY_REDIRECT_OVERRIDES, $map));
}

/** Duong dan dich neu `$path` la URL cu can 301, nguoc lai null. */
function pplus_legacy_redirect_target(string $path): ?string {
    $map = pplus_legacy_redirect_map();
    $key = pplus_legacy_redirect_key($path);
    return isset($map[$key]) && $map[$key] !== $key ? $map[$key] : null;
}

/** Dua danh sach dong API ({alias, aliasRedirect, isActive}) ve bang khoa => duong dan dich. Dong dau tien khop thang (nhu web chinh), tru khi dich thieu "-<id>" du an. */
function pplus_legacy_redirect_build_map(array $rows): array {
    $map = [];
    foreach ($rows as $row) {
        if (empty($row['isActive']) || empty($row['alias']) || empty($row['aliasRedirect'])) {
            continue;
        }
        $key    = pplus_legacy_redirect_key((string) preg_replace('#^https?://[^/]+#i', '', (string) $row['alias']));
        $target = '/' . trim((string) preg_replace('#^https?://[^/]+#i', '', (string) $row['aliasRedirect']), '/');
        if ($key === pplus_legacy_redirect_key($target)) {
            continue;
        }
        // 3 URL cu co 2 dong: dong dau tro toi trang du an THIEU id (khong ton tai) -> uu tien dong co "-<id>".
        if (!isset($map[$key]) || (!preg_match('#-[0-9]+$#', $map[$key]) && preg_match('#-[0-9]+$#', $target))) {
            $map[$key] = $target;
        }
    }
    return $map;
}

/** Chay trong WP-Cron: goi API, thay bang chuyen huong. API loi / tra rong -> KHONG dong vao ban dang co. */
function pplus_legacy_redirect_refresh(): void {
    $response = pplus_api_get('Home/GetLogUrlSite', [], 0);
    $rows     = $response['result'] ?? null;
    if (!is_array($rows)) {
        return;
    }
    $map = pplus_legacy_redirect_build_map($rows);
    if (count($map) < 10) { // API tra gan nhu rong = nhieu kha nang loi/doi cau truc, khong ghi de
        return;
    }
    update_option(PPLUS_LEGACY_REDIRECT_OPTION, $map, true);
    update_option('pplus_legacy_redirect_refreshed_at', time(), false);
}
add_action(PPLUS_LEGACY_REDIRECT_CRON_HOOK, 'pplus_legacy_redirect_refresh');

/** Lan dau: chay sau 15s (khong chan request hien tai); sau do 1 lan/ngay (danh sach hiem khi doi). */
add_action('init', function () {
    if (wp_next_scheduled(PPLUS_LEGACY_REDIRECT_CRON_HOOK)) {
        return;
    }
    wp_schedule_single_event(time() + 15, PPLUS_LEGACY_REDIRECT_CRON_HOOK);
    wp_schedule_event(time() + 3600, 'daily', PPLUS_LEGACY_REDIRECT_CRON_HOOK);
});
