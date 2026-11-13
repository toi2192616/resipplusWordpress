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
    pplus_response_flags('error-notice'); // xem inc/response-headers.php (503 neu API loi tam thoi that)
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
    $html = pplus_rewrite_content_links($html);
    $html = pplus_externalize_inline_images($html);
    $html = pplus_webp_content($html); // anh CMS (admin./app. JPG/PNG) -> WebP cache neu da co, xem inc/image-webp.php
    return pplus_add_lazy_loading_to_images($html);
}

/**
 * 3/11/2026: link TRONG noi dung CMS (bai SEO quan/du an/blog do bien tap vien go tay) tro toi chinh domain cu dang
 * "http://propertyplus.com.vn/en/cho-thue-can-ho/ha-noi/quan-tay-ho/fraser-suites-300": dung http (them 1 lan 301 sang https) va, tren trang
 * EN/JA, goc TIENG VIET (cheo ngon ngu = 404, ke ca o web chinh hien tai -> nguoi doc bam vao la gap trang loi; tren trang quan Tay Ho
 * co 17 link nhu vay). Viet lai thanh URL chuan cua site DANG CHAY (cung host nen server test khong day nguoi dung sang production):
 * pplus_canonical_path() + pplus_fix_cross_language_root(). Chi dong toi link la route cua site; link khac cua domain (file, /admin...) va
 * link ra ngoai giu nguyen.
 */
function pplus_rewrite_content_links(string $html): string {
    if ($html === '' || stripos($html, 'propertyplus.com.vn') === false) {
        return $html;
    }
    return preg_replace_callback(
        '~(<a\b[^>]*?\shref=)(["\'])https?://(?:www\.)?propertyplus\.com\.vn(/[^"\'?#]*)?([?#][^"\']*)?\2~i',
        static function (array $m): string {
            $canon = pplus_canonical_path(rawurldecode($m[3] ?? '/'), 'vi');
            if ($canon === null) {
                return $m[0];
            }
            return $m[1] . $m[2] . esc_url(home_url(pplus_fix_cross_language_root($canon))) . ($m[4] ?? '') . $m[2];
        },
        $html
    ) ?? $html;
}

/**
 * 23/10/2026: anh base64 nhung thang trong du lieu API (src="data:image/...;base64,...") -> file tinh trong
 * uploads/pplus-inline-img/<md5>.<ext>, tra ve URL cua file do. Do thuc te: mo ta quan Ba Dinh (A3) co 3 anh JPEG
 * base64 = 445KB nam NGAY TRONG HTML (trang 716KB, base64 gan nhu khong nen gzip duoc, tai het truoc khi trinh
 * duyet thay duoc anh LCP; loading="lazy" cung vo tac dung voi data URI); trang chi tiet can ho co 57 icon tien
 * ich SVG base64 = 265KB, LAP LAI tren MOI trang chi tiet. Thanh file thi: anh duoi man hinh dau lazy duoc that,
 * icon duoc trinh duyet cache dung chung giua cac trang.
 *
 * Them: du lieu API doi khi la CA THE "<img alt="" src="data:...">" thay vi URL (icon "Tiện ích" trang chi tiet,
 * trinh duyet hieu la URL tuong doi -> request 404 132KB) - lay rieng gia tri src.
 *
 * Loi ghi file (uploads khong ghi duoc, SVG co script) -> tra lai NGUYEN gia tri cu, khong bao gio lam mat anh.
 * Base64 hong (anh von da khong hien duoc) -> ''.
 */
function pplus_data_uri_to_url(string $value): string {
    $value = trim($value);
    if (stripos($value, '<img') === 0 && preg_match('/\ssrc\s*=\s*(["\'])(.*?)\1/is', $value, $src_match)) {
        $value = trim(html_entity_decode($src_match[2], ENT_QUOTES | ENT_HTML5));
    }
    if (!preg_match('~^data:image/(png|jpe?g|gif|webp|svg\+xml);base64,~i', $value, $m)) {
        return $value;
    }

    static $memo = [];
    $key = md5($value);
    if (isset($memo[$key])) {
        return $memo[$key];
    }
    $memo[$key] = $value;

    $ext     = ['png' => 'png', 'jpeg' => 'jpg', 'jpg' => 'jpg', 'gif' => 'gif', 'webp' => 'webp', 'svg+xml' => 'svg'][strtolower($m[1])];
    $uploads = wp_upload_dir(null, false);
    if (!empty($uploads['error'])) {
        return $value;
    }
    $dir  = $uploads['basedir'] . '/pplus-inline-img';
    $name = $key . '.' . $ext;
    if (!is_file($dir . '/' . $name)) {
        $bin = base64_decode((string) preg_replace('/\s+/', '', substr($value, strlen($m[0]))), true);
        if ($bin === false || $bin === '') {
            // Base64 hong (vd du lieu CMS bi cat cut, ket thuc bang "...") - trinh duyet cung khong hien duoc
            // (ERR_INVALID_URL) -> '' de noi goi dung icon/anh mac dinh.
            return $memo[$key] = '';
        }
        // SVG phuc vu tu chinh domain: mo truc tiep URL thi script trong SVG se chay -> chi tach file SVG "sach".
        if ($ext === 'svg' && preg_match('/<script|<foreignObject|\son[a-z]+\s*=|javascript:/i', $bin)) {
            return $value;
        }
        if (!wp_mkdir_p($dir)) {
            return $value;
        }
        $tmp = $dir . '/' . $name . '.' . uniqid('', true) . '.tmp';
        if (@file_put_contents($tmp, $bin) === false || (!@rename($tmp, $dir . '/' . $name) && !is_file($dir . '/' . $name))) {
            @unlink($tmp);
            return $value;
        }
        @unlink($tmp);
    }
    return $memo[$key] = $uploads['baseurl'] . '/pplus-inline-img/' . $name;
}

/** GIF trong suot 1x1 - o giu cho cua anh da hoan (xem pplus_defer_lazy_imgs()). */
if (!defined('PPLUS_BLANK_GIF')) {
    define('PPLUS_BLANK_GIF', 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==');
}

/**
 * Bo loc ma 1 GOI Y tim kiem (quan / loai phong / khu vuc, API Apartment/_GetAllDistrict) mang san trong "jsonSearch", CHI giu cac truong
 * co o man "tuy chon" cua panel tim kiem (so phong ngu/tam, dien tich, gia, tang) - de chon "03 Phong Ngu" o man goi y thi sang man tuy chon
 * o "Phong ngu" tu to mau "3" (Studio -> 1 phong ngu + dien tich toi da 49m2). Gia tri mac dinh (gia 0..12000/15000, dien tich toi da
 * 1000 = "khong gioi han") bi bo - chi hien khi goi y thuc su gioi han.
 *
 * @return array<string,int>
 */
function pplus_suggestion_option_filter(array $item): array {
    $raw = json_decode((string) ($item['jsonSearch'] ?? ''), true);
    return is_array($raw) ? pplus_clean_option_filter($raw) : [];
}

/**
 * Phan loc "sach" cua pplus_suggestion_option_filter(): nhan MANG THO (jsonSearch da giai ma, "filter" cua Reference/GetIndex, hoac cac tham so
 * loc tren URL) va chi giu cac truong that su gioi han ket qua o panel "Chi tiết". Dung chung de popup tim kiem dien lai dung bo loc cua trang
 * dang xem (xem pplus_search_context()).
 *
 * @param array<string,mixed> $raw
 * @return array<string,int>
 */
function pplus_clean_option_filter(array $raw): array {
    $num = static function (string $key) use ($raw): int {
        return isset($raw[$key]) && is_numeric($raw[$key]) ? (int) $raw[$key] : 0;
    };
    $out = [];
    foreach (['bedroom_qty', 'bathroom_qty'] as $key) {
        if ($num($key) >= 1 && $num($key) <= 8) {
            $out[$key] = $num($key);
        }
    }
    if ($num('area_from') > 0) {
        $out['area_from'] = $num('area_from');
    }
    if ($num('area_to') > 0 && $num('area_to') < 1000) {
        $out['area_to'] = $num('area_to');
    }
    if ($num('floor_from') > 0) {
        $out['floor_from'] = $num('floor_from');
    }
    if ($num('floor_to') > 0) {
        $out['floor_to'] = $num('floor_to');
    }
    if ($num('price_from') > 0) {
        $out['price_from'] = $num('price_from');
    }
    if ($num('price_to') > 0 && $num('price_to') < 12000) {
        $out['price_to'] = $num('price_to');
    }
    return $out;
}

/**
 * Luoi "Hoặc chọn theo gợi ý" cua popup tim kiem (desktop + mobile): 16 muc Admin chon (Apartment/_GetAllDistrict, giu NGUYEN thu tu) + cac muc
 * cua MENU ma danh sach do thieu (quan con lai, Chung cu / Dich vu / Cao cap, Truc Bach, Mua sam, Gym, Gia re...). Truoc day popup chi co 16 muc
 * nen trang dang o "Condominium" (co o menu) khong tim lai duoc no trong goi y. Trung alias thi giu muc cua Admin (co icon + bo loc san).
 *
 * @return array<int,array<string,mixed>> moi muc: {alias, subTitle, img, jsonSearch, ...}
 */
function pplus_search_suggestion_items(): array {
    static $items = null;
    if ($items !== null) {
        return $items;
    }
    $items = [];
    $seen  = [];
    $add   = static function (array $list) use (&$items, &$seen): void {
        foreach ($list as $item) {
            $alias = strtolower(trim((string) ($item['alias'] ?? '')));
            if ($alias === '' || isset($seen[$alias]) || trim((string) ($item['subTitle'] ?? '')) === '') {
                continue;
            }
            $seen[$alias] = true;
            $items[]      = $item;
        }
    };
    $add(pplus_get_all_districts());
    $header = pplus_get_header_data();
    if (is_array($header)) {
        $add((array) ($header['apartmentLuxury'] ?? []));
        $add((array) ($header['dataLists'] ?? []));
    }
    return $items;
}

/**
 * The <img> cua 1 icon trong luoi goi y (desktop popup, trang chu, panel mobile) - 13/11/2026 (PageSpeed: moi trang tai 25 icon / 206KB JPG-PNG goc tu Admin, vd
 * "thumbnail.jpg" 95KB cho o 22px, sai ti le khung + ~56 the img lap lai o 2 luoi). Nay: WebP 66px qua cache cua theme (pplus_webp_url, giu goc khi chua tao xong) VA chi
 * tai khi nguoi dung MO popup/panel lan dau (src = GIF 1x1, URL that o data-pplus-icon, JS: PplusSearchSuggest.loadIcons() trong pplus-search-suggest.js). Dung kich thuoc 22x22
 * va object-fit:contain (CSS) nen khong con lech ti le. Loi tai anh -> logo (nhu truoc).
 */
function pplus_suggestion_icon_img(string $img): string {
    $url = pplus_admin_asset_url($img);
    $url = $url !== '' ? pplus_webp_url($url, 66) : '';
    return sprintf(
        '<img title="ListProject" src="%1$s" data-pplus-icon="%2$s" alt="" width="22" height="22" decoding="async" onerror="this.onerror=null;this.src=\'%3$s\'" />',
        PPLUS_BLANK_GIF,
        esc_url($url),
        esc_url(get_template_directory_uri() . '/assets/officebase/Common/Images/logo-96.webp')
    );
}

/**
 * Popup tim kiem biet "nguoi dung dang o dau va dang loc gi" de mo ra DIEN SAN thay vi tinh nang lam lai tu dau:
 * - alias/label: "vung" cua trang danh sach dang xem (quan, loai can ho, khu vuc, toa nha... - /cho-thue-can-ho/ha-noi/{alias}); rong o trang chu
 *   hoac "Tất cả" (/cho-thue-can-ho/ha-noi).
 * - filters: bo loc NGUOI DUNG da ap (query string: gia, phong ngu/tam, dien tich, tang, loai nha, bon tam).
 * - scopeFilters: bo loc ma CHINH "vung" do tu mang theo (vd Studio = 1 phong ngu + <=49m2) va khong bi query string ghi de - doi vung thi tu bo,
 *   giong cach lam cua luoi goi y.
 * - selection: trang A1 (/cho-thue-can-ho/?project_id=...&q=...) - du an / dia diem Google da chon, de bam tim kiem tiep khong mat no.
 * Chi co du lieu tren trang danh sach (reference-index + A1); trang khac tra ve rong (hanh vi cu).
 *
 * @return array{alias:string,label:string,filters:array<string,int>,scopeFilters:array<string,int>,selection:?array}
 */
function pplus_search_context(): array {
    static $ctx = null;
    if ($ctx !== null) {
        return $ctx;
    }
    $ctx = ['alias' => '', 'label' => '', 'filters' => [], 'scopeFilters' => [], 'selection' => null];

    $is_ref  = (bool) get_query_var('pplus_reference_index');
    $is_list = (bool) get_query_var('pplus_apartment_list');
    if (!$is_ref && !$is_list) {
        return $ctx;
    }

    $raw = [];
    foreach (['price_from', 'price_to', 'bedroom_qty', 'bathroom_qty', 'area_from', 'area_to', 'floor_from', 'floor_to'] as $key) {
        if (isset($_GET[$key]) && is_scalar($_GET[$key]) && preg_match('/^\d{1,7}$/', (string) $_GET[$key])) {
            $raw[$key] = (int) $_GET[$key];
        }
    }
    $filters = pplus_clean_option_filter($raw);
    if (isset($_GET['apartment_type']) && in_array((string) $_GET['apartment_type'], ['1', '2'], true)) {
        $filters['apartment_type'] = (int) $_GET['apartment_type'];
    }
    if (isset($_GET['has_bathtub']) && (string) $_GET['has_bathtub'] === '1') {
        $filters['has_bathtub'] = 1;
    }
    $ctx['filters'] = $filters;

    if ($is_ref && sanitize_text_field((string) get_query_var('pplus_ref_alias')) !== '') {
        $model = pplus_get_reference_index_from_query_vars();
        $data  = is_array($model['dataSelected'] ?? null) ? $model['dataSelected'] : [];
        $alias = trim((string) ($data['alias'] ?? ''));
        $label = trim((string) ($data['subTitle'] ?? ''));
        if ($label === '') {
            $label = trim((string) ($data['title'] ?? ''));
        }
        if ($alias !== '' && $label !== '') {
            $ctx['alias']        = $alias;
            $ctx['label']        = $label;
            $ctx['scopeFilters'] = array_diff_key(pplus_clean_option_filter((array) ($model['filter'] ?? [])), $filters);
        }
    }

    if ($is_list) {
        $q = isset($_GET['q']) && is_scalar($_GET['q']) ? trim(sanitize_text_field(wp_unslash((string) $_GET['q']))) : '';
        if ($q !== '') {
            if (!empty($_GET['project_id']) && is_scalar($_GET['project_id'])) {
                $ctx['selection'] = ['type' => 'project', 'id' => (int) $_GET['project_id'], 'label' => $q];
            } else {
                $area = [];
                foreach (['ne_lat', 'ne_lng', 'sw_lat', 'sw_lng'] as $key) {
                    if (isset($_GET[$key]) && is_scalar($_GET[$key]) && is_numeric($_GET[$key])) {
                        $area[$key] = (float) $_GET[$key];
                    }
                }
                if (count($area) === 4) {
                    $ctx['selection'] = ['type' => 'area', 'label' => $q] + $area;
                }
            }
        }
    }
    return $ctx;
}

/**
 * Tom tat NGAN bo loc nguoi dung dang ap, cho chip tren thanh goi y ("2 PN · $500–900"). Toi da 3 phan, con lai "+N".
 * Ky hieu ($, ≥, ≤, m²) dung chung moi ngon ngu.
 *
 * @param array<string,int> $filters pplus_search_context()['filters']
 */
function pplus_search_filter_summary(array $filters): string {
    $range = static function (string $from, string $to, string $prefix, string $suffix) use ($filters): string {
        $a = (int) ($filters[$from] ?? 0);
        $b = (int) ($filters[$to] ?? 0);
        if ($a > 0 && $b > 0) {
            return $prefix . $a . '–' . $b . $suffix;
        }
        if ($a > 0) {
            return '≥ ' . $prefix . $a . $suffix;
        }
        return $b > 0 ? '≤ ' . $prefix . $b . $suffix : '';
    };
    $parts = [];
    if (!empty($filters['apartment_type'])) {
        $parts[] = (int) $filters['apartment_type'] === 1 ? pplus_localize('Căn hộ dịch vụ') : pplus_localize('Chung cư');
    }
    if (!empty($filters['bedroom_qty'])) {
        $parts[] = (int) $filters['bedroom_qty'] . ' ' . pplus_t('PN', 'BR');
    }
    if (!empty($filters['bathroom_qty'])) {
        $parts[] = (int) $filters['bathroom_qty'] . ' ' . pplus_t('WC', 'Bath');
    }
    foreach ([
        $range('price_from', 'price_to', '$', ''),
        $range('area_from', 'area_to', '', ' m²'),
        $range('floor_from', 'floor_to', pplus_t('Tầng', 'Floor') . ' ', ''),
    ] as $part) {
        if ($part !== '') {
            $parts[] = $part;
        }
    }
    if (!empty($filters['has_bathtub'])) {
        $parts[] = pplus_localize('Bồn tắm');
    }
    if (count($parts) > 3) {
        return implode(' · ', array_slice($parts, 0, 3)) . ' +' . (count($parts) - 3);
    }
    return implode(' · ', $parts);
}

/**
 * 23/10/2026 (port tu ban Van phong 004cbfe): hoan anh "loading=lazy" nam DUOI man hinh dau - src = GIF 1x1, anh that o
 * data-pplus-src (+ data-pplus-srcset) va <noscript>, nap lai boi pplus_print_deferred_img_loader() 1 GIAY sau "load"
 * hoac ngay khi nguoi dung cuon/cham/bam phim.
 *
 * Vi sao: Chrome tai SOM ca anh loading=lazy trong vong ~1250-3000px quanh man hinh (3000px khi chua uoc luong duoc toc
 * do mang - dung truong hop PageSpeed), nen trang chu Resi (150 anh lazy, ~3,2MB: carousel can ho/nhan vien/doi tac/am
 * thuc - TAT CA nam tu 1167px tro xuong tren mobile) van tai gan het ngay tu dau, gianh bang thong voi anh/chu LCP va bi
 * Lighthouse tinh vao LCP (moi request xong truoc moc LCP deu tinh). "+1s" thay vi dung "load": anh LCP thuong ve sau
 * "load" vai chuc ms (do cua ban Van phong). Googlebot van thay anh that (loader chay theo dong ho, khong can cuon) va
 * <noscript> giu ban goc cho crawler khong chay JS. Chi dung cho HTML ma MOI anh lazy deu nam duoi man hinh dau.
 */
function pplus_defer_lazy_imgs(string $html): string {
    if (defined('PPLUS_DEFER_IMGS') && !PPLUS_DEFER_IMGS) {
        return $html; // tat han: define('PPLUS_DEFER_IMGS', false) trong wp-config.php
    }
    // <iframe loading="lazy"> cung vay (ban do Google "Địa chỉ" o footer: Chrome van tai trong nguong lazy -> ~600KB script
    // Maps tren MOI trang): bo han src (khung trong) cho toi khi loader nap lai.
    return (string) preg_replace_callback(
        '/<(img|iframe)\b[^>]*>(<\/iframe>)?/i',
        static function (array $m): string {
            $tag = $m[0];
            if (stripos($tag, 'loading="lazy"') === false || stripos($tag, 'data-pplus-src') !== false
                || !preg_match('/\ssrc=(["\'])((?:https?:)?\/[^"\']+)\1/i', $tag, $src)) {
                return $tag;
            }
            $GLOBALS['pplus_img_deferred'] = true;
            $is_img = strtolower($m[1]) === 'img';
            $new = str_replace($src[0], ($is_img ? ' src="' . PPLUS_BLANK_GIF . '"' : '') . ' data-pplus-src="' . $src[2] . '"', $tag);
            $new = (string) preg_replace('/\ssrcset=(["\'])/i', ' data-pplus-srcset=$1', $new);
            return $new . '<noscript>' . $tag . '</noscript>';
        },
        $html
    );
}

/** Script nap lai anh da hoan (chi in khi trang co it nhat 1 anh duoc hoan). <noscript><style> an o GIF khi khong co JS. */
function pplus_print_deferred_img_loader(): void {
    if (empty($GLOBALS['pplus_img_deferred'])) {
        return;
    }
    echo '<noscript><style>img[data-pplus-src],iframe[data-pplus-src]{display:none!important}</style></noscript>' . "\n";
    // Anh/khung nam TRONG modal an (".modal-container-ppus"/Bootstrap ".modal": avatar lien he, icon chat Messenger/Zalo...) khong nap luc
    // load - chi nap khi nguoi dung bam nut mo modal (truoc day ~240KB/trang tai vo ich: 2 avatar PNG + 2 icon chat).
    echo "<script>(function(){var d=document,done=0;function ld(e){var s=e.getAttribute('data-pplus-srcset');if(s){e.srcset=s;e.removeAttribute('data-pplus-srcset');}e.src=e.getAttribute('data-pplus-src');e.removeAttribute('data-pplus-src');}function go(){if(done)return;done=1;var l=d.querySelectorAll('img[data-pplus-src],iframe[data-pplus-src]');for(var i=0;i<l.length;i++){if(!l[i].closest('[data-pplus-modal],.modal'))ld(l[i]);}}window.pplusLoadDeferred=function(r){if(!r)return;var l=r.querySelectorAll('img[data-pplus-src],iframe[data-pplus-src]');for(var i=0;i<l.length;i++)ld(l[i]);};d.addEventListener('click',function(ev){var t=ev.target.closest&&ev.target.closest('[data-pplus-open-modal],[data-bs-target],[data-target]');if(!t)return;var id=t.getAttribute('data-pplus-open-modal')||t.getAttribute('data-bs-target')||t.getAttribute('data-target')||'';id=id.replace('#','');if(id)window.pplusLoadDeferred(d.getElementById(id));},true);function later(){setTimeout(go,1000)}if(d.readyState==='complete')later();else addEventListener('load',later);['scroll','touchstart','keydown','mousedown'].forEach(function(t){addEventListener(t,go,{once:true,passive:true});});})();</script>
";
}
add_action('wp_footer', 'pplus_print_deferred_img_loader', 5);

/** Ap dung pplus_data_uri_to_url() cho src cua moi <img> base64 trong 1 chuoi HTML (noi dung CMS). */
function pplus_externalize_inline_images(string $html): string {
    if (stripos($html, 'data:image/') === false) {
        return $html;
    }
    return (string) preg_replace_callback(
        '/(<img\b[^>]*?\ssrc=)(["\'])(data:image\/[^"\']+)\2/i',
        static function (array $m): string {
            $data_uri = html_entity_decode($m[3], ENT_QUOTES | ENT_HTML5);
            $url      = pplus_data_uri_to_url($data_uri);
            return ($url === $data_uri || $url === '') ? $m[0] : $m[1] . $m[2] . esc_url($url) . $m[2];
        },
        $html
    );
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
        'address'             => pplus_apartment_address($item),
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
                <a href="<?php echo esc_url(home_url($lang_prefix . '/' . pplus_apartment_list_root() . '/ha-noi')); ?>" class="suggest-item<?php echo ($active_alias === '') ? ' active' : ''; ?>">
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
                <?php // 11/11/2026: thanh nay chi co ~12 muc "hot" nen dang o 1 vung khong nam trong do (vd Condominium) thi KHONG muc nao sang - nguoi dung
                // khong thay minh dang o dau. Them 1 pill "dang xem" (sang) cho vung do + 1 chip tom tat bo loc dang ap (bam mo popup, "x" xoa bo loc). ?>
                <?php
                $pplus_ctx    = pplus_search_context();
                $pplus_active = $active_alias !== null ? trim($active_alias) : '';
                $pplus_known  = false;
                foreach ($index_success as $item) {
                    if (trim((string) ($item['alias'] ?? '')) === $pplus_active) {
                        $pplus_known = true;
                        break;
                    }
                }
                if ($pplus_active !== '' && !$pplus_known && $pplus_ctx['alias'] === $pplus_active && $pplus_ctx['label'] !== '') : ?>
                    <a href="<?php echo esc_url(home_url($lang_prefix . '/' . pplus_apartment_list_root() . '/ha-noi/' . $pplus_active)); ?>" class="suggest-item active pplus-scope-pill">
                        <i class="item-img">
                            <svg width="14" height="18" viewBox="0 0 14 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill="#545454" d="M7 0.5C3.13 0.5 0 3.63 0 7.5C0 11.67 4.42 17.42 6.24 19.61C6.64 20.09 7.37 20.09 7.77 19.61C9.58 17.42 14 11.67 14 7.5C14 3.63 10.87 0.5 7 0.5ZM7 10C5.62 10 4.5 8.88 4.5 7.5C4.5 6.12 5.62 5 7 5C8.38 5 9.5 6.12 9.5 7.5C9.5 8.88 8.38 10 7 10Z" />
                            </svg>
                        </i>
                        <div class="item-title"><?php echo esc_html($pplus_ctx['label']); ?></div>
                    </a>
                <?php endif; ?>
                <?php $pplus_chip = $pplus_ctx['filters'] ? pplus_search_filter_summary($pplus_ctx['filters']) : '';
                if ($pplus_chip !== '') :
                    $pplus_clear_url = remove_query_arg(['price_from', 'price_to', 'bedroom_qty', 'bathroom_qty', 'area_from', 'area_to', 'floor_from', 'floor_to', 'apartment_type', 'has_bathtub', 'paged'], pplus_current_url()); ?>
                    <div class="suggest-item pplus-filter-chip">
                        <button type="button" class="pplus-chip-open" data-bs-toggle="modal" data-bs-target="#searchModal" title="<?php echo esc_attr(pplus_localize('Chi tiết')); ?>">
                            <svg width="13" height="13" viewBox="0 0 16 16" fill="none" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"><path d="M1 3h14M4 8h8M6.5 13h3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" /></svg>
                            <span class="item-title"><?php echo esc_html($pplus_chip); ?></span>
                        </button>
                        <a class="pplus-chip-clear" href="<?php echo esc_url($pplus_clear_url); ?>" aria-label="<?php echo esc_attr(pplus_t('Xóa bộ lọc', 'Clear filters')); ?>" title="<?php echo esc_attr(pplus_t('Xóa bộ lọc', 'Clear filters')); ?>">&times;</a>
                    </div>
                <?php endif; ?>
                <?php foreach ($index_success as $item) :
                    $item_alias = (string) ($item['alias'] ?? '');
                    if ($item_alias === '') continue; ?>
                    <a href="<?php echo esc_url(home_url($lang_prefix . '/' . pplus_apartment_list_root() . '/ha-noi/' . $item_alias)); ?>" class="suggest-item<?php echo ($active_alias !== null && $item_alias === $active_alias) ? ' active' : ''; ?>">
                        <i class="item-img">
                            <img src="<?php echo esc_url(pplus_webp_url(pplus_admin_asset_url($item['img'] ?? ''), 66)); ?>" alt="" width="22" height="22" loading="lazy" />
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

/**
 * Thuoc tinh width/height cho anh LAY TU API (anh nhan vien, "Kham pha", "Hoat dong"...) - 4/11/2026.
 *
 * Vi sao: cac anh nay deu loading="lazy" ma khong co kich thuoc, nen khoi chua chung (vd "Co cau
 * to chuc", "Doi ngu nhan vien" tren /ve-chung-toi) CO LAI con ~84px/401px cho toi khi anh tai xong,
 * roi bung ra ~740px/960px -> trang giat manh khi cuon + co luc hien khoang trong (CLS). Co width+
 * height thi trinh duyet giu san cho theo ti le. Anh API co nhieu kich thuoc khac nhau (312x330,
 * 312x373, 400x300, 2048x1536...) nen khong the doan - phai do that.
 *
 * Cach lam: kich thuoc luu trong option "pplus_img_dims" (khong autoload). Lan dau gap 1 URL chua co
 * thi tra '' (nhu cu) va do SAU KHI da tra trang xong (shutdown + fastcgi_finish_request), nen khong
 * lam cham request. Tu lan sau trang co du width/height. Do that bai thi thu lai sau 1 ngay.
 */
function pplus_img_dim_attrs(string $url): string {
    if ($url === '') {
        return '';
    }
    static $map = null;
    if ($map === null) {
        $map = get_option('pplus_img_dims', []);
        if (!is_array($map)) {
            $map = [];
        }
    }
    $row = $map[$url] ?? null;
    if (is_array($row) && (int) ($row[0] ?? 0) > 0 && (int) ($row[1] ?? 0) > 0) {
        return ' width="' . (int) $row[0] . '" height="' . (int) $row[1] . '"';
    }
    if ($row === null || (is_array($row) && (int) ($row[2] ?? 0) < time() - DAY_IN_SECONDS)) {
        pplus_img_dim_queue($url);
    }
    return '';
}

/** Xep hang URL can do kich thuoc va dang ky 1 lan viec chay sau khi da tra trang. */
function pplus_img_dim_queue(string $url): void {
    static $queue = [];
    static $hooked = false;
    if (!preg_match('#^https?://#i', $url) || count($queue) >= 40) {
        return;
    }
    $queue[$url] = true;
    if ($hooked) {
        return;
    }
    $hooked = true;
    add_action('shutdown', function () use (&$queue) {
        if (!$queue) {
            return;
        }
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request(); // nguoi dung da nhan du trang, phan do ben duoi khong anh huong toc do
        }
        @set_time_limit(30);
        $map = get_option('pplus_img_dims', []);
        if (!is_array($map)) {
            $map = [];
        }
        $deadline = microtime(true) + 12;
        foreach (array_keys($queue) as $u) {
            if (microtime(true) > $deadline) {
                break;
            }
            $w = $h = 0;
            $res = wp_remote_get($u, [
                'timeout' => 3,
                'redirection' => 2,
                'headers' => ['Range' => 'bytes=0-262143'],
                'limit_response_size' => 262144, // chi can phan dau file de doc kich thuoc
            ]);
            if (!is_wp_error($res) && in_array((int) wp_remote_retrieve_response_code($res), [200, 206], true)) {
                $size = @getimagesizefromstring((string) wp_remote_retrieve_body($res));
                if ($size && $size[0] > 0 && $size[1] > 0) {
                    $w = (int) $size[0];
                    $h = (int) $size[1];
                }
            }
            $map[$u] = [$w, $h, time()];
        }
        if (count($map) > 400) {
            $map = array_slice($map, -400, null, true);
        }
        update_option('pplus_img_dims', $map, false);
    });
}

/**
 * Ban do Google CUA RESI (dia diem "PropertyPlus Viet Nam_Apartment for rent in Ha Noi") - dung chung cho footer + trang Lien he.
 * 4/11/2026: trang Lien he truoc day nhung ban do cua theme VAN PHONG ("Cho thue van phong tai Ha Noi - propertyplus.vn", tang 4 toa nha
 * 292 Tay Son) - sai dia diem. Ban do dung la ban nhung o chan trang (footer), nay dua vao 1 cho de 2 noi khong lech nhau.
 * $lang doi ngon ngu giao dien ban do (nhan ten duong, nut "Mo trong Maps"...): vi/en/ja theo trang.
 */
function pplus_resi_map_embed_url(?string $lang = null): string {
    $lang = in_array($lang ?: pplus_current_lang(), ['vi', 'en', 'ja'], true) ? ($lang ?: pplus_current_lang()) : 'vi';
    return 'https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d14898.552851937988!2d105.8228791!3d21.0071347!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3135ac83d7aaa7c9%3A0xee4412aece650f2e!2sPropertyPlus%20Viet%20Nam_Apartment%20for%20rent%20in%20Ha%20Noi!5e0!3m2!1s' . $lang . '!2s!4v1696585344584!5m2!1s' . $lang . '!2s';
}

/** Link mo dung dia diem tren ung dung/trang Google Maps (nut "Tim tren ban do"). */
function pplus_resi_map_link(?string $lang = null): string {
    $lang = in_array($lang ?: pplus_current_lang(), ['vi', 'en', 'ja'], true) ? ($lang ?: pplus_current_lang()) : 'vi';
    return 'https://www.google.com/maps?ftid=0x3135ac83d7aaa7c9:0xee4412aece650f2e&hl=' . $lang;
}
