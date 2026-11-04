<?php
/**
 * Trang chi tiet 1 danh sach yeu thich: /{lang}/favourite/favourite-detail?fshare_link={id} | ?ids=1,2&IdProject=3
 * Port favourite-detail.component.html (PPlusAngularResi) - xem inc/favourite-route.php (route) + inc/favourite-items-rest.php (du lieu).
 *
 * Server chi render KHUNG: danh sach yeu thich nam o localStorage cua trinh duyet nen JS (assets/js/pplus-favourite-detail.js) doc
 * localStorage, goi REST lay the, ve ban do va xu ly "bo yeu thich" / "Copy link" / "Xoa danh sach".
 */
if (!defined('ABSPATH')) exit;

get_header();

pplus_render_suggest_header();

get_template_part('parts/url-box-content', null, [
    'breadcrumb' => [
        ['name' => pplus_localize('Trang chủ'), 'url' => pplus_home_url()],
        ['name' => pplus_t('Yêu thích', 'Favourite'), 'url' => pplus_lang_url('danh-sach-yeu-thich')],
        ['name' => pplus_localize('Chi tiết yêu thích')],
    ],
]);
?>
<div class="center-box">
    <div id="pplus-fd" class="fd-page">
        <div class="fd-head">
            <div class="fd-head-info">
                <div class="fd-date" id="fd-date"></div>
                <h1 class="fd-title" id="fd-title"><?php echo esc_html(pplus_localize('Chi tiết yêu thích')); ?></h1>
                <div class="fd-count"><span id="fd-count">0</span> <span id="fd-count-label"><?php echo esc_html(pplus_localize('căn hộ')); ?></span></div>
            </div>
            <div class="fd-actions" id="fd-actions" hidden>
                <button type="button" class="fd-btn" id="fd-copy"><?php echo esc_html(pplus_localize('Copy link')); ?></button>
                <button type="button" class="fd-btn fd-btn-danger" id="fd-delete" hidden><?php echo esc_html(pplus_localize('Xóa')); ?></button>
            </div>
        </div>

        <div class="fd-layout">
            <div class="fd-main">
                <div class="fd-state" id="fd-state"><?php echo esc_html(pplus_t('Đang tải...', 'Loading...')); ?></div>
                <div class="fd-grid" id="fd-apartments"></div>
                <div class="fd-group-title" id="fd-projects-title" hidden><?php echo esc_html(pplus_localize('Dự án')); ?></div>
                <div class="fd-grid" id="fd-projects"></div>
                <p class="fd-back"><a href="<?php echo esc_url(pplus_lang_url('danh-sach-yeu-thich')); ?>">&larr; <?php echo esc_html(pplus_localize('Quay lại danh sách yêu thích')); ?></a></p>
            </div>
            <aside class="fd-map-box" id="fd-map-box" hidden>
                <div id="mapfarourite"></div>
            </aside>
        </div>
    </div>
</div>
<?php
get_footer();
