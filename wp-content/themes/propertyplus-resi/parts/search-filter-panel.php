<?php
/**
 * Panel "Chi tiết" (".focus-popup > .long-term-s3": loai nha/phong, khoang gia, phong ngu/tam, dien tich, tang, bon tam, "Xóa bộ lọc") - DUNG CHUNG boi popup
 * tim kiem (parts/search-modal.php) va o tim kiem trang chu (front-page.php). Port day du tu search-base.component.html dong 341-469; xem docblock
 * dau parts/search-modal.php. Cac "id" (pplus-price-from, pplus-bedroom-qty...) la khoa tra cuu cua assets/js/pplus-search-modal.js - moi trang
 * CHI duoc co 1 panel nay (trang chu khong in #searchModal o footer).
 */
if (!defined('ABSPATH')) exit;

$bedbath_values = range(1, 8); // listValue = [1..8] (search-base.component.ts dong 63)
?>
<div class="focus-popup">
    <div class="long-term-s3">
        <div class="long-term-s3-item">
            <div class="long-term-s3-t1"><?php echo esc_html(pplus_localize('Loại nhà/ phòng')); ?></div>
            <div class="checkbox-term">
                <label><input type="checkbox" name="apartment_type" value="1" class="pplus-apartment-type-check" /> <?php echo esc_html(pplus_localize('Căn hộ dịch vụ')); ?></label>
                <label><input type="checkbox" name="apartment_type" value="2" class="pplus-apartment-type-check" /> <?php echo esc_html(pplus_localize('Chung cư')); ?></label>
            </div>
        </div>
        <div class="long-term-s3-item pt-0">
            <div class="choose-price">
                <div class="group-price-ranger-item">
                    <label class="ranger-label group-price-ranger-item-t1"><?php echo esc_html(pplus_localize('Tối thiểu')); ?></label>
                    <input class="ranger-input group-price-ranger-item-c1" type="text" inputmode="numeric" name="price_from" id="pplus-price-from" min="0" max="15000" placeholder="<?php echo esc_attr(pplus_localize('Nhập số')); ?> ..." />
                    <span class="ranger-currency">$</span>
                </div>
                <div class="group-price-ranger-item group-price-ranger-item2">
                    <label class="ranger-label group-price-ranger-item-t1"><?php echo esc_html(pplus_localize('Tối đa')); ?></label>
                    <input class="ranger-input group-price-ranger-item-c1" type="text" inputmode="numeric" name="price_to" id="pplus-price-to" min="0" max="15000" placeholder="<?php echo esc_attr(pplus_localize('Nhập số')); ?> ..." />
                    <span class="ranger-currency">$</span>
                </div>
            </div>
        </div>
        <div class="long-term-s3-item">
            <div class="long-term-s3-t1"><?php echo esc_html(pplus_localize('Chi tiết phòng')); ?></div>
            <div class="row">
                <div class="col-sm-12 list-value-customer">
                    <label><?php echo esc_html(pplus_localize('Phòng ngủ')); ?></label>
                    <input hidden name="bedroom_qty" type="number" id="pplus-bedroom-qty" />
                    <div class="list-value-select" data-input="pplus-bedroom-qty">
                        <div class="list-value-item action value-item-all" data-value=""><?php echo esc_html(pplus_localize('Bất kỳ')); ?></div>
                        <?php foreach ($bedbath_values as $n) : ?>
                            <div class="list-value-item" data-value="<?php echo (int) $n; ?>"><?php echo (int) $n; ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-sm-12 list-value-customer">
                    <label><?php echo esc_html(pplus_localize('Phòng tắm')); ?></label>
                    <input hidden name="bathroom_qty" type="number" id="pplus-bathroom-qty" />
                    <div class="list-value-select" data-input="pplus-bathroom-qty">
                        <div class="list-value-item action value-item-all" data-value=""><?php echo esc_html(pplus_localize('Bất kỳ')); ?></div>
                        <?php foreach ($bedbath_values as $n) : ?>
                            <div class="list-value-item" data-value="<?php echo (int) $n; ?>"><?php echo (int) $n; ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="row list-chose-content">
                <div class="col-sm-6 pl-0">
                    <label><?php echo esc_html(pplus_localize('Diện tích')); ?></label>
                    <div class="cus-type-1">
                        <span><?php echo esc_html(pplus_localize('từ')); ?></span>
                        <input type="number" name="area_from" id="pplus-area-from" /><text>m</text> -
                        <input type="number" name="area_to" id="pplus-area-to" /><text>m</text>
                    </div>
                </div>
                <div class="col-sm-6">
                    <label><?php echo esc_html(pplus_localize('Tầng số')); ?></label>
                    <div class="cus-type-2">
                        <span><?php echo esc_html(pplus_localize('từ')); ?></span>
                        <input type="number" name="floor_from" id="pplus-floor-from" /> -
                        <input type="number" name="floor_to" id="pplus-floor-to" />
                    </div>
                </div>
            </div>
            <div class="checkbox-term">
                <label><input type="checkbox" name="has_bathtub" id="pplus-has-bathtub" value="true" /> <?php echo esc_html(pplus_localize('Bồn tắm')); ?></label>
            </div>
            <div class="pplus-filter-reset-row">
                <button type="button" class="pplus-reset-filters"><?php echo esc_html(pplus_t('Xóa bộ lọc', 'Clear filters')); ?></button>
            </div>
        </div>
    </div>
</div>
