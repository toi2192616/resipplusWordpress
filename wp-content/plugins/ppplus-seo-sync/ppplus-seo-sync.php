<?php
/**
 * Plugin Name: PPlus SEO Sync
 * Description: Ket noi WordPress voi .NET SEO API (PPlus.Manager.Web.PublicOffice) - cache, webhook dong bo, sitemap, schema. Tach rieng khoi theme de doi theme khong mat logic dong bo.
 * Version: 0.1.0
 * Author: PropertyPlus
 * Text Domain: ppplus-seo-sync
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PPWH_VERSION', '0.1.0');
define('PPWH_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PPWH_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once PPWH_PLUGIN_DIR . 'includes/class-api-client.php';
require_once PPWH_PLUGIN_DIR . 'includes/class-cache-manager.php';
require_once PPWH_PLUGIN_DIR . 'includes/class-webhook-controller.php';
require_once PPWH_PLUGIN_DIR . 'includes/class-redirect-manager.php';
require_once PPWH_PLUGIN_DIR . 'includes/class-sitemap-provider.php';
require_once PPWH_PLUGIN_DIR . 'includes/class-rankmath-schema.php';
require_once PPWH_PLUGIN_DIR . 'admin/settings-page.php';

add_action('plugins_loaded', function () {
    Ppwh_Api_Client::instance();
    Ppwh_Webhook_Controller::instance();
    Ppwh_Redirect_Manager::instance();
    Ppwh_Sitemap_Provider::instance();
    Ppwh_Rankmath_Schema::instance();
});

register_activation_hook(__FILE__, function () {
    if (!wp_next_scheduled('ppwh_reconciliation_cron')) {
        wp_schedule_event(time(), 'hourly', 'ppwh_reconciliation_cron');
    }
});

register_deactivation_hook(__FILE__, function () {
    wp_clear_scheduled_hook('ppwh_reconciliation_cron');
});
