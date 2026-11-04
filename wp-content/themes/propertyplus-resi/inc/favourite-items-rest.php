<?php
/**
 * REST cho TRANG CHI TIET DANH SACH YEU THICH (page-favourite-detail.php / assets/js/pplus-favourite-detail.js) - 4/11/2026.
 *
 * GET pplus/v1/favourite-items?ids=1,2&projects=3&lang=vi|en|ja -> the can ho + du an DA dung san (HTML) va toa do cho ban do.
 * Dung dung endpoint that cua Angular (favourite-detail.component.ts):
 *   POST {lang}/Apartment/_GetApartmentInputDto?isdislike=1&inputstring={ids}   -> result.listApartments
 *   POST {lang}/Project/_GetListProjectByIds?inputstring={ids}                  -> result.listProjects
 * (2 endpoint "offices-by-ids"/"office-markers-by-ids" trong inc/favourite-rest.php la ban Office cu va KHONG dung o day.) Toa do lat/lng
 * nam san trong tung item nen khong can goi them *MarkerByInputDto.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', function () {
    register_rest_route('pplus/v1', '/favourite-items', [
        'methods'             => 'GET',
        'callback'            => 'pplus_rest_favourite_items',
        'permission_callback' => '__return_true',
        'args'                => [
            'ids'      => ['default' => ''],
            'projects' => ['default' => ''],
            'lang'     => ['default' => 'vi'],
        ],
    ]);
});

function pplus_favourite_api_list(string $lang, string $path, string $query): array {
    $url  = pplus_api_base_url() . '/' . $lang . '/' . $path . '?' . $query;
    $data = pplus_api_cached('POST', $url, null, 120, 15);
    return is_array($data) ? (array) ($data['result'] ?? []) : [];
}

/** The 1 can ho / du an cho trang chi tiet (nut tim do o goc phai de bo yeu thich). */
function pplus_favourite_card_html(array $it, string $kind, string $lang): string {
    $is_project = $kind === 'project';
    $id    = (int) ($is_project ? ($it['projectId'] ?? 0) : ($it['apartmentId'] ?? 0));
    $title = trim((string) ($is_project ? ($it['projectName'] ?? '') : ($it['title'] ?? '')));
    $href  = home_url('/' . $lang . '/' . ltrim((string) ($it['urlHerf'] ?? ''), '/'));
    $photo = '';
    foreach ((array) ($it['photoList'] ?? []) as $p) {
        if (is_string($p) && $p !== '') {
            $photo = pplus_admin_asset_url($p);
            break;
        }
    }
    $img   = $photo !== '' ? pplus_webp_url($photo, 400) : PPLUS_THEME_URI . '/assets/officebase/Common/Images/Rectangle 2936.png';
    $price = trim(ltrim(trim((string) ($it['priceString'] ?? '')), '- '));
    $area  = trim((string) ($it['areaString'] ?? ''));
    $beds  = trim((string) ($it['bedRoomQtyString'] ?? ''));
    $baths = trim((string) ($it['bathRoomQtyString'] ?? ''));
    $type  = '';
    if ($is_project) {
        $rt   = (int) ($it['projectResiType'] ?? 0);
        $type = $rt === 1 ? pplus_localize('Căn hộ dịch vụ') : ($rt === 2 ? pplus_localize('Chung cư') : '');
    }
    // Chuoi du lieu chi gom "-" / " - " (API tra khi khong co so lieu) coi nhu trong.
    $has = static fn(string $v): bool => trim($v, " -\t") !== '';

    $row = static function (string $label, string $value_html): string {
        return $value_html === '' ? '' : '<span class="fd-row"><span class="fd-row-k">' . esc_html($label) . '</span><span class="fd-row-v">' . $value_html . '</span></span>';
    };

    ob_start();
    ?>
    <a class="fd-card" href="<?php echo esc_url($href); ?>" data-kind="<?php echo esc_attr($kind); ?>" data-id="<?php echo (int) $id; ?>">
        <span class="fd-photo"><img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($title); ?>" width="400" height="300" loading="lazy" /></span>
        <span class="fd-remove" role="button" tabindex="0" data-fd-remove="<?php echo esc_attr($kind); ?>" data-id="<?php echo (int) $id; ?>" title="<?php echo esc_attr(pplus_localize('Yêu thích')); ?>"><img src="<?php echo esc_url(PPLUS_THEME_URI . '/assets/Content/Img/fv-red.png'); ?>" alt="" width="23" height="18" /></span>
        <span class="fd-body">
            <?php if ($type !== '') : ?><span class="fd-type"><?php echo esc_html($type); ?></span><?php endif; ?>
            <span class="fd-name"><?php echo esc_html($title); ?></span>
            <?php if ($is_project && !empty($it['address'])) : ?><span class="fd-addr"><?php echo esc_html((string) $it['address']); ?></span><?php endif; ?>
            <?php
            echo $row(pplus_localize('Diện tích'), $has($area) ? esc_html($area) . ' m<sup>2</sup>' : '');
            if (!$is_project) {
                echo $row(pplus_localize('Phòng tắm'), $has($baths) ? esc_html($baths) : '');
            }
            echo $row(pplus_localize('Phòng ngủ'), $has($beds) ? esc_html($beds) . ' ' . esc_html(pplus_localize('phòng')) : '');
            ?>
            <?php if ($price !== '') : ?><span class="fd-price"><b><?php echo esc_html($price); ?>$</b> / <?php echo esc_html(pplus_localize('tháng')); ?></span><?php endif; ?>
        </span>
    </a>
    <?php
    return trim((string) ob_get_clean());
}

function pplus_rest_favourite_items(WP_REST_Request $request) {
    $ids  = pplus_sanitize_id_list($request->get_param('ids'));
    $pids = pplus_sanitize_id_list($request->get_param('projects'));
    $lang = strtolower((string) $request->get_param('lang'));
    $lang = in_array($lang, ['en', 'ja'], true) ? $lang : 'vi';
    // Toi da 100 muc moi loai (chong lam dung)
    $ids  = implode(',', array_slice(explode(',', $ids), 0, 100));
    $pids = implode(',', array_slice(explode(',', $pids), 0, 100));

    // Nhan / ten ngon ngu (pplus_localize) lay tu chinh $lang cua request, khong phai tien to URL (REST khong co tien to).
    $force = static function () use ($lang) {
        return $lang;
    };
    add_filter('pplus_current_lang', $force);

    $apartments_html = $projects_html = '';
    $n_apartments    = $n_projects = 0;
    $markers         = [];

    if ($ids !== '') {
        $list = (array) (pplus_favourite_api_list($lang, 'Apartment/_GetApartmentInputDto', 'isdislike=1&inputstring=' . rawurlencode($ids))['listApartments'] ?? []);
        foreach ($list as $it) {
            $n_apartments++;
            $apartments_html .= pplus_favourite_card_html((array) $it, 'apartment', $lang);
            if (!empty($it['lat']) && !empty($it['lng'])) {
                $markers[] = ['kind' => 'apartment', 'id' => (int) ($it['apartmentId'] ?? 0), 'lat' => (float) $it['lat'], 'lng' => (float) $it['lng'],
                    'title' => (string) ($it['title'] ?? ''), 'price' => trim(ltrim((string) ($it['priceString'] ?? ''), '- ')),
                    'url' => home_url('/' . $lang . '/' . ltrim((string) ($it['urlHerf'] ?? ''), '/'))];
            }
        }
    }
    if ($pids !== '') {
        $list = (array) (pplus_favourite_api_list($lang, 'Project/_GetListProjectByIds', 'inputstring=' . rawurlencode($pids))['listProjects'] ?? []);
        foreach ($list as $it) {
            $n_projects++;
            $projects_html .= pplus_favourite_card_html((array) $it, 'project', $lang);
            if (!empty($it['lat']) && !empty($it['lng'])) {
                $markers[] = ['kind' => 'project', 'id' => (int) ($it['projectId'] ?? 0), 'lat' => (float) $it['lat'], 'lng' => (float) $it['lng'],
                    'title' => (string) ($it['projectName'] ?? ''), 'price' => trim(ltrim((string) ($it['priceString'] ?? ''), '- ')),
                    'url' => home_url('/' . $lang . '/' . ltrim((string) ($it['urlHerf'] ?? ''), '/'))];
            }
        }
    }
    remove_filter('pplus_current_lang', $force);

    $response = new WP_REST_Response([
        'apartmentsHtml' => $apartments_html,
        'projectsHtml'   => $projects_html,
        'apartments'     => $n_apartments,
        'projects'       => $n_projects,
        'markers'        => $markers,
    ], 200);
    $response->header('Cache-Control', 'private, max-age=60');
    return $response;
}
