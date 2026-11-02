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
const PPLUS_WEBP_BATCH     = 24; // so anh moi lan WP-Cron (nhieu hon thi chia nhieu su kien)
const PPLUS_WEBP_QUEUE_MAX = 72; // so anh toi da xep hang trong 1 request
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
 * URL anh WebP (chieu rong toi da $max_w) neu DA co trong cache, nguoc lai '' - va xep hang doi tao SAU khi tra trang (khong chan request).
 */
function pplus_webp_cached(string $url, int $max_w = 0): string {
    if ($url === '' || !pplus_webp_enabled() || !preg_match('#^https?://#i', $url)) {
        return '';
    }
    $parts = wp_parse_url($url);
    $host  = $parts['host'] ?? '';
    $path  = $parts['path'] ?? '';
    if ($host === '' || !pplus_webp_allowed_host($host) || !preg_match('#\.(jpe?g|png)$#i', $path)) {
        return '';
    }
    $dirs = pplus_webp_dir();
    $key  = pplus_webp_key($url, $max_w);
    $file = $dirs['dir'] . '/' . $key . '.webp';
    if (is_file($file)) {
        return $dirs['url'] . '/' . $key . '.webp';
    }
    $skip = $file . '.skip';
    if (!is_file($skip) || filemtime($skip) <= time() - 6 * HOUR_IN_SECONDS) {
        pplus_webp_enqueue($url, $max_w);
    }
    return '';
}

/** Nhu pplus_webp_cached() nhung chua co thi tra ve $url goc. */
function pplus_webp_url(string $url, int $max_w = 0): string {
    $cached = pplus_webp_cached($url, $max_w);
    return $cached !== '' ? $cached : $url;
}

/**
 * Anh 2 co 400w / 800w cho 1 anh nguon 800x600: API Resi chi tra 1 danh sach anh (800x600, ~120KB/anh) nen theme TU TAO 2 ban WebP
 * (800w ~50KB, 400w ~15KB) - mobile/man thuong lay 400w, man retina/lon lay 800w qua srcset. Chua du 2 ban trong cache -> anh goc,
 * khong srcset (lan xem sau co du).
 *
 * @return array{src:string,srcset:string,sizes:string}
 */
function pplus_photo_sources(string $url, string $sizes, string $small = ''): array {
    if ($small !== '') {
        // Co file 400x300 THAT (backend da bo sung): dung no lam ung vien 400w (WebP neu da cache, khong thi chinh file do) - co srcset ngay tu lan xem dau.
        $big = pplus_webp_url($url, 800);
        $lit = pplus_webp_url($small, 400);
        return ['src' => $big, 'srcset' => $lit . ' 400w, ' . $big . ' 800w', 'sizes' => $sizes];
    }
    $w400 = pplus_webp_cached($url, 400);
    $w800 = pplus_webp_cached($url, 800);
    if ($w400 !== '' && $w800 !== '') {
        return ['src' => $w800, 'srcset' => $w400 . ' 400w, ' . $w800 . ' 800w', 'sizes' => $sizes];
    }
    return ['src' => $w800 !== '' ? $w800 : $url, 'srcset' => '', 'sizes' => ''];
}

/** Chuoi thuoc tinh `src="..." [srcset="..." sizes="..."]` cho <img> tu pplus_photo_sources(). */
function pplus_photo_img_attrs(string $url, string $sizes, string $small = ''): string {
    $s   = pplus_photo_sources($url, $sizes, $small);
    $out = 'src="' . esc_url($s['src']) . '"';
    if ($s['srcset'] !== '') {
        $out .= ' srcset="' . esc_attr($s['srcset']) . '" sizes="' . esc_attr($s['sizes']) . '"';
    }
    return $out;
}

/** Hang doi tao WebP cua request hien tai, xu ly sau khi tra trang. */
function pplus_webp_enqueue(string $url, int $max_w): void {
    static $queue = [];
    static $registered = false;
    $key = pplus_webp_key($url, $max_w);
    if (isset($queue[$key]) || count($queue) >= PPLUS_WEBP_QUEUE_MAX) {
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
        } else {
            foreach (array_chunk($jobs, PPLUS_WEBP_BATCH) as $i => $chunk) {
                if (!wp_next_scheduled(PPLUS_WEBP_CRON_HOOK, [$chunk])) {
                    wp_schedule_single_event(time() + $i, PPLUS_WEBP_CRON_HOOK, [$chunk]);
                }
            }
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
        @set_time_limit(120);
    }
    foreach ($jobs as $job) {
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
 * Tach anh trang chi tiet thanh danh sach "chinh" (800x600) + ban 400x300 THAT neu backend co: quet photoList va moi khoa photoList2/photoList3/...
 * (phan tu la URL hoac {url}); URL co hau to "_400x300" la ban nho, ghep voi anh chinh cung TEN (bo duoi file, bo hau to _NxM/_type1..3, khong
 * phan biet hoa/thuong); moi anh khac (ke ca "_800x600") la anh chinh. API Resi hien chi co photoList (800x600) nen "small" rong -> theme tu tao
 * ban 400 tu anh 800. Khi bo sung anh 400x300 (dat ten file "<ten>_400x300.<duoi>", tra ve trong photoList hoac photoList2/3) tu dung ban that.
 *
 * @return array{photos:string[],small:string[]} small[i] la URL ban 400x300 cua photos[i] hoac ''
 */
function pplus_photo_variants(array $detail): array {
    $key_of = static function (string $url): string {
        $name = pathinfo(rawurldecode((string) wp_parse_url($url, PHP_URL_PATH)), PATHINFO_FILENAME);
        return strtolower((string) preg_replace('~([-_](?:\d+x\d+|type\d))+$~i', '', $name));
    };
    $masters = [];
    $smalls  = [];
    foreach ($detail as $field => $list) {
        if (!is_array($list) || !preg_match('~^photoList\d*$~i', (string) $field)) {
            continue;
        }
        foreach ($list as $p) {
            $u = pplus_admin_asset_url((string) (is_array($p) ? ($p['url'] ?? $p['Url'] ?? '') : $p));
            if ($u === '') {
                continue;
            }
            if (preg_match('~_400x300\.[a-z0-9]+$~i', (string) wp_parse_url($u, PHP_URL_PATH))) {
                $smalls[$key_of($u)] = $u;
            } elseif (strcasecmp((string) $field, 'photoList') === 0) {
                $masters[] = $u; // photoList2/3... chi dong gop ban "_400x300"; anh chinh khac o do khong dung lam anh hien thi
            }
        }
    }
    $photos = [];
    $small  = [];
    foreach ($masters as $u) {
        $photos[] = $u;
        $small[]  = $smalls[$key_of($u)] ?? '';
    }
    return ['photos' => $photos, 'small' => $small];
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
