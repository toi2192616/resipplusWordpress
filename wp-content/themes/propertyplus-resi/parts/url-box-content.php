<?php
/**
 * Breadcrumb - port DUNG tu search-base.component.html dong 36-56 (khoi
 * *ngIf="isMapshow===false && isShowUrlMenu==true"). Dung chung cho moi trang B2-B6 (va co the ca
 * cac trang khac sau nay) de tranh lap code 5 lan.
 *
 * SUA 17/9: doc lai DUNG DONG (36-56) ban goc that xac nhan khoi nay CHI CO breadcrumb
 * (url-content-list), KHONG CO nut "Tìm kiếm văn phòng"/panel "Bo loc" nao ca - nut do (da xoa,
 * xem lich su git file nay) la bia them nham (co le chep tu ban Van phong, noi thuc su co 1 nut
 * tuong tu), gay ra bug thuc te nguoi dung bao cao khi doi chieu truc tiep voi
 * propertyplus.com.vn/en/about-us: trang goc KHONG co popup tim kiem nao, chi co hang goi y nhanh
 * (suggest-header, tu app-search-base) voi 1 icon kinh lup nho o cuoi hang - KHAC HAN panel
 * "Loại văn phòng/Hạng tòa nhà" da bi gan nham vao day.
 *
 * @param array $args['breadcrumb'] Danh sach {name, url?} - item cuoi (khong url) la trang hien tai.
 *
 * SUA 17/9 (lan 2): CSS that (".url-content .url-content-list a, .url-content .url-content-list
 * span" trong header-component.css) chi style THE <a> HOAC <span> - muc breadcrumb CUOI (trang
 * hien tai, khong url) truoc day xuat text TRAN khong boc the nao, nen KHONG nhan duoc font-size
 * 13px/mau xam nhu cac muc khac, hien to/den bat thuong (bug thuc te nguoi dung bao cao "chữ
 * abouus to quá"). Boc lai bang <span> (thay vi <a>, vi day KHONG phai link that toi chinh no).
 */
if (!defined('ABSPATH')) exit;

$breadcrumb  = $args['breadcrumb'] ?? [];
?>
<div class="container">
    <div class="url-box-content">
        <div class="container">
            <div class="pull-left">
                <div class="col-md-12 text-center mb-20 header-right">
                    <div class="url-content">
                        <div class="url-content-list">
                            <?php foreach ($breadcrumb as $i => $item) :
                                $icon = ($i > 0) ? pplus_breadcrumb_arrow() : '';
                            ?>
                                <?php if (!empty($item['url'])) : ?>
                                    <a href="<?php echo esc_url($item['url']); ?>"><?php echo $icon; ?><?php echo esc_html($item['name']); ?></a>
                                <?php else : ?>
                                    <span><?php echo $icon; ?><?php echo esc_html($item['name']); ?></span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
