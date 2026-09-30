<?php
/**
 * B2 - Danh sach tin tuc /blogs - port rut gon tu blog-list.component.html.
 * Nguon du lieu: Blogs/GetIndex - xem inc/api-client.php: pplus_get_blog_list().
 * Duoc load qua template_include trong inc/blog-routes.php (query var pplus_blog_list),
 * KHONG dung WP_Query/have_posts() - danh sach tin tuc la du lieu song tu .NET, giong het
 * cach page-office-list.php (A1) khong dung WP_Query cho van phong.
 *
 * SUA 17/9: bo "parts/office-filter-panel.php" (panel Bo loc Van phong bia them nham, xem
 * parts/url-box-content.php) va them pplus_render_suggest_header() con thieu - xac nhan qua doc
 * dung blog-list.component.html dong 1 (<app-search-base isMapshow=false isShowUrlMenu=true>) +
 * doi chieu truc tiep propertyplus.com.vn/en/blogs.
 */
if (!defined('ABSPATH')) exit;

get_header();
pplus_render_suggest_header();

$lang_prefix = (pplus_current_lang() === 'en') ? '/en' : '';
$catalogid   = sanitize_text_field($_GET['catalogid'] ?? '');
$paged       = max(1, (int) ($_GET['page'] ?? 1));

$result = pplus_get_blog_list([
    'catalogid' => $catalogid,
    'page'      => $paged,
]);

$page_size   = 16;
$total_item  = (int) ($result['totalItem'] ?? 0);
$total_pages = max(1, (int) ceil($total_item / $page_size));

// Ten catalog dang chon, hien tren nut dropdown mobile (".dropdownListCatalog" - xem ghi chu
// duoi day). "" = "Tat ca".
$current_catalog_name = pplus_t('Tất cả', 'All');
foreach ((array) ($result['listCatalog'] ?? []) as $catalog) {
    if ((string) $catalogid === (string) ($catalog['id'] ?? '')) {
        $current_catalog_name = $catalog['nameCatalog'] ?? $current_catalog_name;
        break;
    }
}
?>
<?php
get_template_part('parts/url-box-content', null, [
    'breadcrumb' => [
        ['name' => pplus_t('Trang chủ', 'Home'), 'url' => home_url('/')],
        ['name' => pplus_t('Tin tức', 'Blog')],
    ],
]);
?>
<div class="container box-container">
    <div class="cusomer-box row">
        <div class="col-md-12 table-mg blog-p-no-margin">
            <div class="customer-header">
                <div class="catalog-content">
                    <div class="content-list-children">
                        <a class="blog-name <?php echo ($catalogid === '') ? 'active' : ''; ?>" href="<?php echo esc_url(pplus_blog_list_url(['catalogid' => '', 'page' => null])); ?>">
                            <?php echo esc_html(pplus_t('Tất cả', 'All')); ?>
                        </a>
                        <?php foreach ((array) ($result['listCatalog'] ?? []) as $catalog) : ?>
                            <a class="blog-name <?php echo ((string) $catalogid === (string) ($catalog['id'] ?? '')) ? 'active' : ''; ?>" href="<?php echo esc_url(pplus_blog_list_url(['catalogid' => $catalog['id'] ?? '', 'page' => null])); ?>">
                                <?php echo esc_html($catalog['nameCatalog'] ?? ''); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <?php // Dropdown chon catalog - CHI hien tren mobile (".dropdownListCatalog", CSS da co
                    // san tu truoc trong blog-list-component.css nhung thieu markup - xem ghi chu tai do).
                    // Doi chieu dropdownListCatalog ben Angular (blog-list.component.html) nhung KHONG
                    // dung "data-bs-toggle=dropdown" cua Bootstrap 5 - site load CA "bootstrap.min.js"
                    // rieng LAN "bootstrap.bundle.min.js" (functions.php), 2 ban dong de nhau khien
                    // dropdown component that (Popper) loi "createPopper is not a function" (da gap
                    // thuc te khi test). Toggle bang tay qua "[data-pplus-toggle]" (main.js), giong
                    // moi dropdown khac trong theme nay, khong can Popper vi khong can dinh vi dong. ?>
                    <div class="dropdownListCatalog dropdown">
                        <button type="button" id="btnListCatalog" class="dropdown-toggle btn btn-outline-primary" data-pplus-toggle="blog-catalog" aria-expanded="false"><?php echo esc_html($current_catalog_name); ?></button>
                        <div class="dropdown-menu" aria-labelledby="btnListCatalog">
                            <a class="dropdown-item blog-name <?php echo ($catalogid === '') ? 'active' : ''; ?>" href="<?php echo esc_url(pplus_blog_list_url(['catalogid' => '', 'page' => null])); ?>">
                                <?php echo esc_html(pplus_t('Tất cả', 'All')); ?>
                            </a>
                            <?php foreach ((array) ($result['listCatalog'] ?? []) as $catalog) : ?>
                                <a class="dropdown-item blog-name <?php echo ((string) $catalogid === (string) ($catalog['id'] ?? '')) ? 'active' : ''; ?>" href="<?php echo esc_url(pplus_blog_list_url(['catalogid' => $catalog['id'] ?? '', 'page' => null])); ?>">
                                    <?php echo esc_html($catalog['nameCatalog'] ?? ''); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-12 table-mg customer-list">
            <?php if ($result === null) : ?>
                <?php pplus_render_api_error(); ?>
            <?php elseif (empty($result['listBlog'])) : ?>
                <div class="pplus-notice"><?php echo esc_html(pplus_t('Chưa có bài viết nào.', 'No articles yet.')); ?></div>
            <?php else : ?>
                <?php foreach ($result['listBlog'] as $item) : ?>
                    <div class="customer-item">
                        <div class="customer-body">
                            <a href="<?php echo esc_url(pplus_blog_detail_url($item['alias'] ?? '', (int) ($item['id'] ?? 0))); ?>">
                                <img src="<?php echo esc_url(pplus_admin_asset_url($item['img'] ?? '')); ?>" alt="<?php echo esc_attr($item['title'] ?? ''); ?>" loading="lazy" />
                            </a>
                            <div class="content">
                                <a href="<?php echo esc_url(pplus_blog_detail_url($item['alias'] ?? '', (int) ($item['id'] ?? 0))); ?>" class="content-title">
                                    <?php echo esc_html($item['title'] ?? ''); ?>
                                </a>
                                <div class="content-des"><?php echo esc_html($item['subTitle'] ?? ''); ?></div>
                                <a class="btn-read" href="<?php echo esc_url(pplus_blog_detail_url($item['alias'] ?? '', (int) ($item['id'] ?? 0))); ?>">
                                    <?php echo esc_html(pplus_t('Xem thêm', 'Read more')); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if ($total_pages > 1) : ?>
            <nav class="pplus-pagination" style="margin: 20px 0; text-align:center; width:100%;">
                <?php if ($paged > 1) : ?>
                    <a href="<?php echo esc_url(pplus_blog_list_url(['page' => $paged - 1])); ?>">&laquo; <?php echo esc_html(pplus_t('Trang trước', 'Previous')); ?></a>
                <?php endif; ?>
                <span style="margin: 0 10px;"><?php echo esc_html($paged); ?> / <?php echo esc_html($total_pages); ?></span>
                <?php if ($paged < $total_pages) : ?>
                    <a href="<?php echo esc_url(pplus_blog_list_url(['page' => $paged + 1])); ?>"><?php echo esc_html(pplus_t('Trang sau', 'Next')); ?> &raquo;</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    </div>
</div>
<?php get_footer(); ?>
