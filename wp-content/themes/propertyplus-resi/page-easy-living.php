<?php
/**
 * C3 - Trang tien ich "Easy living" /{lang}/easy-living/{id} - port rut gon tu easy-living.component.html.
 * Nguon du lieu: {lang}/EasyLiving/_Detail - xem inc/easyliving-route.php: pplus_get_easy_living().
 * Khac ban goc: khong co o "Binh luan" (khong noi vao dau, textarea trang) va ban do dung iframe Google Maps (khong can API key / JS rieng).
 */
if (!defined('ABSPATH')) exit;

$model = pplus_easy_living_current();
$d     = $model['detail'] ?? null;
if ($d === null) {
    // Khong co du lieu: 404 that (id khong ton tai, backend 302 ve dang nhap). Neu thuc ra la API sap tam thoi thi
    // inc/response-headers.php doi sang 503 + Retry-After (co co 'api-failure' + 'error-notice').
    status_header(404);
}

get_header();
?>
<div class="container">
    <?php if ($d === null) : ?>
        <?php pplus_render_api_error(); ?>
    <?php else : ?>
        <?php
        $lang_prefix = pplus_lang_prefix();
        $photos      = array_values(array_filter((array) ($d['photoList'] ?? []), static fn($p) => is_string($p) && $p !== ''));
        $others      = (array) ($model['anotherLocationList'] ?? []);
        $tel         = trim((string) ($d['tel'] ?? ''));
        $open        = trim((string) ($d['openTime'] ?? ''));
        $close       = trim((string) ($d['closeTime'] ?? ''));
        $website     = trim((string) ($d['website'] ?? ''));
        $facebook    = trim((string) ($d['facebook'] ?? ''));
        $lat         = (float) ($d['locationLat'] ?? 0);
        $lng         = (float) ($d['locationLng'] ?? 0);

        get_template_part('parts/url-box-content', null, [
            'breadcrumb' => [
                ['name' => pplus_localize('Trang chủ'), 'url' => pplus_home_url()],
                ['name' => $d['name']],
            ],
        ]);
        get_template_part('parts/office-filter-panel');
        ?>
        <div class="el-detail">
            <div class="el-main">
                <?php if ($photos) : ?>
                    <div class="el-photos">
                        <img class="el-hero" src="<?php echo esc_url(pplus_webp_url(pplus_admin_asset_url($photos[0]), 800)); ?>" alt="<?php echo esc_attr($d['name']); ?>" width="800" height="600" fetchpriority="high" />
                        <?php if (count($photos) > 1) : ?>
                            <div class="el-thumbs">
                                <?php foreach (array_slice($photos, 1, 12) as $i => $p) : ?>
                                    <img src="<?php echo esc_url(pplus_webp_url(pplus_admin_asset_url($p), 400)); ?>" alt="<?php echo esc_attr($d['name'] . ' ' . ($i + 2)); ?>" width="90" height="68" loading="lazy" />
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="el-content">
                    <?php if (pplus_easy_living_type($d) !== '') : ?>
                        <span class="el-type"><?php echo esc_html(pplus_easy_living_type($d)); ?></span>
                    <?php endif; ?>
                    <h1 class="el-name"><?php echo esc_html($d['name']); ?></h1>
                    <?php if (!empty($d['description'])) : ?>
                        <div class="el-description"><?php echo wp_kses_post(wpautop(esc_html((string) $d['description']))); ?></div>
                    <?php endif; ?>
                </div>

                <?php if ($others) : ?>
                    <div class="el-others">
                        <div class="el-others-title"><?php echo esc_html(pplus_localize('Mọi người cũng tìm kiếm')); ?></div>
                        <div class="el-others-list">
                            <?php foreach ($others as $o) : ?>
                                <?php
                                if (empty($o['id']) || empty($o['name'])) {
                                    continue;
                                }
                                $thumb = '';
                                foreach ((array) ($o['photoList'] ?? []) as $p) {
                                    if (is_string($p) && $p !== '') {
                                        $thumb = $p;
                                        break;
                                    }
                                }
                                ?>
                                <a class="el-card" href="<?php echo esc_url(home_url($lang_prefix . '/easy-living/' . (int) $o['id'])); ?>">
                                    <?php if ($thumb !== '') : ?>
                                        <img src="<?php echo esc_url(pplus_webp_url(pplus_admin_asset_url($thumb), 400)); ?>" alt="<?php echo esc_attr($o['name']); ?>" width="220" height="130" loading="lazy" />
                                    <?php endif; ?>
                                    <span class="el-card-body">
                                        <?php if (pplus_easy_living_type($o) !== '') : ?><span class="el-card-type"><?php echo esc_html(pplus_easy_living_type($o)); ?></span><?php endif; ?>
                                        <span class="el-card-name"><?php echo esc_html($o['name']); ?></span>
                                        <?php if (!empty($o['address'])) : ?><span class="el-card-addr"><?php echo esc_html($o['address']); ?></span><?php endif; ?>
                                    </span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <aside class="el-side">
                <?php if ($lat && $lng) : ?>
                    <iframe class="el-map" title="<?php echo esc_attr($d['name']); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                            src="<?php echo esc_url('https://www.google.com/maps?q=' . $lat . ',' . $lng . '&z=16&output=embed'); ?>"></iframe>
                <?php endif; ?>
                <div class="el-info">
                    <div class="el-info-title"><?php echo esc_html(pplus_localize('Thông tin')); ?></div>
                    <?php if (!empty($d['address'])) : ?>
                        <div class="el-info-row">
                            <span class="el-info-text"><?php echo esc_html($d['address']); ?></span>
                            <?php if ($lat && $lng) : ?>
                                <a class="el-info-link" target="_blank" rel="nofollow noopener" href="<?php echo esc_url('https://www.google.com/maps/search/?api=1&query=' . $lat . ',' . $lng); ?>"><?php echo esc_html(pplus_localize('Xem chỉ đường')); ?></a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <div class="el-info-row">
                        <span class="el-info-text"><?php echo esc_html($tel !== '' ? $tel : pplus_localize('Điện thoại')); ?></span>
                        <?php if ($tel !== '') : ?>
                            <a class="el-info-link" href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $tel)); ?>" rel="nofollow"><?php echo esc_html(pplus_localize('Gọi')); ?></a>
                        <?php endif; ?>
                    </div>
                    <?php if ($open !== '' || $close !== '') : ?>
                        <div class="el-info-row">
                            <span class="el-info-text"><?php echo esc_html(pplus_localize('Giờ mở cửa')); ?></span>
                            <span class="el-info-value"><?php echo esc_html(($open !== '' ? $open : '0h') . ' - ' . ($close !== '' ? $close : '0h')); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($facebook !== '') : ?>
                        <div class="el-info-row">
                            <span class="el-info-text">Facebook</span>
                            <a class="el-info-link" target="_blank" rel="nofollow noopener" href="<?php echo esc_url($facebook); ?>"><?php echo esc_html(preg_replace('#^https?://(www\.)?#i', '', $facebook)); ?></a>
                        </div>
                    <?php endif; ?>
                    <?php if ($website !== '') : ?>
                        <div class="el-info-row">
                            <span class="el-info-text">Website</span>
                            <a class="el-info-link" target="_blank" rel="nofollow noopener" href="<?php echo esc_url($website); ?>"><?php echo esc_html(preg_replace('#^https?://(www\.)?#i', '', $website)); ?></a>
                        </div>
                    <?php endif; ?>
                </div>
            </aside>
        </div>
    <?php endif; ?>
</div>
<?php
get_footer();
