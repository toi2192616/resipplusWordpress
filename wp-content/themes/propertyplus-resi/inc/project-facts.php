<?php
/**
 * Khoi "Thong tin toa nha" cho trang DU AN (20/11/2026) - noi dung THAT tu API Project/Detail, 3 ngon ngu.
 *
 * Boi canh: ~89% trang du an cho phep index (mau 9/16 trong sitemap) chi co ten + danh sach can ho + ban do (800-1.800 ky tu chu); Google thu thap
 * roi KHONG lap chi muc ("Da thu thap - hien chua duoc lap chi muc" 665 -> 1.030 trong 17 ngay). Trong khi API da tra san (va DA DICH en/ja, chi nhan
 * loai dia diem la tieng Viet): dia chi, nam hoan thanh, loai phong, dien tich, bai do xe, dich vu da gom/chua gom, tien ich, dia diem lan can (co du an
 * 89 dia diem: sieu thi, benh vien, nha hang Nhat...). Template cu khong hien gi trong so do (cot trai #web-content de trong khi khong co bai SEO).
 *
 * Chi dung DU LIEU CO THAT; truong van ban tu do chi co tieng Viet (quy mo, loai hinh, dien du phong, nuoc nong) CHI hien o ban tieng Viet - khong dua
 * tieng Viet vao trang en/ja. Gia tri "Dang Update"/rong bi bo. Hien thi o parts: pplus_render_project_facts().
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Chon ban theo ngon ngu trang tu bo [vi, en, ja]. */
function pplus_pf_t(array $triple): string {
    $lang = pplus_current_lang();
    return (string) ($lang === 'en' ? $triple[1] : ($lang === 'ja' ? $triple[2] : $triple[0]));
}

/** Gia tri khong co thong tin ("Dang Update", "N/A", "-")? */
function pplus_pf_is_blank(string $v): bool {
    $v = trim($v);
    return $v === '' || (bool) preg_match('/^(đang\s*update|updating|n\/?a|-+|null)\.?$/iu', $v);
}

/** Loai dia diem lan can (id trong CSDL) -> [vi, en, ja]. Id la (13-18 chua gap) -> nhom "Khac". */
function pplus_pf_type_labels(): array {
    return [
        12 => ['Siêu thị & trung tâm thương mại', 'Supermarkets & malls', 'スーパー・ショッピングモール'],
        1  => ['Bệnh viện', 'Hospitals', '病院'],
        2  => ['Phòng khám', 'Clinics', 'クリニック'],
        7  => ['Nha khoa', 'Dental clinics', '歯科'],
        13 => ['Chợ', 'Markets', '市場'],
        8  => ['Quán cafe', 'Cafés', 'カフェ'],
        3  => ['Nhà hàng', 'Restaurants', 'レストラン'],
        4  => ['Nhà hàng & Bar', 'Restaurants & bars', 'レストラン・バー'],
        6  => ['Quán mỳ', 'Noodle shops', '麺料理店'],
        14 => ['Khách sạn', 'Hotels', 'ホテル'],
        15 => ['Phòng gym', 'Gyms', 'ジム'],
        9  => ['Tiệm cắt tóc', 'Hair salons', 'ヘアサロン'],
        11 => ['Tiệm giặt là', 'Laundry', 'クリーニング店'],
        19 => ['Massage & sauna', 'Massage & sauna', 'マッサージ・サウナ'],
        5  => ['Cửa hàng nhỏ', 'Shops', 'ショップ'],
    ];
}

/**
 * Dia diem lan can da nhom theo loai, theo thu tu uu tien (sieu thi, benh vien... truoc), toi da $per_type ten moi nhom va $max_total ten.
 * @return array<int,array{label:string,names:string[]}>
 */
function pplus_project_nearby_groups(array $pd, int $per_type = 6, int $max_total = 20): array {
    $labels = pplus_pf_type_labels();
    $by     = [];
    foreach ((array) ($pd['specialLocationList'] ?? []) as $loc) {
        if (!is_array($loc) || (array_key_exists('isActive', $loc) && !$loc['isActive'])) {
            continue;
        }
        $name = rtrim(trim((string) ($loc['name'] ?? '')), " .,;:"); // du lieu co ten kieu "TYGY." -> bo dau cham cuoi
        $type = (int) ($loc['type'] ?? 0);
        if ($name === '' || mb_strlen($name) > 70) {
            continue;
        }
        $key = isset($labels[$type]) ? $type : 0;
        if (!in_array($name, $by[$key] ?? [], true)) {
            $by[$key][] = $name;
        }
    }
    $out   = [];
    $total = 0;
    foreach (array_merge(array_keys($labels), [0]) as $type) {
        if (empty($by[$type]) || $total >= $max_total) {
            continue;
        }
        $names   = array_slice($by[$type], 0, min($per_type, $max_total - $total));
        $total  += count($names);
        $out[]   = ['label' => $type === 0 ? pplus_pf_t(['Khác', 'Other', 'その他']) : pplus_pf_t($labels[$type]), 'names' => $names];
    }
    return $out;
}

/** Cac dong "thong tin nhanh" (nhan => gia tri); chi cac truong co du lieu that, ban en/ja bo truong van ban tu do tieng Viet. */
function pplus_project_fact_rows(array $pd): array {
    $lang = pplus_current_lang();
    $rows = [];
    $addr = trim((string) ($pd['address'] ?? ''));
    $pid  = (int) ($pd['projectId'] ?? 0);
    if ($lang !== 'vi') {
        if ($pid > 0) {
            $addr = trim((string) (pplus_project_addresses([$pid])[$pid] ?? $addr));
        }
        if ($addr !== '' && preg_match('/[^\x00-\x7F]/u', $addr) && !($lang === 'ja' && pplus_seo_has_cjk($addr))) {
            $addr = pplus_romanize_vi_address($addr);
        }
    }
    $addr = pplus_seo_trim_value($addr);
    if ($addr !== '') {
        $rows[pplus_pf_t(['Địa chỉ', 'Address', '所在地'])] = $addr;
    }
    $dist = pplus_seo_district_label($pd);
    $prov = pplus_seo_province_label($pd);
    $area = $lang === 'vi'
        ? implode(', ', array_filter([trim((string) ($pd['wardName'] ?? '')), $dist, $prov]))
        : trim($dist . ', ' . $prov, ', ');
    if ($area !== '') {
        $rows[pplus_pf_t(['Khu vực', 'Area', 'エリア'])] = $area;
    }
    if (preg_match('/^(\d{4})-/', (string) ($pd['releaseDate'] ?? ''), $m) && (int) $m[1] >= 1990) {
        $rows[pplus_pf_t(['Năm hoàn thành', 'Completed', '竣工年'])] = $lang === 'ja' ? $m[1] . '年' : $m[1];
    }
    if ($lang === 'vi') {
        foreach (['ownership' => 'Loại hình', 'projectSize' => 'Quy mô'] as $k => $label) {
            $v = trim((string) ($pd[$k] ?? ''));
            if (!pplus_pf_is_blank($v) && mb_strlen($v) <= 220) {
                $rows[$label] = $v;
            }
        }
    }
    $areas = pplus_seo_trim_value((string) ($pd['areas'] ?? '')); // 28/11: bo dau cau thua ("180 m2." -> cau gioi thieu + FAQ khong bi "..")
    if (!pplus_pf_is_blank($areas)) {
        $rows[pplus_pf_t(['Diện tích căn hộ', 'Apartment size', '専有面積'])] = $areas;
    }
    $beds = pplus_seo_beds_list((string) ($pd['beedRoom'] ?? ''));
    if (!pplus_pf_is_blank($beds)) {
        $rows[pplus_pf_t(['Loại phòng', 'Layouts', '間取り'])] = $beds;
    }
    if (!empty($pd['carParking'])) {
        $rows[pplus_pf_t(['Bãi đỗ xe', 'Parking', '駐車場'])] = pplus_pf_t(['Có', 'Available', 'あり']);
    }
    if ($lang === 'vi') {
        foreach (['powerBackup' => 'Điện dự phòng', 'hotWater' => 'Nước nóng'] as $k => $label) {
            $v = trim((string) ($pd[$k] ?? ''));
            if (!pplus_pf_is_blank($v) && mb_strlen($v) <= 160) {
                $rows[$label] = $v;
            }
        }
    }
    return $rows;
}

/** @return string[] ten dich vu/tien ich (API da dich en/ja); tien ich co gia tri khac "Yes/Co" thi ghi kem gia tri. */
function pplus_project_service_names(array $list, bool $with_value = false): array {
    $yes = ['yes', 'có', 'ある', 'available'];
    $out = [];
    foreach ($list as $it) {
        $n = trim((string) ($it['serviceName'] ?? ''));
        if ($n === '') {
            continue;
        }
        if ($with_value) {
            $v = trim((string) ($it['value'] ?? ''));
            if ($v !== '' && !in_array(mb_strtolower($v), $yes, true)) {
                $n .= ' (' . $v . ')';
            }
        }
        if (!in_array($n, $out, true)) {
            $out[] = $n;
        }
    }
    return $out;
}

/**
 * Cau hoi thuong gap tu du lieu that (hien thi + FAQPage JSON-LD). Chi cau co cau tra loi that; gia chi khi $facts['complete'].
 * @return array<int,array{q:string,a:string}>
 */
function pplus_project_faq(array $pd, array $facts, array $rows, array $groups): array {
    $name = trim((string) ($pd['projectName'] ?? ''));
    if ($name === '') {
        return [];
    }
    $faq   = [];
    $total = (int) ($facts['total'] ?? 0);
    $min   = (float) ($facts['min'] ?? 0);
    $max   = (float) ($facts['max'] ?? 0);
    if ($total > 0) {
        $price = '';
        if (!empty($facts['complete']) && $max > 0) {
            $price = ($min > 0 && $min < $max)
                ? pplus_pf_t([', giá từ $' . pplus_seo_money($min) . ' đến $' . pplus_seo_money($max) . '/tháng', ', from $' . pplus_seo_money($min) . ' to $' . pplus_seo_money($max) . '/month', '（月額$' . pplus_seo_money($min) . '〜$' . pplus_seo_money($max) . '）'])
                : pplus_pf_t([', giá $' . pplus_seo_money($max) . '/tháng', ', $' . pplus_seo_money($max) . '/month', '（月額$' . pplus_seo_money($max) . '）']);
        }
        $faq[] = [
            'q' => pplus_pf_t(['Giá thuê căn hộ tại ' . $name . ' là bao nhiêu?', 'How much does it cost to rent an apartment at ' . $name . '?', $name . 'の賃貸アパートの家賃はいくらですか？']),
            'a' => pplus_pf_t(['Hiện có ' . $total . ' căn đang cho thuê' . $price . '. Giá được cập nhật hằng tháng; liên hệ Property Plus để nhận báo giá mới nhất.', $total . ($total === 1 ? ' apartment is' : ' apartments are') . ' available for rent' . $price . '. Prices are updated monthly; contact Property Plus for the latest quote.', '現在' . $total . '件を掲載しております' . $price . '。価格は毎月更新しております。最新のお見積もりは、Property Plusまでお気軽にお問い合わせください。']),
        ];
    }
    $where = trim((string) ($rows[pplus_pf_t(['Địa chỉ', 'Address', '所在地'])] ?? ''));
    $area  = trim((string) ($rows[pplus_pf_t(['Khu vực', 'Area', 'エリア'])] ?? ''));
    // Khong co dia chi VA khong co quan (chi con ten tinh) -> bo cau "o dau?" (tra loi "Ha Noi" qua yeu, khong co gia tri).
    if ($where !== '' || pplus_seo_district_label($pd) !== '') {
        // Khu vuc chi them khi dia chi chua da noi (so sanh khong dau, bo khoang trang) - tranh "..., Dong Da District, Hanoi (Dong Da District, Hanoi)".
        $norm = static fn (string $s): string => (string) preg_replace('/[^a-z0-9\x{3040}-\x{9fff}]+/u', '', strtolower(remove_accents($s)));
        $loc  = $where;
        if ($area !== '' && ($where === '' || strpos($norm($where), $norm($area)) === false)) {
            $loc = $where === '' ? $area : $where . pplus_pf_t([' (', ' (', '（']) . $area . pplus_pf_t([')', ')', '）']);
        }
        $faq[] = [
            'q' => pplus_pf_t([$name . ' ở đâu?', 'Where is ' . $name . '?', $name . 'はどこにありますか？']),
            'a' => pplus_pf_t([$loc . '.', $loc . '.', $name . 'は、' . $loc . 'にございます。']),
        ];
    }
    $near = [];
    foreach ($groups as $g) {
        foreach ($g['names'] as $n) {
            $near[] = $n;
        }
    }
    if (count($near) >= 2) {
        $list  = implode(pplus_current_lang() === 'ja' ? '、' : ', ', array_slice($near, 0, 6));
        $faq[] = [
            'q' => pplus_pf_t(['Gần ' . $name . ' có những địa điểm nào?', 'What is near ' . $name . '?', $name . 'の近くには何がありますか？']),
            'a' => pplus_pf_t(['Gần ' . $name . ' có: ' . $list . '.', 'Near ' . $name . ': ' . $list . '.', $name . 'の周辺には、' . $list . 'などがございます。']),
        ];
    }
    return count($faq) >= 2 ? $faq : [];
}

/** Doan mo dau ngan, cau tao tu du lieu that (khong tu van ban tu do tieng Viet). */
function pplus_project_facts_intro(array $pd, array $rows): string {
    $name  = trim((string) ($pd['projectName'] ?? ''));
    $dist  = pplus_seo_district_label($pd);
    $prov  = pplus_seo_province_label($pd);
    $addr  = $rows[pplus_pf_t(['Địa chỉ', 'Address', '所在地'])] ?? '';
    $year  = $rows[pplus_pf_t(['Năm hoàn thành', 'Completed', '竣工年'])] ?? '';
    $areas = $rows[pplus_pf_t(['Diện tích căn hộ', 'Apartment size', '専有面積'])] ?? '';
    $beds  = $rows[pplus_pf_t(['Loại phòng', 'Layouts', '間取り'])] ?? '';
    $lang  = pplus_current_lang();
    if ($lang === 'en') {
        $s = $name . ' is an apartment building for rent in ' . trim($dist . ', ' . $prov, ', ') . ($addr !== '' ? ' (' . $addr . ')' : '') . '.';
        $s .= $year !== '' ? ' Completed in ' . $year . '.' : '';
        $s .= $areas !== '' ? ' Apartments range ' . $areas . ($beds !== '' ? '; layouts: ' . $beds : '') . '.' : '';
        return $s;
    }
    if ($lang === 'ja') {
        $s = $name . 'は' . trim($prov . ' ' . $dist) . 'にある賃貸アパートです。';
        $s .= $year !== '' ? '竣工は' . $year . 'です。' : '';
        $s .= $areas !== '' ? '専有面積は' . $areas . ($beds !== '' ? '、間取りは' . $beds : '') . 'です。' : '';
        return $s;
    }
    $s = $name . ' là tòa nhà căn hộ cho thuê tại ' . trim($dist . ', ' . $prov, ', ') . ($addr !== '' ? ' (' . $addr . ')' : '') . '.';
    $s .= $year !== '' ? ' Hoàn thành năm ' . $year . '.' : '';
    $s .= $areas !== '' ? ' Căn hộ có diện tích ' . $areas . ($beds !== '' ? ', loại phòng: ' . $beds : '') . '.' : '';
    return $s;
}

/** In khoi "Thong tin toa nha" (khong in gi neu khong co du lieu) + FAQPage JSON-LD khi co cau hoi. */
function pplus_render_project_facts(array $pd, array $facts = []): void {
    if (empty($pd['projectName'])) {
        return;
    }
    $rows     = pplus_project_fact_rows($pd);
    $included = pplus_project_service_names((array) ($pd['serviceIncludedList'] ?? []));
    $excluded = pplus_project_service_names((array) ($pd['serviceNotIncludedList'] ?? []));
    $amenity  = pplus_project_service_names((array) ($pd['utilityList'] ?? []), true);
    $groups   = pplus_project_nearby_groups($pd);
    if (count($rows) < 3 && !$included && !$excluded && !$amenity && !$groups) {
        return; // gan nhu khong co gi de noi -> khong in khoi rong
    }
    $name  = (string) $pd['projectName'];
    $faq   = pplus_project_faq($pd, $facts, $rows, $groups);
    // 20/11/2026: đoạn giới thiệu do Admin nhập (seoIntro, nếu Backend có) thay đoạn tự sinh.
    $intro = pplus_seo_admin_text([$pd], ['seoIntro'], 40) ?: pplus_project_facts_intro($pd, $rows);
    $ja    = pplus_current_lang() === 'ja';
    $sep   = $ja ? '、' : ', ';
    ?>
    <section class="pplus-project-facts" id="project-facts">
        <h2><?php echo esc_html(sprintf(pplus_pf_t(['Thông tin về %s', 'About %s', '%sについて']), $name)); ?></h2>
        <?php if ($intro !== '') : ?><p class="pplus-pf-intro"><?php echo esc_html($intro); ?></p><?php endif; ?>
        <?php if ($rows) : ?>
            <dl class="pplus-pf-dl">
                <?php foreach ($rows as $label => $value) : ?>
                    <dt><?php echo esc_html($label); ?></dt><dd><?php echo esc_html($value); ?></dd>
                <?php endforeach; ?>
            </dl>
        <?php endif; ?>
        <?php foreach ([
            [pplus_pf_t(['Dịch vụ đã bao gồm', 'Services included', '含まれるサービス']), $included],
            [pplus_pf_t(['Dịch vụ chưa bao gồm', 'Services not included', '含まれないサービス']), $excluded],
            [pplus_pf_t(['Tiện ích', 'Amenities', '設備・施設']), $amenity],
        ] as [$title, $items]) : if (!$items) { continue; } ?>
            <h3><?php echo esc_html($title); ?></h3>
            <ul class="pplus-pf-list">
                <?php foreach ($items as $it) : ?><li><?php echo esc_html($it); ?></li><?php endforeach; ?>
            </ul>
        <?php endforeach; ?>
        <?php if ($groups) : ?>
            <h3><?php echo esc_html(pplus_pf_t(['Địa điểm lân cận', 'Nearby places', '周辺施設'])); ?></h3>
            <ul class="pplus-pf-near">
                <?php foreach ($groups as $g) : ?>
                    <li><strong><?php echo esc_html($g['label']); ?>:</strong> <?php echo esc_html(implode($sep, $g['names'])); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <?php if ($faq) : ?>
            <h3><?php echo esc_html(pplus_pf_t(['Câu hỏi thường gặp', 'Frequently asked questions', 'よくある質問'])); ?></h3>
            <div class="pplus-pf-faq">
                <?php foreach ($faq as $qa) : ?>
                    <h4><?php echo esc_html($qa['q']); ?></h4>
                    <p><?php echo esc_html($qa['a']); ?></p>
                <?php endforeach; ?>
            </div>
            <script type="application/ld+json"><?php
                echo wp_json_encode([
                    '@context'   => 'https://schema.org',
                    '@type'      => 'FAQPage',
                    'mainEntity' => array_map(static fn ($qa) => ['@type' => 'Question', 'name' => $qa['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $qa['a']]], $faq),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
            ?></script>
        <?php endif; ?>
    </section>
    <?php
}
