<?php
/**
 * B4 - Trang tac gia /author/{alias} - port rut gon tu detai-tacgia.component.html.
 * Nguon du lieu: Blogs/GetDetailTacGia - xem inc/api-client.php: pplus_get_blog_author().
 * Duoc load qua template_include trong inc/author-route.php (query var pplus_author).
 *
 * TODO (khong chan hien thi): khong co Muc Luc tu dong (giong single-news.php).
 */
if (!defined('ABSPATH')) exit;

$model = pplus_get_author_from_query_vars();
$data  = $model['dataModel'] ?? null;

get_header();
?>
<div class="container">
    <?php if ($data === null) : ?>
        <?php pplus_render_api_error(); ?>
    <?php else : ?>
        <?php
        $lang_prefix = (pplus_current_lang() === 'en') ? '/en' : '';
        get_template_part('parts/url-box-content', null, [
            'breadcrumb' => [
                ['name' => pplus_t('Trang chủ', 'Home'), 'url' => home_url('/')],
                ['name' => pplus_t('Tin tức', 'Blog'), 'url' => home_url($lang_prefix . '/blogs/')],
                ['name' => $data['title'] ?? ''],
            ],
        ]);
        get_template_part('parts/office-filter-panel');
        ?>
        <div class="main-tacgia">
            <div class="contac-tacgia">
                <?php if (!empty($data['img'])) : ?>
                    <div class="img-avata">
                        <img src="<?php echo esc_url(pplus_admin_asset_url($data['img'])); ?>" alt="<?php echo esc_attr($data['title'] ?? ''); ?>" />
                    </div>
                <?php endif; ?>
                <div class="des-tac-gia">
                    <h1 class="title-name-tacgia"><?php echo esc_html($data['title'] ?? ''); ?></h1>
                    <div class="data-tacgia"><?php echo pplus_kses_content($data['description'] ?? ''); ?></div>
                </div>
            </div>

            <div class="solical-tacgia">
                <?php if (!empty($data['textExtension4'])) : ?>
                    <div class="solical-item"><a href="<?php echo esc_url($data['textExtension4']); ?>" target="_blank" rel="nofollow noopener"><i class="fa fa-linkedin" aria-hidden="true"></i></a></div>
                <?php endif; ?>
                <?php if (!empty($data['textExtension2'])) : ?>
                    <div class="solical-item"><a href="<?php echo esc_url($data['textExtension2']); ?>" target="_blank" rel="nofollow noopener"><i class="fa fa-twitter" aria-hidden="true"></i></a></div>
                <?php endif; ?>
                <?php if (!empty($data['textExtension5'])) : ?>
                    <div class="solical-item"><a href="tel:<?php echo esc_attr($data['textExtension5']); ?>" rel="nofollow"><i class="fa fa-phone" aria-hidden="true"></i></a></div>
                <?php endif; ?>
                <?php if (!empty($data['textExtension6'])) : ?>
                    <div class="solical-item"><a href="mailto:<?php echo esc_attr($data['textExtension6']); ?>" rel="nofollow"><i class="fa fa-envelope" aria-hidden="true"></i></a></div>
                <?php endif; ?>
                <?php if (!empty($data['textExtension1'])) : ?>
                    <div class="solical-item"><a href="<?php echo esc_url($data['textExtension1']); ?>" target="_blank" rel="nofollow noopener"><i class="fa fa-facebook" aria-hidden="true"></i></a></div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($data['content'])) : ?>
            <?php pplus_render_toc(pplus_extract_toc($data['content'])); ?>
            <div class="data-content" id="content-ckedit">
                <div class="content-1 table-mg blog-p-no-margin"><?php echo pplus_kses_content($data['content']); ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($model['dataList'])) : ?>
            <div class="blog-list-tacgia">
                <div class="title-list"><?php echo esc_html(pplus_t('Bài viết liên quan', 'Related articles')); ?></div>
                <div class="blog-item-list">
                    <div class="blog-item-list-rs">
                        <?php foreach ($model['dataList'] as $item) : ?>
                            <div class="customer-item">
                                <div class="customer-body">
                                    <a href="<?php echo esc_url(pplus_blog_detail_url($item['alias'] ?? '', (int) ($item['id'] ?? 0))); ?>">
                                        <img src="<?php echo esc_url(pplus_admin_asset_url($item['img'] ?? '')); ?>" alt="<?php echo esc_attr($item['title'] ?? ''); ?>" loading="lazy" />
                                    </a>
                                    <div class="content">
                                        <a class="content-title" href="<?php echo esc_url(pplus_blog_detail_url($item['alias'] ?? '', (int) ($item['id'] ?? 0))); ?>">
                                            <?php echo esc_html($item['title'] ?? ''); ?>
                                        </a>
                                        <div class="content-des"><?php echo esc_html($item['subtitle'] ?? ''); ?></div>
                                        <a class="btn-read" href="<?php echo esc_url(pplus_blog_detail_url($item['alias'] ?? '', (int) ($item['id'] ?? 0))); ?>">
                                            <?php echo esc_html(pplus_t('Xem thêm', 'Read more')); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php get_footer(); ?>
