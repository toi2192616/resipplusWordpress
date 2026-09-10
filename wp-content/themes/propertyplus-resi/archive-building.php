<?php
/**
 * Archive mac dinh cho building (vd /toa-nha/). Cac trang landing that su theo tinh
 * (/cho-thue-van-phong-ha-noi) dung page-landing.php, khong dung file nay.
 */
if (!defined('ABSPATH')) exit;

get_header();
?>
<div class="pplus-container">
    <h1><?php post_type_archive_title(); ?></h1>
    <div class="pplus-related-grid">
        <?php while (have_posts()) : the_post(); ?>
            <?php get_template_part('parts/building-card', null, ['item' => [
                'title' => get_the_title(),
                'url'   => get_permalink(),
                'image' => get_the_post_thumbnail_url(get_the_ID(), 'medium') ?: null,
            ]]); ?>
        <?php endwhile; ?>
    </div>
    <?php the_posts_pagination(); ?>
</div>
<?php
get_footer();
