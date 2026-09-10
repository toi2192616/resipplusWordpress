<?php
/**
 * Chan cong cu tim kiem index site (noindex TOAN BO) - dung cho ban TEST/STAGING de Google
 * khong index ban dang thu nghiem.
 *
 * BAT/TAT (uu tien tu tren xuong):
 *   1. Hang so trong wp-config.php (ep cung theo TUNG MOI TRUONG, khuyen dung cho server test):
 *        define('PPLUS_NOINDEX', true);   // ep noindex, bo qua o tich
 *        define('PPLUS_NOINDEX', false);  // ep CHO index (production), bo qua o tich
 *   2. Neu KHONG dinh nghia hang so -> theo o tich "Chan Google index (noindex toan site)"
 *      trong Giao dien > PropertyPlus (option 'pplus_noindex'). Mac dinh: TAT (cho index).
 *
 * Khi BAT, phu TOAN BO trang (ke ca custom route A1-A5/B1-B6, trang chi tiet, PDF, REST...):
 *   - <meta name="robots" content="noindex, nofollow"> trong <head> moi trang HTML (qua wp_robots)
 *   - HTTP header "X-Robots-Tag: noindex, nofollow" cho MOI response
 *   - robots.txt tra ve "Disallow: /" (chi co tac dung neu KHONG co file robots.txt that o goc site)
 *
 * LUU Y khi da bat Page Cache (WP Super Cache): sau khi bat/tat noindex phai "Delete Cache" -
 * HTML cu da cache khong tu co (hoac tu bo) the meta cho den khi cache het han.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Site co dang o che do noindex khong.
 */
function pplus_is_noindex(): bool {
    if (defined('PPLUS_NOINDEX')) {
        return (bool) PPLUS_NOINDEX;
    }
    return (bool) get_option('pplus_noindex', 0);
}

// 1) Meta robots trong <head> - moi template deu goi get_header()/wp_head().
if (function_exists('wp_robots')) { // WP 5.7+
    add_filter('wp_robots', function (array $robots): array {
        if (pplus_is_noindex()) {
            return ['noindex' => true, 'nofollow' => true];
        }
        return $robots;
    }, 99);
} else { // WP cu hon 5.7 (khong co wp_robots)
    add_action('wp_head', function (): void {
        if (pplus_is_noindex()) {
            echo '<meta name="robots" content="noindex, nofollow" />' . "\n";
        }
    }, 1);
}

// 2) HTTP header cho MOI response - phu ca route KHONG phai HTML (PDF export, REST, feed, custom route).
add_action('send_headers', function (): void {
    if (pplus_is_noindex() && !headers_sent()) {
        header('X-Robots-Tag: noindex, nofollow', true);
    }
});

// 3) robots.txt dong (chi khi khong co file robots.txt that o goc site).
add_filter('robots_txt', function ($output, $public) {
    if (pplus_is_noindex()) {
        return "User-agent: *\nDisallow: /\n";
    }
    return $output;
}, 10, 2);
