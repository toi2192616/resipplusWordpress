<?php
/**
 * Tao wp-content/themes/propertyplus-resi/data/building-directory.json - danh sach tat ca TOA NHA (du an) dang co can cho thue,
 * dung cho trang "Tat ca toa nha" (inc/building-directory.php). Chi DOC API cong khai, khong ghi gi len backend.
 *
 * Chay (tu thu muc goc repo):   php tools/gen-building-directory.php
 * Thoi gian: ~5-8 phut (Project/_GetProjects: 70 trang x 3 ngon ngu; Apartment/_GetApartments: ~180 trang, chi tieng Viet).
 * Chay lai khi co du an moi / de lam moi danh sach (vd moi thang hoac truoc moi lan phat hanh ban cap nhat).
 *
 * Quy tac loc: chi giu du an co it nhat 1 can ho dang dang ("vo" du an = 0 can + chua co bai viet -> tu noindex, khong dua vao danh sach;
 * xem inc/hidden-projects.php). API danh sach du an KHONG co so can ho (cot gia/phong khong phai tin hieu), nen dem tu danh sach can ho.
 */

const API_BASE = 'https://admin.propertyplus.com.vn';
const BBOX     = ['ne_lat' => 21.5, 'ne_lng' => 106.3, 'sw_lat' => 20.5, 'sw_lng' => 105.3]; // khung rong: lay du ~838 du an (khung ngam dinh chi tra 825)

function api_post(string $path, array $body, int $tries = 4): ?array {
    $ctx = stream_context_create(['http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/json\r\nAccept: application/json\r\n",
        'content' => json_encode($body),
        'timeout' => 60,
        'ignore_errors' => true,
    ]]);
    for ($i = 1; $i <= $tries; $i++) {
        $raw = @file_get_contents(API_BASE . $path, false, $ctx);
        $j   = $raw ? json_decode($raw, true) : null;
        if (is_array($j) && isset($j['result'])) {
            return $j['result'];
        }
        fwrite(STDERR, "  retry $i $path skip=" . ($body['skip'] ?? '?') . "\n");
        sleep(2 * $i);
    }
    return null;
}

$root = dirname(__DIR__);
$out  = $root . '/wp-content/themes/propertyplus-resi/data/building-directory.json';
if (!is_dir(dirname($out))) {
    mkdir(dirname($out), 0777, true);
}

/**
 * API tra ten quan KHONG nhat quan (vi: "Quận Tây Hồ" / "Nam Từ Liêm"; en: "Tây Hồ district" co dau Viet, "Bac Tu Liem District"; ja: "タイホ区"
 * trong khi ca site dung "タイホー区"). Chuan hoa theo cach web dang goi (tieu de trang quan): khoa = ten tieng Viet bo "Quận/Huyện",
 * nhan 3 ngon ngu theo thu tu [vi, en, ja]. Quan chua co trong bang -> giu nguyen nhan API.
 */
const DISTRICT_LABELS = [
    'Tây Hồ'      => ['Quận Tây Hồ', 'Tay Ho District', 'タイホー区'],
    'Ba Đình'     => ['Quận Ba Đình', 'Ba Dinh District', 'バーディン区'],
    'Hai Bà Trưng' => ['Quận Hai Bà Trưng', 'Hai Ba Trung District', 'ハイバーチュン区'],
    'Cầu Giấy'    => ['Quận Cầu Giấy', 'Cau Giay District', 'カウザイ区'],
    'Hoàn Kiếm'   => ['Quận Hoàn Kiếm', 'Hoan Kiem District', 'ホアンキエム区'],
    'Đống Đa'     => ['Quận Đống Đa', 'Dong Da District', 'ドンダー区'],
    'Nam Từ Liêm' => ['Quận Nam Từ Liêm', 'Nam Tu Liem District', 'ナムトゥリエム区'],
    'Bắc Từ Liêm' => ['Quận Bắc Từ Liêm', 'Bac Tu Liem District', 'バクトゥーリエム区'],
    'Thanh Xuân'  => ['Quận Thanh Xuân', 'Thanh Xuan District', 'タインスアン区'],
    'Hoàng Mai'   => ['Quận Hoàng Mai', 'Hoang Mai District', 'ホアンマイ区'],
    'Long Biên'   => ['Quận Long Biên', 'Long Bien District', 'ロンビエン区'],
    'Hà Đông'     => ['Quận Hà Đông', 'Ha Dong District', 'ハドン区'],
];

/** Doi nhan quan cua moi dong ve nhan chuan (khoa lay tu danh sach tieng Viet cung id). */
function normalize_districts(array $data): array {
    $keyById = [];
    foreach ((array) ($data['vi'] ?? []) as $r) {
        $k = trim((string) preg_replace('/^(Quận|Huyện)\s+/u', '', (string) ($r[2] ?? '')));
        if ($k !== '' && isset(DISTRICT_LABELS[$k])) {
            $keyById[(int) $r[0]] = $k;
        }
    }
    foreach (['vi' => 0, 'en' => 1, 'ja' => 2] as $lang => $idx) {
        foreach ((array) ($data[$lang] ?? []) as $i => $r) {
            if (isset($keyById[(int) $r[0]])) {
                $data[$lang][$i][2] = DISTRICT_LABELS[$keyById[(int) $r[0]]][$idx];
            }
        }
    }
    return $data;
}

// Chi chuan hoa lai file da co (khong goi API): php tools/gen-building-directory.php --normalize-only
if (in_array('--normalize-only', $argv ?? [], true)) {
    $data = normalize_districts(json_decode((string) file_get_contents($out), true) ?: []);
    file_put_contents($out, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    echo "NORMALIZED $out\n";
    exit(0);
}

// 1) Du an co can ho: tap projectId tu danh sach can ho (khong biet thu), chi tieng Viet (id khong phu thuoc ngon ngu).
echo "== apartments (vi)\n";
$withApts = [];
$skip     = 0;
$total    = null;
while (true) {
    $r = api_post('/vi/Apartment/_GetApartments', ['skip' => $skip, 'price_sort_type' => 2, 'apartmentTypeList' => '1,2']);
    if ($r === null) { fwrite(STDERR, "ABORT: apartments page $skip failed\n"); exit(1); }
    $items = $r['listApartments'] ?? [];
    $total = (int) ($r['total_count'] ?? 0);
    foreach ($items as $it) {
        if (!empty($it['projectId'])) {
            $withApts[(int) $it['projectId']] = true;
        }
    }
    $skip += 12;
    if ($skip % 240 === 0) { echo "  apartments $skip / $total, projects with apartments so far: " . count($withApts) . "\n"; }
    if (!$items || $skip >= $total) { break; }
}
echo "apartments scanned: $total, projects with >= 1 apartment: " . count($withApts) . "\n";

/** GET JSON tu API (thu lai vai lan); null neu loi. */
function api_get_json(string $path, int $tries = 4): ?array {
    $ctx = stream_context_create(['http' => ['method' => 'GET', 'header' => "Accept: application/json\r\n", 'timeout' => 90, 'ignore_errors' => true]]);
    for ($i = 1; $i <= $tries; $i++) {
        $raw = @file_get_contents(API_BASE . $path, false, $ctx);
        $j   = $raw ? json_decode($raw, true) : null;
        if (is_array($j) && isset($j['result'])) {
            return $j['result'];
        }
        fwrite(STDERR, "  retry $i $path\n");
        sleep(2 * $i);
    }
    return null;
}

/** So ky tu chu cua bai viet du an (content + description) - CUNG cach tinh voi page-project-detail.php (nguong 200). */
function article_chars(?array $result): int {
    $ds = $result['dataSelected'] ?? null;
    if (!is_array($ds)) {
        return 0;
    }
    $text = html_entity_decode(strip_tags(preg_replace('#<(script|style)\b.*?</\1>#is', ' ', (string) ($ds['content'] ?? '') . ' ' . (string) ($ds['description'] ?? ''))), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return mb_strlen(trim((string) preg_replace('/\s+/u', ' ', $text)));
}

// 2) Danh sach du an theo tung ngon ngu.
$data   = ['built' => date('Y-m-d'), 'with_apartments' => count($withApts)];
$allIds = []; // moi du an (ke ca khong co can) - de tinh du an "vo"
foreach (['vi', 'en', 'ja'] as $lang) {
    echo "== projects ($lang)\n";
    $list  = [];
    $seen  = 0;
    $skip  = 0;
    $total = null;
    while (true) {
        $r = api_post("/$lang/Project/_GetProjects", ['skip' => $skip] + BBOX);
        if ($r === null) { fwrite(STDERR, "ABORT: projects $lang page $skip failed\n"); exit(1); }
        $items = $r['listProjects'] ?? [];
        $total = (int) ($r['total_count'] ?? 0);
        foreach ($items as $it) {
            $seen++;
            $id   = (int) ($it['projectId'] ?? 0);
            $href = trim((string) ($it['urlHerf'] ?? ''), '/');
            $name = trim((string) ($it['projectName'] ?? ''));
            if ($id > 0) {
                $allIds[$id] = true;
            }
            if ($id <= 0 || $name === '' || $href === '' || strpos($href, '//') !== false || empty($withApts[$id])) {
                continue;
            }
            $list[] = [$id, $name, trim((string) ($it['districtName'] ?? '')), $href];
        }
        $skip += 12;
        if (!$items || $skip >= $total) { break; }
    }
    echo "  $lang: $seen projects seen, " . count($list) . " kept\n";
    $data[$lang] = $list;
}

// 3) Du an "vo" theo tung ngon ngu (0 can dang dang VA bai viet < 200 ky tu): trang tu noindex -> bo khoi sitemap san pham (inc/building-directory.php).
//    Khong co ban ghi bai viet o ban Viet -> khong the co ban EN/JA -> vo ca 3. Co ban ghi -> kiem rieng tung ngon ngu. Goi chi tiet loi -> KHONG coi la vo (giu trong sitemap).
//    Sitemap san pham co them vai du an ma API danh sach KHONG tra ve (vd du an ngoai Ha Noi) -> kiem ca cac id do.
$sm = @file_get_contents(API_BASE . '/sitemaps/sitemap_products.xml', false, stream_context_create(['http' => ['timeout' => 120]]));
if ($sm && preg_match_all('#<loc>\s*https?://[^/<]+/(?:vi|en|ja)/(?:cho-thue-can-ho|rental-apartment)/[^/<]+/[^/<]*/[^/<]*-(\d+)\s*</loc>#', $sm, $sm_ids)) {
    $before = count($allIds);
    foreach ($sm_ids[1] as $sid) { $allIds[(int) $sid] = true; }
    echo 'sitemap_products: ' . (count($allIds) - $before) . " extra project ids not in the list API\n";
} else {
    fwrite(STDERR, "WARNING: cannot read sitemap_products.xml - projects missing from the list API are not checked\n");
}
$candidates = array_values(array_diff(array_keys($allIds), array_keys($withApts)));
sort($candidates);
echo "== shell check: " . count($candidates) . " projects without apartments\n";
$shell = ['vi' => [], 'en' => [], 'ja' => []];
$done  = 0;
foreach ($candidates as $id) {
    $vi = api_get_json("/vi/Project/Detail?id=$id");
    if ($vi !== null) {
        if (!is_array($vi['dataSelected'] ?? null)) {
            foreach (['vi', 'en', 'ja'] as $l) { $shell[$l][] = $id; }
        } else {
            if (article_chars($vi) < 200) { $shell['vi'][] = $id; }
            foreach (['en', 'ja'] as $l) {
                $r = api_get_json("/$l/Project/Detail?id=$id");
                if ($r !== null && article_chars($r) < 200) { $shell[$l][] = $id; }
            }
        }
    }
    if (++$done % 25 === 0) { echo "  checked $done / " . count($candidates) . "\n"; }
}
echo "shell projects: vi " . count($shell['vi']) . ", en " . count($shell['en']) . ", ja " . count($shell['ja']) . "\n";
$data['shell'] = $shell;

$data = normalize_districts($data);
file_put_contents($out, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
echo "WROTE $out (" . filesize($out) . " bytes)\n";
