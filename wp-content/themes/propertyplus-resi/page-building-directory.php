<?php
/**
 * Trang "Tat ca toa nha" /{vi|en|ja}/buildings - xem inc/building-directory.php.
 * Giao dien theo khuon trang "Danh sach duong" cua ban Van phong (the khoi mau xanh o ben trai + luoi ten ben phai + hang chip nhay nhom),
 * doi chu cai thanh QUAN. Moi ten la 1 the <a> that toi trang du an (de Google tim thay).
 */
if (!defined('ABSPATH')) exit;

$groups = pplus_building_directory_groups();
$total  = pplus_building_directory_count();
$text   = pplus_building_directory_texts($total);
$built  = (string) (pplus_building_directory_raw()['built'] ?? '');

get_header();
?>
<div class="container">
    <?php
    get_template_part('parts/url-box-content', null, [
        'breadcrumb' => [
            ['name' => pplus_localize('Trang chủ'), 'url' => pplus_home_url()],
            ['name' => $text['h1']],
        ],
    ]);
    get_template_part('parts/office-filter-panel');
    ?>
    <div class="bd-box">
        <h1 class="bd-title"><?php echo esc_html($text['h1']); ?></h1>
        <?php if ($total < 1) : ?>
            <p class="bd-intro"><?php echo esc_html(pplus_bd_t(['Danh sách đang được cập nhật. Vui lòng quay lại sau.', 'The list is being updated. Please check back soon.', '一覧は現在更新中です。しばらくしてから、もう一度お越しください。'])); ?></p>
        <?php else : ?>
            <p class="bd-intro"><?php echo esc_html($text['intro']); ?></p>

            <nav class="bd-jump" aria-label="<?php echo esc_attr(pplus_bd_t(['Chọn quận', 'Jump to a district', '区を選ぶ'])); ?>">
                <?php foreach ($groups as $i => $g) : ?>
                    <a href="#bd-<?php echo (int) $i + 1; ?>"><?php echo esc_html($g['label']); ?><span><?php echo (int) count($g['items']); ?></span></a>
                <?php endforeach; ?>
            </nav>

            <?php foreach ($groups as $i => $g) : ?>
                <section class="bd-group" id="bd-<?php echo (int) $i + 1; ?>">
                    <h2 class="bd-district"><?php echo esc_html($g['label']); ?><span><?php echo esc_html(sprintf(pplus_bd_t(['%d tòa nhà', count($g['items']) === 1 ? '%d building' : '%d buildings', '%d棟']), count($g['items']))); ?></span></h2>
                    <ul class="bd-list">
                        <?php foreach ($g['items'] as $b) : ?>
                            <li><a href="<?php echo esc_url($b['url']); ?>"><?php echo esc_html($b['name']); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endforeach; ?>

            <?php if ($built !== '') : ?>
                <p class="bd-note"><?php echo esc_html(sprintf(pplus_bd_t(['Danh sách cập nhật ngày %s. Tòa nhà chưa có căn hộ đang cho thuê không được liệt kê.', 'List updated on %s. Buildings with no apartment currently for rent are not listed.', '一覧の更新日：%s。現在お取り扱いのお部屋がない建物は掲載しておりません。']), $built)); ?></p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php get_footer(); ?>
