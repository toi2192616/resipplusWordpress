<?php
/**
 * Override Schema/JSON-LD o cap do site-wide (Organization, WebSite) tu .NET.
 * Schema rieng cho tung trang building/news duoc override o theme (inc/seo-hooks.php)
 * vi can ngu canh post/template cu the; file nay chi phu trach phan chung toan site.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ppwh_Rankmath_Schema {
    private static ?Ppwh_Rankmath_Schema $instance = null;

    public static function instance(): Ppwh_Rankmath_Schema {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        if (defined('RANK_MATH_VERSION')) {
            add_filter('rank_math/json_ld', [$this, 'inject_organization_schema'], 9, 2);
        }
    }

    public function inject_organization_schema(array $data, $jsonld): array {
        // TODO: lay thong tin cong ty (ten, logo, dia chi, social profile) tu option settings
        // hoac tu 1 endpoint /api/seo/v1/organization neu can dong bo tu .NET.
        return $data;
    }
}
