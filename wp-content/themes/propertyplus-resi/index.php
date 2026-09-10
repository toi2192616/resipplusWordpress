<?php
if (!defined('ABSPATH')) exit;
get_header();
?>
<div class="pplus-container">
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
        <article <?php post_class(); ?>>
            <h1><?php the_title(); ?></h1>
            <div class="entry-content"><?php the_content(); ?></div>
        </article>
    <?php endwhile; else : ?>
        <p><?php esc_html_e('Khong co noi dung.', 'propertyplus'); ?></p>
    <?php endif; ?>
</div>
<?php get_footer(); ?>
