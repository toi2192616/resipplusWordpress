<?php
/**
 * Template Name: Lien he
 * Trang /lien-he (B6) - port tu contact-index.component.html.
 *
 * Toan bo noi dung trai (dia chi/SDT/email/social/map) la TINH ca ben Angular goc (hard-code
 * qua Localizer(), khong fetch API rieng cho trang nay - khac voi cac man hinh khac). Form ben
 * phai (#SupportCustomerForm) CHUA noi submit that toi API (officeService.IssueNewApartment) -
 * dung y TODO da ghi san trong docs/PORTING_NOTES.md muc 2, giu nguyen giao dien, khong submit
 * that o day.
 *
 * Day la 1 WordPress Page that (tao qua wp-admin, gan Page Template nay, slug "lien-he" - khop
 * link co san trong parts/header.php dong 175).
 */
if (!defined('ABSPATH')) exit;

get_header();

get_template_part('parts/url-box-content', null, [
    'breadcrumb' => [
        ['name' => pplus_t('Trang chủ', 'Home'), 'url' => home_url('/')],
        ['name' => pplus_t('Liên hệ', 'Contact')],
    ],
]);
get_template_part('parts/office-filter-panel');
?>
<div class="contact">
    <div class="container">
        <div class="contact-content row">
            <div class="col-md-6 content-left">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="btn-back">
                    <i class="icon">
                        <svg width="15" height="12" viewBox="0 0 15 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M14 5.25C14.4142 5.25 14.75 5.58579 14.75 6C14.75 6.41421 14.4142 6.75 14 6.75V5.25ZM0.46967 6.53033C0.176777 6.23744 0.176777 5.76256 0.46967 5.46967L5.24264 0.696699C5.53553 0.403806 6.01041 0.403806 6.3033 0.696699C6.59619 0.989593 6.59619 1.46447 6.3033 1.75736L2.06066 6L6.3033 10.2426C6.59619 10.5355 6.59619 11.0104 6.3033 11.3033C6.01041 11.5962 5.53553 11.5962 5.24264 11.3033L0.46967 6.53033ZM14 6.75H1V5.25H14V6.75Z" />
                        </svg>
                    </i>
                    <?php echo esc_html(pplus_t('Quay lại', 'Back')); ?>
                </a>
                <div class="text-1"><?php echo esc_html(pplus_t('Chúng tôi hân hạnh được tư vấn cho bạn', 'We are delighted to advise you')); ?></div>
                <a class="link-1" href="<?php echo esc_url(home_url((pplus_current_lang() === 'en' ? '/en' : '') . '/' . pplus_office_list_base_slug() . '/')); ?>">
                    <?php echo esc_html(pplus_t('Tìm kiếm trên bản đồ', 'Search on the map')); ?>
                </a>

                <div class="text-2"><?php echo esc_html(pplus_t('Địa chỉ', 'Address')); ?></div>
                <div class="text-3">
                    <a href="https://goo.gl/maps/3KkL4TJ54twjdmMS7" target="_blank" rel="noopener">
                        <?php echo esc_html(pplus_t('Tầng 4, tòa nhà Kinh Đô, 292 Tây Sơn, Trung Liệt, Đống Đa, Hà Nội', '4th Floor, Kinh Do Building, 292 Tay Son, Trung Liet, Dong Da, Hanoi')); ?>
                    </a>
                </div>

                <div class="group-1">
                    <div class="group-c">
                        <div class="text-2"><?php echo esc_html(pplus_t('Liên hệ', 'Contact')); ?></div>
                        <div class="text-3">
                            0865 364 866<br />
                            office@propertyplus.com.vn
                        </div>
                    </div>
                    <div class="group-c">
                        <div class="text-4"><?php echo esc_html(pplus_t('Tìm chúng tôi ở', 'Find us on')); ?></div>
                        <div class="contact-link">
                            <a href="<?php echo esc_url(home_url('/')); ?>" aria-label="Google">
                                <i>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="#545353">
                                        <g clip-path="url(#pplusGoogleClip)">
                                            <path d="M16 7.99997C16 7.49735 15.9528 6.99425 15.8595 6.50581C15.8173 6.28472 15.6237 6.12494 15.399 6.12494H8.03125C7.77216 6.12494 7.5625 6.3346 7.5625 6.59369V9.40619C7.5625 9.66528 7.77216 9.87494 8.03125 9.87494H11.8115C11.5759 10.3518 11.2513 10.7655 10.8702 11.1135L13.5196 13.7629C15.044 12.3106 16 10.2668 16 7.99997Z" />
                                            <path d="M8.00077 3.75C8.92042 3.75 9.7952 4.04206 10.5304 4.59503C10.7162 4.73466 10.9785 4.71772 11.1433 4.55153L13.1437 2.55109C13.2367 2.45816 13.2865 2.33044 13.2806 2.19906C13.2747 2.06769 13.2142 1.945 13.1135 1.86031C11.6821 0.660563 9.86614 0 8.00077 0C5.25548 0 2.8302 1.39544 1.39648 3.51413L4.14798 6.26562C4.80442 4.787 6.28136 3.75 8.00077 3.75Z" />
                                            <path d="M8.00077 16C9.79024 16 11.438 15.4 12.7696 14.4004L10.0705 11.7013C9.45355 12.0487 8.74842 12.25 8.00077 12.25C6.28136 12.25 4.80442 11.213 4.14798 9.73438L1.39648 12.4859C2.8302 14.6046 5.25549 16 8.00077 16Z" />
                                            <path d="M3.81825 8.6752C3.78234 8.45399 3.75 8.2312 3.75 8.00005C3.75 7.76889 3.78234 7.54611 3.81825 7.32489L0.876875 4.38351C0.320375 5.47039 0 6.69742 0 8.00005C0 9.30267 0.320375 10.5297 0.876906 11.6165L3.81825 8.6752Z" />
                                        </g>
                                        <defs>
                                            <clipPath id="pplusGoogleClip">
                                                <rect width="16" height="16" />
                                            </clipPath>
                                        </defs>
                                    </svg>
                                </i>
                            </a>
                            <a href="https://www.facebook.com/profile.php?id=100076484342575" target="_blank" rel="noopener" aria-label="Facebook">
                                <i>
                                    <svg width="9" height="17" viewBox="0 0 9 17" fill="#545353" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M5.1109 5.62199V3.57763C5.1109 3.01339 5.56884 2.55545 6.13308 2.55545H7.15526V0H5.1109C3.41715 0 2.04436 1.37279 2.04436 3.06654V5.62199H0V8.17744H2.04436V16.3549H5.1109V8.17744H7.15526L8.17744 5.62199H5.1109Z" />
                                    </svg>
                                </i>
                            </a>
                            <a href="<?php echo esc_url(home_url('/')); ?>" aria-label="Instagram">
                                <i>
                                    <svg width="17" height="17" viewBox="0 0 17 17" fill="#545353" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M11.7612 0H5.47596C2.58371 0 0.238281 2.34543 0.238281 5.23768V11.5229C0.238281 14.4151 2.58371 16.7606 5.47596 16.7606H11.7612C14.6534 16.7606 16.9988 14.4151 16.9988 11.5229V5.23768C16.9988 2.34543 14.6534 0 11.7612 0ZM15.4275 11.5229C15.4275 13.5446 13.7829 15.1893 11.7612 15.1893H5.47596C3.45421 15.1893 1.80958 13.5446 1.80958 11.5229V5.23768C1.80958 3.21593 3.45421 1.5713 5.47596 1.5713H11.7612C13.7829 1.5713 15.4275 3.21593 15.4275 5.23768V11.5229Z" />
                                        <path d="M8.61983 4.19012C6.30582 4.19012 4.42969 6.06626 4.42969 8.38027C4.42969 10.6943 6.30582 12.5704 8.61983 12.5704C10.9338 12.5704 12.81 10.6943 12.81 8.38027C12.81 6.06626 10.9338 4.19012 8.61983 4.19012ZM8.61983 10.9991C7.17632 10.9991 6.00099 9.82377 6.00099 8.38027C6.00099 6.93571 7.17632 5.76143 8.61983 5.76143C10.0633 5.76143 11.2387 6.93571 11.2387 8.38027C11.2387 9.82377 10.0633 10.9991 8.61983 10.9991Z" />
                                        <path d="M13.1228 4.43436C13.4311 4.43436 13.6811 4.18438 13.6811 3.87602C13.6811 3.56766 13.4311 3.31769 13.1228 3.31769C12.8144 3.31769 12.5645 3.56766 12.5645 3.87602C12.5645 4.18438 12.8144 4.43436 13.1228 4.43436Z" />
                                    </svg>
                                </i>
                            </a>
                        </div>
                    </div>
                </div>

                <iframe class="iframe-map" src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d14898.561539119424!2d105.8228378!3d21.0070477!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3135ad91db042b97%3A0xf45d064176086b19!2zQ2hvIHRodcOqIHbEg24gcGjDsm5nIHThuqFpIEjDoCBO4buZaSAtIHByb3BlcnR5cGx1cy52bg!5e0!3m2!1svi!2s!4v1681804917477!5m2!1svi!2s"
                    style="border:0;" allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>

            <div class="col-md-6 content-right">
                <div class="text-1"><?php echo esc_html(pplus_t('Để chúng tôi giúp bạn…', 'Let us help you…')); ?></div>
                <div class="from-send-contact">
                    <form id="SupportCustomerForm" onsubmit="return false;">
                        <div class="mb-3">
                            <input class="form-control" type="text" maxlength="100" name="OwnerName" placeholder="<?php echo esc_attr(pplus_t('Họ và tên', 'Full name')); ?>" />
                        </div>
                        <div class="mb-3">
                            <input class="form-control" type="email" maxlength="100" name="OwnerEmail" placeholder="<?php echo esc_attr(pplus_t('Email', 'Email')); ?>" />
                        </div>
                        <div class="mb-3">
                            <input class="form-control" type="tel" maxlength="100" name="OwnerMobile" placeholder="<?php echo esc_attr(pplus_t('Điện thoại', 'Phone number')); ?>" />
                        </div>
                        <div class="mb-3">
                            <textarea class="form-control" name="AdviseContent" placeholder="<?php echo esc_attr(pplus_t('Yêu cầu tư vấn', 'Your request')); ?>"></textarea>
                        </div>
                        <?php // Khong dung `disabled` - browser tu giam opacity lam nut xam sai mau san (#252323 den
                        // giong production). Chan submit that qua onsubmit="return false" tren <form>, nut van
                        // hien thi/click duoc binh thuong nhu Angular goc (TODO noi API that o docs/PORTING_NOTES.md). ?>
                        <button type="submit" class="btn-sendcontact" title="<?php echo esc_attr(pplus_t('Chưa nối API gửi liên hệ thật (TODO)', 'Not yet wired to the real API (TODO)')); ?>">
                            <?php echo esc_html(pplus_t('Gửi yêu cầu tư vấn', 'Send request')); ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php get_footer(); ?>
