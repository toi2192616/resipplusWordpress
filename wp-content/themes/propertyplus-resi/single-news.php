<?php
/**
 * B3 - Chi tiet 1 bai tin /blogs/{alias}-{id} - port rut gon tu blog-detail.component.html.
 * Duoc load qua template_include trong inc/blog-routes.php (query var pplus_blog_detail),
 * KHONG dung WP Loop/CPT 'news' - noi dung fetch truc tiep tu Blogs/GetDetail moi request
 * (co cache transient trong pplus_api_get), giong het co che A2 (page-office-detail.php).
 * File nay TRUOC DAY la mot CPT single template doc du lieu tu 1 WP post local + goi ham
 * pplus_get_news_seo() chua bao gio duoc dinh nghia (luon tra ve null, khong crash chi vi
 * entity_id local mac dinh la 0) - da thay hoan toan bang kien truc route dong.
 *
 * Thu tu block (dong y het blog-detail.component.html dong 37-181): row(col-md-8 TOC+content,
 * col-md-4 bai viet lien quan) TRUOC, box tac gia (col-md-12, full width) sau CUNG - KHONG
 * phai o dau trang (da sua sau khi Dev doi chieu screenshot production that, xem feedback).
 *
 * TODO (khong chan hien thi, giong scope "SEO co ban truoc" da chot cho A1/A2):
 * - Khong co khoi "van phong lien quan" (Model.projectList, isShowProduct) - can UI office-card
 *   rieng, se lam sau neu can.
 * - Redirect 301 that (isRedirect/redirectUrl tu API) chua xu ly header Location that su -
 *   hien chi hien thong bao, xem TODO trong code duoi.
 * - Form "#SupportCustomerForm" (khoi .contact-us "Ban dang quan tam") CHUA noi submit that -
 *   nut Gui yeu cau tu van van hien binh thuong (KHONG dung `disabled`, tranh browser tu giam
 *   opacity lam sai mau #3e5fb4 goc), chan submit qua `onsubmit="return false"` tren <form>.
 *   Se noi vao admin (gui mail sales) sau, giong TODO tuong tu o page-contact.php (B6).
 */
if (!defined('ABSPATH')) exit;

$data     = pplus_get_blog_detail_from_query_vars();
$result   = $data['result'] ?? null;
$selected = $result['dataSelected'] ?? null;

// API bao bai da doi alias/id - redirect that (301) sang URL moi, giong seoRedirectService
// ben Angular (blog-detail.component.ts dong 63-71).
if (!empty($data['isRedirect']) && !empty($data['redirectUrl'])) {
    wp_safe_redirect($data['redirectUrl'], (int) ($data['redirectType'] ?? 301));
    exit;
}

get_header();

$content = $selected['content'] ?? '';
// Dung pplus_generate_toc (KHONG pplus_extract_toc cu) - ban cu chi lay <h2> DA co san id nen bai
// tin tuc CU post THU CONG (heading dang "<h2 style=...>" khong co id, xac nhan tren loikaka) khong
// bao gio ra muc luc. Ban nay tu SINH id + long h3 duoi h2, va tra ve $toc['html'] DA gan id de
// anchor "#..." nhay dung (giong het page-reference.php/page-landing.php). Content phia duoi phai
// echo $toc['html'] (KHONG con $content goc) de heading co dung id khop anchor.
$toc = pplus_generate_toc($content);
?>
<div class="container">
    <?php if ($result === null || $selected === null) : ?>
        <?php pplus_render_api_error(); ?>
    <?php else : ?>
        <?php
        $lang_prefix = (pplus_current_lang() === 'en') ? '/en' : '';
        get_template_part('parts/url-box-content', null, [
            'breadcrumb' => [
                ['name' => pplus_t('Trang chủ', 'Home'), 'url' => home_url('/')],
                ['name' => pplus_t('Tin tức', 'Blog'), 'url' => home_url($lang_prefix . '/blogs/')],
                ['name' => $selected['title'] ?? ''],
            ],
        ]);
        get_template_part('parts/office-filter-panel');
        ?>
        <div class="blog-header col-md-12 row">
            <h1 class="data-title-header text-1"><?php echo esc_html($selected['title'] ?? ''); ?></h1>
        </div>

        <div class="datalist">
            <div class="container mb-30">
                <div class="row">
                    <div class="col-md-8">
                        <?php pplus_render_toc_nested($toc['items']); ?>
                        <div class="data-content" id="content-ckedit">
                            <div class="content-1 table-mg blog-p-no-margin">
                                <?php echo pplus_kses_content($toc['html']); ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="blog-list-orther">
                            <div class="title-list"><?php echo esc_html(pplus_t('Bài viết liên quan', 'Related articles')); ?></div>
                            <?php if (!empty($result['dataList'])) : ?>
                                <div class="blog-item-list">
                                    <div class="blog-item-list-rs">
                                        <?php foreach ($result['dataList'] as $item) : ?>
                                            <div class="blog-item">
                                                <div class="blog-body">
                                                    <a href="<?php echo esc_url(pplus_blog_detail_url($item['alias'] ?? '', (int) ($item['id'] ?? 0))); ?>">
                                                        <img src="<?php echo esc_url(pplus_admin_asset_url($item['img'] ?? '')); ?>" alt="<?php echo esc_attr($item['title'] ?? ''); ?>" loading="lazy" />
                                                    </a>
                                                    <div class="content">
                                                        <a class="content-title" href="<?php echo esc_url(pplus_blog_detail_url($item['alias'] ?? '', (int) ($item['id'] ?? 0))); ?>">
                                                            <?php echo esc_html($item['title'] ?? ''); ?>
                                                        </a>
                                                        <a class="btn-read" href="<?php echo esc_url(pplus_blog_detail_url($item['alias'] ?? '', (int) ($item['id'] ?? 0))); ?>">
                                                            <?php echo esc_html(pplus_t('Xem thêm', 'Read more')); ?>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="contact-us">
                            <div class="contact-head">
                                <div class="title"><?php echo esc_html(pplus_t('Bạn đang quan tâm', 'Are you interested')); ?></div>
                                <div class="name"><?php echo esc_html(pplus_t('Hãy gửi thông tin tư vấn cho chúng tôi.', 'Send us your information for advice.')); ?></div>
                            </div>
                            <form id="SupportCustomerForm" onsubmit="return false;">
                                <input type="hidden" name="FavoriteLink" value="<?php echo esc_url(home_url(add_query_arg([], $_SERVER['REQUEST_URI'] ?? ''))); ?>" />
                                <div class="mb-3">
                                    <input class="form-control" name="OwnerName" type="text" maxlength="100" placeholder="<?php echo esc_attr(pplus_t('Họ và tên của bạn ...', 'Your full name ...')); ?>" />
                                </div>
                                <div class="mb-3">
                                    <input class="form-control" name="OwnerEmail" type="email" maxlength="100" placeholder="<?php echo esc_attr(pplus_t('Email của bạn ...', 'Your email ...')); ?>" />
                                </div>
                                <div class="mb-3">
                                    <input class="form-control" name="OwnerMobile" type="tel" maxlength="100" placeholder="<?php echo esc_attr(pplus_t('Số điện thoại', 'Phone number')); ?>" />
                                </div>
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="pplusOwnerCheck" />
                                    <label class="form-check-label" for="pplusOwnerCheck"><?php echo esc_html(pplus_t('Tôi muốn nhận thông tin từ PP', 'I want to receive information from PP')); ?></label>
                                </div>
                                <p><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/Group-5.png'); ?>" class="icon-from-search" alt="Icon" width="29" height="24" /><?php echo esc_html(pplus_t('Tư vấn miễn phí', 'Free consultation')); ?></p>
                                <p><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/Group-5.png'); ?>" class="icon-from-search" alt="Icon" width="29" height="24" /><?php echo esc_html(pplus_t('Giá thuê tốt nhất', 'Best rental prices')); ?></p>
                                <p><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/Group-5.png'); ?>" class="icon-from-search" alt="Icon" width="29" height="24" /><?php echo esc_html(pplus_t('Gửi báo giá nhanh chóng', 'Fast quote delivery')); ?></p>
                                <?php // Khong dung `disabled` - browser tu giam opacity lam nut mo di, sai mau xanh
                                // #3e5fb4 goc. Chan submit that qua onsubmit="return false" tren <form> o tren. ?>
                                <button type="submit" class="btn btn-primary mb-3" title="<?php echo esc_attr(pplus_t('Chưa nối API gửi liên hệ thật (TODO) - sẽ nối vào admin để gửi mail sales', 'Not yet wired to the real API (TODO) - will be connected to send sales emails')); ?>">
                                    <?php echo esc_html(pplus_t('Gửi yêu cầu tư vấn', 'Send request')); ?>
                                </button>
                                <a href="tel:0865 364 866" class="btn btn-default"><?php echo esc_html(pplus_t('Hoặc gọi', 'Or call')); ?> 0865 364 866</a>
                            </form>
                        </div>
                    </div>

                    <?php if (!empty($result['dataModel']['alias'])) : $author = $result['dataModel']; ?>
                        <?php $author_url = home_url($lang_prefix . '/author/' . $author['alias']); ?>
                        <div class="col-md-12">
                            <div class="main-tacgia">
                                <div class="contac-tacgia">
                                    <a href="<?php echo esc_url($author_url); ?>" class="img-avata">
                                        <img src="<?php echo esc_url(pplus_admin_asset_url($author['img'] ?? '')); ?>" alt="<?php echo esc_attr($author['title'] ?? ''); ?>" loading="lazy" />
                                    </a>
                                    <div class="des-tac-gia">
                                        <div class="title-1"><?php echo esc_html(pplus_t('Tác giả', 'Author')); ?></div>
                                        <a href="<?php echo esc_url($author_url); ?>" class="title-name-tacgia"><?php echo esc_html($author['title'] ?? ''); ?></a>
                                        <?php if (!empty($author['description'])) : ?>
                                            <div class="data-tacgia"><?php echo pplus_kses_content($author['description']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="solical-tacgia">
                                    <?php if (!empty($author['textExtension4'])) : ?>
                                        <div class="solical-item"><a href="<?php echo esc_url($author['textExtension4']); ?>" target="_blank" rel="nofollow noopener"><i class="fa fa-linkedin" aria-hidden="true"></i></a></div>
                                    <?php endif; ?>
                                    <?php if (!empty($author['textExtension2'])) : ?>
                                        <div class="solical-item"><a href="<?php echo esc_url($author['textExtension2']); ?>" target="_blank" rel="nofollow noopener"><i class="fa fa-twitter" aria-hidden="true"></i></a></div>
                                    <?php endif; ?>
                                    <?php if (!empty($author['textExtension5'])) : ?>
                                        <div class="solical-item"><a href="tel:<?php echo esc_attr($author['textExtension5']); ?>" rel="nofollow"><i class="fa fa-phone" aria-hidden="true"></i></a></div>
                                    <?php endif; ?>
                                    <?php if (!empty($author['textExtension6'])) : ?>
                                        <div class="solical-item"><a href="mailto:<?php echo esc_attr($author['textExtension6']); ?>" rel="nofollow"><i class="fa fa-envelope" aria-hidden="true"></i></a></div>
                                    <?php endif; ?>
                                    <?php if (!empty($author['textExtension1'])) : ?>
                                        <div class="solical-item"><a href="<?php echo esc_url($author['textExtension1']); ?>" target="_blank" rel="nofollow noopener"><i class="fa fa-facebook" aria-hidden="true"></i></a></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php get_footer(); ?>
