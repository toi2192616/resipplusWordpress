<?php
/**
 * A2 - Chi tiet 1 can ho: /cho-thue-can-ho/{province}/{district}/{project}/{id}/. Route dang ky
 * trong inc/apartment-detail-route.php (khong phai Page/Template - URL 4 doan dong).
 *
 * Port tu D:\SoureceCode\PPlusAngularResi\src\app\modules\office\detail\detail.component.html
 * (521 dong that). Doi chieu truc tiep qua curl API that (Apartment/_GetDetail?id=3267&...) truoc
 * khi viet, KHONG doan qua .ts (xem docs/PORTING_NOTES.md).
 *
 * Cac phan CO trong response API nhung KHONG dung toi trong .html/.ts that (xac nhan qua grep toan
 * bo 2 file goc, xem docblock pplus_get_apartment_detail() trong inc/api-client.php) - KHONG bia
 * them, giu dung nhu ban goc:
 * - dataSelected/projectDetail/otherApartmentInProject/servicePrice: field CHET, ban that KHONG
 *   render "Thông tin văn phòng"/TOC/"gần đó" nao ca tren trang can ho (khac han A2 cua Van phong -
 *   page-office-detail.php CO cac phan nay that su, vi Office/_GetDetail CO dung toi).
 * - schoolList: field CHET, khong co UI nao doc no.
 *
 * TODO (ghi ro, khong chan hien thi - dung quy uoc da dung o moi file khac trong theme nay):
 * - "Dự án" > "Xem thêm" (LinkProject) tro toi trang "Dự án cụ thể": DA XONG 30/9 - xem
 *   inc/project-detail-route.php + page-project-detail.php.
 * - Ban do chi ve 1 marker DUY NHAT (vi tri chinh can ho, qua #map-detail data-lat/data-lng co san
 *   trong assets/js/pplus-maps.js) - KHONG ve them cac diem "Tiện ích xung quanh" tu Model.markers
 *   nhu ban Angular that (~20 marker/trang), giong huong don gian da chon cho page-reference.php.
 * - Nut Yeu thich/Xuat PDF/Chia se (nhu A2 Van phong) CHUA noi cho can ho (Angular that cung KHONG
 *   co 3 nut nay tren trang can ho - chi co ".btn-check-like" tinh, khong co logic that trong
 *   detail.component.ts - dung y het, khong bia them).
 */
if (!defined('ABSPATH')) exit;

$model  = pplus_get_apartment_detail_from_query_vars();
$detail = $model['apartmentDetail'] ?? null;

if (!$detail) {
    status_header(404);
    get_header();
    echo '<div class="container" style="padding:40px 0;">';
    pplus_render_api_error();
    echo '</div>';
    get_footer();
    return;
}

$lang_prefix = pplus_lang_prefix();

// photoList o day la MANG CHUOI URL truc tiep (khac han Office: mang {url,alt}) - xac nhan qua
// curl truc tiep. "\" trong duong dan (bug .NET goc) da duoc pplus_admin_asset_url() tu chuan hoa.
$photos = array_values(array_filter(array_map(
    static fn($p) => pplus_admin_asset_url((string) $p),
    $detail['photoList'] ?? []
)));
$fotorama_photos = array_map(static fn(string $url): array => ['img' => $url], $photos);

// 23/10/2026 (port tu ban Van phong 3759b6c): video YouTube o o anh lon .c1 tai NGAY trinh phat (~860KB JS/CSS
// + cookie ben thu ba) du nguoi dung khong bam phat. Thay iframe bang "facade" (thumbnail + nut phat, bam moi tao
// iframe autoplay). Chi ap dung voi link embed YouTube dung dinh dang; link la giu iframe nhung loading="lazy".
$pplus_yt_id = '';
if (!empty($detail['videoUrl']) && preg_match('~^https?://(?:www\.)?youtube(?:-nocookie)?\.com/embed/([A-Za-z0-9_-]{6,20})(?:[/?#]|$)~i', (string) $detail['videoUrl'], $pplus_yt_match) === 1) {
    $pplus_yt_id = $pplus_yt_match[1];
}

// Phan tu LCP (Lighthouse mobile: anh .c1 - "Load Delay" 2,3s vi chi duoc phat hien sau CSS/HTML dau trang).
// Preload trong <head> de tai song song voi CSS; co video thi preload thumbnail cua video thay anh dau.
$pplus_lcp_image = $pplus_yt_id !== '' ? 'https://i.ytimg.com/vi/' . $pplus_yt_id . '/hqdefault.jpg'
    : (empty($detail['videoUrl']) && !empty($photos[0]) ? $photos[0] : '');
if ($pplus_lcp_image !== '') {
    add_action('wp_head', static function () use ($pplus_lcp_image) {
        printf('<link rel="preload" as="image" href="%s" fetchpriority="high" />' . "\n", esc_url($pplus_lcp_image));
    }, 2);
}

$project_id   = (int) ($detail['projectId'] ?? 0);
$district_url = home_url($lang_prefix . '/' . pplus_office_url('cho-thue-can-ho/ha-noi/' . ($detail['alasDistrict'] ?? '')));
// LinkProject - xem TODO dau file (trang "Dự án cụ thể" chua co route, se 404 khi bam).
$link_project = home_url($lang_prefix . '/' . pplus_office_url('cho-thue-can-ho/ha-noi/' . ($detail['alasDistrict'] ?? '') . '/' . ($detail['alasProject'] ?? '') . '-' . $project_id));

add_filter('pplus_language_switch_url', function (string $default, string $target_lang) use ($detail): string {
    if ($target_lang === 'en' || $target_lang === 'ja') {
        $base = $detail['alasUrlBase'] ?? '';
        return $base ? home_url(($target_lang === 'ja' ? '/ja' : '/en') . '/rental-apartment' . $base) : $default;
    }
    $base_vi = $detail['alasUrlBaseVi'] ?? '';
    return $base_vi ? home_url('/cho-thue-can-ho' . $base_vi) : $default;
}, 10, 2);

// Mobile: thanh gia + nut Lien he (.pplus-price-bar, cuoi trang) thay menu chan trang. "hidden-bottom" la class
// co san (footer-component.css) an .footer-mobile duoi 800px.
add_filter('body_class', static fn(array $classes): array => array_merge($classes, ['hidden-bottom', 'pplus-has-price-bar']));

get_header();
?>

<?php // Hang goi y "hot" - dung ham chung, highlight dung quan cua can ho dang xem (them vao,
// khong co trong ban goc - Angular that KHONG co suggest-header tren trang A2, chi co tren cac
// trang co "isShowUrlMenu=true" LAN "co danh sach" nhu A1/A3/A4 - o day them vao de dieu huong
// nhat quan voi phan con lai cua site, giong quyet dinh da lam cho page-reference.php). ?>
<?php pplus_render_suggest_header($detail['alasDistrict'] ?? null); ?>

<div class="container office-detail row">
    <?php // Khong co nut tron "<" (url-callBack): detail.component.html dong 35-39 cua ban goc de BI COMMENT -
    // bo 30/9/2026 theo yeu cau "giong ban goc" (tren mobile no de len breadcrumb). ?>
    <div class="url-box-content">
        <div class="container">
            <div class="url-content">
                <div class="url-content-list">
                    <a href="<?php echo esc_url(home_url('/')); ?>"><?php echo esc_html(pplus_localize('Trang chủ')); ?></a>
                    <a href="<?php echo esc_url($district_url); ?>"><?php echo pplus_breadcrumb_arrow(); ?><?php echo esc_html($detail['provinceName'] ?? ''); ?></a>
                    <a href="<?php echo esc_url($link_project); ?>"><?php echo pplus_breadcrumb_arrow(); ?><?php echo esc_html($detail['projectName'] ?? ''); ?></a>
                    <span><?php echo pplus_breadcrumb_arrow(); ?><?php echo esc_html($detail['title'] ?? ''); ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-12">
        <div class="_info basic">
            <div class="photoContainer" data-photos="<?php echo esc_attr(wp_json_encode($fotorama_photos)); ?>">
                <?php if (!empty($detail['videoUrl'])) : ?>
                    <div class="c1">
                        <div class="photo1-ctn">
                            <div class="youtube">
                                <?php if ($pplus_yt_id !== '') : ?>
                                    <button type="button" class="yt-facade" data-yt-embed="<?php echo esc_url('https://www.youtube.com/embed/' . $pplus_yt_id . '?autoplay=1&rel=0'); ?>" aria-label="<?php echo esc_attr(pplus_t('Phát video', 'Play video')); ?>">
                                        <img src="<?php echo esc_url('https://i.ytimg.com/vi/' . $pplus_yt_id . '/hqdefault.jpg'); ?>" alt="<?php echo esc_attr($detail['projectName'] ?? ''); ?>" width="480" height="360" fetchpriority="high" decoding="async" />
                                        <span class="yt-play" aria-hidden="true"></span>
                                    </button>
                                    <noscript><iframe width="560" height="315" src="<?php echo esc_url($detail['videoUrl']); ?>" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></noscript>
                                    <script>
                                    document.addEventListener('click', function (e) {
                                        var b = e.target.closest ? e.target.closest('.yt-facade') : null;
                                        if (!b) { return; }
                                        var f = document.createElement('iframe');
                                        f.src = b.getAttribute('data-yt-embed');
                                        f.title = 'YouTube video player';
                                        f.width = '560'; f.height = '315';
                                        f.setAttribute('frameborder', '0');
                                        f.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture');
                                        f.setAttribute('allowfullscreen', '');
                                        b.parentNode.replaceChild(f, b);
                                    });
                                    </script>
                                <?php else : ?>
                                    <iframe width="560" height="315" src="<?php echo esc_url($detail['videoUrl']); ?>" title="YouTube video player" frameborder="0" loading="lazy"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php elseif (!empty($photos[0])) : ?>
                    <div class="c1">
                        <div class="photo1-ctn">
                            <a class="photoLink" href="<?php echo esc_url($photos[0]); ?>" data-photo-index="0" target="_blank" rel="noopener">
                                <img src="<?php echo esc_url($photos[0]); ?>" alt="<?php echo esc_attr($detail['projectName'] ?? ''); ?>" fetchpriority="high" />
                            </a>
                        </div>
                    </div>
                <?php else : ?>
                    <div class="c1">
                        <div class="photo1-ctn">
                            <a class="photoLink">
                                <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/officebase/Common/Images/no-image.png'); ?>" alt="" />
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php $thumb_offset = empty($detail['videoUrl']) ? 1 : 0; ?>
                <div class="c2">
                    <?php for ($i = $thumb_offset; $i < $thumb_offset + 2; $i++) : if (empty($photos[$i])) continue; ?>
                        <div class="photo<?php echo ($i - $thumb_offset + 2); ?>-ctn">
                            <a class="photoLink" href="<?php echo esc_url($photos[$i]); ?>" data-photo-index="<?php echo (int) $i; ?>" target="_blank" rel="noopener">
                                <img src="<?php echo esc_url($photos[$i]); ?>" alt="" loading="lazy" />
                            </a>
                        </div>
                    <?php endfor; ?>
                </div>
                <div class="c3">
                    <?php for ($i = $thumb_offset + 2; $i < $thumb_offset + 4; $i++) : if (empty($photos[$i])) continue; ?>
                        <div class="photo<?php echo ($i - $thumb_offset + 2); ?>-ctn">
                            <a class="photoLink" href="<?php echo esc_url($photos[$i]); ?>" data-photo-index="<?php echo (int) $i; ?>" target="_blank" rel="noopener">
                                <img src="<?php echo esc_url($photos[$i]); ?>" alt="" loading="lazy" />
                            </a>
                        </div>
                    <?php endfor; ?>
                </div>
                <?php if (!empty($photos[0])) : ?>
                    <a class="btn-show-photos" href="<?php echo esc_url($photos[0]); ?>" data-photo-index="0" target="_blank" rel="noopener">
                        <svg width="24" height="21" viewBox="0 0 24 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M24 18V0H0V18H10.5V19.5H6V21H18V19.5H13.5V18H24ZM1.5 16.5V1.5H22.5V16.5H1.5Z" fill="black" />
                        </svg>
                        <span class="margin-left-10"><?php echo esc_html(pplus_localize('Xem toàn bộ ảnh')); ?></span>
                    </a>
                <?php endif; ?>
            </div>
            <?php // Khong co ".photoContainer-mobile" (dai anh cuon ngang): do la markup cua ban Van phong, ban goc
            // Resi chi hien 1 anh lon .c1 + nut "Xem toan bo anh" tren mobile (.c2/.c3 tu an bang CSS). ?>
        </div>
    </div>

    <div class="col-md-8 detail-content">
        <div class="group-content">
            <div class="content-detail">
                <div class="_header">
                    <div class="_titlePage"><?php echo esc_html($detail['apartmentNameDisp'] ?? ($detail['title'] ?? '')); ?></div>
                    <div class="_contentid">
                        <div class="title-id"><?php echo esc_html(pplus_localize('ID căn hộ')); ?></div>
                        <div class="content-id"><?php echo esc_html($detail['apartmentId'] ?? ''); ?></div>
                    </div>
                </div>

                <div class="project-info">
                    <div>
                        <i class="fa fa-link"></i>
                        <?php echo esc_html(pplus_localize('Dự án')); ?>
                        <span class="font-bold"><?php echo esc_html($detail['projectName'] ?? ''); ?></span>
                    </div>
                    <a class="project_link" href="<?php echo esc_url($link_project); ?>">
                        <?php echo esc_html(pplus_localize('Xem thêm')); ?>
                        <i class="fa fa-angle-double-right"></i>
                    </a>
                </div>

                <div class="_titleInfo"><?php echo esc_html(pplus_localize('Thông tin căn hộ')); ?></div>
                <div class="info-grid apartment-grid">
                    <div class="ttcoban">
                        <div class="from-icon"><i class="dientich-ico ttcoban-ico"></i></div>
                        <div class="ttcoban_info">
                            <div class="ttcoban_name"><?php echo esc_html(pplus_localize('Diện tích')); ?> (m²)</div>
                            <div class="ttcoban_detail"><?php echo esc_html($detail['area'] ?? ''); ?></div>
                        </div>
                    </div>
                    <div class="ttcoban">
                        <div class="from-icon"><i class="bontam-ico miw-40px ttcoban-ico"></i></div>
                        <div class="ttcoban_info">
                            <div class="ttcoban_name"><?php echo esc_html(pplus_localize('Phòng tắm')); ?></div>
                            <div class="ttcoban_detail"><?php echo esc_html($detail['bathRoomQty'] ?? ''); ?></div>
                        </div>
                    </div>
                    <div class="ttcoban">
                        <div class="from-icon"><i class="phongngu-ico miw-40px ttcoban-ico"></i></div>
                        <div class="ttcoban_info">
                            <div class="ttcoban_name"><?php echo esc_html(pplus_localize('Phòng ngủ')); ?></div>
                            <div class="ttcoban_detail"><?php echo esc_html($detail['bedRoomQty'] ?? ''); ?></div>
                        </div>
                    </div>
                    <div class="ttcoban">
                        <div class="from-icon">
                            <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/icon-detail-1.png'); ?>" width="100" height="100" alt="" style="width: 45px; height:auto; margin-left: 8px; margin-top: 6px;" />
                        </div>
                        <div class="ttcoban_info">
                            <div class="ttcoban_name"><?php echo esc_html(pplus_localize('Tiện nghi')); ?></div>
                            <?php // Gia tri o day RONG ca trong ban that (detail.component.html dong 234-237:
                            // FurnitureType.IsValidEnumValue()/ToDisplayText la snippet Razor CU con sot lai
                            // duoi dang comment HTML trong chinh Angular, khong thuc su chay) - khong bia them
                            // gia tri, giu dung nhu ban goc (chi icon + nhan, khong co so lieu). ?>
                            <div class="ttcoban_detail"></div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($detail['description'])) : ?>
                    <div class="pp-row">
                        <div class="col-md-12">
                            <div class="sub_title"><?php echo esc_html(pplus_localize('Chỗ ở')); ?></div>
                        </div>
                        <div class="col-md-12">
                            <div class="description"><?php echo esc_html($detail['description']); ?></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <hr />

            <?php // SUA 30/9 (bug thuc te nguoi dung bao cao: "trong chi tiết các mục này thiếu
            // icon"): "icon"/"iconOn" tra ve tu API la data URI ("data:image/svg+xml;base64,...")
            // chu KHONG PHAI URL http(s) thuong - esc_url() cua WordPress CHI cho qua 1 danh sach
            // scheme co dinh (http/https/mailto/...), KHONG co "data" trong do, nen tu LANG LANG
            // xoa/lam hong gia tri truoc khi in ra (icon luon rong, hop <i> hien trang - dung y het
            // 1 loi da gap voi "\" trong pplus_admin_asset_url()/urlHerf, nhung lan nay la sai ham
            // esc_ chu khong phai thieu chuan hoa). Dung esc_attr() (chi ma hoa dau nhay/&, khong
            // dong toi scheme) cho MOI cho gan "icon"/"iconOn" vao thuoc tinh style o duoi day. ?>
            <div class="apart_service">
                <?php
                $special_locations = $detail['specialLocationList'] ?? [];
                $special_head      = array_slice($special_locations, 0, 9);
                $special_rest      = array_slice($special_locations, 9);
                ?>
                <?php
                // 3 khoi "Dịch vụ bao gồm"/"Dịch vụ không bao gồm"/"Tiện ích tòa nhà" deu cung 1
                // cau truc {serviceName, value, iconOn} - gom lai 1 vong lap PHP thay vi lap code 3
                // lan (detail.component.html dong 312-390 lap y het 3 lan trong ban goc).
                $service_blocks = [
                    ['serviceIncludedList',    pplus_localize('Dịch vụ bao gồm')],
                    ['serviceNotIncludedList', pplus_localize('Dịch vụ không bao gồm')],
                    ['utilityList',            pplus_localize('Tiện ích tòa nhà')],
                ];
                foreach ($service_blocks as [$key, $label]) :
                    $items = $detail[$key] ?? [];
                    ?>
                    <div class="apart_service-content-2">
                        <div class="_titleInfo"><?php echo esc_html($label); ?></div>
                        <div class="apart_service-data-2">
                            <div>
                                <?php if (!empty($items)) : ?>
                                    <div class="info-grid">
                                        <?php foreach ($items as $item) : ?>
                                            <div class="ttcoban">
                                                <div class="fix-icon-div">
                                                    <?php // Icon base64 tu API -> file tinh dung chung (xem pplus_data_uri_to_url()); '' = du lieu hong -> icon mac dinh. ?>
                                                    <?php $pplus_icon = pplus_data_uri_to_url((string) ($item['iconOn'] ?? '')); ?>
                                                    <?php if ($pplus_icon !== '') : ?>
                                                        <i class="ttcoban-ico" style="background-image:url('<?php echo esc_attr($pplus_icon); ?>')"></i>
                                                    <?php else : ?>
                                                        <i class="ttcoban-ico default-service-ico"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="ttcoban_info">
                                                    <div class="ttcoban_name"><?php echo esc_html($item['serviceName'] ?? ''); ?></div>
                                                    <?php if (!empty($item['value'])) : ?>
                                                        <div class="ttcoban_detail"><?php echo esc_html($item['value']); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else : ?>
                                    <p class="empty"><?php echo esc_html(pplus_localize('Chưa có dữ liệu ...')); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php // SUA 30/9 (theo yeu cau nguoi dung): chuyen "Tiện ích xung quanh" xuong CUOI
                // cung, sau "Tiện ích tòa nhà" - khac thu tu ban Angular goc (detail.component.html
                // dong 255-311, "Tiện ích xung quanh" von o DAU, truoc ca 3 khoi dich vu) nhung day
                // la thay doi giao dien rieng nguoi dung yeu cau, khong phai loi port. ?>
                <div class="apart_service-content-1">
                    <div class="_titleInfo">
                        <?php echo esc_html(pplus_localize('Tiện ích xung quanh')); ?>
                        <?php if (!empty($special_rest)) : ?>
                            <a class="utilities_link" href="#SpecialLocations" role="button" data-bs-toggle="collapse" data-bs-target="#SpecialLocations" aria-expanded="false" aria-controls="SpecialLocations">
                                <?php echo esc_html(pplus_localize('Xem thêm')); ?>
                                <i class="fa fa-angle-double-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="apart_service-data-1">
                        <div class="margin-bottom-20">
                            <?php if (!empty($special_head)) : ?>
                                <div class="info-grid">
                                    <?php foreach ($special_head as $item) : ?>
                                        <div class="ttcoban">
                                            <div class="fix-icon-div">
                                                <?php $pplus_icon = pplus_data_uri_to_url((string) ($item['icon'] ?? '')); ?>
                                                <?php if ($pplus_icon !== '') : ?>
                                                    <i class="ttcoban-ico" style="background-image:url('<?php echo esc_attr($pplus_icon); ?>')"></i>
                                                <?php else : ?>
                                                    <i class="ttcoban-ico special-location-type<?php echo (int) ($item['type'] ?? 0); ?>-ico"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="ttcoban_info">
                                                <div class="ttcoban_name"><?php echo esc_html($item['name'] ?? ''); ?></div>
                                                <div class="ttcoban_detail"><?php echo esc_html($item['address'] ?? ''); ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else : ?>
                                <p class="empty"><?php echo esc_html(pplus_localize('Chưa có dữ liệu ...')); ?></p>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($special_rest)) : ?>
                            <div id="SpecialLocations" class="collapse">
                                <div class="_titleInfo" style="width: 100%; height: 15px;">
                                    <a class="utilities_link customCollape" href="#SpecialLocations" role="button" data-bs-toggle="collapse" data-bs-target="#SpecialLocations">
                                        <?php echo esc_html(pplus_localize('Đóng')); ?>
                                        <i class="fa fa-angle-double-left"></i>
                                    </a>
                                </div>
                                <div class="info-grid margin-bottom-20">
                                    <?php foreach ($special_rest as $item) : ?>
                                        <div class="ttcoban">
                                            <div class="fix-icon-div">
                                                <?php $pplus_icon = pplus_data_uri_to_url((string) ($item['icon'] ?? '')); ?>
                                                <?php if ($pplus_icon !== '') : ?>
                                                    <i class="ttcoban-ico" style="background-image:url('<?php echo esc_attr($pplus_icon); ?>')"></i>
                                                <?php else : ?>
                                                    <i class="ttcoban-ico special-location-type<?php echo (int) ($item['type'] ?? 0); ?>-ico"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="ttcoban_info">
                                                <div class="ttcoban_name"><?php echo esc_html($item['name'] ?? ''); ?></div>
                                                <div class="ttcoban_detail"><?php echo esc_html($item['address'] ?? ''); ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($detail['lat']) && !empty($detail['lng'])) : ?>
            <div class="pp-detail-map">
                <div class="data-title-5"><?php echo esc_html(pplus_localize('Bản đồ')); ?></div>
                <div class="col-12">
                    <div class="project_map">
                        <div id="map-detail" style="height: 40vh; width: 100%"
                             data-lat="<?php echo esc_attr($detail['lat']); ?>"
                             data-lng="<?php echo esc_attr($detail['lng']); ?>"
                             data-title="<?php echo esc_attr($detail['projectName'] ?? ''); ?>"></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-md-4 detail-right">
        <div class="card bangGia-cnt">
            <div class="_giaCa-ctn">
                <div class="_giaCa-Name">
                    <div class="gia-Label"><?php echo esc_html(pplus_localize('Giá khởi điểm')); ?></div>
                </div>
                <div class="giaCanHo">
                    <span class="font-bold"><?php echo esc_html($detail['priceString'] ?? ''); ?></span><span class="font-bold">$</span> / <span><?php echo esc_html(pplus_localize('tháng')); ?></span>
                </div>
                <div class="chiTietGia-Label">(<?php echo esc_html(pplus_localize('Giá trên có thể thay đổi tùy theo điều kiện thị trường và chưa bao gồm phí dịch vụ, thuế')); ?>)</div>
            </div>

            <div class="_tieuDeDv"><?php echo esc_html(pplus_localize('PropertyPlus cung cấp các giá trị gia tăng cho các căn hộ chung cư')); ?></div>
            <div class="_chiTietDv">
                <?php
                $value_added = [
                    ['don-phong-ico', pplus_localize('Dọn phòng')],
                    ['hoa-chat-ico', pplus_localize('Hóa chất')],
                    ['wifi-ico', pplus_localize('Internet')],
                    ['truyen-hinh-ico', pplus_localize('Truyền hình')],
                    ['dien-nuoc-ico', pplus_localize('Hạn mức điện nước')],
                    ['nuoc-uong-ico', pplus_localize('Nước uống Lavie')],
                ];
                foreach ($value_added as [$icon, $label]) : ?>
                    <div class="ttcoban">
                        <div class="fix-icon-div"><i class="ttcoban-ico <?php echo esc_attr($icon); ?>"></i></div>
                        <div class="ttcoban_info"><div class="ttcoban_name"><?php echo esc_html($label); ?></div></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (!empty($model['listUser'])) : ?>
                <hr class="table-mobile" />
                <div class="list-user">
                    <?php foreach ($model['listUser'] as $item) : ?>
                        <div class="item">
                            <?php // Anh tu van vien (PNG 146-172KB/anh tu admin) - luon duoi man hinh dau -> hoan toi load+1s. ?>
                            <div><?php echo pplus_defer_lazy_imgs('<img src="' . esc_url(pplus_admin_asset_url($item['img'] ?? '')) . '" alt="' . esc_attr($item['title'] ?? '') . '" loading="lazy" />'); ?></div>
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

            <div class="_lienHe">
                <span class="font-bold"><?php echo esc_html(pplus_localize('Miễn phí tư vấn')); ?>:</span>
                <br />
                <?php echo esc_html(pplus_localize('Hotline')); ?>: 08 5666 2566
                <br />
                <?php echo esc_html(pplus_localize('Mail')); ?>: enquiry@propertyplus.com.vn
            </div>

            <?php // Modal "Gửi yêu cầu lịch hẹn" (rentApartmentModal, detail.component.html dong 514-552)
            // dung LAI y het co che ".pplus-advise-form" da co san SITE-WIDE (assets/js/main.js) - form
            // nay POST cung 1 endpoint that voi IssueNewApartment() ben Angular (ca 2 deu goi
            // Favourite/IssueLeaseApartment, xem docblock inc/advise-form.php), nen KHONG can viet REST
            // route/JS rieng nhu ban dau tuong (chi can dung dung class/name field). ?>
            <div class="_dichVu">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#rentApartmentModal"><?php echo esc_html(pplus_localize('Gửi yêu cầu lịch hẹn')); ?></button>
            </div>
        </div>
    </div>
</div>

<?php // Chi hien tren mobile (CSS). Nut Lien he mo cung popup lien he (#modal-container5) voi nut "Liên hệ" cua menu
// chan trang da an. JA: "Từ" dich la "～から" (hau to, dat lam nhan tren gia doc sai) nen dung "Giá khởi điểm". ?>
<div class="pplus-price-bar">
    <?php if (!empty($detail['priceString'])) : ?>
        <div class="pplus-price-bar__price">
            <div class="pplus-price-bar__label"><?php echo esc_html(pplus_localize(pplus_current_lang() === 'ja' ? 'Giá khởi điểm' : 'Từ')); ?></div>
            <div class="pplus-price-bar__value"><strong><?php echo esc_html($detail['priceString']); ?>$</strong> <span>/ <?php echo esc_html(pplus_localize('tháng')); ?></span></div>
        </div>
    <?php endif; ?>
    <button type="button" class="pplus-price-bar__contact" data-pplus-open-modal="modal-container5">
        <svg width="22" height="22" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M2 20.5901V5.01514C2 4.46514 2.196 3.99414 2.588 3.60214C2.97933 3.2108 3.45 3.01514 4 3.01514H20C20.55 3.01514 21.021 3.2108 21.413 3.60214C21.8043 3.99414 22 4.46514 22 5.01514V17.0151C22 17.5651 21.8043 18.0361 21.413 18.4281C21.021 18.8195 20.55 19.0151 20 19.0151H6L3.7 21.3151C3.38333 21.6318 3.02067 21.7025 2.612 21.5271C2.204 21.3525 2 21.0401 2 20.5901Z" fill="currentColor" /></svg>
        <span><?php echo esc_html(pplus_localize('Liên hệ')); ?></span>
    </button>
</div>

<?php // Nut "Quay lai" nho (mobile) - JS (main.js, [data-pplus-back]) quay lai trang truoc neu den tu chinh site,
// khong thi di theo href (danh sach quan cua can ho). EN tu dien dich "Come back" nen dung pplus_t() de ra "Back". ?>
<a class="pplus-back-btn" href="<?php echo esc_url($district_url); ?>" data-pplus-back>
    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M14 8H2.5M7 3L2 8L7 13" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg>
    <span><?php echo esc_html(pplus_t('Quay lại', 'Back')); ?></span>
</a>

<div class="modal fade rentApartmentModal" id="rentApartmentModal" tabindex="-1" role="dialog" aria-labelledby="rentApartmentModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <?php // SUA 30/9 (bug thuc te nguoi dung bao cao, doi chieu anh chup that: nut "X" dong
            // modal o day KHONG co trong ban goc - detail.component.html dong 517 that la
            // "<button hidden #cloderentApartmentModal data-bs-dismiss=\"modal\">Close</button>",
            // an hoan toan (chi Angular tu bam qua ViewChild de tu dong dong modal SAU KHI gui thanh
            // cong, khong phai nut nguoi dung bam). Ban port truoc day tu them 1 nut ".btn-close"
            // HIEN THI (khong co trong ban goc) nhung KHONG co ".modal-header" bao ngoai nen render
            // sai vi tri (lech sang trai, de len chu) - doi lai dung y het ban goc: an hoan toan,
            // dong modal qua bam ra ngoai backdrop hoac phim Esc (hanh vi Bootstrap modal mac dinh). ?>
            <button type="button" hidden data-bs-dismiss="modal" aria-label="<?php echo esc_attr(pplus_localize('Đóng')); ?>">Close</button>
            <div class="modal-body">
                <div class="text-center">
                    <h4 class="pp-title m-auto margin-bottom-10 margin-top-15"><?php echo esc_html(pplus_localize('Nhập thông tin liên hệ của bạn để đăng ký thuê căn hộ')); ?></h4>
                    <div class="pp-color"><?php echo esc_html(pplus_localize('Chuyên viên Property Plus sẽ liên hệ theo thông tin khách hàng cung cấp')); ?></div>

                    <div class="mt-30">
                        <form class="pplus-advise-form" novalidate autocomplete="off">
                            <div class="row form-group">
                                <input class="form-control" type="text" name="OwnerName" placeholder="<?php echo esc_attr(pplus_localize('Họ và tên')); ?>" />
                            </div>
                            <div class="row form-group">
                                <input class="form-control" type="email" name="OwnerEmail" placeholder="<?php echo esc_attr(pplus_localize('Email')); ?>" />
                            </div>
                            <div class="row form-group">
                                <input class="form-control" type="tel" name="OwnerMobile" placeholder="<?php echo esc_attr(pplus_localize('Số điện thoại')); ?>" />
                            </div>
                            <div class="row form-group group-check">
                                <div class="font-italic"><label><input type="checkbox" />&nbsp;<?php echo esc_html(pplus_localize('Tôi đồng ý với các điều khoản trên của Property Plus đưa ra')); ?></label></div>
                            </div>
                            <div class="text-center">
                                <button class="btn btn-primary" type="submit"><?php echo esc_html(pplus_localize('Hoàn tất đăng ký dịch vụ')); ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>
