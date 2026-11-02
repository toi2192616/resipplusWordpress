<?php
/**
 * Anh tu CMS/API (admin./app.propertyplus.com.vn - JPG/PNG) -> WebP cache tren chinh server WordPress (2/11/2026).
 *
 * Vi sao: PageSpeed ("Cai thien viec phan phoi hinh anh" 1.160 KiB, "Tranh cac tai nguyen lon" 2.825 KiB) - anh bai SEO (800x600 JPG
 * ~100-160KB/anh x 6), anh the can ho (376x300 JPG 30-55KB x 18+) deu chua phai WebP. Nguoi dung noi viec doi kich co/WebP phia upload se
 * ban voi Dev A; day la lop BO SUNG phia theme, khong dong vao backend va tu het tac dung khi nguon da la WebP (chi xu ly .jpg/.jpeg/.png).
 *
 * Cach lam (KHONG lam cham request nao):
 *  - pplus_webp_url($url, $max_w): da co ban WebP trong uploads/pplus-img/ -> tra URL ban do; chua co -> tra NGUYEN URL goc va xep hang doi tao.
 *  - Tao ban WebP SAU KHI da tra trang (fastcgi_finish_request neu co, khong thi WP-Cron) - toi da 12 anh/lan, moi anh tai (timeout 5s,
 *    toi da 4MB) -> GD imagewebp (chat luong 80, thu nho neu rong hon $max_w). Neu WebP khong nho hon it nhat 10% hoac nguon loi -> danh dau
 *    ".skip" 6 gio (khong thu lai lien tuc), giu anh goc.
 *  - Chi xu ly host cua chinh he thong (asset base + api base) - khong bao gio tai URL la.
 *  - File cu hon 60 ngay duoc don hang ngay (cron) - se tu tao lai khi can.
 * Tat han: define('PPLUS_WEBP', false) trong wp-config.php (hoac xoa thu muc uploads/pplus-img de lam sach cache).
 */

if (!defined('ABSPATH')) {
    exit;
}

const PPLUS_WEBP_QUALITY   = 80;
const PPLUS_WEBP_MAX_BYTES = 4194304; // nguon > 4MB: bo qua
const PPLUS_WEBP_BATCH     = 12;
const PPLUS_WEBP_CRON_HOOK = 'pplus_webp_batch';
const PPLUS_WEBP_CLEAN_HOOK = 'pplus_webp_cleanup';

/** Thu muc + URL cache (uploads/pplus-img) hoac null neu khong dung duoc. */
function pplus_webp_dir(): ?array {
    static $cached = false;
    if ($cached !== false) {
        return $cached;
    }
    $uploads = wp_upload_dir(null, false);
    if (!empty($uploads['error'])) {
        return $cached = null;
    }
    $dir = $uploads['basedir'] . '/pplus-img';
    if (!is_dir($dir) && !wp_mkdir_p($dir)) {
        return $cached = null;
    }
    return $cached = ['dir' => $dir, 'url' => set_url_scheme($uploads['baseurl'] . '/pplus-img')];
}

function pplus_webp_enabled(): bool {
    static $ok = null;
    if ($ok === null) {
        $ok = (!defined('PPLUS_WEBP') || PPLUS_WEBP)
            && function_exists('imagewebp') && function_exists('imagecreatefromstring') && function_exists('imagescale')
            && pplus_webp_dir() !== null;
    }
    return $ok;
}

/** Host duoc phep tai ve (chinh la domain asset/api cua he thong). */
function pplus_webp_allowed_host(string $host): bool {
    static $hosts = null;
    if ($hosts === null) {
        $hosts = [];
        foreach ([pplus_asset_base_url(), pplus_api_base_url()] as $base) {
            $h = wp_parse_url($base, PHP_URL_HOST);
            if ($h) {
                $hosts[strtolower($h)] = true;
            }
        }
    }
    $host = strtolower($host);
    // Anh can ho nam o app.propertyplus.com.vn (photoList), anh CMS o admin.: cho phep ca ho domain propertyplus.com.vn / propertyplus.vn.
    return isset($hosts[$host]) || (bool) preg_match('/(^|\.)propertyplus\.(com\.vn|vn)$/', $host);
}

/** Khoa file cache cua 1 anh (url + chieu rong toi da). */
function pplus_webp_key(string $url, int $max_w): string {
    return md5($url) . ($max_w > 0 ? '-w' . $max_w : '');
}

/**
 * URL anh WebP da cache neu co; chua co thi tra ve $url goc va xep hang doi tao SAU khi tra trang.
 * Khong bao gio chan request hien tai.
 */
function pplus_webp_url(string $url, int $max_w = 0): string {
    if ($url === '' || !pplus_webp_enabled() || !preg_match('#^https?://#i', $url)) {
        return $url;
    }
    $parts = wp_parse_url($url);
    $host  = $parts['host'] ?? '';
    $path  = $parts['path'] ?? '';
    if ($host === '' || !pplus_webp_allowed_host($host) || !preg_match('#\.(jpe?g|png)$#i', $path)) {
        return $url;
    }
    $dirs = pplus_webp_dir();
    $key  = pplus_webp_key($url, $max_w);
    $file = $dirs['dir'] . '/' . $key . '.webp';
    if (is_file($file)) {
        return $dirs['url'] . '/' . $key . '.webp';
    }
    $skip = $file . '.skip';
    if (is_file($skip) && filemtime($skip) > time() - 6 * HOUR_IN_SECONDS) {
        return $url;
    }
    pplus_webp_enqueue($url, $max_w);
    return $url;
}

/** Hang doi tao WebP cua request hien tai, xu ly sau khi tra trang. */
function pplus_webp_enqueue(string $url, int $max_w): void {
    static $queue = [];
    static $registered = false;
    $key = pplus_webp_key($url, $max_w);
    if (isset($queue[$key]) || count($queue) >= PPLUS_WEBP_BATCH) {
        return;
    }
    // Chong xep trung giua cac request dong thoi (khoa 10 phut).
    if (get_transient('pplus_webpq_' . $key) !== false) {
        return;
    }
    set_transient('pplus_webpq_' . $key, 1, 10 * MINUTE_IN_SECONDS);
    $queue[$key] = [$url, $max_w];
    if ($registered) {
        return;
    }
    $registered = true;
    register_shutdown_function(static function () use (&$queue) {
        if (!$queue) {
            return;
        }
        $jobs = array_values($queue);
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
            pplus_webp_process($jobs);
        } elseif (!wp_next_scheduled(PPLUS_WEBP_CRON_HOOK, [$jobs])) {
            wp_schedule_single_event(time(), PPLUS_WEBP_CRON_HOOK, [$jobs]);
        }
    });
}

/**
 * Tai + doi sang WebP cac anh trong $jobs ([url, max_w]). Chay ngoai luong tra trang (shutdown sau fastcgi_finish_request hoac WP-Cron).
 *
 * @param array<int,array{0:string,1:int}> $jobs
 */
function pplus_webp_process(array $jobs): void {
    if (!pplus_webp_enabled()) {
        return;
    }
    $dirs = pplus_webp_dir();
    if (function_exists('set_time_limit')) {
        @set_time_limit(60);
    }
    foreach (array_slice($jobs, 0, PPLUS_WEBP_BATCH) as $job) {
        [$url, $max_w] = [(string) ($job[0] ?? ''), (int) ($job[1] ?? 0)];
        $parts = wp_parse_url($url);
        if ($url === '' || empty($parts['host']) || !pplus_webp_allowed_host($parts['host'])) {
            continue;
        }
        $key  = pplus_webp_key($url, $max_w);
        $file = $dirs['dir'] . '/' . $key . '.webp';
        if (is_file($file)) {
            continue;
        }
        if (!pplus_webp_convert($url, $file, $max_w)) {
            @touch($file . '.skip');
        }
        delete_transient('pplus_webpq_' . $key);
    }
}
add_action(PPLUS_WEBP_CRON_HOOK, 'pplus_webp_process');

/** Tai 1 anh, doi sang WebP vao $file (ghi tmp roi rename). false = giu anh goc. */
function pplus_webp_convert(string $url, string $file, int $max_w): bool {
    // Ten file co dau cach/tieng Viet: ma hoa tung byte ngoai ASCII in duoc (giu nguyen cac %XX da co).
    $url = (string) preg_replace_callback('/[^!-~]/', static fn (array $m): string => rawurlencode($m[0]), $url);
    $res = wp_remote_get($url, ['timeout' => 5, 'limit_response_size' => PPLUS_WEBP_MAX_BYTES + 1, 'headers' => ['Accept' => 'image/*']]);
    if (is_wp_error($res) || (int) wp_remote_retrieve_response_code($res) !== 200) {
        return false;
    }
    $body = (string) wp_remote_retrieve_body($res);
    $size = strlen($body);
    if ($size < 512 || $size > PPLUS_WEBP_MAX_BYTES) {
        return false;
    }
    $im = @imagecreatefromstring($body);
    if (!$im) {
        return false;
    }
    if ($max_w > 0 && imagesx($im) > $max_w) {
        $scaled = imagescale($im, $max_w, -1, IMG_BICUBIC);
        if ($scaled) {
            imagedestroy($im);
            $im = $scaled;
        }
    }
    imagepalettetotruecolor($im);
    imagealphablending($im, true);
    imagesavealpha($im, true);
    $tmp = $file . '.' . uniqid('', true) . '.tmp';
    $ok  = @imagewebp($im, $tmp, PPLUS_WEBP_QUALITY);
    imagedestroy($im);
    if (!$ok || !is_file($tmp)) {
        @unlink($tmp);
        return false;
    }
    // Chi dung neu nho hon nguon it nhat 10% (anh da nen tot / anh nho thi giu nguyen).
    if (filesize($tmp) >= $size * 0.9) {
        @unlink($tmp);
        return false;
    }
    if (!@rename($tmp, $file)) {
        @unlink($tmp);
        return is_file($file);
    }
    return true;
}

/**
 * Doi src cua cac <img> trong 1 doan HTML (noi dung bai SEO tu CMS) sang WebP cache. Chi dong vao img co src host duoc phep + .jpg/.png.
 */
function pplus_webp_content(string $html, int $max_w = 800): string {
    if ($html === '' || !pplus_webp_enabled() || stripos($html, '<img') === false) {
        return $html;
    }
    return (string) preg_replace_callback(
        '#(<img\b[^>]*?\ssrc=)(["\'])(https?://[^"\']+)\2#i',
        static function (array $m) use ($max_w): string {
            return $m[1] . $m[2] . esc_url(pplus_webp_url(html_entity_decode($m[3], ENT_QUOTES), $max_w)) . $m[2];
        },
        $html
    );
}

// Don cache cu (> 60 ngay) moi ngay - file bi xoa se tu tao lai khi trang can.
add_action('init', function () {
    if (!wp_next_scheduled(PPLUS_WEBP_CLEAN_HOOK)) {
        wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', PPLUS_WEBP_CLEAN_HOOK);
    }
});
add_action(PPLUS_WEBP_CLEAN_HOOK, function () {
    $dirs = pplus_webp_dir();
    if (!$dirs) {
        return;
    }
    foreach ((array) glob($dirs['dir'] . '/*') as $f) {
        if (is_file($f) && filemtime($f) < time() - 60 * DAY_IN_SECONDS) {
            @unlink($f);
        }
    }
});
