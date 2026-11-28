<?php
/**
 * Title + meta description TU DONG cho trang DU AN va trang CAN HO khi Admin chua nhap metaTitle/metaDescription (20/11/2026).
 *
 * Boi canh (Search Console 20/11): Google thu thap ngay 18/11 nhieu trang du an/can ho cua Resi nhung CHUA lap chi muc. Doi chieu voi trang co luot
 * nhap (Lotte The Residence, The Authentic): trang bi loai co title chi la TEN du an ("Le Capitole", "MOVENPICK LIVING WEST HANOI"), mo ta 11-27 ky tu
 * (route chi in lai ten), con trang can ho co title lap ten toa ("LeHome Apartment - Toa LeHome Apartment - Tang 4"). Day la loi nam trong theme:
 * fallback cu lay thang projectName. Moi trang can title/mo ta MIEU TA DUNG NOI DUNG va KHAC NHAU (khu vuc, loai phong, dien tich, so can, khoang gia) -
 * toan bo tu du lieu that cua API, khong them cam ket moi (chi dung cac cau da co tren site: "tu van mien phi", "bao gia nhanh").
 *
 * Quy tac: metaTitle/metaDescription do Admin nhap (khong rong) LUON duoc ton trong; chi khi trong moi dung ham o day. Khong goi API moi cho title
 * (chi mo ta du an cua en/ja goi 1 lan pplus_project_addresses - da co cache, trang cung dung de ve dia chi the can ho).
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Chu Nhat (hiragana/katakana/kanji)? */
function pplus_seo_has_cjk(string $s): bool {
    return (bool) preg_match('/[぀-ヿ一-鿿]/u', $s);
}

/** Ten quan/huyen cho title theo ngon ngu: vi "Quận Đống Đa", en "Dong Da District", ja "ドンダー区" (CSDL da dich san sang tieng Nhat). */
function pplus_seo_district_label(array $row): string {
    $lang = pplus_current_lang();
    $name = trim((string) ($row['districtName'] ?? ''));
    if ($lang === 'vi') {
        return $name !== '' ? $name : trim((string) ($row['districtNameEn'] ?? ''));
    }
    if ($lang === 'ja' && pplus_seo_has_cjk($name)) {
        return $name;
    }
    // en (va ja khi CSDL chua dich): tieng Anh khong dau, vd "Dong Da District".
    $en = trim((string) ($row['districtNameEn'] ?? $name));
    if ($en === '') {
        return '';
    }
    $en = trim((string) preg_replace('/\s+district$/i', '', remove_accents($en)));
    return $en !== '' ? $en . ' District' : '';
}

/** Ten tinh/thanh pho theo ngon ngu: "Hà Nội" / "Hanoi" / "ハノイ". */
function pplus_seo_province_label(array $row): string {
    $lang = pplus_current_lang();
    $p    = trim((string) ($row['provinceName'] ?? ''));
    if ($lang === 'vi') {
        return $p !== '' ? $p : 'Hà Nội';
    }
    if ($lang === 'ja') {
        return pplus_seo_has_cjk($p) ? $p : 'ハノイ';
    }
    return ($p !== '' && !preg_match('/[^\x00-\x7F]/', $p)) ? $p : 'Hanoi';
}

/** "2 Phòng Ngủ" / "2-Bedroom" / "2ベッドルーム"; 0 = Studio. */
function pplus_seo_bed_label(int $n): string {
    $lang = pplus_current_lang();
    if ($n <= 0) {
        return $lang === 'ja' ? 'スタジオ' : 'Studio';
    }
    if ($lang === 'en') {
        return $n . '-Bedroom';
    }
    if ($lang === 'ja') {
        return $n . 'ベッドルーム';
    }
    return $n . ' Phòng Ngủ';
}

/**
 * 28/11/2026: du lieu du an hay co dau cau thua o cuoi ("64 m2 ~ 180 m2.", "04 phòng ngủ.", ban JA ket thuc bang "。") -> ghep vao cau thanh
 * "180 m2。です", "m2..", "phòng ngủ., diện tích"; token cuoi "04 phòng ngủ." con lam mat ca thong tin phong ngu o en/ja. Cat dau cau cuoi chuoi.
 */
function pplus_seo_trim_value(string $v): string {
    return (string) preg_replace('/[\s\x{00A0}\x{3000}.。,，、;；:：]+$/u', '', trim($v));
}

/**
 * Chuoi loai phong cua DU AN ("Studio, 1 phòng ngủ" - CSDL chi co tieng Viet) -> ngon ngu trang. Gap token la thi bo ca chuoi
 * ('' ) de khong dua tieng Viet vao mo ta en/ja.
 */
function pplus_seo_beds_list(string $raw): string {
    $raw  = pplus_seo_trim_value($raw);
    $lang = pplus_current_lang();
    if ($raw === '' || $lang === 'vi') {
        return $raw;
    }
    $out = [];
    foreach ((array) preg_split('/\s*,\s*/u', $raw) as $tok) {
        if ($tok === '') {
            continue;
        }
        if (preg_match('/^studio$/iu', $tok)) {
            $out[] = $lang === 'ja' ? 'スタジオ' : 'Studio';
        } elseif (preg_match('/^(\d+)\s*(?:phòng ngủ|pn)$/iu', $tok, $m)) {
            $n     = (int) $m[1];
            $out[] = $lang === 'ja' ? $n . 'ベッドルーム' : $n . ($n === 1 ? ' bedroom' : ' bedrooms');
        } else {
            return '';
        }
    }
    return implode($lang === 'ja' ? '、' : ', ', $out);
}

/** Gia: vi "1.200", en/ja "1,200". */
function pplus_seo_money(float $v): string {
    return pplus_current_lang() === 'vi' ? number_format($v, 0, ',', '.') : number_format($v, 0, '.', ',');
}

/**
 * LỚP "ADMIN ĐIỀN THÌ DÙNG" (20/11/2026): giá trị SEO do biên tập viên nhập trong Admin cho TỪNG dự án/căn hộ (tên trường đề xuất: seoTitle, seoDescription,
 * seoIntro, seoNoIndex - xem docs/DEV_YEU_CAU_TRUONG_SEO_ADMIN.md). Backend CHƯA có các trường này nên hàm trả '' và theme dùng nội dung tự động như trước;
 * khi Dev thêm trường (đúng tên) thì tự có hiệu lực, không cần sửa theme. Lấy chuỗi KHÔNG RỖNG đầu tiên trong các nguồn, theo thứ tự khóa.
 *
 * @param array[]  $sources  vd [$projectDetail, $dataSelected]
 * @param string[] $keys     vd ['seoTitle', 'metaTitle']
 * @param int      $min      độ dài tối thiểu (ký tự chữ) để coi là "đã điền thật"
 */
function pplus_seo_admin_text(array $sources, array $keys, int $min = 1): string {
    foreach ($keys as $k) {
        foreach ($sources as $src) {
            if (!is_array($src) || !isset($src[$k]) || !is_scalar($src[$k])) {
                continue;
            }
            $v = trim((string) preg_replace('/\s+/u', ' ', wp_strip_all_tags((string) $src[$k])));
            if ($v !== '' && mb_strlen($v) >= $min) {
                return $v;
            }
        }
    }
    return '';
}

/**
 * Tiêu đề do biên tập viên nhập cho TRANG DỰ ÁN: ô SEO riêng (seoTitle) -> Meta Title bài viết dự án -> "Tiêu đề" bài viết dự án.
 * 21/11/2026: khi nhân viên MỞ liên kết sửa của một dự án chưa có bài viết (Admin: Bài viết Dự án > nút sửa = DataList/EditOrCreateVP?id={projectId}&type=4),
 * Admin tự TẠO bản ghi trống với "Tiêu đề" = tên dự án (vd #1514 Le Capitole) -> "Tiêu đề" chỉ lặp lại tên dự án KHÔNG được coi là đã nhập
 * (nếu không, tiêu đề tự sinh bị mất và trang quay về chỉ có tên dự án).
 */
function pplus_seo_project_admin_title(array $project_detail, array $data_selected): string {
    $t = pplus_seo_admin_text([$project_detail, $data_selected], ['seoTitle', 'metaTitle']);
    if ($t !== '') {
        return $t;
    }
    $t    = pplus_seo_admin_text([$data_selected], ['title']);
    $name = trim((string) ($project_detail['projectName'] ?? ''));
    return ($t !== '' && mb_strtolower($t) !== mb_strtolower($name)) ? $t : '';
}

/** Admin tích "không index" (seoNoIndex) cho dự án/căn hộ này? */
function pplus_seo_admin_noindex(array $sources): bool {
    foreach ($sources as $src) {
        if (is_array($src) && !empty($src['seoNoIndex']) && $src['seoNoIndex'] !== 'false' && $src['seoNoIndex'] !== '0') {
            return true;
        }
    }
    return false;
}

/** Chon chuoi dau tien <= $max ky tu trong danh sach (tu day du -> ngan); khong co cai nao vua thi lay cai ngan nhat. */
function pplus_seo_pick_title(array $candidates, int $max = 70): string {
    $candidates = array_values(array_filter(array_map('trim', $candidates), 'strlen'));
    foreach ($candidates as $c) {
        if (mb_strlen($c) <= $max) {
            return $c;
        }
    }
    return $candidates ? end($candidates) : '';
}

/**
 * Ghep cac cau vao mo ta <= $max ky tu: cau nao KHONG vua thi bo han (khong cat giua cau), van thu cau ngan hon phia sau (vd cau keu goi hanh dong).
 * Cau dau qua dai thi cat theo tu bang pplus_seo_trim().
 */
function pplus_seo_fit_sentences(array $sentences, int $max, string $glue = ' '): string {
    $out = '';
    foreach (array_values(array_filter(array_map('trim', $sentences), 'strlen')) as $i => $s) {
        $try = $out === '' ? $s : $out . $glue . $s;
        if (mb_strlen($try) <= $max) {
            $out = $try;
        } elseif ($i === 0) {
            $out = pplus_seo_trim($s, $max);
        }
    }
    return $out;
}

/**
 * Title trang DU AN khi Admin chua nhap: "Căn Hộ Cho Thuê Tại Le Capitole - Quận Đống Đa, Hà Nội" (cung kieu title Admin da dung cho Lotte/Authentic),
 * en "Le Capitole Apartments for Rent - Dong Da District, Hanoi", ja "Le Capitole 賃貸アパート - ハノイ ドンダー区". Them " | Property Plus" khi con cho.
 */
function pplus_seo_project_title(array $pd): string {
    $name = trim((string) ($pd['projectName'] ?? ''));
    if ($name === '') {
        return '';
    }
    $lang = pplus_current_lang();
    $dist = pplus_seo_district_label($pd);
    $prov = pplus_seo_province_label($pd);
    $brand = ' | Property Plus';
    if ($lang === 'en') {
        $lead = $name . ' Apartments for Rent';
        $locs = [trim($dist . ', ' . $prov, ', '), $dist];
    } elseif ($lang === 'ja') {
        $lead = $name . ' 賃貸アパート';
        $locs = [trim($prov . ' ' . $dist), $prov];
    } else {
        $lead = 'Căn Hộ Cho Thuê Tại ' . $name;
        $locs = [trim($dist . ', ' . $prov, ', '), $dist];
    }
    $c = [];
    foreach ($locs as $loc) {
        if ($loc !== '') {
            $c[] = $lead . ' - ' . $loc . $brand;
            $c[] = $lead . ' - ' . $loc;
        }
    }
    $c[] = $lead . $brand;
    $c[] = $lead;
    return pplus_seo_pick_title($c, 70);
}

/**
 * Mo ta trang DU AN khi Admin chua nhap. $facts (tu page-project-detail.php, chi khi KHONG loc/phan trang): ['total' => int, 'min' => float, 'max' => float,
 * 'complete' => bool]; 'complete' = da lay du ca danh sach nen min/max la gia that (du an > 1 trang chi noi so luong, khong noi khoang gia).
 */
function pplus_seo_project_description(array $pd, array $facts = []): string {
    $name = trim((string) ($pd['projectName'] ?? ''));
    if ($name === '') {
        return '';
    }
    $lang  = pplus_current_lang();
    $pid   = (int) ($pd['projectId'] ?? 0);
    $addr  = trim((string) ($pd['address'] ?? ''));
    if ($lang !== 'vi') {
        if ($pid > 0) {
            $addr = trim((string) (pplus_project_addresses([$pid])[$pid] ?? $addr));
        }
        // Con dau tieng Viet (CSDL chua dich dia chi) -> khong dau; ja: giu nguyen neu da la chu Nhat.
        if ($addr !== '' && preg_match('/[^\x00-\x7F]/u', $addr) && !($lang === 'ja' && pplus_seo_has_cjk($addr))) {
            $addr = pplus_romanize_vi_address($addr);
        }
    }
    $addr  = pplus_seo_trim_value($addr);
    $beds  = pplus_seo_beds_list((string) ($pd['beedRoom'] ?? ''));
    $areas = pplus_seo_trim_value((string) ($pd['areas'] ?? ''));
    $total = (int) ($facts['total'] ?? 0);
    $min   = (float) ($facts['min'] ?? 0);
    $max   = (float) ($facts['max'] ?? 0);
    $range = '';
    if ($total > 0 && !empty($facts['complete']) && $max > 0) {
        $range = ($min > 0 && $min < $max) ? [pplus_seo_money($min), pplus_seo_money($max)] : [pplus_seo_money($max)];
    }

    if ($lang === 'en') {
        $s   = [];
        $s[] = 'Apartments for rent at ' . $name . ($addr !== '' ? ', ' . $addr : '') . '.';
        if ($total > 0) {
            $s[] = $total . ($total === 1 ? ' apartment' : ' apartments') . ' available' . ($range ? (count($range) === 2 ? ', from $' . $range[0] . ' to $' . $range[1] . '/month' : ', $' . $range[0] . '/month') : '') . '.';
        }
        $spec = array_filter([$beds, $areas]);
        if ($spec) {
            $s[] = implode(', ', $spec) . '.';
        }
        $s[] = 'Real photos, free advice and fast quotes - Property Plus.';
        return pplus_seo_fit_sentences($s, 160);
    }
    if ($lang === 'ja') {
        $s   = [];
        $s[] = $name . 'の賃貸アパート情報です。' . ($addr !== '' ? '所在地は' . $addr . 'です。' : '');
        if ($total > 0) {
            $s[] = '現在' . $total . '件を掲載しております' . ($range ? (count($range) === 2 ? '（月額$' . $range[0] . '〜$' . $range[1] . '）' : '（月額$' . $range[0] . '）') : '') . '。';
        }
        $spec = array_filter([$beds !== '' ? '間取りは' . $beds : '', $areas !== '' ? '面積は' . $areas : '']);
        if ($spec) {
            $s[] = implode('、', $spec) . 'です。';
        }
        $s[] = '無料のご相談・お見積もりを承っております（Property Plus）。';
        return pplus_seo_fit_sentences($s, 130, '');
    }
    $s   = [];
    $s[] = 'Cho thuê căn hộ tại ' . $name . ($addr !== '' ? ', ' . $addr : '') . '.';
    if ($total > 0) {
        $s[] = 'Hiện có ' . $total . ' căn đang cho thuê' . ($range ? (count($range) === 2 ? ', giá từ $' . $range[0] . ' đến $' . $range[1] . '/tháng' : ', giá $' . $range[0] . '/tháng') : '') . '.';
    }
    $spec = array_filter([$beds, $areas !== '' ? 'diện tích ' . $areas : '']);
    if ($spec) {
        $s[] = implode(', ', $spec) . '.';
    }
    $s[] = 'Ảnh thật, tư vấn miễn phí, báo giá nhanh - Property Plus.';
    return pplus_seo_fit_sentences($s, 160);
}

/**
 * Title cua can ho tu API co dang "Du an - Toa Du an - Tang 4": khi ten toa trung ten du an thi bo phan lap ("LeHome Apartment - Tầng 4").
 */
function pplus_seo_clean_apartment_title(array $d): string {
    $title = trim((string) ($d['title'] ?? ''));
    if ($title === '') {
        return '';
    }
    $segs = array_map('trim', explode(' - ', $title));
    if (count($segs) >= 3 && $segs[0] !== '' && mb_stripos($segs[1], $segs[0]) !== false && mb_strlen($segs[1]) <= mb_strlen($segs[0]) + 12) {
        unset($segs[1]); // "Tòa LeHome Apartment" / "Building LeHome Apartment" / "ビル LeHome Apartment" khi trung ten du an
    }
    return implode(' - ', $segs);
}

/**
 * Title can ho khi Admin chua nhap: "Cho Thuê Căn Hộ 1 Phòng Ngủ Tại LeHome Apartment - Quận Hoàn Kiếm - $600/Tháng (ID 7372)".
 * ID de moi can mot title rieng (nhieu can cung toa, cung so phong, cung gia) va khop H1 "(ID: ...)" cua trang.
 */
function pplus_seo_apartment_title(array $d): string {
    $name = trim((string) ($d['projectName'] ?? ''));
    if ($name === '') {
        $name = trim((string) ($d['buildingName'] ?? ''));
    }
    if ($name === '') {
        return pplus_seo_clean_apartment_title($d);
    }
    $lang  = pplus_current_lang();
    $bed   = pplus_seo_bed_label((int) ($d['bedRoomQty'] ?? 0));
    $price = (float) ($d['price'] ?? 0);
    $dist  = pplus_seo_district_label($d);
    $id    = (int) ($d['apartmentId'] ?? 0) ?: (int) get_query_var('pplus_ap_id');
    $idtxt = $id > 0 ? ' (ID ' . $id . ')' : '';
    if ($lang === 'en') {
        $lead  = $bed . ' Apartment for Rent at ' . $name;
        $money = $price > 0 ? '$' . pplus_seo_money($price) . '/month' : '';
    } elseif ($lang === 'ja') {
        $lead  = $name . ' ' . $bed . ' 賃貸アパート';
        $money = $price > 0 ? '月額$' . pplus_seo_money($price) : '';
    } else {
        $lead  = 'Cho Thuê Căn Hộ ' . $bed . ' Tại ' . $name;
        $money = $price > 0 ? '$' . pplus_seo_money($price) . '/Tháng' : '';
    }
    $c = [];
    if ($dist !== '' && $money !== '') {
        $c[] = $lead . ' - ' . $dist . ' - ' . $money . $idtxt;
    }
    if ($money !== '') {
        $c[] = $lead . ' - ' . $money . $idtxt;
    }
    $c[] = $lead . $idtxt;
    return pplus_seo_pick_title($c, 72);
}
