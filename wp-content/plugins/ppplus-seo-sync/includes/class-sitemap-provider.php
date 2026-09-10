<?php
/**
 * Hook vao RankMath sitemap de inject entries lay tu .NET /api/seo/v1/sitemap-data,
 * thay vi de RankMath tu quet toan bo bang post cua WordPress (khong scale khi so
 * luong building/news lon). Cung xu ly "dirty chunk" - chi rebuild phan thay doi.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ppwh_Sitemap_Provider {
    private static ?Ppwh_Sitemap_Provider $instance = null;
    private const DIRTY_OPTION = 'ppwh_sitemap_dirty_chunks';

    public static function instance(): Ppwh_Sitemap_Provider {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('ppwh_reconciliation_cron', [$this, 'rebuild_dirty_chunks']);

        if (defined('RANK_MATH_VERSION')) {
            add_filter('rank_math/sitemap/providers', [$this, 'register_provider']);
        }
    }

    public function mark_dirty(string $post_type, int $post_id): void {
        $chunk = $post_type . '-' . intdiv($post_id, 500); // 500 url/chunk, khop convention XmlSiteMap.cs hien tai
        $dirty = get_option(self::DIRTY_OPTION, []);
        $dirty[$chunk] = true;
        update_option(self::DIRTY_OPTION, $dirty, false);
    }

    public function rebuild_dirty_chunks(): void {
        $dirty = get_option(self::DIRTY_OPTION, []);
        if (empty($dirty)) {
            return;
        }
        foreach (array_keys($dirty) as $chunk) {
            // TODO: goi /api/seo/v1/sitemap-data?type=...&chunk=... va ghi lai file/cache sitemap.
            do_action('ppwh_rebuild_sitemap_chunk', $chunk);
        }
        update_option(self::DIRTY_OPTION, [], false);
    }

    public function register_provider(array $providers): array {
        // TODO: dang ky provider tuy chinh tra ve URL building/news tu .NET thay vi tu WP_Query.
        return $providers;
    }
}
