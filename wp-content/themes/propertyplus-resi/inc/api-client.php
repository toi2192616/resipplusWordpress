<?php
/**
 * Goi truc tiep cac API .NET ma Angular dang dung (khong phai API tong hop /api/seo/v1/*
 * cua ke hoach truoc) - xem docs/SCREENS_AND_TASK_SPLIT.md. Dung chung cho header/footer
 * va toan bo cac trang o Phase 1.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Base URL de GOI API .NET (server -> server, chay trong PHP qua wp_remote_get/post - KHONG phai
 * trinh duyet nguoi dung). Khi deploy chung 1 may voi app .NET, co the tro thang vao PORT NOI BO
 * (vd "http://localhost:5000" hoac "http://127.0.0.1:5000") de goi loopback, KHONG di ra Internet/
 * qua domain public - nhanh hon va khong phu thuoc DNS/SSL ben ngoai. Dat trong Giao dien >
 * PropertyPlus > "API .NET Base URL".
 */
function pplus_api_base_url(): string {
    $base = rtrim((string) get_option('pplus_dotnet_api_base', 'https://admin.propertyplus.com.vn'), '/');
    // Guard: CHI fallback ve domain that khi gia tri ro rang la loi (rong, HOAC localhost/127.0.0.1
    // TRAN - khong co port). Truong hop nay thuong do vo tinh giu lai gia tri DB dev/test, se khien
    // moi anh/API tren site public vo ERR_CONNECTION_REFUSED voi khach.
    // NHUNG "localhost:<port>"/"127.0.0.1:<port>" (CO port) la CHU DICH: goi app .NET qua loopback
    // ngay tren cung may server, khong ra Internet (xem docblock tren + inc/settings-page.php) - phai
    // CHO PHEP di qua. Regex (/|$) sau host = chi bat khi host KHONG kem port (ket thuc bang "/" hoac
    // het chuoi); "localhost:5000" khong khop nen duoc giu nguyen.
    if ($base === '' || preg_match('#^https?://(localhost|127\.0\.0\.1)(/|$)#i', $base)) {
        return 'https://admin.propertyplus.com.vn';
    }
    return $base;
}

/**
 * Base URL de GHEP URL ANH/ASSET (item.img, photoList...) render ra <img src> cho TRINH DUYET
 * NGUOI DUNG tai. KHAC voi pplus_api_base_url(): cai nay PHAI la domain PUBLIC truy cap duoc tu
 * ben ngoai - trinh duyet cua khach KHONG the mo "http://localhost:port" cua server. Vi vay khi
 * dat API base = port noi bo, van phai giu asset base = domain that (vd https://admin.propertyplus.vn).
 * De TRONG option "pplus_dotnet_asset_base" -> tu dung lai pplus_api_base_url() (giu nguyen hanh vi
 * cu / local dev, khong can cau hinh gi them) - NHUNG KHONG BAO GIO tra ve localhost:<port>: vi
 * pplus_api_base_url() gio cho phep "localhost:<port>" (goi .NET noi bo server->server), neu asset
 * cung lay gia tri do thi <img src> ra link loopback -> trinh duyet khach vo ERR_CONNECTION_REFUSED
 * (dung loi "anh lay ca link local"). Truong hop API base la loopback thi asset fallback ve domain
 * that thay vi localhost.
 */
function pplus_asset_base_url(): string {
    $asset_base = trim((string) get_option('pplus_dotnet_asset_base', ''));
    if ($asset_base !== '') {
        return rtrim($asset_base, '/');
    }
    $api_base = pplus_api_base_url();
    if (preg_match('#^https?://(localhost|127\.0\.0\.1)(:\d+)?#i', $api_base)) {
        return 'https://admin.propertyplus.com.vn';
    }
    return $api_base;
}

/**
 * Ghep URL anh tuong doi (item.img) voi domain .NET, giong bien currentDomainAdmin
 * ma Angular dung o rat nhieu cho trong home.component.html.
 */
function pplus_admin_asset_url(?string $relative_path): string {
    if (empty($relative_path)) {
        return '';
    }
    // API .NET goc doi khi tra ve duong dan anh voi dau "\" (path Windows, vd photoList cua
    // listOffice: "Photos\202304\Ten anh.jpg") thay vi "/" - trinh duyet KHONG hieu "\" trong
    // URL nen anh bi 404 (loi da gap thuc te, xem feedback). Chuan hoa lai o day 1 lan cho moi
    // noi dung pplus_admin_asset_url(), tranh phai sua rieng tung cho goi ham nay.
    $relative_path = str_replace('\\', '/', $relative_path);
    if (preg_match('#^https?://#i', $relative_path)) {
        return $relative_path;
    }
    // Dung asset base (domain public) chu KHONG phai api base - xem ghi chu pplus_asset_base_url().
    return pplus_asset_base_url() . '/' . ltrim($relative_path, '/');
}

/**
 * Chuan hoa "urlHerf" tra ve tu API (Office/_GetOffices, Office/_GetDetail...) truoc khi dung lam
 * <a href>. API tra ve URL KHONG co dau "/" cuoi (vd "/en/office-for-rent/ha-noi/hoan-kiem-district/
 * 63-ly-thai-to-building-465") - WordPress redirect_canonical() se tu 301 sang ban co dau "/" cuoi,
 * va trong qua trinh do LANG LANG XOA MAT tien to "/en" (cung 1 loi da gap va sua nhieu lan trong
 * theme nay cho cac link tu viet, xem header.php/front-page.php...). Truoc day chi anh huong link
 * tu viet (home_url($lang_prefix...)); day la lan dau "urlHerf" tho tu API bi dung truc tiep khong
 * qua buoc chuan hoa nay, nen loi chi lo ro khi click tu 1 trang tieng Anh (vi urlHerf ban viet
 * khong co tien to /en nen redirect khong lam mat gi ca, che khuat loi cho toi bay gio).
 */
function pplus_office_url(?string $url_herf): string {
    $url_herf = trim((string) $url_herf);
    if ($url_herf === '') {
        return '#';
    }
    // 24/10/2026: web chinh KHONG co "/" cuoi (canonical + sitemap + link noi bo) -> giu nguyen dang API tra ve, chi
    // bo "/" cuoi neu co. Truoc day them "/" cuoi (WP permalink dep) -> moi URL lech web chinh 1 ky tu.
    return rtrim($url_herf, '/');
}

/**
 * Ten hang muc dich vu (Van phong rieng/Cho ngoi co dinh/Cho ngoi linh hoat/Dang ky kinh doanh) -
 * port GlobalConstants.ts OfficeTypeList (dung chung boi office-list.component.ts
 * GetNameLangeOfficeType() va detail.component.ts getOfficeTypeName()). Backend KHONG co ban tieng
 * Anh cho 4 ten nay (xac nhan qua production that: ca panel loc "Loại văn phòng" lan nhan tren the
 * san pham deu chi tra ve tieng Viet) - NHUNG day la 1 tap CO DINH chi 4 gia tri (hang so trong
 * code Angular, khong phai noi dung CMS co the sua), nen quyet dinh (theo yeu cau nguoi dung) tu
 * dich san o day thay vi cho backend, dung pplus_t() nhu moi chuoi tinh khac trong theme.
 */
function pplus_office_type_name($value): string {
    $names = [
        1 => pplus_t('Văn phòng riêng', 'Private Office'),
        2 => pplus_t('Chỗ ngồi cố định', 'Dedicated Desk'),
        3 => pplus_t('Chỗ ngồi linh hoạt', 'Hot Desk'),
        4 => pplus_t('Đăng ký kinh doanh', 'Business Registration'),
    ];
    return $names[(int) $value] ?? '';
}

/**
 * Giai ma "zone_stringify" (4 goc chu nhat, filter.zone_stringify tra ve tu Reference/GetIndex*)
 * thanh bounds ne/sw dung duoc ngay cho Office/_GetOffices (xem pplus_get_office_list()). Dung
 * min/max tren ca 4 goc thay vi tin ten truong "Bl/Br/Tl/Tr" (Bottom-left/...) - du lieu thuc te
 * KHONG theo dung quy uoc do (vd BlLocationLng > BrLocationLng, nguoc voi "trai" thuong <= "phai"
 * ve kinh do) - da xac nhan qua debug thuc te, tin ten truong se gan nham ne/sw nguoc hoan toan.
 *
 * @return array{ne_lat:float,ne_lng:float,sw_lat:float,sw_lng:float}|null
 */
function pplus_zone_to_bounds(?string $zone_stringify): ?array {
    if (empty($zone_stringify)) {
        return null;
    }
    $zone = json_decode($zone_stringify, true);
    if (!is_array($zone) || !isset($zone['BlLocationLat'], $zone['BlLocationLng'])) {
        return null;
    }
    $lats = [
        (float) $zone['BlLocationLat'],
        (float) ($zone['TlLocationLat'] ?? $zone['BlLocationLat']),
        (float) ($zone['BrLocationLat'] ?? $zone['BlLocationLat']),
        (float) ($zone['TrLocationLat'] ?? $zone['BlLocationLat']),
    ];
    $lngs = [
        (float) $zone['BlLocationLng'],
        (float) ($zone['BrLocationLng'] ?? $zone['BlLocationLng']),
        (float) ($zone['TlLocationLng'] ?? $zone['BlLocationLng']),
        (float) ($zone['TrLocationLng'] ?? $zone['BlLocationLng']),
    ];
    return [
        'ne_lat' => max($lats),
        'ne_lng' => max($lngs),
        'sw_lat' => min($lats),
        'sw_lng' => min($lngs),
    ];
}

/**
 * Ep "apartmentTypeList" trong 1 filter goi Apartment/_GetApartments VE "1,2" (can ho/chung cu
 * binh thuong) - chan TAN GOC tai moi noi filter duoc gui len API that (14/9, theo yeu cau nguoi
 * dung: "loai bo hoan toan loai hinh biet thu ra khoi bo goi API, de nhung setup cu co goi loai
 * nha/biet thu cung khong goi ra"). Xac nhan qua 2 lan goi API that RIENG BIET:
 * 1) POST {"apartmentTypeList":"3",...} toi Apartment/_GetApartments tra ve THAT du lieu biet thu
 *    ("Villa Ciputra"/"Villa StarLake") - endpoint nay khong tu chan gi ca.
 * 2) QUAN TRONG HON: filter that cua 1 "vung" tuy chinh tren menu (vd alias "pho-nguoi-nhat", loai=3
 *    trong nhom apartmentLuxury cua Home/HeaderInvokeAsync) tra ve "apartmentTypeList" RONG (khong
 *    phai "1,2" nhu filter cua 1 quan) - va goi thang filter rong do (khong sua gi) ra ket qua CO
 *    LAN "Villa Ciputra" (apartmentType=3) ngay dau danh sach. Nghia la "rong" o phia API nay =
 *    "TAT CA loai, bao gom ca biet thu", KHONG PHAI "khong loc gi" - nen KHONG THE chi sua khi thay
 *    ma "3", PHAI LUON ep ve "1,2" ke ca khi filter goc de trong.
 *
 * Dung cho MOI noi filter co the den tu nguon KHONG kiem soat duoc truc tiep (jsonSearch cua 1 nhom
 * trang chu cau hinh qua admin .NET, hoac filter tra ve nguyen ban tu Reference/GetIndex) - khong
 * dua vao rewrite rule/alias whitelist (se sot khi co alias/vung moi).
 *
 * @param array $filter Filter chuan bi gui len Apartment/_GetApartments.
 * @return array Cung mang, "apartmentTypeList" LUON la "1,2" hoac 1 tap con khong co ma "3".
 */
function pplus_strip_villa_type(array $filter): array {
    $types = (string) ($filter['apartmentTypeList'] ?? '');
    $list  = array_filter(array_map('trim', explode(',', $types)), static fn ($t) => $t !== '3' && $t !== '');
    $filter['apartmentTypeList'] = !empty($list) ? implode(',', $list) : '1,2';
    return $filter;
}

/**
 * Bounds (ne/sw) cho 1 TINH/THANH, dung CHUNG cho MOI trang quan/phuong/duong/hang trong tinh do
 * (KHONG dung bounds rieng cua tung quan/duong) - goi Reference/GetIndexProvince (co cache transient,
 * re khi lap lai) + pplus_zone_to_bounds() tren "zone_stringify" cua CHINH TINH.
 *
 * Bug thuc te nguoi dung bao cao (15/8/2026): trang Quan Hoan Kiem tren local CHI ra 22 ket qua/2
 * trang, ban chinh (propertyplus.vn) ra 77/5 trang cho CUNG 1 URL. 2 lop nguyen nhan da xac nhan
 * qua goi truc tiep pplus_get_office_list() (bo qua UI), THU TU DA THU:
 * 1) Dung bounds tu zone_stringify cua RIENG quan (4 goc chu nhat khoanh vung rat hep, vd Hoan Kiem
 *    chi ~1.9km be ngang) -> 22 (khop loi). Zone rieng cua quan hop de VE polygon/tam ban do, nhung
 *    qua hep de LOC van phong (loai bo nham van phong o ria quan).
 * 2) Thu doi sang 4 truong "ne_lat/ne_lng/sw_lat/sw_lng" co san TRONG CUNG object filter (tuong la
 *    vung "Angular thuc su dung" vi khong can giai ma gi) -> 77 cho Hoan Kiem (Ha Noi, DUNG), NHUNG
 *    xac nhan lai voi Quan 4 (TP.HCM, cung loai=1 that, dataSelected day du) thi 4 truong nay LAI
 *    TRA VE Y HET toa do Ha Noi (21.08/20.97) - BUG PHIA .NET: 4 truong nay bi "dinh" o gia tri Ha
 *    Noi cho MOI tinh/thanh, khong tinh dung theo tung request (zone_stringify van dung rieng tung
 *    quan/tinh, xac nhan qua Quan 4 co zone_stringify DUNG toa do TPHCM ~10.75/106.7) - dung 4 truong
 *    nay se loc SAI hoan toan cho moi tinh/thanh KHAC Ha Noi, khong an toan de dung.
 * 3) GIAI PHAP DUNG: van dung zone_stringify (qua pplus_zone_to_bounds(), dang tin cay, tinh rieng
 *    dung tung request) nhung lay o CAP TINH/THANH (GetIndexProvince) thay vi cap quan/duong rieng -
 *    vung TINH tu nhien rong hon nhieu (bao trum CA quan), khong loai nham van phong o ria quan nao
 *    ca, van giu duoc loi ich ban dau cua bounds (tranh total_count=0 sai o 1 so tinh/thanh nhu
 *    TPHCM) ma khong can dung 4 truong ne_lat.. dang loi. Da xac nhan qua goi that: Hoan Kiem + bounds
 *    TINH Ha Noi -> dung 77 (khop production).
 *
 * @return array{ne_lat:float,ne_lng:float,sw_lat:float,sw_lng:float}|null
 */
function pplus_province_bounds(string $province_alias): ?array {
    if ($province_alias === '') {
        return null;
    }
    $province_zone = pplus_api_get('vi/Reference/GetIndexProvince', ['alias' => $province_alias], 600);
    return pplus_zone_to_bounds($province_zone['result']['filter']['zone_stringify'] ?? null);
}

function pplus_current_lang(): string {
    // Tien to /en/ hoac /ja/ da duoc phat hien SOM trong inc/language-route.php (truoc khi WP
    // parse_request() chay) va luu vao hang so PPLUS_LANG (vi|en|ja).
    $default = defined('PPLUS_LANG') ? PPLUS_LANG : 'vi';
    return apply_filters('pplus_current_lang', $default);
}

/**
 * Tra ve dictionary dich {name => value} cho 1 ngon ngu (en/ja) - PORT tu Angular:
 * homeService.GetLangBase(lang) = GET {api}/{lang}/Home/GetLangApi, .result la mang {name, value}.
 * VI khong co dictionary (Localizer tra thang key). Cache transient 1 ngay + nho cap-request.
 *
 * @return array<string,string>
 */
function pplus_lang_dictionary(string $lang): array {
    static $cache = [];
    if (isset($cache[$lang])) {
        return $cache[$lang];
    }
    if ($lang !== 'en' && $lang !== 'ja') {
        return $cache[$lang] = [];
    }
    $data = pplus_api_get("{$lang}/Home/GetLangApi", [], DAY_IN_SECONDS);
    $map  = [];
    foreach ((array) ($data['result'] ?? []) as $row) {
        if (isset($row['name'])) {
            $map[(string) $row['name']] = (string) ($row['value'] ?? $row['name']);
        }
    }
    return $cache[$lang] = $map;
}

/**
 * Dich 1 chuoi TINH theo ngon ngu hien tai - PORT NGUYEN cua AppComponentBase.Localizer():
 * vi => tra thang $vi_key; en/ja => tra cuu dictionary GetLangApi theo name==$vi_key, fallback ve
 * chinh $vi_key khi khong tim thay. Dung cho MOI label/menu/button viet cung trong theme
 * (header/footer/page-*). KHONG dung cho du lieu API (da tu doi ngon ngu qua {lang}/... trong URL).
 */
function pplus_localize(string $vi_key): string {
    $lang = pplus_current_lang();
    if ($lang === 'vi') {
        return $vi_key;
    }
    $dict = pplus_lang_dictionary($lang);
    return $dict[$vi_key] ?? $vi_key;
}

/**
 * Danh sach ngon ngu - PORT GlobalConstants.language (vi/en/ja).
 * @return array<int,array{DisplayName:string,Name:string}>
 */
function pplus_language_options(): array {
    return [
        ['DisplayName' => 'Tiếng Việt', 'Name' => 'vi'],
        ['DisplayName' => 'English',    'Name' => 'en'],
        ['DisplayName' => '日本語',      'Name' => 'ja'],
    ];
}

/**
 * Tien to duong dan theo ngon ngu hien tai: '/vi', '/en' hoac '/ja' (khop Angular Resi: urlLang cho ca vi).
 */
function pplus_lang_prefix(?string $lang = null): string {
    $lang = $lang ?: pplus_current_lang();
    // 24/10/2026: CA 3 ngon ngu deu co tien to tuong minh (/vi, /en, /ja) - giong web chinh (xem inc/url-canonical.php).
    return in_array($lang, ['vi', 'en', 'ja'], true) ? ('/' . $lang) : '/vi';
}

/**
 * Ghep 1 URL noi bo co tien to ngon ngu hien tai. $path bat dau bang '/'.
 */
function pplus_lang_url(string $path): string {
    return home_url(pplus_lang_prefix() . '/' . ltrim($path, '/'));
}

/**
 * URL DAY DU cua trang HIEN TAI, GIU LAI dung tien to ngon ngu (vd "/en/...", "/ja/...") - dung lam
 * $base cho add_query_arg() khi can sinh link phan trang/sap xep TREN CHINH trang dang xem.
 *
 * SUA 30/9 (bug thuc te nguoi dung bao cao: "bấm trang 2 chuyển trang thì lỗi"): add_query_arg()
 * KHONG truyen $base se tu dung $_SERVER['REQUEST_URI'] lam goc - nhung inc/language-route.php DA
 * STRIP SAN tien to "/en"/"/ja" khoi bien nay TU RAT SOM (truoc khi bat ky code nao khac trong
 * functions.php chay, xem file do) de cac rewrite rule khop dung, nen add_query_arg() "khong bao
 * gio" con thay tien to ngon ngu nua - link phan trang tren MOI trang EN/JA co pagination (A3/A4:
 * page-reference.php, A1: page-apartment-list.php, "Dự án cụ thể": page-project-detail.php) deu bi
 * mat tien to, dua nguoi dung ve URL KHONG dau, PPLUS_LANG tu dong roi ve 'vi' trong khi duong dan
 * lai la 1 alias kieu EN (vd "rental-apartment/.../dong-da-district") - Reference/GetIndex goi voi
 * lang='vi' + alias EN se KHONG khop du lieu nao ca, ra thang trang loi API.
 */
function pplus_current_url(): string {
    return home_url(pplus_lang_prefix() . ($_SERVER['REQUEST_URI'] ?? '/'));
}

/**
 * Dich 1 chuoi text TINH (khong phai du lieu API) theo ngon ngu hien tai - WordPress i18n that
 * (wp_set_locale()/.po-.mo) khong duoc dung o day vi site khong doi locale that su theo /en/,
 * chi doi field text hien thi. Dung cho label/breadcrumb/button viet cung trong theme (header,
 * footer, cac page-*.php) - KHONG dung cho du lieu tra ve tu pplus_api_get() (da tu doi ngon
 * ngu dung qua {lang}/... trong URL goi API roi).
 */
function pplus_t(string $vi, string $en): string {
    // SUA 30/9 (kiem tra da ngon ngu theo yeu cau nguoi dung - bug NGHIEM TRONG NHAT tim thay trong
    // dot kiem tra nay): ham nay truoc gio CHI phan biet 'en', MOI ngon ngu khac (bao gom 'ja') deu
    // roi ve $vi - nghia la TOAN BO noi dung tinh viet qua pplus_t() tren CA THEME (~240 lan goi,
    // 22 file: page-about.php, page-contact.php, page-favourite.php, page-office-detail.php,
    // single-news.php, archive-news.php, parts/office-card.php...) hien 100% TIENG VIET cho nguoi
    // dung dang xem ban tieng Nhat - xac nhan qua kiem tra truc tiep "/ja/about-us/": ca trang (tieu
    // de, breadcrumb, moi doan van) deu la VI thay vi JA, du URL/menu xung quanh da dung JA. Sua tai
    // 1 diem duy nhat: doi voi 'ja', tra cuu qua CHINH dictionary that (GetLangApi, khop theo khoa
    // $vi - cung co che voi pplus_localize()) thay vi mac dinh ve VI - loi ich toi da 240 cho goi
    // ham nay cung 1 luc, khong can sua tung file rieng le.
    $lang = pplus_current_lang();
    if ($lang === 'en') {
        return $en;
    }
    if ($lang === 'ja') {
        $dict = pplus_lang_dictionary('ja');
        return $dict[$vi] ?? $vi;
    }
    return $vi;
}

/**
 * Doan URL tinh "cho-thue-van-phong" (goc cua A1/A3/A4) - ban tieng Anh THAT cua production la
 * "office-for-rent" (KHAC HAN, khong phai giu nguyen tieng Viet), xac nhan qua doi chieu truc tiep
 * hreflang tren propertyplus.vn (17/8/2026): "/cho-thue-van-phong/ha-noi" (vi) <-> "/en/office-for-
 * rent/ha-noi" (en). Truoc gio site nay dung CHUNG 1 slug "cho-thue-van-phong" cho ca 2 ngon ngu
 * (chi doi tien to /en) - dung cho URL KHONG bi loi/gay nham, nhung khong khop URL that cua
 * production (bug thuc te nguoi dung bao cao). Dung ham nay o MOI noi xay link toi A1/A3/A4 thay vi
 * ghi thang chuoi "cho-thue-van-phong" - xem cac rewrite rule EN moi trong inc/reference-index-
 * route.php + functions.php (A1/A3) khop nguoc lai dung segment nay.
 */
/**
 * Goc URL danh sach can ho cua ngon ngu hien tai: VI "cho-thue-can-ho", EN/JA "rental-apartment" (khop route Angular
 * Resi). KHONG nham voi pplus_office_list_base_slug() ben duoi (cua ban Van phong cu, "cho-thue-van-phong"/"office-for-rent").
 */
function pplus_apartment_list_root(?string $lang = null): string {
    return ($lang ?: pplus_current_lang()) === 'vi' ? 'cho-thue-can-ho' : 'rental-apartment';
}

function pplus_office_list_base_slug(): string {
    return pplus_current_lang() === 'en' ? 'office-for-rent' : 'cho-thue-van-phong';
}

/**
 * Chuan hoa "alasProvince" tra ve tu API .NET (_GetDetail) truoc khi ghep vao URL breadcrumb -
 * PORT tu Angular detail.component.ts: `this.Model.alasProvince.replace("thanh-pho-ho-chi-minh",
 * "ho-chi-minh")`. .NET tra alias DAI cho TPHCM: "thanh-pho-ho-chi-minh" (VI) / "ho-chi-minh-city"
 * (EN), nhung URL that cua production dung "ho-chi-minh" (khop pplus_office_list_base_slug +
 * rewrite route). WordPress truoc day dung alias THO -> breadcrumb toa nha HCM tro sai URL (404) o
 * ca VI lan EN. Angular goi CUNG API nhung lam .replace() nay nen dung; day la lo hong phia WP, KHONG
 * phai loi data .NET. (Ha Noi khong bi: alias "ha-noi" da dung san.)
 */
function pplus_normalize_province_alias(string $alias): string {
    return str_replace(['thanh-pho-ho-chi-minh', 'ho-chi-minh-city'], 'ho-chi-minh', $alias);
}

/**
 * Duong dan hien tai (KHONG con tien to /en, vi inc/language-route.php da strip khoi REQUEST_URI/
 * PATH_INFO tu truoc do roi) - dung de xay link doi ngon ngu ma VAN O LAI trang hien tai, thay vi
 * luon nhay ve trang chu nhu code cu (home_url($lang_prefix . '/')) - xem feedback.
 */
function pplus_current_path(): string {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    return $path ?: '/';
}

/** Doi goc duong dan (doan dau, KHONG co tien to ngon ngu) sang goc cua ngon ngu dich. Goc dung chung / khong biet -> giu nguyen. */
function pplus_switch_root(string $path, string $target_lang): string {
    $map = [
        'vi' => ['rental-apartment' => 'cho-thue-can-ho', 'about-us' => 've-chung-toi', 'contact' => 'lien-he'],
        'en' => ['cho-thue-can-ho' => 'rental-apartment', 've-chung-toi' => 'about-us', 'lien-he' => 'contact'],
    ];
    $to   = $target_lang === 'vi' ? 'vi' : 'en'; // ja dung chung goc voi en
    $segs = explode('/', ltrim($path, '/'), 2);
    if (isset($map[$to][$segs[0]])) {
        $segs[0] = $map[$to][$segs[0]];
        return '/' . implode('/', $segs);
    }
    return $path;
}

/**
 * Alias cua 1 khu vuc (quan/loai can ho/...) o ngon ngu dich, tra cuu qua Reference/GetIndex (aliasVi/aliasEn/aliasJa; co cache 10 phut).
 * Dung cho trang du an ("{tinh}/{quan}/{du-an}"): alias quan khac nhau theo ngon ngu (quan-ba-dinh / ba-dinh-district).
 */
function pplus_area_alias_for_lang(string $province, string $alias, string $target_lang): ?string {
    $model = pplus_get_reference_index($province, $alias, pplus_current_lang());
    $data  = $model['dataSelected'] ?? [];
    $key   = ['vi' => 'aliasVi', 'en' => 'aliasEn', 'ja' => 'aliasJa'][$target_lang] ?? 'aliasEn';
    $found = (string) ($data[$key] ?? '');
    if ($found === '' && $target_lang === 'ja') {
        $found = (string) ($data['aliasEn'] ?? ''); // JA khong co alias rieng -> dung alias EN (giong API)
    }
    return $found !== '' ? $found : null;
}

/**
 * Sinh URL doi ngon ngu, GIU NGUYEN trang hien tai (chi doi/bo tien to /en).
 *
 * KHONG dung duoc cho trang chi tiet van phong (page-office-detail.php): URL vi/en cua CUNG 1
 * toa nha KHAC HAN nhau ca ve route prefix (cho-thue-van-phong vs office-for-rent) lan alias
 * (vd "toa-nha-63-ly-thai-to" vs "63-ly-thai-to-building") - xac nhan qua doi chieu truc tiep
 * API that: Office/_GetDetail YEU CAU DUNG alias theo tung ngon ngu, sai alias tra ve rong ngay
 * ca khi id/province/district dung (bug thuc te da gap: doi ngon ngu tren A2 ca 2 chieu deu 404).
 * page-office-detail.php tu dang ky filter "pplus_language_switch_url" (xem
 * pplus_get_office_url_in_lang() ben duoi) de tra ve dung URL thay vi chi doi tien to nhu o day.
 */
function pplus_language_switch_url(string $target_lang): string {
    $path    = pplus_current_path();
    $prefix  = pplus_lang_prefix($target_lang);
    // 24/10/2026: ngoai tien to con phai doi GOC duong dan (web chinh: vi/cho-thue-can-ho <-> en/rental-apartment, vi/ve-chung-toi
    // <-> en/about-us, vi/lien-he <-> en/contact). Chi doi tien to se ra URL cheo ngon ngu -> 404 (xem pplus_root_allowed_for_lang()).
    // Alias doan sau giu nguyen; cac trang co alias khac nhau theo ngon ngu (khu vuc, blog, can ho, dich vu...) tu ghi de qua filter.
    $path = pplus_switch_root($path, $target_lang);
    $default = home_url($prefix . ($path === '/' ? '' : $path)); // trang chu: /vi, /en, /ja (home_url bo "/" cuoi)
    return apply_filters('pplus_language_switch_url', $default, $target_lang);
}

/**
 * Tra ve urlHerf cua CHINH XAC 1 van phong (theo projectId, ngon ngu-doc-lap) o ngon ngu chi dinh -
 * dung de sinh link doi ngon ngu dung tren A2 (xem page-office-detail.php), vi Office/_GetDetail
 * khong the goi truc tiep bang alias cua ngon ngu con lai (xem ghi chu pplus_language_switch_url()).
 *
 * Office/_GetOffices (danh sach) lai chap nhan loc theo "project_id" MA KHONG can alias/toa do map -
 * xac nhan qua goi API that: {lang}/Office/_GetOffices voi payload {project_id: 465} tra ve dung
 * total_count=1, item dau tien co urlHerf CHINH XAC theo dung ngon ngu duoc goi (province/district
 * trong payload hoan toan bi bo qua khi loc theo project_id, khong can truyen).
 */
function pplus_get_office_url_in_lang(int $project_id, int $project_type, string $lang): ?string {
    if ($project_id <= 0) {
        return null;
    }
    $payload = [
        'skip'        => 0,
        'page_count'  => 1,
        'ProjectType' => $project_type,
        'SortType'    => 2,
        'project_id'  => $project_id,
    ];
    $data     = pplus_api_post_json("{$lang}/Office/_GetOffices", $payload, 600);
    $url_herf = $data['result']['listOffice'][0]['urlHerf'] ?? '';
    return $url_herf !== '' ? $url_herf : null;
}

/**
 * Goi HTTP that toi .NET (GET hoac POST JSON). Khong cache - chi cho pplus_api_cached() / tac vu refresh.
 */
function pplus_api_http(string $method, string $url, ?array $payload, int $timeout): ?array {
    if ($method === 'POST') {
        $response = wp_remote_post($url, [
            'timeout' => $timeout,
            'headers' => ['Content-Type' => 'application/json; charset=utf-8', 'Accept' => 'application/json'],
            'body'    => wp_json_encode($payload ?? []),
        ]);
    } else {
        $response = wp_remote_get($url, [
            'timeout' => $timeout,
            'headers' => ['Accept' => 'application/json'],
        ]);
    }
    // 24/10/2026 (port ban Van phong ba05c18): ghi lai LOAI loi de response-headers.php quyet dinh HTTP status/Cache-Control.
    //  - 'api-failure' = CHAC CHAN tam thoi (loi mang/timeout, HTTP 408/429/502/503/504) -> trang loi se tra 503 + Retry-After.
    //  - 'api-error'   = mo ho (5xx khac, body khong phai JSON). Backend tra 302 -> trang dang nhap (HTML 200) cho bai viet/can ho
    //    KHONG TON TAI nen KHONG coi la su co: chi gan no-store, khong doi ma trang thai.
    //  - HTTP 4xx khac: khong danh dau (khong ton tai that su).
    $code = is_wp_error($response) ? 0 : (int) wp_remote_retrieve_response_code($response);
    if (is_wp_error($response) || $code >= 400) {
        $kind = (is_wp_error($response) || in_array($code, [408, 429, 502, 503, 504], true)) ? 'api-failure' : ($code >= 500 ? 'api-error' : '');
        pplus_api_note_failure($kind);
        return null;
    }
    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (!is_array($body)) {
        pplus_api_note_failure('api-error');
        return null;
    }
    return $body;
}

/** Ghi nho loai loi cua lan goi API gan nhat + danh dau response hien tai (xem inc/response-headers.php). */
function pplus_api_note_failure(string $kind): void {
    $GLOBALS['pplus_api_last_failure'] = $kind;
    if ($kind !== '' && function_exists('pplus_response_flags')) {
        pplus_response_flags($kind);
    }
}

/**
 * Cache API kieu STALE-WHILE-REVALIDATE (30/9/2026). Truoc day: het TTL (5-15 phut) thi NGUOI TRUY CAP KE TIEP
 * phai cho toan bo chuoi goi API tuan tu de dung lai trang (do that tren local: trang chu 17-23s luc cache
 * nguoi, 0.5s luc nong) va ca luc API .NET cham/loi thi moi trang deu doi het timeout 6-8s.
 * Bay gio:
 *   - con hieu luc (< ttl)  -> tra ngay.
 *   - het ttl nhung con ban cu (giu them 24h) -> TRA NGAY ban cu, lam moi NGAM sau khi da tra trang cho nguoi
 *     dung (fastcgi_finish_request neu co, khong thi WP-Cron) - khong ai phai cho.
 *   - chua co gi -> goi that (chi xay ra lan dau tien cua tung khoa).
 *   - API loi + khong co ban cu -> nho loi 30s (khong doi timeout lap lai tren moi request trong luc API sap).
 * Khoa transient dung tien to moi 'pplus_swr_' (khong dung lai dinh dang 'pplus_api_' cu).
 */
function pplus_api_cached(string $method, string $url, ?array $payload, int $ttl, int $timeout): ?array {
    if ($ttl <= 0) {
        return pplus_api_http($method, $url, $payload, $timeout);
    }
    $key   = 'pplus_swr_' . md5($method . $url . ($payload !== null ? wp_json_encode($payload) : ''));
    $entry = get_transient($key);

    if (is_array($entry) && array_key_exists('b', $entry)) {
        if (time() < (int) $entry['f']) {
            return $entry['b'];
        }
        pplus_api_queue_refresh($key, $method, $url, $payload, $ttl, $timeout);
        return $entry['b'];
    }

    $failed = get_transient($key . '_fail');
    if ($failed !== false) {
        // Dang trong cua so "nho loi 30s": van phai danh dau response nhu luc loi that (cung loai loi da nho).
        if (is_string($failed) && $failed !== '' && $failed !== '1') {
            pplus_api_note_failure($failed);
        }
        return null;
    }

    $GLOBALS['pplus_api_last_failure'] = '';
    $body = pplus_api_http($method, $url, $payload, $timeout);
    if ($body === null) {
        set_transient($key . '_fail', ($GLOBALS['pplus_api_last_failure'] ?? '') !== '' ? $GLOBALS['pplus_api_last_failure'] : 'none', 30);
        return null;
    }
    set_transient($key, ['b' => $body, 'f' => time() + $ttl], $ttl + DAY_IN_SECONDS);
    return $body;
}

/** Lam moi 1 khoa cache: chay sau khi da tra trang (neu ha tang cho phep) hoac qua WP-Cron. */
function pplus_api_queue_refresh(string $key, string $method, string $url, ?array $payload, int $ttl, int $timeout): void {
    if (get_transient($key . '_lock') !== false) {
        return; // dang co request khac lam moi khoa nay (chong don dap khi nhieu nguoi vao cung luc)
    }
    set_transient($key . '_lock', 1, 90);

    static $queue = [];
    static $registered = false;
    $queue[$key] = [$key, $method, $url, $payload, $ttl, $timeout];

    if (function_exists('fastcgi_finish_request')) {
        if (!$registered) {
            $registered = true;
            register_shutdown_function(static function () use (&$queue) {
                if (function_exists('fastcgi_finish_request')) {
                    fastcgi_finish_request();
                }
                foreach ($queue as $job) {
                    pplus_api_refresh_job(...$job);
                }
            });
        }
        return;
    }
    wp_schedule_single_event(time(), 'pplus_api_refresh', [$key, $method, $url, $payload, $ttl, $timeout]);
}

function pplus_api_refresh_job(string $key, string $method, string $url, ?array $payload, int $ttl, int $timeout): void {
    $body = pplus_api_http($method, $url, $payload, max($timeout, 15));
    $old  = get_transient($key);
    if ($body !== null) {
        set_transient($key, ['b' => $body, 'f' => time() + $ttl], $ttl + DAY_IN_SECONDS);
    } elseif (is_array($old) && array_key_exists('b', $old)) {
        // Refresh loi: giu ban cu, thu lai sau 60s (khong xoa, khong lam trang cham).
        set_transient($key, ['b' => $old['b'], 'f' => time() + 60], $ttl + DAY_IN_SECONDS);
    }
    delete_transient($key . '_lock');
}
add_action('pplus_api_refresh', 'pplus_api_refresh_job', 10, 6);

/**
 * Goi 1 endpoint .NET, JSON GET, co cache transient. Dung chung cho moi man hinh.
 *
 * @param string $path   Vi du "vi/Home/HeaderInvokeAsync" hoac "Office/_GetDetail"
 * @return array|null    null neu loi (KHONG lam sap trang - template phai tu xu ly fallback)
 */
function pplus_api_get(string $path, array $query = [], int $cache_seconds = 600): ?array {
    $url = pplus_api_base_url() . '/' . ltrim($path, '/');
    if (!empty($query)) {
        $url = add_query_arg($query, $url);
    }
    return pplus_api_cached('GET', $url, null, $cache_seconds, 6);
}

/**
 * @return array|null Shape: { result: { listMenu: [...], listCatalogBlog: [...] } }
 */
function pplus_get_header_data(): ?array {
    // Nho dem cap-request (static, KHAC voi transient tren - transient tranh goi lai API GIUA
    // CAC LUOT TAI TRANG, cai nay tranh doc lai/giai ma lai CUNG 1 transient NHIEU LAN trong CUNG
    // 1 lan tai trang). Xac nhan qua ra soat: ham nay dang duoc goi tu 2-3 noi khac nhau tren CUNG
    // 1 trang (parts/header.php + parts/office-filter-panel.php, them 1 lan nua tu page-reference.php
    // voi trang dich vu) - moi lan goi truoc day deu tu doc lai + giai ma transient (co the vai KB
    // du lieu menu toan site), du ket qua giong het nhau. Key theo ngon ngu vi HeaderInvokeAsync tra
    // ve du lieu KHAC NHAU giua vi/en.
    static $cache = [];
    $lang = pplus_current_lang();
    if (array_key_exists($lang, $cache)) {
        return $cache[$lang];
    }
    $data = pplus_api_get("{$lang}/Home/HeaderInvokeAsync", [], 900);
    $result = $data['result'] ?? null;

    // Chan biet thu TAN GOC (14/9, theo yeu cau nguoi dung): parts/header.php da THOI khong render
    // $header['apartmentVilla'] tu 13/9, nhung du lieu THAT (alias/id biet thu that) van con nguyen
    // trong chinh ket qua nay - xoa han khoi day de KHONG CON noi nao trong theme doc duoc mang nay
    // nua du co, vd 1 doan code cu duoc khoi phuc nham hoac copy lai vo tinh.
    if (is_array($result)) {
        unset($result['apartmentVilla']);
    }

    return $cache[$lang] = $result;
}

/**
 * Ban do "id => alias tieng Viet" cho TOAN BO cac muc trong menu header (quan/phuong/duong/hang/
 * dich vu) - dung de header.php luon dung DUNG 1 alias (VI) lam URL slug cho MOI ngon ngu, tranh
 * bug thuc te da gap: Home/HeaderInvokeAsync tra ve "alias" KHAC NHAU theo ngon ngu dang goi (vd
 * quan Hoan Kiem: vi="hoan-kiem", en="hoan-kiem-district" - "aliasVi"/"aliasEn" trong response LUON
 * null, khong dung duoc). Site nay dung CHUNG 1 slug cho ca 2 ngon ngu (chi doi tien to /en, xem
 * pplus_language_switch_url()) - neu link header xay dung tu alias theo ngon ngu HIEN TAI (vd dang
 * o /en/ thi dung alias tieng Anh), bam vao se ra URL mang alias EN, roi khi doi ngon ngu tiep (EN
 * -> VI) se bi sai vi pplus_get_reference_index() chi tu doi alias theo chieu VI -> EN, khong co
 * chieu nguoc lai. Truong "id" (khoa DB) da xac nhan GIONG NHAU giua 2 ngon ngu qua API that, dung
 * lam khoa doi chieu dang tin cay.
 *
 * Ngoai alias, map nay con luu kem "title"/"subTitle" ban tieng Viet cua tung phan tu de dung
 * lam fallback hien thi (xem pplus_header_label()) - phat hien qua doi chieu truc tiep API that:
 * 1 vai muc con (vd duong "Phú Thượng", id=6634) co ban ghi EN HOAN TOAN rong (title/alias/aliasEn
 * deu null, chi co id/stt/loai/menuItemID), khong phai do site nay chua dich ma do chinh backend
 * chua nhap noi dung tieng Anh cho muc do - neu in thang ra se ra 1 <li> rong giua danh sach, lam
 * lech hang doi voi cot ben canh trong CSS grid 2 cot (bug thuc te: "Find by street" tieng Anh nhin
 * lech hang so voi "Tìm theo đường" tieng Viet, do 1 muc rong chen giua).
 *
 * @return array<int,array{alias:string,title:string,subTitle:string}> id => { alias, title, subTitle } (vi)
 */
function pplus_get_header_vi_alias_map(): array {
    // Nho dem cap-request - xem ghi chu tuong tu trong pplus_get_header_data(): tren trang dich vu
    // (A4, EN) ham nay bi goi 2 lan (page-reference.php + parts/header.php) du cung ket qua.
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $cache_key = 'pplus_header_vi_alias_map';
    $cached = get_transient($cache_key);
    if ($cached !== false) {
        return $cache = $cached;
    }

    $data = pplus_api_get('vi/Home/HeaderInvokeAsync', [], 900);
    $list_menu = $data['result']['listMenu'] ?? [];

    $map = [];
    $walk = function (array $item) use (&$walk, &$map) {
        if (isset($item['id'])) {
            $map[(int) $item['id']] = [
                'alias'     => (string) ($item['alias'] ?? ''),
                'title'     => (string) ($item['title'] ?? ''),
                'subTitle'  => (string) ($item['subTitle'] ?? ''),
                'subTitle2' => (string) ($item['subTitle2'] ?? ''),
            ];
        }
        foreach (['dataListDistrict', 'dataListWard', 'dataListStreet', 'dataListVpHang', 'dataListVPDichVu'] as $child_key) {
            foreach ((array) ($item[$child_key] ?? []) as $child) {
                if (is_array($child)) {
                    $walk($child);
                }
            }
        }
    };
    foreach ((array) $list_menu as $item_menu) {
        if (!empty($item_menu['menu']) && is_array($item_menu['menu'])) {
            $walk($item_menu['menu']);
        }
        foreach (['dataListDistrict', 'dataListStreet', 'dataListVpHang', 'dataListVPDichVu'] as $child_key) {
            foreach ((array) ($item_menu[$child_key] ?? []) as $child) {
                if (is_array($child)) {
                    $walk($child);
                }
            }
        }
    }

    set_transient($cache_key, $map, 900);
    return $cache = $map;
}

/**
 * Tra ve alias DUNG DE LAM URL cho 1 phan tu menu header - dung DUNG ban theo NGON NGU HIEN TAI
 * ($item['alias'], da tu dung vi da goi Home/HeaderInvokeAsync voi tien to {lang} hien tai - xem
 * pplus_get_header_data()), fallback ve ban tieng Viet (qua $vi_alias_map) CHI khi ban hien tai
 * rong (bug du lieu backend that, vd duong "Phú Thượng" id=6634 khong co ban ghi tieng Anh nao ca -
 * xem ghi chu pplus_get_header_vi_alias_map()), KHONG PHAI vi khong the dung alias EN.
 *
 * TRUOC DAY (den 17/8/2026) ham nay LUON uu tien ban tieng Viet (nguoc lai voi o day) - luc do la
 * chu dich, vi routing A4 (inc/reference-index-route.php) CHI biet xu ly alias dang tieng Viet
 * trong URL (KHONG co rewrite rule/logic nao doc duoc alias tieng Anh truc tiep tu URL, phai do
 * tham nguoc qua vi truoc). Da xay THEM rewrite rule + logic doc alias EN truc tiep (xem
 * pplus_get_reference_index() tham so $alias_lang, rewrite rule "office-for-rent/..." trong
 * inc/reference-index-route.php) nen gio dung an toan duoc alias tieng Anh THAT (xac nhan qua goi
 * API that "en/Home/HeaderInvokeAsync": item alias da la "hoan-kiem-district"/"duy-tan-street"/
 * "grade-a" dung san, khong can tu doan/ghep chuoi).
 */
function pplus_header_alias(array $item, array $vi_alias_map): string {
    $current = (string) ($item['alias'] ?? '');
    if ($current !== '') {
        return $current;
    }
    if (isset($item['id'], $vi_alias_map[(int) $item['id']]['alias'])) {
        return $vi_alias_map[(int) $item['id']]['alias'];
    }
    return '';
}

/**
 * Tra ve nhan hien thi (title hoac subTitle) cho 1 phan tu menu header - fallback ve ban tieng
 * Viet (qua $vi_alias_map) khi ban hien tai (EN) rong, xem ghi chu pplus_get_header_vi_alias_map().
 */
function pplus_header_label(array $item, array $vi_alias_map, string $field = 'title'): string {
    $current = (string) ($item[$field] ?? '');
    if ($current !== '') {
        return $current;
    }
    if (isset($item['id'], $vi_alias_map[(int) $item['id']][$field])) {
        return $vi_alias_map[(int) $item['id']][$field];
    }
    return '';
}

/**
 * Tim alias tinh/thanh (vd "ho-chi-minh") tu TEN TIENG VIET (vd "Thành phố Hồ Chí Minh") - dung
 * de tra cuu lai bounds ban do DUNG tinh khi nguoi dung doi Tinh qua panel Bo loc trong khi dang
 * o 1 trang A4 da co san bounds cua tinh KHAC (xem page-reference.php: $map_bounds). Ho Chi Minh
 * dac biet CAN bounds moi ra ket qua (Office/_GetOffices bo qua Province neu thieu bounds voi 1
 * so tinh/thanh, xac nhan qua goi API that), nen khong the giu nguyen bounds cu cua tinh truoc do.
 *
 * Dung LAI pplus_get_header_vi_alias_map() (luon fetch ban VI, khong phu thuoc ngon ngu hien tai)
 * thay vi goi rieng 1 API khac - map nay da co san moi item (tinh/quan/phuong/duong...) keyed theo
 * id, chi can duyet tim item co subTitle/subTitle2 khop ten truyen vao.
 */
function pplus_find_province_alias_by_name(string $vi_name): string {
    if ($vi_name === '') {
        return '';
    }
    foreach (pplus_get_header_vi_alias_map() as $item) {
        if (($item['subTitle'] ?? '') === $vi_name || ($item['subTitle2'] ?? '') === $vi_name) {
            return (string) ($item['alias'] ?? '');
        }
    }
    return '';
}

/**
 * Tra ve gia tri DUNG DE LOC (gui len Office/_GetOffices lam Province/District/WardName) cho 1
 * phan tu menu header - LUON la ban tieng Viet co dau, BAT KE ban hien tai (EN) co rong hay khong
 * (khac han pplus_header_label() o tren, chi fallback khi EN rong - o day PHAI ep ve VI luon vi
 * ban EN LUON khac dinh dang).
 *
 * Xac nhan qua goi truc tiep API that (Office/_GetOffices): field Province chap nhan ca "Ha Noi"
 * lan "Hà Nội" (co ve co chuan hoa/khong phan biet dau), NHUNG District/WardName CHI khop khi
 * dung dung ten tieng Viet co dau (vd "Hoàn Kiếm"/"Phường Hoàn Kiếm") - truyen ban tieng Anh cua
 * header (subTitle/subTitle2 luc lang=en, vd "Office For Lease in Hanoi"/"Hoan Kiem Dist."/"Office
 * for Lease in Hoan Kiem Ward") LUON tra ve total_count=0 (bug thuc te: bo loc quan/phuong/tinh
 * tren panel tieng Anh khong tim ra ket qua nao ca). $vi_alias_map rong (dang o ban vi) -> tra ve
 * thang $item[$field] (da la tieng Viet san).
 */
function pplus_header_filter_value(array $item, array $vi_alias_map, string $field): string {
    if (isset($item['id'], $vi_alias_map[(int) $item['id']][$field]) && $vi_alias_map[(int) $item['id']][$field] !== '') {
        return $vi_alias_map[(int) $item['id']][$field];
    }
    return (string) ($item[$field] ?? '');
}

/**
 * @return array|null Shape: { result: { listMenuFooter: [...], myHistories: [...], phone1, phone2, email1, email2 } }
 */
function pplus_get_footer_data(): ?array {
    $lang = pplus_current_lang();
    $data = pplus_api_get("{$lang}/Home/FooterInvokeAsync", [], 900);
    return $data['result'] ?? null;
}

/**
 * @return array|null Shape that thuc te (xac nhan qua goi truc tiep API, KHONG phai shape cua ban
 *   Van phong): { datalistApartment, datalistApartmentVila, danhSachNhanVien, listBaoChis,
 *   khamPhaVietNamList, listApartmentType, ... }. Moi phan tu cua datalistApartment/
 *   datalistApartmentVila la { data: { id, title, subTitle, alias, jsonSearch }, apartmentList: null }
 *   - danh sach can ho CHUA duoc giai quyet, xem pplus_get_home_apartment_groups().
 */
function pplus_get_home_data(): ?array {
    $lang = pplus_current_lang();
    $data = pplus_api_get("{$lang}/Home/GetIndex", [], 300);
    return $data['result'] ?? null;
}

/**
 * Danh sach goi y "hot" (quan + loai can ho tron lan, co anh icon rieng, sap theo stT_GOIY) -
 * port tu search-base.component.ts GetDataSuccess() -> homeService.GetListSucces() ->
 * {lang}/Home/_GetIndexSuccess. Hien thi o hang "suggest-header" tren cung cua trang A3/A4 (VA
 * ca trang chu, nhung trang chu chua dung toi ham nay). KHONG PHAI cung du lieu voi
 * $header['dataLists']/$header['apartmentType'] (2 mang do la TOAN BO quan/loai cho mega-menu,
 * con day la 1 danh sach RIENG, ngan hon, do BE chon "hot" thu cong - xac nhan qua goi API that:
 * co ca item khong phai quan/loai chuan nhu "Phố Người Nhật"/"Căn Hộ Hồ Tây").
 *
 * @return array Moi item: {img, title, subTitle, alias, loai, stT_GOIY, ...}
 */
function pplus_get_index_success(): array {
    $lang = pplus_current_lang();
    $data = pplus_api_get("{$lang}/Home/_GetIndexSuccess", [], 600);
    return $data['result'] ?? [];
}

/**
 * Danh sach "gợi ý" DAY DU (16 muc: quan/loai/vung dac biet) hien trong luoi "Hoặc chọn theo gợi ý"
 * cua #searchModal (them 17/9, theo yeu cau nguoi dung) - port tu search-base.component.ts
 * GetAllDistrict() -> officeService.GetAllDistrict() -> {lang}/Apartment/_GetAllDistrict. KHAC
 * pplus_get_index_success() (12 muc, rieng cho hang pill "suggest-header") - endpoint nay tra ve
 * NHIEU HON (16 muc, xac nhan qua goi API that): them "04 Bedrooms" va 3 "vung dac biet" (loai=3,
 * "Lake View Apartment"/"Near Japanese School Hanoi"/"Easy Access To Industrial Parks") khong co
 * trong danh sach pill. Moi item co "alias" dung THANG duoc voi route /cho-thue-can-ho/ha-noi/
 * {alias}/ da co san (KHONG can route/logic moi - da xac nhan cung 1 co che voi cac pill hien tai,
 * inc/reference-index-route.php xu ly chung moi "loai": 1=quan, 3=vung, 9=so phong).
 *
 * "jsonSearch" (chuoi JSON filter: bedroom_qty/price_from/price_to/area_from/area_to) CHUA dung
 * toi trong lan nay - chi dung "alias" de dieu huong don gian, giong het cach hang pill dang lam.
 * Deferred: loc chi tiet theo jsonSearch se can page-reference.php doc them cac query var tuong
 * ung (ngoai pham vi hien tai, xem TODO trong parts/search-modal.php).
 *
 * @return array Moi item: {img, title, subTitle, alias, loai, jsonSearch, ...}
 */
function pplus_get_all_districts(): array {
    $lang = pplus_current_lang();
    $data = pplus_api_get("{$lang}/Apartment/_GetAllDistrict", [], 600);
    return $data['result'] ?? [];
}

/**
 * Danh sach video/bai viet o cot phai trang Dich vu (page-service.php) - PORT
 * ListServiceComponent.ngOnInit() (list-service.component.ts dong 32-39): goi 1 lan cho MOI
 * TypeSite (1-4), KHONG PHAI du lieu cua chinh noi dung tab dang xem (3 component tinh
 * the-management/housekeeping/techical KHONG goi API nao ca - chi component nay).
 *
 * @param int $type_site 1-4, xem TypeSite trong inc/service-route.php.
 * @return array<int,array{id:mixed,img:string,title:string,subTitle:string,content:string}>
 */
function pplus_get_service_type_list(int $type_site): array {
    $lang = pplus_current_lang();
    $data = pplus_api_get("{$lang}/Service/ServiceType", ['ListIdSiteBase' => $type_site], 600);
    return $data['result'] ?? [];
}

/**
 * Bang gia/tuy chon dich vu cho TypeSite 4 (Dang ky dich vu) - PORT
 * RegisterServiceComponent.ngOnInit() (register-service.component.ts dong 62-125): API tra ve
 * servicePrice[] voi "name" dang "Nhan|Ghi_chu" (ghep boi "|") va "type" 1-4 quyet dinh kieu input
 * (1=so, 2=Co/Khong, 3=select tu 1 chuoi JSON-nhu cac gia tri "Ten|Gia", 4=text/gia thuan) - chuan
 * hoa lai y het logic Angular truoc khi tra ve de parts/service-register.php chi viec lap va render,
 * KHONG lap lai logic parse o 2 noi.
 *
 * @return array{servicePrice: array<int,array>} servicePrice moi phan tu da co them 'value'
 *   (gia tri mac dinh) va, voi type==3, 'listSelect' (mang {nameC,priceC}).
 */
function pplus_get_service_regist(): array {
    $lang = pplus_current_lang();
    $data = pplus_api_get("{$lang}/Service/RegistService", [], 300);
    $result = $data['result'] ?? [];
    $list   = $result['servicePrice'] ?? [];

    foreach ($list as &$item) {
        $name_parts   = explode('|', (string) ($item['name'] ?? ''), 2);
        $item['name'] = $name_parts[0];
        $type         = (int) ($item['type'] ?? 0);

        if ($type === 1) { // So (number input)
            $default_parts       = explode('|', (string) ($item['default'] ?? ''), 2);
            $item['numberDefault'] = $default_parts[0] !== '' ? $default_parts[0] : '0';
            $item['value']          = $item['numberDefault'];
        } elseif ($type === 2) { // Checked (select Co/Khong)
            $item['value'] = '1';
        } elseif ($type === 3) { // Select tu danh sach "Ten|Gia"
            // Ban that (register-service.component.ts dong 90) lam sach chuoi bang mot chuoi
            // .replace() ky la (bo dau ngoac "[" dau/"]" cuoi, bo dau "\") truoc khi JSON.parse -
            // gia tri thuc te tra ve la 1 CHUOI JSON cua 1 MANG CHUOI (vd '["Co|100000","Khong|0"]'),
            // json_decode() PHP xu ly thang duoc dang nay ma khong can "lam sach" kieu Angular.
            $raw_list     = (string) ($item['value'] ?? '');
            $decoded      = json_decode($raw_list, true);
            $list_select  = [];
            if (is_array($decoded)) {
                foreach ($decoded as $option) {
                    $option_parts  = explode('|', (string) $option, 2);
                    $list_select[] = [
                        'nameC'  => $option_parts[0],
                        'priceC' => $option_parts[1] ?? '',
                    ];
                }
            }
            $item['listSelect'] = $list_select;
            $item['value']      = $list_select[0]['nameC'] ?? '';
        }
        // type==4 (text/gia thuan): giu nguyen $item['value'] nhu API tra ve, khong can xu ly them.
    }
    unset($item);

    return ['servicePrice' => $list];
}

/**
 * 1 nhom trong datalistApartment khong kem san danh sach can ho - phai goi rieng API voi filter
 * giai ma tu data.jsonSearch cua chinh nhom do.
 *
 * Dung Apartment/_GetApartments (endpoint danh sach day du, se dung cho trang A1 sau nay) CHU
 * KHONG PHAI Home/_GetApartments (endpoint "teaser" trang chu ma Angular goi) - ly do: da xac
 * nhan qua goi API that Home/_GetApartments LUON co dinh dung 4 ket qua GIA CAO NHAT tuyet doi
 * (tham so "take" gui len bi bo qua hoan toan), ma cac can trong CUNG 1 du an thuong co gia gan
 * nhau -> 1 du an dat tien de chiem het ca 4 cho hien thi (bug thuc te nguoi dung bao cao: "3-4/4
 * the la cung 1 du an"). Apartment/_GetApartments tra ve CUNG HET field/shape nhung gioi han
 * 12 ket qua/lan goi (cung khong doi duoc qua "take", nhung "skip" CO hoat dong - da xac nhan
 * qua API that: skip=0/12/24 tra ve 3 tap id hoan toan khac nhau) - goi 2 trang (skip 0 va 12,
 * 24 ung vien) de co nguon rong hon truoc khi loc trung theo du an.
 *
 * @param array $groups Mang tu $home['datalistApartment']
 * @return array Cung mang, moi phan tu duoc gan them khoa 'listApartments' (toi da 4 can, moi can
 *   thuoc 1 du an khac nhau, uu tien can gia cao nhat cua tung du an, thu tu ngau nhien - chay lai
 *   moi lan tai trang vi shuffle() nam NGOAI cache API, khong bi "dong cung" 1 bo suot 5 phut).
 */
function pplus_get_home_apartment_groups(array $groups): array {
    $lang = pplus_current_lang();
    foreach ($groups as &$group) {
        $filter = json_decode($group['data']['jsonSearch'] ?? '', true);
        if (!is_array($filter)) {
            $group['listApartments'] = [];
            continue;
        }
        // Chan biet thu TAN GOC (14/9) - "jsonSearch" la du lieu cau hinh qua admin .NET (khong
        // kiem soat truc tiep tu theme), phong khi 1 nhom duoc cau hinh chua "apartmentTypeList"
        // chua ma "3" (biet thu) - xem pplus_strip_villa_type().
        $filter = pplus_strip_villa_type($filter);

        $candidates = [];
        foreach ([0, 12] as $skip) {
            $page = pplus_api_post_json("{$lang}/Apartment/_GetApartments", array_merge($filter, ['skip' => $skip]), 300);
            $candidates = array_merge($candidates, $page['result']['listApartments'] ?? []);
        }

        // Loc trung theo projectId, GIU CAN DAU TIEN gap cho moi du an - danh sach da sap gia
        // giam dan tu API nen "dau tien" = can gia cao nhat cua chinh du an do.
        $by_project = [];
        foreach ($candidates as $apartment) {
            $project_id = $apartment['projectId'] ?? null;
            if ($project_id !== null && !isset($by_project[$project_id])) {
                $by_project[$project_id] = $apartment;
            }
        }
        $diverse = array_values($by_project);
        shuffle($diverse);

        $group['listApartments'] = array_slice($diverse, 0, 4);
    }
    unset($group);
    return $groups;
}

/**
 * Danh sach can ho cho trang A3/A4 (page-reference.php - landing theo tinh/quan/loai-can-ho).
 * Goi Apartment/_GetApartments voi $filter (nguyen ban tra ve tu pplus_get_reference_index(),
 * chi ghi de skip/price_sort_type theo trang/sap xep nguoi dung dang chon).
 *
 * Loc trung theo du an TRONG PHAM VI 1 trang (lay rong gap doi - 2 lan goi API, dung ky thuat
 * giong pplus_get_home_apartment_groups() - nhung KHONG shuffle, giu nguyen thu tu sap xep nguoi
 * dung dang chon vi day la danh sach that can phan trang on dinh, khong phai 1 khoi "gioi thieu").
 * Ly do can loc: 1 toa nha thuong co nhieu can cung toa do (vd, lng) gan nhu trung nhau -> tren
 * ban do cac marker gia chong len nhau nhin nhu chi co vai diem (bug thuc te nguoi dung bao cao:
 * "vao 1 quan...1 du an hien nhieu can roi long vao nhau, lam ban do chi co vai du an").
 *
 * QUAN TRONG - danh doi da biet (nguoi dung da duoc giai thich va chon huong don gian hon): loc
 * trung chi trong PHAM VI 1 trang, KHONG dam bao 1 du an chi xuat hien DUY NHAT 1 lan tren toan
 * bo cac trang (vd du an X co the vua o trang 1 vua o trang 3 neu co nhieu can trai gia rong) -
 * de lam dung nghia toan site can quet het (692 can ~ 58 lan goi API) roi cache lai, qua nang cho
 * 1 lan tai trang dau tien.
 *
 * @return array{listApartments: array, total_count: int} total_count dung de tinh so trang (van
 *   la tong so can THAT tu API, KHONG phai so can sau khi loc trung - xem ghi chu ben tren).
 *
 * SUA 22/9 (theo yeu cau nguoi dung): "$per_page" truoc day CO DINH "12" NGAY TRONG HAM nay (trung
 * lap voi hang so cung gia tri trong page-reference.php) - chuyen thanh THAM SO ($per_page, mac
 * dinh 12 de an toan neu con noi goi khac trong tuong lai) de page-reference.php tang len 18
 * (3 cot x 6 hang) ma KHONG can sua 2 noi. API THAT van CO DINH tra ve 12/lan goi bat ke gui gi
 * (xac nhan qua curl truc tiep voi "take"/"pageSize"/"limit" - deu bi bo qua, luon 12) nen ham nay
 * van phai GOI NHIEU LAN de gom du "$per_page" muc, KHONG the chi doi so lan goi.
 */
function pplus_get_apartment_list(array $filter, int $skip, int $price_sort_type, int $per_page = 12): array {
    $lang = pplus_current_lang();
    $api_page_size = 12; // CO DINH phia API that, KHONG doi duoc - xem docblock ham nay.

    // Chan biet thu TAN GOC (14/9) - phong khi $filter (nguyen ban tra ve tu
    // pplus_get_reference_index()) bi lot qua nhanh chan o do (vd goi truc tiep ham nay tu 1 noi
    // khac trong tuong lai, khong di qua pplus_get_reference_index()). Xac nhan qua goi API that:
    // "apartmentTypeList"="3" la ma loai biet-thu (khac "1,2"=can ho binh thuong); ep ve "1,2" bat
    // ky khi nao thay "3" xuat hien, KHONG chi xoa di (giu dung dinh dang chuoi CSV ham API can).
    $filter = pplus_strip_villa_type($filter);

    // Loc theo dung tọa độ 4 goc cua vung (zone_stringify) TAN GOC - theo yeu cau nguoi dung (17/9):
    // "ne_lat/ne_lng/sw_lat/sw_lng" tra ve san trong $filter (tu Reference/GetIndex) la BUG PHIA
    // .NET, LUON dinh o 1 gia tri co dinh cho toan Ha Noi bat ke alias nao (da xac nhan qua goi API
    // that: 3 alias khac nhau ra CUNG HET 4 so) - dung THANG 4 so do se KHONG loc duoc gi ca, du an
    // ngoai vung van hien (dung y het bug nguoi dung bao cao). "zone_stringify" (4 goc THAT, tinh
    // rieng dung tung alias) moi la nguon dang tin cay - GIAI MA LAI qua pplus_zone_to_bounds() (ham
    // da co san, dung on dinh cho nhanh Van phong/pplus_province_bounds()) va GHI DE 4 truong tren
    // truoc khi goi API, KHONG dung nguyen 4 gia tri "loi" co san trong $filter.
    $zone_bounds = pplus_zone_to_bounds($filter['zone_stringify'] ?? null);
    if ($zone_bounds !== null) {
        $filter = array_merge($filter, $zone_bounds);
    }

    // So lan goi API that can de gom du "$per_page" can SAU KHI loc trung du an - giu dung ty le
    // "2x" cua ban goc (12 muc dich -> 2 lan goi 12/lan = 24 tho), tang theo khi "$per_page" lon
    // hon 12 (18 muc dich -> 3 lan goi = 36 tho) thay vi co dinh "2 lan" nhu truoc (se khong du tho
    // cho 18 muc dich, ty le tho/dich giam tu 2x xuong ~1.3x).
    $api_calls = (int) ceil(($per_page * 2) / $api_page_size);

    $candidates = [];
    $total_count = 0;
    for ($i = 0; $i < $api_calls; $i++) {
        $page_filter = $filter;
        $page_filter['skip'] = $skip + ($i * $api_page_size);
        $page_filter['price_sort_type'] = $price_sort_type;
        $result = pplus_api_post_json("{$lang}/Apartment/_GetApartments", $page_filter, 300);
        $candidates = array_merge($candidates, $result['result']['listApartments'] ?? []);
        $total_count = max($total_count, (int) ($result['result']['total_count'] ?? 0));
    }

    // SUA 30/9 (bug thuc te nguoi dung bao cao: trang "Dự án cụ thể" - page-project-detail.php -
    // "38 chỗ ở" nhung MOI TRANG CHI HIEN 1 SAN PHAM): loc trung theo projectId o duoi day GIA DINH
    // $filter dang loc theo QUAN/KHU VUC (nhieu du an khac nhau, can tranh 1 du an chiem het trang -
    // xem docblock tren, theo dung yeu cau nguoi dung 17/9) - nhung khi $filter DA TU LOC SAN theo
    // DUNG 1 "project_id" (trang du an cu the, pplus_get_project_detail()'s filter), TOAN BO ket qua
    // deu CUNG 1 projectId, nen loc trung se giu lai DUY NHAT 1 can/trang, xoa mat 37/38 can con lai.
    // Bo qua buoc loc trung trong truong hop nay - dung nghia "hien tat ca can cua CHINH du an dang
    // xem", khong phai "da dang hoa danh sach nhieu du an".
    if (!empty($filter['project_id'])) {
        return [
            'listApartments' => array_slice($candidates, 0, $per_page),
            'total_count'    => $total_count,
        ];
    }

    // Giu thu tu (KHONG shuffle) - "dau tien" trong moi projectId van la can dung vi tri sap xep
    // (gia cao/thap/moi nhat...) da chon, chi bo cac can trung du an DUNG SAU can dau tien do.
    $seen_projects = [];
    $diverse = [];
    foreach ($candidates as $apartment) {
        $project_id = $apartment['projectId'] ?? null;
        if ($project_id !== null && isset($seen_projects[$project_id])) {
            continue;
        }
        if ($project_id !== null) {
            $seen_projects[$project_id] = true;
        }
        $diverse[] = $apartment;
    }

    return [
        'listApartments' => array_slice($diverse, 0, $per_page),
        'total_count'    => $total_count,
    ];
}

/**
 * (A2) Chi tiet 1 can ho - /cho-thue-can-ho/{province}/{district}/{project}/{id}. Doi chieu
 * office.service.ts GetDetail() (dong 37-44 - GET Apartment/_GetDetail?id=&province=&district=
 * &project=, KHAC HAN pplus_get_office_detail() dung "alias" cho van phong: o day tham so la
 * "project" khong phai "alias", va khong co field "alias" rieng - alias du an nam san trong
 * URL query "project" giong nguyen chuoi tren URL).
 *
 * Xac nhan qua goi API that (curl truc tiep id=3267): response GOM 7 khoa cap 1, nhung CHI
 * "apartmentDetail"/"markers"/"listUser" thuc su duoc detail.component.html/.ts dung toi -
 * "dataSelected"/"projectDetail"/"otherApartmentInProject"/"servicePrice" deu la field CHET (khong
 * xuat hien o bat ky dau trong .html/.ts that, xac nhan qua grep toan bo 2 file do) - KHONG bia
 * them section nao cho 4 khoa chet nay khi port (giu dung y that, khong lam "day du hon ban goc").
 *
 * @return array|null Shape: { apartmentDetail: {apartmentId, projectId, projectName, title,
 *   address, area, bedRoomQty, bathRoomQty, hasBathtub, price, priceString, description,
 *   projectDesc, lat, lng, photoList[], specialLocationList[], serviceIncludedList[],
 *   serviceNotIncludedList[], utilityList[], alasDistrict, alasProject, alasUrlBase, alasUrlBaseVi,
 *   videoUrl, ...}, markers: [{lat,lng,...}], listUser: [{img,title,content}] }
 */
function pplus_get_apartment_detail(int $id, string $province, string $district, string $project): ?array {
    $lang = pplus_current_lang();
    $data = pplus_api_get("{$lang}/Apartment/_GetDetail", [
        'id'       => $id,
        'province' => $province,
        'district' => $district,
        'project'  => $project,
    ], 600);
    return $data['result'] ?? null;
}

/**
 * (A3/A4 nhanh "Dự án cụ thể") Trang 1 du an cu the - /cho-thue-can-ho/{province}/{district}/{project}.
 * Doi chieu project.service.ts GetDetail() (dong 50-53: GET Project/Detail?id=) + reference-index.
 * component.ts nhanh "else" (dong 122-186, khi route co tham so "project"): id tach tu DOAN CUOI
 * cua alias sau dau "-" cuoi cung (giong het pplus_get_apartment_detail_from_query_vars(), xem
 * inc/apartment-detail-route.php).
 *
 * QUAN TRONG - xac nhan qua doc toan bo reference-index.component.html (189 dong, KHONG co 1 dong
 * nao doc "Model.projectDetail"/"Model.apartmentsInProject"): du response co ca 2 khoa nay (thong
 * tin toa nha rat day du: address/areas/beedRoom/powerBackup/ownership/photoList rieng cua du
 * an/specialLocationList/serviceIncludedList...), TRANG THAT KHONG HIEN THI chung o dau ca - trang
 * nay dung LAI y HET template cua trang "province/alias" (pplus_get_reference_index()): breadcrumb +
 * suggest-header + <app-search-base> (danh sach + ban do, loc theo "filter" - da co san "project_id"
 * dung san trong do) + bai viet SEO "dataSelected". "apartmentsInProject" thuc chat la du lieu THUA -
 * danh sach can ho HIEN THI THAT lai di qua search-base.loadData() tu chinh "filter" nay (goi
 * Apartment/_GetApartments binh thuong), KHONG doc truc tiep "apartmentsInProject". KHONG bia them
 * section "Giới thiệu dự án" nao tu projectDetail - giu dung nhu ban that.
 *
 * @return array|null Shape: { dataSelected: {title, content, metaTitle, metaDescription, subTitle,
 *   alias, isIndex}, filter: {...co san "project_id", dung THANG cho pplus_get_apartment_list()} }
 *   (projectDetail/apartmentsInProject/markers/questionList: co trong response nhung KHONG dung -
 *   xem ghi chu tren).
 */
function pplus_get_project_detail(int $id): ?array {
    $lang = pplus_current_lang();
    $data = pplus_api_get("{$lang}/Project/Detail", ['id' => $id], 600);
    return $data['result'] ?? null;
}

/**
 * Goi 1 endpoint .NET bang POST + JSON body (dung cho _GetOffices, _GetMarkers...).
 * Khac voi pplus_api_get() vi cac endpoint nay yeu cau POST JSON, khong phai GET query string.
 *
 * @return array|null null neu loi
 */
function pplus_api_post_json(string $path, array $payload, int $cache_seconds = 300): ?array {
    $url = pplus_api_base_url() . '/' . ltrim($path, '/');
    return pplus_api_cached('POST', $url, $payload, $cache_seconds, 8);
}

/**
 * Danh sach van phong cho trang /cho-thue-van-phong. Da doi chieu truc tiep code .NET that
 * (OfficeController._GetOffices + OfficeFilter.cs + ApiGetAllProjectInput.cs) de xac dinh
 * dung ten field can gui - khong doan qua Angular code (Angular gui ca object searchBase,
 * ta chi gui dung cac field controller thuc su doc: Province, District, ProjectType, SortType,
 * RatingLevel, price_from, price_to, skip, page_count).
 *
 * @param array $args { province?, district?, ward?, price_from?, price_to?, rating_level?, project_type?, page? }
 * @return array|null Shape: { listOffice: [...], total_count: N }
 */
function pplus_get_office_list(array $args): ?array {
    $lang = pplus_current_lang();
    $page = max(1, (int) ($args['page'] ?? 1));
    // Mac dinh 18 (PPlusConst.DefaultPageSize/GlobalConstants.searchFormGolbal.page_count) - nhung
    // "Văn phòng gần đó" tren A2 dung page_count=5 rieng (searchBaseDetail.page_count trong
    // detail.component.ts dong 217), cho phep ghi de qua $args.
    $page_count = isset($args['page_count']) ? max(1, (int) $args['page_count']) : 18;

    $payload = [
        'skip'       => ($page - 1) * $page_count,
        'page_count' => $page_count,
        // Mac dinh ProjectType=2 (van phong truyen thong) + SortType=2 (gia giam dan), giong
        // hanh vi thuc te cua ApiGetAllProjectInput khi SortType=0 se tu chuyen thanh 2.
        'ProjectType' => (int) ($args['project_type'] ?? 2),
        'SortType'    => (int) ($args['sort_type'] ?? 2),
    ];

    if (!empty($args['province'])) {
        $payload['Province'] = $args['province'];
    }
    if (!empty($args['district'])) {
        $payload['District'] = $args['district'];
    }
    if (!empty($args['ward'])) {
        $payload['WardName'] = $args['ward']; // xem OfficeFilter.cs field WardName
    }
    // "OfficeType" - phan biet loai van phong dich vu (chỗ ngồi cố định/văn phòng ảo/trọn gói/chỗ
    // ngồi linh hoạt) khi ProjectType=1 (dich vu, khac ProjectType=2 van phong truyen thong). Dung
    // cho trang loai=3 (page-reference.php, vd "cho-thue-cho-ngoi-co-dinh") - xac nhan gia tri truc
    // tiep qua API (filter.officeType), KHONG doan.
    if (isset($args['office_type']) && $args['office_type'] !== '') {
        $payload['OfficeType'] = (int) $args['office_type'];
    }
    if (!empty($args['rating_level'])) {
        $payload['RatingLevel'] = $args['rating_level'];
    }
    // "StreetCode" - trang duong (page-reference.php loai=6). Xac nhan qua doi chieu truc tiep API
    // that: gui "Street" (ten duong dang text, vd "Duy Tân") bi backend BO QUA, tra ve gan het van
    // phong ca tinh (679 ket qua); phai gui "StreetCode" (vd "D00074", lay tu filter.streetCode) moi
    // loc dung, khop chinh xac danh sach production (36 ket qua, dung thu tu Epic Tower/The Vista
    // Duy Tân/IDMC Duy Tân Building).
    if (!empty($args['street_code'])) {
        $payload['StreetCode'] = $args['street_code'];
    }
    // "project_id" (chu thuong, KHONG phai "ProjectId") - dung khi nguoi dung chon 1 goi y "Tên dự
    // án" cu the tu autocomplete "Địa điểm" (xem inc/location-search.php). Da xac nhan qua goi API
    // that (xem pplus_get_office_url_in_lang() o duoi): {project_id: N} tra ve DUNG total_count=1
    // (chinh xac 1 toa do), va MOI dieu kien loc khac (Province/District...) trong payload bi backend
    // BO QUA hoan toan khi co project_id - dung y muon (chon 1 du an cu the = loc con dung 1 toa do).
    if (!empty($args['project_id'])) {
        $payload['project_id'] = (int) $args['project_id'];
    }
    // "project_ids" (danh sach ID toa nha, dang chuoi "1944,2029,...") - .NET reference (GetIndex) tra
    // field nay cho trang QUAN, dac biet TPHCM: filter.province/district deu RONG cho quan HCM (vd
    // quan-2/quan-9), CHI co project_ids moi loc dung toa nha cua quan (85 id cho quan-2). Angular gui
    // NGUYEN filter (co project_ids) nen dung; WP truoc day bo qua -> trang quan HCM ro ca tinh (bug #3
    // file loi 26/8). Quan HN dung district text (project_ids RONG) nen field nay chi anh huong HCM.
    // LUU Y: project_ids 1 minh -> 0; PHAI kem bounds (zone_stringify cua quan) moi ra ket qua (xac
    // nhan API: quan-2 project_ids khong bounds=0, + zone bounds=40 dung) - fallback bounds da xu ly.
    if (!empty($args['project_ids'])) {
        $payload['project_ids'] = (string) $args['project_ids'];
    }
    if (!empty($args['price_from'])) {
        $payload['price_from'] = (float) $args['price_from'];
    }
    if (!empty($args['price_to'])) {
        $payload['price_to'] = (float) $args['price_to'];
    }
    if (!empty($args['project_id_not_in'])) {
        $payload['project_idNotIn'] = (int) $args['project_id_not_in'];
    }
    // Vung ban do (ne_lat/ne_lng/sw_lat/sw_lng) - xac nhan qua doi chieu truc tiep voi API that:
    // Province/District dang chuoi TEXT khong du de tim ra van phong that o mot so tinh/thanh (vd
    // Ho Chi Minh, khac Ha Noi) - search-base.component.ts (Angular) LUON gui KEM bounds nay (tu
    // MapFilter(), doc truc tiep tu Google Map dang hien) cung voi Province/District trong MOI request
    // GetOffices, khong bao gio goi rieng chi Province/District. Thieu bounds se tra ve total_count=0
    // du van phong that su ton tai (bug thuc te da gap: trang quan HCM luon "Không tìm thấy...").
    if (isset($args['ne_lat'], $args['ne_lng'], $args['sw_lat'], $args['sw_lng'])) {
        $payload['ne_lat'] = (float) $args['ne_lat'];
        $payload['ne_lng'] = (float) $args['ne_lng'];
        $payload['sw_lat'] = (float) $args['sw_lat'];
        $payload['sw_lng'] = (float) $args['sw_lng'];
        $payload['zoom']   = (int) ($args['zoom'] ?? 13);
    }

    $data = pplus_api_post_json("{$lang}/Office/_GetOffices", $payload, 300);
    return $data['result'] ?? null;
}

/**
 * Danh cho nhanh "project_id"/"street_code" (chon 1 goi y "Địa điểm" cu the, xem page-office-
 * list.php + page-reference.php) - 2 nhanh nay TUNG can them $bounds de tranh total_count=0 sai o
 * TPHCM (xem ghi chu project_id/street_code trong pplus_get_office_list() o tren, bug 16/8/2026:
 * project_id=2043 Pearl 5 Building KHONG bounds -> 0; CO bounds TPHCM -> 1 dung).
 *
 * NHUNG bug thuc te MOI nguoc lai (17/8/2026): chon du an "Keangnam Landmark Ha Noi Tower"
 * (project_id=284) tu goi y autocomplete tren trang A1 -> "Không tìm thấy văn phòng phù hợp", du
 * du an nay CHAC CHAN co that (xac nhan qua pplus_get_office_list() truc tiep, bo qua UI: KHONG
 * bounds -> total_count=1 dung; CO bounds Ha Noi (pplus_province_bounds()) -> total_count=0 SAI).
 * Nguyen nhan: toa do that cua Keangnam (lat=21.0177746, lng=105.7846364, khu Mỹ Đình/Nam Từ Liêm)
 * nam NGOAI (phia tay) bounds tinh Ha Noi tinh tu zone_stringify (sw_lng=105.791 > 105.7846) - tuc
 * la CHINH bounds cap tinh (da la lua chon "rong nhat" co the, xem pplus_province_bounds()) van
 * chua du rong de bao trum HET moi du an that trong tinh, chu khong phai loi tinh toan sai.
 *
 * Ket luan: bounds vua co the CAN THIET (TPHCM) vua co the LAM HONG (Keangnam) tuy du an - khong co
 * 1 lua chon "luon dung" duy nhat cho toan bo du an/duong. Giai phap AN TOAN: thu KHONG bounds
 * TRUOC (truong hop pho bien, nhanh - 1 lan goi), CHI fallback sang CO bounds neu lan dau ra RONG
 * (truong hop hiem, chi ton THEM 1 lan goi khi that su can). Dung CHUNG cho ca nhanh project_id lan
 * street_code o ca 2 trang (A1 + A4) - khong lap lai logic try/fallback o tung noi goi.
 */
function pplus_get_office_list_with_bounds_fallback(array $base_args, ?array $bounds): ?array {
    $result = pplus_get_office_list($base_args);
    if (!empty($result['total_count']) || empty($bounds)) {
        return $result;
    }
    return pplus_get_office_list(array_merge($base_args, $bounds));
}

/**
 * Landing theo tinh (A3) - /cho-thue-van-phong/{province-alias}, vd "ha-noi", "ho-chi-minh".
 * Doi chieu tu reference.service.ts GetIndexProvinceProduct() + reference-main.component.ts.
 *
 * @return array|null Shape: { dataSelected: {title, subTitle, description, content, metaTitle,
 *   metaDescription, alias, isIndex}, dataList: [{title, alias, listOffice: [...]}] }
 */
function pplus_get_province_landing(string $province_alias): ?array {
    $lang = pplus_current_lang();
    $data = pplus_api_get("{$lang}/Reference/GetIndexProvinceProduct", ['alias' => $province_alias], 600);
    return $data['result'] ?? null;
}

/**
 * Chi tiet 1 van phong/toa nha - GET (khong phai POST nhu _GetOffices).
 * Doi chieu tu office.service.ts GetDetail() + OfficeController._GetDetail(int id, string lang,
 * string province, string district, string alias). Field "alias" phai la phan KHONG co "-{id}"
 * o cuoi - xem pplus_parse_office_alias_id() trong inc/office-detail-route.php.
 *
 * @return array|null Shape: { projectId, projectName, address, ratingLevel, photoList[{url,alt}],
 *   dataSelected{title,description,content,metaTitle,metaDescription}, serviceIncludedList[],
 *   fromLeaseFeeString, managerFeeString, fromTotalAmtString, releaseDateString, elevatorInfoString,
 *   urlHerf, alasProvince, alasDistrict, subType, lat, lng, videoUrl, ... }
 */
function pplus_get_office_detail(int $id, string $province, string $district, string $alias): ?array {
    $lang = pplus_current_lang();
    $data = pplus_api_get("{$lang}/Office/_GetDetail", [
        'id'       => $id,
        'province' => $province,
        'district' => $district,
        'alias'    => $alias,
    ], 600);
    return $data['result'] ?? null;
}

/**
 * Landing theo tinh/alias-loai-hinh (A4) - "thue-van-phong/{province}" (khong alias, dung
 * province lam alias) HOAC "cho-thue-van-phong/{province}/{alias}" (co alias rieng, vd
 * "cho-thue-van-phong/ha-noi/hoan-kiem"). Doi chieu tu reference.service.ts GetIndex() -
 * xem inc/reference-index-route.php: khi $alias rong, goi Reference/GetIndexProvince?alias=
 * (dung $province lam gia tri alias, dung logic Angular), nguoc lai goi Reference/GetIndex
 * ?province=&alias=. Khac voi A3 (pplus_get_province_landing): response O DAY KHONG co
 * listOffice trong dataList (chi la bai viet + form lien he, khong co luoi van phong).
 *
 * @return array|null Shape: { dataSelected: {id, title, subTitle, content, metaTitle,
 *   metaDescription, alias, aliasVi, aliasEn, isIndex, loai}, menuItem: {alias, ...}|null }
 */
function pplus_get_reference_index(string $province, string $alias, string $alias_lang = 'vi'): ?array {
    $lang = pplus_current_lang();
    if ($alias !== '') {
        // BUG doi ngon ngu tren trang quan/phuong (feedback thuc te): API .NET luu noi dung tieng
        // Anh duoi 1 alias KHAC HAN tieng Viet (vd vi "hoan-kiem" nhung en la "hoan-kiem-district",
        // xac nhan qua curl truc tiep - goi "en/Reference/GetIndex?alias=hoan-kiem" tra ve toan bo
        // dataSelected = null). URL VI-form ("cho-thue-van-phong/{province}/{alias}", $alias_lang
        // mac dinh 'vi') VAN con duoc chap nhan ke ca duoi tien to /en (tuong thich nguoc voi link
        // cu/URL da chia se) - khi do, lang=en phai TU DOI sang aliasEn that truoc khi goi API that,
        // bang 1 cuoc goi "do tham" phia vi (co cache transient nen khong ton them request that khi
        // lap lai).
        //
        // URL EN-form MOI ("office-for-rent/{province}/{alias}", $alias_lang='en' - xem rewrite
        // rule rieng trong inc/reference-index-route.php) da mang SAN alias dung ngon ngu EN (vd
        // "hoan-kiem-district") - KHONG can do tham nua, goi thang en/Reference/GetIndex voi alias
        // do la du (da xac nhan qua goi API that truc tiep: "en/Reference/GetIndex?alias=hoan-kiem-
        // district" tra ve dung dataSelected, khac han vi du "alias=hoan-kiem" o tren).
        $resolved_alias = $alias;
        // SUA 30/9 (kiem tra da ngon ngu theo yeu cau nguoi dung): dieu kien truoc day CHI xu ly
        // "$lang === 'en'" - "$lang === 'ja'" bi BO SOT hoan toan, khien URL VI-form duoi tien to
        // "/ja/" (vd "/ja/cho-thue-can-ho/ha-noi/quan-tay-ho", tuong thich nguoc voi link VI cu -
        // xem ghi chu tren) luon 404/trang trang (goi thang "ja/Reference/GetIndex?alias=quan-tay-ho"
        // that su tra ve dataSelected RONG - xac nhan qua curl truc tiep). "aliasJa" that (xac nhan
        // qua API) LUON BANG "aliasEn" (chua co ban dich rieng cho JA o CSDL goc), nen dung LAI
        // chinh "aliasEn" tu cuoc "do tham" - khong can goi rieng 1 truong "aliasJa" nao khac.
        if (($lang === 'en' || $lang === 'ja') && $alias_lang !== 'en') {
            $vi_probe   = pplus_api_get('vi/Reference/GetIndex', ['province' => $province, 'alias' => $alias], 600);
            $alias_en   = $vi_probe['result']['dataSelected']['aliasEn'] ?? '';
            if ($alias_en !== '') {
                $resolved_alias = $alias_en;
            }
        }
        $data = pplus_api_get("{$lang}/Reference/GetIndex", ['province' => $province, 'alias' => $resolved_alias], 600);
    } else {
        $data = pplus_api_get("{$lang}/Reference/GetIndexProvince", ['alias' => $province], 600);
    }
    $result = $data['result'] ?? null;

    // Chan biet thu TAN GOC (14/9, theo yeu cau nguoi dung): san pham biet thu da bo khoi menu tu
    // 13/9 nhung CHI o tang hien thi (parts/header.php khong render $header['apartmentVilla'] nua) -
    // du lieu THAT van con nguyen tren backend va van con URL cu (vd
    // /cho-thue-can-ho/ha-noi/biet-thu-ciputra/) van goi dung ham nay vi rewrite rule khong loc theo
    // loai. Xac nhan qua goi API that: dataSelected cua BAT KY alias biet-thu nao (con hang trong
    // apartmentVilla[] tra ve tu Home/HeaderInvokeAsync) LUON co "loai"="8" (khac han "1"=quan,
    // "9"=loai-can-ho) - chan tai day (tra ve null) de page-reference.php tu 404 (dung LAI y het
    // nhanh 404 co san cho alias khong ton tai, KHONG can sua rieng page-reference.php), ap dung cho
    // CA URL cu LAN bat ky URL nao khac co the con tro toi 1 alias biet-thu trong tuong lai - khong
    // dua vao 1 danh sach alias biet-thu can liet ke thu cong (se sot khi co alias moi).
    if (is_array($result) && (string) ($result['dataSelected']['loai'] ?? '') === '8') {
        return null;
    }

    return $result;
}

/**
 * Danh sach duong theo tinh (A5) - /street/{province-alias}. Doi chieu tu reference.service.ts
 * GetIndexStreet() + list-street.component.ts dong 39-40: param GET la "province" (KHONG phai
 * "alias" nhu cac ham Reference/* khac o tren).
 *
 * @return array|null Shape: { menuData: {subTitle, alias, ...}, listRank: ["a","b",...],
 *   list: [{ rank: "a", listStreet: [{title, alias, loai, ...}] }] }
 */
function pplus_get_province_streets(string $province_alias): ?array {
    $lang = pplus_current_lang();
    $data = pplus_api_get("{$lang}/Reference/GetIndexProvinceStreet", ['province' => $province_alias], 600);
    return $data['result'] ?? null;
}

/**
 * "Hà Nội"/"Hồ Chí Minh" (gia tri text VI dung lam filter Province khap theme nay) -> slug alias
 * dung cho cac endpoint Reference/* (GetIndexProvinceStreet/GetIndex...) - chi 2 tinh, khong can
 * bang doi day du (xem <select name="province"> front-page.php, cung chi 2 option nay).
 */
function pplus_province_text_to_alias(string $province_text): string {
    $normalized = mb_strtolower(remove_accents($province_text));
    return (strpos($normalized, 'ho chi minh') !== false) ? 'ho-chi-minh' : 'ha-noi';
}

/**
 * Ten duong (text hien thi, vd "Kim Mã", lay tu goi y autocomplete "Địa điểm" - xem
 * inc/location-search.php) -> "streetCode" (vd "D00129") dung duoc cho Office/_GetOffices
 * (StreetCode, xem pplus_get_office_list()). API .NET KHONG co endpoint tra ve streetCode truc
 * tiep tu ten duong - phai di qua 2 buoc, CA HAI DEU CO CACHE TRANSIENT (600s, pplus_api_get()):
 * (1) Reference/GetIndexProvinceStreet - danh sach DAY DU duong cua 1 tinh, tim theo "title" khop
 *     $street_name (khong phan biet hoa/thuong + dau) de lay "alias" cua CHINH XAC duong do; (2)
 *     Reference/GetIndex?alias=<alias do> - tra ve filter.streetCode (field CHI co o day, khong co
 *     o (1)). Da xac nhan qua goi API that ca 2 buoc (vd "Kim Mã" -> alias "kim-ma" -> streetCode
 *     "D00129"). Chi chay 1 lan/luot submit (khong phai moi phim go), nen 2 lan goi them nay khong
 *     anh huong toc do go-tim-goi-y (endpoint rieng, xem inc/location-search.php).
 */
function pplus_resolve_street_code(string $province_text, string $street_name): ?string {
    $street_name = trim($street_name);
    if ($street_name === '') {
        return null;
    }

    $province_alias = pplus_province_text_to_alias($province_text);
    $streets = pplus_get_province_streets($province_alias);
    $needle  = pplus_location_search_normalize($street_name);

    $matched_alias = null;
    foreach ((array) ($streets['list'] ?? []) as $rank_group) {
        foreach ((array) ($rank_group['listStreet'] ?? []) as $street) {
            $title = (string) ($street['title'] ?? '');
            if (pplus_location_search_normalize($title) === $needle) {
                $matched_alias = (string) ($street['alias'] ?? '');
                break 2;
            }
        }
    }
    if ($matched_alias === null || $matched_alias === '') {
        return null;
    }

    $index = pplus_get_reference_index($province_alias, $matched_alias);
    $code  = $index['filter']['streetCode'] ?? '';
    return $code !== '' ? (string) $code : null;
}

/**
 * Sinh URL trang danh sach van phong voi query param duoc ghi de (dung cho phan trang/loc,
 * xem page-office-list.php).
 */
function pplus_office_list_url(array $override = []): string {
    $params = array_merge($_GET, $override);
    $params = array_filter($params, fn($v) => $v !== '' && $v !== null);
    return add_query_arg($params, home_url(pplus_lang_prefix() . '/' . pplus_office_list_base_slug()));
}

/**
 * Chia mang thanh 4 cot theo kieu round-robin, giong ham ControlHome() trong home.component.ts
 * (danhSachNhanVien chia deu vao 4 cot de hien thi luoi 4 cot doi ngu nhan vien).
 */
function pplus_chunk_round_robin(array $items, int $columns): array {
    $result = array_fill(0, $columns, []);
    foreach ($items as $index => $item) {
        $result[$index % $columns][] = $item;
    }
    return $result;
}

/**
 * Tach id + alias tu doan cuoi URL dang "{alias}-{id}" - dung chung cho blog detail (B3) va
 * office detail (A2, xem pplus_parse_office_alias_id trong office-detail-route.php). Logic
 * giong het detail.component.ts / blog-detail.component.ts: id la phan sau dau "-" cuoi cung.
 *
 * @return array{id:int, alias:string}
 */
function pplus_parse_alias_id(string $alias_id): array {
    $parts = explode('-', $alias_id);
    $id    = (int) end($parts);
    $alias = preg_replace('/-' . preg_quote((string) $id, '/') . '$/', '', $alias_id);
    return ['id' => $id, 'alias' => $alias];
}

/**
 * (B2) Danh sach tin tuc /blogs - doi chieu blog.service.ts GetIndex(): GET voi query
 * ?catalogid=&page= (khong phai POST JSON nhu office). page_count co dinh 16 giong
 * dataPaging.maxSizePage ben Angular (blog-list.component.ts).
 *
 * @return array|null Shape: { listBlog: [{id,alias,title,subTitle,img}], listCatalog: [{id,nameCatalog}], totalItem }
 */
function pplus_get_blog_list(array $args = []): ?array {
    $lang = pplus_current_lang();
    $page = max(1, (int) ($args['page'] ?? 1));

    $data = pplus_api_get("{$lang}/Blogs/GetIndex", [
        'catalogid' => $args['catalogid'] ?? '',
        'page'      => $page,
    ], 300);

    return $data['result'] ?? null;
}

/**
 * (B3) Chi tiet 1 bai tin - doi chieu blog.service.ts GetDetail(): GET ?id=&alias=.
 * $alias PHAI la phan KHONG co "-{id}" o cuoi - dung pplus_parse_alias_id() truoc khi goi.
 *
 * QUAN TRONG (sua 17/9, xac nhan qua goi API THAT bang curl - KHONG doan tu .ts vi .ts chi cho
 * thay REQUEST, khong cho thay ro shape RESPONSE): ham nay tra ve NGUYEN VEN body cua
 * pplus_api_get() (KHONG tu unwrap them), shape THAT la:
 * {result: {catalog: {id, nameCatalog, ...}, data: {id, title, subTitle, content, alias, aliasVi,
 * aliasEn, aliasJa, dateUpdate, metaTitle, metaDescription, isIndex, dataModel? (tac gia, LONG
 * BEN TRONG data, KHONG PHAI sibling)}, listLQ: [...cung shape voi data, bai lien quan]},
 * isRedirect?, redirectUrl?, redirectType?}. Truoc day docblock (va single-news.php) gia dinh SAI
 * la "dataSelected"/"dataList"/"dataModel" (dataSelected khong bao gio ton tai trong response
 * that -> trang chi tiet LUON hien "loi API" du du lieu that co that su).
 *
 * @return array|null Shape: { result: { catalog, data, listLQ }, isRedirect?, redirectUrl?, redirectType? }
 */
function pplus_get_blog_detail(int $id, string $alias): ?array {
    $lang = pplus_current_lang();
    return pplus_api_get("{$lang}/Blogs/GetDetail", [
        'id'    => $id,
        'alias' => $alias,
    ], 300);
}

/**
 * (B4) Trang tac gia /author/{alias} - doi chieu blog.service.ts GetDetailTacGia(): GET ?alias=.
 *
 * @return array|null Shape: { dataModel: {title,img,description,content,metaTitle,metaDescription,
 *   textExtension1..6 (social links/phone/email)}, dataList: [related blogs], alias, aliasVi, aliasEn }
 */
function pplus_get_blog_author(string $alias): ?array {
    $lang = pplus_current_lang();
    $data = pplus_api_get("{$lang}/Blogs/GetDetailTacGia", ['alias' => $alias], 600);
    return $data['result'] ?? null;
}

/**
 * (B5) Trang "Ve chung toi" - doi chieu abount.service.ts GetIndex(): GET, khong query param.
 * Phan lon noi dung trang la text tinh (xem about-index.component.html) - endpoint nay tra ve 3
 * phan dong: doi ngu nhan vien (listUser), hoat dong cong ty (listDataAboutUs), va carousel "Kham
 * pha Property Plus" (model.listKhamPhaPP - LONG MOT CAP duoi khoa "model", KHAC 2 mang tren nam
 * thang o cap ngoai cung - xac nhan qua doc about-index.component.ts dong 263:
 * "Model.model.listKhamPhaPP").
 *
 * @return array|null Shape: { listUser: [{title,content,img,imgSize}], listDataAboutUs: [{title,content,img}], model: { listKhamPhaPP: [{title,subTitle,img}] } }
 */
function pplus_get_about_index(): ?array {
    $lang = pplus_current_lang();
    $data = pplus_api_get("{$lang}/About/GetIndex", [], 900);
    return $data['result'] ?? null;
}

/**
 * Trang con /ve-chung-toi/{alias}-{id} (vd "chinh-sach-bao-mat-thong-tin-12", lay tu
 * $footer['myHistories'] trong parts/footer.php, cung API FooterInvokeAsync). Doi chieu
 * abount.service.ts GetNewsAbout(): GET About/TinTucHoatDong?id=...&alias=... - CA 2 tham so deu
 * bat buoc (xac nhan qua doc truc tiep about-new.component.ts dong 26-30: URL segment duoc tach
 * "{id la phan sau dau '-' cuoi cung}" + "{alias la phan con lai, DA BO '-{id}'}" truoc khi goi,
 * y het co che A2 (office-detail)/B3 (blog-detail) - dung lai pplus_parse_alias_id() da co san,
 * KHONG tu viet lai logic tach rieng o day).
 *
 * QUAN TRONG (xac nhan bang goi API THAT 14/9, KHONG doan tu .ts vi .ts khong the hien ro shape
 * response): du lieu that nam LONG THEM 1 CAP duoi khoa "model" - {result:{model:{id,img,content,
 * idParent,title,description,lang,alias,websiteType,stt,metaTitle,metaDescription,isIndex},
 * listLQ:[...]}} - KHONG PHAI {result:{title,content,...}} phang nhu ban truoc day gia dinh (bug
 * thuc te: trang con luon hien trang trang vi doc nham $data['result']['title'] la null). "listLQ"
 * (co le "lien quan") KHONG dung toi - xac nhan qua about-new.component.html: cot phai (col-md-4)
 * de RONG, khong co sidebar bai lien quan nao.
 *
 * @return array|null Shape: { id, title, content (HTML), metaTitle, metaDescription, alias, ... }
 */
function pplus_get_about_subpage(int $id, string $alias): ?array {
    $lang = pplus_current_lang();
    $data = pplus_api_get("{$lang}/About/TinTucHoatDong", ['id' => $id, 'alias' => $alias], 900);
    return $data['result']['model'] ?? null;
}

/**
 * Sinh URL trang /blogs voi query param duoc ghi de (loc theo catalogid, phan trang) -
 * giong pplus_office_list_url() dung cho A1.
 */
function pplus_blog_list_url(array $override = []): string {
    // SUA 30/9 (kiem tra da ngon ngu - cung 1 bug voi pplus_blog_detail_url(), xem ghi chu day du o
    // do): bo sot tien to "/ja", lam link phan trang/loc theo danh muc tren trang Blog JA roi ve VI.
    $params = array_merge($_GET, $override);
    $params = array_filter($params, fn($v) => $v !== '' && $v !== null);
    return add_query_arg($params, home_url(pplus_lang_prefix() . '/blogs/'));
}

/**
 * Sinh URL chi tiet 1 bai tin dang /blog/{alias}-{id} (SO IT - sua 17/9, truoc day sai thanh
 * "/blogs/" so nhieu, xac nhan qua doc truc tiep app-routing.module.ts + moi routerLink that
 * trong blog-list.component.html/blog-detail.component.html/home.component.html DEU dung "blog"
 * so it cho trang chi tiet, "blogs" so nhieu CHI danh cho trang danh sach - 2 goc khac nhau,
 * KHONG phai 1 loi go nham).
 */
function pplus_blog_detail_url(string $alias, int $id): string {
    // SUA 30/9 (kiem tra da ngon ngu theo yeu cau nguoi dung): "=== 'en' ? '/en' : ''" bo sot hoan
    // toan tien to "/ja" - moi link bai blog tren 1 trang JA (carousel trang chu, danh sach blog,
    // bai lien quan trong trang chi tiet blog) truoc day roi thang ve ban VI khi bam vao. Dung
    // pplus_lang_prefix() (xu ly dung ca 3 ngon ngu) thay vi tu so sanh lai.
    return home_url(pplus_lang_prefix() . '/blog/' . $alias . '-' . $id);
}
