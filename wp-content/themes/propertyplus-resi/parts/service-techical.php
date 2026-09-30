<?php
/**
 * TypeSite 3 - "Dịch vụ kỹ thuật - bảo dưỡng". Port 1:1 tu techical.component.html (PPlusAngularResi,
 * ten file/class giu nguyen chinh ta "techical" nhu ban goc du thieu chu "n" - KHONG sua lai vi day
 * la ten CLASS CSS that (.service-3-*), sua se lam mat khop CSS) - 100% noi dung tinh, khong goi
 * API nao (giong the-management/housekeeping).
 */
if (!defined('ABSPATH')) exit;

$img = get_template_directory_uri() . '/assets/Content/ImgService/';
?>
<div class="service-3-box-1 mb-60px">
    <div class="service-3-box-1-icon"><img src="<?php echo esc_url($img . 'Group 4.png'); ?>" alt="" loading="lazy" /></div>
    <div class="service-3-title-c text-center">
        <h3><?php echo esc_html(pplus_localize('Chuẩn kỹ thuật đầu vào')); ?></h3>
        <p><?php echo esc_html(pplus_localize('Bộ phận kỹ thuật sẽ tiến hành kiểm tra và bảo dưỡng tổng thể căn hộ trước khi khách check-in, đảm bảo các trang thiết bị trong căn hộ hoạt động trong tình trang tốt nhất')); ?></p>
    </div>
</div>
<div class="service-3-body-1 row">
    <div class="col-md-6 row">
        <div class="service-3-body-step">
            <div class="service-3-body-step-head">
                <label>01</label>
                <div class="service-3-body-step1-c">
                    <label><?php echo esc_html(pplus_localize('Quy trình bảo dưỡng định kỳ các danh mục sau')); ?></label>
                </div>
            </div>
            <div class="form-group">
                <div class="col-md-6">
                    <img class="img-responsive" src="<?php echo esc_url($img . 'Computer display_Flatline.png'); ?>" alt="" loading="lazy" />
                    <div class="service-3-body-2-t"><?php echo esc_html(pplus_localize('03 tháng 01 lần')); ?></div>
                    <p><?php echo esc_html(pplus_localize('Hệ thống nước, máy giặt, quạt, quạt thông gió, ti vi, và internet')); ?></p>
                </div>
                <div class="col-md-6">
                    <img class="img-responsive" src="<?php echo esc_url($img . 'Group 1192.png'); ?>" alt="" loading="lazy" />
                    <div class="service-3-body-2-t"><?php echo esc_html(pplus_localize('04 tháng 01 lần')); ?></div>
                    <p><?php echo esc_html(pplus_localize('Hệ thống điều hoà')); ?></p>
                </div>
            </div>
            <div class="form-group">
                <div class="col-md-6">
                    <img class="img-responsive" src="<?php echo esc_url($img . 'Factory Worker_Flatline.png'); ?>" alt="" loading="lazy" />
                    <div class="service-3-body-2-t"><?php echo esc_html(pplus_localize('06 tháng 01 lần')); ?></div>
                    <p><?php echo esc_html(pplus_localize('Vệ sinh thảm và sofa, ghế ăn, silicon khu vực bếp và nhà tắm')); ?></p>
                </div>
                <div class="col-md-6">
                    <img class="img-responsive" src="<?php echo esc_url($img . 'Group 1191.png'); ?>" alt="" loading="lazy" />
                    <div class="service-3-body-2-t"><?php echo esc_html(pplus_localize('12 tháng 01 lần')); ?></div>
                    <p><?php echo esc_html(pplus_localize('Tường, rèm, cửa, két, đồng hồ, điều khiển')); ?></p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 row">
        <div class="service-3-body-step">
            <div class="service-3-body-step-head">
                <label>02</label>
                <div class="service-3-body-step1-c">
                    <label><?php echo esc_html(pplus_localize('Quy trình xử lý sự cố phát sinh')); ?></label>
                </div>
            </div>
            <div class="col-md-12 mt-25">
                <div class="service-3-body-img pull-left"><img class="img-responsive" src="<?php echo esc_url($img . 'Mobile Testing_Flatline.png'); ?>" alt="" loading="lazy" /></div>
                <div class="service-3-body-2-t2 pull-left">
                    1. <?php echo esc_html(pplus_localize('Tiếp nhận thông tin')); ?>
                    <p><?php echo esc_html(pplus_localize('Tiếp nhận các vấn đề phát sinh tại căn hộ qua ứng dụng mobile app của Property Plus.')); ?></p>
                </div>
            </div>
            <div class="col-md-12 mt-50">
                <div class="service-3-body-2-t2 pull-left">
                    2. <?php echo esc_html(pplus_localize('Kiểm tra và đưa phương án xử lý')); ?>
                    <p><?php echo esc_html(pplus_localize('Nhân viên kỹ thuật đến căn hộ kiểm tra và đưa ra các phương án xử lý trong vòng hai giờ (ngoại trừ trường hợp đặc biệt).')); ?></p>
                </div>
                <div class="service-3-body-img pull-left"><img class="img-responsive" src="<?php echo esc_url($img . 'Quality Check_Flatline.png'); ?>" alt="" loading="lazy" /></div>
            </div>
        </div>
    </div>
</div>

<hr class="service-hr-cus" />

<div class="service-3-box-2">
    <img class="img-responsive" src="<?php echo esc_url($img . 'secure.png'); ?>" alt="" loading="lazy" />
    <div class="service-3-box2-c">
        <h3><?php echo esc_html(pplus_localize('Đảm bảo an toàn - an ninh trong căn hộ')); ?></h3>
        <p><?php echo esc_html(pplus_localize('Property Plus cam kết an toàn an ninh tuyệt đối trong căn hộ. Chúng tôi sẽ chịu trách nhiệm hoàn toàn về thiệt hại tài sản do nhân viên của Property Plus tạo ra trong căn hộ khách hàng sử dụng dịch vụ của chúng tôi.')); ?></p>
    </div>
</div>
