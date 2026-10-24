<?php
/**
 * B7 - "Danh sách yêu thích" (URL that: /danh-sach-yeu-thich, xem inc/favourite-route.php). Port
 * DUNG tu favourite.component.html/.ts (PPlusAngularResi) - day la trang LIET KE CAC BO SUU TAP
 * (moi bo suu tap = 1 the/1 dong voi anh dai dien 4-o + ten + so luong + menu Doi ten/Xoa/Tai PDF/
 * Copy link), KHONG PHAI trang chi tiet 1 bo suu tap (co ban do/luoi can ho ben trong - do la
 * favourite-detail.component, 1 trang RIENG "/favourite/favourite-detail", CHUA xay trong lan nay).
 *
 * SUA LON 22/9: toan bo file nay (+ inc/favourite-route.php, inc/favourite-rest.php,
 * assets/js/pplus-favourite.js, assets/css/component-source/favourite.less) truoc day duoc xay
 * cho ĐÚNG BO CUC CUA favourite-DETAIL (sidebar + luoi van phong + Google Map, dung endpoint that
 * "Office/_GetOfficesInputDto"/"Office/_OfficeMarkerByInputDto") - xac nhan qua doc truc tiep
 * favourite.component.html/.ts (PPlusAngularResi) hoan toan KHONG khop: trang that ĐON GIAN HON
 * NHIEU (chi la 1 luoi the bo suu tap, KHONG API nao ca - LoadList() chi doc localStorage), va URL
 * route cu "office-favourite" + toan bo tu vung "office/projectId" (thay vi "apartment/apartmentId"
 * that cua Resi) xac nhan day la 1 COPY tu theme Van phong chua tung duoc doi chieu/sua, cung LOP
 * loi "Office-copy" da gap nhieu lan trong theme nay (Ve chung toi, Lien he...) - chi lan nay o tang
 * SAU HON (ca route/REST/JS, khong chi CSS/markup). Vo trang sidebar+map+detail CU van con nguyen
 * trong git history, du dung lai (sau khi sua tu vung office->apartment) khi xay favourite-detail
 * that (task rieng, chua lam).
 *
 * Server chi render KHUNG (header/toolbar rong) - assets/js/pplus-favourite.js doc "ListFavourite"/
 * "listApartmentFavourite" tu localStorage (KHONG can goi API/backend gi - giong het LoadList() that,
 * xac nhan qua doc toan bo .ts khong co goi HTTP nao) roi render luoi/bang.
 */
if (!defined('ABSPATH')) exit;

get_header();

pplus_render_suggest_header();

get_template_part('parts/url-box-content', null, [
    'breadcrumb' => [
        ['name' => pplus_t('Trang chủ', 'Home'), 'url' => home_url(pplus_lang_prefix() . '/')],
        // Nhan that: ListUrlMenu = [{linkUrl:'#', nameUrl: Localizer("Yêu thích")}] (favourite.
        // component.ts dong 33-38) - "Yêu thích", KHONG PHAI "Danh sách yêu thích" (nhan do la
        // tieu de ".collection-title" o duoi, khac muc breadcrumb).
        ['name' => pplus_t('Yêu thích', 'Favourite')],
    ],
]);
?>
<div class="center-box">
    <div class="favourite-container row">
        <div class="col-md-6 collection-header">
            <div class="collection-title"><?php echo esc_html(pplus_t('Danh sách yêu thích', 'Favourite list')); ?></div>
            <div class="item-count">
                <span class="count" id="pplus-fa-count">0</span> <span><?php echo esc_html(pplus_t('Danh mục', 'Collections')); ?></span>
            </div>
        </div>
        <div class="col-md-6 collection-toolbar">
            <form id="search-favorite" class="search-input" method="get" autocomplete="off" spellcheck="false" onsubmit="return false;">
                <input class="form-control" name="FavouriteName" id="FavouriteName" placeholder="<?php echo esc_attr(pplus_t('Tìm kiếm theo tên danh sách', 'Search by list name')); ?>" />
                <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/officebase/Common/Icons/gray-search.svg'); ?>" width="44" height="44" alt="" />
            </form>
            <?php // SUA 22/9 (nguoi dung phat hien icon "+" khong dung ban that qua anh chup): ban
            // dau bia them "fa fa-plus" (glyph Font Awesome) chong len "plus-white-ico" - markup
            // that (favourite.component.html dong 23) CHI co "<i class=\"plus-white-ico\"></i>",
            // 1 icon SVG that (assets/officebase/Common/Icons/plus-white.svg, CO SAN san trong
            // theme + CSS ".plus-white-ico" da ported day du trong icon.less - KHONG can Font
            // Awesome gi ca, chi can dung DUNG 1 class that. ?>
            <button class="btn btn-primary" id="btnAddCollectionGrid" aria-label="<?php echo esc_attr(pplus_t('Tạo bộ sưu tập mới', 'Create new collection')); ?>">
                <i class="plus-white-ico"></i>
            </button>
            <div class="action-find">
                <div class="sort-list sort-list-1 pull-left active" data-fa-view="1">
                    <i class="fa fa-th-large" aria-hidden="true"></i>
                </div>
                <div class="sort-list sort-list-2 pull-left" data-fa-view="2">
                    <i class="fa fa-th-list" aria-hidden="true"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="collection-area" id="collection-area-data-table" style="display:none">
        <table>
            <thead>
                <tr>
                    <th><?php echo esc_html(pplus_t('Tên danh mục', 'List name')); ?></th>
                    <th width="200"><?php echo esc_html(pplus_t('Ngày tạo', 'Created')); ?></th>
                    <th width="200"><?php echo esc_html(pplus_t('Số lượng căn', 'Apartments')); ?></th>
                    <th width="200"><?php echo esc_html(pplus_t('Thao tác', 'Actions')); ?></th>
                </tr>
            </thead>
            <tbody id="collection-area-data-body"><!-- JS render --></tbody>
        </table>
    </div>

    <div id="pplus-fa-grid"><!-- JS render .collection-gridbox --></div>
</div>
<?php
get_footer();
