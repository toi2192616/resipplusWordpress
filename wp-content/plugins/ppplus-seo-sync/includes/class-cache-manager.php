<?php
/**
 * Wrapper cache (object cache / Redis neu co, fallback transient) - TTL mac dinh 15 phut,
 * bi invalidate chu dong khi webhook building-sync/news-sync ban toi (xem class-webhook-controller.php).
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ppwh_Cache_Manager {
    private static ?Ppwh_Cache_Manager $instance = null;
    private const GROUP = 'ppplus_seo';
    private const DEFAULT_TTL = 15 * MINUTE_IN_SECONDS;

    public static function instance(): Ppwh_Cache_Manager {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @param string   $key      Key ngan gon, vd "building_123".
     * @param callable $resolver Ham tra ve du lieu that (goi API) neu cache miss.
     * @return array|WP_Error
     */
    public function remember(string $key, callable $resolver, int $ttl = self::DEFAULT_TTL) {
        $cached = wp_cache_get($key, self::GROUP);
        if ($cached !== false) {
            return $cached;
        }

        $value = $resolver();
        if (is_wp_error($value)) {
            // Khong cache loi - lan sau request khac se thu lai ngay, tranh "dong bang" loi.
            return $value;
        }

        wp_cache_set($key, $value, self::GROUP, $ttl);
        return $value;
    }

    public function forget(string $key): void {
        wp_cache_delete($key, self::GROUP);
    }

    public function forget_entity(string $type, int $id): void {
        // Xoa moi bien the key co the co (vd landing co them so trang) - don gian hoa bang prefix scan
        // neu backend object-cache ho tro (Redis). Voi transient/object-cache mac dinh, chi xoa key chinh.
        $this->forget("{$type}_{$id}");
    }
}
