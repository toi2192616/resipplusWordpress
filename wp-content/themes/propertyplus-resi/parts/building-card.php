<?php
/**
 * Card hien thi 1 building rut gon trong danh sach landing/related.
 * Nhan $args['item'] la 1 phan tu trong mang "related"/"offices" tra ve tu API.
 */
if (!defined('ABSPATH')) exit;
$item = $args['item'] ?? [];
if (empty($item)) return;
?>
<a class="pplus-building-card" href="<?php echo esc_url($item['url'] ?? '#'); ?>">
    <?php if (!empty($item['image'])) : ?>
        <img src="<?php echo esc_url($item['image']); ?>" alt="<?php echo esc_attr($item['title'] ?? ''); ?>" loading="lazy">
    <?php endif; ?>
    <h3><?php echo esc_html($item['title'] ?? ''); ?></h3>
    <?php if (!empty($item['price'])) : ?>
        <span class="price"><?php echo esc_html(pplus_format_price((float) $item['price'])); ?></span>
    <?php endif; ?>
</a>
