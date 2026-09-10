<?php
/**
 * Custom Post Type: building
 * WordPress chi luu building_id (khoa lien ket .NET) + SEO override.
 * Gia/dien tich/anh KHONG luu o day - luon fetch runtime qua pplus_get_building_seo().
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', function () {
    register_post_type('building', [
        'label'        => __('Toa nha', 'propertyplus'),
        'labels'       => [
            'name'          => __('Toa nha', 'propertyplus'),
            'singular_name' => __('Toa nha', 'propertyplus'),
            'add_new_item'  => __('Them toa nha', 'propertyplus'),
            'edit_item'     => __('Sua toa nha', 'propertyplus'),
        ],
        'public'       => true,
        'has_archive'  => true,
        'rewrite'      => ['slug' => 'toa-nha', 'with_front' => false],
        'supports'     => ['title', 'thumbnail'],
        'show_in_rest' => true,
        'menu_icon'    => 'dashicons-building',
    ]);

    register_post_type('project', [
        'label'        => __('Du an', 'propertyplus'),
        'public'       => true,
        'has_archive'  => true,
        'rewrite'      => ['slug' => 'du-an', 'with_front' => false],
        'supports'     => ['title', 'thumbnail'],
        'show_in_rest' => true,
        'menu_icon'    => 'dashicons-admin-multisite',
    ]);
});

/**
 * Cac meta field chuan cho building/project - dung chung 1 danh sach
 * de tranh lap code giua cac CPT (news dung lai qua inc/cpt-news.php).
 */
function pplus_seo_meta_fields(): array {
    return [
        'entity_id'        => __('ID lien ket (.NET)', 'propertyplus'),
        'seo_title'        => __('SEO Title', 'propertyplus'),
        'meta_description' => __('Meta Description', 'propertyplus'),
        'canonical'        => __('Canonical URL', 'propertyplus'),
        'focus_keyword'    => __('Focus Keyword', 'propertyplus'),
        'no_index'         => __('No Index (1/0)', 'propertyplus'),
        'og_title'         => __('OpenGraph Title', 'propertyplus'),
        'og_image'         => __('OpenGraph Image URL', 'propertyplus'),
    ];
}

add_action('add_meta_boxes', function () {
    foreach (['building', 'project'] as $post_type) {
        add_meta_box(
            'pplus_seo_box',
            __('Lien ket du lieu & SEO', 'propertyplus'),
            'pplus_render_seo_meta_box',
            $post_type,
            'normal',
            'high'
        );
    }
});

function pplus_render_seo_meta_box(WP_Post $post) {
    wp_nonce_field('pplus_save_seo_meta', 'pplus_seo_meta_nonce');
    echo '<table class="form-table">';
    foreach (pplus_seo_meta_fields() as $key => $label) {
        $value = get_post_meta($post->ID, "_pplus_{$key}", true);
        printf(
            '<tr><th><label for="pplus_%1$s">%2$s</label></th><td><input type="text" style="width:100%%" id="pplus_%1$s" name="pplus_%1$s" value="%3$s" /></td></tr>',
            esc_attr($key),
            esc_html($label),
            esc_attr($value)
        );
    }
    echo '</table>';
    echo '<p class="description">' . esc_html__('entity_id la BuildingId/ProjectId ben .NET - dung de goi API tong hop khi render trang. Gia/dien tich/anh khong sua o day.', 'propertyplus') . '</p>';
}

add_action('save_post', function (int $post_id) {
    if (!isset($_POST['pplus_seo_meta_nonce']) || !wp_verify_nonce($_POST['pplus_seo_meta_nonce'], 'pplus_save_seo_meta')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    foreach (array_keys(pplus_seo_meta_fields()) as $key) {
        if (isset($_POST["pplus_{$key}"])) {
            update_post_meta($post_id, "_pplus_{$key}", sanitize_text_field(wp_unslash($_POST["pplus_{$key}"])));
        }
    }
});
