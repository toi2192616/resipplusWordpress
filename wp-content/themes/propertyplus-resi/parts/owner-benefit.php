<?php
/**
 * TypeSite 1 - "Loi ich cua chu nha". Port 1:1 tu owner-benefit.component.html (PPlusAngularResi) -
 * 100% noi dung tinh, khong goi API nao (component that KHONG co field API, chi
 * "GlobalConstants.ListLanguageChange" cho nut doi ngon ngu - xem inc/owner-route.php).
 */
if (!defined('ABSPATH')) exit;

$img = get_template_directory_uri() . '/assets/Content/ImgOwer/';
?>
<div class="service-1-banner">
    <img class="img-responsive" src="<?php echo esc_url($img . 'Thinking_Monochromatic 1.png'); ?>" alt="" loading="lazy" />
    <div class="service-1-banner-c">
        <h1><?php echo esc_html(pplus_localize('Bạn đang băn khoăn')); ?>?</h1>
        <h3><?php echo esc_html(pplus_localize('Property Plus sẽ làm được gì cho bạn?')); ?></h3>
        <hr />
        <p>
            <?php echo esc_html(pplus_localize('Với chính sách thuê và quản lý đa đạng nhằm tối ưu hóa thời gian cho thuê và lợi nhuận đem đến cho chủ nhà, Property Plus tự tin giải quyết mọi trở ngại của các chủ nhà cá nhân bận rộn.')); ?>
        </p>
    </div>
</div>

<div class="service-1-body-1 row">
    <div class="col-md-6">
        <div class="header-title">
            <h3><?php echo esc_html(pplus_localize('Làm sao để đảm bảo chất lượng căn hộ sau thời gian thuê?')); ?></h3>
        </div>
        <hr class="hr-cus" />
        <p>
            <?php echo esc_html(pplus_localize('Ngay khi nhận bàn giao căn hộ, Property Plus tiến hành xử lý căn hộ chuẩn kỹ thuật đầu vào từ hệ thống điện, nước, điều hòa, máy móc trang thiết bị đến tường sàn... Hơn nữa, lịch bảo dưỡng định kì giúp duy trì chất lượng căn hộ, tránh tình trạng xuống cấp, hỏng hóc sau thời gian thuê.')); ?>
            <br />
            <?php echo esc_html(pplus_localize('Cùng với đó, căn hộ của bạn sẽ luôn được vệ sinh hàng tuần bởi đội ngũ dọn phòng tiêu chuẩn 05 sao.')); ?>
            <br />
            <?php echo esc_html(pplus_localize('Sử dụng dịch vụ của Property Plus, bạn sẽ hoàn toàn yên tâm rằng căn hộ của mình luôn ở trình trạng tốt nhất.')); ?>
        </p>
    </div>
    <div class="col-md-6">
        <img class="img-responsive" src="<?php echo esc_url($img . 'undraw_people_search_wctu 1.png'); ?>" alt="" loading="lazy" />
    </div>
</div>

<div class="service-1-body-1 row box-custom">
    <div class="col-md-6">
        <img class="img-responsive" src="<?php echo esc_url($img . 'Paypal_Monochromatic 1.png'); ?>" alt="" loading="lazy" />
    </div>
    <div class="col-md-6">
        <div class="header-title">
            <h3><?php echo esc_html(pplus_localize('Giá cho thuê bao nhiêu là hợp lý?')); ?></h3>
        </div>
        <hr class="hr-cus" />
        <p>
            <?php echo esc_html(pplus_localize('Dựa trên việc phân tích vị trí dự án, nội thất và loại hình căn hộ, Property Plus sẽ tư vấn cho chủ nhà mức giá thuê tốt nhất phù hợp với ngân sách khách hàng.')); ?>
        </p>
    </div>
</div>

<div class="service-1-body-1 row">
    <div class="col-md-6">
        <div class="header-title">
            <h3><?php echo esc_html(pplus_localize('Các thủ tục hành chính, thuế khi cho thuê?')); ?></h3>
        </div>
        <hr class="hr-cus" />
        <p><?php echo esc_html(pplus_localize('Các vấn đề hành chính sẽ không còn là mối bận tâm, vì Property Plus sẽ hỗ trợ')); ?>:</p>
        <p>- <?php echo esc_html(pplus_localize('Đăng ký thủ tục làm đăng ký kinh doanh, kê khai và nộp thuế')); ?></p>
        <p>- <?php echo esc_html(pplus_localize('Thanh toán đúng hạn tiền thuê nhà và mọi chi phí phát sinh với ban quản lý tòa nhà và nhà cung cấp')); ?></p>
        <p>- <?php echo esc_html(pplus_localize('Làm việc với cơ quan chức năng để hoàn thiện các thủ tục nhập cảnh, đăng kí tạm trú')); ?></p>
    </div>
    <div class="col-md-6">
        <img class="img-responsive" src="<?php echo esc_url($img . 'undraw_wash_hands_nwl2 1.png'); ?>" alt="" loading="lazy" />
    </div>
</div>

<div class="service-1-body-1 row box-custom">
    <div class="col-md-6">
        <img class="img-responsive" src="<?php echo esc_url($img . 'undraw_in_sync_xwsa 1.png'); ?>" alt="" loading="lazy" />
    </div>
    <div class="col-md-6">
        <div class="header-title">
            <h3><?php echo esc_html(pplus_localize('Làm sao để khách gia hạn hợp đồng?')); ?></h3>
        </div>
        <hr class="hr-cus" />
        <p>
            <?php echo esc_html(pplus_localize('Với việc cung cấp giải pháp toàn diện phục vụ nhu cầu khách hàng, chúng tôi tự tin khả năng gia hạn hợp đồng của khách hàng là cao nhất, giúp ổn định thời gian thuê với chủ nhà.')); ?>
        </p>
    </div>
</div>
