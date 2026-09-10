<?php
/**
 * B5 (+ trang con) - /ve-chung-toi/{alias} (vd "chinh-sach-bao-mat-thong-tin") - port tu
 * about-new.component.html. Duoc load qua template_include trong
 * inc/about-subpage-route.php (query var pplus_about_subpage), KHONG phai WordPress Page (khac
 * voi /ve-chung-toi khong-alias, page-about.php, Template Name "Ve chung toi") - noi dung fetch
 * truc tiep tu About/TinTucHoatDong moi request (co cache transient), giong co che A2/B3.
 *
 * Angular goc (about-new.component.html) CHI co tieu de (h1) + noi dung (content, HTML tu CMS) -
 * cot phai (col-md-4) de RONG (khong co sidebar/lien quan nhu B3) - giu dung, khong tu bia them.
 */
if (!defined('ABSPATH')) exit;

$model = pplus_get_about_subpage_from_query_vars();

get_header();
?>
<div class="container">
    <?php if ($model === null) : ?>
        <?php pplus_render_api_error(); ?>
    <?php else : ?>
        <?php
        get_template_part('parts/url-box-content', null, [
            'breadcrumb' => [
                ['name' => pplus_t('Trang chủ', 'Home'), 'url' => home_url('/')],
                ['name' => $model['title'] ?? ''],
            ],
        ]);
        get_template_part('parts/office-filter-panel');
        ?>
        <div class="about-new">
            <div class="col-md-12">
                <div class="row">
                    <div class="col-md-12 header-right">
                        <div class="data-title">
                            <h1><?php echo esc_html($model['title'] ?? ''); ?></h1>
                        </div>
                    </div>
                </div>
            </div>
            <div class="datalist">
                <div class="container mb-30">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="data-content">
                                <div class="content-1 table-mg blog-p-no-margin">
                                    <?php echo pplus_kses_content($model['content'] ?? ''); ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4"></div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php get_footer(); ?>
