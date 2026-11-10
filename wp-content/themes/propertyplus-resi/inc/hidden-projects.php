<?php
/**
 * "Dự án đã gỡ" - cờ ẩn DỰ ÁN ở phía Resi (11/11/2026).
 *
 * VÌ SAO CÓ: Admin .NET chỉ có "dừng đăng" ở mức CĂN HỘ, không có cách ẩn một DỰ ÁN. Ô "Hiển thị" / "Hiển thị gợi ý" ở Admin > Bài viết Dự án chỉ
 * điều khiển bài viết SEO của dự án, không ẩn dự án (dự án Hòa Xá, ID 745: cả hai ô bỏ trống mà trang vẫn mở 200, vẫn nằm trong gợi ý tìm kiếm).
 * Bỏ khỏi sitemap cũng không đủ để gỡ trang. Quy tắc "dự án không còn căn hộ nào thì ẩn" KHÔNG dùng được: lấy mẫu 29 dự án trong sitemap thì
 * 13 dự án (~45%) không có căn hộ nào đang đăng, nhưng vẫn là trang dự án có nội dung (ảnh, mô tả, bài SEO) - ẩn hết sẽ mất gần nửa số trang.
 *
 * Nên cần một DANH SÁCH rõ ràng các dự án phải gỡ. Nhập ID (cách nhau bằng dấu phẩy, khoảng trắng hoặc xuống dòng) ở:
 *   WordPress admin > PropertyPlus (cấu hình) > "Dự án đã gỡ (danh sách ID)"; hoặc define('PPLUS_HIDDEN_PROJECT_IDS', '745,790,793') trong wp-config.php
 * (hai nguồn được gộp). Mặc định RỖNG = không đổi gì. Với mỗi ID trong danh sách, Resi:
 *   - trang dự án trả 410 Gone (noindex, không cache), không gọi API chi tiết;
 *   - bỏ khỏi gợi ý tìm kiếm (REST pplus/v1/search-group);
 *   - bỏ khỏi sitemap (kể cả khi Admin vẫn liệt kê) - xem inc/sitemap-admin.php;
 *   - trên trang chi tiết căn hộ, tên dự án hiện là chữ thường, không còn link tới trang đã gỡ.
 * Đây là chốt chặn tạm: khi backend có cờ "ngừng dự án" và các API công khai (Project/Detail, Apartment/_GetFilterGroupList, ...) ngừng trả dự án
 * đó thì Resi tự trả 404 (API trả 302 về trang đăng nhập = không tìm thấy), danh sách này không còn cần nữa.
 */

if (!defined('ABSPATH')) {
    exit;
}

/** @return int[] ID các dự án đã gỡ (gộp hằng PPLUS_HIDDEN_PROJECT_IDS và ô trong WP admin). */
function pplus_hidden_project_ids(): array {
    static $ids = null;
    if ($ids !== null) {
        return $ids;
    }
    $raw = (defined('PPLUS_HIDDEN_PROJECT_IDS') ? (string) PPLUS_HIDDEN_PROJECT_IDS : '') . ' ' . (string) get_option('pplus_hidden_project_ids', '');
    preg_match_all('/\d+/', $raw, $m);
    $ids = array_values(array_unique(array_filter(array_map('intval', $m[0]))));
    return $ids;
}

function pplus_project_is_hidden(int $project_id): bool {
    return $project_id > 0 && in_array($project_id, pplus_hidden_project_ids(), true);
}

/** Ô nhập trong trang cấu hình PropertyPlus của WP admin (inc/settings-page.php). */
add_action('admin_init', function () {
    register_setting('pplus_settings', 'pplus_hidden_project_ids', [
        'sanitize_callback' => static function ($v): string {
            preg_match_all('/\d+/', (string) $v, $m);
            return implode(', ', array_unique($m[0]));
        },
        'default' => '',
    ]);
});

/**
 * Meta robots cho trang dự án: đã gỡ -> "noindex, nofollow" (header X-Robots-Tag gửi ở page-project-detail.php);
 * "vỏ" dự án (không căn hộ đang đăng + không bài viết SEO hiển thị, xem page-project-detail.php) -> "noindex, follow".
 */
add_filter('wp_robots', static function (array $robots): array {
    if (!empty($GLOBALS['pplus_project_gone'])) {
        return ['noindex' => true, 'nofollow' => true];
    }
    if (!empty($GLOBALS['pplus_project_shell'])) {
        unset($robots['index'], $robots['nofollow']);
        $robots['noindex'] = true;
        $robots['follow']  = true;
    }
    return $robots;
}, 99);

/** Gợi ý tìm kiếm: bỏ dự án đã gỡ (hậu xử lý kết quả của inc/search-group-route.php). */
add_filter('pplus_search_group_projects', static function (array $projects): array {
    return array_values(array_filter($projects, static fn(array $p): bool => !pplus_project_is_hidden((int) ($p['id'] ?? 0))));
});

/**
 * Bỏ các <url> của dự án đã gỡ khỏi sitemap con "products" (URL kết thúc bằng "-{id}"). Dùng cho nội dung lấy từ Admin trong inc/sitemap-admin.php.
 */
function pplus_sitemap_drop_hidden_projects(string $xml): string {
    $ids = pplus_hidden_project_ids();
    if (!$ids || strpos($xml, '<url>') === false) {
        return $xml;
    }
    $alt = implode('|', array_map('intval', $ids));
    return (string) preg_replace('#\s*<url>(?:(?!</url>).)*?-(?:' . $alt . ')</loc>(?:(?!</url>).)*?</url>#s', '', $xml);
}
