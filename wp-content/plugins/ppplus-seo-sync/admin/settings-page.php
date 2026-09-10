<?php
/**
 * Trang cau hinh: API base URL, internal key (goi ra .NET), webhook secret (nhan tu .NET).
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', function () {
    add_options_page(
        __('PPlus SEO Sync', 'ppplus-seo-sync'),
        __('PPlus SEO Sync', 'ppplus-seo-sync'),
        'manage_options',
        'ppwh-settings',
        'ppwh_render_settings_page'
    );
});

add_action('admin_init', function () {
    register_setting('ppwh_settings', 'ppwh_api_base_url', ['sanitize_callback' => 'esc_url_raw']);
    register_setting('ppwh_settings', 'ppwh_internal_key', ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('ppwh_settings', 'ppwh_webhook_secret', ['sanitize_callback' => 'sanitize_text_field']);
});

function ppwh_render_settings_page(): void {
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('PPlus SEO Sync - Cau hinh', 'ppplus-seo-sync'); ?></h1>
        <form method="post" action="options.php">
            <?php settings_fields('ppwh_settings'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="ppwh_api_base_url"><?php esc_html_e('API Base URL (.NET)', 'ppplus-seo-sync'); ?></label></th>
                    <td><input type="url" style="width:400px" id="ppwh_api_base_url" name="ppwh_api_base_url"
                               value="<?php echo esc_attr(get_option('ppwh_api_base_url', 'https://admin.propertyplus.vn')); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="ppwh_internal_key"><?php esc_html_e('Internal Key (WordPress -> .NET)', 'ppplus-seo-sync'); ?></label></th>
                    <td><input type="text" style="width:400px" id="ppwh_internal_key" name="ppwh_internal_key"
                               value="<?php echo esc_attr(get_option('ppwh_internal_key', '')); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="ppwh_webhook_secret"><?php esc_html_e('Webhook HMAC Secret (.NET -> WordPress)', 'ppplus-seo-sync'); ?></label></th>
                    <td><input type="text" style="width:400px" id="ppwh_webhook_secret" name="ppwh_webhook_secret"
                               value="<?php echo esc_attr(get_option('ppwh_webhook_secret', '')); ?>" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <p class="description">
            <?php esc_html_e('Webhook endpoint cho .NET goi toi:', 'ppplus-seo-sync'); ?>
            <code><?php echo esc_url(rest_url('ppwh/v1/building-sync')); ?></code>,
            <code><?php echo esc_url(rest_url('ppwh/v1/news-sync')); ?></code>
        </p>
    </div>
    <?php
}
