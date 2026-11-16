<?php
/**
 * Tai lieu cho AI/tac nhan (16/11/2026) - PageSpeed "Duyet web bang tac nhan":
 *   - /llms.txt                       Markdown theo de xuat llmstxt.org (bat buoc co 1 H1) - tom tat site + link cac trang chinh.
 *   - /.well-known/ard.json           Ban kê Agentic Resource Discovery (ARD) - ten MOI Lighthouse 13.x doc.
 *   - /.well-known/ai-catalog.json    CUNG noi dung, ten cu (PageSpeed ban cu va nguoi dung cu van doc ten nay).
 *
 * ARD la hop dong: CHI liet ke thu that su chay. Hien Property Plus KHONG co MCP server / A2A agent / Agent Skill nao nen "entries" de TRONG (hop le:
 * schema cho phep mang rong). Khi co (vd MCP server tim can ho), them 1 muc vao pplus_ard_manifest(): identifier (urn:air:...), displayName, type (media type),
 * dung 1 trong "url" hoac "data".
 *
 * Dung hook "init" uu tien 1 + so khop DUONG DAN CHINH XAC (khong can flush rewrite rules khi deploy) roi exit. Tat: define('PPLUS_AI_DISCOVERY', false).
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Quan Ha Noi co trang danh sach (slug EN trong sitemap_quan): [ten, slug]. */
function pplus_ai_districts(): array {
    return [
        ['Ba Dinh', 'ba-dinh-district'], ['Bac Tu Liem', 'bac-tu-liem-district'], ['Cau Giay', 'cau-giay-district'], ['Dong Da', 'dong-da-district'],
        ['Hai Ba Trung', 'hai-ba-trung-district'], ['Hoan Kiem', 'hoan-kiem-district'], ['Hoang Mai', 'hoang-mai-district'], ['Long Bien', 'long-bien-district'],
        ['Nam Tu Liem', 'nam-tu-liem-district'], ['Tay Ho', 'tay-ho-district'], ['Thanh Xuan', 'thanh-xuan-district'],
    ];
}

/** Noi dung /llms.txt (Markdown). Link tuyet doi theo host dang chay (production = propertyplus.com.vn). */
function pplus_llms_txt_body(): string {
    $u = static fn(string $path): string => home_url($path);
    $out = [];
    $out[] = '# Property Plus';
    $out[] = '';
    $out[] = '> Property Plus (propertyplus.com.vn) is a Hanoi rental agency and apartment-management company. Its website lists furnished and serviced apartments for rent in Hanoi - by district, by apartment type and by building/project - with photos, monthly rent, bedrooms, bathrooms, floor area and map location. The site is available in Vietnamese (/vi), English (/en) and Japanese (/ja).';
    $out[] = '';
    $out[] = 'Operator: CÔNG TY CỔ PHẦN THƯƠNG MẠI VÀ ĐẦU TƯ BẤT ĐỘNG SẢN ĐẠI LỢI, Floor 4, Kinh Do building, 292 Tay Son, Dong Da, Hanoi, Vietnam.';
    $out[] = '';
    $out[] = 'Notes for agents:';
    $out[] = '- Listings and prices change often. Read the listing or project page for the current rent and availability instead of relying on a cached summary.';
    $out[] = '- Every page exists in three languages with the same structure; each page links its other-language versions (hreflang alternates).';
    $out[] = '- To ask about an apartment or arrange a viewing, use the contact page or the contact forms on the listing pages.';
    $out[] = '';
    $out[] = '## Apartments for rent in Hanoi';
    $out[] = '- [Apartments for rent in Hanoi (English)](' . $u('/en/rental-apartment/ha-noi') . '): all current listings with filters for district, bedrooms and price, plus a map';
    $out[] = '- [Căn hộ cho thuê tại Hà Nội (Tiếng Việt)](' . $u('/vi/cho-thue-can-ho/ha-noi') . '): the same listings in Vietnamese';
    $out[] = '- [ハノイのアパート賃貸 (日本語)](' . $u('/ja/rental-apartment/ha-noi') . '): the same listings in Japanese';
    $out[] = '';
    $out[] = '## Districts (English pages)';
    foreach (pplus_ai_districts() as [$name, $slug]) {
        $out[] = '- [Apartments for rent in ' . $name . ' District](' . $u('/en/rental-apartment/ha-noi/' . $slug) . ')';
    }
    $out[] = '';
    $out[] = '## Company and help';
    $out[] = '- [About us](' . $u('/en/about-us') . '): who Property Plus is and how its apartment service works';
    $out[] = '- [Contact](' . $u('/en/contact') . '): phone, email, office address and an enquiry form';
    $out[] = '- [Blog](' . $u('/en/blogs') . '): guides to renting in Hanoi and its neighbourhoods';
    $out[] = '';
    $out[] = '## Optional';
    $out[] = '- [Sitemap index](' . $u('/sitemap.xml') . '): every listing, project, district and blog URL';
    $out[] = '- [Giới thiệu (Tiếng Việt)](' . $u('/vi/ve-chung-toi') . ')';
    $out[] = '- [Liên hệ (Tiếng Việt)](' . $u('/vi/lien-he') . ')';
    $out[] = '- [Blog (Tiếng Việt)](' . $u('/vi/blogs') . ')';
    return implode("\n", $out) . "\n";
}

/** Ban ke ARD / AI Catalog. "entries" rong = hop le (xem docblock dau file). */
function pplus_ard_manifest(): array {
    return [
        'specVersion' => '1.0',
        'host'        => [
            'displayName' => 'Property Plus',
            'identifier'  => untrailingslashit(home_url('/')),
        ],
        'entries'     => [],
    ];
}

add_action('init', static function () {
    if ((defined('PPLUS_AI_DISCOVERY') && !PPLUS_AI_DISCOVERY) || is_admin()) {
        return;
    }
    $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if ($method !== 'GET' && $method !== 'HEAD') {
        return;
    }
    $path = (string) wp_parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    if ($path === '/llms.txt') {
        $body = pplus_llms_txt_body();
        $type = 'text/plain; charset=utf-8';
    } elseif ($path === '/.well-known/ard.json' || $path === '/.well-known/ai-catalog.json') {
        $body = (string) wp_json_encode(pplus_ard_manifest(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
        $type = 'application/json; charset=utf-8';
        header('Access-Control-Allow-Origin: *');
    } else {
        return;
    }
    status_header(200);
    header('Content-Type: ' . $type);
    header('Cache-Control: public, max-age=3600');
    header('X-Content-Type-Options: nosniff');
    header('Content-Length: ' . strlen($body));
    if ($method !== 'HEAD') {
        echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- van ban thuan/JSON, khong phai HTML
    }
    exit;
}, 1);
