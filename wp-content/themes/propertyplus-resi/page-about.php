<?php
/**
 * Template Name: Ve chung toi
 * Trang /ve-chung-toi (B5) - port tu about-index.component.html (PPlusAngularResi).
 *
 * VIET LAI HOAN TOAN 14/9: ban truoc day la COPY Y HET (0 dong khac biet khi diff) theme Van
 * phong - toan bo copy marketing la ve cho thue VAN PHONG (khong phai can ho), thieu 3 khoi that
 * (a-u-c-3 bio lanh dao, a-u-c-4 thanh tuu, a-u-c-5 co cau to chuc), va co them 1 khoi BIA (35 logo
 * khach hang, ".story-project-client") KHONG TON TAI trong Angular that - da xoa. Doi chieu tung
 * dong voi about-index.component.html/.ts that, dung dung 9 khoi theo dung thu tu ban goc.
 *
 * Phan lon noi dung la copy marketing TINH (hard-code qua Localizer() ben Angular, khong phai CMS).
 * 3 phan DONG tu About/GetIndex: doi ngu nhan vien (listUser), hoat dong cong ty (listDataAboutUs),
 * carousel "Kham pha Property Plus" (model.listKhamPhaPP) - xem pplus_get_about_index().
 *
 * Day la 1 WordPress Page that (tao qua wp-cli 14/9: ID 4, slug "ve-chung-toi", gan
 * _wp_page_template = "page-about.php" - khop link co san trong parts/header.php).
 *
 * TODO (khong chan hien thi, giong scope da chot cho cac man hinh khac):
 * - Trang con /ve-chung-toi/{alias}-{id} (about-new.component) da co san (page-about-subpage.php +
 *   inc/about-subpage-route.php), moi duoc noi day (14/9): require_once trong functions.php +
 *   enqueue CSS + sua bug thieu tham so "id" khi goi API - xem inc/about-subpage-route.php.
 */
if (!defined('ABSPATH')) exit;

get_header();

$about = pplus_get_about_index();
$employee_columns = !empty($about['listUser']) ? pplus_chunk_round_robin($about['listUser'], 4) : [];
$discover_items = $about['model']['listKhamPhaPP'] ?? [];

// Hang goi y nhanh (suggest-header) - PORT DUNG tu <app-search-base isShowUrlMenu=true> o dau
// about-index.component.html dong 1 (them 17/9: truoc day thieu han o trang nay - chi co
// breadcrumb ben duoi, khong co hang pill nay - bug thuc te nguoi dung bao cao khi doi chieu voi
// propertyplus.com.vn/en/about-us: hang pill nay LUON hien mac dinh tren ban goc).
pplus_render_suggest_header();

get_template_part('parts/url-box-content', null, [
    'breadcrumb' => [
        ['name' => pplus_t('Trang chủ', 'Home'), 'url' => home_url('/')],
        ['name' => pplus_t('Về chúng tôi', 'About Us')],
    ],
]);
// "parts/office-filter-panel.php" (panel "Bo loc" Loai van phong/Hang toa nha) DA XOA khoi day
// 17/9 - xem ghi chu chi tiet trong parts/url-box-content.php: doc lai dung dong 36-56 cua
// search-base.component.html xac nhan khoi nay CHI CO breadcrumb, KHONG CO panel/nut tim kiem nao,
// goi truoc day la bia them nham.
?>
<div class="about-us-tab">
    <div class="a-u-banner">
        <?php // 24/10/2026: anh banner (phan tu LCP trang nay) goc 3200x2133 nang 1,2MB -> srcset 800/1600 (WebP ~81/244KB, JPG
        // du phong), giu ban goc cho man hinh rat rong; width/height de giu cho (CLS), fetchpriority=high.
        $au_img = get_template_directory_uri() . '/assets/Content/ImgAboutUs/f669e1eff6ee2cb075ff'; ?>
        <picture>
            <source type="image/webp" srcset="<?php echo esc_url($au_img . '-800.webp'); ?> 800w, <?php echo esc_url($au_img . '-1600.webp'); ?> 1600w" sizes="100vw" />
            <img src="<?php echo esc_url($au_img . '.jpg'); ?>" srcset="<?php echo esc_url($au_img . '-800.jpg'); ?> 800w, <?php echo esc_url($au_img . '-1600.jpg'); ?> 1600w, <?php echo esc_url($au_img . '.jpg'); ?> 3200w" sizes="100vw" width="3200" height="2133" class="img-responsive a-u-banner-img" alt="Property Plus" fetchpriority="high" />
        </picture>
        <div class="a-u-banner-backgroud"></div>
        <div class="a-u-banner-t">
            <h1><?php echo esc_html(pplus_t('Về chúng tôi', 'About us')); ?></h1>
            <div class="a-u-banner-sub-t"><?php echo esc_html(pplus_t('Thành lập và phát triển', 'Founded and growing')); ?></div>
            <hr />
            <div class="a-u-banner-content">
                <?php echo esc_html(pplus_t('Được thành lập từ năm 2011, Property Plus tự hào là một trong những đơn vị hàng đầu trong lĩnh vực tư vấn, quản lý và cho thuê căn hộ chung cư, căn hộ dịch vụ cao cấp cho người nước ngoài.', 'Founded in 2011, Property Plus is proud to be one of the leading companies in consulting, managing, and leasing apartments and premium serviced apartments for foreigners.')); ?>
            </div>
        </div>
        <div class="a-u-banner-next"></div>
    </div>

    <div class="a-u-c-1">
        <div class="container">
            <div class="a-u-cus-t1"><?php echo esc_html(pplus_t('Property Plus có gì khác biệt?', 'What makes Property Plus different?')); ?></div>
            <hr class="a-u-cus-hr" />
            <div class="row col-md-12">
                <?php
                // SUA 30/9 (kiem tra da ngon ngu theo yeu cau nguoi dung): so thu tu "1. "/"2. "...
                // TRUOC DAY bi nhet CHUNG vao trong chinh khoa dich (vd "1. Mạng lưới căn hộ đa dạng")
                // - dictionary that (GetLangApi) chi luu khoa KHONG co so ("Mạng lưới căn hộ đa dạng"),
                // nen tra cuu JA/EN qua khoa co "1. " o dau LUON THAT BAI (khong khop chinh xac tung
                // ky tu), am tham roi ve nguyen van tieng Viet - xac nhan qua doi chieu truc tiep
                // about-index.component.html that (dong 34/43/52/61): so thu tu la TEXT THUONG nam
                // NGOAI {{Localizer(...)}}, khong phai 1 phan cua chuoi dich. Tach rieng "seq" (so, in
                // thang khong qua ham dich) khoi "title" (chi con dung chuoi can dich) de khop dung
                // ban goc.
                $diff_items = [
                    ['img' => 'Group12.png', 'seq' => 1, 'title' => pplus_t('Mạng lưới căn hộ đa dạng', 'Diverse apartment network'), 'desc' => pplus_t('Chúng tôi quản lý mạng lưới căn hộ rộng khắp Hà Nội, đa dạng về loại hình (biệt thự, căn hộ dịch vụ, chung cư, ...), đa dạng mức ngân sách, nhằm đáp ứng nhu cầu của nhiều nhóm khách hàng.', 'We manage a wide network of apartments across Hanoi, diverse in type (villas, serviced apartments, condominiums...) and budget, to meet the needs of many customer groups.')],
                    ['img' => 'Group10.png', 'seq' => 2, 'title' => pplus_t('Cá nhân hóa dịch vụ tiêu chuẩn 05 sao', 'Personalized 5-star standard service'), 'desc' => pplus_t('Với sự am hiểu về văn hóa của từng quốc gia, cá nhân hóa dịch vụ tới từng khách hàng. Property Plus đảm bảo mang đến chất lượng dịch vụ 5 sao làm hài lòng tất cả các khách hàng.', 'With an understanding of each country\'s culture, we personalize service for every customer. Property Plus ensures 5-star service quality that satisfies all customers.')],
                    ['img' => 'Group14.png', 'seq' => 3, 'title' => pplus_t('Ứng dụng công nghệ cao', 'High technology application'), 'desc' => pplus_t('Property Plus phát triển nền tảng công nghệ tích hợp trong quản lý số lượng lớn căn hộ (dịch vụ, căn hộ chung cư) với tốc độ và hiệu suất tối ưu.', 'Property Plus develops an integrated technology platform to manage a large number of apartments with optimal speed and performance.')],
                    ['img' => 'Group11.png', 'seq' => 4, 'title' => pplus_t('Văn hóa phục vụ tận tâm', 'Dedicated service culture'), 'desc' => pplus_t('Ở Property Plus, chúng tôi luôn dành trọn sự tận tâm đến từng khách hàng.', 'At Property Plus, we are always fully dedicated to every customer.')],
                ];
                foreach ($diff_items as $item) :
                ?>
                    <div class="col-md-3 a-u-c-1-item">
                        <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/ImgAboutUs/' . $item['img']); ?>" alt="about" loading="lazy" />
                        <div class="a-u-cus-t3 text-center"><?php echo (int) $item['seq']; ?>. <?php echo esc_html($item['title']); ?></div>
                        <div class="a-u-cus-t4 text-center"><?php echo esc_html($item['desc']); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="a-u-c-2">
        <div class="container">
            <div class="a-u-cus-t1"><?php echo esc_html(pplus_t('Tầm nhìn - Phương châm - Sứ mệnh', 'Vision - Motto - Mission')); ?></div>
            <hr class="a-u-cus-hr" />
            <div class="row col-md-12">
                <?php
                $mission_items = [
                    ['title' => pplus_t('Tầm nhìn', 'Vision'), 'desc' => pplus_t('Property Plus nỗ lực để trở thành công ty số 01 Việt Nam chuyên cung cấp giải pháp toàn diện về nhà ở cho người nước ngoài (tại Việt nam) với dịch vụ quản gia chuyên nghiệp tiêu chuẩn 05 sao.', 'Property Plus strives to become Vietnam\'s No. 1 company providing comprehensive housing solutions for foreigners in Vietnam, with professional 5-star butler service.')],
                    ['title' => pplus_t('Phương châm', 'Motto'), 'desc' => pplus_t('Property Plus cố gắng đem đến cho những người bạn quốc tế không gian sống đầy đủ tiện nghi, thoải mái và cảm giác được đón chào ngay từ khoảnh khắc bước qua cánh cửa.', 'Property Plus strives to bring international friends a fully-equipped, comfortable living space and a feeling of being welcomed from the moment they step through the door.')],
                    ['title' => pplus_t('Sứ mệnh', 'Mission'), 'desc' => pplus_t('Property Plus tâm huyết mang lại lợi ích cho cả chủ thuê và khách thuê, đồng thời nâng cao thu nhập cho người lao động, góp phần phát triển kinh tế Việt Nam.', 'Property Plus is dedicated to benefiting both landlords and tenants, while raising workers\' incomes and contributing to Vietnam\'s economic development.')],
                ];
                foreach ($mission_items as $i => $item) :
                ?>
                    <div class="col-md-4 a-u-c-2-item<?php echo $i === 0 ? ' ml-0' : ''; ?>">
                        <div class="a-u-step"><?php echo (int) $i + 1; ?></div>
                        <div class="a-u-cus-t3 font-size20 text-center"><?php echo esc_html($item['title']); ?></div>
                        <div class="a-u-cus-t4"><?php echo esc_html($item['desc']); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="a-u-c-3">
        <div class="row">
            <div class="col-md-6 a-u-c-3-img p-0 hidden-pc">
                <img class="img-responsive" src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/ImgAboutUs/Group1217.jpg'); ?>" alt="Phạm Văn Lợi" loading="lazy" />
            </div>
            <div class="col-md-6 a-u-c-3-content">
                <div class="a-u-c-2-c1"><?php echo esc_html(pplus_t('Nhà sáng lập & Chủ tịch HĐQT', 'Founder & Chairman')); ?></div>
                <div class="a-u-c-2-c2"><?php echo esc_html(pplus_t('Ông Phạm Văn Lợi', 'Mr. Phạm Văn Lợi')); ?></div>
                <div class="a-u-c-2-c3">Email: loi.pham@propertyplus.com.vn</div>
                <hr class="a-u-cus-hr2" />
                <div class="a-u-c-2-c3">
                    - <?php echo esc_html(pplus_t('04 năm kinh nghiệm tại Savills và Cushman & Wakefield', '4 years of experience at Savills and Cushman & Wakefield')); ?><br />
                    - <?php echo esc_html(pplus_t('Hơn 10 năm sáng lập và điều hành Property Plus', 'More than 10 years founding and running Property Plus')); ?>
                </div>
            </div>
            <div class="col-md-6 a-u-c-3-img hidden-moblie p-0">
                <img class="img-responsive" src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/ImgAboutUs/Group1217.jpg'); ?>" alt="Phạm Văn Lợi" loading="lazy" />
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 p-0 a-u-c-3-img">
                <img class="img-responsive" src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/ImgAboutUs/Group1218.jpg'); ?>" alt="Trần Thị Hoa" loading="lazy" />
            </div>
            <div class="col-md-6 a-u-c-3-content">
                <div class="a-u-c-2-c1"><?php echo esc_html(pplus_t('Giám đốc kinh doanh', 'Sales Director')); ?></div>
                <div class="a-u-c-2-c2"><?php echo esc_html(pplus_t('Bà Trần Thị Hoa', 'Ms. Trần Thị Hoa')); ?></div>
                <div class="a-u-c-2-c3">Email: hoa.tran@propertyplus.com.vn</div>
                <hr class="a-u-cus-hr2" />
                <div class="a-u-c-2-c3">
                    - <?php echo esc_html(pplus_t('04 năm kinh nghiệm tại khách sạn Janadeco', '4 years of experience at Janadeco hotel')); ?><br />
                    - <?php echo esc_html(pplus_t('02 năm kinh nghiệm ở khách sạn Daewoo', '2 years of experience at Daewoo hotel')); ?><br />
                    - <?php echo esc_html(pplus_t('13 năm kinh nghiệm kinh doanh ở Sofitel Plaza Hanoi (hiện là Khách sạn Pan Pacific)', '13 years of business experience at Sofitel Plaza Hanoi (now Pan Pacific Hotel)')); ?><br />
                    - <?php echo esc_html(pplus_t('Hơn 07 năm điều hành Property Plus', 'More than 7 years running Property Plus')); ?>
                </div>
            </div>
        </div>
    </div>

    <div class="a-u-c-4">
        <div class="container">
            <div class="a-u-cus-t1"><?php echo esc_html(pplus_t('Thành tựu', 'Achievements')); ?></div>
            <div class="a-u-cus-t2"><?php echo esc_html(pplus_t('Quy mô thị trường', 'Market scale')); ?></div>
            <hr class="a-u-cus-hr" />
            <div class="row col-md-12 a-u-c-4-content">
                <?php
                $stats = [
                    [60, pplus_t('Dự án', 'Projects')],
                    [110, pplus_t('Đối tác', 'Partners')],
                    [1150, pplus_t('Khách hàng', 'Customers')],
                    [750, pplus_t('Chủ sở hữu', 'Owners')],
                ];
                foreach ($stats as $stat) :
                ?>
                    <div class="col-md-3 col-xs-6">
                        <div class="a-u-step"><span><?php echo (int) $stat[0]; ?></span>++</div>
                        <div class="a-u-title"><?php echo esc_html($stat[1]); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="a-u-c-5">
        <div class="container">
            <div class="a-u-cus-t1"><?php echo esc_html(pplus_t('CƠ CẤU TỔ CHỨC', 'ORGANIZATIONAL STRUCTURE')); ?></div>
            <img class="img-responsive" src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/ImgAboutUs/cocautochuc.png'); ?>" alt="Cơ cấu tổ chức" loading="lazy" />
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

    <?php if (!empty($discover_items)) : ?>
        <div class="a-u-c-6">
            <div class="container">
                <div class="a-u-cus-t1"><?php echo esc_html(pplus_t('Khám phá Property Plus', 'Discover Property Plus')); ?></div>
                <hr class="a-u-cus-hr" />
                <div class="row _blog">
                    <div class="col-md-12 p-0">
                        <div class="_blog-silder">
                            <?php // Ban that dung Swiper - port sang Slick (quy uoc chung cua theme nay,
                            // xem assets/js/main.js). Khong can div boc rieng nhu ".apt-card-slide": ca
                            // "._blog-item" lan ".item" (khoi Hoat dong ben duoi) deu KHONG co nen/bong
                            // rieng tren the ngoai, chi bo-goc cho <img> con ben trong. ?>
                            <div class="blog-silder-loop">
                                <?php foreach ($discover_items as $item) : ?>
                                    <div class="_blog-item">
                                        <img class="_blog-img" src="<?php echo esc_url(pplus_admin_asset_url($item['img'] ?? '')); ?>" alt="<?php echo esc_attr($item['title'] ?? ''); ?>" loading="lazy" />
                                        <div class="_blog-item-title"><?php echo esc_html($item['title'] ?? ''); ?></div>
                                        <div class="_blog-item-sub-title"><?php echo esc_html($item['subTitle'] ?? ''); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($about['listDataAboutUs'])) : ?>
        <div class="about-img">
            <div class="container">
                <div class="a-u-cus-t1"><?php echo esc_html(pplus_t('Hoạt động của Property Plus', 'Property Plus Activities')); ?></div>
                <hr class="a-u-cus-hr" />
                <div class="list-about">
                    <div class="silder-about-loop">
                        <?php foreach ($about['listDataAboutUs'] as $item) : ?>
                            <div class="item">
                                <img src="<?php echo esc_url(pplus_admin_asset_url($item['img'] ?? '')); ?>" alt="<?php echo esc_attr($item['title'] ?? ''); ?>" loading="lazy" />
                                <div class="item-content">
                                    <label class="text-1"><?php echo esc_html($item['title'] ?? ''); ?></label><br />
                                    <label class="text-2"><?php echo esc_html($item['content'] ?? ''); ?></label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php get_footer(); ?>
