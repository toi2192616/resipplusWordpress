<?php
/**
 * HTTP client goi .NET SEO API (PPlus.Manager.Web.PublicOffice /api/seo/v1/*).
 * Day la NOI DUY NHAT trong toan bo site duoc phep goi wp_remote_get() ra .NET -
 * theme va cac file khac cua plugin phai di qua cac ham global o cuoi file nay.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ppwh_Api_Client {
    private static ?Ppwh_Api_Client $instance = null;
    private const TIMEOUT_SECONDS = 5;

    public static function instance(): Ppwh_Api_Client {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function base_url(): string {
        return rtrim((string) get_option('ppwh_api_base_url', ''), '/');
    }

    private function internal_key(): string {
        return (string) get_option('ppwh_internal_key', '');
    }

    /**
     * @return array|WP_Error Mang JSON da decode, hoac WP_Error neu request that bai.
     */
    public function get(string $path, array $query = []) {
        $base = $this->base_url();
        if (empty($base)) {
            return new WP_Error('ppwh_config', 'Chua cau hinh API base URL trong Settings > PPlus SEO Sync.');
        }

        $url = $base . '/' . ltrim($path, '/');
        if (!empty($query)) {
            $url = add_query_arg($query, $url);
        }

        $response = wp_remote_get($url, [
            'timeout' => self::TIMEOUT_SECONDS,
            'headers' => [
                'Accept'        => 'application/json',
                'X-Internal-Key' => $this->internal_key(),
            ],
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code >= 400) {
            return new WP_Error('ppwh_http_error', sprintf('API %s tra ve HTTP %d', $path, $code));
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($body)) {
            return new WP_Error('ppwh_bad_response', 'Response khong phai JSON hop le: ' . $path);
        }

        return $body;
    }

    /**
     * Xac thuc HMAC signature cua webhook goi TU .NET VE WordPress (chieu nguoc lai).
     */
    public function verify_incoming_signature(string $payload, string $signature_header): bool {
        $secret = (string) get_option('ppwh_webhook_secret', '');
        if (empty($secret)) {
            return false;
        }
        $expected = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expected, $signature_header);
    }
}

/**
 * ---- Ham global dung boi theme va cac file khac cua plugin ----
 * Moi ham deu cache-first (xem class-cache-manager.php) truoc khi goi that ra API.
 */

function ppwh_get_building_seo(int $building_id) {
    return Ppwh_Cache_Manager::instance()->remember(
        "building_{$building_id}",
        fn() => Ppwh_Api_Client::instance()->get("/api/seo/v1/building/{$building_id}")
    );
}

function ppwh_get_news_seo(int $news_id) {
    return Ppwh_Cache_Manager::instance()->remember(
        "news_{$news_id}",
        fn() => Ppwh_Api_Client::instance()->get("/api/seo/v1/news/{$news_id}")
    );
}

function ppwh_get_landing_seo(string $province_slug, int $page = 1) {
    return Ppwh_Cache_Manager::instance()->remember(
        "landing_{$province_slug}_{$page}",
        fn() => Ppwh_Api_Client::instance()->get("/api/seo/v1/landing/{$province_slug}", ['page' => $page])
    );
}

/**
 * Search building/news theo tu khoa - dung cho o tim kiem trong man hinh admin WordPress
 * khi lien ket 1 CPT post voi entity_id ben .NET (khong cache, du lieu real-time).
 */
function ppwh_search_entities(string $type, string $keyword) {
    return Ppwh_Api_Client::instance()->get("/api/seo/v1/{$type}/search", ['q' => $keyword]);
}
