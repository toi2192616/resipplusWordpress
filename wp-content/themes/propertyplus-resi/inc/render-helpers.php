<?php
/**
 * Helper render dung chung cho cac template single-building.php, single-news.php...
 */

if (!defined('ABSPATH')) {
    exit;
}

function pplus_format_price(?float $value): string {
    if ($value === null) {
        return '';
    }
    return number_format($value, 0, ',', '.') . ' USD/m2';
}

function pplus_format_area(?float $value): string {
    if ($value === null) {
        return '';
    }
    return number_format($value, 0, ',', '.') . ' m2';
}

/**
 * In loi thay vi crash trang khi API loi/timeout - hien fallback don gian,
 * dung cho truong hop back4office cham/down (xem rui ro trong plan).
 */
function pplus_render_api_error(): void {
    echo '<div class="pplus-notice">' . esc_html__('Du lieu dang duoc cap nhat, vui long quay lai sau.', 'propertyplus') . '</div>';
}

/**
 * wp_kses_post() nhung CHO PHEP them protocol "data:" tren src cua <img> - noi dung CMS
 * (Blogs/About/Office...) doi khi co anh dan/nhung TRUC TIEP dang base64 (xac nhan qua kiem tra
 * that: content cua "Chính sách bảo mật thông tin" - <img src="data:image/png;base64,...">).
 * wp_kses_post() MAC DINH khong cho phep protocol "data" (chi http/https/mailto/tel...), nen AM
 * THAM XOA rieng tien to "data:" (giu lai phan con lai "image/png;base64,..." khong con la URL
 * hop le), khien anh vo hinh HOAN TOAN (khong phai 404 - src sai dang tu dau) - loi thuc te da
 * gap ("2 trong 3 bai chinh sach bi loi anh").
 *
 * KHONG dung filter "kses_allowed_protocols" (cach thong thuong) vi wp_allowed_protocols() cache
 * ket qua vao 1 bien static va CHI doc lai filter khi "wp_loaded" CHUA chay - luc cac template
 * (page-about-subpage.php...) chay thi "wp_loaded" da xong tu lau, gan filter luc do vo tac dung.
 * Thay vao do goi thang wp_kses() (ham loi cua wp_kses_post()) voi danh sach protocol tu tay bo
 * sung, khong dung filter/cache gi ca.
 */
function pplus_kses_content(string $html): string {
    $protocols   = wp_allowed_protocols();
    $protocols[] = 'data';
    $html = wp_kses($html, 'post', $protocols);
    return pplus_add_lazy_loading_to_images($html);
}

/**
 * Them "loading=lazy" cho MOI <img> (chua co san thuoc tinh "loading") trong 1 chuoi HTML tho -
 * dung cho noi dung CMS (Blogs/Office/About...) la HTML tho tu API .NET (khong phai post content
 * cua WP), nen KHONG tu duoc WP xu ly qua co che "the_content" mac dinh.
 *
 * DA THU dung ham loi co san cua WP (wp_filter_content_tags()) truoc, nhung KHONG hoat dung tren
 * site nay: ham do (qua wp_get_loading_optimization_attributes(), wp-includes/media.php dong
 * ~6191-6221) CHI lazy-load dung khi dang o "trong Loop" (in_the_loop()+is_main_query()) HOAC
 * "truoc Loop" ($wp_query->before_loop). Toan bo theme nay dung route/API rieng (inc/api-client.php),
 * KHONG BAO GIO goi the_post()/have_posts() - nen "$wp_query->before_loop" LUON true tu dau den
 * cuoi 1 trang, khien WP coi MOI anh la "dang trong khung hinh" va KHONG BAO GIO gan loading=lazy
 * (da xac nhan qua doc thang wp-includes/media.php + kiem tra thuc te HTML xuat ra: chi thay
 * "decoding=async" duoc them, "loading" thi khong bao gio xuat hien). Vi vay tu xu ly bang regex
 * don gian o day thay vi dua vao heuristic do cua WP (von thiet ke rieng cho kien truc Loop
 * truyen thong, khong hop voi theme nay).
 *
 * KHONG can "bo qua anh dau tien" (khac logic LCP-protection thu cong ap dung cho cac <img> TINH
 * trong template PHP, xem parts/office-card.php...): ham nay CHI dung cho noi dung THAN BAI VIET/
 * MO TA (body content), luon nam DUOI hero/anh chinh cua trang (hero da duoc render rieng, TRUOC
 * do, boi chinh template - vd page-office-detail.php giu photos[0] KHONG lazy) - khong co truong
 * hop anh trong noi dung nay la LCP.
 */
function pplus_add_lazy_loading_to_images(string $html): string {
    return (string) preg_replace_callback(
        '/<img\b[^>]*>/i',
        static function (array $match): string {
            if (stripos($match[0], 'loading=') !== false) {
                return $match[0];
            }
            return substr($match[0], 0, 4) . ' loading="lazy"' . substr($match[0], 4);
        },
        $html
    );
}

/**
 * Xay "data-compare" (JSON) cho 1 item van phong - dung CHUNG boi parts/office-card.php (luc render
 * the tren trang) VA REST route "pplus/v1/compare-item" (inc/compare-pdf-route.php, luc lam moi lai
 * 1 item CO SAN trong danh sach "So sánh" sang ngon ngu hien tai - xem ghi chu $item['lang'] trong
 * assets/js/compare.js) - tranh 2 noi giu 2 ban logic khac nhau roi lech nhau dan.
 *
 * @param array $item 1 phan tu tu result.listOffice cua Office/_GetOffices.
 * @return array Shape khop dung CRITERIA_ROWS trong assets/js/compare.js.
 */
function pplus_build_compare_payload(array $item): array {
    $photo = !empty($item['photoList'][0]) ? pplus_admin_asset_url($item['photoList'][0]) : '';

    return [
        'projectId'           => $item['projectId'] ?? '',
        'projectName'         => $item['projectName'] ?? '',
        'urlHerf'             => pplus_office_url($item['urlHerf'] ?? ''),
        'photo'               => $photo,
        'address'             => $item['address'] ?? '',
        'ratingLevel'         => $item['ratingLevel'] ?? '',
        'itemPrice'           => $item['itemPrice'] ?? '',
        'managerFee'          => $item['managerFee'] ?? '',
        'subType'             => $item['subType'] ?? '',
        'fromTotalAmt'        => $item['fromTotalAmt'] ?? '',
        'toTotalAmt'          => $item['toTotalAmt'] ?? '',
        'pmCompany'           => $item['pmCompany'] ?? '',
        'releaseDate'         => !empty($item['releaseDate']) ? date('d/m/Y', strtotime($item['releaseDate'])) : '',
        'floorAreas'          => $item['floorAreas'] ?? '',
        'parkingAvailability' => $item['parkingAvailability'] ?? '',
        'powerGeneratorInfo'  => $item['powerGeneratorInfo'] ?? '',
        'elevatorInfo'        => $item['elevatorInfo'] ?? '',
        'airConditionInfo'    => $item['airConditionInfo'] ?? '',
        // "carParkInfo"/"motorParkInfo" KHONG dua vao day - xem ghi chu day du tai noi goi
        // (parts/office-card.php): 2 truong nay LUON rong tren Office/_GetOffices, phai de THIEU HAN
        // (undefined ben JS) de assets/js/compare.js (ensureParkFees) nhan ra can goi lay rieng.
    ];
}

/**
 * Trich "Muc Luc" tu noi dung bai viet (B3/B4) - thay the app-table-of-contents ben Angular
 * (component do scan DOM luc runtime bang JS, o day server-render thang tu HTML).
 *
 * Content tra ve tu API DA CO SAN <h2 id="..."> voi id la slug tieng Viet khong dau (kiem tra
 * thuc te qua curl: vd <h2 id="1-danh-gia-thuc-te-ve-vi-tri...">) - khong can tu sinh id, chi
 * can doc lai dung id co san de link neu dung.
 *
 * @return array<int, array{id:string, text:string}>
 */
function pplus_extract_toc(string $html): array {
    if (trim($html) === '') {
        return [];
    }

    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8"?><div>' . $html . '</div>', LIBXML_NOERROR | LIBXML_NOWARNING);
    libxml_clear_errors();

    $items = [];
    foreach ($doc->getElementsByTagName('h2') as $heading) {
        $id   = $heading->getAttribute('id');
        $text = trim($heading->textContent);
        if ($id === '' || $text === '') {
            continue;
        }
        $items[] = ['id' => $id, 'text' => $text];
    }
    return $items;
}

/**
 * In khoi "Muc Luc" (nav-title + danh sach anchor link) - dung chung boi single-news.php (B3)
 * va page-author.php (B4), giong vi tri #bg-toc ben Angular (truoc noi dung, trong cung cot).
 */
function pplus_render_toc(array $items): void {
    if (empty($items)) {
        return;
    }
    echo '<div class="post-navigation-left" id="bg-toc">';
    echo '<div class="nav-title">' . pplus_t('Mục Lục', 'Table of Contents') . '</div>';
    echo '<div class="toc"><ul>';
    foreach ($items as $item) {
        printf('<li><a href="#%s">%s</a></li>', esc_attr($item['id']), esc_html($item['text']));
    }
    echo '</ul></div></div>';
}

/**
 * Ban ĐẦY ĐỦ của "Muc Luc" - port 1:1 thuat toan generateTOC() trong
 * src/app/partials/table-of-contents/table-of-contents.component.ts (dong 54-77), dung cho A3/A4
 * (page-landing.php, page-reference.php) vi noi dung tra ve tu Reference/* KHONG co san <h2 id="">
 * nhu content cua Blogs/* (khac voi pplus_extract_toc()/pplus_render_toc() o tren, chi doc id co
 * san - KHONG dung duoc o day vi se ra rong).
 *
 * Khac voi ban tren: quet ca h1/h2/h3/h5/h6 (dung BO h4 - giong that Angular), tu sinh id neu
 * heading chua co (slug = text.trim().toLowerCase().replace(khoang-trang, '-'), y het dong 61 ben
 * Angular), GHI ID DO NGUOC LAI vao chinh heading trong HTML tra ve (server-render tuong duong
 * viec Angular ghi thang vao DOM luc runtime), va long h1/h3/h5/h6 vao lam con cua h2 gan nhat
 * (findParent(), dong 79-91) thay vi 1 danh sach phang.
 *
 * @return array{html:string, items:array} html = noi dung DA duoc gan id vao heading,
 *   items = cay TOC long nhau [{id,text,level,children:[...]}]
 */
function pplus_generate_toc(string $html): array {
    if (trim($html) === '') {
        return ['html' => $html, 'items' => []];
    }

    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8"?><div id="pplus-toc-wrap">' . $html . '</div>', LIBXML_NOERROR | LIBXML_NOWARNING);
    libxml_clear_errors();

    $wrap = $doc->getElementsByTagName('div')->item(0);
    if (!$wrap) {
        return ['html' => $html, 'items' => []];
    }

    $xpath    = new DOMXPath($doc);
    $headings = $xpath->query('.//h1 | .//h2 | .//h3 | .//h5 | .//h6', $wrap);

    $items = [];
    foreach ($headings as $heading) {
        $level = (int) substr($heading->nodeName, 1);
        $text  = trim($heading->textContent);
        if ($text === '') {
            continue;
        }
        $id = $heading->getAttribute('id');
        if ($id === '') {
            $id = strtolower(trim($text));
            $id = preg_replace('/\s+/u', '-', $id);
            $heading->setAttribute('id', $id);
        }

        $item = ['id' => $id, 'text' => $text, 'level' => $level, 'children' => []];

        if ($level === 2) {
            $items[] = $item;
        } else {
            $parent = &pplus_toc_find_parent($items, $level - 1);
            if ($parent !== null) {
                $parent['children'][] = $item;
            }
            unset($parent);
        }
    }

    $out_html = '';
    foreach (iterator_to_array($wrap->childNodes) as $child) {
        $out_html .= $doc->saveHTML($child);
    }

    return ['html' => $out_html, 'items' => $items];
}

/**
 * Tim item gan nhat co dung $level trong cay (duyet tu cuoi len, dao sau vao children) - port
 * findParent() dong 79-91. Tra ve THAM CHIEU that (PHP reference-returning function) toi phan tu
 * trong $items de goi noi con sua truc tiep vao dung vi tri trong cay long nhau.
 */
function &pplus_toc_find_parent(array &$items, int $level) {
    $not_found = null;
    for ($i = count($items) - 1; $i >= 0; $i--) {
        if ($items[$i]['level'] === $level) {
            return $items[$i];
        }
        if (!empty($items[$i]['children'])) {
            $child_parent = &pplus_toc_find_parent($items[$i]['children'], $level);
            if ($child_parent !== null) {
                return $child_parent;
            }
        }
    }
    return $not_found;
}

/**
 * In hang "goi y nhanh" (".suggest-header") - port dung tu search-base.component.html dong 1-35
 * (<app-search-base>), thanh phan CHUNG xuat hien tren CA trang danh sach can ho/quan
 * (page-reference.php) LAN trang Dich vu (page-service.php) - xac nhan qua doi chieu truc tiep
 * ban that (cung 1 hang icon "Tất cả/Quận Tây Hồ/.../01 Phòng Ngủ" xuat hien nguyen ven tren ca 2
 * trang), TRUOC DAY chi ton tai rieng trong page-reference.php nen tach ra day de dung chung, tranh
 * lap code.
 *
 * Nguon du lieu la Home/_GetIndexSuccess qua pplus_get_index_success() (BE tu chon "hot", KHONG
 * PHAI danh sach quan/loai day du cua mega-menu header).
 *
 * @param string|null $active_alias Alias dang xem de gan class "active" (nhu page-reference.php).
 *   Truyen null (mac dinh) khi trang khong thuoc he thong loc quan/loai nao (vd trang Dich vu) -
 *   se KHONG co muc nao active, ke ca "Tất cả" (khac voi truyen '' - truong hop do "Tất cả" SE
 *   active, dung y het hanh vi goc cua page-reference.php khi dang o trang goc "ha-noi/" khong
 *   alias).
 */
function pplus_render_suggest_header(?string $active_alias = null): void {
    $lang_prefix    = pplus_lang_prefix();
    $index_success  = pplus_get_index_success();
    ?>
    <?php // SUA 17/9: them 3 phan tu truoc day BI THIEU HOAN TOAN (chi co ".suggest-list-data" o
    // giua) - ".btn-prev-search"/".btn-next-search" (cuon hang pill) va ".suggest-item.btn-modal-
    // search" (nut tim kiem tron xanh, mo tam #searchModal - modal nay CHUA lam, xem TODO plan Blog/
    // Dich vu - nut van hien de dung layout that, click chua co tac dung) - CSS 3 phan tu nay da co
    // san tu truoc (header.less dong 1900-1996, dung), chi thieu markup PHP nen ".suggest-list-data"
    // (width:calc(100% - 135px), da chua san 135px cho 2 nut nay) truoc day hien nhu 1 khoang trang
    // rong o mep phai, khong "can doi" - bug thuc te nguoi dung bao cao khi doi chieu ban that. ?>
    <div class="suggest-header">
        <div class="suggest-heade-list">
            <div class="btn-prev-search">
                <button class="btn" aria-label="<?php echo esc_attr(pplus_localize('Trước')); ?>"><i class="fa fa-chevron-left"></i></button>
            </div>
            <div class="suggest-list-data">
                <a href="<?php echo esc_url(home_url($lang_prefix . '/cho-thue-can-ho/ha-noi/')); ?>" class="suggest-item<?php echo ($active_alias === '') ? ' active' : ''; ?>">
                    <i class="item-img">
                        <?php // "fill" tren <path> them vao (ban goc fill="none" tren the <svg> cha, khong co
                        // CSS nao gan mau mac dinh cho trang thai KHONG active - chi co ".active .item-img
                        // svg{fill:#BF913D}" trong header-component.css, nen icon nay vo hinh khi khong phai
                        // trang dang xem - bug thuc te nguoi dung bao cao khi doi chieu). Dung #545454 khop
                        // mau chu mac dinh cua ".item-title" ben canh cho dong bo. ?>
                        <svg width="14" height="18" viewBox="0 0 14 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill="#545454" d="M7 0.5C3.13 0.5 0 3.63 0 7.5C0 11.67 4.42 17.42 6.24 19.61C6.64 20.09 7.37 20.09 7.77 19.61C9.58 17.42 14 11.67 14 7.5C14 3.63 10.87 0.5 7 0.5ZM7 10C5.62 10 4.5 8.88 4.5 7.5C4.5 6.12 5.62 5 7 5C8.38 5 9.5 6.12 9.5 7.5C9.5 8.88 8.38 10 7 10Z" />
                        </svg>
                    </i>
                    <div class="item-title"><?php echo esc_html(pplus_localize('Tất cả')); ?></div>
                </a>
                <?php foreach ($index_success as $item) :
                    $item_alias = (string) ($item['alias'] ?? '');
                    if ($item_alias === '') continue; ?>
                    <a href="<?php echo esc_url(home_url($lang_prefix . '/cho-thue-can-ho/ha-noi/' . $item_alias . '/')); ?>" class="suggest-item<?php echo ($active_alias !== null && $item_alias === $active_alias) ? ' active' : ''; ?>">
                        <i class="item-img">
                            <img src="<?php echo esc_url(pplus_admin_asset_url($item['img'] ?? '')); ?>" alt="<?php echo esc_attr($item['subTitle'] ?? ''); ?>" loading="lazy" />
                        </i>
                        <div class="item-title"><?php echo esc_html($item['subTitle'] ?? ''); ?></div>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="btn-next-search">
                <button class="btn" aria-label="<?php echo esc_attr(pplus_localize('Sau')); ?>"><i class="fa fa-chevron-right"></i></button>
            </div>
            <div class="suggest-item btn-modal-search">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#searchModal" aria-label="searchModal"><i class="search-ico"></i></button>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Cac tham so loc tren URL danh sach can ho (xem page-apartment-list.php / page-reference.php). Dung de giu lai
 * khi doi "Lọc theo" (form GET chi co o "sort" - truoc day bam doi sap xep la mat het bo loc).
 */
function pplus_listing_query_keys(): array {
    return ['price_from', 'price_to', 'bedroom_qty', 'bathroom_qty', 'area_from', 'area_to', 'floor_from', 'floor_to',
        'apartment_type', 'has_bathtub', 'project_id', 'ne_lat', 'ne_lng', 'sw_lat', 'sw_lng', 'q'];
}

function pplus_render_preserved_query_inputs(): void {
    foreach (pplus_listing_query_keys() as $key) {
        if (isset($_GET[$key]) && is_scalar($_GET[$key]) && $_GET[$key] !== '') {
            printf('<input type="hidden" name="%s" value="%s" />', esc_attr($key), esc_attr(sanitize_text_field(wp_unslash((string) $_GET[$key]))));
        }
    }
}

/**
 * Mui ten "⟶" giua cac muc breadcrumb (.url-content-list). SVG theo mau chu (currentColor) - thay anh
 * arrow-29.png 21x8px bi mo khi phong to tren man hinh mat do cao.
 */
function pplus_breadcrumb_arrow(): string {
    return '<i class="pplus-bc-arrow" aria-hidden="true"><svg width="20" height="8" viewBox="0 0 20 8" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0.75 4H18.5M15.25 0.75L18.5 4L15.25 7.25" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg></i>';
}

/**
 * In khoi "Muc Luc" dang cay (h2 = cap 1, h1/h3/h5/h6 long lam con) - port template
 * table-of-contents.component.html. Dung CSS co san #bg-toc/.toc-container/.toc-list/.toc-link
 * trong style/layout.css (da ported tu src/assets/officebase, enqueue toan site).
 */
function pplus_render_toc_nested(array $items): void {
    if (empty($items)) {
        return;
    }
    echo '<div class="post-navigation-left" id="bg-toc">';
    echo '<div class="nav-title">' . pplus_t('Mục Lục', 'Table of Contents') . '</div>';
    echo '<div class="toc"><div class="toc-container"><ul class="toc-list">';
    foreach ($items as $item) {
        printf('<li class="toc-list-item level-%d"><a href="#%s" class="toc-link">%s</a>', (int) $item['level'], esc_attr($item['id']), esc_html($item['text']));
        if (!empty($item['children'])) {
            echo '<ul>';
            foreach ($item['children'] as $child) {
                printf('<li class="toc-list-item level-%d"><a href="#%s" class="toc-link">%s</a></li>', (int) $child['level'], esc_attr($child['id']), esc_html($child['text']));
            }
            echo '</ul>';
        }
        echo '</li>';
    }
    echo '</ul></div></div></div>';
}

/**
 * Phan trang - port DUNG cau truc/class that cua <ngb-pagination> (search-base.component.html
 * dong 162-164: [maxSize]="5" [boundaryLinks]="true") - KHONG dung WordPress paginate_links() mac
 * dinh (bug thuc te nguoi dung bao cao qua anh chup: ra 1 danh sach cham dau dong "<ul class=
 * page-numbers">" khong co style gi, vi CSS THAT (".pagination-ctn"/".pagination"/".paging-btn"/
 * ".paging-btn--prev"/".paging-btn--next"/".threedot"/".current-page", da co san tu truoc trong
 * assets/officebase/style/layout.less, dong ~1018-1105) khong khop TEN CLASS voi ".page-numbers"/
 * ".current" ma paginate_links() tu sinh ra - CSS khong bao gio ap dung duoc, dung LOP loi
 * "CSS that co san nhung markup dung sai class" da gap nhieu lan trong theme nay.
 *
 * ngb-bootstrap khong co API cong khai de "port dung 100% thuat toan windowing that" (closed-source
 * component), nen ham nay CHU DONG lam 1 windowing hop ly tuong duong (luon hien trang 1/trang
 * cuoi - "boundaryLinks", toi da 5 nut trang giua - "maxSize=5", "..." khi co khoang trong) - khop
 * DUNG class/giao dien that, khong nhat thiet khop tung pixel thuat toan noi bo cua ngb-pagination.
 *
 * @param int    $current    Trang hien tai (1-based).
 * @param int    $total      Tong so trang.
 * @param string $url_format URL trang, chua "%d" thay cho so trang (vd "...&paged=%d").
 */
function pplus_render_pagination(int $current, int $total, string $url_format): void {
    if ($total <= 1) {
        return;
    }
    $current = max(1, min($current, $total));

    // Cua so toi da 5 trang giua (maxSize=5), luon bao gom trang 1 va trang cuoi (boundaryLinks).
    $window = 2; // so trang moi ben cua trang hien tai trong cua so giua
    $mid_start = max(2, $current - $window);
    $mid_end   = min($total - 1, $current + $window);

    $pages = [1];
    if ($mid_start > 2) {
        $pages[] = '...';
    }
    for ($p = $mid_start; $p <= $mid_end; $p++) {
        $pages[] = $p;
    }
    if ($mid_end < $total - 1) {
        $pages[] = '...';
    }
    if ($total > 1) {
        $pages[] = $total;
    }
    $pages = array_values(array_unique($pages, SORT_REGULAR));

    $page_url = static fn (int $p): string => str_replace('%d', (string) $p, $url_format);
    ?>
    <div class="pagination-ctn">
        <div class="pagination">
            <?php if ($current > 1) : ?>
                <a class="paging-btn paging-btn--prev" href="<?php echo esc_url($page_url($current - 1)); ?>" aria-label="<?php echo esc_attr(pplus_localize('Trước')); ?>">&laquo;</a>
            <?php else : ?>
                <span class="paging-btn paging-btn--prev" disabled>&laquo;</span>
            <?php endif; ?>

            <?php foreach ($pages as $p) : ?>
                <?php if ($p === '...') : ?>
                    <span class="threedot">&hellip;</span>
                <?php elseif ((int) $p === $current) : ?>
                    <span class="paging-btn current-page"><?php echo (int) $p; ?></span>
                <?php else : ?>
                    <a class="paging-btn" href="<?php echo esc_url($page_url((int) $p)); ?>"><?php echo (int) $p; ?></a>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if ($current < $total) : ?>
                <a class="paging-btn paging-btn--next" href="<?php echo esc_url($page_url($current + 1)); ?>" aria-label="<?php echo esc_attr(pplus_localize('Sau')); ?>">&raquo;</a>
            <?php else : ?>
                <span class="paging-btn paging-btn--next" disabled>&raquo;</span>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
