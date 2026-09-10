<?php
/**
 * Trang cau hinh domain dung boi header/footer/api-client (inc/api-client.php).
 * Gia tri mac dinh lay dung tu cau hinh Angular hien tai (da publish that):
 * - API .NET: src/app/configs/app.configs.ts + GlobalConstants.ts -> "https://admin.propertyplus.vn/"
 * - App/site hien tai: Angular dang serve ngay tai domain goc "https://propertyplus.vn"
 *   (chua co subdomain app.propertyplus.vn rieng - se doi sau khi Angular chi con
 *   dashboard/favourite/login, xem docs/SCREENS_AND_TASK_SPLIT.md).
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', function () {
    add_theme_page(
        __('PropertyPlus - Cấu hình domain', 'propertyplus'),
        __('PropertyPlus', 'propertyplus'),
        'manage_options',
        'pplus-settings',
        'pplus_render_settings_page'
    );
});

add_action('admin_init', function () {
    register_setting('pplus_settings', 'pplus_dotnet_api_base', [
        'sanitize_callback' => 'esc_url_raw',
        'default'           => 'https://admin.propertyplus.vn',
    ]);
    register_setting('pplus_settings', 'pplus_dotnet_asset_base', [
        'sanitize_callback' => 'esc_url_raw',
        'default'           => '',
    ]);
    register_setting('pplus_settings', 'pplus_angular_app_base', [
        'sanitize_callback' => 'esc_url_raw',
        'default'           => 'https://propertyplus.vn',
    ]);
    register_setting('pplus_settings', 'pplus_noindex', [
        'sanitize_callback' => function ($v) { return empty($v) ? 0 : 1; },
        'default'           => 0,
    ]);
});

function pplus_render_settings_page(): void {
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('PropertyPlus - Cấu hình domain', 'propertyplus'); ?></h1>
        <form method="post" action="options.php">
            <?php settings_fields('pplus_settings'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Chặn Google index', 'propertyplus'); ?></th>
                    <td>
                        <?php $pplus_noindex_forced = defined('PPLUS_NOINDEX'); ?>
                        <label>
                            <input type="checkbox" name="pplus_noindex" value="1"
                                   <?php checked(1, (int) get_option('pplus_noindex', 0)); ?>
                                   <?php disabled($pplus_noindex_forced); ?> />
                            <?php esc_html_e('Bật noindex toàn site (chặn công cụ tìm kiếm — dùng cho bản test/staging)', 'propertyplus'); ?>
                        </label>
                        <p class="description">
                            <?php if ($pplus_noindex_forced) : ?>
                                <strong><?php echo esc_html(sprintf(
                                    /* translators: %s = true/false */
                                    __('Đang bị ép bởi hằng số PPLUS_NOINDEX = %s trong wp-config.php — ô tích này vô hiệu.', 'propertyplus'),
                                    PPLUS_NOINDEX ? 'true' : 'false'
                                )); ?></strong><br>
                            <?php endif; ?>
                            <?php esc_html_e('Khi bật: thêm meta robots "noindex, nofollow" + HTTP header X-Robots-Tag cho MỌI trang, và robots.txt trả về Disallow: /. Nhớ Delete Cache sau khi đổi. Có thể ép cứng theo môi trường bằng define(\'PPLUS_NOINDEX\', true/false) trong wp-config.php.', 'propertyplus'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="pplus_dotnet_api_base"><?php esc_html_e('API .NET Base URL', 'propertyplus'); ?></label></th>
                    <td>
                        <input type="url" style="width:420px" id="pplus_dotnet_api_base" name="pplus_dotnet_api_base"
                               value="<?php echo esc_attr(get_option('pplus_dotnet_api_base', 'https://admin.propertyplus.vn')); ?>" />
                        <p class="description">
                            <?php esc_html_e('Endpoint Office/Blogs/Home/Reference... hiện có (giống Angular đang dùng) — chỉ dùng để PHP gọi API (server→server). Khi deploy chung máy với app .NET, đặt về port nội bộ để gọi loopback, không qua Internet, ví dụ: http://localhost:5000 hoặc http://127.0.0.1:5000. Mặc định: https://admin.propertyplus.vn', 'propertyplus'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="pplus_dotnet_asset_base"><?php esc_html_e('Asset/Ảnh Base URL', 'propertyplus'); ?></label></th>
                    <td>
                        <input type="url" style="width:420px" id="pplus_dotnet_asset_base" name="pplus_dotnet_asset_base"
                               value="<?php echo esc_attr(get_option('pplus_dotnet_asset_base', '')); ?>" placeholder="https://admin.propertyplus.vn" />
                        <p class="description">
                            <?php esc_html_e('Domain PUBLIC để ghép URL ảnh (photoList, item.img...) render vào <img src> cho TRÌNH DUYỆT khách tải. Bắt buộc là domain truy cập được từ ngoài — trình duyệt khách KHÔNG mở được http://localhost:port của server. Nếu API Base ở trên trỏ vào port nội bộ thì Ô NÀY phải điền domain thật của app .NET (vd https://admin.propertyplus.vn). Để TRỐNG thì tự dùng lại "API .NET Base URL" ở trên (phù hợp local dev / khi cả 2 cùng 1 domain).', 'propertyplus'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="pplus_angular_app_base"><?php esc_html_e('Angular App Base URL', 'propertyplus'); ?></label></th>
                    <td>
                        <input type="url" style="width:420px" id="pplus_angular_app_base" name="pplus_angular_app_base"
                               value="<?php echo esc_attr(get_option('pplus_angular_app_base', 'https://propertyplus.vn')); ?>" />
                        <p class="description">
                            <?php esc_html_e('Dùng cho link "Yêu thích" và các trang chưa port (dashboard/favourite/login). Hiện tại Angular đang chạy ngay tại domain gốc; đổi giá trị này khi tách sang app.propertyplus.vn.', 'propertyplus'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="pplus_google_map_keys"><?php esc_html_e('Google Maps API Key(s)', 'propertyplus'); ?></label></th>
                    <td>
                        <textarea style="width:420px;height:100px" id="pplus_google_map_keys" name="pplus_google_map_keys"><?php echo esc_textarea(get_option('pplus_google_map_keys', pplus_google_map_default_keys())); ?></textarea>
                        <p class="description">
                            <?php esc_html_e('Mỗi dòng 1 key. Chọn ngẫu nhiên 1 key mỗi lần tải trang (giống Angular production). Mặc định là các key đang chạy thật trên Angular.', 'propertyplus'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
