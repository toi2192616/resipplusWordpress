<?php
/**
 * News dung native post type 'post' + category, hoac CPT rieng 'news' neu can tach URL /tin-tuc/.
 * O day dang ky CPT rieng de kiem soat rewrite slug doc lap voi blog mac dinh cua WordPress.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', function () {
    register_post_type('news', [
        'label'        => __('Tin tuc', 'propertyplus'),
        'labels'       => [
            'name'          => __('Tin tuc', 'propertyplus'),
            'singular_name' => __('Tin tuc', 'propertyplus'),
            'add_new_item'  => __('Them tin tuc', 'propertyplus'),
            'edit_item'     => __('Sua tin tuc', 'propertyplus'),
        ],
        'public'       => true,
        'has_archive'  => true,
        'rewrite'      => ['slug' => 'tin-tuc', 'with_front' => false],
        'supports'     => ['title', 'thumbnail', 'excerpt'],
        'show_in_rest' => true,
        'menu_icon'    => 'dashicons-media-text',
    ]);
});

add_action('add_meta_boxes', function () {
    add_meta_box(
        'pplus_seo_box_news',
        __('Lien ket du lieu & SEO', 'propertyplus'),
        'pplus_render_seo_meta_box',
        'news',
        'normal',
        'high'
    );
});
