<?php
/**
 * TypeSite 4 - "Đăng ký dịch vụ". Port tu register-service.component.html/ts (PPlusAngularResi) -
 * phan TUONG TAC DUY NHAT trong 4 TypeSite: bang gia/tuy chon dich vu (KHONG PHAI 1 bo may tinh
 * gia truc tiep - xac nhan qua doc day du template that: khong co tong tien nao duoc hien/tinh o
 * day, chi la 1 form khao sat co cau truc, dong "Vui lòng liên hệ nhân viên..." ngay duoi bang xac
 * nhan dieu nay) + thong tin can ho + 2 bo dem (+/-) + modal xac nhan lai truoc khi gui that.
 *
 * Cac dong ID (NameProject/NameBuilding/Area/NumberFloor/NumberApartment/Bedroom/People/
 * OwnerName/OwnerEmail/OwnerMobile/DongYDieuKhoan) giu NGUYEN theo dung ten field that
 * (DataInput.*) - assets/js/main.js doc truc tiep theo cac id nay, KHONG di qua object JS trung
 * gian nao khac.
 *
 * Bo dem "Số phòng ngủ"/"Số người ở" dung lai thu vien CO SAN nhung CHUA TUNG duoc goi o dau trong
 * theme nay (assets/officebase/lib/bootstrap-input-spinner.js, xem plan) - chi can render dung
 * input[type=number].numberspin, JS enqueue rieng cho trang nay se tu bien thanh widget +/-.
 */
if (!defined('ABSPATH')) exit;

$service_model = pplus_get_service_regist();
$service_price = $service_model['servicePrice'] ?? [];
?>
<div class="row service-regis">

    <div class="col-md-12 service-regis-title">
        <h3 class="discover-title">
            <?php echo esc_html(pplus_localize('Bạn muốn sử dụng dịch vụ của Property Plus cho căn hộ hiện tại của mình?')); ?>
            <br />
            <?php echo esc_html(pplus_localize('Chúng tôi rất vui khi được phục vụ bạn đến từng chi tiết!')); ?>
        </h3>
    </div>

    <hr class="service-hr-cus2" />

    <div class="col-md-12" id="service-TinhGiaTri">
        <?php if (!empty($service_price)) : ?>
            <table>
                <thead>
                    <tr>
                        <td><?php echo esc_html(pplus_localize('Tiện ích')); ?></td>
                        <td width="250"><?php echo esc_html(pplus_localize('Loại')); ?></td>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($service_price as $item) :
                        $type = (int) ($item['type'] ?? 0); ?>
                        <tr class="text-in-row-2 price-all-price" data-name="<?php echo esc_attr($item['name'] ?? ''); ?>" data-type="<?php echo (int) $type; ?>">
                            <td><label class="service-label"><?php echo esc_html($item['name'] ?? ''); ?></label></td>

                            <?php if ($type === 1) : ?>
                                <td class="table-row-2">
                                    <input class="serice-input form-control priceInput" type="number" value="<?php echo esc_attr($item['value'] ?? ''); ?>" />
                                </td>
                            <?php elseif ($type === 2) : ?>
                                <td class="table-row-2">
                                    <select class="serice-input form-control priceInput">
                                        <option value="1" <?php selected((string) ($item['value'] ?? ''), '1'); ?>><?php echo esc_html(pplus_localize('Có')); ?></option>
                                        <option value="0" <?php selected((string) ($item['value'] ?? ''), '0'); ?>><?php echo esc_html(pplus_localize('Không')); ?></option>
                                    </select>
                                </td>
                            <?php elseif ($type === 3) : ?>
                                <td class="table-row-2">
                                    <select class="serice-input form-control priceInput">
                                        <?php foreach (($item['listSelect'] ?? []) as $option) : ?>
                                            <option value="<?php echo esc_attr($option['nameC'] ?? ''); ?>" data-price="<?php echo esc_attr($option['priceC'] ?? ''); ?>">
                                                <?php echo esc_html($option['nameC'] ?? ''); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            <?php elseif ($type === 4) : ?>
                                <td class="table-row-2">
                                    <input type="text" class="serice-input form-control priceInput" value="<?php echo esc_attr($item['value'] ?? ''); ?>" />
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="text-in-row-2 price-all-price">
                        <td class="border-none" colspan="2"><?php echo esc_html(pplus_localize('Vui lòng liên hệ nhân viên PropertyPlus để được tư vấn')); ?></td>
                    </tr>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="col-md-12 service-regis-title">
        <h3 class="discover-title"><?php echo esc_html(pplus_localize('Hãy cho chúng tôi biết thông tin căn hộ của bạn')); ?></h3>
    </div>

    <hr class="service-hr-cus2" />

    <div class="col-md-12 form-group text-in-row-cus">
        <label><?php echo esc_html(pplus_localize('Tên dự án')); ?></label>
        <input type="text" id="NameProject" name="NameProject" class="form-control" />
    </div>
    <div class="col-md-12 form-group text-in-row-cus">
        <label><?php echo esc_html(pplus_localize('Tên tòa nhà')); ?></label>
        <input type="text" id="NameBuilding" name="NameBuilding" class="form-control" />
    </div>

    <div class="col-md-4 form-group text-in-row-cus">
        <label><?php echo esc_html(pplus_localize('Diện tích')); ?></label>
        <div class="input-group-pp">
            <input class="form-control text-right" type="number" name="Area" id="Area" />
            <div class="input-group-append"><span class="input-group-text">m<sup>2</sup></span></div>
        </div>
    </div>
    <div class="col-md-4 form-group text-in-row-cus">
        <label><?php echo esc_html(pplus_localize('Tầng')); ?></label>
        <input type="text" id="NumberFloor" name="NumberFloor" class="form-control" />
    </div>
    <div class="col-md-4 form-group text-in-row-cus">
        <label><?php echo esc_html(pplus_localize('Số phòng')); ?></label>
        <input type="text" id="NumberApartment" name="NumberApartment" class="form-control" />
    </div>

    <div class="row form-group text-in-row-cus-number">
        <div class="col-md-4 form-group text-in-row">
            <label><?php echo esc_html(pplus_localize('Số phòng ngủ')); ?></label>
            <input class="form-control numberspin" name="Bedroom" id="Bedroom" type="number" min="0" value="1" />
        </div>
        <div class="col-md-4 form-group text-in-row">
            <label><?php echo esc_html(pplus_localize('Số người ở')); ?></label>
            <input class="form-control numberspin" id="People" name="People" type="number" min="0" value="1" />
        </div>
    </div>

    <div class="col-md-12 form-group text-in-row-3">
        <p class="note-row-3"><?php echo esc_html(pplus_localize('Khách hàng có thể tăng hoặc giảm các dịch vụ theo yêu cầu, công cụ chỉ mang tính chất tham khảo xin vui lòng liên hệ chúng tôi để biết thêm chi tiết.')); ?></p>
    </div>

    <div class="col-md-12 service-regis-title-c1">
        <button class="btn btn-primary btn-send-service" type="button" data-bs-toggle="modal" data-bs-target="#modal-service-register" id="pplus-open-service-register-modal">
            <?php echo esc_html(pplus_localize('Đăng ký dịch vụ')); ?>
        </button>
    </div>
</div>

<?php // Modal xac nhan lai (thay the ng-template #registermodal cua Angular, mo qua NgbModal voi
// {size:'lg', windowClass:'modal-register registSeviceModal'}) - NgbModal TU BOC noi dung
// <ng-template> (chi bat dau tu ".modal-body", xem dong 143-144 that) vao bo khung Bootstrap
// chuan ".modal > .modal-dialog > .modal-content" (CSS trang/border-radius/box-shadow/can giua
// deu lay tu chinh Bootstrap, khong phai tu Angular component). Ban SUA 30/9 nay doi tu he thong
// modal rieng cua theme ([data-pplus-modal]/.modal-container-ppus) sang dung THANG Bootstrap that
// (data-bs-toggle/data-bs-dismiss) - he thong rieng do CHI thiet ke cho hop nho (goi dien/chat o
// footer), ".modal-background .modal" cua no KHONG co background/be rong nao ca (tin mau trang lay
// tu 1 class con RIENG cua tung modal, vd "#modal-container4 .from-send-mail-footer") - modal nay
// KHONG co lop do nen tu render TRONG SUOT, de lo noi dung trang phia sau xuyen qua (bug thuc te
// nguoi dung bao cao, doi chieu voi anh chup ban goc). ?>
<div class="modal fade modal-register registSeviceModal" id="modal-service-register" tabindex="-1" aria-labelledby="registSeviceModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="position:absolute; top:15px; right:15px; z-index:1;"></button>
            <div class="modal-body" id="registSeviceModalBody">
                <div class="row service-modal">
                    <div class="col-md-7">
                        <div class="text-left">
                            <h4 class="pp-title f2o5rem font-bold margin-bottom-10"><?php echo esc_html(pplus_localize('Kiểm tra lại lựa chọn')); ?></h4>
                            <div class="pp-color margin-bottom-20"><?php echo esc_html(pplus_localize('Giúp bạn có một sự khởi đầu thuận lợi trong khi đăng ký dịch vụ của Property Plus')); ?></div>
                        </div>
                        <div class="row margin-bottom-20">
                            <div class="col-md-12">
                                <div class="service-modal-box">1</div>
                                <div class="tieuDeKiemTra"><?php echo esc_html(pplus_localize('Dịch vụ đã chọn')); ?></div>
                            </div>
                        </div>
                        <div class="row service-modal-chose" id="pplus-service-modal-chose"></div>

                        <div class="row margin-bottom-20">
                            <div class="col-md-12">
                                <div class="service-modal-box">2</div>
                                <div class="tieuDeKiemTra"><?php echo esc_html(pplus_localize('Thông tin căn hộ')); ?></div>
                            </div>
                        </div>
                        <div class="row ttin">
                            <div class="col-md-4">
                                <div class="_label"><?php echo esc_html(pplus_localize('Tên dự án')); ?></div>
                                <div class="number-value mt-5" id="modal-echo-ProjectName"></div>
                            </div>
                            <div class="col-md-4">
                                <div class="_label"><?php echo esc_html(pplus_localize('Tên toà nhà')); ?></div>
                                <div class="number-value mt-5" id="modal-echo-BuildingName"></div>
                            </div>
                            <div class="col-md-2">
                                <div class="_label"><?php echo esc_html(pplus_localize('Tầng')); ?></div>
                                <div class="number-value mt-5" id="modal-echo-FloorNo"></div>
                            </div>
                            <div class="col-md-2">
                                <div class="_label"><?php echo esc_html(pplus_localize('Số phòng')); ?></div>
                                <div class="number-value mt-5" id="modal-echo-RoomNo"></div>
                            </div>
                        </div>

                        <hr class="w50o" />

                        <div class="row ttin">
                            <div class="col-md-4"><div class="_label"><?php echo esc_html(pplus_localize('Số phòng ngủ')); ?></div></div>
                            <div class="col-md-8"><div class="number-value" id="modal-echo-BedroomQty"></div></div>
                        </div>
                        <div class="row ttin">
                            <div class="col-md-4"><div class="_label"><?php echo esc_html(pplus_localize('Số người ở')); ?></div></div>
                            <div class="col-md-8"><div class="number-value" id="modal-echo-PersonQty"></div></div>
                        </div>
                    </div>

                    <div class="col-md-5 thongtin-dangky">
                        <h4 class="pp-title w70o m-auto margin-bottom-10 text-center"><?php echo esc_html(pplus_localize('Nhập thông tin liên hệ của bạn')); ?></h4>
                        <div class="pp-color text-center"><?php echo esc_html(pplus_localize('Chuyên viên sẽ liên hệ ngay với bạn khi nhận thông tin')); ?></div>

                        <div class="m-auto margin-top-30">
                            <form id="ServiceRegisterForm" novalidate class="form-validation form-group-lg" spellcheck="false" onsubmit="return false;">
                                <div class="form-group">
                                    <label><?php echo esc_html(pplus_localize('Họ và tên')); ?></label>
                                    <input class="form-control" type="text" name="OwnerName" id="modal-OwnerName" placeholder="<?php echo esc_attr(pplus_localize('Họ và tên')); ?>" />
                                </div>
                                <div class="form-group">
                                    <label><?php echo esc_html(pplus_localize('Địa chỉ email')); ?></label>
                                    <input class="form-control" type="text" name="OwnerEmail" id="modal-OwnerEmail" placeholder="<?php echo esc_attr(pplus_localize('Địa chỉ email')); ?>" />
                                </div>
                                <div class="form-group">
                                    <label><?php echo esc_html(pplus_localize('Số điện thoại')); ?></label>
                                    <input class="form-control" type="text" name="OwnerMobile" id="modal-OwnerMobile" placeholder="<?php echo esc_attr(pplus_localize('Số điện thoại')); ?>" />
                                </div>
                                <div class="font-size16 margin-top-15 margin-bottom-10">
                                    <input type="checkbox" name="DongYDieuKhoan" id="modal-DongYDieuKhoan" />&nbsp;
                                    <label for="modal-DongYDieuKhoan"><?php echo esc_html(pplus_localize('Tôi đồng ý với điều khoản và dịch vụ của Property Plus')); ?></label>
                                </div>
                                <div class="text-center">
                                    <button class="btn btn-primary" id="HoanTatDangKyDichVu" type="button">
                                        <?php echo esc_html(pplus_localize('HOÀN TẤT ĐĂNG KÝ SỬ DỤNG DỊCH VỤ')); ?>
                                    </button>
                                    <div class="hotline">Hotline: 08 5666 2566</div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
