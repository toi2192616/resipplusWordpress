<?php
/**
 * A5 - Danh sach duong theo tinh (module Angular "list-street"). Route dang ky trong
 * inc/street-list-route.php (rewrite rule, KHONG phai Page).
 * Port 1:1 tu src/app/modules/list-street/list-street.component.html.
 *
 * Link tung duong tro sang A4 (page-reference.php): /cho-thue-van-phong/{province}/{street-alias}
 * - giong het routerLink trong Angular (list-street.component.html dong 33).
 *
 * TODO (khong chan hien thi):
 * - Khong co Google Maps/search-base o dau trang (isMapshow=false giong Angular that).
 * - Nut nhay chu cai (street-key-item) can 1 doan JS scroll nho - chua port (khong anh huong SEO).
 */
if (!defined('ABSPATH')) exit;

$model = pplus_get_street_list_from_query_vars();

if (empty($model['menuData']) || empty($model['list'])) {
    status_header(404);
    get_header();
    echo '<div class="container" style="padding:40px 0;">';
    pplus_render_api_error();
    echo '</div>';
    get_footer();
    return;
}

$menu         = $model['menuData'];
$lang_prefix  = (pplus_current_lang() === 'en') ? '/en' : '';
$province_url = home_url($lang_prefix . '/' . pplus_office_list_base_slug() . '/' . ($menu['alias'] ?? ''));

get_header();
?>
<div class="street-box">
    <div class="container">
        <h1><?php esc_html_e('Tìm văn phòng theo đường tại', 'propertyplus'); ?> <?php echo esc_html($menu['subTitle'] ?? ''); ?></h1>
    </div>

    <?php if (!empty($model['listRank'])) : ?>
        <div class="street-key-search">
            <div class="container">
                <div class="street-key-content">
                    <?php foreach ($model['listRank'] as $rank) : ?>
                        <a class="street-key-item" href="#rank-<?php echo esc_attr($rank); ?>"><?php echo esc_html($rank); ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="street-list">
        <?php foreach ($model['list'] as $group) : ?>
            <div class="street-list-item">
                <div class="container">
                    <div class="street-list-data">
                        <div class="street-list-left" id="rank-<?php echo esc_attr($group['rank'] ?? ''); ?>">
                            <?php echo esc_html($group['rank'] ?? ''); ?>
                        </div>
                        <div class="street-list-right">
                            <div class="street-list-content">
                                <?php foreach (($group['listStreet'] ?? []) as $street) : ?>
                                    <a class="street-name" href="<?php echo esc_url($province_url . '/' . ($street['alias'] ?? '') . '/'); ?>">
                                        <?php echo esc_html($street['title'] ?? ''); ?>
                                        <i>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="9" height="9" viewBox="0 0 9 9" fill="none">
                                                <path d="M1.1269 6.81241C0.834003 7.10531 0.834003 7.58018 1.1269 7.87307C1.41979 8.16597 1.89466 8.16597 2.18756 7.87307L1.1269 6.81241ZM8.74997 0.999995C8.74997 0.585782 8.41419 0.249995 7.99997 0.249995L1.24997 0.249995C0.835761 0.249995 0.499974 0.585781 0.499975 0.999995C0.499975 1.41421 0.835761 1.74999 1.24997 1.75H7.24997V7.74999C7.24997 8.16421 7.58576 8.49999 7.99997 8.49999C8.41419 8.5 8.74997 8.16421 8.74997 7.74999L8.74997 0.999995ZM2.18756 7.87307L8.5303 1.53033L7.46964 0.469665L1.1269 6.81241L2.18756 7.87307Z" />
                                            </svg>
                                        </i>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php get_footer(); ?>
