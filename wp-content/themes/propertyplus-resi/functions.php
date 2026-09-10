<?php
/**
 * PropertyPlus RESI theme bootstrap (ban can ho/nha o).
 * Port tu Angular G:\WorkLamThem\PPlusAgular\PPlusAngular. Theme khong luu du lieu nghiep vu -
 * moi field hien thi (gia, dien tich, anh) lay runtime tu API .NET (Apartment/*, Reference/*,
 * Home/*, Service/*, Owner/*, EasyLiving/*) qua inc/api-client.php.
 *
 * PHASE 0 (nen tang dung chung): assets + enqueue theo dung thu tu angular.json, header/footer,
 * api-client, da ngon ngu vi/en/ja. Cac route/template rieng (A1-A6, B1-B8, C1-C3) bat dan khi
 * lam tung man hinh - xem docs/PORTING_PLAN.md. Cac require route se bo sung o day khi co template.
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PPLUS_THEME_VERSION', '0.1.0');
define('PPLUS_THEME_DIR', get_template_directory());
define('PPLUS_THEME_URI', get_template_directory_uri());

/** Cache-bust theo filemtime() cho CSS/JS RIENG cua theme (khong dung cho vendor officebase tinh). */
function pplus_asset_version(string $relative_path): string {
    $file  = PPLUS_THEME_DIR . '/' . ltrim($relative_path, '/');
    $mtime = file_exists($file) ? filemtime($file) : false;
    return $mtime ? (string) $mtime : PPLUS_THEME_VERSION;
}

// --- Nen tang dung chung (Phase 0) ---
require_once PPLUS_THEME_DIR . '/inc/api-client.php';       // pplus_api_get, lang, localize, header/footer data
require_once PPLUS_THEME_DIR . '/inc/language-route.php';   // strip tien to /en /ja + hreflang (dung PPLUS_LANG)
require_once PPLUS_THEME_DIR . '/inc/settings-page.php';    // trang cau hinh API base / map key
require_once PPLUS_THEME_DIR . '/inc/render-helpers.php';   // helper render dung chung
require_once PPLUS_THEME_DIR . '/inc/breadcrumb.php';       // breadcrumb dung chung

/*
 * TODO Phase 1+: bat dan khi convert tung route sang Apartment/Reference/Service/Owner/EasyLiving:
 * require_once PPLUS_THEME_DIR . '/inc/apartment-list-route.php';   // A1
 * require_once PPLUS_THEME_DIR . '/inc/apartment-detail-route.php'; // A2
 * require_once PPLUS_THEME_DIR . '/inc/reference-route.php';        // A3/A4
 * require_once PPLUS_THEME_DIR . '/inc/street-route.php';           // A6
 * require_once PPLUS_THEME_DIR . '/inc/blog-routes.php';            // B2/B3
 * require_once PPLUS_THEME_DIR . '/inc/service-route.php';          // C1
 * require_once PPLUS_THEME_DIR . '/inc/owner-route.php';            // C2
 * require_once PPLUS_THEME_DIR . '/inc/easyliving-route.php';       // C3
 */

add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'comment-form', 'gallery', 'caption']);
    register_nav_menus([
        'primary' => __('Menu chinh', 'propertyplus-resi'),
        'footer'  => __('Menu footer', 'propertyplus-resi'),
    ]);
});

/** Bo cac tinh nang WordPress khong dung (toi uu frontend). */
add_action('init', function () {
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'wp_shortlink_wp_head');
});

/**
 * CSS officebase - GIU DUNG THU TU angular.json (styles[]) de khong lech layout/z-index.
 * Port thang tu G:\WorkLamThem\PPlusAgular\angular.json dong 39-59 (file .less da compile ->
 * .css qua lessc, xem docs/PORTING_PLAN.md muc 1). Cac file node_modules cua Angular (select2,
 * ngx-spinner) lay ban co san trong officebase/assets/select2 (ngx-spinner: khong dung o Phase 0).
 */
function pplus_officebase_styles(): array {
    return [
        'assets/simple-line-icons/css/simple-line-icons.css',
        'lib/jquery-ui/jquery-ui.css',
        'assets/select2/dist/css/select2.min.css',
        'assets/font-awesome/css/font-awesome.css',
        'assets/famfamfam-flags/dist/sprite/famfamfam-flags.css',
        'assets/boostrap-5.1.3/bootstrap.min.css',
        'assets/toastr/build/toastr.css',
        'assets/bootstrap4-datetimepicker/build/css/bootstrap-datetimepicker.css',
        'assets/bootstrap-daterangepicker/daterangepicker.css',
        'fonts/sf-pro-display/sf-pro-display.css',
        'assets/slick/slick.css',          // compiled tu slick.less
        'assets/slick/slick-theme.css',    // compiled tu slick-theme.less
        'assets/fotorama/fotorama.css',
        'metronic/assets/frontend/layout/css/style.css',
        'metronic/assets/frontend/layout/css/style-responsive.css',
        'style/icon.css',                  // compiled tu icon.less
        'Common/Styles/common.css',        // compiled tu common.less
        'Common/Styles/core.css',          // compiled tu core.less
        'style/layout.css',                // compiled tu layout.less
    ];
}

/** JS officebase - GIU DUNG THU TU angular.json (scripts[]). */
function pplus_officebase_scripts(): array {
    return [
        'assets/select2/dist/js/select2.min.js',
        'assets/abp-web-resources/Abp/Framework/scripts/abp.js',
        'assets/abp-web-resources/Abp/Framework/scripts/libs/abp.jquery.js',
        'Common/Scripts/lodash.js',
        'Common/Scripts/jquery-custom.js',
        'Common/Scripts/jquery.lazy.min.js',
        'Common/Scripts/common.js',
    ];
}

add_action('wp_enqueue_scripts', function () {
    $ob_uri = PPLUS_THEME_URI . '/assets/officebase/';

    // jQuery officebase (v cua Angular) thay cho jQuery WordPress - officebase code + common.js
    // phu thuoc $ toan cuc. De WordPress dung ban nay tren frontend, deregister ban core roi
    // dang ky lai voi handle 'jquery'.
    if (!is_admin()) {
        wp_deregister_script('jquery');
        wp_register_script('jquery', $ob_uri . 'lib/jquery/dist/jquery.min.js', [], PPLUS_THEME_VERSION, true);
        wp_enqueue_script('jquery');
        wp_enqueue_script('pplus-bootstrap', $ob_uri . 'assets/boostrap-5.1.3/bootstrap.bundle.min.js', ['jquery'], PPLUS_THEME_VERSION, true);
        wp_enqueue_script('pplus-hover-dropdown', $ob_uri . 'assets/bootstrap-hover-dropdown/bootstrap-hover-dropdown.js', ['pplus-bootstrap'], PPLUS_THEME_VERSION, true);
        wp_enqueue_script('pplus-toastr', $ob_uri . 'assets/toastr/build/toastr.min.js', ['pplus-bootstrap'], PPLUS_THEME_VERSION, true);
    }

    // --- CSS officebase (dung thu tu) ---
    $prev = null;
    foreach (pplus_officebase_styles() as $i => $path) {
        $handle = "pplus-ob-css-{$i}";
        wp_enqueue_style($handle, $ob_uri . $path, $prev ? [$prev] : [], PPLUS_THEME_VERSION);
        $prev = $handle;
    }

    // CSS goc cua component header/footer Angular (compile bang lessc, khong nam trong officebase).
    wp_enqueue_style('pplus-header-component', PPLUS_THEME_URI . '/assets/css/header-component.css', [$prev], pplus_asset_version('assets/css/header-component.css'));
    wp_enqueue_style('pplus-footer-component', PPLUS_THEME_URI . '/assets/css/footer-component.css', ['pplus-header-component'], pplus_asset_version('assets/css/footer-component.css'));
    $prev = 'pplus-footer-component';

    // --- JS officebase (dung thu tu) ---
    $prev_js = 'pplus-toastr';
    foreach (pplus_officebase_scripts() as $i => $path) {
        $handle = "pplus-ob-js-{$i}";
        wp_enqueue_script($handle, $ob_uri . $path, [$prev_js], PPLUS_THEME_VERSION, true);
        $prev_js = $handle;
    }

    // JS tuong tac header/footer (menu mobile, submenu tap, modal goi/mail/chat) - viet thuan cho
    // ban Resi, thay cho showMenu()/ActionFooter()/ModalContainerPpus() trong 2 component Angular.
    wp_enqueue_script('pplus-resi-ui', PPLUS_THEME_URI . '/assets/js/resi-ui.js', [$prev_js], pplus_asset_version('assets/js/resi-ui.js'), true);
});

/** Resource hints cho domain anh .NET (dung site-wide qua the san pham/anh). */
add_action('wp_head', function () {
    $host = wp_parse_url(pplus_asset_base_url(), PHP_URL_HOST);
    if ($host) {
        printf('<link rel="preconnect" href="https://%1$s" crossorigin />' . "\n", esc_attr($host));
        printf('<link rel="dns-prefetch" href="//%1$s" />' . "\n", esc_attr($host));
    }
}, 1);
