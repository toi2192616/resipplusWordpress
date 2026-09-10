<?php
/**
 * Tao 301 khi alias/slug doi (vd Admin .NET sua ten toa nha lam slug thay doi)
 * hoac khi 1 CPT bi xoa (building-sync action=delete). Luu mapping trong 1 option table
 * don gian - co the nang cap len custom table neu so luong redirect lon.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ppwh_Redirect_Manager {
    private static ?Ppwh_Redirect_Manager $instance = null;
    private const OPTION_KEY = 'ppwh_redirect_map';

    public static function instance(): Ppwh_Redirect_Manager {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('template_redirect', [$this, 'maybe_redirect']);
    }

    public function record_alias_change(string $old_url, string $new_url): void {
        $map = get_option(self::OPTION_KEY, []);
        $map[wp_parse_url($old_url, PHP_URL_PATH)] = $new_url;
        update_option(self::OPTION_KEY, $map, false);
    }

    public function record_removed_url(int $post_id): void {
        $url = get_permalink($post_id);
        if ($url) {
            $this->record_alias_change($url, home_url('/'));
        }
    }

    public function maybe_redirect(): void {
        if (is_admin()) {
            return;
        }
        $path = wp_parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $map  = get_option(self::OPTION_KEY, []);
        if (isset($map[$path])) {
            wp_safe_redirect($map[$path], 301);
            exit;
        }
    }
}
