<?php
/**
 * Card can ho/biet thu dung cho cac carousel trang chu (nhom trong datalistApartment/
 * datalistApartmentVila, xem pplus_get_home_apartment_groups() trong inc/api-client.php).
 * Port 1:1 tu home.component.html dong 68-107 (PPlusAngularResi) - class giu nguyen ten Angular
 * (.item-office/.object--info/.thongtin-ctn/...) de khop dung voi home-component.css moi compile
 * tu chinh home.component.less, KHONG dung lai class cua parts/office-card.php (theme khac).
 *
 * Nhan $args['item'] la 1 phan tu cua listApartments (Home/_GetApartments).
 *
 * SUA 17/9 (theo yeu cau nguoi dung, KHONG phai loi port): them ".item-address" (du lieu that -
 * $item['address'], da co san trong response, chi truoc gio khong render) va ".item-type-badge"
 * (nhan goc anh - dung THANG $item['apartmentType'], 1|2, khong bia them - xem chi tiet duoi day).
 * 2 phan nay KHONG co trong home.component.html goc (card carousel trang chu that khong co ca 2),
 * la thay doi giao dien rieng cua ban WP nay theo yeu cau nguoi dung, ap dung cho MOI noi dung
 * dung parts/apartment-card.php (ca carousel trang chu lan luoi danh sach page-reference.php).
 */
if (!defined('ABSPATH')) exit;

$item = $args['item'] ?? [];
if (empty($item)) return;

$photo = !empty($item['photoList'][0]) ? pplus_admin_asset_url($item['photoList'][0]) : '';
$title = $item['title'] ?? ($item['projectName'] ?? '');

// 23/10/2026 (port tu ban Van phong b4a7104): luoi danh sach (A1/A3/A4/Du an) truyen $args['index'] - the 0
// la phan tu LCP tren mobile (Lighthouse do: anh nay "Load Delay" 15s vi loading=lazy, trinh duyet chi tai
// sau khi layout xong) -> bo lazy + fetchpriority=high. The 1-2 cung nam trong man hinh dau (desktop 3 cot)
// nen bo lazy nhung KHONG uu tien. Carousel trang chu/ban do khong truyen index -> giu lazy nhu cu.
$card_index = $args['index'] ?? null;
$img_loading_attr = $card_index === 0 ? 'fetchpriority="high"'
    : ((is_int($card_index) && $card_index < 3) ? '' : 'loading="lazy"');

// pplus_office_url() tra ve duong dan TUONG DOI, KHONG CO dau "/" dau (vd "cho-thue-can-ho/ha-noi/
// .../3267/"). esc_url() cua WordPress khi gap chuoi kieu nay (khong "/" dau, khong scheme) se TU
// Y them "http://" vao truoc, bien "cho-thue-can-ho" thanh TEN MIEN gia (href sai thanh
// "http://cho-thue-can-ho/...") - bug thuc te da gap khi kiem tra link tren tung the. Boc qua
// home_url() truoc de co URL tuyet doi dung truoc khi esc_url().
//
// SUA 30/9 (bug thuc te nguoi dung bao cao: "đang ở trang /en > click vào 1 căn hộ bị chuyển sang
// /vi"): "urlHerf" tho tu Apartment/_GetApartments/_GetDetail KHONG BAO GIO mang tien to "/en"/"/ja"
// du goi API voi lang nao (xac nhan qua curl truc tiep: "en/Apartment/_GetDetail" van tra ve
// "alasUrlHref":"rental-apartment/..." KHONG co "/en" dau) - KHAC HAN "urlHerf" cua Office/_GetOffices
// (tu mang san "/en/office-for-rent/..." khi goi lang=en, xem ghi chu dau ham pplus_office_url()).
// Thieu $lang_prefix o day khien link tren MOI the can ho (trang chu, A1, A3/A4) tren ban EN/JA thieu
// tien to ngon ngu that su trong href - bam vao se roi thang toi trang KHONG co "/en", khien
// inc/language-route.php (doc tien to tu URL) tu dong coi la trang VI, lam noi dung + nut chuyen ngu
// canh chuyen han sang VI ngay sau khi bam.
$apartment_url = home_url(pplus_lang_prefix() . '/' . ltrim(pplus_office_url($item['urlHerf'] ?? ''), '/'));

// "data-marker-id" (them vao, khong co trong ban goc): khop voi id gan tren marker ban do tuong
// ung trong page-reference.php/reference-map-simple.js, de tro chuot vao the nay lam marker phong
// to. Rong tren cac trang khong co ban do (vd carousel trang chu) - vo hai, JS chi gan listener khi
// initMap() thuc su chay.
$marker_id = $item['apartmentId'] ?? ($item['projectId'] ?? '');

// (them 17/9, theo yeu cau nguoi dung) Nhan "Căn hộ dịch vụ"/"Chung cư" tren goc anh - dung THANG
// truong that "apartmentType" cua Apartment/_GetApartments (1|2, xac nhan qua goi API truc tiep:
// KHONG phai du lieu bia them - 3 la ma biet-thu, da bi loc bo tu truoc qua pplus_strip_villa_type()
// nen o day chi con gap 1 hoac 2). Nhan chu dung dung 2 khoa dictionary that ma checkbox loc
// "Loại nhà/phòng" cua chinh search-base.component.html dang dung (dong 349/352: "Căn hộ dịch vụ"/
// "Chung cư") - pplus_localize() tu dich sang EN qua Home/GetLangApi that ("Serviced apartment"/
// "Condominium", da xac nhan co san trong dictionary that, khong can code cung chuoi EN).
$apartment_type = (int) ($item['apartmentType'] ?? 0);
if ($apartment_type === 1) {
    $badge_label = pplus_localize('Căn hộ dịch vụ');
    $badge_class = 'badge-serviced';
} elseif ($apartment_type === 2) {
    $badge_label = pplus_localize('Chung cư');
    $badge_class = 'badge-condo';
} else {
    $badge_label = '';
    $badge_class = '';
}
?>
<a class="item-office" href="<?php echo esc_url($apartment_url); ?>" data-marker-id="<?php echo esc_attr($marker_id); ?>">
    <?php if ($photo) : ?>
        <img class="lazy" src="<?php echo esc_url($photo); ?>" alt="<?php echo esc_attr($title); ?>" <?php echo $img_loading_attr; ?> />
    <?php else : ?>
        <img class="lazy" src="<?php echo esc_url(get_template_directory_uri() . '/assets/officebase/Common/Images/Rectangle 2936.png'); ?>" alt="<?php echo esc_attr($title); ?>" <?php echo $img_loading_attr; ?> />
    <?php endif; ?>

    <?php if ($badge_label) : ?>
        <div class="item-type-badge <?php echo esc_attr($badge_class); ?>">
            <?php if ($apartment_type === 1) : ?><i class="fa fa-star"></i><?php endif; ?>
            <?php echo esc_html($badge_label); ?>
        </div>
    <?php endif; ?>

    <?php
    // (them 22/9, theo yeu cau nguoi dung "làm sang màn danh sách yêu thích") Noi nut yeu thich voi
    // co che localStorage that (favourite.component.ts, PPlusAngularResi) - xem assets/js/
    // pplus-favourite.js. "data-apartment" mang du lieu THAT can cho trang /danh-sach-yeu-thich
    // (photoList de ghep thumbnail 4-o + cac field hien thi khac) - dung json_encode 1 lan, KHONG
    // goi API rieng khi bam "thich" (giong that: AddFavourite() chi day thang "itemSelect" - chinh
    // object dang co san tren client - vao localStorage, khong fetch lai server).
    $fav_payload = [
        'apartmentId'       => $item['apartmentId'] ?? null,
        'title'              => $title,
        'address'            => $item['address'] ?? '',
        'photoList'          => !empty($item['photoList']) ? array_map('pplus_admin_asset_url', array_slice($item['photoList'], 0, 4)) : [],
        'priceString'        => $item['priceString'] ?? '',
        'areaString'         => $item['areaString'] ?? '',
        'bedRoomQtyString'   => $item['bedRoomQtyString'] ?? '',
        'bathRoomQtyString'  => $item['bathRoomQtyString'] ?? '',
        'urlHerf'            => ltrim(pplus_office_url($item['urlHerf'] ?? ''), '/'),
    ];
    ?>
    <div class="item-like" data-apartment="<?php echo esc_attr(wp_json_encode($fav_payload)); ?>">
        <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/fv-white.png'); ?>" alt="Like Product" loading="lazy" width="23" height="18" />
    </div>

    <div class="item-content-m">
        <div class="item-content">
            <div class="item-name"><?php echo esc_html($title); ?></div>
            <?php if (!empty($item['address'])) : ?>
                <div class="item-address"><i class="icon-location-pin"></i> <?php echo esc_html($item['address']); ?></div>
            <?php endif; ?>
            <div class="object--info">
                <div class="thongtin-ctn">
                    <span class="ten"><?php echo esc_html(pplus_localize('Diện tích')); ?></span>
                    <span class="thongtin"><span class="solieu"><?php echo esc_html($item['areaString'] ?? ''); ?> m<sup>2</sup></span></span>
                </div>
                <div class="thongtin-ctn">
                    <span class="ten"><?php echo esc_html(pplus_localize('Phòng tắm')); ?></span>
                    <span class="thongtin"><i class="bontam-ico"></i><span class="solieu"><?php echo esc_html($item['bathRoomQtyString'] ?? ''); ?></span></span>
                </div>
                <div class="thongtin-ctn">
                    <span class="ten"><?php echo esc_html(pplus_localize('Phòng ngủ')); ?></span>
                    <span class="thongtin"><i class="phongngu-ico"></i><span class="solieu"><?php echo esc_html($item['bedRoomQtyString'] ?? ''); ?> <?php echo esc_html(pplus_localize('phòng')); ?></span></span>
                </div>
            </div>
            <div class="item-price">
                <span><?php echo esc_html($item['priceString'] ?? ''); ?>$</span>&nbsp;/<span class="time_unit"><?php echo esc_html(pplus_localize('tháng')); ?></span>
            </div>
        </div>
    </div>
</a>
