<?php
/**
 * TypeSite 1 - "Dịch vụ Quản lý của Property Plus". Port 1:1 tu
 * the-management.component.html (PPlusAngularResi) - 100% noi dung tinh, KHONG goi API nao (xac
 * nhan qua doc day du the-management.component.ts: ngOnInit() chi gan GlobalConstants.
 * ListLanguageChange, khong co goi service nao khac). Giu nguyen ten class Angular
 * (.service-1-*) de khop CSS compile tu list-service.component.less (xem functions.php).
 */
if (!defined('ABSPATH')) exit;

$img = get_template_directory_uri() . '/assets/Content/ImgService/';
?>
<div class="service-1-banner">
    <?php // 24/10/2026: anh banner = phan tu LCP trang Dich vu - KHONG lazy (Lighthouse: "LCP image was lazily loaded") +
    // width/height (thieu thi khoi ben duoi nhay xuong khi anh tai xong: CLS 0,103) + WebP 240KB -> nhe hon. ?>
    <picture>
        <source type="image/webp" srcset="<?php echo esc_url($img . 'Group 1194.webp'); ?>" />
        <img class="img-responsive" src="<?php echo esc_url($img . 'Group 1194.png'); ?>" alt="" width="1099" height="344" fetchpriority="high" />
    </picture>
    <div class="service-1-banner-c">
        <h1><?php echo esc_html(pplus_localize('Dịch vụ')); ?></h1>
        <h3><?php echo esc_html(pplus_localize('Quản lý của Property Plus')); ?></h3>
        <hr />
        <p><?php echo esc_html(pplus_localize('Property Plus tối ưu hóa quy trình quản lý nhằm mang đến những trải nghiệm tốt nhất:')); ?></p>
    </div>
</div>
<div class="service-1-body-1 row">
    <div class="col-md-4">
        <img class="img-responsive" src="<?php echo esc_url($img . 'Customer Service_Two Color.png'); ?>" alt="" loading="lazy" />
        <div class="service-1-body-1-t">
            1. <?php echo esc_html(pplus_localize('Chăm sóc khách hàng')); ?>
            <img class="service-1-body-next" src="<?php echo esc_url($img . 'Vector.png'); ?>" alt="" loading="lazy" />
        </div>
        <p><?php echo esc_html(pplus_localize('Xử lý tất cả các vấn đề khách hàng gặp phải trong suốt quá trình sử dụng dịch vụ của Property Plus')); ?></p>
    </div>
    <div class="col-md-4">
        <img class="img-responsive" src="<?php echo esc_url($img . 'Group 9.png'); ?>" alt="" loading="lazy" />
        <div class="service-1-body-1-t">
            2. <?php echo esc_html(pplus_localize('Giải quyết thủ tục hành chính')); ?>
            <img class="service-1-body-next" src="<?php echo esc_url($img . 'Vector.png'); ?>" alt="" loading="lazy" />
        </div>
        <p><?php echo esc_html(pplus_localize('Đại diện khách hàng làm việc với chính quyền sở tại về việc lưu trú và các thủ tục hành chính')); ?></p>
    </div>
    <div class="col-md-4">
        <img class="img-responsive" src="<?php echo esc_url($img . 'Online payment_Flatline.png'); ?>" alt="" loading="lazy" />
        <div class="service-1-body-1-t">
            3. <?php echo esc_html(pplus_localize('Hỗ trợ quản lý tài chính căn hộ')); ?>
        </div>
        <p><?php echo esc_html(pplus_localize('Đảm bảo các loại hóa đơn dịch vụ được thanh toán đúng hạn')); ?></p>
    </div>
</div>
<div class="service-1-body-2 row">
    <div class="title-1 mb-30px">
        <label><?php echo esc_html(pplus_localize('QUY TRÌNH CHĂM SÓC KHÁCH HÀNG')); ?></label>
    </div>
    <div class="col-md-4 mobile-50-w">
        <div class="service-body-img"><img class="img-responsive" src="<?php echo esc_url($img . 'telephone.png'); ?>" alt="" loading="lazy" /></div>
        <div class="service-1-body-2-t">1. <?php echo esc_html(pplus_localize('Sẵn sàng 24/7')); ?></div>
        <p><?php echo esc_html(pplus_localize('Hệ thống hotline Anh - Nhật - Việt sẵn sàng 24/07')); ?></p>
    </div>
    <div class="col-md-4 mobile-50-w">
        <div class="service-body-img"><img class="img-responsive" src="<?php echo esc_url($img . 'smartphone.png'); ?>" alt="" loading="lazy" /></div>
        <div class="service-1-body-2-t">2. <?php echo esc_html(pplus_localize('Ứng dụng công nghệ')); ?></div>
        <p><?php echo esc_html(pplus_localize('Sử dụng App Property Plus (Android và iOS) để nhanh chóng chuyển giao thông tin và theo dõi quá trình')); ?></p>
    </div>
    <div class="col-md-4 mobile-50-w">
        <div class="service-body-img"><img class="img-responsive" src="<?php echo esc_url($img . 'user-group.png'); ?>" alt="" loading="lazy" /></div>
        <div class="service-1-body-2-t">3. <?php echo esc_html(pplus_localize('Điều phối')); ?></div>
        <p><?php echo esc_html(pplus_localize('Linh hoạt giữa các bộ phận chuyên môn để xử lý vấn đề của khách hàng')); ?></p>
    </div>
    <div class="col-md-4 mobile-50-w">
        <div class="service-body-img"><img class="img-responsive" src="<?php echo esc_url($img . 'customer-service.png'); ?>" alt="" loading="lazy" /></div>
        <div class="service-1-body-2-t">4. <?php echo esc_html(pplus_localize('Phản hồi')); ?></div>
        <p><?php echo esc_html(pplus_localize('Đưa giải pháp và phương án xử lý chỉ trong 02 tiếng')); ?></p>
    </div>
    <div class="col-md-4 mobile-50-w">
        <div class="service-body-img"><img class="img-responsive" src="<?php echo esc_url($img . 'chat.png'); ?>" alt="" loading="lazy" /></div>
        <div class="service-1-body-2-t">5. <?php echo esc_html(pplus_localize('Nhận đánh giá')); ?></div>
        <p><?php echo esc_html(pplus_localize('Thường xuyên xin đánh giá để liên tục cải thiện dịch vụ')); ?></p>
    </div>
</div>
