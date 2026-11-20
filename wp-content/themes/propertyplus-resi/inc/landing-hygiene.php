<?php
/**
 * Trang đích (landing) KHÔNG phải trang căn hộ - vệ sinh index + sitemap (20/11/2026).
 *
 * Kiểm toán 20/11 (Search Console + 206 URL trong sitemap_quan/sitemap_type): Admin đang lưu BÀI TIN NỘI BỘ (văn hóa công ty, team building, hiến máu,
 * phỏng vấn ban lãnh đạo...; trường "loai" = 90/91/92) trong CÙNG bảng với trang đích căn hộ (loai 1 quận, 3 khu vực/chủ đề, 4 tòa nhà, 9 số phòng ngủ).
 * Nội dung các bài này RỖNG (0-18 ký tự) nên trang chỉ là danh sách căn hộ chung với tiêu đề lạc đề ("A great day", "Rules for ringing the doorbell",
 * "Team building at Co To beach"...). Bản tiếng Việt không có loại này; EN có 18, JA có 19 trang; 28 trang vẫn "index, follow" (Admin chỉ tắt index cho một
 * số bài). Ngoài ra 3 trang "Villa-*" trả 410 + 3 alias 404 + 1 trang Admin đã tắt index vẫn nằm trong sitemap, và URL bị lặp 2-3 lần trong cùng file
 * sitemap do bộ sinh sitemap của Admin.
 *
 * Cách xử lý KHÔNG đổi Admin:
 *  1. pplus_landing_is_news_shell(): loai >= 90 VÀ bài viết < 200 ký tự chữ -> meta robots "noindex, follow" (xem inc/reference-index-route.php). Khi biên tập
 *     viên viết bài thật (>= 200 ký tự) trang tự hợp lệ trở lại - cùng quy tắc với dự án "vỏ" (page-project-detail.php).
 *  2. pplus_sitemap_clean_urls(): bỏ URL trùng lặp; riêng sitemap quan/type bỏ thêm các URL trong danh sách dưới đây (tin nội bộ, 404/410, trang Admin đã tắt
 *     index). Danh sách lấy từ API thật 20/11/2026; bài mới thêm vào Admin vẫn tự noindex theo mục 1, chỉ còn nằm trong sitemap cho đến khi được thêm vào đây
 *     (hoặc Back lọc "loai" < 90 và isIndex = true khi sinh sitemap - xem docs/DEV_YEU_CAU_TRUONG_SEO_ADMIN.md).
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Trang đích là "bài tin nội bộ rỗng" (loai >= 90, chưa có bài viết thật)? */
function pplus_landing_is_news_shell(array $data): bool {
    if ((int) ($data['loai'] ?? 0) < 90) {
        return false;
    }
    $text = trim((string) preg_replace('/\s+/u', ' ', wp_strip_all_tags((string) ($data['content'] ?? '') . ' ' . (string) ($data['description'] ?? ''))));
    return mb_strlen($text) < (int) apply_filters('pplus_landing_min_article_chars', 200);
}

/** @return array<string,array<string,bool>> alias (chữ thường) không nên nằm trong sitemap, theo ngôn ngữ. */
function pplus_landing_sitemap_denylist(): array {
    static $list = null;
    if ($list === null) {
        $raw  = [
            'en' => [
                '03-bedrooms-apartment-for-rent-in-r2-royal-city-ready-to-move-in',
                '12',
                'a-great-day',
                'book-donation-at-the-children-hopital',
                'experience-of-japanese-people-in-hanoi',
                'fdfdf',
                'international-standard-service',
                'japanese-peoples-experience-of-renting-a-house',
                'mr-pham-van-loi-chairman-of-the-board-of-directors-applies-technology-to-renting-apartments',
                'ms-tran-thi-hoa-general-director-analyzes-apartment-rental-trends',
                'ms-tran-thi-hoa-general-director-commented-on-the-rental-real-estate-market',
                'pioneering-technology',
                'professional-care',
                'professional-working-culture-at-property-plus-company-office',
                'quality-comes-from-dedication',
                'rules-for-ringing-the-doorbell',
                'silent-connection',
                'team-building-at-co-to-beach',
                'teambuilding-da-nang-beach',
                'technical-maintenance-247',
                'the-journey-of-fresh-lychee',
                'Villa-ciputra',
                'Villa-starlake',
                'Villa-west-lake',
                'voluntary-blood-donation',
            ],
            'ja' => [
                '1',
                '12',
                '123',
                '1234',
                '12345',
                '123456',
                '1234567',
                '12345678',
                '123456789',
                '1234567890',
                '12345678901',
                '123456789012',
                '1324567890',
                '2345644',
                '24365',
                'co-to-',
                'pham-van-loi',
                'property-plus-',
                'r2--3-',
                'tran-thi-hoa-',
                'Villa-ciputra',
                'Villa-tay-ho-tay',
                'Villa-west-lake',
            ],
        ];
        $list = [];
        foreach ($raw as $lang => $aliases) {
            foreach ($aliases as $a) {
                $list[$lang][mb_strtolower($a)] = true;
            }
        }
    }
    return $list;
}

/** Làm sạch 1 file sitemap của Admin: bỏ <url> lặp (giữ cái đầu); sitemap quan/type bỏ thêm URL trong denylist. */
function pplus_sitemap_clean_urls(string $xml, string $which = ''): string {
    if (strpos($xml, '<url>') === false) {
        return $xml;
    }
    $deny = in_array($which, ['quan', 'type'], true) ? pplus_landing_sitemap_denylist() : [];
    $seen = [];
    $out  = preg_replace_callback('#\s*<url>.*?</url>#s', static function (array $m) use (&$seen, $deny): string {
        if (!preg_match('#<loc>\s*([^<]+?)\s*</loc>#', $m[0], $l)) {
            return $m[0];
        }
        $loc = mb_strtolower(rawurldecode(html_entity_decode($l[1], ENT_QUOTES | ENT_XML1)));
        if (isset($seen[$loc])) {
            return '';
        }
        $seen[$loc] = true;
        if ($deny && preg_match('#/(vi|en|ja)/[^/]+/[^/]+/([^/]+?)/?$#u', $loc, $p) && isset($deny[$p[1]][$p[2]])) {
            return '';
        }
        return $m[0];
    }, $xml);
    return $out === null ? $xml : $out;
}
