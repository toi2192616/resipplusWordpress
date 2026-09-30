<?php
/**
 * A3/A4 nhanh "Dự án cụ thể" - /cho-thue-can-ho/{province}/{district}/{project-alias}-{id}/ (VI)
 * hoac /rental-apartment/{province}/{district}/{project-alias}-{id}/ (EN/JA, tien to da bi strip
 * boi inc/language-route.php). 3 DOAN dong sau "cho-thue-can-ho/" (id NOI voi alias qua dau "-" o
 * DOAN CUOI CUNG - KHAC voi A2 can ho o inc/apartment-detail-route.php, id la 1 DOAN RIENG).
 *
 * Xac nhan truc tiep tu app-routing.module.ts: 'vi/cho-thue-can-ho/:province/:district/:project'
 * CUNG mot component (ReferenceIndexComponent) voi bien the 'province/:alias' (2 doan, dang ky trong
 * inc/reference-index-route.php) - component tu re nhanh trong chinh no theo "route co tham so
 * 'project' hay khong" (xem reference-index.component.ts dong 51-55). O day tach thanh 1 route/
 * template RIENG (khong nhet vao reference-index-route.php) vi API GOI HOAN TOAN KHAC
 * (Project/Detail, khong phai Reference/GetIndex) - xem pplus_get_project_detail().
 *
 * Bug thuc te nguoi dung bao cao (30/9): "nhấn vào chỗ khoanh đỏ đang không được" (breadcrumb ten
 * du an tren trang chi tiet can ho, page-apartment-detail.php $link_project) va truoc do "Xem thêm"
 * canh "Dự án" tren cung trang - CA HAI deu tro toi dung dang URL nay, chua tung dang ky nen 404
 * 100%. File nay la fix goc.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', function () {
    add_rewrite_rule(
        '^cho-thue-can-ho/([^/]+)/([^/]+)/([^/]+)/?$',
        'index.php?pplus_project_detail=1&pplus_pd_alias=$matches[3]',
        'top'
    );
    add_rewrite_rule(
        '^rental-apartment/([^/]+)/([^/]+)/([^/]+)/?$',
        'index.php?pplus_project_detail=1&pplus_pd_alias=$matches[3]',
        'top'
    );
});

add_filter('query_vars', function (array $vars): array {
    $vars[] = 'pplus_project_detail';
    $vars[] = 'pplus_pd_alias';
    return $vars;
});

add_action('parse_query', function (WP_Query $query) {
    if ($query->is_main_query() && get_query_var('pplus_project_detail')) {
        $query->is_home = false;
    }
});

add_action('template_redirect', function () {
    if (get_query_var('pplus_project_detail')) {
        global $wp_query;
        $wp_query->is_404 = false;
        status_header(200);
    }
}, 0);

add_action('init', function () {
    if (get_option('pplus_rewrite_flushed_project_detail_v1') !== '1') {
        flush_rewrite_rules();
        update_option('pplus_rewrite_flushed_project_detail_v1', '1');
    }
}, 20);

add_filter('template_include', function (string $template): string {
    if (get_query_var('pplus_project_detail')) {
        $custom = PPLUS_THEME_DIR . '/page-project-detail.php';
        if (file_exists($custom)) {
            return $custom;
        }
    }
    return $template;
});

/**
 * Tach id o CUOI CUNG cua alias (sau dau "-" cuoi cung, giong het cach LinkProject duoc sinh ra -
 * xem $link_project trong page-apartment-detail.php: "{alasProject}-{projectId}"). Cung 1 quy tac
 * voi pplus_parse_office_alias_id() (inc/office-detail-route.php) nhung KHONG goi ham do - file do
 * KHONG duoc require trong functions.php (van con la file "mo coi" tu ban Van phong copy sang, xem
 * ghi chu trong page-office-detail.php/docblock file do), tu tach rieng o day de khong phu thuoc 1
 * file dormant co the khong ton tai luc chay.
 */
function pplus_get_project_detail_from_query_vars(): ?array {
    static $cached = null;
    static $has_run = false;
    if ($has_run) {
        return $cached;
    }
    $has_run = true;

    $alias = sanitize_text_field(get_query_var('pplus_pd_alias'));
    if ($alias === '') {
        return null;
    }
    $parts = explode('-', $alias);
    $id    = (int) end($parts);
    if ($id <= 0) {
        return null;
    }

    $cached = pplus_get_project_detail($id);
    return $cached;
}

// "dataSelected" co the rong (bai viet SEO tuy chon, xem ghi chu day du trong page-project-detail.php)
// - fallback ve "projectDetail.projectName" giong dung nhanh else cua reference-index.component.ts,
// KHONG tra ve $title mac dinh cua WordPress (se ra ten site chung chung, khong lien quan du an).
add_filter('pre_get_document_title', function (string $title): string {
    if (!get_query_var('pplus_project_detail')) {
        return $title;
    }
    $model = pplus_get_project_detail_from_query_vars();
    if (!$model || empty($model['projectDetail'])) {
        return $title;
    }
    return $model['dataSelected']['metaTitle']
        ?? $model['dataSelected']['title']
        ?? $model['projectDetail']['projectName']
        ?? $title;
});

add_action('wp_head', function () {
    if (!get_query_var('pplus_project_detail')) {
        return;
    }
    $model = pplus_get_project_detail_from_query_vars();
    if (!$model || empty($model['projectDetail'])) {
        return;
    }
    $description = $model['dataSelected']['metaDescription']
        ?? $model['dataSelected']['title']
        ?? $model['projectDetail']['projectName']
        ?? '';
    if ($description) {
        printf('<meta name="description" content="%s" />' . "\n", esc_attr($description));
    }
}, 1);
