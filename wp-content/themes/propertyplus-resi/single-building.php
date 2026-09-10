<?php
/**
 * CANH BAO: template thuoc kien truc CPT cu da bi bo (xem canh bao chi tiet trong
 * inc/seo-hooks.php). KHONG co post nao thuoc CPT "building"/"project" duoc tao ra trong thuc te
 * (khong co code nao goi wp_insert_post() cho 2 loai nay) - template nay hien khong bao gio duoc
 * WordPress goi toi. Trang chi tiet van phong THAT (A2) la page-office-detail.php, qua rewrite
 * route rieng (inc/office-detail-route.php), khong phai CPT.
 *
 * Template cho CPT building/project. Chi goi API dung 1 lan/trang (pplus_current_seo_data()),
 * moi phan cua trang (breadcrumb, anh, gia, related) dung chung bien $seo.
 */
if (!defined('ABSPATH')) exit;

get_header();

while (have_posts()) : the_post();
    $seo = pplus_current_seo_data();

    if (!$seo) {
        pplus_render_api_error();
        get_footer();
        return;
    }
    ?>
    <div class="pplus-container pplus-building">
        <?php get_template_part('parts/breadcrumb-part', null, ['items' => $seo['breadcrumb'] ?? []]); ?>

        <h1><?php echo esc_html($seo['building']['name'] ?? get_the_title()); ?></h1>

        <?php if (!empty($seo['images'])) : ?>
            <div class="pplus-gallery">
                <?php foreach ($seo['images'] as $image) : ?>
                    <img src="<?php echo esc_url($image['url']); ?>" alt="<?php echo esc_attr($image['alt'] ?? ''); ?>" loading="lazy">
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="pplus-building__meta">
            <p><?php echo esc_html($seo['building']['address'] ?? ''); ?></p>
            <p>
                <?php echo esc_html($seo['building']['district'] ?? ''); ?>,
                <?php echo esc_html($seo['building']['province'] ?? ''); ?>
            </p>
        </div>

        <?php if (!empty($seo['offices'])) : ?>
            <h2><?php esc_html_e('Van phong con trong', 'propertyplus'); ?></h2>
            <table class="pplus-office-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Tang', 'propertyplus'); ?></th>
                        <th><?php esc_html_e('Dien tich', 'propertyplus'); ?></th>
                        <th><?php esc_html_e('Gia', 'propertyplus'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($seo['offices'] as $office) : ?>
                        <tr>
                            <td><?php echo esc_html($office['floor'] ?? ''); ?></td>
                            <td><?php echo esc_html(pplus_format_area($office['area'] ?? null)); ?></td>
                            <td><?php echo esc_html(pplus_format_price($office['price'] ?? null)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if (!empty($seo['related'])) : ?>
            <h2><?php esc_html_e('Toa nha lien quan', 'propertyplus'); ?></h2>
            <div class="pplus-related-grid">
                <?php foreach ($seo['related'] as $item) : ?>
                    <?php get_template_part('parts/building-card', null, ['item' => $item]); ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
endwhile;

get_footer();
