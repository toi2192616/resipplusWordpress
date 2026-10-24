<?php
/**
 * Header HTTP cho HTML trang - phuc vu nginx proxy_cache va Googlebot (1/10/2026; port sang Resi 24/10/2026).
 *
 * PORT NGUYEN VAN tu ban Van phong (commit ba05c18). Resi co CUNG van de: backend .NET tra 302 (trang dang nhap admin) cho
 * bai viet/can ho KHONG TON TAI nen "khong ton tai" va "API loi" deu ra null; rieng Resi do that (24/10): loi API tam thoi
 * ngoai y muon khien trang that tra 404 (apartment detail) hoac 200 + thong bao rong - Googlebot gap luc do se bo/index
 * trang mong. Xem them pplus_api_http() trong inc/api-client.php (noi danh dau).
 *
 * VAN DE: khi API .NET loi/timeout luc render, cac template van tra HTTP 200 (hoac 404 o
 * page-reference/page-landing/page-office-detail/page-street) kem dong "Du lieu dang duoc cap
 * nhat". 1) Neu nginx bat proxy_cache thi ban loi nay bi LUU va tra cho moi khach toi 30 phut.
 * 2) Voi Googlebot: 200 + trang rong = bi index thanh trang mong; 404 = tin hieu "trang da bien
 * mat". Ca hai deu hai thu hang hon nhieu so voi 1 lan loi tam thoi.
 *
 * GIAI PHAP (chi anh huong response CO loi, response binh thuong khong doi gi):
 *  - Loi CHAC CHAN tam thoi cua API (loi mang/timeout, HTTP 408/429/502/503/504 - xem pplus_api_get()/
 *    pplus_api_post_json()) -> danh dau 'api-failure'.
 *  - Tin hieu MO HO (HTTP 5xx khac, body khong phai JSON) -> 'api-error': CHI gan no-store, KHONG doi
 *    ma trang thai. Ly do: voi toa nha/bai viet KHONG TON TAI, backend .NET chuyen huong 302 sang trang
 *    dang nhap admin (HTML, HTTP 200) - da do thuc te 1/10/2026 - nen "body khong phai JSON" khong
 *    chung to la su co. HTTP 4xx -> khong danh dau gi (khong ton tai that su, giu hanh vi 404 cu).
 *  - pplus_render_api_error() (template tu in dong bao loi cho khach) -> danh dau 'error-notice'.
 *  - Co bat ky danh dau nao (ke ca 'api-error') -> "Cache-Control: no-store" (nginx/trinh duyet khong luu ban loi;
 *    nhung nginx co "proxy_cache_use_stale ... updating http_503" van tra BAN TOT DA LUU truoc do
 *    cho khach thay vi ban loi).
 *  - Co 'api-failure' VA 'error-notice' (API sap that + khach dang thay thong bao loi) -> doi sang HTTP 503 +
 *    "Retry-After: 120": Google hieu la loi tam thoi, thu lai sau, GIU NGUYEN trang da index.
 *    Tat bang define('PPLUS_DEGRADED_HTTP_503', false) trong wp-config.php neu can hoan tac.
 *
 * Header chi gan duoc khi chua gui byte nao -> bat output buffering (ob_start) tu dau request
 * front-end va dang ky header_register_callback(): PHP goi callback DUNG LUC sap gui header
 * (sau khi ca trang da render xong) nen biet duoc API co loi hay khong. Khong dung flush() o dau
 * trong theme nen khong mat tinh nang "stream som" nao (da do thuc te: header phan hoi toi sau
 * ~90% thoi gian render vi PHP/IIS da buffer san).
 *
 * ETag: them "ETag" (md5 cua body) cho response 200 binh thuong. nginx proxy_cache dung no de
 * tra 304 cho Googlebot/trinh duyet quay lai ma khong gui lai ~60KB (Googlebot ho tro
 * If-None-Match - tiet kiem crawl budget). Khong xu ly 304 o PHP: khi co proxy_cache nginx da
 * tu lam, con khi khong co cache thi PHP van phai render nen khong tiet kiem them duoc gi.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ghi/doc cac danh dau "response bi suy giam" cua request hien tai.
 *
 * @param string|null $set 'api-failure' | 'error-notice' de danh dau; null chi doc.
 * @return array<string,true> cac danh dau da co.
 */
function pplus_response_flags(?string $set = null): array {
    static $flags = [];
    if ($set !== null) {
        $flags[$set] = true;
    }
    return $flags;
}

/** Callback header_register_callback(): chay ngay truoc khi PHP gui header (sau khi render xong). */
function pplus_apply_response_headers(): void {
    $flags = pplus_response_flags();
    if (!$flags) {
        return;
    }
    header('Cache-Control: no-store, max-age=0');
    header('X-PPlus-Degraded: ' . implode(',', array_keys($flags)));

    $use_503 = !defined('PPLUS_DEGRADED_HTTP_503') || PPLUS_DEGRADED_HTTP_503;
    if ($use_503 && isset($flags['api-failure'], $flags['error-notice'])) {
        // status_header() cua WP (khong dung http_response_code()): WP da dat dong trang thai bang
        // header("HTTP/1.1 404 ..."), http_response_code() khong ghi de duoc dong nay.
        status_header(503);
        header('Retry-After: 120');
    }
}

/** Handler cua ob_start(): chi them ETag cho response 200 binh thuong, khong dong vao body. */
function pplus_response_output_handler(string $body, int $phase) {
    // Chi xu ly khi toan bo body den 1 lan (khong co flush giua chung); khac di thi giu nguyen.
    $whole = PHP_OUTPUT_HANDLER_START | PHP_OUTPUT_HANDLER_FINAL;
    if (($phase & $whole) !== $whole || $body === '' || headers_sent()) {
        return false;
    }
    if (pplus_response_flags() || (int) http_response_code() !== 200) {
        return false;
    }
    // Chi HTML (sitemap/robots/file khac khong can).
    foreach (headers_list() as $line) {
        if (stripos($line, 'content-type:') === 0 && stripos($line, 'text/html') === false) {
            return false;
        }
        if (stripos($line, 'etag:') === 0) {
            return false; // da co ETag tu noi khac, khong ghi de
        }
    }
    header('ETag: "' . md5($body) . '"');
    return false;
}

add_action('template_redirect', function () {
    if (headers_sent() || is_admin() || wp_doing_ajax() || wp_doing_cron() || is_feed() || is_robots()) {
        return;
    }
    if (defined('REST_REQUEST') && REST_REQUEST) {
        return;
    }
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method !== 'GET' && $method !== 'HEAD') {
        return;
    }
    ob_start('pplus_response_output_handler');
    header_register_callback('pplus_apply_response_headers');
}, 0);
