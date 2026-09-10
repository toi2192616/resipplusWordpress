<?php
/**
 * Breadcrumb + nut "Tim kiem van phong" - port rut gon tu phan dau search-base.component.html
 * (dong 419-453, khoi *ngIf="isMapshow===false && isShowUrlMenu==true"). Dung chung cho moi
 * trang B2-B6 (va co the ca cac trang khac sau nay) de tranh lap code 5 lan.
 *
 * Nut bam mo parts/office-filter-panel.php (component "Bo loc") qua co che toggle co san trong
 * assets/js/main.js: data-pplus-toggle="search" -> $('#box-search-base').toggleClass('show-search').
 *
 * @param array $args['breadcrumb'] Danh sach {name, url?} - item cuoi (khong url) la trang hien tai.
 */
if (!defined('ABSPATH')) exit;

$breadcrumb  = $args['breadcrumb'] ?? [];
$lang_prefix = (pplus_current_lang() === 'en') ? '/en' : '';
?>
<div class="container">
    <div class="url-box-content">
        <div class="container">
            <div class="pull-left">
                <div class="col-md-12 text-center mb-20 header-right">
                    <div class="url-content">
                        <div class="url-content-list">
                            <?php foreach ($breadcrumb as $i => $item) : ?>
                                <?php if (!empty($item['url'])) : ?>
                                    <a href="<?php echo esc_url($item['url']); ?>">
                                        <?php if ($i > 0) : ?>
                                            <i><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/arrow-29.png'); ?>" alt="Icon" loading="lazy" /></i>
                                            &nbsp;
                                        <?php endif; ?>
                                        <?php echo esc_html($item['name']); ?> &nbsp;
                                    </a>
                                <?php else : ?>
                                    <?php if ($i > 0) : ?>
                                        <i><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/arrow-29.png'); ?>" alt="Icon" loading="lazy" /></i>
                                        &nbsp;
                                    <?php endif; ?>
                                    <?php echo esc_html($item['name']); ?> &nbsp;
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-action-search-base" id="btn-action-filter" data-pplus-toggle="search">
                <i><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/search-filter-white.png'); ?>" loading="lazy" alt="" /></i>
                <?php echo esc_html(pplus_t('Tìm kiếm văn phòng', 'Search for offices')); ?>
            </button>
        </div>
    </div>
</div>
