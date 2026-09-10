<?php
/**
 * Breadcrumb render tu du lieu API (mang breadcrumb[] trong SeoBuildingDto/SeoNewsDto),
 * khong tu dung WordPress category/parent vi cau truc breadcrumb SEO da duoc .NET tinh san.
 */

if (!defined('ABSPATH')) {
    exit;
}

function pplus_render_breadcrumb(array $items): void {
    if (empty($items)) {
        return;
    }
    echo '<nav class="pplus-breadcrumb" aria-label="breadcrumb"><ol>';
    foreach ($items as $i => $item) {
        $is_last = ($i === count($items) - 1);
        echo '<li>';
        if (!$is_last && !empty($item['url'])) {
            printf('<a href="%s">%s</a>', esc_url($item['url']), esc_html($item['name']));
        } else {
            printf('<span aria-current="page">%s</span>', esc_html($item['name']));
        }
        echo '</li>';
    }
    echo '</ol></nav>';
}
