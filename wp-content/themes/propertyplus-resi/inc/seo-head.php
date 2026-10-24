<?php
/**
 * Lop SEO / GEO tap trung cho ban Resi (30/9/2026).
 *
 * Truoc file nay Resi chi co title/meta description RIENG LE trong tung route (xem cac
 * add_filter('pre_get_document_title') trong inc/*-route.php) - khong co canonical (tru trang WP
 * Page that), khong co Open Graph/Twitter, khong co JSON-LD, trang chu/dich vu/chu nha khong co meta
 * description. File nay bo sung PHAN CON THIEU o 1 cho, KHONG sua/tat cac hook san co cua tung route:
 *   - gom (ob_start) toan bo output cua wp_head de biet route da in san description/robots/canonical
 *     chua -> chi bo sung khi thieu, khong bao gio in trung.
 *   - canonical tu URL that (co tien to /en /ja, bo query rac, giu ?paged=N).
 *   - Open Graph + Twitter card, og:locale theo ngon ngu.
 *   - JSON-LD @graph: Organization+RealEstateAgent (du lieu lay tu chinh JSON-LD trong index.html
 *     cua Angular Resi), WebSite, WebPage/CollectionPage, + Apartment/Offer (chi tiet can ho) +
 *     BlogPosting (chi tiet blog).
 *   - BreadcrumbList JSON-LD sinh THANG TU breadcrumb dang hien (".url-content-list") o cuoi request
 *     (ob toan trang) - luon khop dung cai nguoi dung nhin thay, khong lech khi sua template.
 *   - geo.* meta (co trong index.html Angular goc).
 * Chan index toan site cho server test: inc/noindex.php (option/hang so PPLUS_NOINDEX).
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Thong tin doanh nghiep - lay nguyen tu 3 khoi JSON-LD trong PPlusAngularResi/src/index.html. */
function pplus_seo_business(): array {
    return [
        'name'      => 'Property Plus',
        'phone'     => '+84856662566',
        'street'    => 'Tòa nhà 292, P. Tây Sơn, Trung Liệt, Đống Đa, Hà Nội',
        'locality'  => 'Hà Nội',
        'postal'    => '11515',
        'lat'       => 21.0070477,
        'lng'       => 105.8228378,
        'founded'   => '2011',
        'price'     => '$200 - $5100',
        'same_as'   => [
            'https://www.facebook.com/propertyplusgroup',
            'https://www.youtube.com/@propertyplusvietnam4956',
            'https://www.instagram.com/propertyplus.vietnam',
            'https://line.me/ti/p/TAkfiXNubz',
        ],
    ];
}

/**
 * URL canonical cua request hien tai: dang chuan cua web chinh (tien to /vi|/en|/ja, KHONG "/" cuoi; trang chu goc
 * "/" giu "/"), bo query, giu ?paged=N (N>1). Lay tu duong dan GOC (truoc khi strip tien to) - xem inc/url-canonical.php.
 */
function pplus_seo_canonical_url(): string {
    $canonical = pplus_canonical_path(pplus_original_path(), pplus_current_lang()) ?? pplus_original_path();
    $url       = home_url($canonical);
    $paged     = (int) ($_GET['paged'] ?? 0);
    if ($paged > 1) {
        $url = add_query_arg('paged', $paged, $url);
    }
    return $url;
}

/** Loai trang hien tai (de chon title/mo ta/schema du phong). */
function pplus_seo_page_type(): string {
    if (is_front_page()) {
        return 'home';
    }
    foreach ([
        'pplus_apartment_detail' => 'apartment_detail',
        'pplus_apartment_list'   => 'apartment_list',
        'pplus_project_detail'   => 'project_detail',
        'pplus_reference_index'  => 'reference',
        'pplus_blog_detail'      => 'blog_detail',
        'pplus_blog_list'        => 'blog_list',
        'pplus_about_subpage'    => 'about_subpage',
        'pplus_service'          => 'service',
        'pplus_owner'            => 'owner',
    ] as $qv => $type) {
        if (get_query_var($qv)) {
            return $type;
        }
    }
    if (is_page_template('page-about.php')) {
        return 'about';
    }
    return 'page';
}

/** Tagline thuong hieu theo ngon ngu (chuoi VI goc co trong tu dien vi/en/ja cua Angular). */
function pplus_seo_tagline(): string {
    return pplus_localize('Property plus là công ty môi giới bất động sản tại Hà Nội, chuyên tư vấn, quản lý, cho thuê căn hộ và căn hộ dịch vụ cao cấp cho người nước ngoài thuê.');
}

/** Mo ta ngan cho can ho tu cac field that (title da theo ngon ngu hien tai tu API). */
function pplus_seo_apartment_description(array $d): string {
    $parts = [];
    if (!empty($d['title'])) {
        $parts[] = $d['title'];
    }
    $facts = [];
    if (!empty($d['bedRoomQty'])) {
        $facts[] = (int) $d['bedRoomQty'] . ' ' . pplus_localize('Phòng ngủ');
    }
    if (!empty($d['bathRoomQty'])) {
        $facts[] = (int) $d['bathRoomQty'] . ' ' . pplus_localize('Phòng tắm');
    }
    if (!empty($d['area'])) {
        $facts[] = pplus_localize('Diện tích') . ' ' . (float) $d['area'] . ' m²';
    }
    if (!empty($d['priceString'])) {
        $facts[] = pplus_localize('Giá khởi điểm') . ' ' . $d['priceString'] . '$/' . pplus_localize('tháng');
    }
    if ($facts) {
        $parts[] = implode(', ', $facts);
    }
    $is_vi = pplus_current_lang() === 'vi';
    $loc   = array_filter([
        $is_vi ? ($d['address'] ?? '') : '',
        $is_vi ? ($d['districtName'] ?? '') : ($d['districtNameEn'] ?? $d['districtName'] ?? ''),
        $d['provinceName'] ?? '',
    ]);
    if ($loc) {
        $parts[] = implode(', ', array_unique($loc));
    }
    return implode('. ', $parts);
}

/** Cat mo ta ~160 ky tu (theo tu, khong cat giua chu). */
function pplus_seo_trim(string $text, int $max = 160): string {
    $text = trim(preg_replace('/\s+/u', ' ', wp_strip_all_tags($text)));
    if (mb_strlen($text) <= $max) {
        return $text;
    }
    $cut = mb_substr($text, 0, $max);
    $sp  = mb_strrpos($cut, ' ');
    return rtrim($sp !== false && $sp > $max * 0.6 ? mb_substr($cut, 0, $sp) : $cut, " ,.;:-") . '…';
}

/** Ma hoa khoang trang/ky tu dac biet trong path anh (API tra ve ten file co dau cach) de og:image/JSON-LD hop le. */
function pplus_seo_img(string $url): string {
    $p = wp_parse_url($url);
    if (empty($p['host'])) {
        return $url;
    }
    $path = implode('/', array_map('rawurlencode', explode('/', rawurldecode($p['path'] ?? ''))));
    return ($p['scheme'] ?? 'https') . '://' . $p['host'] . (isset($p['port']) ? ':' . $p['port'] : '') . $path . (isset($p['query']) ? '?' . $p['query'] : '');
}

// --- Title du phong (chi khi route chua tu dat title) ---------------------------------------------
add_filter('pre_get_document_title', function (string $title): string {
    if ($title !== '') {
        return $title;
    }
    $type = pplus_seo_page_type();
    if ($type === 'home') {
        // Title/description trang chu: nguyen van home.component.ts cua Angular Resi.
        return pplus_localize('Căn Hộ Cho Thuê Cao Cấp Cho Người Nước Ngoài Ở Hà Nội | PROPERTY PLUS');
    }
    if ($type === 'about') {
        return pplus_localize('Công ty bất động sản ở Hà Nội | Property Plus');
    }
    return $title;
}, 20);

/** Service/Owner tra ve title tho (vd "Dich vu don phong") - noi them thuong hieu neu chua co. */
add_filter('pre_get_document_title', function (string $title): string {
    if (in_array(pplus_seo_page_type(), ['service', 'owner'], true) && $title !== '' && stripos($title, 'property') === false) {
        return $title . ' | Property Plus';
    }
    return $title;
}, 30);

// --- Robots: them max-snippet/video (cho phep AI/snippet trich dan day du), 404 -> noindex -------
add_filter('wp_robots', function (array $robots): array {
    if (http_response_code() === 404) {
        return ['noindex' => true, 'nofollow' => true];
    }
    $robots['max-snippet']       = '-1';
    $robots['max-video-preview'] = '-1';
    return $robots;
}, 100);

// WP tu in canonical cho Page that nhung khong co tien to /en /ja -> sai; ta in canonical rieng o duoi.
add_action('init', function () {
    remove_action('wp_head', 'rel_canonical');
});

// --- Gom output wp_head de bo sung nhung gi con thieu -----------------------------------------------
add_action('wp_head', function () {
    ob_start();
}, 0);

add_action('wp_head', function () {
    $buf = ob_get_clean();
    if ($buf === false) {
        return;
    }
    $type  = pplus_seo_page_type();
    $lang  = pplus_current_lang();
    $url   = pplus_seo_canonical_url();
    $biz   = pplus_seo_business();
    $title = wp_get_document_title();

    // Mo ta: uu tien cai route da in; khong co thi du phong theo loai trang.
    $description = '';
    $emit_desc   = '';
    if (preg_match('#<meta name="description" content="([^"]*)"#i', $buf, $m)) {
        $description = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
    }
    $model  = null;
    $detail = null;
    if ($type === 'apartment_detail') {
        $model  = pplus_get_apartment_detail_from_query_vars();
        $detail = $model['apartmentDetail'] ?? null;
        // Route chi in title lam description (yeu) - thay bang mo ta that tu field cua can ho.
        if ($detail && ($description === '' || $description === ($detail['title'] ?? ''))) {
            $description = pplus_seo_apartment_description($detail);
            $buf = preg_replace('#<meta name="description"[^>]*>\s*#i', '', $buf);
            $emit_desc = '<meta name="description" content="' . esc_attr(pplus_seo_trim($description, 200)) . '" />' . "\n";
        }
    }
    if ($description === '') {
        if ($type === 'home') {
            $description = pplus_localize('Update các căn hộ chung cư, căn hộ dịch vụ, villa cho thuê ở Ba Đình, Tây Hồ, Hoàn Kiếm, Hai Bà Trưng T10/2023. Miễn phí môi giới, cam kết dịch vụ');
        } elseif ($type === 'apartment_list') {
            $description = pplus_t(
                'Tìm căn hộ, căn hộ dịch vụ và villa cho thuê cao cấp tại Hà Nội. Cập nhật giá mới nhất, miễn phí môi giới, hỗ trợ khách nước ngoài.',
                'Find premium apartments, serviced apartments and villas for rent in Hanoi. Latest prices, no brokerage fee, foreigner-friendly support.'
            );
        } else {
            $description = pplus_seo_tagline();
        }
        if ($description !== '' && stripos($buf, 'name="description"') === false) {
            $emit_desc = '<meta name="description" content="' . esc_attr(pplus_seo_trim($description, 200)) . '" />' . "\n";
        }
    }
    echo $buf . $emit_desc;

    // Canonical (chi 1 lan, khong trung neu route/plugin da in).
    if (stripos($buf, 'rel="canonical"') === false && stripos($buf, "rel='canonical'") === false) {
        printf('<link rel="canonical" href="%s" />' . "\n", esc_url($url));
    }

    // Anh dai dien: anh that cua trang neu co, khong thi banner trang chu.
    $image = get_template_directory_uri() . '/assets/Content/Img/home-1920x1080.png';
    if ($detail && !empty($detail['photoList'][0])) {
        $image = pplus_seo_img(pplus_admin_asset_url((string) $detail['photoList'][0]));
    } elseif ($type === 'blog_detail') {
        $blog = pplus_get_blog_detail_from_query_vars();
        $bd   = $blog['result']['data'] ?? [];
        if (!empty($bd['img'])) {
            $image = pplus_seo_img(pplus_admin_asset_url((string) $bd['img']));
        }
    }

    // Open Graph + Twitter.
    $locale_map = ['vi' => 'vi_VN', 'en' => 'en_US', 'ja' => 'ja_JP'];
    $og = [
        'og:site_name'    => $biz['name'],
        'og:type'         => in_array($type, ['blog_detail'], true) ? 'article' : 'website',
        'og:title'        => $title,
        'og:description'  => pplus_seo_trim($description, 200),
        'og:url'          => $url,
        'og:image'        => $image,
        'og:locale'       => $locale_map[$lang] ?? 'vi_VN',
    ];
    foreach ($og as $prop => $val) {
        if ($val !== '' && stripos($buf, 'property="' . $prop . '"') === false) {
            printf('<meta property="%s" content="%s" />' . "\n", esc_attr($prop), esc_attr($val));
        }
    }
    foreach ($locale_map as $l => $loc) {
        if ($l !== $lang) {
            printf('<meta property="og:locale:alternate" content="%s" />' . "\n", esc_attr($loc));
        }
    }
    printf('<meta name="twitter:card" content="summary_large_image" />' . "\n");
    printf('<meta name="twitter:title" content="%s" />' . "\n", esc_attr($title));
    if ($description !== '') {
        printf('<meta name="twitter:description" content="%s" />' . "\n", esc_attr(pplus_seo_trim($description, 200)));
    }
    printf('<meta name="twitter:image" content="%s" />' . "\n", esc_url($image));

    // GEO (co trong index.html Angular goc) - tin hieu vi tri cho AI/engine dia phuong.
    printf('<meta name="geo.region" content="VN-HN" />' . "\n");
    printf('<meta name="geo.placename" content="Hà Nội" />' . "\n");
    printf('<meta name="geo.position" content="%s;%s" />' . "\n", esc_attr($biz['lat']), esc_attr($biz['lng']));
    printf('<meta name="ICBM" content="%s, %s" />' . "\n", esc_attr($biz['lat']), esc_attr($biz['lng']));

    // JSON-LD @graph.
    $org_id     = home_url('/#organization');
    $website_id = home_url('/#website');
    $lang_tag   = ['vi' => 'vi-VN', 'en' => 'en', 'ja' => 'ja'][$lang] ?? 'vi-VN';
    $graph = [
        [
            '@type'        => ['Organization', 'RealEstateAgent'],
            '@id'          => $org_id,
            'name'         => $biz['name'],
            'url'          => home_url('/'),
            'logo'         => get_template_directory_uri() . '/assets/officebase/Common/Images/logo.png',
            'image'        => get_template_directory_uri() . '/assets/Content/Img/home-1920x1080.png',
            'description'  => 'Luxury Apartment For Rent For Foreigners In Hanoi',
            'telephone'    => $biz['phone'],
            'priceRange'   => $biz['price'],
            'foundingDate' => $biz['founded'],
            'founder'      => ['@type' => 'Person', 'name' => 'Phạm Văn Lợi', 'jobTitle' => 'CEO'],
            'address'      => [
                '@type'           => 'PostalAddress',
                'streetAddress'   => $biz['street'],
                'addressLocality' => $biz['locality'],
                'postalCode'      => $biz['postal'],
                'addressCountry'  => 'VN',
            ],
            'geo'          => ['@type' => 'GeoCoordinates', 'latitude' => $biz['lat'], 'longitude' => $biz['lng']],
            'areaServed'   => ['@type' => 'City', 'name' => 'Hà Nội'],
            'contactPoint' => [
                '@type'             => 'ContactPoint',
                'telephone'         => $biz['phone'],
                'contactType'       => 'sales',
                'areaServed'        => 'VN',
                'availableLanguage' => ['Vietnamese', 'English', 'Japanese'],
            ],
            'openingHoursSpecification' => [
                ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'], 'opens' => '08:30', 'closes' => '12:00'],
                ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'], 'opens' => '13:30', 'closes' => '17:30'],
            ],
            'sameAs'       => $biz['same_as'],
        ],
        [
            '@type'      => 'WebSite',
            '@id'        => $website_id,
            'url'        => home_url('/'),
            'name'       => $biz['name'],
            'inLanguage' => $lang_tag,
            'publisher'  => ['@id' => $org_id],
        ],
    ];

    $page_node = [
        '@type'      => in_array($type, ['reference', 'apartment_list', 'project_detail', 'blog_list'], true) ? 'CollectionPage' : 'WebPage',
        '@id'        => $url . '#webpage',
        'url'        => $url,
        'name'       => $title,
        'inLanguage' => $lang_tag,
        'isPartOf'   => ['@id' => $website_id],
        'about'      => ['@id' => $org_id],
    ];
    if ($description !== '') {
        $page_node['description'] = pplus_seo_trim($description, 300);
    }
    if ($type !== 'home') {
        $graph[] = $page_node;
    } else {
        $page_node['@type'] = 'WebPage';
        $graph[] = $page_node;
    }

    if ($detail) {
        $photos = array_slice(array_values(array_filter(array_map(
            static fn($p) => pplus_seo_img(pplus_admin_asset_url((string) $p)),
            $detail['photoList'] ?? []
        ))), 0, 8);
        $apt = [
            '@type'       => ['Apartment', 'Product'],
            '@id'         => $url . '#apartment',
            'name'        => $detail['title'] ?? $title,
            'description' => pplus_seo_trim($description, 300),
            'url'         => $url,
            'mainEntityOfPage' => ['@id' => $url . '#webpage'],
            'brand'       => ['@id' => $org_id],
            'address'     => array_filter([
                '@type'           => 'PostalAddress',
                'streetAddress'   => $detail['address'] ?? '',
                'addressLocality' => $detail['districtName'] ?? '',
                'addressRegion'   => $detail['provinceName'] ?? '',
                'addressCountry'  => 'VN',
            ]),
        ];
        if ($photos) {
            $apt['image'] = $photos;
        }
        if (!empty($detail['lat']) && !empty($detail['lng'])) {
            $apt['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => (float) $detail['lat'], 'longitude' => (float) $detail['lng']];
        }
        if (!empty($detail['bedRoomQty'])) {
            $apt['numberOfBedrooms'] = (int) $detail['bedRoomQty'];
        }
        if (!empty($detail['bathRoomQty'])) {
            $apt['numberOfBathroomsTotal'] = (int) $detail['bathRoomQty'];
        }
        if (!empty($detail['area'])) {
            $apt['floorSize'] = ['@type' => 'QuantitativeValue', 'value' => (float) $detail['area'], 'unitCode' => 'MTK'];
        }
        if (!empty($detail['price'])) {
            $apt['offers'] = [
                '@type'         => 'Offer',
                'price'         => (float) $detail['price'],
                'priceCurrency' => 'USD',
                'priceSpecification' => ['@type' => 'UnitPriceSpecification', 'price' => (float) $detail['price'], 'priceCurrency' => 'USD', 'unitCode' => 'MON'],
                'availability'  => 'https://schema.org/InStock',
                'url'           => $url,
                'seller'        => ['@id' => $org_id],
            ];
        }
        $graph[] = $apt;
    }

    if ($type === 'blog_detail') {
        $blog = pplus_get_blog_detail_from_query_vars();
        $bd   = $blog['result']['data'] ?? null;
        if ($bd) {
            $author = $bd['dataModel'] ?? null;
            $post = [
                '@type'            => 'BlogPosting',
                '@id'              => $url . '#article',
                'headline'         => pplus_seo_trim((string) ($bd['title'] ?? $title), 110),
                'description'      => pplus_seo_trim((string) ($bd['metaDescription'] ?? $bd['subTitle'] ?? $description), 300),
                'mainEntityOfPage' => ['@id' => $url . '#webpage'],
                'image'            => [$image],
                'inLanguage'       => $lang_tag,
                'publisher'        => ['@id' => $org_id],
                'author'           => !empty($author['name'])
                    ? ['@type' => 'Person', 'name' => (string) $author['name']]
                    : ['@id' => $org_id],
            ];
            $ts_mod = !empty($bd['dateUpdate']) ? strtotime((string) $bd['dateUpdate']) : false;
            $ts_pub = !empty($bd['dateCreate']) ? strtotime((string) $bd['dateCreate']) : $ts_mod;
            if ($ts_pub) {
                $post['datePublished'] = gmdate('c', $ts_pub);
            }
            if ($ts_mod) {
                $post['dateModified'] = gmdate('c', $ts_mod);
            }
            $graph[] = $post;
        }
    }

    printf(
        '<script type="application/ld+json">%s</script>' . "\n",
        wp_json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );
}, PHP_INT_MAX);

// --- BreadcrumbList JSON-LD: doc lai chinh breadcrumb da render (".url-content-list") -------------
add_action('template_redirect', function () {
    if (is_feed() || is_robots() || is_embed() || wp_doing_ajax() || get_query_var('sitemap') || get_query_var('sitemap-stylesheet')) {
        return;
    }
    ob_start(function (string $html): string {
        $pos = stripos($html, '</head>');
        if ($pos === false) {
            return $html;
        }
        // Anh do bien tap vien chen qua CMS (noi dung tinh/quan/blog) hay thieu alt -> tu dien tu ten file
        // (bo duoi, doi - _ thanh khoang trang). Chi dung khi THIEU han thuoc tinh alt (alt="" co chu y = anh trang tri, giu nguyen).
        $html = preg_replace_callback('#<img\b(?![^>]*\balt\s*=)([^>]*?)(/?)>#i', static function (array $m): string {
            if (!preg_match('#\bsrc\s*=\s*["\']([^"\']+)["\']#i', $m[1], $s) || preg_match('#[\'+{}$]#', $s[1])) {
                return $m[0]; // khong co src, hoac la chuoi mau JS (vd "'+url+'") -> khong dong vao
            }
            $file = pathinfo(rawurldecode((string) parse_url($s[1], PHP_URL_PATH)), PATHINFO_FILENAME);
            $alt  = trim(preg_replace('/[\s_\-]+/u', ' ', $file));
            return $alt === '' ? $m[0] : '<img' . $m[1] . ' alt="' . esc_attr($alt) . '"' . $m[2] . '>';
        }, $html) ?? $html;
        $pos = stripos($html, '</head>');
        if ($pos === false || !preg_match('#<div class="url-content-list">(.*?)</div>#s', $html, $blk)) {
            return $html;
        }
        if (!preg_match_all('#<(a|span)\b([^>]*)>(.*?)</\1>#s', $blk[1], $items, PREG_SET_ORDER)) {
            return $html;
        }
        $list = [];
        $pos_n = 0;
        foreach ($items as $it) {
            $name = trim(str_replace("\xC2\xA0", ' ', html_entity_decode(strip_tags($it[3]), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
            $name = trim(preg_replace('/\s+/u', ' ', $name));
            if ($name === '') {
                continue;
            }
            $pos_n++;
            $node = ['@type' => 'ListItem', 'position' => $pos_n, 'name' => $name];
            if ($it[1] === 'a' && preg_match('#href="([^"]+)"#', $it[2], $h)) {
                $href = html_entity_decode($h[1], ENT_QUOTES, 'UTF-8');
                $node['item'] = (strpos($href, 'http') === 0) ? $href : home_url($href);
            } else {
                $node['item'] = pplus_seo_canonical_url();
            }
            $list[] = $node;
        }
        if (count($list) < 2) {
            return $html;
        }
        $script = '<script type="application/ld+json">' . wp_json_encode(
            ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $list],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ) . "</script>\n";
        return substr($html, 0, $pos) . $script . substr($html, $pos);
    });
}, 0);
