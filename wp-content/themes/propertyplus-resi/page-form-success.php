<?php
/**
 * Trang cam on sau khi gui form - port dung tu send-mail-succes.component.html (web chinh):
 * <app-search-base> + .container > .blog-box-detail.row > .col-md-12.table-mg > h1 + hr + h3.
 * Xem inc/form-success-route.php (vi sao co trang nay: do chuyen doi GTM/GA4/Google Ads).
 */
if (!defined('ABSPATH')) exit;

get_header();
pplus_render_suggest_header();
?>
<div class="container">
    <div class="blog-box-detail row">
        <div class="col-md-12 table-mg">
            <h1><?php echo esc_html(pplus_localize('Thông báo')); ?></h1>
            <hr class="hr-1" />
            <h3><?php echo esc_html(pplus_localize('Gửi hỗ trợ thành công, chúng tôi sẽ liên hệ sớm nhất với bạn!')); ?></h3>
        </div>
    </div>
</div>
<?php get_footer(); ?>
