<?php
/**
 * Nhan webhook TU .NET (Admin tao/sua Building hoac News) - xem plan muc 6.
 * .NET goi: POST /wp-json/ppwh/v1/building-sync  { entityId, action: upsert|delete }
 * kem header X-Ppwh-Signature = HMAC_SHA256(body, shared_secret).
 */

if (!defined('ABSPATH')) {
    exit;
}

class Ppwh_Webhook_Controller {
    private static ?Ppwh_Webhook_Controller $instance = null;

    public static function instance(): Ppwh_Webhook_Controller {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void {
        register_rest_route('ppwh/v1', '/building-sync', [
            'methods'             => 'POST',
            'permission_callback' => [$this, 'verify_request'],
            'callback'            => [$this, 'handle_building_sync'],
        ]);

        register_rest_route('ppwh/v1', '/news-sync', [
            'methods'             => 'POST',
            'permission_callback' => [$this, 'verify_request'],
            'callback'            => [$this, 'handle_news_sync'],
        ]);
    }

    public function verify_request(WP_REST_Request $request) {
        $signature = $request->get_header('x-ppwh-signature') ?? '';
        $body      = $request->get_body();
        if (empty($signature) || !Ppwh_Api_Client::instance()->verify_incoming_signature($body, $signature)) {
            return new WP_Error('ppwh_forbidden', 'Chu ky HMAC khong hop le.', ['status' => 403]);
        }
        return true;
    }

    public function handle_building_sync(WP_REST_Request $request) {
        return $this->handle_sync($request, 'building', 'ppwh_get_building_seo');
    }

    public function handle_news_sync(WP_REST_Request $request) {
        return $this->handle_sync($request, 'news', 'ppwh_get_news_seo');
    }

    private function handle_sync(WP_REST_Request $request, string $post_type, string $fetch_fn) {
        $params    = $request->get_json_params();
        $entity_id = (int) ($params['entityId'] ?? 0);
        $action    = (string) ($params['action'] ?? 'upsert');

        if ($entity_id <= 0) {
            return new WP_Error('ppwh_bad_request', 'entityId khong hop le.', ['status' => 400]);
        }

        Ppwh_Cache_Manager::instance()->forget_entity($post_type, $entity_id);

        $existing = $this->find_post_by_entity_id($post_type, $entity_id);

        if ($action === 'delete') {
            if ($existing) {
                Ppwh_Redirect_Manager::instance()->record_removed_url($existing->ID);
                wp_trash_post($existing->ID);
            }
            return ['status' => 'deleted', 'entityId' => $entity_id];
        }

        $data = $fetch_fn($entity_id);
        if (is_wp_error($data)) {
            return new WP_Error('ppwh_upstream_error', $data->get_error_message(), ['status' => 502]);
        }

        $title = $data['seo']['title'] ?? $data['building']['name'] ?? ('#' . $entity_id);
        $old_permalink = $existing ? get_permalink($existing->ID) : null;

        $post_id = wp_insert_post([
            'ID'          => $existing->ID ?? 0,
            'post_type'   => $post_type,
            'post_title'  => wp_strip_all_tags($title),
            'post_status' => 'publish',
        ], true);

        if (is_wp_error($post_id)) {
            return new WP_Error('ppwh_save_failed', $post_id->get_error_message(), ['status' => 500]);
        }

        update_post_meta($post_id, '_pplus_entity_id', $entity_id);
        if (!empty($data['seo']['metaDescription'])) {
            update_post_meta($post_id, '_pplus_meta_description', $data['seo']['metaDescription']);
        }
        if (!empty($data['seo']['canonical'])) {
            update_post_meta($post_id, '_pplus_canonical', $data['seo']['canonical']);
        }

        $new_permalink = get_permalink($post_id);
        if ($old_permalink && $new_permalink && $old_permalink !== $new_permalink) {
            Ppwh_Redirect_Manager::instance()->record_alias_change($old_permalink, $new_permalink);
        }

        Ppwh_Sitemap_Provider::instance()->mark_dirty($post_type, $post_id);

        return ['status' => 'ok', 'postId' => $post_id, 'permalink' => $new_permalink];
    }

    private function find_post_by_entity_id(string $post_type, int $entity_id): ?WP_Post {
        $query = new WP_Query([
            'post_type'      => $post_type,
            'post_status'    => 'any',
            'meta_key'       => '_pplus_entity_id',
            'meta_value'     => $entity_id,
            'posts_per_page' => 1,
        ]);
        return $query->posts[0] ?? null;
    }
}
