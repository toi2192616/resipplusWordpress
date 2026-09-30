<?php
/**
 * TypeSite 2 - "Đăng ký cho thuê nhà". Port tu register-leasing.component.html/.ts
 * (PPlusAngularResi) - form nhieu truong + tai anh (base64) + modal xac nhan lai truoc khi gui
 * that, dung THANG Bootstrap modal that (data-bs-toggle/data-bs-dismiss, y het parts/service-register.php
 * sau ban sua 30/9 - KHONG dung he thong [data-pplus-modal] rieng cua theme nua, xem ghi chu tai
 * modal ".modal-owner-register" ben duoi) va REST route rieng (inc/owner-register-route.php -
 * endpoint .NET KHAC voi TypeSite 4, xem ghi chu file do).
 *
 * Cac id field (NameProject/NameBuilding/NumberFloor/NumberApartment/Bedroom/WCroom/Area/
 * TotalPrice/TotalPriceUnit/IsNegotiatePrice/Remarks) giu NGUYEN theo dung ten field that
 * (Model.*) - assets/js/main.js doc truc tiep theo cac id nay.
 *
 * Bo dem "Số phòng ngủ"/"Số phòng tắm" dung lai bootstrap-input-spinner.js (giong TypeSite 4).
 *
 * "UtilityString" (hidden input, dong 7 ban goc) - CO trong markup that nhung KHONG co
 * [(ngModel)] nao ca (khong bind toi dau, luon rong) - port GIU NGUYEN cho dung markup nhung
 * KHONG gui len payload (khong co gia tri that de gui).
 */
if (!defined('ABSPATH')) exit;
?>
<div class="owner-regist">
    <div class="owner-regist-content">
        <form id="frm-owner-regis">
            <input name="UtilityString" hidden />

            <div class="header-title">
                <h3><?php echo esc_html(pplus_localize('Đăng cho thuê nhanh với với những thông tin ngắn gọn tại đây.')); ?></h3>
            </div>
            <hr />

            <div class="row radius-area">
                <div class="col-md-12 form-group">
                    <label><?php echo esc_html(pplus_localize('Tên dự án')); ?></label>
                    <input type="text" id="NameProject" name="NameProject" class="form-control" />
                </div>
            </div>
            <div class="row radius-area">
                <div class="col-md-6 form-group">
                    <label><?php echo esc_html(pplus_localize('Tên tòa nhà')); ?></label>
                    <input type="text" id="NameBuilding" name="NameBuilding" class="form-control" />
                </div>
                <div class="col-md-3 form-group">
                    <label><?php echo esc_html(pplus_localize('Tầng')); ?></label>
                    <input type="text" id="NumberFloor" name="NumberFloor" class="form-control" />
                </div>
                <div class="col-md-3 form-group">
                    <label><?php echo esc_html(pplus_localize('Số phòng')); ?></label>
                    <input type="text" id="NumberApartment" name="NumberApartment" class="form-control" />
                </div>
            </div>

            <div class="header-title">
                <h3><?php echo esc_html(pplus_localize('Căn hộ của bạn thế nào?')); ?></h3>
            </div>
            <hr />
            <div class="row">
                <div class="col-md-5">
                    <div class="form-group">
                        <label><?php echo esc_html(pplus_localize('Số phòng ngủ')); ?></label>
                        <input type="number" class="form-control numberspin" name="Bedroom" id="Bedroom" min="0" value="1" />
                    </div>
                    <div class="form-group">
                        <label><?php echo esc_html(pplus_localize('Số phòng tắm')); ?></label>
                        <input type="number" class="form-control numberspin" name="WCroom" id="WCroom" min="0" value="1" />
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="form-group">
                        <label><?php echo esc_html(pplus_localize('Diện tích')); ?></label>
                        <div class="input-group-pp">
                            <input class="form-control text-right" name="Area" id="Area" type="text" />
                            <div class="input-group-append"><span class="input-group-text">m<sup>2</sup></span></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?php echo esc_html(pplus_localize('Nội thất')); ?></label>
                        <div class="radio-group-pp">
                            <label class="opacity-1">
                                <input type="radio" name="NTT" value="<?php echo esc_attr(pplus_localize('Cơ bản')); ?>" checked />
                                <span class="label-cus"><?php echo esc_html(pplus_localize('Cơ bản')); ?></span>
                            </label>
                            <label class="opacity-1">
                                <input type="radio" name="NTT" value="<?php echo esc_attr(pplus_localize('Đầy đủ')); ?>" />
                                <span class="label-cus"><?php echo esc_html(pplus_localize('Đầy đủ')); ?></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 col-label">
                    <label class="label-cus"><?php echo esc_html(pplus_localize('Giá mong muốn cho thuê')); ?></label>
                    <label style="font-weight:normal; font-style: italic; font-size: 13px; color: #9a9a9a; margin-top: 0;">
                        <?php echo esc_html(pplus_localize('Giá không bao gồm phí QL và thuế (nếu có)')); ?>
                    </label>
                </div>
                <div class="col-md-4">
                    <div class="input-group-pp">
                        <input class="form-control text-right" type="text" name="TotalPrice" id="TotalPrice" />
                        <div class="input-group-append" style="min-width: 60px;">
                            <select class="form-control" style="padding: 0px;" name="TotalPriceUnit" id="TotalPriceUnit">
                                <option value="VND">VND</option>
                                <option value="USD">USD</option>
                                <option value="JPY">JPY</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 mt-25 margin-bottom-25">
                    <label>
                        <input type="checkbox" name="IsNegotiatePrice" id="IsNegotiatePrice" /> <span class="label-cus"><?php echo esc_html(pplus_localize('Thương lượng giá')); ?></span>
                    </label>
                </div>
            </div>

            <div class="row header-title">
                <div class="col-md-7">
                    <h3><?php echo esc_html(pplus_localize('Cung cấp hình ảnh căn hộ')); ?></h3>
                    <div class="sub-question"><?php echo esc_html(pplus_localize('Chụp ảnh bằng điện thoại, máy ảnh. Tải lên ít nhất một ảnh để đăng nhà/phòng cho thuê của bạn - bạn luôn có thể thêm hoặc chỉnh sửa sau')); ?></div>
                </div>
            </div>
            <hr />
            <div class="row">
                <div class="col-md-6">
                    <div id="drop_zone_img"></div>
                    <div id="drop_zone">
                        <input class="readURL" type="file" name="file" multiple id="pplus-owner-photo-input" accept="image/*" hidden />
                        <label for="pplus-owner-photo-input" class="btn btn-primary margin-top-15"><?php echo esc_html(pplus_localize('Tải ảnh lên')); ?></label>
                        <div><?php echo esc_html(pplus_localize('Hoặc kéo vào')); ?></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <textarea rows="17" class="form-control border-radius-5" name="Remarks" id="Remarks" placeholder="<?php echo esc_attr(pplus_localize('Mô tả qua không gian căn hộ của bạn ...')); ?>"></textarea>
                </div>
            </div>
        </form>
    </div>
</div>
<div class="row col-md-12">
    <button type="button" style="height:50px;width: auto;" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-owner-register" id="pplus-open-owner-register-modal">
        <?php echo esc_html(pplus_localize('Gửi thông tin')); ?>
    </button>
</div>

<?php // Modal xac nhan lai (thay the <ng-template #registermodal>, mo qua NgbModal ben Angular) -
// gia tri hien thi (id="modal-echo-*") deu RONG luc render PHP - assets/js/main.js tu doc lai form
// dang hien NGAY LUC bam nut "Gửi thông tin" o tren, dung y het co che Angular (modal doc lai chinh
// Model dang co trong bo nho luc mo). SUA 30/9: dung THANG Bootstrap modal that (data-bs-toggle/
// data-bs-dismiss, giong parts/service-register.php) thay vi he thong rieng cua theme
// ([data-pplus-modal]/.modal-container-ppus) - he thong rieng do CHI thiet ke cho hop nho (goi
// dien/chat o footer), khong co background/be rong nao ca nen modal xac nhan lon nay bi render
// TRONG SUOT. ?>
<div class="modal fade modal-register registSeviceModal" id="modal-owner-register" tabindex="-1" aria-labelledby="registSeviceModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="position:absolute; top:15px; right:15px; z-index:1;"></button>
            <div class="modal-body" id="registSeviceModalBody">
                <div class="row service-modal">
                    <div class="col-md-7">
                        <div class="text-left">
                            <h4 class="pp-title f2o5rem font-bold margin-bottom-10"><?php echo esc_html(pplus_localize('Kiểm tra lại lựa chọn')); ?></h4>
                            <div class="pp-color margin-bottom-20"><?php echo esc_html(pplus_localize('Giúp bạn có một sự khởi đầu thuận lợi trong khi đăng ký.')); ?></div>
                        </div>

                        <div class="row margin-bottom-20">
                            <div class="col-md-12">
                                <div class="service-modal-box">1</div>
                                <div class="tieuDeKiemTra"><?php echo esc_html(pplus_localize('Thông tin căn hộ')); ?></div>
                            </div>
                        </div>
                        <hr class="w50o" />
                        <div class="row ttin">
                            <div class="col-md-3">
                                <div class="_label"><?php echo esc_html(pplus_localize('Tên dự án')); ?></div>
                                <div class="number-value mt-5" id="modal-echo-NameProject"></div>
                            </div>
                            <div class="col-md-3">
                                <div class="_label"><?php echo esc_html(pplus_localize('Tên toà nhà')); ?></div>
                                <div class="number-value mt-5" id="modal-echo-NameBuilding"></div>
                            </div>
                            <div class="col-md-3">
                                <div class="_label"><?php echo esc_html(pplus_localize('Tầng')); ?></div>
                                <div class="number-value mt-5" id="modal-echo-NumberFloor"></div>
                            </div>
                            <div class="col-md-3">
                                <div class="_label"><?php echo esc_html(pplus_localize('Số phòng')); ?></div>
                                <div class="number-value mt-5" id="modal-echo-NumberApartment"></div>
                            </div>
                        </div>

                        <div class="row margin-bottom-20 mt-25">
                            <div class="col-md-12">
                                <div class="service-modal-box">2</div>
                                <div class="tieuDeKiemTra"><?php echo esc_html(pplus_localize('Chi tiết căn hộ')); ?></div>
                            </div>
                        </div>
                        <hr class="w50o" />
                        <div class="row ttin">
                            <div class="col-md-4"><div class="_label"><?php echo esc_html(pplus_localize('Số phòng ngủ')); ?></div></div>
                            <div class="col-md-8"><div class="number-value" id="modal-echo-Bedroom"></div></div>
                        </div>
                        <div class="row ttin">
                            <div class="col-md-4"><div class="_label"><?php echo esc_html(pplus_localize('Số phòng tắm')); ?></div></div>
                            <div class="col-md-8"><div class="number-value" id="modal-echo-WCroom"></div></div>
                        </div>
                        <div class="row ttin">
                            <div class="col-md-4"><div class="_label"><?php echo esc_html(pplus_localize('Diện tích')); ?></div></div>
                            <div class="col-md-8"><div class="number-value" id="modal-echo-Area"></div></div>
                        </div>
                        <div class="row ttin">
                            <div class="col-md-4"><div class="_label"><?php echo esc_html(pplus_localize('Nội thất')); ?></div></div>
                            <div class="col-md-8"><div class="number-value" id="modal-echo-NTT"></div></div>
                        </div>

                        <div class="row margin-bottom-20 mt-25">
                            <div class="col-md-12">
                                <div class="service-modal-box">3</div>
                                <div class="tieuDeKiemTra"><?php echo esc_html(pplus_localize('Ghi chú')); ?></div>
                            </div>
                        </div>
                        <hr class="w50o" />
                        <div class="row ttin">
                            <div class="col-md-12">
                                <p style="opacity:0.75; margin-top:0px;" id="modal-echo-Remarks"></p>
                            </div>
                        </div>

                        <div class="row margin-bottom-20 mt-25">
                            <div class="col-md-12">
                                <div class="service-modal-box">4</div>
                                <div class="tieuDeKiemTra"><?php echo esc_html(pplus_localize('Giá thuê')); ?></div>
                            </div>
                        </div>
                        <hr class="w50o" />
                        <div class="row ttin">
                            <div class="col-md-4"><div class="_label"><?php echo esc_html(pplus_localize('Giá mong muốn cho thuê')); ?>:</div></div>
                            <div class="col-md-8"><div class="number-value" id="modal-echo-TotalPrice"></div></div>
                        </div>
                        <div class="row ttin">
                            <div class="col-md-4"><div class="_label"><?php echo esc_html(pplus_localize('Thương lượng giá')); ?>:</div></div>
                            <div class="col-md-8"><div class="number-value" id="modal-echo-IsNegotiatePrice"></div></div>
                        </div>
                    </div>

                    <div class="col-md-5 thongtin-dangky">
                        <h4 class="pp-title w70o m-auto margin-bottom-10 text-center"><?php echo esc_html(pplus_localize('Nhập thông tin liên hệ của bạn')); ?></h4>
                        <div class="pp-color text-center"><?php echo esc_html(pplus_localize('Chuyên viên sẽ liên hệ ngay với bạn khi nhận thông tin')); ?></div>

                        <div class="m-auto margin-top-30">
                            <form id="OwnerRegisterForm" novalidate class="form-validation form-group-lg" spellcheck="false" onsubmit="return false;">
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
                                    <button class="btn btn-primary" id="HoanTatDangKyChoThueNha" type="button">
                                        <?php echo esc_html(pplus_localize('Hoàn tất đăng ký cho thuê căn hộ')); ?>
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
