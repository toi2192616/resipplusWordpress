<?php
/**
 * Card van phong that (khac voi parts/building-card.php la ban rut gon dung cho landing).
 * Port tu src/app/partials/office-list/office-list.component.html - dung cho trang
 * danh sach /cho-thue-van-phong (page-office-list.php).
 * Nhan $args['item'] la 1 phan tu tu result.listOffice cua Office/_GetOffices.
 *
 * Nut "Yeu thich" (.item-like) da duoc noi voi assets/js/pplus-favourite.js (Dev A, localStorage +
 * modal, xem inc/favourite-route.php) - can `data-office` (JSON gon, tren chinh ".item-office") de
 * JS luu vao bo suu tap ma khong can goi lai API.
 * Nut "So sanh" (.viewdetail) da noi voi assets/js/compare.js (Dev B, xem parts/compare-modal.php) -
 * can `data-compare` (JSON, tren ".viewdetail") mang du cac field mau "Tieu chi so sanh" can
 * (product-compare.component.html) vi trang WP la multi-page (khong con GlobalConstants.
 * ListProducCompare in-memory nhu Angular SPA), phai nhung san du lieu ngay tren item thay vi goi
 * lai API luc mo modal. pplus-favourite.js (Dev A) CUNG tung co 1 ban "So sanh" rieng (dung chung
 * ".viewdetail" + key localStorage "ListProducCompare") - da BO phan do khoi pplus-favourite.js,
 * GIU LAI ban nay (day du hon: co ca bang so sanh chi tiet + xuat PDF, da test end-to-end) theo
 * quyet dinh cua nguoi dung khi merge 2 nhanh Dev A/Dev B.
 */
if (!defined('ABSPATH')) exit;

$item = $args['item'] ?? [];
if (empty($item)) return;

// Toi uu LCP (Largest Contentful Paint) - theo yeu cau nguoi dung sau khi xem Lighthouse that: anh
// THE DAU TIEN trong danh sach chinh la anh LCP (luon nam san trong man hinh dau, khong can cuon
// toi), nhung truoc day CA THE DEU gan "loading=lazy" nhu nhau - trinh duyet tu tri hoan tai anh
// nay lam LCP cham hon that su can (Lighthouse bao "fetchpriority=high should be applied", diem
// LCP chi 49/100). $args['index'] (0-based, cac trang danh sach chinh truyen vao qua foreach $i =>
// $item, xem page-office-list.php/page-reference.php) - CHI anh dau tien (index 0) moi bo lazy +
// them fetchpriority=high (dung 1 anh de tranh "eager-load qua nhieu anh" phan tac dung nguoc,
// cac the con lai van lazy nhu cu).
$is_lcp_candidate = ($args['index'] ?? null) === 0;

$photo = !empty($item['photoList'][0]) ? pplus_admin_asset_url($item['photoList'][0]) : '';
$rank  = $item['ratingLevel'] ?? '';
$is_traditional = ($item['subType'] ?? null) === 2 || ($item['subType'] ?? null) === '2';

// pplus_build_compare_payload() (inc/render-helpers.php) - dung CHUNG voi REST route
// "pplus/v1/compare-item" (lam moi lai 1 item sang ngon ngu khac, xem assets/js/compare.js
// "ensureCorrectLang") de tranh 2 noi giu 2 ban logic xay payload khac nhau roi lech nhau dan.
// "urlHerf" trong payload - de assets/js/compare.js goi REST route pplus/v1/office-park-fees (lay
// them Phi gui o to/xe may, 2 truong LUON rong tren chinh API danh sach nay - xem inc/compare-pdf-
// route.php). "carParkInfo"/"motorParkInfo" CO CHU DICH KHONG co trong payload (khac cac field
// khac) - 2 truong nay LUON rong tren chinh API danh sach nay du van phong co du lieu that (xac
// nhan qua API that), gia tri THAT chi lay duoc qua goi luoi rieng (pplus/v1/office-park-fees)
// trong assets/js/compare.js. Neu dua san "carParkInfo: ''" (rong) vao day, JS se KHONG THE phan
// biet duoc "chua goi API lay du lieu" voi "da goi nhung van rong" (ca 2 deu la chuoi rong '') nen
// se BO QUA khong goi lay du lieu that luon - dung nguyen nhan bug thuc te da gap (nguoi dung bao
// "sao khong thay phi gui xe", du co luc thay co luc khong). Cu de 2 truong nay THIEU HAN (undefined
// ben JS) de ensureParkFees() nhan ra dung.
$compare_payload = pplus_build_compare_payload($item);

// Gia hien thi (giong logic ben duoi) - luu kem vao data-office de assets/js/pplus-favourite.js
// (Dev A, tinh nang Yeu thich) dung lai, khong can goi lai API.
$price_display = $is_traditional
    ? ($item['itemPrice'] ?? '')
    : (number_format((float) ($item['fromLeaseFee'] ?? 0), 0, ',', '.') . ' VND/' . pplus_t('Tháng', 'Month'));

// Object gon luu localStorage (khop field JS pplus-favourite.js can: projectId/projectName/address/
// ratingLevel/itemPrice/urlHerf/photoList/PhotoListBaSE/officeType).
$office_data = [
    'projectId'     => $item['projectId'] ?? '',
    'projectName'   => $item['projectName'] ?? '',
    'address'       => $is_traditional ? ($item['address'] ?? '') : (pplus_current_lang() === 'en' ? ($item['officeDescEn'] ?? '') : ($item['officeDesc'] ?? '')),
    'ratingLevel'   => $rank,
    'itemPrice'     => $price_display,
    'urlHerf'       => pplus_office_url($item['urlHerf'] ?? ''),
    'photoList'     => $photo ? [$photo] : [],
    'PhotoListBaSE' => $photo,
    'officeType'    => $item['officeType'] ?? 0,
];
?>
<div class="item-office hover-office" data-id="office-<?php echo esc_attr($item['projectId'] ?? ''); ?>" data-office="<?php echo esc_attr(wp_json_encode($office_data)); ?>">
    <a href="<?php echo esc_url(pplus_office_url($item['urlHerf'] ?? '')); ?>" title="<?php echo esc_attr($item['projectName'] ?? ''); ?>">
        <?php if ($photo) : ?>
            <img src="<?php echo esc_url($photo); ?>" alt="<?php echo esc_attr($item['projectName'] ?? ''); ?>" <?php echo $is_lcp_candidate ? 'fetchpriority="high"' : 'loading="lazy"'; ?> />
        <?php else : ?>
            <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/officebase/Common/Images/Rectangle 2936.png'); ?>" alt="<?php echo esc_attr($item['projectName'] ?? ''); ?>" <?php echo $is_lcp_candidate ? 'fetchpriority="high"' : 'loading="lazy"'; ?> />
        <?php endif; ?>
    </a>
    <div class="item-like" data-id="<?php echo esc_attr($item['projectId'] ?? ''); ?>">
        <i>
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 13 12" fill="none">
                <path d="M11.3828 1.25696L11.3832 1.2574C12.5077 2.41818 12.5077 4.30975 11.3832 5.47054L11.383 5.47076L6.72666 10.2832L2.07025 5.46799L2.06994 5.46767C0.945437 4.30689 0.945437 2.41532 2.06994 1.25453L2.07036 1.2541C3.03878 0.252006 4.60228 0.251328 5.57419 1.25446C5.57422 1.25448 5.57425 1.25451 5.57428 1.25454L6.36737 2.07357L6.72593 2.44386L7.08515 2.07419L7.87832 1.25795L7.87928 1.25696C8.84797 0.254591 10.4141 0.254591 11.3828 1.25696Z" />
            </svg>
        </i>
    </div>
    <div class="item-content-m">
        <div class="item-content">
            <div class="RateTing">
                <?php if ($rank === 'A' || $rank === 'B' || $rank === 'C') : ?>
                    <div class="RateTingContent class<?php echo esc_attr($rank); ?>"><?php echo esc_html(sprintf(pplus_t('Hạng %s', 'Grade %s'), $rank)); ?></div>
                <?php else : ?>
                    <div class="RateTingContent"><?php echo esc_html(pplus_t('Đang cập nhật', 'Updating')); ?></div>
                <?php endif; ?>

                <?php if ($is_traditional) : ?>
                    <div class="item-price"><?php echo esc_html($item['itemPrice'] ?? ''); ?></div>
                <?php else : ?>
                    <div class="item-price"><?php echo esc_html(number_format((float) ($item['fromLeaseFee'] ?? 0), 0, ',', '.')); ?> VND/<?php echo esc_html(pplus_t('Tháng', 'Month')); ?></div>
                <?php endif; ?>
            </div>

            <?php // Ten hang muc dich vu cua CHINH the nay (Van phong rieng/Cho ngoi co dinh/Cho
            // ngoi linh hoat/Dang ky kinh doanh) - dong 47-49 office-list.component.html, truoc day
            // thieu hoan toan khien 4 trang dich vu (page-reference.php) nhin nhu giong het nhau du
            // gia/mo ta da dung. Rong voi van phong truyen thong (item.officeType khong khop). ?>
            <div class="office-type"><?php echo esc_html(pplus_office_type_name($item['officeType'] ?? 0)); ?></div>

            <h3 class="item-name">
                <a href="<?php echo esc_url(pplus_office_url($item['urlHerf'] ?? '')); ?>" title="<?php echo esc_attr($item['projectName'] ?? ''); ?>">
                    <?php echo esc_html($item['projectName'] ?? ''); ?>
                </a>
            </h3>

            <?php if ($is_traditional) : ?>
                <div class="item-address"><?php echo esc_html($item['address'] ?? ''); ?></div>
            <?php else : ?>
                <?php // Angular (office-list.component.html dong 60-67) doi field theo ngon ngu:
                // "officeDesc" (vi) / "officeDescEn" (en) - truoc day luon dung "officeDesc" du
                // dang o /en/, hien tieng Viet sai (bug thuc te da gap). ?>
                <div class="item-address"><?php echo esc_html((pplus_current_lang() === 'en' ? ($item['officeDescEn'] ?? '') : ($item['officeDesc'] ?? ''))); ?></div>
            <?php endif; ?>

            <div class="viewdetail" data-id="<?php echo esc_attr($item['projectId'] ?? ''); ?>" data-compare="<?php echo esc_attr(wp_json_encode($compare_payload)); ?>">
                <?php echo esc_html(pplus_t('So sánh', 'Compare')); ?>
                <i>
                    <svg width="13" height="13" viewBox="0 0 17 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M1 6.85596H16M16 6.85596L10.375 0.855957M16 6.85596L10.375 12.856" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </i>
            </div>
        </div>
    </div>
</div>
