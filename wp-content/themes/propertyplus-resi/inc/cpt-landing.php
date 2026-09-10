<?php
/**
 * Landing theo tinh (A3, vd /cho-thue-van-phong/ha-noi) dung page template chuan cua WordPress
 * (page-landing.php), la Page con cua Page "Cho thue van phong" (A1) - thay vi CPT rieng, vi so
 * luong tinh it/on dinh. Field province_slug la alias goi Reference/GetIndexProvinceProduct
 * (xem pplus_get_province_landing() trong inc/api-client.php).
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('add_meta_boxes', function () {
    add_meta_box(
        'pplus_landing_box',
        __('Landing Page - Lien ket tinh/thanh', 'propertyplus'),
        'pplus_render_landing_meta_box',
        'page',
        'side',
        'default'
    );
});

function pplus_render_landing_meta_box(WP_Post $post) {
    wp_nonce_field('pplus_save_landing_meta', 'pplus_landing_meta_nonce');
    $province_id   = get_post_meta($post->ID, '_pplus_province_id', true);
    $province_slug = get_post_meta($post->ID, '_pplus_province_slug', true);
    ?>
    <p>
        <label for="pplus_province_id"><?php esc_html_e('Province ID (.NET)', 'propertyplus'); ?></label>
        <input type="text" style="width:100%" id="pplus_province_id" name="pplus_province_id" value="<?php echo esc_attr($province_id); ?>" />
    </p>
    <p>
        <label for="pplus_province_slug"><?php esc_html_e('Province slug (alias goi Reference/GetIndexProvinceProduct, vd "ha-noi")', 'propertyplus'); ?></label>
        <input type="text" style="width:100%" id="pplus_province_slug" name="pplus_province_slug" value="<?php echo esc_attr($province_slug); ?>" />
    </p>
    <p class="description"><?php esc_html_e('Chon template "Landing tinh (Reference Main)" o phan Page Attributes. De trong se tu dung slug cua Page.', 'propertyplus'); ?></p>
    <?php
}

// Title/meta SEO tu dataSelected.metaTitle/metaDescription (API that), giong cach lam o A2
// (inc/office-detail-route.php) - khong dung title mac dinh cua WordPress Page.
add_filter('pre_get_document_title', function (string $title): string {
    if (!is_page_template('page-landing.php')) {
        return $title;
    }
    $model = pplus_get_current_landing_data();
    return $model['dataSelected']['metaTitle'] ?? ($model['dataSelected']['title'] ?? $title);
});

add_action('wp_head', function () {
    if (!is_page_template('page-landing.php')) {
        return;
    }
    $model = pplus_get_current_landing_data();
    $description = $model['dataSelected']['metaDescription'] ?? $model['dataSelected']['description'] ?? '';
    if ($description) {
        printf('<meta name="description" content="%s" />' . "\n", esc_attr($description));
    }
}, 1);

/**
 * Wrapper goi pplus_get_province_landing() dua theo Page dang xem - dung chung boi
 * title/meta filter va page-landing.php, cache trong 1 request qua static var.
 */
function pplus_get_current_landing_data(): ?array {
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $post_id = get_queried_object_id();
    if (!$post_id) {
        return null;
    }
    $province_slug = get_post_meta($post_id, '_pplus_province_slug', true) ?: get_post_field('post_name', $post_id);
    return $cache = pplus_get_province_landing($province_slug);
}

add_action('save_post_page', function (int $post_id) {
    if (!isset($_POST['pplus_landing_meta_nonce']) || !wp_verify_nonce($_POST['pplus_landing_meta_nonce'], 'pplus_save_landing_meta')) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    foreach (['province_id', 'province_slug'] as $key) {
        if (isset($_POST["pplus_{$key}"])) {
            update_post_meta($post_id, "_pplus_{$key}", sanitize_text_field(wp_unslash($_POST["pplus_{$key}"])));
        }
    }
});
