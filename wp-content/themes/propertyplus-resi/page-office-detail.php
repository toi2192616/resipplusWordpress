<?php
/**
 * Trang chi tiet van phong (A2) - /cho-thue-van-phong/{province}/{district}/{alias}-{id}
 * Port rut gon (ban SEO co ban) tu src/app/modules/office/detail/detail.component.html.
 * Route dang ky trong inc/office-detail-route.php (khong phai Page template vi URL 3 doan dong).
 *
 * Nut "Yeu thich/Xuat PDF/Chia se" (.actine-share, dong ~219) da noi voi assets/js/pplus-favourite.js
 * (13/8/2026, xem ghi chu ngay tren "actine-share" ben duoi) - doi chieu truc tiep tren propertyplus.vn
 * that (Pearl 5 Building) truoc khi sua: Yeu thich mo modal "them vao bo suu tap" (giong nut tren
 * card), Xuat PDF hien confirm ("Ban xac nhan muon tai file PDF...") roi tai .pdf, Chia se copy link
 * trang hien tai vao clipboard + toast - CA 3 dung chung 1 payload "data-office" tren wrapper.
 */
if (!defined('ABSPATH')) exit;

$model = pplus_get_office_detail_from_query_vars();

if (!$model) {
    status_header(404);
    get_header();
    echo '<div class="container" style="padding:40px 0;">';
    pplus_render_api_error();
    echo '</div>';
    get_footer();
    return;
}

$lang_prefix = pplus_lang_prefix();
$is_vpdv     = ((int) ($model['subType'] ?? 0)) === 1; // van phong dich vu (tron goi)
$unit        = $is_vpdv ? pplus_t('người', 'people') : 'm2';
// API tra ve "url" da la link tuyet doi (khac photoList cua listOffice, la duong dan tuong doi) -
// NHUNG van co the dinh dau "\" (bug .NET goc, xem ghi chu trong pplus_admin_asset_url()) khien
// esc_url() lang lang xoa het dau "\" do, dinh lien 2 doan path lam anh 404. Chay qua ham chung
// nay (da xu ly ca 2 truong hop tuong doi/tuyet doi) thay vi dung "url" tho.
$photos = array_map(static function (array $p): array {
    $p['url'] = pplus_admin_asset_url($p['url'] ?? '');
    return $p;
}, $model['photoList'] ?? []);

// Du lieu cho lightbox Fotorama (showPhotos() trong Common/Scripts/common.js, da duoc enqueue
// san toan site qua officebase bundle) - doi chieu tu detail.component.ts dong 474-485
// (viewAllPhotos()): chi can key "img", lay tu chinh mang photoList da dung cho luoi 5 anh tinh.
$fotorama_photos = array_map(static fn(array $p): array => ['img' => $p['url'] ?? ''], $photos);

// "Văn phòng gần đó" - port CHINH XAC searchBaseDetail (detail.component.ts dong 198-217): loc
// theo 1 O VUONG DIA LY (+-0.3 do quanh toa do CHINH DU AN dang xem, KHONG phai toan tinh/thanh
// nhu tim kiem thuong), page_count=5 (khong phai 18). Truoc day thieu ca bounds lan page_count
// dung, khien ket qua "gan do" thuc chat la 1 truy van rong/khong on dinh, hay lap lai CUNG 1 du
// an (voi nhieu the loai dich vu khac nhau cua no) thay vi cac du an KHAC nhau that su gan ve mat
// dia ly (bug thuc te da gap).
$nearby = pplus_get_office_list([
    'province'          => $model['provinceName'] ?? '',
    'project_type'      => $model['subType'] ?? 2,
    'project_id_not_in' => $model['projectId'] ?? 0,
    'sort_type'         => 4,
    'page_count'        => 5,
    'ne_lat'            => isset($model['lat']) ? (float) $model['lat'] + 0.3 : null,
    'ne_lng'            => isset($model['lng']) ? (float) $model['lng'] + 0.3 : null,
    'sw_lat'            => isset($model['lat']) ? (float) $model['lat'] - 0.3 : null,
    'sw_lng'            => isset($model['lng']) ? (float) $model['lng'] - 0.3 : null,
]);

// Muc luc ("Mục Lục") - giong het cach A3/A4 lam (xem page-reference.php), truoc day thieu hoan
// toan o day nen phan "Thông tin văn phòng" chi co bai viet, khong co khung Mục Lục dieu huong.
$toc = pplus_generate_toc($model['dataSelected']['content'] ?? '');

// Doi ngon ngu (header.php, nut EN/VI) - URL vi/en cua CUNG 1 toa nha KHAC HAN nhau (xem ghi chu
// pplus_language_switch_url()/pplus_get_office_url_in_lang() trong inc/api-client.php), nen phai
// tu ghi de bang urlHerf that lay qua Office/_GetOffices?project_id=... thay vi de header.php chi
// doi/bo tien to /en tren cung 1 duong dan (se ra URL sai alias, 404 ca 2 chieu - bug thuc te da gap).
add_filter('pplus_language_switch_url', function (string $default, string $target_lang) use ($model): string {
    $target_url = pplus_get_office_url_in_lang(
        (int) ($model['projectId'] ?? 0),
        (int) ($model['subType'] ?? 2),
        $target_lang
    );
    return $target_url ? pplus_office_url($target_url) : $default;
}, 10, 2);

get_header();
?>
<div class="container office-detail row">
    <?php // Nut tron "<" quay lai trang truoc (goc tren-trai, CHI hien tren mobile) - port tu
    // detail.component.html dong 24-28 (div.url-callBack, click goi backClicked() = history.back()).
    // CSS da co san (search-base-component.css: .action-close-map, display:none mac dinh, chi
    // hien duoi 880px) nhung thieu markup nen truoc day khong hien tren mobile. ?>
    <div class="url-callBack">
        <div class="action-close-map">
            <i class="fa fa-angle-right" aria-hidden="true" title="next"></i>
        </div>
    </div>
    <div class="url-box-content">
        <?php // Bao ngoai boi ".container" giong het A1 (page-office-list.php) - can cho fix flex
        // ben duoi (main.css) dat dung breadcrumb+nut tren CUNG 1 hang (xem ghi chu trong main.css). ?>
        <div class="container">
        <div class="url-content">
            <div class="url-content-list">
                <a href="<?php echo esc_url(home_url('/')); ?>"><?php echo esc_html(pplus_t('Trang chủ', 'Home')); ?></a>
                &nbsp;&nbsp;
                <?php // alasProvince THO tu .NET la "thanh-pho-ho-chi-minh"(VI)/"ho-chi-minh-city"(EN) cho
                // TPHCM -> phai chuan hoa ve "ho-chi-minh" (giong Angular .replace()) moi ra URL dung,
                // khong 404. Xem pplus_normalize_province_alias(). ?>
                <?php $alas_province = pplus_normalize_province_alias($model['alasProvince'] ?? ''); ?>
                <a href="<?php echo esc_url(home_url($lang_prefix . '/' . pplus_office_list_base_slug() . '/' . $alas_province . '/')); ?>">
                    <i><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/arrow-29.png'); ?>" alt="Icon" loading="lazy" /></i>
                    &nbsp;<?php echo esc_html(sprintf(pplus_t('Cho thuê văn phòng tại %s', 'Office for rent in %s'), $model['provinceName'] ?? '')); ?>
                </a>
                &nbsp;&nbsp;
                <a href="<?php echo esc_url(home_url($lang_prefix . '/' . pplus_office_list_base_slug() . '/' . $alas_province . '/' . ($model['alasDistrict'] ?? '') . '/')); ?>">
                    <i><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/arrow-29.png'); ?>" alt="Icon" loading="lazy" /></i>
                    &nbsp;<?php echo esc_html(sprintf(pplus_t('Cho thuê văn phòng tại %s', 'Office for rent in %s'), $model['districtName'] ?? '')); ?>
                </a>
                &nbsp;&nbsp;
                <i><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/arrow-29.png'); ?>" alt="Icon" loading="lazy" /></i>
                &nbsp;<span><?php echo esc_html($model['projectName'] ?? ''); ?></span>
            </div>
        </div>
        <?php // "<app-search-base ... [isShowUrlMenu]="true">" (detail.component.html dong 2) -
        // nut "Tìm kiếm văn phòng" canh phai thanh breadcrumb, giong het A1 - truoc day thieu hoan
        // toan o trang chi tiet (chi con breadcrumb, khong co nut). ?>
        <button type="button" class="btn btn-action-search-base" id="btn-action-filter" data-pplus-toggle="search">
            <i><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/search-filter-white.png'); ?>" loading="lazy" alt="" /></i>
            <?php echo esc_html(pplus_t('Tìm kiếm văn phòng', 'Search for offices')); ?>
        </button>
        </div>
    </div>

    <?php // Panel "Bo loc" (parts/office-filter-panel.php) - nut "Tìm kiếm văn phòng" o tren dung
    // data-pplus-toggle="search" -> $('#box-search-base').toggleClass('show-search') (assets/js/
    // main.js), nhung "#box-search-base" CHI ton tai trong chinh panel nay. Trang nay truoc day
    // KHONG include panel (khac voi moi trang khac co cung nut: A1/A3/A4/blog/lien he...) nen bam
    // nut khong co gi de mo ca (bug thuc te: "không click được"). ?>
    <?php get_template_part('parts/office-filter-panel'); ?>

    <div class="col-md-12">
        <div class="_info basic">
            <div class="photoContainer" data-photos="<?php echo esc_attr(wp_json_encode($fotorama_photos)); ?>">
                <?php if (!empty($model['videoUrl'])) : ?>
                    <div class="c1">
                        <div class="photo1-ctn">
                            <div class="youtube">
                                <iframe width="560" height="315" src="<?php echo esc_url($model['videoUrl']); ?>" title="YouTube video player" frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                            </div>
                        </div>
                    </div>
                <?php elseif (!empty($photos[0])) : ?>
                    <div class="c1">
                        <div class="photo1-ctn">
                            <a class="photoLink" href="<?php echo esc_url($photos[0]['url']); ?>" data-photo-index="0" target="_blank" rel="noopener">
                                <img src="<?php echo esc_url($photos[0]['url']); ?>" alt="<?php echo esc_attr($photos[0]['alt'] ?? ($model['projectName'] ?? '')); ?>" />
                            </a>
                        </div>
                    </div>
                <?php else : ?>
                    <?php // Khong co video LAN khong co anh - anh placeholder chung, giong het
                    // detail.component.html dong 48-51 (*ngIf="PhotoListBaSE == undefined && Model.videoUrl == undefined"). ?>
                    <div class="c1">
                        <div class="photo1-ctn">
                            <a class="photoLink">
                                <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/officebase/Common/Images/no-image.png'); ?>" alt="" />
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php $thumb_offset = empty($model['videoUrl']) ? 1 : 0; ?>
                <div class="c2">
                    <?php for ($i = $thumb_offset; $i < $thumb_offset + 2; $i++) : if (empty($photos[$i])) continue; ?>
                        <div class="photo<?php echo ($i - $thumb_offset + 2); ?>-ctn">
                            <a class="photoLink" href="<?php echo esc_url($photos[$i]['url']); ?>" data-photo-index="<?php echo (int) $i; ?>" target="_blank" rel="noopener">
                                <img src="<?php echo esc_url($photos[$i]['url']); ?>" alt="<?php echo esc_attr($photos[$i]['alt'] ?? ''); ?>" loading="lazy" />
                            </a>
                        </div>
                    <?php endfor; ?>
                </div>
                <div class="c3">
                    <?php for ($i = $thumb_offset + 2; $i < $thumb_offset + 4; $i++) : if (empty($photos[$i])) continue; ?>
                        <div class="photo<?php echo ($i - $thumb_offset + 2); ?>-ctn">
                            <a class="photoLink" href="<?php echo esc_url($photos[$i]['url']); ?>" data-photo-index="<?php echo (int) $i; ?>" target="_blank" rel="noopener">
                                <img src="<?php echo esc_url($photos[$i]['url']); ?>" alt="<?php echo esc_attr($photos[$i]['alt'] ?? ''); ?>" loading="lazy" />
                            </a>
                        </div>
                    <?php endfor; ?>
                </div>
                <?php if (!empty($photos[0])) : ?>
                    <?php // Icon khung anh (svg 24x21) truoc chu - dong 98-104 detail.component.html,
                    // truoc day thieu, chi con chu "Xem toàn bộ ảnh"/"See all". ?>
                    <a class="btn-show-photos" href="<?php echo esc_url($photos[0]['url']); ?>" data-photo-index="0" target="_blank" rel="noopener">
                        <svg width="24" height="21" viewBox="0 0 24 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M24 18V0H0V18H10.5V19.5H6V21H18V19.5H13.5V18H24ZM1.5 16.5V1.5H22.5V16.5H1.5Z" fill="black" />
                        </svg>
                        <span class="margin-left-10"><?php echo esc_html(pplus_t('Xem toàn bộ ảnh', 'See all')); ?></span>
                    </a>
                <?php endif; ?>
            </div>

            <?php // Gallery rieng cho mobile (duoi 800px, xem office-detail-component.css dong 552-
            // 574: ".photoContainer" tu an, ".photoContainer-mobile" hien thanh dai anh cuon ngang) -
            // port tu detail.component.html dong 110-115. CSS da co san tu truoc nhung markup thi
            // chua, nen tren mobile KHONG THAY ANH NAO CA (ca ".photoContainer" lan thay the deu
            // an) - bug thuc te. Dung chung 1 data-photos + cung selector click (main.js) voi
            // ".photoContainer" de mo lai dung lightbox Fotorama. ?>
            <?php if (!empty($photos)) : ?>
                <div class="photoContainer-mobile" data-photos="<?php echo esc_attr(wp_json_encode($fotorama_photos)); ?>">
                    <?php foreach ($photos as $i => $photo) : ?>
                        <a class="photoLink" href="<?php echo esc_url($photo['url']); ?>" data-photo-index="<?php echo (int) $i; ?>" target="_blank" rel="noopener">
                            <img src="<?php echo esc_url($photo['url']); ?>" alt="<?php echo esc_attr($photo['alt'] ?? ($model['projectName'] ?? '')); ?>" loading="lazy" />
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-md-12">
        <div class="content-detail-1">
            <div class="title-1">
                <div class="title-1-c">
                    <h1><?php echo esc_html($model['projectName'] ?? ''); ?></h1>
                    <label><?php echo esc_html(sprintf(pplus_t('Hạng %s', 'Grade %s'), $model['ratingLevel'] ?? '')); ?></label>
                </div>
                <div class="title-adress"><?php echo esc_html($model['address'] ?? ''); ?></div>
            </div>
            <?php // Icon "Xuat PDF/Chia se/Yeu thich" (dong 131-171 detail.component.html), noi qua
            // assets/js/pplus-favourite.js (delegate click ".export-favorite"/".share-link"/".item-like",
            // dung CHUNG payload "data-office" nay - cung field/shape voi parts/office-card.php de
            // ".item-like" (handler co san, khong doi) hoat dong ngay khong can code rieng). "urlHerf"
            // dung REQUEST_URI (dang O SAN trang nay) + $lang_prefix (dong 27) - PHAI tu noi lai tien
            // to "/en", vi inc/language-route.php da STRIP han "/en" khoi $_SERVER['REQUEST_URI'] tu
            // dau (truoc khi file nay chay, xem ghi chu trong do), neu dung REQUEST_URI tho thi link
            // luu vao bo suu tap tu trang EN se thanh URL VI (thieu "/en") - bug thuc te da gap khi
            // kiem thu ban EN (Pearl 5 Building). ?>
            <?php $favourite_payload = [
                'projectId'     => $model['projectId'] ?? '',
                'projectName'   => $model['projectName'] ?? '',
                'address'       => $model['address'] ?? '',
                'ratingLevel'   => $model['ratingLevel'] ?? '',
                'itemPrice'     => trim(($model['fromLeaseFeeString'] ?? '') . '$/' . $unit),
                'urlHerf'       => esc_url_raw($lang_prefix . (parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '')),
                'photoList'     => !empty($photos[0]['url']) ? [$photos[0]['url']] : [],
                'PhotoListBaSE' => $photos[0]['url'] ?? '',
                'officeType'    => $model['subType'] ?? 0,
            ]; ?>
            <div class="actine-share" data-office="<?php echo esc_attr(wp_json_encode($favourite_payload)); ?>">
                <button type="button" class="btn export-favorite" aria-label="<?php echo esc_attr(pplus_t('Xuất PDF', 'Export PDF')); ?>">
                    <i>
                        <svg style="width: 18px; margin-left: 5px; margin-top: -2px;" fill="#61A2D8" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512">
                            <path d="M32 64C32 28.7 60.7 0 96 0H256V128c0 17.7 14.3 32 32 32H416V288H248c-13.3 0-24 10.7-24 24s10.7 24 24 24H416V448c0 35.3-28.7 64-64 64H96c-35.3 0-64-28.7-64-64V64zM416 336V288H526.1l-39-39c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l80 80c9.4 9.4 9.4 24.6 0 33.9l-80 80c-9.4 9.4-24.6 9.4-33.9 0s-9.4-24.6 0-33.9l39-39H416zm0-208H288V0L416 128z" />
                        </svg>
                    </i>
                </button>
                <button type="button" class="btn share-link" aria-label="<?php echo esc_attr(pplus_t('Chia sẻ', 'Share')); ?>">
                    <i>
                        <svg style="margin-top: -4px;" width="16" height="16" viewBox="0 0 18 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M15 20C14.1667 20 13.4583 19.7083 12.875 19.125C12.2917 18.5417 12 17.8333 12 17C12 16.8833 12.0083 16.7623 12.025 16.637C12.0417 16.5117 12.0667 16.3993 12.1 16.3L5.05 12.2C4.76667 12.45 4.45 12.646 4.1 12.788C3.75 12.93 3.38333 13.0007 3 13C2.16667 13 1.45833 12.7083 0.875 12.125C0.291667 11.5417 0 10.8333 0 10C0 9.16667 0.291667 8.45833 0.875 7.875C1.45833 7.29167 2.16667 7 3 7C3.38333 7 3.75 7.071 4.1 7.213C4.45 7.355 4.76667 7.55067 5.05 7.8L12.1 3.7C12.0667 3.6 12.0417 3.48767 12.025 3.363C12.0083 3.23833 12 3.11733 12 3C12 2.16667 12.2917 1.45833 12.875 0.875C13.4583 0.291667 14.1667 0 15 0C15.8333 0 16.5417 0.291667 17.125 0.875C17.7083 1.45833 18 2.16667 18 3C18 3.83333 17.7083 4.54167 17.125 5.125C16.5417 5.70833 15.8333 6 15 6C14.6167 6 14.25 5.92933 13.9 5.788C13.55 5.64667 13.2333 5.45067 12.95 5.2L5.9 9.3C5.93333 9.4 5.95833 9.51267 5.975 9.638C5.99167 9.76333 6 9.884 6 10C6 10.1167 5.99167 10.2377 5.975 10.363C5.95833 10.4883 5.93333 10.6007 5.9 10.7L12.95 14.8C13.2333 14.55 13.55 14.3543 13.9 14.213C14.25 14.0717 14.6167 14.0007 15 14C15.8333 14 16.5417 14.2917 17.125 14.875C17.7083 15.4583 18 16.1667 18 17C18 17.8333 17.7083 18.5417 17.125 19.125C16.5417 19.7083 15.8333 20 15 20Z"
                                fill="url(#pplus-share-gradient)" />
                            <defs>
                                <linearGradient id="pplus-share-gradient" x1="0" y1="0" x2="16.3444" y2="22.0185" gradientUnits="userSpaceOnUse">
                                    <stop stop-color="#B581DB" />
                                    <stop offset="1" stop-color="#70A0D4" />
                                </linearGradient>
                            </defs>
                        </svg>
                    </i>
                </button>
                <button type="button" class="btn item-like save-link" aria-label="<?php echo esc_attr(pplus_t('Yêu thích', 'Favourite')); ?>">
                    <i>
                        <svg width="17" height="17" viewBox="0 0 17 17" fill="#61A2D8" xmlns="http://www.w3.org/2000/svg">
                            <path d="M8.69082 13.3884L8.49082 13.2134C3.94082 9.51335 2.71582 8.21335 2.71582 6.08835C2.71582 4.33835 4.14082 2.93835 5.86582 2.93835C7.31582 2.93835 8.14082 3.76335 8.69082 4.38835C9.24082 3.76335 10.0658 2.93835 11.5158 2.93835C13.2658 2.93835 14.6658 4.36335 14.6658 6.08835C14.6658 8.21335 13.4408 9.51335 8.89082 13.2134L8.69082 13.3884ZM5.86582 3.58835C4.49082 3.58835 3.36582 4.71335 3.36582 6.08835C3.36582 7.91335 4.51582 9.11335 8.69082 12.5384C12.8658 9.11335 14.0158 7.91335 14.0158 6.08835C14.0158 4.71335 12.8908 3.58835 11.5158 3.58835C10.2658 3.58835 9.59082 4.33835 9.06582 4.93835L8.69082 5.36335L8.31582 4.93835C7.79082 4.33835 7.11582 3.58835 5.86582 3.58835Z"
                                fill="#134563" />
                        </svg>
                    </i>
                </button>
            </div>
        </div>
    </div>

    <div class="col-md-8 detail-content">
        <div class="card table-price-mobile">
            <table>
                <tbody>
                    <tr>
                        <td class="text-left title-1"><?php echo esc_html(pplus_t('Khoảng giá', 'Price range')); ?></td>
                        <td class="text-right title-3"><?php echo esc_html($model['fromLeaseFeeString'] ?? ''); ?>$/<?php echo esc_html($unit); ?></td>
                    </tr>
                    <tr>
                        <td class="text-left title-1"><?php echo esc_html(pplus_t('Phí dịch vụ', 'Service charge')); ?></td>
                        <td class="text-right title-3"><?php echo esc_html($model['managerFeeString'] ?? ''); ?>$/<?php echo esc_html($unit); ?></td>
                    </tr>
                    <tr>
                        <td class="text-left title-1"><span class="title-2"><?php echo esc_html(pplus_t('Tổng giá', 'Total price')); ?></span></td>
                        <td class="text-right title-4"><?php echo esc_html($model['fromTotalAmtString'] ?? ''); ?>$/<?php echo esc_html($unit); ?></td>
                    </tr>
                    <tr>
                        <td class="text-left title-1" colspan="2">
                            <span class="title-5">
                                <?php echo !empty($model['isVat'])
                                    ? esc_html(pplus_t('(Đã bao gồm phí dịch vụ, chưa bao gồm VAT)', '(Including service charge, excluding VAT)'))
                                    : esc_html(pplus_t('(Đã bao gồm 10% VAT)', '(Including 10% VAT)')); ?>
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <?php if ($is_vpdv && !empty($model['projectPriceList'])) : ?>
            <?php // Bang gia theo tung hang muc dich vu (Van phong rieng/Cho ngoi co dinh/Cho ngoi
            // linh hoat/Dang ky kinh doanh) - dong 210-224 detail.component.html, truoc day thieu
            // hoan toan. Ten hang muc dung pplus_office_type_name() (inc/api-client.php) - LUON
            // tieng Viet du dang /en/, dung y het Angular goc (GlobalConstants.ts OfficeTypeList
            // khong co ban tieng Anh - khong phai thieu sot cua ban port nay). ?>
            <div class="data-content data-content-vp">
                <?php foreach ($model['projectPriceList'] as $price_item) :
                    // "unitEn"/"remarksEn" - dung khi API co gia tri (khong phai moi listing deu co,
                    // xac nhan qua thuc te: co listing null, co listing khac da dien) - fallback ve
                    // ban tieng Viet neu rong, tranh o trong hoan toan khi chua co ban dich.
                    $price_unit    = (pplus_current_lang() === 'en' && !empty($price_item['unitEn'])) ? $price_item['unitEn'] : ($price_item['unit'] ?? '');
                    $price_remark  = (pplus_current_lang() === 'en' && !empty($price_item['remarksEn'])) ? $price_item['remarksEn'] : ($price_item['remarks'] ?? '');
                ?>
                    <div class="data-content-item">
                        <div class="collapse-tile">
                            <div class="title-span"><?php echo esc_html(pplus_office_type_name($price_item['officeType'] ?? 0)); ?></div>
                        </div>
                        <div class="collapse-body">
                            <div class="item-vp-price">
                                <img class="icon" src="<?php echo esc_url(get_template_directory_uri() . '/assets/officebase/Common/Images/About/icon-content-vp.svg'); ?>" alt="detail" />
                                <div class="icon-lable"><?php echo esc_html(pplus_t('Từ', 'From')); ?> <?php echo esc_html(number_format((float) ($price_item['fromPrice'] ?? 0), 0, ',', '.')); ?> <?php echo esc_html($price_unit); ?></div>
                            </div>
                            <div class="item-vp-content"><?php echo esc_html($price_remark); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="group-content">
            <div class="content-detail">
                <div class="collapse-tile"><span><?php echo esc_html(pplus_t('Giới thiệu dự án', 'Building information')); ?></span></div>

                <?php
                // Icon "img-service-N.png" (Content/Img-Icon-Detail/) - doi chieu tung truong theo
                // dung detail.component.html dong 257-380 (truoc day thieu hoan toan, chi con label).
                $field_icons = [
                    'ownerInfo'           => 'img-service-3.png',
                    'releaseDateString'   => 'img-service-12.png',
                    'pmCompany'           => 'img-service-2.png',
                    'projectSize'         => 'img-service-13.png',
                    'fLoorAreas'          => 'img-service-11.png',
                    'airConditionInfo'    => 'img-service-4.png',
                    'ceilingHeight'       => 'img-service-1.png',
                    'powerGeneratorInfo'  => 'img-service-9.png',
                    'parkingAvailability' => 'img-service-6.png',
                    'elevatorInfoString'  => 'img-service-14.png',
                ];
                ?>
                <?php if ($is_vpdv) : ?>
                    <div class="collapse-body row show">
                        <div class="col-md-12 service-item">
                            <div class="service-item-name service-item-name-vpdv">
                                <div class="div-img"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img-Icon-Detail/' . $field_icons['ownerInfo']); ?>" alt="detail" /></div>
                                <label><?php echo esc_html(pplus_t('Chủ đầu tư', 'The landlord')); ?></label>
                            </div>
                            <div class="service-item-value service-item-value-vpdv"><?php echo esc_html($model['ownerInfo'] ?? ''); ?></div>
                        </div>
                        <div class="col-md-12 service-item">
                            <div class="service-item-name service-item-name-vpdv">
                                <div class="div-img"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img-Icon-Detail/' . $field_icons['projectSize']); ?>" alt="detail" /></div>
                                <label><?php echo esc_html(pplus_t('Quy mô toà nhà', 'Building scale')); ?></label>
                            </div>
                            <div class="service-item-value service-item-value-vpdv"><?php echo esc_html($model['projectSize'] ?? ''); ?></div>
                        </div>
                    </div>
                <?php else :
                    $fields = [
                        'ownerInfo'         => pplus_t('Chủ đầu tư', 'The landlord'),
                        'releaseDateString' => pplus_t('Ngày hoàn tất', 'Complete date'),
                        'pmCompany'         => pplus_t('Đơn vị quản lý', 'Management by'),
                        'projectSize'       => pplus_t('Quy mô toà nhà', 'Building scale'),
                        'fLoorAreas'        => pplus_t('Diện tích mỗi sàn', 'Typical floor plate'),
                        'airConditionInfo'  => pplus_t('Điều hoà', 'Air-conditional system'),
                        'ceilingHeight'     => pplus_t('Chiều cao trần', 'Ceiling height'),
                        'powerGeneratorInfo'=> pplus_t('Máy phát điện', 'Back up power'),
                        'parkingAvailability' => pplus_t('Khu vực để xe', 'Basement/Parking'),
                        'elevatorInfoString'  => pplus_t('Thang máy', 'Elevators'),
                    ];
                    ?>
                    <div class="collapse-body row show">
                        <?php foreach ($fields as $key => $label) : ?>
                            <div class="col-md-6 service-item">
                                <div class="service-item-name">
                                    <div class="div-img"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img-Icon-Detail/' . $field_icons[$key]); ?>" alt="detail" /></div>
                                    <label><?php echo esc_html($label); ?></label>
                                </div>
                                <div class="service-item-value"><?php echo esc_html($model[$key] ?? ''); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($is_vpdv && !empty($model['utilityList'])) : ?>
                <hr />
                <div class="content-detail content-detail-vpdv">
                    <div class="collapse-tile"><span><?php echo esc_html(pplus_t('Tiện ích đã bao gồm trong giá', 'Amenities included in the price')); ?></span></div>
                    <div class="collapse-body row show">
                        <?php foreach ($model['utilityList'] as $utility_item) : ?>
                            <div class="col-md-6 service-item">
                                <div class="service-item-name"><label><?php echo esc_html($utility_item['serviceName'] ?? ''); ?></label></div>
                                <div class="service-item-value"><?php echo esc_html($utility_item['value'] ?? ''); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($model['serviceIncludedList'])) : ?>
                <hr />
                <div class="content-detail">
                    <div class="collapse-tile"><span><?php echo esc_html(pplus_t('Các khoản chi phí thuê văn phòng', 'Office leasing expenses')); ?></span></div>
                    <div class="collapse-body row show">
                        <?php foreach ($model['serviceIncludedList'] as $item) :
                            if (empty($item['value']) || $item['value'] === 'NULL') continue; ?>
                            <div class="col-md-6 service-item">
                                <div class="service-item-name">
                                    <?php if (!empty($item['iconOn'])) : ?>
                                        <div class="div-img"><img src="<?php echo esc_attr($item['iconOn']); ?>" alt="<?php echo esc_attr($item['serviceName'] ?? ''); ?>" /></div>
                                    <?php endif; ?>
                                    <label><?php echo esc_html($item['serviceName'] ?? ''); ?></label>
                                </div>
                                <div class="service-item-value"><?php echo esc_html($item['value']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php // Angular: "*ngIf="Model.dataSelected != undefined"" - KHONG dieu kien theo content
        // rong/day (dataSelected LUON ton tai neu trang khong 404), khac voi truoc day chi hien
        // khoi nay khi co bai viet, an mat ca tieu de "Thông tin văn phòng" cho nhung listing chua
        // co bai viet (vd cac listing van phong dich vu, thuong chi co "Giới thiệu dự án" ngan). ?>
        <?php if (!empty($model['dataSelected'])) : ?>
            <div class="data-content">
                <div class="data-title-5"><?php echo esc_html(pplus_t('Thông tin văn phòng', 'Office information')); ?></div>
                <?php if (!empty($model['dataSelected']['content'])) : ?>
                    <?php pplus_render_toc_nested($toc['items']); ?>
                    <div class="content-1 table-mg blog-p-no-margin" id="content-ckedit">
                        <?php echo pplus_kses_content($toc['html']); ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($model['lat']) && !empty($model['lng'])) : ?>
            <div class="pp-detail-map">
                <div class="data-title-5"><?php echo esc_html(pplus_t('Bản đồ', 'Map')); ?></div>
                <div class="col-12">
                    <div class="project_map">
                        <div id="map-detail" style="height: 40vh; width: 100%"
                             data-lat="<?php echo esc_attr($model['lat']); ?>"
                             data-lng="<?php echo esc_attr($model['lng']); ?>"
                             data-title="<?php echo esc_attr($model['projectName'] ?? ''); ?>"
                             data-rating="<?php echo esc_attr($model['ratingLevel'] ?? ''); ?>"></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-md-4 detail-right">
        <div class="card">
            <?php // Bang gia (ban desktop, hien trong sidebar) - TRUNG voi bang ".table-price-mobile"
            // o col-md-8 phia tren (chi hien tren mobile qua CSS), khong phai trung lap thua - dung
            // y het detail.component.html dong 447-483 (2 ban cho 2 breakpoint). ?>
            <div class="table-price table-mobile">
                <table>
                    <tbody>
                        <tr>
                            <td class="text-left title-1"><?php echo esc_html(pplus_t('Khoảng giá', 'Price range')); ?></td>
                            <td class="text-right title-3"><?php echo esc_html($model['fromLeaseFeeString'] ?? ''); ?>$/<?php echo esc_html($unit); ?></td>
                        </tr>
                        <tr>
                            <td class="text-left title-1"><?php echo esc_html(pplus_t('Phí dịch vụ', 'Service charge')); ?></td>
                            <td class="text-right title-3"><?php echo esc_html($model['managerFeeString'] ?? ''); ?>$/<?php echo esc_html($unit); ?></td>
                        </tr>
                        <tr>
                            <td class="text-left title-1"><span class="title-2"><?php echo esc_html(pplus_t('Tổng giá', 'Total price')); ?></span></td>
                            <td class="text-right title-4"><?php echo esc_html($model['fromTotalAmtString'] ?? ''); ?>$/<?php echo esc_html($unit); ?></td>
                        </tr>
                        <tr>
                            <td class="text-left title-1" colspan="2">
                                <span class="title-5">
                                    <?php echo !empty($model['isVat'])
                                        ? esc_html(pplus_t('(Đã bao gồm phí dịch vụ, chưa bao gồm VAT)', '(Including service charge, excluding VAT)'))
                                        : esc_html(pplus_t('(Đã bao gồm 10% VAT)', '(Including 10% VAT)')); ?>
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($model['listUser'])) : ?>
                <hr class="table-mobile" />
                <div class="list-user">
                    <?php foreach ($model['listUser'] as $item) : ?>
                        <div class="item">
                            <div><img src="<?php echo esc_url(pplus_admin_asset_url($item['img'] ?? '')); ?>" alt="<?php echo esc_attr($item['title'] ?? ''); ?>" loading="lazy" /></div>
                            <div class="item-content">
                                <label class="text-1"><?php echo esc_html($item['title'] ?? ''); ?></label>
                                <br />
                                <div class="text-2"><?php echo pplus_kses_content($item['content'] ?? ''); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <hr class="table-mobile" />

            <div class="contact-us">
                <div class="contact-head">
                    <div class="title"><?php echo esc_html(pplus_t('Bạn đang quan tâm', 'You are interested in')); ?></div>
                    <div class="name"><?php echo esc_html($model['dataSelected']['title'] ?? ($model['projectName'] ?? '')); ?></div>
                </div>
                <form class="pplus-advise-form">
                    <div class="mb-3"><input class="form-control" type="text" name="OwnerName" placeholder="<?php echo esc_attr(pplus_t('Họ và tên của bạn ...', 'Your full name ...')); ?>" /></div>
                    <div class="mb-3"><input class="form-control" type="email" name="OwnerEmail" placeholder="<?php echo esc_attr(pplus_t('Email của bạn ...', 'Your email ...')); ?>" /></div>
                    <div class="mb-3"><input class="form-control" type="tel" name="OwnerMobile" placeholder="<?php echo esc_attr(pplus_t('Số điện thoại ...', 'Your phone number ...')); ?>" /></div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="pplusOwnerCheck" />
                        <label class="form-check-label" for="pplusOwnerCheck"><?php echo esc_html(pplus_t('Tôi muốn nhận thông tin từ Property Plus', 'I want to receive information from Property Plus')); ?></label>
                    </div>
                    <p><?php echo esc_html(pplus_t('Tư vấn miễn phí - Giá thuê tốt nhất - Gửi báo giá nhanh chóng', 'Free consultation - Best rental price - Send quotes quickly')); ?></p>
                    <button type="submit" class="btn btn-primary mb-3"><?php echo esc_html(pplus_t('Gửi yêu cầu tư vấn', 'Send request')); ?></button>
                    <a href="tel:0865364866" class="btn btn-default phone-contact-1"><?php echo esc_html(pplus_t('Hoặc gọi', 'Or call')); ?> 0865.364.866</a>
                </form>
            </div>
        </div>
    </div>

    <?php if (!empty($nearby['listOffice'])) : ?>
        <div class="col-md-12">
            <div class="vp-list">
                <div class="text-title"><?php echo esc_html(pplus_t('Văn phòng gần đó', 'Nearby offices')); ?></div>
                <div class="result-box-detail">
                    <div class="list-product-office full-content">
                        <?php foreach ($nearby['listOffice'] as $item) : ?>
                            <?php get_template_part('parts/office-card', null, ['item' => $item]); ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php get_footer(); ?>
