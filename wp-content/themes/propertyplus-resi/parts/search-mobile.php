<?php
/**
 * Thanh tim kiem MOBILE (".searching-tool") + panel tim kiem MOBILE 2 buoc ("#search-tab-mobile").
 * Port DUNG tu search-base.component.html (Angular that) - dong 521-529 (".searching-tool") va
 * dong 531-793 ("#search-tab-mobile"). Ban that <app-search-base> co mat o GAN NHU MOI trang (ke ca
 * trang chu tren mobile, xem home.component.html dong 1 "*ngIf=isMobile") nen 2 khoi nay dat
 * SITE-WIDE trong footer.php (giong parts/search-modal.php - ban desktop #searchModal, ban that
 * "*ngIf=!isMobile"); CSS tu an tren desktop (".searching-tool": display:none, chi hien duoi 800px;
 * "#search-tab-mobile": display:none, chi hien duoi 760px). Logic JS: assets/js/pplus-search-mobile.js.
 *
 * Hanh vi that (search-base.component.ts):
 * - o nhap ".txt-search" bam -> showSearchBase() (buoc 1); nut "Bo loc" -> showSearchBase(2) (buoc 2);
 *   nut "Tim kiem" o menu chan trang (teamplate-header.component.html dong 290) -> showSearchBase().
 * - "Quay lai": buoc>1 thi lui 1 buoc, buoc 1 thi dong panel. "Tiep theo": buoc+1 (an o buoc 2).
 * - chon 1 goi y quan/huyen (DataCoreroupToBack type 2) -> dien ten vao o, CHUYEN SANG buoc 2.
 * - "Xac nhan" (clickSubmitSearch) -> tim kiem voi cac truong loc (o WP: dieu huong toi URL loc,
 *   dung y het #searchModal desktop, xem pplus-search-modal.js).
 * CHUA lam (ghi ro, giong quy uoc theme): goi y theo TEN DU AN/dia diem Google khi go chu vao o
 * (listSearchGroup/listSearchGroupGoogleMap - can API "Apartment/GetFilterGroupList"), khong co
 * o day; o nhap van go duoc, chi khong co goi y dong.
 */
if (!defined('ABSPATH')) exit;

$pplus_sm_lang_prefix = pplus_lang_prefix();
$pplus_sm_districts   = pplus_get_all_districts();
$pplus_sm_values      = range(1, 8); // listValue = [1..8] (search-base.component.ts dong 63)
?>
<?php // KHONG de khoang trang/xuong dong giua <a>/<input>/<button> (Angular AOT bo khoang trang giua the;
// 3 phan tu la inline-block nam chung 1 hang, khoang trang that se day nut "Bộ lọc" xuong dong - do
// truc tiep, xem ghi chu cu trong page-reference.php). Class "resultOnTop" LUON co san luc dau (tren
// mobile that "fullContent=true" mac dinh - ngOnInit dong 137); page-reference.php go class nay khi mo
// che do ban do (fullContent=false) - xem main.js. ?>
<div class="searching-tool resultOnTop"><a href="<?php echo esc_url(home_url($pplus_sm_lang_prefix . '/')); ?>"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/officebase/Common/Images/logo.png'); ?>" alt="logoPP" width="100" height="94" /></a><input class="txt-search" type="text" aria-label="<?php echo esc_attr(pplus_localize('Tìm kiếm')); ?>" value="" readonly="readonly" data-pplus-search-mobile="1" /><button id="btnSearchMore" type="button" data-pplus-search-mobile="2"><?php echo esc_html(pplus_localize('Bộ lọc')); ?></button></div>

<div class="tab-container search-tab-mobile" step="1" id="search-tab-mobile" data-base-url="<?php echo esc_url(home_url($pplus_sm_lang_prefix . '/' . pplus_apartment_list_root() . '/ha-noi')); ?>">
    <div class="tab-header">
        <button type="button" class="btnBackStep">
            <i class="fa fa-angle-left" aria-hidden="true" title="prev"></i>
            <span><?php echo esc_html(pplus_localize('Quay lại')); ?></span>
        </button>
        <button type="button" class="btnNextStep">
            <span><?php echo esc_html(pplus_localize('Tiếp theo')); ?></span>
            <i class="fa fa-angle-right" aria-hidden="true" title="next"></i>
        </button>
    </div>

    <div class="tab-content-search">
        <div class="m-step">
            <div class="search-home-mobile">
                <input class="search-mobile txt-filter" id="pplus-m-filter" placeholder="<?php echo esc_attr(pplus_localize('Bạn muốn đi đâu')); ?>" autocomplete="off" />
                <button class="btn btn-primary btn-search-notext pull-right hidden" aria-label="Search" type="button">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M15.2124 14.5735L11.4044 10.6129C12.3835 9.44901 12.92 7.98455 12.92 6.45999C12.92 2.89801 10.022 0 6.45999 0C2.89801 0 0 2.89801 0 6.45999C0 10.022 2.89801 12.92 6.45999 12.92C7.79721 12.92 9.07152 12.5167 10.161 11.751L13.998 15.7416C14.1583 15.9082 14.374 16 14.6052 16C14.824 16 15.0316 15.9166 15.1891 15.7649C15.5239 15.4428 15.5346 14.9085 15.2124 14.5735ZM6.45999 1.68522C9.09286 1.68522 11.2348 3.82713 11.2348 6.45999C11.2348 9.09286 9.09286 11.2348 6.45999 11.2348C3.82713 11.2348 1.68522 9.09286 1.68522 6.45999C1.68522 3.82713 3.82713 1.68522 6.45999 1.68522Z" fill="#A4A4A4" />
                    </svg>
                </button>
                <button class="btn btn-primary btn-delete-text pull-right hidden" type="button" aria-label="Clear">
                    <i class="fa fa-times" aria-hidden="true"></i>
                </button>
            </div>
            <div class="pplus-show-district">
                <div class="suggestDistrict">
                    <div class="textHeader"><?php echo esc_html(pplus_localize('Hoặc chọn theo gợi ý')); ?></div>
                    <div class="list-suggest" id="pplus-m-suggest-list">
                        <?php foreach ($pplus_sm_districts as $pplus_sm_item) :
                            $pplus_sm_alias = trim((string) ($pplus_sm_item['alias'] ?? ''));
                            if ($pplus_sm_alias === '') continue;
                            $pplus_sm_label = trim((string) ($pplus_sm_item['subTitle'] ?? ''));
                        ?>
                            <div class="item-content-district-data item-suggest" role="button" tabindex="0" data-alias="<?php echo esc_attr($pplus_sm_alias); ?>" data-label="<?php echo esc_attr($pplus_sm_label); ?>">
                                <figure class="icon-suggest">
                                    <img title="ListProject" src="<?php echo esc_url(pplus_admin_asset_url($pplus_sm_item['img'] ?? '')); ?>" alt="<?php echo esc_attr($pplus_sm_label); ?>" onerror="this.src='<?php echo esc_url(get_template_directory_uri() . '/assets/officebase/Common/Images/logo.png'); ?>'" loading="lazy" />
                                </figure>
                                <div class="content-suggest">
                                    <p class="content-suggest-text1"><?php echo esc_html($pplus_sm_label); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="m-step-content" style="display:none;">
            <div class="search-option">
                <div class="long-term-s3">
                    <div class="long-term-s3-item">
                        <div class="long-term-s3-t1"><?php echo esc_html(pplus_localize('Loại nhà/ phòng')); ?></div>
                        <div class="checkbox-term">
                            <label>
                                <?php echo esc_html(pplus_localize('Căn hộ dịch vụ')); ?>
                                <input name="pplus_m_apartment_type" type="checkbox" value="1" class="pplus-m-apartment-type" />
                            </label>
                            <label>
                                <?php echo esc_html(pplus_localize('Chung cư')); ?>
                                <input name="pplus_m_apartment_type" type="checkbox" value="2" class="pplus-m-apartment-type" />
                            </label>
                        </div>
                    </div>
                    <div class="long-term-s3-item pt-0">
                        <div class="choose-price">
                            <div>
                                <div class="group-price-ranger-item">
                                    <label class="ranger-label group-price-ranger-item-t1" for="pplus-m-price-from"><?php echo esc_html(pplus_localize('Giá tối thiểu')); ?></label>
                                    <input class="ranger-input group-price-ranger-item-c1 min" name="price_from" id="pplus-m-price-from" type="text" inputmode="numeric" min="0" max="15000" placeholder="<?php echo esc_attr(pplus_localize('Nhập số')); ?> ..." />
                                    <span class="ranger-currency">$</span>
                                </div>
                                <div class="group-price-ranger-item group-price-ranger-item2">
                                    <label class="ranger-label group-price-ranger-item-t1" for="pplus-m-price-to"><?php echo esc_html(pplus_localize('Giá tối đa')); ?></label>
                                    <input class="ranger-input group-price-ranger-item-c1 max" name="price_to" id="pplus-m-price-to" type="text" inputmode="numeric" min="0" max="15000" placeholder="<?php echo esc_attr(pplus_localize('Nhập số')); ?> ..." />
                                    <span class="ranger-currency">$</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="long-term-s3-item">
                        <div class="long-term-s3-t1"><?php echo esc_html(pplus_localize('Chi tiết phòng')); ?></div>
                        <div class="row">
                            <div class="col-sm-12 list-value-customer">
                                <label><?php echo esc_html(pplus_localize('Phòng ngủ')); ?></label>
                                <input hidden name="bedroom_qty" type="number" id="pplus-m-bedroom-qty" />
                                <div class="rs-mobile-list-value">
                                    <div class="list-value-select list-value-select-bedroom" data-input="pplus-m-bedroom-qty">
                                        <div class="list-value-item value-item-all action" data-value=""><?php echo esc_html(pplus_localize('Bất kỳ')); ?></div>
                                        <?php foreach ($pplus_sm_values as $pplus_sm_n) : ?>
                                            <div class="list-value-item" data-value="<?php echo (int) $pplus_sm_n; ?>"><?php echo (int) $pplus_sm_n; ?></div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-12 list-value-customer">
                                <label><?php echo esc_html(pplus_localize('Phòng tắm')); ?></label>
                                <input hidden type="number" name="bathroom_qty" id="pplus-m-bathroom-qty" aria-label="Search" />
                                <div class="rs-mobile-list-value">
                                    <div class="list-value-select list-value-select-bathroom" data-input="pplus-m-bathroom-qty">
                                        <div class="list-value-item value-item-all action" data-value=""><?php echo esc_html(pplus_localize('Bất kỳ')); ?></div>
                                        <?php foreach ($pplus_sm_values as $pplus_sm_n) : ?>
                                            <div class="list-value-item" data-value="<?php echo (int) $pplus_sm_n; ?>"><?php echo (int) $pplus_sm_n; ?></div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="flex-box-between">
                                <label><?php echo esc_html(pplus_localize('Diện tích')); ?></label>
                                <div class="cus-type-1">
                                    <span><?php echo esc_html(pplus_localize('từ')); ?></span>
                                    <input type="number" name="area_from" id="pplus-m-area-from" aria-label="Search" /><text>m</text> - <input type="number" name="area_to" id="pplus-m-area-to" aria-label="Search" /><text>m</text>
                                </div>
                            </div>
                            <div class="flex-box-between">
                                <label><?php echo esc_html(pplus_localize('Tầng số')); ?></label>
                                <div class="cus-type-2">
                                    <span><?php echo esc_html(pplus_localize('từ')); ?></span>
                                    <input type="number" name="floor_from" id="pplus-m-floor-from" aria-label="Search" /> - <input type="number" name="floor_to" id="pplus-m-floor-to" aria-label="Search" />
                                </div>
                            </div>
                        </div>
                        <div class="checkbox-term">
                            <label>
                                <?php echo esc_html(pplus_localize('Bồn tắm')); ?>
                                <input type="checkbox" name="has_bathtub" id="pplus-m-has-bathtub" value="true" />
                            </label>
                        </div>
                    </div>
                </div>
                <div class="accept-box">
                    <button class="btn btn-primary btnSearchMenu w100o" type="button"><?php echo esc_html(pplus_localize('Xác nhận')); ?></button>
                </div>
            </div>
        </div>
    </div>
</div>
