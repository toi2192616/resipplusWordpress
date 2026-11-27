<?php
/**
 * B2 (/blogs, danh sach) + B3 (/blog/{alias}-{id}, chi tiet - CHU Y so IT, khac goc "blogs" cua
 * danh sach - xem ghi chu chi tiet trong pplus_blog_detail_url(), inc/api-client.php). Dung cung
 * 1 co che voi A2 (inc/office-detail-route.php): tu dang ky rewrite rule + query var +
 * template_include, KHONG dung CPT 'news' (inc/cpt-news.php, slug rieng "tin-tuc") - CPT do khong
 * duoc lien ket toi tu bat cu dau trong theme (header.php, front-page.php, sitemap_posts.xml deu
 * tro thang ve /blogs hoac /blog/{alias}-{id}), nen khong dung lam URL cong khai o day de tranh 2
 * nguon su that.
 *
 * SUA 17/9: rewrite rule chi tiet TRUOC DAY dung nham "blogs/{alias}-{id}" (so nhieu, giong het
 * goc danh sach) - xac nhan qua doc truc tiep app-routing.module.ts + moi routerLink that trong
 * blog-list/blog-detail/home.component.html deu dung "blog" SO IT rieng cho chi tiet. Bug nay tu
 * "an toan" voi chinh no (pplus_blog_detail_url() cung sai giong het nen link noi bo van bam duoc)
 * nhung sai hoan toan so voi URL That cua production/API.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', function () {
    // /blog/{alias}-{id} (chi tiet, SO IT) phai dang ky TRUOC /blogs (danh sach, SO NHIEU) de
    // khop dung (2 goc khac nhau nen thuc ra khong de doi trung, nhung giu thu tu cho ro rang).
    add_rewrite_rule('^blog/([^/]+)/?$', 'index.php?pplus_blog_detail=1&pplus_alias_id=$matches[1]', 'top');
    add_rewrite_rule('^blogs/?$', 'index.php?pplus_blog_list=1', 'top');
    add_rewrite_rule('^en/blog/([^/]+)/?$', 'index.php?pplus_blog_detail=1&pplus_alias_id=$matches[1]', 'top');
    add_rewrite_rule('^en/blogs/?$', 'index.php?pplus_blog_list=1', 'top');
});

add_filter('query_vars', function (array $vars): array {
    $vars[] = 'pplus_blog_list';
    $vars[] = 'pplus_blog_detail';
    $vars[] = 'pplus_alias_id';
    return $vars;
});

/**
 * Query var tu (pplus_blog_list/pplus_blog_detail) KHONG duoc WordPress nhan dien la bat ky
 * loai noi dung nao (post/page/archive...), nen WP_Query::parse_query() mac dinh coi day la
 * trang chu (is_home=true vi show_on_front='posts') - lam is_front_page() sai, keo theo
 * parts/header.php gan nham class "headerHome" (an anh nen + doi mau chu sang trang, chi dung
 * cho trang chu that) thay vi ":not(.headerHome)" (co anh nen, dung cho trang con) - trieu chung
 * la ca menu header bien mat (chu trang tren nen trang). Sua tai goc bang parse_query.
 */
add_action('parse_query', function (WP_Query $query) {
    if ($query->is_main_query() && (get_query_var('pplus_blog_list') || get_query_var('pplus_blog_detail'))) {
        $query->is_home = false;
    }
});

add_action('init', function () {
    // Bump "_v3" -> "_v4" (17/9): rewrite rule chi tiet vua doi tu "blogs/" sang "blog/" - option
    // cu da la '1' tu truoc, se KHONG tu flush lai neu giu nguyen ten, khien rule MOI khong bao gio
    // duoc ghi vao bang rewrite that su (van khop nham theo rule cu da cache).
    if (get_option('pplus_rewrite_flushed_v4') !== '1') {
        flush_rewrite_rules();
        update_option('pplus_rewrite_flushed_v4', '1');
    }
}, 20);

add_filter('template_include', function (string $template): string {
    if (get_query_var('pplus_blog_list')) {
        $custom = PPLUS_THEME_DIR . '/archive-news.php';
        if (file_exists($custom)) {
            return $custom;
        }
    }
    if (get_query_var('pplus_blog_detail')) {
        $custom = PPLUS_THEME_DIR . '/single-news.php';
        if (file_exists($custom)) {
            return $custom;
        }
    }
    return $template;
});

/**
 * Tieu de + mo ta trang danh sach bai viet theo ngon ngu (26/11/2026: truoc day /ja/blogs la "Blog - PropertyPlus" - khong co chu Nhat,
 * va mo ta dung chung tagline voi nhieu trang khac). Chu de trong mo ta = cac chuyen muc/bai that dang co. Tieng Nhat dung kinh ngu.
 */
function pplus_blog_list_seo_texts(?string $lang = null): array {
    $texts = [
        'vi' => [
            'title'       => 'Tin Tức, Cẩm Nang Thuê Căn Hộ Tại Hà Nội | Property Plus',
            'description' => 'Cẩm nang thuê căn hộ và sinh sống tại Hà Nội: thủ tục nhập cảnh, tạm trú cho người nước ngoài, khu vực nên ở, ăn uống, mua sắm và văn hóa Việt Nam.',
        ],
        'en' => [
            'title'       => 'Hanoi Rental and Expat Living Guides | Property Plus',
            'description' => 'Guides to renting and living in Hanoi: entry and residence procedures for foreigners, where to stay, dining, shopping and Vietnamese culture.',
        ],
        'ja' => [
            'title'       => 'ハノイの賃貸・生活ガイド（ブログ）｜Property Plus',
            'description' => 'ハノイでのお部屋探しと暮らしに役立つ情報をご紹介しております。入国・在留の手続き、住むエリア、グルメ、ショッピング、ベトナムの文化などをご覧いただけます。',
        ],
    ];
    return $texts[$lang ?: pplus_current_lang()] ?? $texts['vi'];
}

add_filter('pre_get_document_title', function (string $title): string {
    if (!get_query_var('pplus_blog_list')) {
        return $title;
    }
    return pplus_blog_list_seo_texts()['title'];
});

add_action('wp_head', function () {
    if (!get_query_var('pplus_blog_list')) {
        return;
    }
    // inc/seo-head.php thay the da co the description thi khong in them cau du phong.
    printf('<meta name="description" content="%s" />' . "\n", esc_attr(pplus_blog_list_seo_texts()['description']));
    printf('<meta name="robots" content="%s" />' . "\n", esc_attr('index, follow'));
}, 1);

/**
 * Wrapper doc query var hien tai va goi pplus_get_blog_detail() - dung chung boi title/meta
 * filter va single-news.php, static-cache trong 1 request (giong pattern A2/A4).
 */
function pplus_get_blog_detail_from_query_vars(): ?array {
    static $cached = null;
    static $has_run = false;
    if ($has_run) {
        return $cached;
    }
    $has_run = true;

    $alias_id = sanitize_text_field(get_query_var('pplus_alias_id'));
    if (!$alias_id) {
        return null;
    }
    $parsed = pplus_parse_alias_id($alias_id);
    if ($parsed['id'] <= 0) {
        return null;
    }
    $cached = pplus_get_blog_detail($parsed['id'], $parsed['alias']);
    return $cached;
}

add_filter('pre_get_document_title', function (string $title): string {
    if (!get_query_var('pplus_blog_detail')) {
        return $title;
    }
    $data = pplus_get_blog_detail_from_query_vars();
    $selected = $data['result']['data'] ?? null;
    if (!$selected) {
        return $title;
    }
    return $selected['metaTitle'] ?? ($selected['title'] ?? $title);
});

/** Chuoi mo ta tu Admin da lam sach: bo the/khoang trang thua; chuoi bi dan 2 lan lien nhau ("AA") -> giu 1; trung tieu de -> coi nhu trong. */
function pplus_blog_clean_admin_description(string $text, string $title): string {
    $norm  = static fn (string $s): string => trim((string) preg_replace('/[\s\x{00A0}\x{3000}]+/u', ' ', html_entity_decode(wp_strip_all_tags($s), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    $text  = $norm($text);
    $len   = mb_strlen($text);
    if ($len >= 40) {
        $half  = intdiv($len, 2);
        $first = trim(mb_substr($text, 0, $half));
        if ($first === trim(mb_substr($text, $len - $half))) {
            $text = $first;
        }
    }
    return mb_strtolower($text) === mb_strtolower($norm($title)) ? '' : $text;
}

/**
 * Doan mo dau cua bai (doan <p> dau tien du dai; muc luc nam trong <ol>/<li> nen khong bi lay) cat gon lam mo ta.
 * Do chuoi bang stripos (khong regex .*?) vi noi dung blog co the 2-6 MB do anh nhung base64.
 */
function pplus_blog_content_excerpt(string $html, string $lang): string {
    $ja     = $lang === 'ja';
    $min    = $ja ? 30 : 60;
    $max    = $ja ? 110 : 160;
    $offset = 0;
    for ($i = 0; $i < 40; $i++) {
        $start = stripos($html, '<p', $offset);
        if ($start === false) {
            break;
        }
        $end = stripos($html, '</p>', $start);
        if ($end === false) {
            break;
        }
        $offset = $end + 4;
        $next   = $html[$start + 2] ?? '';
        if ($next !== '>' && !ctype_space($next)) {
            $offset = $start + 2; // the khac bat dau bang "<p..." (vd <pre>, <picture>)
            continue;
        }
        $text = html_entity_decode(strip_tags(substr($html, $start, $end - $start)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim((string) preg_replace('/[\s\x{00A0}\x{3000}]+/u', ' ', $text));
        if (mb_strlen($text) < $min || mb_stripos($text, 'Mục lục') === 0) {
            continue;
        }
        if (mb_strlen($text) <= $max) {
            return $text;
        }
        // Uu tien cat o cuoi cau (du dai); khong co thi cat theo tu (tieng Nhat: cat thang) + "…".
        $cut = mb_substr($text, 0, $max);
        if (preg_match($ja ? '/^.*[。！？]/u' : '/^.*[.!?](?=\s|$)/u', $cut, $m) && mb_strlen($m[0]) >= ($ja ? 40 : 80)) {
            return $m[0];
        }
        if (!$ja) {
            $sp  = mb_strrpos($cut, ' ');
            $cut = $sp !== false && $sp > $max * 0.6 ? mb_substr($cut, 0, $sp) : $cut;
        }
        return rtrim($cut, " ,.;:、-") . '…';
    }
    return '';
}

/**
 * Mo ta tim kiem cua 1 bai blog (26/11/2026). Thu tu: Meta Description trong Admin -> Gioi thieu ngan (subTitle) -> doan mo dau cua bai.
 * Truoc day bai en/ja bo trong o tom tat dung chung cau gioi thieu cong ty -> nhieu trang trung mo ta tren Google.
 */
function pplus_blog_meta_description(array $post, ?string $lang = null): string {
    $title = (string) ($post['title'] ?? '');
    // 27/11/2026: bai mo dau bang gach dau dong ("- Ho chieu ...") -> bo ky hieu dau dong o dau mo ta.
    $unbullet = static fn (string $s): string => (string) preg_replace('/^(?:[\-–—•●▪・*]+(?:[\s\x{00A0}\x{3000}]+|(?=[^\d\s])))+/u', '', $s);
    foreach (['metaDescription', 'subTitle'] as $field) {
        $text = pplus_blog_clean_admin_description((string) ($post[$field] ?? ''), $title);
        if ($text !== '') {
            return $unbullet($text);
        }
    }
    return $unbullet(pplus_blog_content_excerpt((string) ($post['content'] ?? ''), $lang ?: pplus_current_lang()));
}

add_action('wp_head', function () {
    if (!get_query_var('pplus_blog_detail')) {
        return;
    }
    $data = $data_model = pplus_get_blog_detail_from_query_vars();
    $selected = $data['result']['data'] ?? null;
    if (!$selected) {
        return;
    }
    $description = pplus_blog_meta_description((array) $selected);
    if ($description) {
        printf('<meta name="description" content="%s" />' . "\n", esc_attr($description));
    }
    $robots = ($selected['isIndex'] === false) ? 'noindex, nofollow' : 'index, follow';
    printf('<meta name="robots" content="%s" />' . "\n", esc_attr($robots));
}, 1);

/**
 * Doi ngon ngu tren trang chi tiet blog: alias moi ngon ngu khac nhau (vi "...-8" / en "Property-Plus's-Success-Stories-8"), web chinh
 * (blog-detail.component.ts) dung aliasVi/aliasEn/aliasJa cua bai. Neu thieu alias o ngon ngu dich -> giu mac dinh (doi goc + alias hien tai).
 */
add_filter('pplus_language_switch_url', function (string $default, string $target_lang) {
    if (!get_query_var('pplus_blog_detail')) {
        return $default;
    }
    $data = pplus_get_blog_detail_from_query_vars();
    $post = $data['result']['data'] ?? null;
    if (!is_array($post) || empty($post['id'])) {
        return $default;
    }
    $alias = (string) ($post['alias' . ucfirst($target_lang)] ?? '');
    if ($alias === '') {
        return $default;
    }
    return home_url(pplus_lang_prefix($target_lang) . '/blog/' . $alias . '-' . (int) $post['id']);
}, 10, 2);
