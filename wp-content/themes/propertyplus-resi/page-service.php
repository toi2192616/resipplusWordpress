<?php
/**
 * C1 - Trang "Dich vu" (4 TypeSite co dinh, xem inc/service-route.php). Port tu
 * list-service.component.html (PPlusAngularResi): hang goi y nhanh (".suggest-header", dung chung
 * voi page-reference.php qua pplus_render_suggest_header() - xem inc/render-helpers.php), tab nav
 * (".info-list"), 1 trong 4 noi dung con theo TypeSite, va cot phai la danh sach video/bai viet
 * THAT tu API (Service/ServiceType?ListIdSiteBase={typeSite} - KHONG PHAI du lieu cua tab dang
 * xem, xem pplus_get_service_type_list()).
 */
if (!defined('ABSPATH')) exit;

$model = pplus_get_service_from_query_vars();

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
$titles      = pplus_service_type_titles();
$bases       = pplus_service_type_bases();

$sidebar_items = pplus_get_service_type_list($type_site);

$partial_by_type = [
    1 => 'parts/service-the-management',
    2 => 'parts/service-housekeeping',
    3 => 'parts/service-techical',
    4 => 'parts/service-register',
];

get_header();
?>

<?php pplus_render_suggest_header(); ?>

<?php // Breadcrumb "Trang chủ" - port dung tu search-base.component.html dong 36-56
// (".container-aparment > .url-box-content", chi hien khi isShowUrlMenu=true VA isMapshow=false -
// dung CHINH XAC 2 gia tri nay cho trang Dich vu). "ListUrlMenu" (vong lap sau "Trang chủ") LUON
// RONG cho trang nay (xac nhan qua doc list-service.component.ts: "ListUrlMenu: any[] = []", khong
// bao gio duoc gan gia tri o dau ca) nen chi in "Trang chủ" don, khong vong lap them. Khoi nay tao
// ra khoang cach doc/ngang giua hang goi y nhanh va hang tab (".url-box-content{padding:5px 0}" +
// ".mb-20" trong header-component.css, da enqueue site-wide) - nguoi dung phat hien ban local
// thieu khoang cach nay khi doi chieu (14/9). ?>
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
    <div class="service-box-1 row">
        <div class="col-md-12">
            <ul class="info-list oflowX">
                <?php foreach ($bases as $type => $base) : ?>
                    <li class="<?php echo ($type === $type_site) ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(home_url($lang_prefix . '/service/' . pplus_localize($base) . '-' . $type . '/')); ?>">
                            <?php echo esc_html(pplus_localize($titles[$type])); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="col-md-9 service-<?php echo (int) $type_site; ?>">
            <?php get_template_part($partial_by_type[$type_site]); ?>
        </div>

        <div class="col-md-3 table-mg">
            <?php foreach ($sidebar_items as $item) :
                $title    = (string) ($item['title'] ?? '');
                $sub      = (string) ($item['subTitle'] ?? '');
                $content  = pplus_kses_content((string) ($item['content'] ?? '')); ?>
                <div class="_blog-item view-videos js-open-video" data-video-content="<?php echo esc_attr($content); ?>">
                    <img class="_blog-img _img-height-auto" src="<?php echo esc_url(pplus_admin_asset_url($item['img'] ?? '')); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy" />
                    <div class="view-videos-hover">
                        <i>
                            <svg width="100" height="100" viewBox="0 0 74 74" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M69.42 36.4417C69.42 36.2842 69.42 36.1053 69.4128 35.8978C69.4057 35.3181 69.3914 34.6669 69.3771 33.9727C69.3198 31.976 69.2196 29.9865 69.0622 28.1115C68.8475 25.528 68.5326 23.3739 68.1032 21.7566C67.6501 20.0687 66.7619 18.5293 65.5274 17.2923C64.2929 16.0553 62.7553 15.164 61.0684 14.7074C59.0431 14.1635 55.0784 13.8272 49.4963 13.6196C46.8413 13.5194 44.0001 13.455 41.159 13.4193C40.1643 13.4049 39.2411 13.3978 38.4109 13.3906H36.3069C35.4768 13.3978 34.5536 13.4049 33.5588 13.4193C30.7177 13.455 27.8766 13.5194 25.2215 13.6196C19.6395 13.8343 15.6676 14.1707 13.6495 14.7074C11.962 15.1629 10.4238 16.0538 9.18915 17.291C7.95448 18.5282 7.06665 20.0681 6.61465 21.7566C6.1781 23.3739 5.87037 25.528 5.65568 28.1115C5.49823 29.9865 5.39804 31.976 5.34079 33.9727C5.31932 34.6669 5.31216 35.3181 5.30501 35.8978C5.30501 36.1053 5.29785 36.2842 5.29785 36.4417V36.8424C5.29785 36.9999 5.29785 37.1788 5.30501 37.3863C5.31216 37.966 5.32648 38.6173 5.34079 39.3114C5.39804 41.3081 5.49823 43.2976 5.65568 45.1726C5.87037 47.7561 6.18526 49.9102 6.61465 51.5276C7.53068 54.9555 10.2215 57.6607 13.6495 58.5767C15.6676 59.1206 19.6395 59.4569 25.2215 59.6645C27.8766 59.7647 30.7177 59.8291 33.5588 59.8649C34.5536 59.8792 35.4768 59.8863 36.3069 59.8935H38.4109C39.2411 59.8863 40.1643 59.8792 41.159 59.8649C44.0001 59.8291 46.8413 59.7647 49.4963 59.6645C55.0784 59.4498 59.0502 59.1134 61.0684 58.5767C64.4963 57.6607 67.1872 54.9627 68.1032 51.5276C68.5397 49.9102 68.8475 47.7561 69.0622 45.1726C69.2196 43.2976 69.3198 41.3081 69.3771 39.3114C69.3985 38.6173 69.4057 37.966 69.4128 37.3863C69.4128 37.1788 69.42 36.9999 69.42 36.8424V36.4417ZM64.2673 36.8138C64.2673 36.9641 64.2673 37.1287 64.2602 37.3219C64.253 37.8801 64.2387 38.4956 64.2244 39.1611C64.1743 41.0648 64.0741 42.9684 63.9238 44.736C63.7306 47.0404 63.4586 48.9298 63.1223 50.1965C62.6786 51.8496 61.3761 53.1592 59.7301 53.5958C58.2272 53.9966 54.4415 54.3186 49.2959 54.5118C46.691 54.612 43.8856 54.6764 41.0875 54.7122C40.107 54.7265 39.1981 54.7337 38.3823 54.7337H36.3355L33.6304 54.7122C30.8322 54.6764 28.034 54.612 25.4219 54.5118C20.2764 54.3114 16.4834 53.9966 14.9877 53.5958C13.3417 53.1521 12.0393 51.8496 11.5956 50.1965C11.2592 48.9298 10.9873 47.0404 10.794 44.736C10.6437 42.9684 10.5507 41.0648 10.4935 39.1611C10.472 38.4956 10.4648 37.873 10.4577 37.3219C10.4577 37.1287 10.4505 36.9569 10.4505 36.8138V36.4703C10.4505 36.32 10.4505 36.1554 10.4577 35.9622C10.4648 35.404 10.4792 34.7885 10.4935 34.123C10.5436 32.2193 10.6437 30.3157 10.794 28.5481C10.9873 26.2437 11.2592 24.3544 11.5956 23.0877C12.0393 21.4345 13.3417 20.1249 14.9877 19.6883C16.4906 19.2876 20.2764 18.9655 25.4219 18.7723C28.0269 18.6721 30.8322 18.6077 33.6304 18.5719C34.6108 18.5576 35.5197 18.5505 36.3355 18.5505H38.3823L41.0875 18.5719C43.8856 18.6077 46.6838 18.6721 49.2959 18.7723C54.4415 18.9727 58.2344 19.2876 59.7301 19.6883C61.3761 20.132 62.6786 21.4345 63.1223 23.0877C63.4586 24.3544 63.7306 26.2437 63.9238 28.5481C64.0741 30.3157 64.1671 32.2193 64.2244 34.123C64.2459 34.7885 64.253 35.4111 64.2602 35.9622C64.2602 36.1554 64.2673 36.3272 64.2673 36.4703V36.8138ZM30.9897 46.2318L47.5927 36.5705L30.9897 27.0524V46.2318Z" fill="red" />
                            </svg>
                        </i>
                    </div>
                    <?php if ($title !== '') : ?>
                        <div class="_blog-item-title"><?php echo esc_html(mb_strlen($title) < 30 ? $title : (mb_substr($title, 0, 30) . '...')); ?></div>
                    <?php endif; ?>
                    <?php if ($sub !== '') : ?>
                        <div class="_blog-item-sub-title"><?php echo esc_html(mb_strlen($sub) < 70 ? $sub : (mb_substr($sub, 0, 70) . '...')); ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>
