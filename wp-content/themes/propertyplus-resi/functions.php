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
require_once PPLUS_THEME_DIR . '/inc/maps.php';             // key Google Maps that (chua tung duoc nap)
require_once PPLUS_THEME_DIR . '/inc/reference-index-route.php'; // A3/A4 - landing tinh/quan/loai-can-ho
require_once PPLUS_THEME_DIR . '/inc/apartment-list-route.php';   // A1 - /cho-thue-can-ho/ (danh sach tong quat, khong loc tinh)
require_once PPLUS_THEME_DIR . '/inc/apartment-detail-route.php'; // A2 - /cho-thue-can-ho/{province}/{district}/{project}/{id}/
require_once PPLUS_THEME_DIR . '/inc/project-detail-route.php';   // A3/A4 nhanh "Dự án cụ thể" - /cho-thue-can-ho/{province}/{district}/{project-alias}-{id}/
require_once PPLUS_THEME_DIR . '/inc/map-area-search.php';        // REST "Tim kiem khu vuc nay" (map mode mobile)
require_once PPLUS_THEME_DIR . '/inc/service-route.php';          // C1 - trang Dich vu (4 TypeSite)
require_once PPLUS_THEME_DIR . '/inc/service-register-route.php'; // C1 - REST submit "Dang ky dich vu"
require_once PPLUS_THEME_DIR . '/inc/about-subpage-route.php';    // B5 - trang con /ve-chung-toi/{alias}-{id}
require_once PPLUS_THEME_DIR . '/inc/about-route.php';             // B5 - alias EN/JA "about-us" -> Page "ve-chung-toi"
require_once PPLUS_THEME_DIR . '/inc/blog-routes.php';             // B2/B3 - /blogs (danh sach) + /blog/{alias}-{id} (chi tiet)
require_once PPLUS_THEME_DIR . '/inc/owner-route.php';             // C2 - trang Chu nha (2 TypeSite)
require_once PPLUS_THEME_DIR . '/inc/owner-register-route.php';    // C2 - REST submit "Dang ky cho thue nha" (TypeSite 2)
require_once PPLUS_THEME_DIR . '/inc/favourite-route.php';         // B7 - trang /danh-sach-yeu-thich
require_once PPLUS_THEME_DIR . '/inc/favourite-rest.php';          // B7 - REST "favourite-pdf" (2 endpoint con lai chua dung toi, xem docblock file)
require_once PPLUS_THEME_DIR . '/inc/seo-head.php';                // SEO/GEO tap trung: canonical, OG/Twitter, JSON-LD, BreadcrumbList, mo ta du phong
require_once PPLUS_THEME_DIR . '/inc/noindex.php';                 // Chan index toan site cho server TEST (PPLUS_NOINDEX / o tich trong Giao dien > PropertyPlus)
require_once PPLUS_THEME_DIR . '/inc/sitemap.php';                // Sitemap XML: can ho (VI+EN), blog, trang khu vuc/dich vu - tinh san qua WP-Cron theo dot nho
require_once PPLUS_THEME_DIR . '/inc/search-group-route.php';      // REST goi y "Tìm theo tên dự án" khi go o tim kiem (Apartment/_GetFilterGroupList)

/*
 * TODO Phase 1+: A1/A2 da xong (29/9, xem 2 dong require o tren) - bat dan tiep khi convert:
 * require_once PPLUS_THEME_DIR . '/inc/street-route.php';           // A6
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
 * Thu vien vendor chi 1-2 loai trang dung - khong nap site-wide (select2 ~85KB, fotorama ~54KB).
 * select2: chi bo loc "Loc theo" (.order-control, main.js) tren A1/A3/A4 + Du an cu the.
 * fotorama: chi lightbox anh "Xem toan bo anh" (showPhotos()) tren trang chi tiet can ho.
 * Da loai han khoi theme (khong trang nao dung): jquery-ui, famfamfam-flags, datetimepicker,
 * daterangepicker (CSS) va lodash (557KB), abp/abp.jquery, jquery.lazy, bootstrap-hover-dropdown (JS) -
 * xac nhan bang doi chieu selector voi DOM that + grep toan bo theme (30/9/2026).
 */
function pplus_officebase_asset_needed(string $path): bool {
    if (strpos($path, 'select2') !== false) {
        return (bool) (get_query_var('pplus_reference_index') || get_query_var('pplus_apartment_list') || get_query_var('pplus_project_detail'));
    }
    if (strpos($path, 'fotorama') !== false) {
        return (bool) get_query_var('pplus_apartment_detail');
    }
    return true;
}

/**
 * CSS officebase - GIU DUNG THU TU angular.json (styles[]) de khong lech layout/z-index.
 * Port thang tu G:\WorkLamThem\PPlusAgular\angular.json dong 39-59 (file .less da compile ->
 * .css qua lessc, xem docs/PORTING_PLAN.md muc 1). Cac file node_modules cua Angular (select2,
 * ngx-spinner) lay ban co san trong officebase/assets/select2 (ngx-spinner: khong dung o Phase 0).
 */
function pplus_officebase_styles(): array {
    return [
        'assets/simple-line-icons/css/simple-line-icons.css',
        'assets/select2/dist/css/select2.min.css',
        'assets/font-awesome/css/font-awesome.css',
        'assets/boostrap-5.1.3/bootstrap.min.css',
        'assets/toastr/build/toastr.css',
        'fonts/sf-pro-display/sf-pro-display.css',
        'assets/slick/slick.css',          // compiled tu slick.less
        'assets/slick/slick-theme.css',    // compiled tu slick-theme.less (CSS san sang, xem
                                            // pplus_officebase_scripts() ben duoi cho slick.min.js)
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
        'assets/slick/slick.min.js',       // CSS da enqueue o pplus_officebase_styles(); thieu file
                                            // JS nay khien moi carousel tren trang chu (.offices-col,
                                            // .item-blog, .silder-newspapers trong assets/js/main.js)
                                            // khong bao gio chay - $.fn.slick khong ton tai.
        'assets/select2/dist/js/select2.min.js',
        // SUA 30/9 (bug thuc te nguoi dung bao cao: "nhấn show all ảnh chưa được" - loi that trong
        // console: "fotoramaDiv.fotorama is not a function"): CSS ".../fotorama/fotorama.css" da
        // enqueue tu truoc (pplus_officebase_styles()) nhung file JS cua chinh plugin nay
        // (fotorama.js, dinh nghia $.fn.fotorama) thi CHUA TUNG duoc enqueue o dau ca - showPhotos()
        // trong common.js (goi duoi day) luon that bai ngay khi bam "Xem toàn bộ ảnh"/"Show all
        // photo" tren MOI trang co lightbox anh (A2 can ho LAN van phong). Phai nam TRUOC
        // "Common/Scripts/common.js" trong mang nay (thu tu enqueue tuan tu, common.js can $.fn.
        // fotorama co san luc no chay).
        'assets/fotorama/fotorama.js',
        'Common/Scripts/jquery-custom.js',
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
        wp_enqueue_script('pplus-toastr', $ob_uri . 'assets/toastr/build/toastr.min.js', ['pplus-bootstrap'], PPLUS_THEME_VERSION, true);
    }

    // --- CSS officebase (dung thu tu) ---
    // 4 file "compiled tu X.less" (icon/common/core/layout, xem comment trong
    // pplus_officebase_styles()) la OUTPUT BIEN DICH CUA CHINH THEME NAY, KHONG PHAI vendor tinh -
    // truoc day dung chung PPLUS_THEME_VERSION ("0.1.0" co dinh, danh cho vendor khong doi) nen MOI
    // lan sua .less nguon (vd layout.less, sua 17/9 cho ".item-office") deu KHONG lam trinh duyet
    // nguoi dung tai lai file - CSS moi nam dung tren dia nhung trinh duyet van dung ban cache cu
    // (bug thuc te tu phat hien khi kiem tra thay doi ".item-office" khong len hinh du file .css da
    // dung). Doi rieng 4 file nay sang pplus_asset_version() (filemtime, tu dong doi moi lan sua),
    // giu nguyen PPLUS_THEME_VERSION cho cac file vendor con lai (jquery-ui/select2/bootstrap/...).
    $ob_compiled_files = ['style/icon.css', 'Common/Styles/common.css', 'Common/Styles/core.css', 'style/layout.css'];
    $prev = null;
    foreach (pplus_officebase_styles() as $i => $path) {
        if (!pplus_officebase_asset_needed($path)) {
            continue;
        }
        $handle  = "pplus-ob-css-{$i}";
        $version = in_array($path, $ob_compiled_files, true) ? pplus_asset_version('assets/officebase/' . $path) : PPLUS_THEME_VERSION;
        wp_enqueue_style($handle, $ob_uri . $path, $prev ? [$prev] : [], $version);
        $prev = $handle;

        // Font Awesome cat gon (23/10/2026, port tu ban Van phong cad3da9): 27 glyph theme Resi that su dung
        // (2.6 KB) thay cho font day du 77 KB. @font-face nay khai bao SAU font-awesome.css nen trinh duyet
        // uu tien no cho cac ky tu trong unicode-range; icon ngoai danh sach (vd icon trong noi dung CMS) tu
        // roi ve font day du cua font-awesome.css. Them icon FA moi: them ma vao unicode-range va tao lai file:
        //   python -m fontTools.subset fontawesome-webfont.woff2 --unicodes=<danh sach> --flavor=woff2
        //     --output-file=fontawesome-subset.woff2 --layout-features= --no-hinting --name-IDs=
        // U+0020 (dau cach) co trong danh sach vi breadcrumb dung content:"\f105 " (style.css metronic) - thieu
        // no thi dau cach do se keo ca font day du ve.
        // Simple Line Icons: theme chi dung DUY NHAT 1 icon (".icon-location-pin" U+E096, parts/apartment-card.php)
        // trong 189 icon - ban cat gon 0.5 KB thay 30 KB, cung co che roi ve font day du nhu Font Awesome ben duoi.
        if ($path === 'assets/simple-line-icons/css/simple-line-icons.css') {
            $sli_subset = 'assets/officebase/assets/simple-line-icons/fonts/simple-line-icons-subset.woff2';
            wp_add_inline_style($handle, sprintf(
                '@font-face{font-family:"simple-line-icons";src:url(%s) format("woff2");font-weight:400;font-style:normal;font-display:swap;unicode-range:U+0020,U+E096}',
                esc_url(PPLUS_THEME_URI . '/' . $sli_subset . '?ver=' . pplus_asset_version($sli_subset))
            ));
        }
        if ($path === 'assets/font-awesome/css/font-awesome.css') {
            $fa_subset = 'assets/officebase/assets/font-awesome/fonts/fontawesome-subset.woff2';
            wp_add_inline_style($handle, sprintf(
                '@font-face{font-family:FontAwesome;src:url(%s) format("woff2");font-weight:400;font-style:normal;font-display:swap;unicode-range:%s}',
                esc_url(PPLUS_THEME_URI . '/' . $fa_subset . '?ver=' . pplus_asset_version($fa_subset)),
                'U+0020,U+F002,U+F005,U+F007,U+F009,U+F00B,U+F00D,U+F016,U+F053,U+F054,U+F055,U+F064,U+F067,U+F095,U+F099,U+F09A,U+F0C1,U+F0E0,U+F0E1,U+F100,U+F101,U+F104,U+F105,U+F110,U+F112,U+F16A,U+F16D,U+F1F8'
            ));
        }
    }

    // CSS goc cua component header/footer Angular (compile bang lessc, khong nam trong officebase).
    wp_enqueue_style('pplus-header-component', PPLUS_THEME_URI . '/assets/css/header-component.css', [$prev], pplus_asset_version('assets/css/header-component.css'));
    wp_enqueue_style('pplus-footer-component', PPLUS_THEME_URI . '/assets/css/footer-component.css', ['pplus-header-component'], pplus_asset_version('assets/css/footer-component.css'));
    $prev = 'pplus-footer-component';

    // CSS rieng cua trang chu (home.component.less, compile lai tu chinh nguon Resi - ban truoc day
    // la CSS COPY tu theme Van phong, khong khop class nao ca) - chi enqueue tren front page. Khong
    // co search-home-component.css: search-home.component.less cua Resi RONG (0 dong), toan bo style
    // form tim kiem thuc te nam trong teamplate-header.component.less (ViewEncapsulation.None nen
    // Angular gop vao bundle toan cuc) -> da co san trong pplus-header-component o tren, khong can
    // enqueue file rieng.
    if (is_front_page()) {
        wp_enqueue_style('pplus-home-component', PPLUS_THEME_URI . '/assets/css/home-component.css', [$prev], pplus_asset_version('assets/css/home-component.css'));
        $prev = 'pplus-home-component';
    }

    // CSS panel "Bo loc" (search-base.component.less, compile lai tu chinh nguon Resi - ban truoc
    // day CUNG la CSS copy tu theme Van phong, 41 cho nhac "office"). parts/office-filter-panel.php
    // (form #box-search-base panel nay style) da tu lau dung SITE-WIDE (about/contact/author/
    // favourite/office-list/office-detail/landing/single-news/archive-news/reference-index/dich vu -
    // xem get_template_part('parts/office-filter-panel') o tung file), KHONG CON rieng A3/A4 nhu luc
    // comment cu con ghi ("chua co is_page_template rieng vi A1 chua ton tai" - A1 gio DA ton tai).
    // Truoc 17/9, gate nay CHI bat khi "pplus_reference_index" khien MOI trang khac (vd Ve chung toi)
    // hoan toan KHONG CO style nao cho #box-search-base -> panel hien nguyen dang, mo san, day het
    // noi dung tho ngay dau trang (bug thuc te nguoi dung bao cao) - vi vay chuyen sang enqueue
    // SITE-WIDE, khop dung pham vi su dung that cua chinh parts/office-filter-panel.php.
    //
    // Rieng ".pplus-reference-index-component" (reference-index.component.less) truoc day CHI dung
    // cho page-reference.php nen gate theo "pplus_reference_index" - nhung ".pplus-map-price-pin"
    // (marker gia tren #pplus-reference-map, dinh nghia trong CHINH file nay) gio CUNG duoc
    // page-apartment-list.php dung (29/9, cung 1 ban do rut gon reference-map-simple.js) - thieu gate
    // "pplus_apartment_list" se khien marker gia hien THO (chi la <div> chu so, khong co bubble/mau
    // nen/mui ten) tren trang do - bug thuc te nguoi dung bao cao ("bên bản đồ mất các hiệu ứng hiển
    // thị giá" sau khi bam nut tim kiem tu trang chu).
    wp_enqueue_style('pplus-search-base-component', PPLUS_THEME_URI . '/assets/css/search-base-component.css', [$prev], pplus_asset_version('assets/css/search-base-component.css'));
    $prev = 'pplus-search-base-component';
    // "pplus_project_detail" (them 30/9) - page-project-detail.php ("Dự án cụ thể", dung LAI y het
    // template page-reference.php) cung can CUNG 1 CSS nay (breadcrumb/group-oder/data-content...).
    if (get_query_var('pplus_reference_index') || get_query_var('pplus_apartment_list') || get_query_var('pplus_project_detail')) {
        wp_enqueue_style('pplus-reference-index-component', PPLUS_THEME_URI . '/assets/css/reference-index-component.css', [$prev], pplus_asset_version('assets/css/reference-index-component.css'));
        $prev = 'pplus-reference-index-component';
    }

    // CSS trang chi tiet can ho (A2, detail.component.less - compile lai tu chinh nguon Resi 29/9,
    // KHAC han office-detail-component.css/office-detail.less cua theme nay - 2 file do la ban COPY
    // tu theme Van phong (~30% class trung ten nhau nhung nhieu class khac di, vd ".info-grid.
    // apartment-grid" 4 cot rieng cua can ho khong ton tai ben Van phong), khong dung duoc cho trang
    // nay - xem ghi chu dau assets/css/component-source/apartment-detail.less.
    if (get_query_var('pplus_apartment_detail')) {
        wp_enqueue_style('pplus-apartment-detail-component', PPLUS_THEME_URI . '/assets/css/apartment-detail-component.css', [$prev], pplus_asset_version('assets/css/apartment-detail-component.css'));
        $prev = 'pplus-apartment-detail-component';
    }

    // CSS trang Dich vu (list-service.component.less + register-service.component.less, compile
    // lai tu chinh nguon Resi - lan dau xay trang nay nen KHONG co rui ro CSS copy tu ban Van
    // phong nhu cac trang khac). list-service-component.css dung cho CA 4 TypeSite (wrapper +
    // ca 3 component tinh, vi ban goc dung ViewEncapsulation.None nen 1 file gop chung); rieng
    // register-service-component.css chi can khi dang o TypeSite 4.
    $pplus_service_model = get_query_var('pplus_service') ? pplus_get_service_from_query_vars() : null;
    if ($pplus_service_model) {
        wp_enqueue_style('pplus-list-service-component', PPLUS_THEME_URI . '/assets/css/list-service-component.css', [$prev], pplus_asset_version('assets/css/list-service-component.css'));
        $prev = 'pplus-list-service-component';
        if ($pplus_service_model['type_site'] === 4) {
            wp_enqueue_style('pplus-register-service-component', PPLUS_THEME_URI . '/assets/css/register-service-component.css', [$prev], pplus_asset_version('assets/css/register-service-component.css'));
            $prev = 'pplus-register-service-component';
        }
    }

    // CSS trang "Ve chung toi" (about-index.component.less + about-new.component.less, compile lai
    // tu chinh nguon Resi 14/9 - ban truoc day la CSS COPY 100% tu theme Van phong, xem
    // component-source/about.less). "about-component.css" chua tung duoc enqueue o dau ca truoc
    // day (bug rieng, xem memory) nen phai them moi hoan toan, khong phai sua 1 dieu kien co san.
    if (is_page_template('page-about.php')) {
        wp_enqueue_style('pplus-about-component', PPLUS_THEME_URI . '/assets/css/about-component.css', [$prev], pplus_asset_version('assets/css/about-component.css'));
        $prev = 'pplus-about-component';
    }
    if (get_query_var('pplus_about_subpage')) {
        wp_enqueue_style('pplus-about-new-component', PPLUS_THEME_URI . '/assets/css/about-new-component.css', [$prev], pplus_asset_version('assets/css/about-new-component.css'));
        $prev = 'pplus-about-new-component';
    }

    // CSS trang Blog (blog-list.component.less + blog-detail.component.less, compile lai tu chinh
    // nguon Resi 17/9). Giong "about-component.css", 2 file nay da ton tai va da compile dung tu
    // truoc nhung chua tung duoc enqueue o dau ca (inc/blog-routes.php chua tung duoc require trong
    // functions.php nen khong trang nao dung toi cac query var duoi day).
    if (get_query_var('pplus_blog_list')) {
        wp_enqueue_style('pplus-blog-list-component', PPLUS_THEME_URI . '/assets/css/blog-list-component.css', [$prev], pplus_asset_version('assets/css/blog-list-component.css'));
        $prev = 'pplus-blog-list-component';
    }
    if (get_query_var('pplus_blog_detail')) {
        wp_enqueue_style('pplus-blog-detail-component', PPLUS_THEME_URI . '/assets/css/blog-detail-component.css', [$prev], pplus_asset_version('assets/css/blog-detail-component.css'));
        $prev = 'pplus-blog-detail-component';
    }

    // CSS trang "Chu nha" (C2, xem inc/owner-route.php). ".info-list" (tab nav) dung LAI nguyen
    // "list-service-component.css" (da co san cho trang Dich vu C1, la CSS khong scope rieng
    // component nao trong ban goc - an toan dung chung) - enqueue lai file NAY, roi them
    // "owner-component.css" (owner.less, MOI compile 22/9) cho phan rieng cua trang (".service-1-
    // banner"/".service-1-body-1" 2-cot, ".hr-cus", ".box-custom", ".header-title" - deu scope
    // duoi ".owner-box" de tranh de/ghi de nham voi ban 3-cot cua trang Dich vu, xem ghi chu dau
    // owner.less).
    if (get_query_var('pplus_owner')) {
        wp_enqueue_style('pplus-list-service-component', PPLUS_THEME_URI . '/assets/css/list-service-component.css', [$prev], pplus_asset_version('assets/css/list-service-component.css'));
        $prev = 'pplus-list-service-component';
        wp_enqueue_style('pplus-owner-component', PPLUS_THEME_URI . '/assets/css/owner-component.css', [$prev], pplus_asset_version('assets/css/owner-component.css'));
        $prev = 'pplus-owner-component';
    }

    // CSS "Yeu thich" (favourite.component.less + create-farourite.component.less, xem
    // inc/favourite-route.php) - SITE-WIDE (khong gate theo query var): nut ".item-like" xuat hien
    // tren MOI card can ho (parts/apartment-card.php, dung tren nhieu trang) va 2 modal
    // (parts/favourite-modals.php) da duoc nhung san toan site tu footer.php - CSS/JS truoc day
    // chua tung duoc enqueue o dau ca (bug "written but never wired" lan thu N trong theme nay, xem
    // memory) nen nut "Yeu thich" tren MOI trang truoc gio khong co tac dung gi va 2 modal khong co
    // style/JS nao ca.
    wp_enqueue_style('pplus-favourite-component', PPLUS_THEME_URI . '/assets/css/favourite-component.css', [$prev], pplus_asset_version('assets/css/favourite-component.css'));
    $prev = 'pplus-favourite-component';

    // --- JS officebase (dung thu tu) ---
    $prev_js = 'pplus-toastr';
    foreach (pplus_officebase_scripts() as $i => $path) {
        if (!pplus_officebase_asset_needed($path)) {
            continue;
        }
        $handle = "pplus-ob-js-{$i}";
        // common.js/jquery-custom.js la code CUA THEME (sua duoc) - cache-bust theo filemtime, khong dung ver co dinh cua vendor.
        $ver = (strpos($path, 'Common/Scripts/') === 0) ? pplus_asset_version('assets/officebase/' . $path) : PPLUS_THEME_VERSION;
        wp_enqueue_script($handle, $ob_uri . $path, [$prev_js], $ver, true);
        $prev_js = $handle;
    }

    // JS tuong tac header/footer (menu mobile, submenu tap, modal goi/mail/chat) - viet thuan cho
    // ban Resi, thay cho showMenu()/ActionFooter()/ModalContainerPpus() trong 2 component Angular.
    wp_enqueue_script('pplus-resi-ui', PPLUS_THEME_URI . '/assets/js/resi-ui.js', [$prev_js], pplus_asset_version('assets/js/resi-ui.js'), true);

    // Khoi tao cac carousel Slick (trang chu: nhom can ho/biet thu, nhan vien, doi tac, khach hang,
    // kham pha) - moi khoi deu tu kiem tra $('.selector').length nen an toan load moi trang, khong
    // can gioi han is_front_page(). Giong cach lam cua ban Van phong (main.js load site-wide o do).
    wp_enqueue_script('propertyplus-main', PPLUS_THEME_URI . '/assets/js/main.js', ['pplus-resi-ui'], pplus_asset_version('assets/js/main.js'), true);

    // #searchModal (parts/search-modal.php, footer.php) - site-wide vi nut mo (".btn-modal-search")
    // nam trong suggest-header, xuat hien tren moi trang. File rieng (khong gop vao main.js) vi
    // logic kha lon (chon goi y, panel "Chi tiết", dieu huong loc that - xem chinh file JS).
    // Goi y khi go vao o "Bạn muốn đi đâu" (du an + dia diem Google) - dung chung cho #searchModal, panel mobile
    // va o tim kiem trang chu; script Google Maps chi tai khi nguoi dung bat dau go.
    wp_enqueue_script('pplus-search-suggest', PPLUS_THEME_URI . '/assets/js/pplus-search-suggest.js', ['propertyplus-main'], pplus_asset_version('assets/js/pplus-search-suggest.js'), true);
    $pplus_lang = pplus_current_lang();
    wp_add_inline_script('pplus-search-suggest', 'var PPLUS_SUGGEST_CONFIG = ' . wp_json_encode([
        'restUrl'  => rest_url('pplus/v1/search-group'),
        'lang'     => $pplus_lang,
        'mapKey'   => pplus_google_map_key(),
        'listUrl'  => home_url(pplus_lang_prefix() . ($pplus_lang === 'vi' ? '/cho-thue-can-ho/' : '/rental-apartment/')),
        'logoIcon' => PPLUS_THEME_URI . '/assets/officebase/Common/Images/logo.png',
        'pinIcon'  => PPLUS_THEME_URI . '/assets/officebase/Common/Images/makerMap.png',
        'i18n'     => [
            // "Địa điểm" khong co trong tu dien EN/JA cua API (ban goc hien chu tieng Viet o moi ngon ngu).
            'location'  => $pplus_lang === 'ja' ? '場所' : pplus_t('Địa điểm', 'Location'),
            'byProject' => pplus_localize('Tìm theo tên dự án'),
            'byGoogle'  => pplus_localize('Tìm theo định vị google'),
        ],
    ]) . ';', 'before');

    wp_enqueue_script('pplus-search-modal', PPLUS_THEME_URI . '/assets/js/pplus-search-modal.js', ['pplus-search-suggest'], pplus_asset_version('assets/js/pplus-search-modal.js'), true);

    // Panel tim kiem MOBILE (#search-tab-mobile + .searching-tool, parts/search-mobile.php, footer.php) -
    // site-wide (ban that <app-search-base> co mat o gan nhu moi trang).
    wp_enqueue_script('pplus-search-mobile', PPLUS_THEME_URI . '/assets/js/pplus-search-mobile.js', ['pplus-search-suggest'], pplus_asset_version('assets/js/pplus-search-mobile.js'), true);

    // "Yeu thich" (nut ".item-like" tren card + 2 modal cua parts/favourite-modals.php, xem ghi chu
    // enqueue CSS o tren) - SITE-WIDE cung ly do. "detailUrl" tro toi URL THAT cua favourite-detail
    // (chua xay, xem docblock page-favourite.php) - de link Copy/Xem chi tiet DA DUNG san, hoat
    // dong ngay khi trang do duoc xay, khong can sua lai o day sau nay.
    wp_enqueue_script('pplus-favourite', PPLUS_THEME_URI . '/assets/js/pplus-favourite.js', ['propertyplus-main'], pplus_asset_version('assets/js/pplus-favourite.js'), true);
    wp_add_inline_script('pplus-favourite', 'var PPLUS_FAV_CONFIG = ' . wp_json_encode([
        'restUrl'       => rest_url('pplus/v1/'),
        'lang'          => pplus_current_lang() === 'en' ? 'en' : 'vi',
        'detailUrl'     => pplus_lang_url('favourite/favourite-detail'),
        'placeholderImg' => PPLUS_THEME_URI . '/assets/officebase/Common/Images/Rectangle 2936.png',
        'i18n'          => [
            'noCollection'      => pplus_localize('Chưa có bộ sưu tập nào'),
            'pickCollection'    => pplus_localize('Bạn chưa chọn danh mục yêu thích'),
            'added'             => pplus_localize('Thêm vào bộ sưu tập thành công'),
            'needName'          => pplus_localize('Bạn cần nhập tên bộ sưu tập'),
            'createdCollection' => pplus_localize('Thêm mới bộ sưu tập thành công'),
            'renamed'           => pplus_localize('Cập nhật bộ sưu tập thành công'),
            'deletedCollection' => pplus_localize('Xóa bộ sưu tập thành công'),
            'confirmDelColl'    => pplus_localize('Bạn muốn bỏ bộ sưu tập yêu thích này?'),
            'copied'            => pplus_localize('Copy link yêu thích thành công!'),
            'pdfConfirm2'       => pplus_localize('Bạn xác nhận muốn tải file PDF bộ sưu tập này?'),
            'pdfOk'             => pplus_localize('Tải file PDF thành công!'),
            'pdfErr'            => pplus_localize('Không tải được file PDF!'),
            'pdfNoOffice'       => pplus_localize('Không thấy căn hộ nào'),
            'editBtn'           => pplus_localize('Sửa'),
            'editTitle'         => pplus_localize('Đổi tên bộ sưu tập'),
            'createTitle'       => pplus_localize('Tạo bộ sưu tập mới'),
            'addBtn'            => pplus_localize('Thêm vào'),
            'rename'            => pplus_localize('Đổi tên'),
            'delColl'           => pplus_localize('Xóa'),
            'exportPdf'         => pplus_localize('Tải PDF'),
            'share'             => pplus_localize('Copy link'),
            'savedOn'           => pplus_localize('Lưu ngày'),
            'apartmentUnit'     => pplus_localize('căn hộ'),
        ],
    ]) . ';', 'before');

    // Ban do gia don gian cho A1/A3/A4 - xem TODO dau page-reference.php (KHONG dung
    // pplus_enqueue_google_maps()/assets/js/pplus-maps.js co san, 2 cai do van la ban Van phong
    // chua convert cho A1/A2 cua Office - key thi dung chung duoc nhung JS thi khong, viet 1 file
    // JS moi rieng, don gian hon nhieu (khong OverlayView/Places, xem reference-map-simple.js).
    // "pplus_apartment_list"/"pplus_project_detail" (them 29-30/9) - page-apartment-list.php va
    // page-project-detail.php dung CUNG 1 layout ".box-map #pplus-reference-map" voi A3/A4, phai
    // cung dieu kien enqueue nay khong thi ban do khong bao gio tai (script chi kiem tra
    // "pplus_reference_index" truoc day).
    if (get_query_var('pplus_reference_index') || get_query_var('pplus_apartment_list') || get_query_var('pplus_project_detail')) {
        $map_key = pplus_google_map_key();
        if ($map_key !== '') {
            printf('<link rel="preconnect" href="https://maps.googleapis.com" />' . "\n");
            printf('<link rel="preconnect" href="https://maps.gstatic.com" crossorigin />' . "\n");
            wp_enqueue_script('pplus-reference-map', PPLUS_THEME_URI . '/assets/js/reference-map-simple.js', [], pplus_asset_version('assets/js/reference-map-simple.js'), true);
            wp_add_inline_script('pplus-reference-map', 'var PPLUS_MAP_CONFIG = ' . wp_json_encode(['key' => $map_key, 'lang' => pplus_current_lang(), 'areaUrl' => rest_url('pplus/v1/map-area')]) . ';', 'before');
        }
    }

    // Ban do #map-detail cho A2 (chi tiet can ho, 1 marker duy nhat) - dung pplus_enqueue_google_maps()
    // + assets/js/pplus-maps.js (KHAC voi khoi tren, chi danh cho #pplus-reference-map cua A1/A3/A4).
    // Ham nay DA CO SAN tu truoc (inc/maps.php, ghi ro trong chinh docblock "goi tu functions.php,
    // chi tren trang can map A1/A2") nhung CHUA TUNG duoc goi o dau ca (bug "written but never wired"
    // - xem memory) - hau qua: "#map-detail" tren ca A2 Van phong (page-office-detail.php) LAN A2
    // Can ho (page-apartment-detail.php, moi 29/9) deu render div rong, khong co ban do nao ca (bug
    // thuc te nguoi dung bao cao 30/9: "dưới cùng bản đồ chưa hiện"). Goi o day cho CA 2 query var
    // (pplus_office_detail: Van phong; pplus_apartment_detail: Can ho) - cung 1 script/id #map-detail
    // dung chung boi ca 2 trang.
    if (get_query_var('pplus_office_detail') || get_query_var('pplus_apartment_detail')) {
        pplus_enqueue_google_maps();
    }

    // Bo dem (+/-) "Số phòng ngủ"/"Số người ở" tren trang Dang ky dich vu (TypeSite 4) - thu vien
    // CO SAN trong theme tu truoc (assets/officebase/lib/bootstrap-input-spinner.js) nhung CHUA
    // TUNG duoc enqueue/goi o dau (xem plan) - chi can cho trang nay.
    $pplus_service_model_js = get_query_var('pplus_service') ? pplus_get_service_from_query_vars() : null;
    if ($pplus_service_model_js && $pplus_service_model_js['type_site'] === 4) {
        wp_enqueue_script('pplus-input-spinner', $ob_uri . 'lib/bootstrap-input-spinner.js', ['jquery'], PPLUS_THEME_VERSION, true);

        // Cau hinh JS cho form "Dang ky dich vu" (parts/service-register.php + assets/js/main.js) -
        // URL REST that va TOAN BO chuoi hien thi deu truyen tu PHP (khong hardcode trong main.js)
        // de dam bao dung ngon ngu hien tai (giong quy uoc pplus_localize()), VA de tranh lap lai
        // dung 1 bug da gap voi PPLUS_ADVISE_* (inc/advise-form.php): cac hang so do KHONG BAO GIO
        // duoc output qua wp_add_inline_script/wp_localize_script o bat ky dau, khien handler JS
        // tuong ung bi loi "is not defined" ngay khi chay - o day dam bao output that qua chinh
        // enqueue nay, gan lien voi script se dung no.
        wp_add_inline_script('propertyplus-main', 'var PPLUS_SERVICE_REGISTER_CONFIG = ' . wp_json_encode([
            'restUrl'      => rest_url('pplus/v1/service-register'),
            'lang'         => pplus_current_lang() === 'en' ? 'en' : 'vi',
            'yesText'      => pplus_localize('Có'),
            'noText'       => pplus_localize('Không'),
            'errorContact' => pplus_localize('Bạn chưa nhập Email hoặc Số điện thoại'),
            'errorTerms'   => pplus_localize('Bạn chưa xác nhận đồng ý với điều khoản của chúng tôi!'),
            'errorEmail'   => pplus_localize('Email không đúng định dạng!'),
            'errorPhone'   => pplus_localize('Số điện thoại không đúng định dạng!'),
            'confirmMsg'   => pplus_localize('Bạn có chắc chắn muốn gửi thông tin hỗ trợ?'),
            'failMsg'      => pplus_localize('Gửi yêu cầu thất bại, vui lòng thử lại sau.'),
            'warningTitle' => pplus_localize('Cảnh báo'),
        ]) . ';', 'before');
    }

    // Bo dem (+/-) "Số phòng ngủ"/"Số phòng tắm" + cau hinh JS cho form "Dang ky cho thue nha"
    // (TypeSite 2, parts/owner-register-leasing.php + assets/js/main.js) - cung ly do/co che voi
    // khoi TypeSite 4 ngay o tren (PPLUS_OWNER_REGISTER_CONFIG rieng vi khac REST URL/field, xem
    // inc/owner-register-route.php).
    $pplus_owner_model_js = get_query_var('pplus_owner') ? pplus_get_owner_from_query_vars() : null;
    if ($pplus_owner_model_js && $pplus_owner_model_js['type_site'] === 2) {
        wp_enqueue_script('pplus-input-spinner', $ob_uri . 'lib/bootstrap-input-spinner.js', ['jquery'], PPLUS_THEME_VERSION, true);

        wp_add_inline_script('propertyplus-main', 'var PPLUS_OWNER_REGISTER_CONFIG = ' . wp_json_encode([
            'restUrl'      => rest_url('pplus/v1/owner-register'),
            'lang'         => pplus_current_lang() === 'en' ? 'en' : 'vi',
            'yesText'      => pplus_localize('Có'),
            'noText'       => pplus_localize('Không'),
            'errorContact' => pplus_localize('Bạn chưa nhập Email hoặc Số điện thoại'),
            'errorTerms'   => pplus_localize('Bạn chưa xác nhận đồng ý với điều khoản của chúng tôi!'),
            'errorEmail'   => pplus_localize('Email không đúng định dạng!'),
            'errorPhone'   => pplus_localize('Số điện thoại không đúng định dạng!'),
            'confirmMsg'   => pplus_localize('Bạn có chắc chắn muốn gửi thông tin hỗ trợ?'),
            'failMsg'      => pplus_localize('Gửi yêu cầu thất bại, vui lòng thử lại sau.'),
            'warningTitle' => pplus_localize('Cảnh báo'),
        ]) . ';', 'before');
    }
});

/** Resource hints cho domain anh .NET (dung site-wide qua the san pham/anh). */
add_action('wp_head', function () {
    $host = wp_parse_url(pplus_asset_base_url(), PHP_URL_HOST);
    if ($host) {
        printf('<link rel="preconnect" href="https://%1$s" crossorigin />' . "\n", esc_attr($host));
        printf('<link rel="dns-prefetch" href="//%1$s" />' . "\n", esc_attr($host));
    }

    // Preload anh hero trang chu (LCP) - dung dung <source> WebP trong front-page.php (nguong 800px); Angular goc
    // cung preload anh mobile trong index.html. fetchpriority=high de trinh duyet tai truoc CSS/JS khac.
    if (is_front_page()) {
        $img = PPLUS_THEME_URI . '/assets/Content/Img/';
        printf('<link rel="preload" as="image" href="%s" type="image/webp" media="(max-width: 800px)" fetchpriority="high" />' . "\n", esc_url($img . 'home-600x1000.webp'));
        printf('<link rel="preload" as="image" href="%s" type="image/webp" media="(min-width: 801px)" fetchpriority="high" />' . "\n", esc_url($img . 'home-1920x1080.webp'));
    }
}, 1);
