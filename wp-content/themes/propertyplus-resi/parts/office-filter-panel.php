<?php
/**
 * "Bo loc" - panel truot ra tu nut Tim kiem van phong (parts/url-box-content.php). Port rut gon
 * tu search-base.component.html (khong lam popup "Dia diem" autocomplete/ban do - qua phuc tap,
 * giu dung scope "SEO co ban truoc" da chot).
 *
 * MOI truong (Tinh/Quan/Phuong/Hang/Loai van phong/Gia) deu la 1 dieu kien loc thuan tuy - theo
 * yeu cau nguoi dung. assets/js/main.js quyet dinh submit di dau luc bam "Tim kiem":
 * - Dang o 1 trang DA CO san pham (co "#result-box" - A1 page-office-list.php hoac A4
 *   page-reference.php khi show_office_search=true) -> GIU NGUYEN URL hien tai (path), chi doi/
 *   them query string, de PHP trang do tu doc lai $_GET va loc lai ket qua.
 * - Dang o 1 trang KHONG co san pham (A2 chi tiet, blog, lien he, ve chung toi, yeu thich...) ->
 *   submit GET binh thuong toi trang tim kiem chung "/cho-thue-van-phong/" (hanh vi form goc).
 *
 * Trang thai mo/dong dung LAI co che co san trong assets/js/main.js:
 * data-pplus-toggle="search" -> $('#box-search-base').toggleClass('show-search').
 *
 * Nhan Quan/Phuong dung dung field subTitle2 (ten ngan, vd "Hoan Kiem") - KHONG dung subTitle
 * (ten dai, vd "Thue Van Phong Hoan Kiem", chi hop cho <title>/SEO). Du lieu phuong (dataListWard)
 * da co san trong CUNG response Home/HeaderInvokeAsync, khong can goi API rieng - xac nhan qua
 * curl truc tiep API that.
 *
 * TODO (khong chan hien thi):
 * - Khong co "Dia diem" autocomplete goi y toa nha/duong (search_key chi la text input thuong -
 *   API _GetOffices/OfficeFilter.cs KHONG co field tim theo ten du an dang free-text, chi co
 *   "filter" (goi y vi tri gg map, khac muc dich) - khong the wire field nay vao API hien co).
 *
 * $args['current'] (tuy chon, xem cac trang goi get_template_part('parts/office-filter-panel',
 * null, ['current' => [...]])) - dieu kien loc DANG AP DUNG tren trang goi panel nay (province/
 * district/ward/project_type/rating_level/price_from/price_to, gia tri VI giong het field "value"
 * cua cac input trong panel), dung de PRE-CHECK dung tinh/quan/phuong/loai/hang/gia dang loc thay
 * vi luon mac dinh ve Tinh dau tien + khong chon gi ca - theo yeu cau nguoi dung: "vao tu trang
 * quan Hoan Kiem thi mo Bo loc len phai dang o dieu kien Ha Noi > Quan Hoan Kiem" de nguoi dung
 * con nhin thay dieu kien cu truoc khi doi sang dieu kien khac. Trang khong co dieu kien loc ro
 * rang (A2 chi tiet, blog, lien he...) khong truyen 'current' -> giu nguyen mac dinh cu.
 */
if (!defined('ABSPATH')) exit;

$lang_prefix = pplus_lang_prefix();
$header      = pplus_get_header_data();
$provinces   = (array) ($header['listMenu'] ?? []);
// Gia tri LOC (Province/District/WardName gui len Office/_GetOffices) PHAI LUON la ten tieng
// Viet co dau, ke ca khi dang render ban tieng Anh - xem ghi chu chi tiet trong
// pplus_header_filter_value() (inc/api-client.php): da xac nhan qua goi truc tiep API that,
// truyen ban tieng Anh (subTitle/subTitle2 tra ve tu Home/HeaderInvokeAsync luc lang=en) LUON ra
// total_count=0 (bug thuc te: bo loc Tinh/Quan/Phuong tren panel tieng Anh khong ra ket qua nao).
$pplus_vi_alias_map = (pplus_current_lang() === 'en') ? pplus_get_header_vi_alias_map() : [];

$current = array_merge([
    'province'     => '',
    'district'     => '',
    'ward'         => '',
    'project_type' => 2,
    'office_type'  => '',
    'rating_level' => '',
    'price_from'   => '',
    'price_to'     => '',
], (array) ($args['current'] ?? []));

// Gia mac dinh - port initPriceBase() (search-base.component.ts dong 868-882 ben Angular goc):
// Van phong TRUYEN THONG mac dinh 0-80 (don vi $), Van phong TRON GOI de trong (niem yet theo VND
// rieng tung listing, khong co 1 khoang gia chung nhu $ ben truyen thong). CHI ap dung khi trang
// goi panel KHONG truyen san 1 khoang gia cu the (vd nguoi dung vua tu chon qua chinh panel nay va
// dang xem lai dung gia da chon, xem $current o tren) - tranh de gia trang tu no de lai (vd
// filter.price_from cua rieng 1 trang quan/huyen) hien nham thanh "gia dang chon" gay kho hieu.
if ($current['price_from'] === '' && $current['price_to'] === '' && (int) $current['project_type'] === 2) {
    $current['price_from'] = 0;
    $current['price_to']   = 80;
}

// Tim tinh dang active (khop $current['province']) de mo panel dung ngay tu dau - mac dinh tinh
// dau tien (index 0) neu khong truyen 'current' hoac khong khop tinh nao (giu nguyen hanh vi cu).
$active_province_index = 0;
if ($current['province'] !== '') {
    foreach ($provinces as $i => $itemMenu) {
        $menu_probe = $itemMenu['menu'] ?? [];
        $val = pplus_header_filter_value($menu_probe, $pplus_vi_alias_map, 'subTitle2');
        if ($val === '') {
            $val = pplus_header_filter_value($menu_probe, $pplus_vi_alias_map, 'subTitle');
        }
        if ($val === $current['province']) {
            $active_province_index = $i;
            break;
        }
    }
}
?>
<?php // data-current-province/district: gia tri Tinh/Quan CUA CHINH TRANG dang goi panel nay (rong
// neu trang khong gan voi 1 Tinh/Quan cu the, vd A1 "/cho-thue-van-phong/") - main.js dung de biet
// khi nao nguoi dung DOI sang Tinh/Quan khac voi trang dang dung (xem ghi chu chi tiet trong
// main.js, gan nut "Tim kiem"). ?>
<form method="get" action="<?php echo esc_url(home_url($lang_prefix . '/' . pplus_office_list_base_slug() . '/')); ?>" class="box-search box-search-pane row" id="box-search-base" data-current-province="<?php echo esc_attr($current['province']); ?>" data-current-district="<?php echo esc_attr($current['district']); ?>">
    <?php // Thanh "Bo loc" tren cung + nut dong "X" - dung y het Angular goc (search-base.component.
    // html dong 7-15: div.title-seach + div.close-search, click goi showSearchBase() = chinh
    // toggle "show-search" dang dung o day). CHI hien tren mobile (xem .pplus-mobile-top-bar trong
    // search-base-component.css) - desktop giu nguyen khong co thanh nay (theo yeu cau nguoi dung:
    // "chỉ với Mobile thôi nhé, khôi phục đúng theo bản gốc" - xac nhan truc tiep qua propertyplus.vn
    // that, ca desktop lan mobile deu dung thanh nay, nhung o day chi khoi phuc pham vi mobile theo
    // dung yeu cau). ?>
    <div class="col-md-12 pplus-mobile-top-bar">
        <div class="title-seach">
            <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/search-filter.png'); ?>" alt="<?php echo esc_attr(pplus_t('Icon tìm kiếm', 'Search icon')); ?>" width="24" height="24" loading="lazy" />
            <?php echo esc_html(pplus_t('Bộ lọc', 'Filter')); ?>
        </div>
        <div class="close-search" data-pplus-toggle="search">X</div>
    </div>
    <?php if (!empty($provinces)) : ?>
        <div class="col-md-12 search-input-group">
            <div class="search-option">
                <ul class="nav nav-tabs tab-district" role="tablist">
                    <?php foreach ($provinces as $i => $itemMenu) : $menu = $itemMenu['menu'] ?? []; ?>
                        <?php
                        // "data-province-alias" (them 15/8/2026) - dung boi assets/js/main.js khi chon 1 goi y
                        // "Tên đường"/"Tên dự án" tu autocomplete "Địa điểm" (inc/location-search.php tra ve
                        // kem "provinceAlias" theo toa do that cua chinh goi y do): can DOI TAB tinh/thanh cho
                        // KHOP voi tinh THAT SU cua goi y vua chon (bug thuc te da gap: dang o tab "Hà Nội",
                        // chon 1 duong THAT SU thuoc TPHCM tu goi y, submit van giu nguyen province=Hà Nội cu
                        // -> loc SAI hoan toan sang van phong Ha Noi thay vi TPHCM). Khong the dung index (0/1)
                        // vi thu tu 2 tinh trong $provinces khong dam bao co dinh.
                        $province_alias_for_tab = $menu['alias'] ?? '';
                        ?>
                        <li class="nav-item" role="presentation">
                            <?php // "role=tab"/"aria-selected"/"aria-controls"/id lien ket voi ".pplus-province-panel"
                            // (them 20/8/2026, PageSpeed Insights bao thieu): "<ul role=tablist>" bat buoc con
                            // truc tiep (qua "<li role=presentation>") phai co "role=tab" - truoc day <button>
                            // KHONG co role nao ca. "aria-selected" khoi tao dung theo trang thai active luc render
                            // dau, main.js tu cap nhat lai khi nguoi dung bam doi tab (xem ".pplus-province-tab"
                            // click handler). ?>
                            <button type="button" id="pplus-province-tab-<?php echo (int) $i; ?>" role="tab" aria-selected="<?php echo $i === $active_province_index ? 'true' : 'false'; ?>" aria-controls="pplus-province-panel-<?php echo (int) $i; ?>" class="nav-link pplus-province-tab<?php echo $i === $active_province_index ? ' active' : ''; ?>" data-province-index="<?php echo (int) $i; ?>" data-province-alias="<?php echo esc_attr($province_alias_for_tab); ?>">
                                <?php
                                // API .NET (Home/HeaderInvokeAsync) KHONG co field "title"/"subTitle2" cho tinh -
                                // CHI co "subTitle" ("Hà Nội"/"Thành phố Hồ Chí Minh"). Nhung nhan tab tren pop
                                // "Bo loc" cua propertyplus.vn that lai la "TP. Hà Nội"/"TP. Hồ Chí Minh" (vi) va
                                // "Hanoi City"/"Ho Chi Minh City" (en) - xac nhan qua doi chieu truc tiep DOM that,
                                // KHONG lay tu API ma la text hardcode rieng cho dung 2 tinh nay trong Angular goc
                                // (chi 2 tinh, khong phai du lieu CMS). Hardcode lai y het o day theo alias.
                                if ($province_alias_for_tab === 'ho-chi-minh') {
                                    $province_tab_label = pplus_t('TP. Hồ Chí Minh', 'Ho Chi Minh City');
                                } elseif ($province_alias_for_tab === 'ha-noi') {
                                    $province_tab_label = pplus_t('TP. Hà Nội', 'Hanoi City');
                                } else {
                                    $province_tab_label = $menu['subTitle2'] ?? ($menu['subTitle'] ?? '');
                                }
                                echo esc_html($province_tab_label);
                                ?>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="tab-content tab-district-list">
                    <?php foreach ($provinces as $i => $itemMenu) :
                        $menu = $itemMenu['menu'] ?? [];
                        $districts = (array) ($itemMenu['dataListDistrict'] ?? []);
                        $is_active_province = ($i === $active_province_index);
                    ?>
                        <?php
                        $province_filter_value = pplus_header_filter_value($menu, $pplus_vi_alias_map, 'subTitle2');
                        if ($province_filter_value === '') {
                            $province_filter_value = pplus_header_filter_value($menu, $pplus_vi_alias_map, 'subTitle');
                        }

                        // Che do mac dinh (Quan/Phuong) va quan dang active trong 2 cot che do Phuong -
                        // chi tinh cho tinh dang active, dua theo $current['ward'] (uu tien) roi moi
                        // toi $current['district'] (tim quan chua no de tab "Quan" cung to sang dung
                        // quan). Mac dinh: che do "Quan", quan dau tien (index 0) - giong het truoc day.
                        $default_mode = 'quan';
                        $active_ward_district_index = 0;
                        if ($is_active_province && $current['ward'] !== '') {
                            $default_mode = 'phuong';
                            foreach ($districts as $di => $d) {
                                foreach ((array) ($d['dataListWard'] ?? []) as $w) {
                                    $wn = pplus_header_filter_value($w, $pplus_vi_alias_map, 'subTitle2');
                                    if ($wn === '') {
                                        $wn = pplus_header_filter_value($w, $pplus_vi_alias_map, 'subTitle');
                                    }
                                    if ($wn === $current['ward']) {
                                        $active_ward_district_index = $di;
                                        break 2;
                                    }
                                }
                            }
                        }
                        ?>
                        <div class="pplus-province-panel<?php echo $is_active_province ? ' show active' : ''; ?>" id="pplus-province-panel-<?php echo (int) $i; ?>" role="tabpanel" aria-labelledby="pplus-province-tab-<?php echo (int) $i; ?>" data-province-index="<?php echo (int) $i; ?>" <?php echo $is_active_province ? '' : 'style="display:none"'; ?>>
                            <input type="radio" name="province" value="<?php echo esc_attr($province_filter_value); ?>" <?php echo $is_active_province ? 'checked' : ''; ?> hidden />

                            <div class="search-controls pplus-mode-tab-row">
                                <i class="icon"><img class="icon-from-search" src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/Group.png'); ?>" alt="Icon" width="29" height="24" loading="lazy" /></i>
                                <div class="control control-search-group">
                                    <label class="search-label pplus-mode-tab<?php echo $default_mode === 'quan' ? ' active' : ''; ?>" data-mode="quan" data-province-index="<?php echo (int) $i; ?>"><?php echo esc_html(pplus_t('Quận', 'District')); ?></label>
                                    <label class="search-label pplus-mode-tab<?php echo $default_mode === 'phuong' ? ' active' : ''; ?>" data-mode="phuong" data-province-index="<?php echo (int) $i; ?>"><?php echo esc_html(pplus_t('Phường', 'Ward')); ?></label>
                                </div>
                            </div>

                            <div class="office-popup-search3">
                                <!-- Che do "Quan": luoi nut quan, giong het truoc gio -->
                                <div class="pplus-show-district pplus-mode-panel" data-mode="quan" <?php echo $default_mode === 'quan' ? '' : 'style="display:none"'; ?>>
                                    <?php foreach ($districts as $d) :
                                        // Gia tri phai la TEN quan ("Hoan Kiem" = subTitle2), KHONG phai alias
                                        // ("quan-hoan-kiem") - .NET Office/_GetOffices (OfficeFilter.District) can
                                        // ten, giong het quy uoc Province o tren. Da doi chieu qua ApiGetAllProjectInput.cs.
                                        // LUON ep ve ban tieng Viet (pplus_header_filter_value(), xem ghi chu dau file) -
                                        // subTitle2 ban EN (vd "Hoan Kiem Dist.") khong khop API, chi subTitle2/subTitle
                                        // ban VI ("Hoàn Kiếm") moi loc ra dung ket qua.
                                        $district_name = pplus_header_filter_value($d, $pplus_vi_alias_map, 'subTitle2');
                                        if ($district_name === '') {
                                            $district_name = pplus_header_filter_value($d, $pplus_vi_alias_map, 'subTitle');
                                        }
                                        $district_checked = $is_active_province && $current['district'] !== '' && $current['district'] === $district_name;
                                    ?>
                                        <div class="district-item">
                                            <input class="pplus-toggle-check" name="district" id="pplus_district_<?php echo esc_attr($d['alias'] ?? ''); ?>" type="checkbox" value="<?php echo esc_attr($district_name); ?>" <?php echo $district_checked ? 'checked' : ''; ?> hidden />
                                            <label class="item-class" for="pplus_district_<?php echo esc_attr($d['alias'] ?? ''); ?>">
                                                <span class="class-name"><?php echo esc_html($d['subTitle2'] ?? ($d['subTitle'] ?? '')); ?></span>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Che do "Phuong": cot trai chon quan, cot phai hien phuong cua quan do -->
                                <div class="pplus-mode-panel" data-mode="phuong" <?php echo $default_mode === 'phuong' ? '' : 'style="display:none"'; ?>>
                                    <div class="pplus-show-district pplus-show-district-ward">
                                        <?php foreach ($districts as $di => $d) : ?>
                                            <div class="district-item">
                                                <label class="item-class pplus-ward-district-select<?php echo $di === $active_ward_district_index ? ' active' : ''; ?>" data-district-index="<?php echo (int) $di; ?>">
                                                    <span class="class-name"><?php echo esc_html($d['subTitle2'] ?? ($d['subTitle'] ?? '')); ?></span>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="pplus-show-district pplus-show-list-ward">
                                        <?php foreach ($districts as $di => $d) :
                                            $wards = (array) ($d['dataListWard'] ?? []);
                                            if (empty($wards)) {
                                                continue;
                                            }
                                        ?>
                                            <div class="pplus-ward-group" data-district-index="<?php echo (int) $di; ?>" <?php echo $di === $active_ward_district_index ? '' : 'style="display:none"'; ?>>
                                                <?php foreach ($wards as $w) :
                                                    // Cung ly do nhu district o tren: gia tri phai la TEN phuong (WardName),
                                                    // khong phai alias - xem OfficeFilter.cs field WardName. LUON ep ve VI
                                                    // (pplus_header_filter_value()) - "Office for Lease in Hoan Kiem Ward"
                                                    // (subTitle ban EN) khong khop API, chi "Phường Hoàn Kiếm" (ban VI) moi khop.
                                                    $ward_name = pplus_header_filter_value($w, $pplus_vi_alias_map, 'subTitle2');
                                                    if ($ward_name === '') {
                                                        $ward_name = pplus_header_filter_value($w, $pplus_vi_alias_map, 'subTitle');
                                                    }
                                                    $ward_checked = $is_active_province && $current['ward'] !== '' && $current['ward'] === $ward_name;
                                                ?>
                                                    <div class="district-item">
                                                        <input class="pplus-toggle-check" name="ward" id="pplus_ward_<?php echo esc_attr($w['alias'] ?? $w['id'] ?? ''); ?>" type="checkbox" value="<?php echo esc_attr($ward_name); ?>" <?php echo $ward_checked ? 'checked' : ''; ?> hidden />
                                                        <label class="item-class" for="pplus_ward_<?php echo esc_attr($w['alias'] ?? $w['id'] ?? ''); ?>">
                                                            <span class="class-name"><?php echo esc_html($w['subTitle2'] ?? ($w['subTitle'] ?? '')); ?></span>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="col-md-12 search-input-group">
        <div class="search-option search-option-search-text">
            <div class="search-controls">
                <i class="icon"><img class="icon-from-search" src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/Group-1.png'); ?>" alt="Icon" width="29" height="24" loading="lazy" /></i>
                <label class="search-label"><?php echo esc_html(pplus_t('Địa điểm', 'Location')); ?></label>
            </div>
            <div class="search-controls">
                <div class="control">
                    <input class="search-input search-input-style form-control" name="search_key" type="text" placeholder="<?php echo esc_attr(pplus_t('Tìm theo tên tòa nhà...', 'Search by building name...')); ?>" />
                    <?php // Dien tu JS khi nguoi dung bam chon 1 goi y autocomplete "Địa điểm" (xem
                    // assets/js/main.js + inc/location-search.php) - CHINH cap dieu kien loc THAT
                    // (project_id/street_name), "search_key" o tren chi la text hien thi cho nguoi
                    // dung, KHONG duoc doc de loc (API khong co field free-text ten du an). ?>
                    <input type="hidden" name="project_id" value="" />
                    <input type="hidden" name="street_name" value="" />
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-12 search-input-group">
        <div class="search-option">
            <div class="search-controls">
                <i class="icon"><img class="icon-from-search" src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/Group-4.png'); ?>" alt="Icon" width="29" height="18" loading="lazy" /></i>
                <label class="search-label"><?php echo esc_html(pplus_t('Mức giá', 'Price range')); ?></label>
            </div>
            <?php
            // Ky hieu tien te - port dung dieu kien Angular goc (searchBase.projectType == 2 -> "$",
            // == 1 -> "VND", dong 227-228 search-base.component.html): Van phong TRUYEN THONG niem
            // yet theo $/m2, Van phong TRON GOI niem yet cu the tung goi theo VND. 2 span nam san
            // trong DOM (khong render dong theo PHP vi project_type co the doi ngay tren client qua
            // JS khi bam nut Loai van phong khac, khong reload trang) - chi 1 cai hien tai 1 thoi
            // diem qua CSS/JS (xem "pplus-ranger-currency-usd/-vnd" trong assets/js/main.js).
            $is_service_default = ((int) $current['project_type'] === 1);
            ?>
            <div class="choose-price">
                <div class="ranger-group">
                    <label class="ranger-label" for="ranger_input_min"><?php echo esc_html(pplus_t('Từ', 'From')); ?> $</label>
                    <input class="ranger-input min" name="price_from" id="ranger_input_min" type="text" inputmode="numeric" value="<?php echo esc_attr($current['price_from']); ?>" placeholder="<?php echo esc_attr(pplus_t('Nhập số ...', 'Enter a number ...')); ?>" />
                    <span class="ranger-currency pplus-ranger-currency-usd" style="<?php echo $is_service_default ? 'display:none' : ''; ?>">$</span>
                    <span class="ranger-currency pplus-ranger-currency-vnd" style="<?php echo $is_service_default ? '' : 'display:none'; ?>">VND</span>
                </div>
                <div class="ranger-group">
                    <label class="ranger-label" for="ranger_input_max"><?php echo esc_html(pplus_t('Đến', 'To')); ?> $</label>
                    <input class="ranger-input max" name="price_to" id="ranger_input_max" type="text" inputmode="numeric" value="<?php echo esc_attr($current['price_to']); ?>" placeholder="<?php echo esc_attr(pplus_t('Nhập số ...', 'Enter a number ...')); ?>" />
                    <span class="ranger-currency pplus-ranger-currency-usd" style="<?php echo $is_service_default ? 'display:none' : ''; ?>">$</span>
                    <span class="ranger-currency pplus-ranger-currency-vnd" style="<?php echo $is_service_default ? '' : 'display:none'; ?>">VND</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-12 search-input-group">
        <div class="search-option">
            <div class="search-controls">
                <i class="icon"><img class="icon-from-search" src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/Group-3.png'); ?>" alt="Icon" width="29" height="26" loading="lazy" /></i>
                <label class="search-label"><?php echo esc_html(pplus_t('Loại văn phòng', 'Office type')); ?></label>
            </div>
            <div class="office-list-base">
                <div class="office-type">
                    <input class="input-check pplus-toggle-check" type="checkbox" id="pplus_traditional_office" name="project_type" value="2" <?php echo ((int) $current['project_type'] === 2) ? 'checked' : ''; ?> hidden />
                    <label class="office-class" for="pplus_traditional_office">
                        <i class="icon">
                            <svg width="19" height="19" viewBox="0 0 19 19" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M17.8125 7.125H7.125V5.9375H10.0938C10.2512 5.9375 10.4022 5.87494 10.5136 5.76359C10.6249 5.65224 10.6875 5.50122 10.6875 5.34375V1.1875C10.6875 1.03003 10.6249 0.879005 10.5136 0.767655C10.4022 0.656306 10.2512 0.59375 10.0938 0.59375H2.96875C2.81128 0.59375 2.66026 0.656306 2.54891 0.767655C2.43756 0.879005 2.375 1.03003 2.375 1.1875V5.34375C2.375 5.50122 2.43756 5.65224 2.54891 5.76359C2.66026 5.87494 2.81128 5.9375 2.96875 5.9375H5.9375V7.125H1.1875C1.03003 7.125 0.879005 7.18756 0.767655 7.29891C0.656306 7.41026 0.59375 7.56128 0.59375 7.71875V9.5C0.59375 9.65747 0.656306 9.8085 0.767655 9.91985C0.879005 10.0312 1.03003 10.0938 1.1875 10.0938H1.78125V17.8125C1.78125 17.97 1.84381 18.121 1.95516 18.2323C2.06651 18.3437 2.21753 18.4062 2.375 18.4062C2.53247 18.4062 2.68349 18.3437 2.79484 18.2323C2.90619 18.121 2.96875 17.97 2.96875 17.8125V10.0938H10.0938V17.8125C10.0938 17.97 10.1563 18.121 10.2677 18.2323C10.379 18.3437 10.53 18.4062 10.6875 18.4062H16.625C16.7825 18.4062 16.9335 18.3437 17.0448 18.2323C17.1562 18.121 17.2188 17.97 17.2188 17.8125V10.0938H17.8125C17.97 10.0938 18.121 10.0312 18.2323 9.91985C18.3437 9.8085 18.4062 9.65747 18.4062 9.5V7.71875C18.4062 7.56128 18.3437 7.41026 18.2323 7.29891C18.121 7.18756 17.97 7.125 17.8125 7.125ZM3.5625 1.78125H9.5V4.75H3.5625V1.78125ZM16.0312 17.2188H11.2812V14.25H16.0312V17.2188ZM16.0312 13.0625H11.2812V10.0938H16.0312V13.0625ZM17.2188 8.90625H1.78125V8.3125H17.2188V8.90625Z" />
                                <path d="M13.0625 12.1719H14.25C14.4075 12.1719 14.5585 12.1093 14.6698 11.998C14.7812 11.8866 14.8438 11.7356 14.8438 11.5781C14.8438 11.4207 14.7812 11.2696 14.6698 11.1583C14.5585 11.0469 14.4075 10.9844 14.25 10.9844H13.0625C12.905 10.9844 12.754 11.0469 12.6427 11.1583C12.5313 11.2696 12.4688 11.4207 12.4688 11.5781C12.4688 11.7356 12.5313 11.8866 12.6427 11.998C12.754 12.1093 12.905 12.1719 13.0625 12.1719Z" />
                                <path d="M13.0625 16.3281H14.25C14.4075 16.3281 14.5585 16.2656 14.6698 16.1542C14.7812 16.0429 14.8438 15.8918 14.8438 15.7344C14.8438 15.5769 14.7812 15.4259 14.6698 15.3145C14.5585 15.2032 14.4075 15.1406 14.25 15.1406H13.0625C12.905 15.1406 12.754 15.2032 12.6427 15.3145C12.5313 15.4259 12.4688 15.5769 12.4688 15.7344C12.4688 15.8918 12.5313 16.0429 12.6427 16.1542C12.754 16.2656 12.905 16.3281 13.0625 16.3281Z" />
                            </svg>
                        </i>
                        <?php echo esc_html(pplus_t('Văn phòng truyền thống', 'Traditional office')); ?>
                    </label>
                </div>
                <div class="office-type">
                    <input class="input-check pplus-toggle-check" type="checkbox" id="pplus_full_service_office" name="project_type" value="1" <?php echo ((int) $current['project_type'] === 1) ? 'checked' : ''; ?> hidden />
                    <label class="office-class" for="pplus_full_service_office">
                        <i class="icon">
                            <svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <g clip-path="url(#pplusOfficeTypeClip)">
                                    <path d="M13.5625 5.6875V9.625H0.4375V0.4375H8.3125" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M8.75 9.625H5.25V12.25H8.75V9.625Z" stroke-width="1.2" stroke-linejoin="round" />
                                    <path d="M10.0625 13.5625H3.9375L4.375 12.25H9.625L10.0625 13.5625Z" stroke-width="1.2" stroke-linejoin="round" />
                                    <path d="M0.4375 7H13.5625" stroke-width="1.2" stroke-linejoin="round" />
                                    <path d="M2.625 13.5625H11.375" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M11.1562 5.25C12.4852 5.25 13.5625 4.17269 13.5625 2.84375C13.5625 1.51481 12.4852 0.4375 11.1562 0.4375C9.8273 0.4375 8.75 1.51481 8.75 2.84375C8.75 4.17269 9.8273 5.25 11.1562 5.25Z" stroke-width="1.2" stroke-linejoin="round" />
                                    <path d="M11.0113 3.83773C10.8724 3.97076 10.6471 3.97076 10.5083 3.83773L9.72911 3.09119C9.5903 2.95822 9.5903 2.74237 9.72911 2.60941C9.86789 2.47639 10.0932 2.47639 10.2321 2.60941L10.6963 3.05414C10.7313 3.08766 10.7883 3.08766 10.8233 3.05414L12.0804 1.84977C12.2192 1.71674 12.4445 1.71674 12.5834 1.84977C12.65 1.91365 12.6875 2.00033 12.6875 2.09066C12.6875 2.18099 12.65 2.26766 12.5834 2.33154L11.0113 3.83773Z" />
                                </g>
                                <defs>
                                    <clipPath id="pplusOfficeTypeClip">
                                        <rect width="14" height="14" fill="white" />
                                    </clipPath>
                                </defs>
                            </svg>
                        </i>
                        <?php echo esc_html(pplus_t('Văn phòng trọn gói', 'Serviced office')); ?>
                    </label>
                </div>
            </div>
            <?php
            // 4 loai con cua "Van phong tron goi" - port GlobalConstants.OfficeTypeList (dung
            // pplus_office_type_name(), xem inc/api-client.php - da dung chung cho A2/office-card.php,
            // gia tri 1-4 CO DINH khong doi theo ngon ngu). CHI hien khi project_type=1 dang duoc
            // chon - port *ngIf="searchBase.projectType == 1" (search-base.component.html dong 323),
            // dung CSS mac dinh an + JS toggle theo project_type (xem assets/js/main.js), KHONG can
            // reload trang khi doi qua lai giua 2 loai van phong.
            ?>
            <div class="office-type-list" style="<?php echo $is_service_default ? '' : 'display:none'; ?>">
                <?php foreach ([1, 2, 3, 4] as $office_type_value) : ?>
                    <div class="office-type-item">
                        <input type="radio" name="office_type" id="pplus_office_type_<?php echo (int) $office_type_value; ?>" value="<?php echo (int) $office_type_value; ?>" <?php echo ((string) $current['office_type'] === (string) $office_type_value) ? 'checked' : ''; ?> data-pplus-deselectable="1" />
                        <label class="item-class" for="pplus_office_type_<?php echo (int) $office_type_value; ?>">
                            <span class="class-name"><?php echo esc_html(pplus_office_type_name($office_type_value)); ?></span>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="col-md-12 search-input-group">
        <div class="search-option">
            <div class="search-controls">
                <i class="icon"><img class="icon-from-search" src="<?php echo esc_url(get_template_directory_uri() . '/assets/Content/Img/Group-5.png'); ?>" alt="Icon" width="29" height="24" loading="lazy" /></i>
                <label class="search-label"><?php echo esc_html(pplus_t('Hạng tòa nhà', 'Building class')); ?></label>
            </div>
            <div class="building-class-list-base">
                <?php foreach (['A', 'B', 'C'] as $rating) : ?>
                    <div class="search-rating">
                        <input class="pplus-toggle-check" name="rating_level" id="pplus_rating_<?php echo esc_attr($rating); ?>" type="checkbox" value="<?php echo esc_attr($rating); ?>" <?php echo ($current['rating_level'] === $rating) ? 'checked' : ''; ?> hidden />
                        <label class="building-class" for="pplus_rating_<?php echo esc_attr($rating); ?>">
                            <span class="class-name"><?php echo esc_html(sprintf(pplus_t('Hạng %s', 'Grade %s'), $rating)); ?></span>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="col-md-12 pplus-filter-bottom-bar">
        <button class="btn btn-primary" type="submit" id="btnSearch"><?php echo esc_html(pplus_t('Tìm kiếm', 'Search')); ?> &gt;</button>
    </div>
</form>
<?php // Nut tron nho "<" dinh giua man hinh, o canh panel - co san trong Angular goc
// (search-base.component.html: div.action-close-search, cung ham toggle voi nut "X"), truoc day
// bi thieu hoan toan o ban port nay (chi con CSS, quen gan HTML - xem ghi chu chi tiet trong
// assets/css/component-source/search-base.less). Dung LAI CHINH co che toggle co san
// (data-pplus-toggle="search"), CSS rieng (#box-search-base.show-search ~ .action-close-search)
// tu an/hien theo dung trang thai dong/mo cua panel, khong can JS them. ?>
<div class="action-close-search" data-pplus-toggle="search" aria-label="<?php echo esc_attr(pplus_t('Đóng bộ lọc', 'Close filter')); ?>">X</div>
