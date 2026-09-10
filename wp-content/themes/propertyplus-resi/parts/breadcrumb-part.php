<?php
/**
 * Wrapper de goi tu template: <?php get_template_part('parts/breadcrumb-part', null, ['items' => $seo['breadcrumb']]); ?>
 */
if (!defined('ABSPATH')) exit;
$items = $args['items'] ?? [];
pplus_render_breadcrumb($items);
