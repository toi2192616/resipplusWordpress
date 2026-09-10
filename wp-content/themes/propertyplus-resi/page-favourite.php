<?php
/**
 * Vo trang Danh sach yeu thich (/office-favourite) - xem inc/favourite-route.php.
 * Server chi render layout + container rong; assets/js/pplus-favourite.js doc localStorage,
 * goi REST proxy (inc/favourite-rest.php) roi do du lieu vao cac id/class ben duoi (giu dung
 * class cua favourite.component.html de dung lai CSS da port).
 *
 * Modal (tao bo suu tap / them vao bo suu tap) nam o parts/favourite-modals.php, duoc nhung san
 * toan site qua parts/footer.php nen o day khong lap lai.
 */
if (!defined('ABSPATH')) exit;

// Trang can Google Maps de hien VP trong bo suu tap (giong Angular createMap()).
if (function_exists('pplus_enqueue_google_maps')) {
    add_action('wp_enqueue_scripts', 'pplus_enqueue_google_maps', 20);
}

get_header();

// Breadcrumb + nut "Tim kiem van phong" (parts/url-box-content.php) + panel "Bo loc"
// (parts/office-filter-panel.php) - DUNG CHUNG voi moi trang B khac (about/contact/author/news/
// landing...), truoc day trang nay tu rieng 1 div breadcrumb don gian, thieu ca nut tim kiem LAN
// panel di kem (khac voi ban chinh propertyplus.vn) - bug thuc te nguoi dung bao cao: "bản local
// thiếu so với bản chính". Khong truyen 'current' (trang nay khong co dieu kien loc ro rang nhu
// A1/A4) - panel giu nguyen mac dinh, giong page-about.php/page-contact.php.
get_template_part('parts/url-box-content', null, [
    'breadcrumb' => [
        ['name' => pplus_t('Trang chủ', 'Home'), 'url' => home_url((pplus_current_lang() === 'en' ? '/en' : '') . '/')],
        ['name' => pplus_t('Danh sách yêu thích', 'Favourite list')],
    ],
]);
get_template_part('parts/office-filter-panel');
?>
<div class="content-favourite">
    <div class="favourite">
        <!-- SIDEBAR: danh sach bo suu tap (JS do vao #item-list-fa) -->
        <div class="list-favourite">
            <div class="title-1 mb-15"><?php echo esc_html(pplus_t('Danh sách yêu thích', 'Favourite list')); ?></div>
            <input class="form-control pplus-fa-filter" placeholder="<?php echo esc_attr(pplus_t('Nhập tên bộ sưu tập', 'Enter collection name')); ?>" />

            <div class="list-content" id="item-list-fa"><!-- JS render --></div>

            <div class="action-favourite" id="pplus-fa-create"><i class="fa fa-plus"></i> <label><?php echo esc_html(pplus_t('Tạo danh sách mới', 'Create new list')); ?></label></div>
        </div>

        <!-- DETAIL: luoi van phong trong bo suu tap dang chon (JS do vao) -->
        <div class="detail-favourite fullContent" id="pplus-fa-detail">
            <div class="no-list" id="pplus-fa-empty">
                <div class="text-1"><?php echo esc_html(pplus_t('Danh sách yêu thích trống', 'Your favourite list is empty')); ?></div>
                <img src="<?php echo esc_url(PPLUS_THEME_URI . '/assets/Content/Img/img-farourite.png'); ?>" alt="empty" />
            </div>

            <div class="header-f" id="pplus-fa-header" style="display:none">
                <div class="header-f-right">
                    <h1 id="setName"><?php echo esc_html(pplus_t('Văn phòng yêu thích', 'Favourite offices')); ?></h1>
                    <span><?php echo esc_html(pplus_t('Lưu', 'Saved')); ?></span>
                </div>
                <div class="header-f-left">
                    <button class="btn share-link" id="pplus-fa-share">
                        <i><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 16 16"><path d="M10.934 5.175l-4.95 2.522a3.036 3.036 0 0 1 0 .606l4.95 2.522a3 3 0 1 1-.908 1.782L5.13 10.112a3 3 0 1 1 0-4.225l4.896-2.494a3 3 0 1 1 .908 1.782zM2 7v2h2V7H2zm10 5v2h2v-2h-2zm0-10v2h2V2h-2z" fill-rule="evenodd"/></svg></i>
                        <?php echo esc_html(pplus_t('Copy link', 'Copy link')); ?>
                    </button>
                    <button class="btn export-favorite" id="pplus-fa-pdf">
                        <i><svg style="width:20px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path d="M32 64C32 28.7 60.7 0 96 0H256V128c0 17.7 14.3 32 32 32H416V288H248c-13.3 0-24 10.7-24 24s10.7 24 24 24H416V448c0 35.3-28.7 64-64 64H96c-35.3 0-64-28.7-64-64V64zM416 336V288H526.1l-39-39c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l80 80c9.4 9.4 9.4 24.6 0 33.9l-80 80c-9.4 9.4-24.6 9.4-33.9 0s-9.4-24.6 0-33.9l39-39H416zm0-208H288V0L416 128z"/></svg></i>
                        <?php echo esc_html(pplus_t('Xuất file pdf', 'Export PDF')); ?>
                    </button>
                    <button class="btn see-map-favorite" id="pplus-fa-togglemap">
                        <i><svg width="26" height="26" viewBox="0 0 31 31" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M11.889 5.166l.061-.002.059.002c.029.002.058.005.087.009l.021.005c.11.018.22.057.325.119l.067.043 6.549 4.605 6.545-4.601c.604-.425 1.423-.043 1.517.658l.009.135v13.734c0 .27-.113.526-.308.708l-.104.084-7.104 4.994c-.345.242-.759.222-1.069.03l-6.594-4.639-6.548 4.604c-.604.425-1.423.043-1.517-.658L3.875 24.862V11.129c0-.27.113-.526.308-.708l.104-.084 7.104-4.994c.083-.058.169-.101.258-.13l.158-.036.082-.01zM25.19 8.004l-5.166 3.632v11.365l5.166-3.632V8.004zm-14.211-.004L5.812 11.632v11.365l5.167-3.632V8zm1.94-.001v11.365l5.167 3.632V11.632L12.92 7.999z" fill="#212121"/></svg></i>
                        <?php echo esc_html(pplus_t('Xem bằng Bản đồ', 'View on map')); ?>
                    </button>
                </div>
            </div>

            <div class="content-f" id="content-office-f" style="display:none">
                <div class="list-product-office full-content" id="pplus-fa-list"><!-- JS render cac item-office --></div>
            </div>
        </div>

        <!-- Nut hien lai danh sach khi dang o che do map (fullContent=false) -->
        <div class="toggleTopGroup" id="pplus-fa-toggletop" style="display:none">
            <div class="toggleTop">
                <div class="ripple" style="animation-delay:2s"></div>
                <div class="ripple" style="animation-delay:4s"></div>
                <span><?php echo esc_html(pplus_t('Hiển thị', 'Show')); ?></span>
            </div>
        </div>

        <div class="box-map hidden-map" id="pplus-fa-boxmap">
            <div class="action-show-off-map">
                <button title="<?php echo esc_attr(pplus_t('Mở rộng bản đồ', 'Expand map')); ?>" type="button" class="btn-show-off-map" id="pplus-fa-collapsemap">
                    <svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" style="display:block;fill:none;height:16px;width:16px;stroke:currentColor;stroke-width:3;overflow:visible" aria-hidden="true"><g fill="none"><path d="m20 28-11.29289322-11.2928932c-.39052429-.3905243-.39052429-1.0236893 0-1.4142136l11.29289322-11.2928932"></path></g></svg>
                </button>
            </div>
            <div id="mapfarourite"></div>
        </div>
    </div>
</div>
<?php
get_footer();
