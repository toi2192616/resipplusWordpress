<?php
/**
 * C2 - Trang "Chu nha" (2 TypeSite, xem inc/owner-route.php). Port tu owner-index.component.html
 * (PPlusAngularResi): hang goi y nhanh
 * (".suggest-header", dung chung qua pplus_render_suggest_header()), breadcrumb "Trang chủ" don
 * (ListUrlMenu luon rong that - xac nhan owner-benefit.component.ts dong 15), tab nav (".info-list",
 * CSS da co san tu list-service-component.css, dung chung voi trang Dich vu), 1 trong 2 noi dung
 * theo TypeSite, va cot phai (".col-md-3.table-mg") RONG - xac nhan owner-index.component.html
 * dong 21-39 toan bo noi dung cot nay bi COMMENT trong chinh ban goc (danh sach video, tinh nang
 * chua bao gio bat), KHONG phai thieu sot cua ban port nay.
 */
if (!defined('ABSPATH')) exit;

$model = pplus_get_owner_from_query_vars();

if (!$model) {
    status_header(404);
    get_header();
    echo '<div class="container" style="padding:40px 0;">';
    pplus_render_api_error();
    echo '</div>';
    get_footer();
    return;
}

$type_site   = $model['type_site'];
$lang_prefix = pplus_lang_prefix();
$titles      = pplus_owner_type_titles();
$bases       = pplus_owner_type_bases();

$partial_by_type = [
    1 => 'parts/owner-benefit',
    2 => 'parts/owner-register-leasing',
];

get_header();
?>

<?php pplus_render_suggest_header(); ?>

<?php // Breadcrumb "Trang chủ" - cung khoi HTML that voi page-service.php (ca 2 deu dung
// <app-search-base isShowUrlMenu=true isMapshow=false>, "ListUrlMenu" luon RONG), xem ghi chu
// day du trong page-service.php. ?>
<div class="container-aparment">
    <div class="url-box-content">
        <div class="container">
            <div class="pull-left">
                <div class="col-md-12 text-center mb-20 header-right">
                    <div class="url-content">
                        <div class="url-content-list">
                            <a href="<?php echo esc_url(home_url('/')); ?>"><?php echo esc_html(pplus_localize('Trang chủ')); ?></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="owner-box row">
        <div class="col-md-12">
            <ul class="info-list oflowX">
                <?php foreach ($bases as $type => $base) : ?>
                    <li class="<?php echo ($type === $type_site) ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(home_url($lang_prefix . '/owner/' . pplus_localize($base) . '/')); ?>">
                            <?php echo esc_html(pplus_localize($titles[$type])); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="col-md-9 service-1">
            <?php if (isset($partial_by_type[$type_site])) : ?>
                <?php get_template_part($partial_by_type[$type_site]); ?>
            <?php endif; ?>
        </div>

        <div class="col-md-3 table-mg"></div>
    </div>
</div>

<?php get_footer(); ?>
