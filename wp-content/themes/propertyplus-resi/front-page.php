<?php
/**
 * Trang chu - port tu src/app/modules/home/home.component.html.
 * Nguon du lieu: {lang}/Home/GetIndex (xem home.service.ts: GetDataHome, home.component.ts: ControlHome).
 *
 * TODO chua lam (khong chan hien thi, ghi ro de Dev B hoan thien sau):
 * - Form tim kiem (search-home.component.html) chi lam ban tinh gian luoc (khong co popup
 *   chon dia diem/loai van phong/gia/hang bang AJAX nhu Angular) - can lam cung luc voi
 *   trang danh sach van phong (Dev A) vi 2 phan dung chung logic filter.
 * - Slick carousel that su cho .offices-col / .item-blog (da enqueue slick.js, can init JS
 *   trong assets/js/main.js giong ham BaseAction() ben Angular).
 * - Swiper coverflow cho video (dang render dang luoi tinh thay vi carousel).
 * - Modal xem video (Angular dùng NgbModal danh cho <ng-template>videoModal</ng-template>).
 */
if (!defined('ABSPATH')) exit;

get_header();

$home  = pplus_get_home_data();
$lang  = pplus_current_lang();
$lang_prefix = ($lang === 'en') ? '/en' : '';

$employee_columns = !empty($home['danhSachNhanVien'])
    ? pplus_chunk_round_robin($home['danhSachNhanVien'], 4)
    : [];

// Giong het ControlHome() trong home.component.ts: loc video co anh, roi NHAN DOI mang
// (push them ban sao cua chinh no) truoc khi dua vao Swiper - thieu buoc nay lam Swiper
// tinh sai layout coverflow vi so luong slide khong khop.
$home_videos = [];
if (!empty($home['listVideos'])) {
    $home_videos = array_values(array_filter($home['listVideos'], fn($v) => !empty($v['img'])));
    $home_videos = array_merge($home_videos, $home_videos);
}
?>

<?php // Panel "Bo loc" (parts/office-filter-panel.php) - tren mobile, form tim kiem desktop
// (.form-search-destop ben duoi) bi an hoan toan (CSS chi danh cho PC, giong Angular goc chi
// render <app-search-home> khi isMoblie==false). Angular thay the bang <app-search-base> rieng
// cho mobile (home.component.html dong 1) de nguoi dung van tim kiem duoc; ben nay chua tung
// include panel nay nen nut "Tìm kiếm" o thanh footer-mobile khong co gi de mo (bug thuc te:
// mobile khong co CACH NAO tim kiem tu trang chu - cung mau bug da fix truoc do o page-office-
// detail.php). ?>
<?php get_template_part('parts/office-filter-panel'); ?>

<div class="langdingpage-header">
    <div class="bg-img">
        <?php // Anh nen hero - doi tu PNG sang JPG (toi uu hieu nang, xem feedback nguoi dung):
        // anh chup thanh pho khong co vung trong suot nao (da kiem tra qua GD), PNG (nen khong
        // mat du lieu) hoan toan khong phu hop cho anh chup - qua GD imagejpeg(quality=85) giam
        // duoc 88% dung luong (1.4MB -> 174KB / 676KB -> 79KB) ma mat chat luong khong dang ke.
        // Them ban .webp (13/8/2026, sharp quality=82) song song ban .jpg goc (KHONG xoa .jpg -
        // dung lam <source> uu tien + fallback cho trinh duyet khong ho tro webp) - giam tiep
        // ~35% dung luong: 1440x650 173.8KB -> 112KB, 600x649 78.7KB -> 51KB. width/height tren
        // <img> chi la hint du phong (CSS ".bg-img img{width:100%;height:100%}" + ".bg-img{height:
        // 100vh}"/"aspect-ratio" tren container da tu chan CLS roi, xem header-component.css dong
        // 286-304), nhung van them cho dung chuan HTML/qua duoc audit Lighthouse. ?>
        <picture>
            <source media="(max-width: 800px)" type="image/webp" srcset="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/home-600x1000.webp'); ?>" />
            <source media="(max-width: 800px)" srcset="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/home-600x1000.jpg'); ?>" />
            <source type="image/webp" srcset="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/home-1920x1080.webp'); ?>" />
            <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/home-1920x1080.jpg'); ?>" alt="langdingpage" width="1440" height="650" />
        </picture>
    </div>
    <div class="bg-search">
        <div class="container">
            <div class="our-company">
                <h1 class="hidden"><?php echo esc_html(pplus_t('Propertyplus.vn | Cho Thuê Văn Phòng Tại Hà Nội Uy Tín Nhất', 'Propertyplus.vn | The Most Trusted Office Rental in Hanoi')); ?></h1>
                <h2 class="title"><?php echo esc_html(pplus_t('Tìm văn phòng làm việc cùng chúng tôi', 'Find your workspace with us')); ?></h2>
            </div>

            <div class="form-search-destop">
                <div class="tab-content">
                    <div class="tab-pane box-search-pane fade show active">
                        <div class="searching-bar">
                            <form action="<?php echo esc_url(home_url($lang_prefix . '/' . pplus_office_list_base_slug() . '/')); ?>" method="get" autocomplete="off" id="office-form" class="office-form">
                                <div class="box-search row">
                                    <div class="col-md-5 search-input-group">
                                        <div class="search-option">
                                            <div class="search-controls">
                                                <div class="control control-two">
                                                    <img class="icon-from-search" src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/Group.png'); ?>" alt="Icon" width="29" height="24" />
                                                    <label class="search-label"><?php echo esc_html(pplus_t('Thành phố', 'City')); ?></label>
                                                    <?php // Gia tri option PHAI la ten tinh tieng Viet co dau ("Hà Nội"/"Hồ Chí Minh"), KHONG
                                                    // phai alias dang slug ("ha-noi"/"ho-chi-minh") - page-office-list.php doc $_GET['province']
                                                    // va gui THANG lam filter "Province" cho Office/_GetOffices (xem inc/api-client.php), API chi
                                                    // khop dung khi la ten that. Truoc day dung slug lam gia tri khien tim kiem tu trang chu luon
                                                    // ra "Khong tim thay san pham nao" (bug thuc te da bao cao) - doi chieu voi gia tri mac dinh
                                                    // 'Hà Nội' o page-office-list.php dong 24 de xac nhan dung dinh dang. ?>
                                                    <select class="form-control" name="province" aria-label="Province">
                                                        <option value="Hà Nội"><?php echo esc_html(pplus_t('Hà Nội', 'Hanoi')); ?></option>
                                                        <option value="Hồ Chí Minh"><?php echo esc_html(pplus_t('Hồ Chí Minh', 'Ho Chi Minh City')); ?></option>
                                                    </select>
                                                </div>
                                                <div class="control">
                                                    <img class="icon-from-search" src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/Group-1.png'); ?>" alt="Icon" width="29" height="24" />
                                                    <label class="search-label"><?php echo esc_html(pplus_t('Địa điểm', 'Location')); ?></label>
                                                    <input class="search-input form-control" type="text" name="search_key" placeholder="<?php echo esc_attr(pplus_t('Tên dự án, Quận...', 'Project name, District...')); ?>" />
                                                    <?php // Xem ghi chu giong het trong parts/office-filter-panel.php - dien tu JS khi chon 1
                                                    // goi y autocomplete "Địa điểm" (assets/js/main.js + inc/location-search.php). ?>
                                                    <input type="hidden" name="project_id" value="" />
                                                    <input type="hidden" name="street_name" value="" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2 search-input-group pplus-popup-field">
                                        <div class="search-option">
                                            <div class="search-controls">
                                                <div class="control">
                                                    <img class="icon-from-search" src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/Group-3.png'); ?>" alt="Icon" width="29" height="26" />
                                                    <label class="search-label"><?php echo esc_html(pplus_t('Loại văn phòng', 'Office type')); ?></label>
                                                    <input class="search-input form-control pplus-popup-trigger" type="text" readonly value="<?php echo esc_attr(pplus_t('Văn phòng truyền thống', 'Traditional office')); ?>" placeholder="<?php echo esc_attr(pplus_t('Loại văn phòng', 'Office type')); ?>" />
                                                </div>
                                            </div>
                                            <div class="focus-popup">
                                                <ul class="office-list">
                                                    <li class="office-type">
                                                        <input class="pplus-toggle-check pplus-office-type-radio" type="checkbox" id="pplus_home_traditional" name="project_type" value="2" checked />
                                                        <label for="pplus_home_traditional">
                                                            <i class="icon">
                                                                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                    <g clip-path="url(#pplusHomeOfficeTypeClip1)">
                                                                        <path d="M13.5625 5.6875V9.625H0.4375V0.4375H8.3125" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                                        <path d="M8.75 9.625H5.25V12.25H8.75V9.625Z" stroke-width="1.2" stroke-linejoin="round" />
                                                                        <path d="M10.0625 13.5625H3.9375L4.375 12.25H9.625L10.0625 13.5625Z" stroke-width="1.2" stroke-linejoin="round" />
                                                                        <path d="M0.4375 7H13.5625" stroke-width="1.2" stroke-linejoin="round" />
                                                                        <path d="M2.625 13.5625H11.375" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                                                        <path d="M11.1562 5.25C12.4852 5.25 13.5625 4.17269 13.5625 2.84375C13.5625 1.51481 12.4852 0.4375 11.1562 0.4375C9.8273 0.4375 8.75 1.51481 8.75 2.84375C8.75 4.17269 9.8273 5.25 11.1562 5.25Z" stroke-width="1.2" stroke-linejoin="round" />
                                                                        <path d="M11.0113 3.83773C10.8724 3.97076 10.6471 3.97076 10.5083 3.83773L9.72911 3.09119C9.5903 2.95822 9.5903 2.74237 9.72911 2.60941C9.86789 2.47639 10.0932 2.47639 10.2321 2.60941L10.6963 3.05414C10.7313 3.08766 10.7883 3.08766 10.8233 3.05414L12.0804 1.84977C12.2192 1.71674 12.4445 1.71674 12.5834 1.84977C12.65 1.91365 12.6875 2.00033 12.6875 2.09066C12.6875 2.18099 12.65 2.26766 12.5834 2.33154L11.0113 3.83773Z" />
                                                                    </g>
                                                                    <defs>
                                                                        <clipPath id="pplusHomeOfficeTypeClip1">
                                                                            <rect width="14" height="14" fill="white" />
                                                                        </clipPath>
                                                                    </defs>
                                                                </svg>
                                                            </i>
                                                            <?php echo esc_html(pplus_t('Văn phòng truyền thống', 'Traditional office')); ?>
                                                        </label>
                                                    </li>
                                                    <li class="office-type">
                                                        <input class="pplus-toggle-check pplus-office-type-radio" type="checkbox" id="pplus_home_full_service" name="project_type" value="1" />
                                                        <label for="pplus_home_full_service">
                                                            <i class="icon">
                                                                <svg width="19" height="19" viewBox="0 0 19 19" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                    <path d="M17.8125 7.125H7.125V5.9375H10.0938C10.2512 5.9375 10.4022 5.87494 10.5136 5.76359C10.6249 5.65224 10.6875 5.50122 10.6875 5.34375V1.1875C10.6875 1.03003 10.6249 0.879005 10.5136 0.767655C10.4022 0.656306 10.2512 0.59375 10.0938 0.59375H2.96875C2.81128 0.59375 2.66026 0.656306 2.54891 0.767655C2.43756 0.879005 2.375 1.03003 2.375 1.1875V5.34375C2.375 5.50122 2.43756 5.65224 2.54891 5.76359C2.66026 5.87494 2.81128 5.9375 2.96875 5.9375H5.9375V7.125H1.1875C1.03003 7.125 0.879005 7.18756 0.767655 7.29891C0.656306 7.41026 0.59375 7.56128 0.59375 7.71875V9.5C0.59375 9.65747 0.656306 9.8085 0.767655 9.91985C0.879005 10.0312 1.03003 10.0938 1.1875 10.0938H1.78125V17.8125C1.78125 17.97 1.84381 18.121 1.95516 18.2323C2.06651 18.3437 2.21753 18.4062 2.375 18.4062C2.53247 18.4062 2.68349 18.3437 2.79484 18.2323C2.90619 18.121 2.96875 17.97 2.96875 17.8125V10.0938H10.0938V17.8125C10.0938 17.97 10.1563 18.121 10.2677 18.2323C10.379 18.3437 10.53 18.4062 10.6875 18.4062H16.625C16.7825 18.4062 16.9335 18.3437 17.0448 18.2323C17.1562 18.121 17.2188 17.97 17.2188 17.8125V10.0938H17.8125C17.97 10.0938 18.121 10.0312 18.2323 9.91985C18.3437 9.8085 18.4062 9.65747 18.4062 9.5V7.71875C18.4062 7.56128 18.3437 7.41026 18.2323 7.29891C18.121 7.18756 17.97 7.125 17.8125 7.125ZM3.5625 1.78125H9.5V4.75H3.5625V1.78125ZM16.0312 17.2188H11.2812V14.25H16.0312V17.2188ZM16.0312 13.0625H11.2812V10.0938H16.0312V13.0625ZM17.2188 8.90625H1.78125V8.3125H17.2188V8.90625Z" stroke-width="1.2" stroke-linejoin="round" />
                                                                    <path d="M13.0625 12.1719H14.25C14.4075 12.1719 14.5585 12.1093 14.6698 11.998C14.7812 11.8866 14.8438 11.7356 14.8438 11.5781C14.8438 11.4207 14.7812 11.2696 14.6698 11.1583C14.5585 11.0469 14.4075 10.9844 14.25 10.9844H13.0625C12.905 10.9844 12.754 11.0469 12.6427 11.1583C12.5313 11.2696 12.4688 11.4207 12.4688 11.5781C12.4688 11.7356 12.5313 11.8866 12.6427 11.998C12.754 12.1093 12.905 12.1719 13.0625 12.1719Z" />
                                                                    <path d="M13.0625 16.3281H14.25C14.4075 16.3281 14.5585 16.2656 14.6698 16.1542C14.7812 16.0429 14.8438 15.8918 14.8438 15.7344C14.8438 15.5769 14.7812 15.4259 14.6698 15.3145C14.5585 15.2032 14.4075 15.1406 14.25 15.1406H13.0625C12.905 15.1406 12.754 15.2032 12.6427 15.3145C12.5313 15.4259 12.4688 15.5769 12.4688 15.7344C12.4688 15.8918 12.5313 16.0429 12.6427 16.1542C12.754 16.2656 12.905 16.3281 13.0625 16.3281Z" />
                                                                </svg>
                                                            </i>
                                                            <?php echo esc_html(pplus_t('Văn phòng trọn gói', 'Serviced office')); ?>
                                                        </label>
                                                    </li>
                                                </ul>
                                                <?php // Doi chieu search-home.component.html dong 212-229 (Angular): 4 lua chon con
                                                // (Van phong rieng/Cho ngoi co dinh/Cho ngoi linh hoat/Dang ky kinh doanh - gia tri 1-4,
                                                // GlobalConstants.OfficeTypeList) CHI hien khi da chon "Van phong tron goi" o tren. Cung
                                                // markup/class voi parts/office-filter-panel.php (".office-type-list"/".office-type-item",
                                                // radio co data-pplus-deselectable) de dung LAI dung 2 handler co san trong main.js
                                                // (input[name="project_type"] an/hien, input[name="office_type"][data-pplus-deselectable]
                                                // cho bam lai de bo chon) - CSS .office-type-list da co san (search-home-component.css) tu
                                                // truoc nhung markup bi thieu, nut bam khong lam gi (bug thuc te da bao cao). ?>
                                                <div class="office-type-list" style="display:none">
                                                    <?php foreach ([
                                                        1 => pplus_t('Văn phòng riêng', 'Private office'),
                                                        2 => pplus_t('Chỗ ngồi cố định', 'Dedicated desk'),
                                                        3 => pplus_t('Chỗ ngồi linh hoạt', 'Flexible desk'),
                                                        4 => pplus_t('Đăng ký kinh doanh', 'Business registration'),
                                                    ] as $ot_value => $ot_label) : ?>
                                                        <div class="office-type-item">
                                                            <input type="radio" name="office_type" id="pplus_home_office_type_<?php echo (int) $ot_value; ?>" value="<?php echo (int) $ot_value; ?>" data-pplus-deselectable="1" />
                                                            <label class="item-class" for="pplus_home_office_type_<?php echo (int) $ot_value; ?>">
                                                                <span class="class-name"><?php echo esc_html($ot_label); ?></span>
                                                            </label>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2 search-input-group pplus-popup-field">
                                        <div class="search-option">
                                            <div class="search-controls">
                                                <div class="control">
                                                    <img class="icon-from-search" src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/Group-4.png'); ?>" alt="Icon" width="29" height="18" />
                                                    <label class="search-label"><?php echo esc_html(pplus_t('Mức giá', 'Price range')); ?></label>
                                                    <input class="search-input form-control pplus-popup-trigger pplus-price-display" type="text" readonly placeholder="<?php echo esc_attr(pplus_t('Chọn khoảng giá', 'Select a price range')); ?>" />
                                                </div>
                                            </div>
                                            <?php // Ky hieu tien te doi theo "Loai van phong" (project_type) - cung logic voi
                                            // parts/office-filter-panel.php (Van phong truyen thong niem yet $/m2, Van phong tron goi
                                            // niem yet VND). Mac dinh homepage la Truyen thong (input value="2" checked o tren) nen $
                                            // hien san, VND an; JS (main.js, handler input[name="project_type"] cua #office-form) doi
                                            // lai 2 span nay + chu trong o readonly ".pplus-price-display" khi doi loai van phong. ?>
                                            <div class="focus-popup">
                                                <div class="choose-price">
                                                    <div class="ranger-group">
                                                        <label class="ranger-label"><?php echo esc_html(pplus_t('Từ', 'From')); ?> $</label>
                                                        <input class="ranger-input min pplus-price-from" name="price_from" type="number" placeholder="<?php echo esc_attr(pplus_t('Nhập số...', 'Enter a number...')); ?>" />
                                                        <span class="ranger-currency pplus-ranger-currency-usd">$</span>
                                                        <span class="ranger-currency pplus-ranger-currency-vnd" style="display:none">VND</span>
                                                    </div>
                                                    <div class="ranger-group">
                                                        <label class="ranger-label"><?php echo esc_html(pplus_t('Đến', 'To')); ?> $</label>
                                                        <input class="ranger-input max pplus-price-to" name="price_to" type="number" placeholder="<?php echo esc_attr(pplus_t('Nhập số...', 'Enter a number...')); ?>" />
                                                        <span class="ranger-currency pplus-ranger-currency-usd">$</span>
                                                        <span class="ranger-currency pplus-ranger-currency-vnd" style="display:none">VND</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2 search-input-group pplus-popup-field">
                                        <div class="search-option">
                                            <div class="search-controls">
                                                <div class="control">
                                                    <img class="icon-from-search" src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/Group-5.png'); ?>" alt="Icon" width="29" height="24" />
                                                    <label class="search-label"><?php echo esc_html(pplus_t('Hạng', 'Class')); ?></label>
                                                    <input class="search-input form-control pplus-popup-trigger pplus-rating-display" type="text" readonly placeholder="<?php echo esc_attr(pplus_t('Chọn hạng tòa nhà', 'Select building class')); ?>" />
                                                </div>
                                            </div>
                                            <div class="focus-popup">
                                                <ul class="building-class-list">
                                                    <?php foreach (['A', 'B', 'C'] as $rating) : ?>
                                                        <li class="search-rating">
                                                            <input class="pplus-toggle-check" name="rating_level" id="pplus_home_rating_<?php echo esc_attr($rating); ?>" type="checkbox" value="<?php echo esc_attr($rating); ?>" hidden />
                                                            <label class="building-class" for="pplus_home_rating_<?php echo esc_attr($rating); ?>">
                                                                <span class="class-label class<?php echo esc_attr($rating); ?>"><span><?php echo esc_html($rating); ?></span></span>
                                                                <span class="class-name"><?php echo esc_html(sprintf(pplus_t('Tòa hạng %s', 'Grade %s building'), $rating)); ?></span>
                                                            </label>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-1">
                                        <button type="submit" class="btn btn-primary btn-search pull-right" aria-label="Search">
                                            <i class="fa fa-search" aria-hidden="true"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($home) : ?>

<div class="searching-engine bk-hn-group">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <?php // Doi xung voi khoi TP.HCM ben duoi (theo yeu cau nguoi dung, doi chieu dung
                // production propertyplus.vn): TP.HCM = tieu de/anh minh hoa TRAI, luoi quan PHAI;
                // Ha Noi = NGUOC LAI, luoi quan TRAI, tieu de/anh minh hoa PHAI. Tren mobile (2 cot
                // xep chong doc), van muon tieu de hien TRUOC luoi quan (thu tu doc de hieu) - vi 1
                // khoi tieu de KHONG THE vua "sau luoi quan" (desktop) vua "truoc luoi quan" (mobile)
                // cung luc bang thuan CSS display, nhan doi lam 2 ban rieng (.isMobile/.isWebPC, CSS
                // toggle theo breakpoint 880px da co san trong home.less) - dung 1 ky thuat voi ban
                // production that (xac nhan qua doi chieu DOM truc tiep). ?>
                <div class="row search-feature">
                    <div class="col-md-6 isMobile">
                        <div class="tite-box-2">
                            <div class="group-title">
                                <div class="title"><?php echo esc_html(pplus_t('Thành phố Hà Nội', 'Hanoi City')); ?></div>
                                <a class="golink" href="<?php echo esc_url(home_url($lang_prefix . '/' . pplus_office_list_base_slug() . '/ha-noi/')); ?>"><?php echo esc_html(pplus_t('Xem tất cả', 'View all')); ?></a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 distric-col1-scroll">
                        <h2 class="hidden"><?php echo esc_html(pplus_t('Cho thuê văn phòng Hà Nội', 'Office for rent in Hanoi')); ?></h2>
                        <div class="distric-col1">
                            <?php foreach ((array) ($home['dataList'] ?? []) as $item) : ?>
                                <a class="district-name" href="<?php echo esc_url(home_url($lang_prefix . '/' . pplus_office_list_base_slug() . '/ha-noi/' . ($item['alias'] ?? '') . '/')); ?>">
                                    <img src="<?php echo esc_url(pplus_admin_asset_url($item['img'] ?? '')); ?>" alt="Img District" loading="lazy" />
                                    <div class="district-name-text"><h3><?php echo esc_html($item['subTitle'] ?? ''); ?></h3></div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="col-md-6 isWebPC">
                        <div class="tite-box-2">
                            <div class="group-title">
                                <div class="title"><?php echo esc_html(pplus_t('Thành phố Hà Nội', 'Hanoi City')); ?></div>
                                <div class="sub-title"><?php echo esc_html(pplus_t('Tìm văn phòng cho thuê theo quận tại TP. Hà Nội', 'Find office space for rent by district in Hanoi')); ?></div>
                                <a class="golink" href="<?php echo esc_url(home_url($lang_prefix . '/' . pplus_office_list_base_slug() . '/ha-noi/')); ?>">&lt;&lt; <?php echo esc_html(pplus_t('Xem tất cả', 'View all')); ?></a>
                            </div>
                            <div class="sile-img">
                                <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/GroupHN.png'); ?>" alt="Img home" loading="lazy" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="searching-engine bk-hcm-group">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="row search-feature">
                    <div class="col-md-6">
                        <div class="tite-box-3">
                            <div class="group-title">
                                <div class="title"><?php echo esc_html(pplus_t('Thành phố Hồ Chí Minh', 'Ho Chi Minh City')); ?></div>
                                <div class="sub-title isWebPC"><?php echo esc_html(pplus_t('Tìm văn phòng cho thuê theo quận tại TP. Hồ Chí Minh', 'Find office space for rent by district in Ho Chi Minh City')); ?></div>
                                <a class="golink isWebPC" href="<?php echo esc_url(home_url($lang_prefix . '/' . pplus_office_list_base_slug() . '/ho-chi-minh/')); ?>"><?php echo esc_html(pplus_t('Xem tất cả', 'View all')); ?> &gt;&gt;</a>
                                <a class="golink isMobile" href="<?php echo esc_url(home_url($lang_prefix . '/' . pplus_office_list_base_slug() . '/ho-chi-minh/')); ?>"><?php echo esc_html(pplus_t('Xem tất cả', 'View all')); ?></a>
                            </div>
                            <div class="sile-img isWebPC">
                                <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/GroupHCM.png'); ?>" alt="Img home" loading="lazy" />
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 distric-col1-scroll">
                        <h2 class="hidden"><?php echo esc_html(pplus_t('Cho thuê văn phòng Thành phố Hồ Chí Minh', 'Office for rent in Ho Chi Minh City')); ?></h2>
                        <div class="distric-col1">
                            <?php foreach ((array) ($home['dataListHCM'] ?? []) as $item) : ?>
                                <a class="district-name" href="<?php echo esc_url(home_url($lang_prefix . '/' . pplus_office_list_base_slug() . '/ho-chi-minh/' . ($item['alias'] ?? '') . '/')); ?>">
                                    <img src="<?php echo esc_url(pplus_admin_asset_url($item['img'] ?? '')); ?>" alt="Img District" loading="lazy" />
                                    <div class="district-name-text"><h3><?php echo esc_html($item['subTitle'] ?? ''); ?></h3></div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="offices-engine">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="offices-feature">
                    <div class="col-md-12">
                        <div class="tite-box">
                            <h2 class="title"><?php echo esc_html(pplus_t('Danh sách văn phòng HOT', 'HOT office listings')); ?></h2>
                            <div class="sub-title"><?php echo esc_html(pplus_t('Những văn phòng đang dẫn đầu thị trường, bạn đã khám phá chưa?', 'Have you discovered the offices leading the market?')); ?></div>
                            <hr />
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="offices-col stick-custom-css">
                            <?php foreach ((array) ($home['listOffice'] ?? []) as $item) :
                                $photo = !empty($item['photoList'][0]) ? pplus_admin_asset_url($item['photoList'][0]) : '';
                            ?>
                                <a class="item-office hover-office" href="<?php echo esc_url(pplus_office_url($item['urlHerf'] ?? '')); ?>">
                                    <?php if ($photo) : ?>
                                        <img src="<?php echo esc_url($photo); ?>" alt="<?php echo esc_attr($item['projectName'] ?? ''); ?>" loading="lazy" />
                                    <?php else : ?>
                                        <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/officebase/Common/Images/Rectangle 2936.png'); ?>" alt="<?php echo esc_attr($item['projectName'] ?? ''); ?>" loading="lazy" />
                                    <?php endif; ?>
                                    <div class="item-content-m">
                                        <div class="item-content">
                                            <div class="RateTing">
                                                <?php $rank = $item['ratingLevel'] ?? ''; ?>
                                                <?php if ($rank === 'A' || $rank === 'B' || $rank === 'C') : ?>
                                                    <div class="RateTingContent class<?php echo esc_attr($rank); ?>"><?php echo esc_html(sprintf(pplus_t('Hạng %s', 'Grade %s'), $rank)); ?></div>
                                                <?php else : ?>
                                                    <div class="RateTingContent"><?php echo esc_html(pplus_t('Đang cập nhật', 'Updating')); ?></div>
                                                <?php endif; ?>
                                                <div class="item-price"><?php echo esc_html($item['itemPrice'] ?? ''); ?></div>
                                            </div>
                                            <div class="item-name"><?php echo esc_html($item['projectName'] ?? ''); ?></div>
                                            <div class="item-address"><?php echo esc_html($item['address'] ?? ''); ?></div>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="why-choose-us">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <div class="content-left">
                    <div class="content-1">
                        <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/about-us-1-cohoa.JPG'); ?>" alt="Img Home" loading="lazy">
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="content-right">
                    <div class="title-vct-1"><?php echo esc_html(pplus_t('Thành lập từ năm 2011, Property Plus là công ty tư vấn hàng đầu cho các doanh nghiệp có nhu cầu thuê văn phòng tại Hà Nội và Hồ Chí Minh', 'Founded in 2011, Property Plus is a leading consulting company for businesses looking to rent office space in Hanoi and Ho Chi Minh City')); ?></div>
                    <div class="title-vct-2"><?php echo esc_html(pplus_t('Với đa dạng các loại không gian làm việc trên khắp địa bàn Hà Nội và Hồ Chí Minh, văn phòng cho thuê của chúng tôi cho phép bạn tự do chọn lựa không gian theo sở thích và mong muốn, phù hợp với mọi đối tượng khách hàng.', 'With a wide variety of workspaces across Hanoi and Ho Chi Minh City, our rental offices let you freely choose the space that fits your preferences and needs, suitable for every type of customer.')); ?></div>
                    <a class="action-link-abount" href="<?php echo esc_url(home_url($lang_prefix . '/ve-chung-toi/')); ?>" aria-label="About">
                        <?php echo esc_html(pplus_t('Xem thêm', 'Read more')); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="achievement-box">
    <div class="box-1 row isWebPC">
        <div class="col-md-5"></div>
        <div class="col-md-7 bk-box-1"></div>
    </div>
    <div class="container">
        <div class="box-2 row">
            <div class="col-md-5 tite-box">
                <div class="tite-box isWebPC">
                    <h2 class="title"><?php echo esc_html(pplus_t('Thành tựu', 'Achievements')); ?></h2>
                    <div class="sub-title"><?php echo esc_html(pplus_t('Những con số biết nói của Property Plus', 'Numbers that speak for Property Plus')); ?></div>
                    <hr />
                </div>
                <div class="tite-box isMobile">
                    <h2 class="title"><?php echo esc_html(pplus_t('Về Property Plus', 'About Property Plus')); ?></h2>
                    <hr />
                    <div class="sub-title"><?php echo esc_html(pplus_t('Thành lập từ năm 2011, Property Plus là công ty tư vấn hàng đầu cho các doanh nghiệp có nhu cầu thuê văn phòng tại Hà Nội và Hồ Chí Minh', 'Founded in 2011, Property Plus is a leading consulting company for businesses looking to rent office space in Hanoi and Ho Chi Minh City')); ?></div>
                </div>
            </div>
            <div class="col-md-7 row">
                <div class="col-md-4 box-2-item">
                    <div class="content-1"><?php echo esc_html(pplus_t('15+', '15+')); ?></div>
                    <div class="content-2"><?php echo esc_html(pplus_t('Số năm kinh nghiệm', 'Years of experience')); ?></div>
                </div>
                <div class="col-md-4 box-2-item">
                    <div class="content-1"><?php echo esc_html(pplus_t('2200+', '2200+')); ?></div>
                    <div class="content-2"><?php echo esc_html(pplus_t('Số toà nhà cung cấp', 'Buildings offered')); ?></div>
                </div>
                <div class="col-md-4 box-2-item">
                    <div class="content-1"><?php echo esc_html(pplus_t('95%', '95%')); ?></div>
                    <div class="content-2"><?php echo esc_html(pplus_t('Khách hàng hài lòng', 'Satisfied customers')); ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="achievement-box-2">
    <div class="box-1 row">
        <div class="col-md-8 bk-box-1 isWebPC">
            <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/achievement-bk-2.png'); ?>" alt="Img Home" loading="lazy" />
        </div>
        <div class="col-md-4"></div>
    </div>
    <div class="container">
        <div class="box-2 row">
            <div class="col-md-8 row list-box-2-item stick-custom-css">
                <div class="box-2-item">
                    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/Frame 1597883993.png'); ?>" alt="Img Home" loading="lazy">
                    <div class="content-1"><?php echo esc_html(pplus_t('Uy tín & chuyên nghiệp', 'Trusted & professional')); ?></div>
                    <div class="content-2"><?php echo esc_html(pplus_t('Thương hiệu hơn 15 năm trên thị trường cho thuê văn phòng với hơn 8000 khách hàng thân thiết', 'A brand with over 15 years in the office leasing market and more than 8,000 loyal customers')); ?></div>
                </div>
                <div class="box-2-item">
                    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/Frame 1597883993-1.png'); ?>" alt="All Vp" loading="lazy">
                    <div class="content-1"><?php echo esc_html(pplus_t('Hơn 2200+ toà văn phòng', 'Over 2200+ office buildings')); ?></div>
                    <div class="content-2"><?php echo esc_html(pplus_t('Đối tác cho thuê hơn 2200+ toà nhà chuyên nghiệp tại Hà Nội, cung cấp thông tin chính xác và ngay lập tức', 'Partnering with over 2200+ professional buildings for lease in Hanoi, providing accurate information instantly')); ?></div>
                </div>
                <div class="box-2-item">
                    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/Frame 1597883993-2.png'); ?>" alt="Img Home" loading="lazy">
                    <div class="content-1"><?php echo esc_html(pplus_t('Miễn phí tư vấn 24/7', 'Free 24/7 consultation')); ?></div>
                    <div class="content-2"><?php echo esc_html(pplus_t('MIỄN PHÍ 100% phí tư vấn, hỗ trợ liên tục 24/7, quy trình nhanh chóng và chuyên nghiệp', '100% FREE consultation fees, continuous 24/7 support, fast and professional process')); ?></div>
                </div>
            </div>
            <div class="col-md-4 tite-box">
                <h2 class="title"><?php echo esc_html(pplus_t('Vì sao nên chọn Property Plus?', 'Why choose Property Plus?')); ?></h2>
                <hr />
            </div>
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
                    <?php foreach ($column as $item) : ?>
                        <div class="item">
                            <div>
                                <img src="<?php echo esc_url(pplus_admin_asset_url($item['imgSize'] ?? ($item['img'] ?? ''))); ?>" alt="<?php echo esc_attr($item['title'] ?? ''); ?>" loading="lazy" />
                            </div>
                            <div class="item-content">
                                <label class="text-1"><?php echo esc_html($item['title'] ?? ''); ?></label>
                                <br />
                                <label class="text-2"><?php echo esc_html($item['content'] ?? ''); ?></label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="advise-box">
    <div class="container">
        <div class="row">
            <div class="col-md-7">
                <div class="content-right">
                    <div class="group-title">
                        <h2 class="title"><?php echo esc_html(pplus_t('Quy trình tư vấn thuê văn phòng tại Property Plus', 'Office rental consulting process at Property Plus')); ?></h2>
                    </div>
                    <div class="list-data-1">
                        <?php
                        $advise_steps = [
                            ['icon 01.png', pplus_t('01. Tiếp nhận nhu cầu thuê của khách hàng', '01. Receive customer rental needs'), pplus_t('Phân tích nhu cầu và dự thảo chi phí tài chính', 'Analyze needs and draft financial costs')],
                            ['icon 02.png', pplus_t('02. Gửi tài liệu và báo giá văn phòng phù hợp', '02. Send documents and suitable office quotes'), pplus_t('Lên kế hoạch chi tiết lộ trình tìm văn phòng', 'Plan a detailed office search roadmap')],
                            ['icon 03.png', pplus_t('03. Tư vấn chuyên sâu và tham quan mặt bằng', '03. In-depth consulting and site visits'), pplus_t('Tư vấn dự án phù hợp với nhu cầu và ngân sách', 'Advise on projects matching needs and budget')],
                            ['icon 04.png', pplus_t('04. Hỗ trợ khách hàng đàm phán & ký hợp đồng', '04. Support customers in negotiating & signing contracts'), pplus_t('Hỗ trợ trước, trong, sau khi đàm phán hợp đồng', 'Support before, during, and after contract negotiation')],
                        ];
                        foreach ($advise_steps as $step) : ?>
                            <div class="data-1">
                                <div class="content-1">
                                    <img width="100" height="100" src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/' . $step[0]); ?>" alt="advise" loading="lazy" />
                                    <div class="h4"><?php echo esc_html($step[1]); ?></div>
                                </div>
                                <div class="content-2"><?php echo esc_html($step[2]); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-5 isWebPC">
                <div class="content-left">
                    <img width="100" src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/quy-trinh-tu-van.png'); ?>" alt="advise" loading="lazy" />
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($home_videos)) : ?>
<div class="videos-box">
    <div class="container">
        <div class="row">
            <div class="col-md-12 data-header">
                <h2 class="title-1"><?php echo esc_html(pplus_t('Video dự án', 'Project videos')); ?></h2>
                <hr />
            </div>
            <div class="col-md-12 silder-video-list">
                <div class="silder-video swiper-custom-css">
                    <swiper-container init="false">
                        <?php foreach ($home_videos as $item) : ?>
                            <swiper-slide>
                                <?php // Bam vao mo modal xem video (nhung YouTube) - port openModalVideo()
                                // (home.component.ts): item.content la HTML iframe embed co san tu API,
                                // xem parts/video-modal.php + assets/js/main.js ".js-open-video". ?>
                                <div class="item-video js-open-video" data-video-content="<?php echo esc_attr($item['content'] ?? ''); ?>">
                                    <div class="data-1">
                                        <img src="<?php echo esc_url(pplus_admin_asset_url($item['img'])); ?>" alt="<?php echo esc_attr($item['title'] ?? ''); ?>" loading="lazy" />
                                        <div class="view-videos-hover view-videos">
                                            <i>
                                                <svg width="60" height="60" viewBox="0 0 74 74" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M69.42 36.4417C69.42 36.2842 69.42 36.1053 69.4128 35.8978C69.4057 35.3181 69.3914 34.6669 69.3771 33.9727C69.3198 31.976 69.2196 29.9865 69.0622 28.1115C68.8475 25.528 68.5326 23.3739 68.1032 21.7566C67.6501 20.0687 66.7619 18.5293 65.5274 17.2923C64.2929 16.0553 62.7553 15.164 61.0684 14.7074C59.0431 14.1635 55.0784 13.8272 49.4963 13.6196C46.8413 13.5194 44.0001 13.455 41.159 13.4193C40.1643 13.4049 39.2411 13.3978 38.4109 13.3906H36.3069C35.4768 13.3978 34.5536 13.4049 33.5588 13.4193C30.7177 13.455 27.8766 13.5194 25.2215 13.6196C19.6395 13.8343 15.6676 14.1707 13.6495 14.7074C11.962 15.1629 10.4238 16.0538 9.18915 17.291C7.95448 18.5282 7.06665 20.0681 6.61465 21.7566C6.1781 23.3739 5.87037 25.528 5.65568 28.1115C5.49823 29.9865 5.39804 31.976 5.34079 33.9727C5.31932 34.6669 5.31216 35.3181 5.30501 35.8978C5.30501 36.1053 5.29785 36.2842 5.29785 36.4417V36.8424C5.29785 36.9999 5.29785 37.1788 5.30501 37.3863C5.31216 37.966 5.32648 38.6173 5.34079 39.3114C5.39804 41.3081 5.49823 43.2976 5.65568 45.1726C5.87037 47.7561 6.18526 49.9102 6.61465 51.5276C7.53068 54.9555 10.2215 57.6607 13.6495 58.5767C15.6676 59.1206 19.6395 59.4569 25.2215 59.6645C27.8766 59.7647 30.7177 59.8291 33.5588 59.8649C34.5536 59.8792 35.4768 59.8863 36.3069 59.8935H38.4109C39.2411 59.8863 40.1643 59.8792 41.159 59.8649C44.0001 59.8291 46.8413 59.7647 49.4963 59.6645C55.0784 59.4498 59.0502 59.1134 61.0684 58.5767C64.4963 57.6607 67.1872 54.9627 68.1032 51.5276C68.5397 49.9102 68.8475 47.7561 69.0622 45.1726C69.2196 43.2976 69.3198 41.3081 69.3771 39.3114C69.3985 38.6173 69.4057 37.966 69.4128 37.3863C69.4128 37.1788 69.42 36.9999 69.42 36.8424V36.4417ZM64.2673 36.8138C64.2673 36.9641 64.2673 37.1287 64.2602 37.3219C64.253 37.8801 64.2387 38.4956 64.2244 39.1611C64.1743 41.0648 64.0741 42.9684 63.9238 44.736C63.7306 47.0404 63.4586 48.9298 63.1223 50.1965C62.6786 51.8496 61.3761 53.1592 59.7301 53.5958C58.2272 53.9966 54.4415 54.3186 49.2959 54.5118C46.691 54.612 43.8856 54.6764 41.0875 54.7122C40.107 54.7265 39.1981 54.7337 38.3823 54.7337H36.3355L33.6304 54.7122C30.8322 54.6764 28.034 54.612 25.4219 54.5118C20.2764 54.3114 16.4834 53.9966 14.9877 53.5958C13.3417 53.1521 12.0393 51.8496 11.5956 50.1965C11.2592 48.9298 10.9873 47.0404 10.794 44.736C10.6437 42.9684 10.5507 41.0648 10.4935 39.1611C10.472 38.4956 10.4648 37.873 10.4577 37.3219C10.4577 37.1287 10.4505 36.9569 10.4505 36.8138V36.4703C10.4505 36.32 10.4505 36.1554 10.4577 35.9622C10.4648 35.404 10.4792 34.7885 10.4935 34.123C10.5436 32.2193 10.6437 30.3157 10.794 28.5481C10.9873 26.2437 11.2592 24.3544 11.5956 23.0877C12.0393 21.4345 13.3417 20.1249 14.9877 19.6883C16.4906 19.2876 20.2764 18.9655 25.4219 18.7723C28.0269 18.6721 30.8322 18.6077 33.6304 18.5719C34.6108 18.5576 35.5197 18.5505 36.3355 18.5505H38.3823L41.0875 18.5719C43.8856 18.6077 46.6838 18.6721 49.2959 18.7723C54.4415 18.9727 58.2344 19.2876 59.7301 19.6883C61.3761 20.132 62.6786 21.4345 63.1223 23.0877C63.4586 24.3544 63.7306 26.2437 63.9238 28.5481C64.0741 30.3157 64.1671 32.2193 64.2244 34.123C64.2459 34.7885 64.253 35.4111 64.2602 35.9622C64.2602 36.1554 64.2673 36.3272 64.2673 36.4703V36.8138ZM30.9897 46.2318L47.5927 36.5705L30.9897 27.0524V46.2318Z" fill="red" /></svg>
                                            </i>
                                        </div>
                                        <div class="data-content"><div class="title-1"><?php echo esc_html($item['title'] ?? ''); ?></div></div>
                                    </div>
                                </div>
                            </swiper-slide>
                        <?php endforeach; ?>
                    </swiper-container>
                    <button class="swiper-button-prev video-prev" aria-label="Previous slide" type="button"><i class="fa fa-angle-left"></i></button>
                    <button class="swiper-button-next video-next" aria-label="Next slide" type="button"><i class="fa fa-angle-right"></i></button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php // Modal xem video (dung chung he thong modal-container-ppus/data-pplus-close-modal co san,
// xem assets/css/footer-component.css + assets/js/main.js) - noi dung ".js-video-content" duoc JS
// bom vao luc bam 1 slide, xoa rong luc dong (dung phat video ngam sau khi da tat modal). ?>
<div class="modal-container-ppus modal-container-video" id="modal-video-player" data-pplus-modal>
    <div class="modal-background">
        <div class="modal">
            <button type="button" class="modal-video-close" data-pplus-close-modal="modal-video-player" aria-label="<?php echo esc_attr(pplus_t('Đóng', 'Close')); ?>">&times;</button>
            <div class="js-video-content"></div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($home['listBaoChis'])) : ?>
<div class="newspapers-box">
    <div class="container">
        <div class="row">
            <div class="col-md-12 data-header">
                <h2 class="title-1"><?php echo esc_html(pplus_t('Báo chí nói về Property Plus', 'Press coverage of Property Plus')); ?></h2>
                <hr />
            </div>
            <div class="col-md-12">
                <div class="silder-newspapers stick-custom-css">
                    <?php foreach ((array) $home['listBaoChis'] as $item) : ?>
                        <div class="item-newspapers">
                            <img src="<?php echo esc_url(pplus_admin_asset_url($item['img'] ?? '')); ?>" alt="<?php echo esc_attr($item['title'] ?? ''); ?>" loading="lazy" />
                            <div class="data-action"><?php echo esc_html($item['title'] ?? ''); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($home['listBlogNews'])) : ?>
<div class="blog-box">
    <div class="container">
        <div class="row">
            <div class="col-md-12 data-header">
                <h2 class="title-1"><?php echo esc_html(pplus_t('Tin tức nổi bật', 'Featured news')); ?></h2>
                <hr />
            </div>
            <div class="col-md-12">
                <div class="silder-blog">
                    <div class="item-blog stick-custom-css">
                        <?php foreach ((array) $home['listBlogNews'] as $item) : ?>
                            <a href="<?php echo esc_url(home_url($lang_prefix . '/blogs/' . ($item['alias'] ?? '') . '-' . ($item['id'] ?? ''))); ?>" class="data-1">
                                <img src="<?php echo esc_url(pplus_admin_asset_url($item['img'] ?? '')); ?>" alt="<?php echo esc_attr($item['title'] ?? ''); ?>" loading="lazy" />
                                <div class="data-content">
                                    <?php echo esc_html($item['title'] ?? ''); ?>
                                    <div class="data-view"><?php echo esc_html(pplus_t('Xem thêm', 'Read more')); ?></div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="story-project-client">
    <div class="container">
        <div class="row">
            <div class="col-md-12 data-header">
                <h2 class="title-1"><?php echo esc_html(pplus_t('Khách hàng của Property Plus', 'Property Plus Customers')); ?></h2>
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

<?php else : ?>
<div class="container" style="padding: 40px 0;">
    <?php pplus_render_api_error(); ?>
</div>
<?php endif; ?>

<?php get_footer(); ?>
