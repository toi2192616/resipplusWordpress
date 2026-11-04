<?php
/**
 * Template Name: Lien he
 * Trang /lien-he (B6) - port DUNG tu contact-index.component.html + contact-index.component.less cua Resi
 * (D:\SoureceCode\PPlusAngularResi\src\app\modules\contact\contact-index).
 *
 * LAM LAI 4/11/2026: ban truoc day la ban sao trang Lien he cua theme VAN PHONG (Office) - khong co banner,
 * khong co icon, khong co anh ben phai, va KHONG co file CSS nao duoc nap (contact-component.css la CSS cua
 * Office va chua tung duoc enqueue) nen trang hien ra nhu HTML tho. Trang that tren web chinh gom:
 *   1. Banner "CHUNG TOI HAN HANH DUOC TU VAN CHO BAN" (nen anh + lop phu toi)
 *   2. "Thong tin lieu he": 3 dong co icon (SDT, email, dia chi) + anh toa nha cong vom ben phai
 *   3. Cot trai: khung form gradient tim-xanh "De chung toi giup ban..." (4 truong + nut "Gui yeu cau tu van")
 *      Cot phai: "Dia chi cua chung toi" (ban do) + link "Tim tren ban do" + "Tim chung toi o" (3 icon mang xa hoi)
 *
 * Form dung class ".pplus-advise-form" - bo xu ly chung trong assets/js/main.js, POST toi REST route
 * pplus/v1/advise (inc/advise-form.php) -> {lang}/Favourite/IssueLeaseApartment cua .NET, y het
 * IssueLeaseApartmentBase() ben Angular. Truoc day form nay "onsubmit=return false" (khong gui duoc).
 *
 * Day la 1 WordPress Page that (slug "lien-he", gan Page Template nay) - xem inc/about-route.php cho alias
 * EN/JA "contact".
 */
if (!defined('ABSPATH')) exit;

get_header();

pplus_render_suggest_header();

get_template_part('parts/url-box-content', null, [
    'breadcrumb' => [
        ['name' => pplus_t('Trang chủ', 'Home'), 'url' => pplus_home_url()],
        ['name' => pplus_t('Liên hệ', 'Contact')],
    ],
]);

$ct_img = get_template_directory_uri() . '/assets/Content/Img-LienHe/';
// Ten file anh co dau cach ("Group 1000004441.png") -> ma hoa de URL hop le.
$ct = static fn (string $file): string => esc_url($ct_img . rawurlencode($file));
$maps_link = pplus_resi_map_link(); // ban do CUA RESI (cung dia diem voi ban do o footer) - khong phai ban do Van phong
$company   = (array) ((pplus_get_footer_data() ?: [])['company'] ?? []); // facebook/instagram that tu API (da duoc cache boi footer)
$ct_fb     = !empty($company['facebook']) ? (string) $company['facebook'] : 'https://www.facebook.com/propertyplusgroup/';
$ct_ig     = !empty($company['instagram']) ? (string) $company['instagram'] : '';
?>
<div class="contact">
    <div class="container">
        <div class="contact-conten-1">
            <h1 class="c-c-1-t-1"><?php echo esc_html(pplus_t('Chúng tôi hân hạnh được tư vấn cho bạn', 'We are delighted to advise you')); ?></h1>
            <div class="c-c-1-t-2"><?php echo esc_html(pplus_t('Tư vẫn miến phí - Giá thuê tốt - Gửi báo giá nhanh chóng', 'Free consultation - Good rental prices - Fast quotation')); ?></div>
        </div>

        <div class="container">
            <div class="container-content-2">
                <div class="col-md-12 row">
                    <div class="col-md-6">
                        <div class="group-title-style-1">
                            <h2 class="title"><?php echo esc_html(pplus_t('Thông tin liên hệ', 'Contact information')); ?></h2>
                        </div>
                        <div class="content-2">
                            <div class="content-2-item">
                                <div class="item-img">
                                    <img src="<?php echo $ct('Group 1000004441.png'); ?>" width="93" height="96" alt="" />
                                </div>
                                <div class="item-text"><a href="tel:+84856662566">08 5666 2566</a></div>
                            </div>
                            <div class="content-2-item">
                                <div class="item-img">
                                    <img src="<?php echo $ct('Group 1000004440.png'); ?>" width="93" height="96" alt="" />
                                </div>
                                <div class="item-text"><a href="mailto:enquiry@propertyplus.com.vn">enquiry@propertyplus.com.vn</a></div>
                            </div>
                            <div class="content-2-item">
                                <div class="item-img">
                                    <img src="<?php echo $ct('Group 1000004442.png'); ?>" width="93" height="96" alt="" />
                                </div>
                                <div class="item-text"><?php echo esc_html(pplus_t('Tầng 4, tòa nhà Kinh Đô, 292 Tây Sơn, Trung Liệt, Đống Đa, Hà Nội', '4th Floor, Kinh Do Building, 292 Tay Son, Trung Liet, Dong Da, Hanoi')); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <img class="content-2-img" src="<?php echo $ct('img-right.png'); ?>" width="456" height="642" alt="<?php echo esc_attr(pplus_t('Property Plus - Hà Nội', 'Property Plus - Hanoi')); ?>" loading="lazy" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="contact-content row">
            <div class="col-md-6">
                <div class="content-right">
                    <div class="text-1"><?php echo esc_html(pplus_t('Để chúng tôi giúp bạn…', 'Let us help you…')); ?></div>
                    <div class="from-send-contact">
                        <form id="SupportCustomerForm" class="pplus-advise-form" novalidate autocomplete="off">
                            <div class="mb-3">
                                <input class="form-control" name="OwnerName" type="text" maxlength="100" placeholder="<?php echo esc_attr(pplus_t('Họ và tên', 'Full name')); ?>" aria-label="<?php echo esc_attr(pplus_t('Họ và tên', 'Full name')); ?>" />
                            </div>
                            <div class="mb-3">
                                <input class="form-control" name="OwnerEmail" type="email" maxlength="100" placeholder="<?php echo esc_attr(pplus_t('Email', 'Email')); ?>" aria-label="Email" />
                            </div>
                            <div class="mb-3">
                                <input class="form-control" name="OwnerMobile" type="tel" maxlength="100" placeholder="<?php echo esc_attr(pplus_t('Điện thoại', 'Phone number')); ?>" aria-label="<?php echo esc_attr(pplus_t('Điện thoại', 'Phone number')); ?>" />
                            </div>
                            <div class="mb-3">
                                <textarea class="form-control" name="AdviseContent" placeholder="<?php echo esc_attr(pplus_t('Yêu cầu tư vấn', 'Your request')); ?>" aria-label="<?php echo esc_attr(pplus_t('Yêu cầu tư vấn', 'Your request')); ?>"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary mb-3 btn-sendcontact"><?php echo esc_html(pplus_t('Gửi yêu cầu tư vấn', 'Send request')); ?></button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6 content-left">
                <div class="group-title-style-1">
                    <h2 class="title"><?php echo esc_html(pplus_t('Địa chỉ của chúng tôi', 'Our address')); ?></h2>
                </div>

                <iframe class="iframe-map" title="<?php echo esc_attr(pplus_t('Bản đồ Property Plus', 'Property Plus map')); ?>" src="<?php echo esc_url(pplus_resi_map_embed_url()); ?>"
                    style="border:0;" allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>

                <a href="<?php echo esc_url($maps_link); ?>" target="_blank" rel="noopener" class="action-link-google-map">
                    <?php // Khong dung pplus_t(): tu dien API tieng Nhat dich sai khoa nay (ra "一軒家タイプ" = loai nha rieng) va tu dien duoc uu tien. ?>
                    <?php echo esc_html(['vi' => 'Tìm trên bản đồ', 'en' => 'Find on the map', 'ja' => '地図で見る'][pplus_current_lang()] ?? 'Tìm trên bản đồ'); ?>
                    <i>
                        <svg width="17" height="14" viewBox="0 0 17 14" fill="#B581DB" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M1 6.85596H16M16 6.85596L10.375 0.855957M16 6.85596L10.375 12.856" stroke="#B581DB" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </i>
                </a>

                <div class="group-title-style-1">
                    <h2 class="title"><?php echo esc_html(pplus_t('Tìm chúng tôi ở', 'Find us on')); ?></h2>
                </div>

                <div class="group-1">
                    <?php // Ban goc de <a> khong co href. Link that lay tu API cong ty (facebook/instagram) + ban do Resi. ?>
                    <div class="group-c">
                        <a href="<?php echo esc_url($ct_fb); ?>" target="_blank" rel="noopener" aria-label="Facebook">
                            <img src="<?php echo $ct('Group 1000004441-1.png'); ?>" width="126" height="133" title="Facebook" alt="Facebook" loading="lazy" />
                        </a>
                    </div>
                    <div class="group-c">
                        <a href="<?php echo esc_url($maps_link); ?>" target="_blank" rel="noopener" aria-label="Google">
                            <img src="<?php echo $ct('Group 1000004440-1.png'); ?>" width="126" height="133" title="Google" alt="Google" loading="lazy" />
                        </a>
                    </div>
                    <div class="group-c">
                        <a<?php if ($ct_ig !== '') : ?> href="<?php echo esc_url($ct_ig); ?>" target="_blank" rel="noopener"<?php endif; ?> aria-label="Instagram">
                            <img src="<?php echo $ct('Group 1000004442-1.png'); ?>" width="126" height="133" title="Instagram" alt="Instagram" loading="lazy" />
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php get_footer(); ?>
