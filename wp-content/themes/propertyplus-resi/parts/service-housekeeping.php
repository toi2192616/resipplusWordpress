<?php
/**
 * TypeSite 2 - "Dịch vụ dọn phòng". Port 1:1 tu housekeeping.component.html (PPlusAngularResi) -
 * 100% noi dung tinh, khong goi API nao (giong the-management, xem ghi chu file do).
 */
if (!defined('ABSPATH')) exit;

$img = get_template_directory_uri() . '/assets/Content/ImgService/';
?>
<div class="service-1-body-2 mt-0 row">
    <div class="title-1">
        <label><?php echo esc_html(pplus_localize('DỊCH VỤ DỌN PHÒNG')); ?></label>
    </div>
    <div class="title-2"><?php echo esc_html(pplus_localize('Quy trình theo tiêu chuẩn 05 sao')); ?></div>

    <div class="col-md-4 mobile-50-w">
        <div class="service-body-img"><img class="img-responsive" src="<?php echo esc_url($img . 'casual-t-shirt.png'); ?>" alt="" loading="lazy" /></div>
        <div class="service-1-body-2-t">1. <?php echo esc_html(pplus_localize('Giặt là')); ?></div>
        <p><?php echo esc_html(pplus_localize('Kiểm tra, phân loại chất liệu trước khi giặt là quần áo (sử dụng máy giặt)')); ?></p>
    </div>
    <div class="col-md-4 mobile-50-w">
        <div class="service-body-img"><img class="img-responsive" src="<?php echo esc_url($img . 'triangular-arrows-sign-for-recycle.png'); ?>" alt="" loading="lazy" /></div>
        <div class="service-1-body-2-t">2. <?php echo esc_html(pplus_localize('Dọn rác')); ?></div>
        <p><?php echo esc_html(pplus_localize('Xử lý rác và các vật dụng không sử dụng trong căn hộ')); ?></p>
    </div>
    <div class="col-md-4 mobile-50-w">
        <div class="service-body-img"><img class="img-responsive" src="<?php echo esc_url($img . 'mop.png'); ?>" alt="" loading="lazy" /></div>
        <div class="service-1-body-2-t">3. <?php echo esc_html(pplus_localize('Dọn dẹp phòng ngủ')); ?></div>
        <p><?php echo esc_html(pplus_localize('Dọn dẹp, thay ga trải giường')); ?></p>
    </div>
    <div class="col-md-4 mobile-50-w">
        <div class="service-body-img"><img class="img-responsive" src="<?php echo esc_url($img . 'towel.png'); ?>" alt="" loading="lazy" /></div>
        <div class="service-1-body-2-t">4. <?php echo esc_html(pplus_localize('Dọn dẹp phòng tắm')); ?></div>
        <p><?php echo esc_html(pplus_localize('Dọn dẹp phòng tắm, diệt khuẩn, cọ rửa sạch sẽ')); ?></p>
    </div>
    <div class="col-md-4 mobile-50-w">
        <div class="service-body-img"><img class="img-responsive" src="<?php echo esc_url($img . 'washing-machine.png'); ?>" alt="" loading="lazy" /></div>
        <div class="service-1-body-2-t">5. <?php echo esc_html(pplus_localize('Dọn dẹp khu vực bếp')); ?></div>
        <p><?php echo esc_html(pplus_localize('Rửa bát, lau chùi khu vực bếp')); ?></p>
    </div>
    <div class="col-md-4 mobile-50-w">
        <div class="service-body-img"><img class="img-responsive" src="<?php echo esc_url($img . 'bucket.png'); ?>" alt="" loading="lazy" /></div>
        <div class="service-1-body-2-t">6. <?php echo esc_html(pplus_localize('Hút bụi')); ?></div>
        <p><?php echo esc_html(pplus_localize('Hút bụi sàn và lau sạch các bề mặt trong nhà, ban công và lô gia')); ?></p>
    </div>
</div>

<div class="service-2-body-2 mt-60 row">
    <div class="title-1">
        <label><?php echo esc_html(pplus_localize('CAM KẾT')); ?></label> <?php echo esc_html(pplus_localize('CHẤT LƯỢNG')); ?>
    </div>
    <div class="title-2"><?php echo esc_html(pplus_localize('Đảm bảo sau khi dọn phòng')); ?></div>

    <div class="col-md-4">
        <div class="service-2-body-2-box">
            <div class="service-body-step">1</div>
            <div class="service-1-body-2-t">1. <?php echo esc_html(pplus_localize('Vệ sinh')); ?></div>
            <p><?php echo esc_html(pplus_localize('Luôn đảm bảo vệ sinh căn hộ sạch sẽ, các thiết bị hoạt động tốt')); ?></p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="service-2-body-2-box">
            <div class="service-body-step">2</div>
            <div class="service-1-body-2-t">2. <?php echo esc_html(pplus_localize('Đảm bảo')); ?></div>
            <p><?php echo esc_html(pplus_localize('Bảo mật mọi thông tin cá nhân và đảm bảo đồ đạc trong căn hộ')); ?></p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="service-2-body-2-box">
            <div class="service-body-step">3</div>
            <div class="service-1-body-2-t">3. <?php echo esc_html(pplus_localize('Giám sát')); ?></div>
            <p><?php echo esc_html(pplus_localize('Mọi phát sinh bất thường trong căn hộ đều được báo cáo kịp thời tới khách hàng')); ?></p>
        </div>
    </div>
</div>
