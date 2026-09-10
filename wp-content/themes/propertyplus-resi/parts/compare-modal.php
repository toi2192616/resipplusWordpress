<?php
/**
 * Khung 2 modal "So sánh" (tinh nang "So sanh" tren cac the van phong, xem parts/office-card.php
 * data-compare + assets/js/compare.js). Port tu:
 *  - src/app/partials/list-compare/list-compare.component.html  -> #modal-compare-list
 *  - src/app/partials/product-compare/product-compare.component.html -> #modal-compare-table
 *
 * Khac Angular (GlobalConstants.ListProducCompare, bien tinh trong bo nho, mat khi doi route vi la
 * SPA): WP la multi-page nen danh sach so sanh luu localStorage (key "pplus_compare_list") va
 * MOI item duoc render lai hoan toan boi JS (khong the biet truoc se so sanh van phong nao luc PHP
 * render trang - nguoi dung co the da chon tu 1 trang KHAC). Cot "Tieu chi so sanh" (nhan + icon)
 * la TINH nen render san bang PHP (dung pplus_t()) - THU TU 14 dong duoi day PHAI khop dung thu tu
 * mang PPLUS_COMPARE_CRITERIA_ORDER trong assets/js/compare.js (JS chi render GIA TRI theo dung
 * thu tu, khong lap lai ten field).
 *
 * Bao gom qua parts/footer.php (site-wide) vi office-card.php xuat hien tren rat nhieu trang
 * (A1/A2/A3/A4 + "Van phong lien quan" tren single-news.php).
 */
if (!defined('ABSPATH')) exit;

$pplus_compare_criteria = [
    ['icon' => 'img-service-15.png', 'label' => pplus_t('Giá thuê', 'Rent cost')],
    ['icon' => 'img-service-15.png', 'label' => pplus_t('Giá dịch vụ', 'Service price')],
    ['icon' => 'img-service-15.png', 'label' => pplus_t('Tổng giá', 'Total price')],
    ['icon' => 'img-service-17.png', 'label' => pplus_t('Vị trí', 'Location'), 'class' => 'inforComapre-address'],
    ['icon' => 'img-service-18.png', 'label' => pplus_t('Hạng', 'Grade')],
    ['icon' => 'img-service-2.png', 'label' => pplus_t('Đơn vị quản lý', 'Management by')],
    ['icon' => 'img-service-12.png', 'label' => pplus_t('Ngày hoàn tất', 'Complete Date')],
    ['icon' => 'img-service-11.png', 'label' => pplus_t('Diện tích mỗi sàn', 'Typical floor plate')],
    ['icon' => 'img-service-16.png', 'label' => pplus_t('Khu vực để xe', 'Basement/Parking')],
    ['icon' => 'img-service-9.png', 'label' => pplus_t('Máy phát điện', 'Back up power')],
    ['icon' => 'img-service-14.png', 'label' => pplus_t('Thang máy', 'Elevators')],
    ['icon' => 'img-service-4.png', 'label' => pplus_t('Điện điều hòa', 'AC electricity cost')],
    ['icon' => 'img-service-6.png', 'label' => pplus_t('Phí gửi ô tô', 'Car parking fee')],
    ['icon' => 'img-service-19.png', 'label' => pplus_t('Phí gửi xe máy', 'Motorbike parking fee')],
];
$pplus_icon_detail_uri = get_template_directory_uri() . '/assets/Content/Img-Icon-Detail/';
?>
<!-- Modal: danh sach mini "So sanh" (buoc 1, bam "So sanh" tren the van phong se mo cai nay) -->
<div class="modal-container-ppus modal-container-compare" id="modal-compare-list" data-pplus-modal>
  <div class="modal-background">
    <div class="modal">
      <div class="modal-header">
        <div class="modal-title"><?php echo esc_html(pplus_t('So sánh thông tin toà nhà', 'Compare building information')); ?></div>
        <button type="button" class="btn btn-primary" data-pplus-close-modal="modal-compare-list"><?php echo esc_html(pplus_t('Chọn thêm', 'Add more')); ?></button>
      </div>
      <div class="modal-body">
        <div class="list-compare" id="pplus-compare-list-items"></div>
        <button type="button" class="btn btn-primary" id="pplus-compare-open-table">
          <i><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/icon-compare-white.png'); ?>" alt="" loading="lazy" /></i>
          <?php echo esc_html(pplus_t('So sánh', 'Compare')); ?>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: bang so sanh day du (buoc 2, bam nut "So sanh" trong modal tren se mo cai nay) -->
<div class="modal-container-ppus modal-container-compare" id="modal-compare-table" data-pplus-modal>
  <div class="modal-background">
    <div class="modal">
      <div class="modal-header">
        <div class="modal-title"><?php echo esc_html(pplus_t('So sánh thông tin toà nhà', 'Compare building information')); ?></div>
        <div class="pull-right">
          <button type="button" class="btn btn-outline-success btn-pdf" id="pplus-compare-export-pdf">
            <i>
              <svg style="width:18px;margin-left:5px;margin-top:-2px;" fill="#61A2D8" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512">
                <path d="M32 64C32 28.7 60.7 0 96 0H256V128c0 17.7 14.3 32 32 32H416V288H248c-13.3 0-24 10.7-24 24s10.7 24 24 24H416V448c0 35.3-28.7 64-64 64H96c-35.3 0-64-28.7-64-64V64zM416 336V288H526.1l-39-39c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l80 80c9.4 9.4 9.4 24.6 0 33.9l-80 80c-9.4 9.4-24.6 9.4-33.9 0s-9.4-24.6 0-33.9l39-39H416zm0-208H288V0L416 128z" />
              </svg>
            </i>
            <?php echo esc_html(pplus_t('Xuất PDF', 'Export PDF')); ?>
          </button>
          <button type="button" class="btn btn-outline-primary" data-pplus-close-modal="modal-compare-table"><?php echo esc_html(pplus_t('Đóng', 'Close')); ?></button>
        </div>
      </div>
      <div class="modal-body">
        <div class="container-compare">
          <div class="container-compare-data">
            <?php // Cot nhan (".inforThongSo") va tung cot san pham (".inforCompare-data", JS render
            // trong "#pplus-compare-table-items") CUNG la con truc tiep cua LUOI CSS Grid duy nhat
            // ".container-compare-data" ("display:contents" tren 2 wrapper nay + tren
            // ".inforCompare-data" - xem compare-component.css) - moi tieu chi (hang) duoc gan
            // "grid-row" TUONG MINH giong nhau giua nhan va MOI cot san pham, nen trinh duyet TU
            // canh chieu cao tung hang khop nhau (hang nao noi dung dai thi ca hang cao ra, hang
            // ngan thi hep lai) MA KHONG can ep chieu cao co dinh nhu truoc (gay lang phi khoang
            // trong khi da so hang chi co noi dung ngan). ?>
            <div class="inforThongSo">
              <div class="header-data-1" style="grid-row:1;grid-column:1;">
                <div class="div-img-header">
                  <?php // Ban .webp (13/8/2026) - xem ghi chu parts/header.php. ?>
                  <picture>
                    <source type="image/webp" srcset="<?php echo esc_url(get_template_directory_uri() . '/assets/officebase/Common/Images/Home/logo_gold-text.webp'); ?>" />
                    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/officebase/Common/Images/Home/logo_gold-text.png'); ?>" alt="PropertyPlus" loading="lazy" />
                  </picture>
                </div>
                <h3><?php echo esc_html(pplus_t('Tiêu chí so sánh', 'Comparison criteria')); ?></h3>
              </div>
              <?php foreach ($pplus_compare_criteria as $i => $criterion) : ?>
                <div class="inforComapre<?php echo !empty($criterion['class']) ? ' ' . esc_attr($criterion['class']) : ''; ?>" style="grid-row:<?php echo (int) $i + 2; ?>;grid-column:1;">
                  <div class="div-img"><img src="<?php echo esc_url($pplus_icon_detail_uri . $criterion['icon']); ?>" alt="" loading="lazy" /></div>
                  <label><?php echo esc_html($criterion['label']); ?></label>
                </div>
              <?php endforeach; ?>
            </div>
            <div class="list-inforCompare" id="pplus-compare-table-items"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
