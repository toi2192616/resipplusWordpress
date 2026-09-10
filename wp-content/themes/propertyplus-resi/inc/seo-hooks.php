<?php
/**
 * CANH BAO: FILE NAY THUOC KIEN TRUC CU DA BI BO (CPT building/news + webhook sync +
 * "/api/seo/v1/*" tong hop, xem ke hoach goc). Du an da doi huong tu som sang "dung thang API
 * Angular hien co qua rewrite route" (xem inc/office-detail-route.php, inc/reference-index-route.php...
 * - moi trang A1-A5/B1-B6 that su dang chay deu qua co che do, KHONG qua CPT nay).
 *
 * KHONG CO CODE NAO GAN meta "_pplus_entity_id" cho bat ky post nao (da grep xac nhan), nen
 * pplus_current_seo_data() LUON tra ve null trong thuc te - file nay hien khong lam gi ca (khong
 * loi, nhung cung khong co tac dung). Cac ham no goi (pplus_get_building_seo()/pplus_get_news_seo())
 * CUNG KHONG TON TAI o dau khac trong code - neu $entity_id bao gio > 0 that, day se la loi PHP
 * fatal ngay (undefined function). Giu lai file (khong xoa) phong khi sau nay co nguoi quay lai
 * huong CPT that, nhung KHONG duoc coi day la co che SEO dang hoat dong cho site.
 *
 * Override title/meta/canonical/schema cua RankMath bang du lieu tu .NET SEO API,
 * de tranh RankMath tu suy luan sai (du lieu that nam o SQL Server, khong phai trong post content).
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Lay du lieu SEO tuong ung voi post hien tai, cache trong 1 request (khong goi API 2 lan/trang).
 */
function pplus_current_seo_data(): ?array {
    static $cache = [];
    $post_id = get_the_ID();
    if (!$post_id) {
        return null;
    }
    if (array_key_exists($post_id, $cache)) {
        return $cache[$post_id];
    }

    $post_type  = get_post_type($post_id);
    $entity_id  = (int) get_post_meta($post_id, '_pplus_entity_id', true);
    $data       = null;

    if ($entity_id > 0) {
        if (in_array($post_type, ['building', 'project'], true)) {
            $result = pplus_get_building_seo($entity_id);
        } elseif ($post_type === 'news') {
            $result = pplus_get_news_seo($entity_id);
        } else {
            $result = null;
        }
        if (is_array($result)) {
            $data = $result;
        }
    }

    return $cache[$post_id] = $data;
}

if (defined('RANK_MATH_VERSION')) {
    add_filter('rank_math/frontend/title', function ($title) {
        $data = pplus_current_seo_data();
        return !empty($data['seo']['title']) ? $data['seo']['title'] : $title;
    });

    add_filter('rank_math/frontend/description', function ($desc) {
        $data = pplus_current_seo_data();
        return !empty($data['seo']['metaDescription']) ? $data['seo']['metaDescription'] : $desc;
    });

    add_filter('rank_math/frontend/canonical', function ($canonical) {
        $data = pplus_current_seo_data();
        return !empty($data['seo']['canonical']) ? $data['seo']['canonical'] : $canonical;
    });

    add_filter('rank_math/frontend/robots', function ($robots) {
        $data = pplus_current_seo_data();
        if (!empty($data['seo']['noIndex'])) {
            $robots['index'] = 'noindex';
        }
        return $robots;
    });

    // Override / bo sung JSON-LD lay tu .NET (da build san schema, xem SeoBuildingDto.schema).
    add_filter('rank_math/json_ld', function ($data_schema, $jsonld) {
        $data = pplus_current_seo_data();
        if (!empty($data['schema'])) {
            $data_schema['pplusEntity'] = $data['schema'];
        }
        return $data_schema;
    }, 10, 2);
}
