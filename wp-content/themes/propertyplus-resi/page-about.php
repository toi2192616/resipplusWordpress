<?php
/**
 * Template Name: Ve chung toi
 * Trang /ve-chung-toi (B5) - port tu about-index.component.html.
 *
 * Phan lon noi dung trang la copy marketing TINH (khong doi theo API - giong Angular goc,
 * cac chuoi nay hard-code qua Localizer() ben Angular, khong phai lay tu CMS). Chi 2 phan
 * dong tu About/GetIndex: doi ngu nhan vien (listUser) va hoat dong cong ty (listDataAboutUs).
 * Xem inc/api-client.php: pplus_get_about_index().
 *
 * Day la 1 WordPress Page that (tao qua wp-admin, gan Page Template nay, slug "ve-chung-toi" -
 * khop link co san trong parts/header.php dong 152).
 *
 * TODO (khong chan hien thi, giong scope da chot cho cac man hinh khac):
 * - Khong co Slick carousel that cho khoi "Hoat dong cua Property Plus" (list-about) - hien
 *   render dang luoi tinh, giong cach front-page.php dang lam voi video/blog carousel.
 * - Trang con /ve-chung-toi/{alias} (about-new.component, API About/TinTucHoatDong) chua lam -
 *   TODO rieng, xem docs/SCREENS_AND_TASK_SPLIT.md B5 "(+ trang con)".
 */
if (!defined('ABSPATH')) exit;

get_header();

$about = pplus_get_about_index();
$employee_columns = !empty($about['listUser']) ? pplus_chunk_round_robin($about['listUser'], 4) : [];

get_template_part('parts/url-box-content', null, [
    'breadcrumb' => [
        ['name' => pplus_t('Trang chủ', 'Home'), 'url' => home_url('/')],
        ['name' => pplus_t('Về chúng tôi', 'About Us')],
    ],
]);
get_template_part('parts/office-filter-panel');
?>
<div class="about-us">
    <div class="a-u-banner">
        <div class="container">
            <div class="col-md-12 row">
                <div class="col-md-5">
                    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/about-us-1.png'); ?>" alt="About" class="img-responsive a-u-banner-img" />
                </div>
                <div class="col-md-7">
                    <div class="a-u-banner-t">
                        <h1><?php echo esc_html(pplus_t('Thành lập từ năm 2011, PropertyPlus là công ty tư vấn hàng đầu cho các doanh nghiệp có nhu cầu thuê văn phòng tại Hà Nội và Hồ Chí Minh', 'Founded in 2011, PropertyPlus is a leading consulting company for businesses looking to rent office space in Hanoi and Ho Chi Minh City')); ?></h1>
                        <div class="a-u-banner-content">
                            <?php echo esc_html(pplus_t('Với đa dạng các loại không gian văn phòng trên khắp địa bàn Hà Nội và Hồ Chí Minh, văn phòng lưu động của chúng tôi cho phép bạn tự do chọn lựa không gian theo sở thích và mong muốn với chi phí tối ưu, phù hợp với mọi đối tượng khách hàng.', 'With a wide variety of office spaces across Hanoi and Ho Chi Minh City, our flexible offices let you freely choose the space that fits your preferences and budget, suitable for every type of customer.')); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="a-u-c-1">
        <div class="container">
            <div class="a-u-cus-t1"><?php echo esc_html(pplus_t('Property Plus có gì khác biệt?', 'What makes Property Plus different?')); ?></div>
            <hr class="a-u-cus-hr" />
            <div class="row col-md-12">
                <?php
                $diff_items = [
                    ['img' => 'Group-3.png', 'title' => pplus_t('1. Thương hiệu uy tín', '1. Trusted brand'), 'desc' => pplus_t('Với chiến lược kinh doanh bền vững, thương hiệu Property Plus ngày càng khẳng định tên tuổi với các chủ đầu tư tòa nhà nói riêng và thị trường cho thuê văn phòng nói chung.', 'With a sustainable business strategy, the Property Plus brand keeps building its reputation with building owners and the office leasing market in general.')],
                    ['img' => 'Group.png', 'title' => pplus_t('2. Đội ngũ chuyên nghiệp', '2. Professional team'), 'desc' => pplus_t('Đội ngũ nhân viên giàu kinh nghiệm, năng lực chuyên môn cao. Chúng tôi có quy trình bài bản trong quy trình tư vấn tòa nhà làm việc, phù hợp với nhu cầu và ngân sách của khách hàng.', 'An experienced team with strong expertise. We follow a structured process for office consulting, matching customer needs and budget.')],
                    ['img' => 'Group-2.png', 'title' => pplus_t('3. Mạng lưới thông tin đa dạng', '3. Wide information network'), 'desc' => pplus_t('Property Plus sử dụng nền tảng công nghệ tích hợp trong quản lý số lớn; phát triển website giúp khách hàng thuận tiện tìm kiếm, tham khảo thông tin văn phòng.', 'Property Plus uses an integrated technology platform to manage a large database, with a website that makes it easy for customers to search and reference office information.')],
                    ['img' => 'Group-1.png', 'title' => pplus_t('4. Hỗ trợ tốt nhất', '4. Best support'), 'desc' => pplus_t('Ở Property Plus, chúng tôi luôn dành trọn sự tận tâm đến từng khách hàng. Công ty lấy khách hàng là trung tâm, hỗ trợ 24/7 trước, trong và sau khi thuê văn phòng.', 'At Property Plus, we are always dedicated to every customer. We put customers first, with 24/7 support before, during, and after renting an office.')],
                ];
                foreach ($diff_items as $item) :
                ?>
                    <div class="col-md-3 a-u-c-1-item">
                        <div class="icon-c-1">
                            <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/officebase/Common/Images/About/' . $item['img']); ?>" alt="about" loading="lazy" />
                        </div>
                        <div class="a-u-cus-t3 text-center"><?php echo esc_html($item['title']); ?></div>
                        <div class="a-u-cus-t4 text-center"><?php echo esc_html($item['desc']); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="a-u-c-2">
        <div class="container">
            <div class="tite-box">
                <h2 class="title"><?php echo esc_html(pplus_t('Tầm nhìn - Phương châm - Sứ mệnh', 'Vision - Motto - Mission')); ?></h2>
                <hr />
            </div>
            <div class="row col-md-12 a-u-c-2-items">
                <?php
                $mission_items = [
                    ['img' => 'Rectangle 9.png', 'title' => pplus_t('Tầm nhìn', 'Vision'), 'desc' => pplus_t('Property Plus định hướng trở thành công ty dẫn đầu thị trường bất động sản cho cho thuê văn phòng tại Hà Nội.', 'Property Plus aims to become the leading company in the office leasing real estate market in Hanoi.')],
                    ['img' => 'Rectangle 9-1.png', 'title' => pplus_t('Phương châm', 'Motto'), 'desc' => pplus_t('Property Plus luôn nỗ lực tư vấn cho khách hàng không gian văn phòng hiện đại, môi trường làm việc phù hợp theo từng ngành nghề kinh doanh.', 'Property Plus always strives to advise customers on modern office spaces and work environments suited to each industry.')],
                    ['img' => 'Rectangle 9-2.png', 'title' => pplus_t('Sứ mệnh', 'Mission'), 'desc' => pplus_t('Property Plus mang đến giải pháp toàn diện và tối ưu cho khách hàng tìm thuê văn phòng, đồng thời, mang lại giá trị gia tăng cho chủ đầu tư.', 'Property Plus provides comprehensive, optimal solutions for customers looking to rent offices, while creating added value for building owners.')],
                ];
                foreach ($mission_items as $i => $item) :
                ?>
                    <div class="a-u-c-2-item<?php echo $i === 0 ? ' ml-0' : ''; ?>">
                        <div class="a-u-step">
                            <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/' . $item['img']); ?>" alt="about" loading="lazy" />
                        </div>
                        <div class="a-u-cus-t3"><?php echo esc_html($item['title']); ?></div>
                        <div class="a-u-cus-t4"><?php echo esc_html($item['desc']); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php if (!empty($employee_columns)) : ?>
        <div class="sale-nhanvien">
            <div class="container">
                <div class="a-u-cus-t1"><?php echo esc_html(pplus_t('Đội ngũ nhân viên', 'Our team')); ?></div>
                <hr class="a-u-cus-hr" />
                <div class="list-nhanvien row">
                    <?php foreach ($employee_columns as $column) : ?>
                        <div class="col-md-3 col-sm-6">
                            <?php foreach ($column as $person) : ?>
                                <div class="item">
                                    <img src="<?php echo esc_url(pplus_admin_asset_url($person['imgSize'] ?? $person['img'] ?? '')); ?>" alt="<?php echo esc_attr($person['title'] ?? ''); ?>" loading="lazy" />
                                    <div class="item-content">
                                        <label class="text-1"><?php echo esc_html($person['title'] ?? ''); ?></label><br />
                                        <label class="text-2"><?php echo esc_html($person['content'] ?? ''); ?></label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="story-project-client">
        <div class="container">
            <div class="row">
                <div class="tite-box">
                    <h2 class="title"><?php echo esc_html(pplus_t('Khách hàng của Property Plus', 'Property Plus Customers')); ?></h2>
                    <hr />
                </div>
                <div class="col-md-12 client-slider-list">
                    <div class="client-slider">
                        <?php for ($i = 1; $i <= 35; $i++) : ?>
                            <div class="client-logo">
                                <img width="100" height="100" src="<?php echo esc_url(get_template_directory_uri() . '/assets/officebase/Common/Images/Home/CustomerT6/' . $i . '.png'); ?>" alt="custommer" loading="lazy" />
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($about['listDataAboutUs'])) : ?>
        <div class="about-img">
            <div class="container">
                <div class="tite-box">
                    <h2 class="title"><?php echo esc_html(pplus_t('Hoạt động của Property Plus', 'Property Plus Activities')); ?></h2>
                    <hr />
                </div>
                <div class="list-about stick-custom-css">
                    <?php foreach ($about['listDataAboutUs'] as $item) : ?>
                        <div class="item">
                            <img src="<?php echo esc_url(pplus_admin_asset_url($item['img'] ?? '')); ?>" alt="about" loading="lazy" />
                            <div class="item-content">
                                <label class="text-1"><?php echo esc_html($item['title'] ?? ''); ?></label><br />
                                <label class="text-2"><?php echo esc_html($item['content'] ?? ''); ?></label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php get_footer(); ?>
