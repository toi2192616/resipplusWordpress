<?php
/**
 * Gop CSS/JS cua theme thanh it file hon - TU DONG luc in trang, KHONG can buoc build tay.
 *
 * 23/10/2026 (port y tuong tu ban Van phong 2075641/40ee63a "gop 12 CSS vendor thanh 1", "gop 8 JS thanh 2"):
 * trang chu Resi tai 18 file CSS (deu chan hien thi - Lighthouse mobile: "render-blocking" 18 muc) + 12 file
 * JS rieng le. Ban Van phong gop bang lenh build tay (cat + clean-css/terser) va commit file bundle - moi lan
 * sua 1 file nguon ma quen build lai la bundle cu van chay (da gap that: 678cc47 "build lai bundle sau khi
 * cherry-pick"). O day bundle duoc SINH LAI TU DONG khi bat ky file nguon nao doi (khoa = duong dan + mtime +
 * kich thuoc tung file + noi dung inline CSS), nen sua .css/.js nhu binh thuong la du.
 *
 * Cach lam: hook "print_styles_array"/"print_scripts_array" (danh sach handle WordPress sap in, DA dung thu tu
 * phu thuoc) -> cac handle LIEN KE cung 1 nhom trong pplus_bundle_groups() duoc noi lai thanh 1 file trong
 * uploads/pplus-bundle/, thay bang 1 handle duy nhat. Thu tu cascade/thuc thi giu NGUYEN (chi noi cac file dung
 * canh nhau theo dung thu tu WordPress da sap). Handle khong thuoc nhom nao (CSS/JS rieng tung trang: select2,
 * fotorama, home, chi tiet...) giu nguyen file rieng - nhom co dinh nen bundle "vendor"/"theme" GIONG HET nhau
 * tren moi trang, trinh duyet cache dung chung khi chuyen trang.
 *
 * CSS: url() tuong doi duoc doi sang duong dan tuyet doi (file goc nam o nhieu thu muc khac nhau), bo BOM +
 * @charset (giua file la sai cu phap), bo comment (giu /*! license *\/) + khoang trang thua. Inline CSS gan
 * voi handle (vd @font-face Font Awesome cat gon) duoc dua vao bundle DUNG vi tri sau file cua no.
 * JS: chi noi file (khong minify lai), bo dong "//# sourceMappingURL" (duong dan .map tuong doi se sai), chen
 * ";\n" giua cac file. Inline "before"/localize cua cac handle trong nhom (deu chi la "var X = {...}") in TRUOC
 * bundle; "after" in SAU bundle.
 *
 * An toan: bat ky loi nao (uploads khong ghi duoc, file nguon mat...) -> giu nguyen danh sach handle goc (cac file
 * rieng le nhu truoc). Tat han: define('PPLUS_ASSET_BUNDLE', false) trong wp-config.php.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle -> ten nhom. Chi handle LIEN KE trong cung 1 nhom moi duoc gop.
 * "pplus-ob-css-{i}"/"pplus-ob-js-{i}": {i} la vi tri trong pplus_officebase_styles()/pplus_officebase_scripts().
 *
 * @return array{css: array<string,string>, js: array<string,string>}
 */
function pplus_bundle_groups(): array {
    $css = [];
    foreach (pplus_officebase_styles() as $i => $path) {
        if (strpos($path, 'select2') !== false || strpos($path, 'fotorama') !== false) {
            continue; // chi 1 so trang dung - giu file rieng de bundle vendor giong nhau tren moi trang
        }
        $css["pplus-ob-css-{$i}"] = strpos($path, 'metronic/') === 0 || strpos($path, 'style/') === 0 || strpos($path, 'Common/') === 0
            ? 'theme' : 'vendor';
    }
    $css['pplus-header-component'] = 'theme';
    $css['pplus-footer-component'] = 'theme';

    $js = ['jquery' => 'vendor', 'pplus-bootstrap' => 'vendor', 'pplus-toastr' => 'vendor'];
    foreach (pplus_officebase_scripts() as $i => $path) {
        if (strpos($path, 'select2') !== false || strpos($path, 'fotorama') !== false) {
            continue;
        }
        $js["pplus-ob-js-{$i}"] = strpos($path, 'Common/') === 0 ? 'theme' : 'vendor';
    }
    foreach (['pplus-resi-ui', 'propertyplus-main', 'pplus-search-suggest', 'pplus-search-modal', 'pplus-search-mobile', 'pplus-favourite'] as $handle) {
        $js[$handle] = 'theme';
    }
    return ['css' => $css, 'js' => $js];
}

function pplus_bundle_enabled(): bool {
    if (defined('PPLUS_ASSET_BUNDLE') && !PPLUS_ASSET_BUNDLE) {
        return false;
    }
    return !is_admin() && !(defined('SCRIPT_DEBUG') && SCRIPT_DEBUG);
}

/** URL cua 1 asset (src WordPress) -> duong dan file tren dia, chi khi file nam trong thu muc theme. */
function pplus_bundle_local_path(string $src): ?string {
    $src  = preg_replace('/[?#].*$/', '', $src);
    $base = PPLUS_THEME_URI . '/';
    if (strpos($src, $base) !== 0) {
        return null;
    }
    $path = PPLUS_THEME_DIR . '/' . rawurldecode(substr($src, strlen($base)));
    return is_file($path) ? $path : null;
}

/** Thu muc + URL luu bundle (uploads/pplus-bundle), null neu khong dung duoc. */
function pplus_bundle_dir(): ?array {
    $uploads = wp_upload_dir(null, false);
    if (!empty($uploads['error'])) {
        return null;
    }
    $dir = $uploads['basedir'] . '/pplus-bundle';
    if (!is_dir($dir) && !wp_mkdir_p($dir)) {
        return null;
    }
    return ['dir' => $dir, 'url' => set_url_scheme($uploads['baseurl'] . '/pplus-bundle')];
}

/**
 * Ghi file bundle (tmp + rename de request song song khong doc phai file dang ghi do). Moi lan sinh bundle MOI cho 1 nhom,
 * xoa ban cu cung nhom da qua 7 ngay - KHONG xoa ngay vi HTML cu (cache trang/trinh duyet) van co the tro toi ban cu.
 */
function pplus_bundle_write(string $file, string $content): bool {
    if (is_file($file)) {
        return true;
    }
    $tmp = $file . '.' . uniqid('', true) . '.tmp';
    if (@file_put_contents($tmp, $content) === false) {
        return false;
    }
    $ok = @rename($tmp, $file) || is_file($file);
    @unlink($tmp);
    if ($ok && preg_match('/^(.+)-[0-9a-f]{12}\.(css|js)$/', basename($file), $m)) {
        foreach ((array) glob(dirname($file) . '/' . $m[1] . '-*.' . $m[2]) as $old) {
            if ($old !== $file && is_file($old) && filemtime($old) < time() - 7 * DAY_IN_SECONDS) {
                @unlink($old);
            }
        }
    }
    return $ok;
}

/** Doi url() tuong doi trong 1 file CSS sang duong dan tuyet doi (theo thu muc cua chinh file do). */
function pplus_bundle_rebase_css(string $css, string $src): string {
    $dir_url = (string) wp_parse_url(preg_replace('/[?#].*$/', '', $src), PHP_URL_PATH);
    $dir_url = rtrim(dirname($dir_url), '/') . '/';
    return (string) preg_replace_callback(
        '~url\(\s*(["\']?)([^"\')]+)\1\s*\)~i',
        static function (array $m) use ($dir_url): string {
            $u = trim($m[2]);
            if ($u === '' || preg_match('~^(data:|[a-z][a-z0-9+.-]*:|//|/|#)~i', $u)) {
                return $m[0];
            }
            return 'url(' . $m[1] . $dir_url . $u . $m[1] . ')';
        },
        $css
    );
}

/** Minify CSS an toan: bo comment (tru /*! *\/), gom khoang trang - KHONG dung vao chuoi trong "..." / '...'. */
function pplus_bundle_minify_css(string $css): string {
    $css = (string) preg_replace_callback(
        '~("(?:[^"\\\\]|\\\\.)*"|\'(?:[^\'\\\\]|\\\\.)*\')|(/\*(?!!).*?\*/)|(\s+)~s',
        static function (array $m): string {
            if ($m[1] !== '') {
                return $m[1];
            }
            return isset($m[3]) && $m[3] !== '' ? ' ' : '';
        },
        $css
    );
    // Bo khoang trang quanh { } ; , (an toan voi selector - KHONG dong vao ":" vi "a :hover" khac "a:hover").
    $css = (string) preg_replace_callback(
        '~("(?:[^"\\\\]|\\\\.)*"|\'(?:[^\'\\\\]|\\\\.)*\')|\s*([{};,])\s*~s',
        static fn(array $m): string => $m[1] !== '' ? $m[1] : $m[2],
        $css
    );
    return trim($css);
}

/** Noi dung 1 file CSS da xu ly de dua vao bundle. */
function pplus_bundle_css_piece(string $path, string $src): string {
    $css = (string) file_get_contents($path);
    $css = preg_replace('/^\xEF\xBB\xBF/', '', $css);
    $css = (string) preg_replace('/@charset\s+["\'][^"\']*["\']\s*;/i', '', $css);
    $css = (string) preg_replace('~/\*#\s*sourceMappingURL=[^*]*\*/~', '', $css);
    return pplus_bundle_minify_css(pplus_bundle_rebase_css($css, $src));
}

/**
 * Chia $to_do thanh cac doan lien ke: doan co "group" = cac handle lien ke cung nhom (gop duoc), doan
 * group null = 1 handle giu nguyen.
 *
 * @param array<string,string> $groups
 * @return array<int, array{group:string, handles:string[]}>
 */
function pplus_bundle_runs(WP_Dependencies $deps, array $to_do, array $groups, callable $eligible): array {
    $runs = [];
    $cur  = null;
    foreach ($to_do as $handle) {
        $group = $groups[$handle] ?? null;
        $ok    = $group !== null && !in_array($handle, $deps->done, true) && $eligible($handle);
        if ($ok && $cur !== null && $cur['group'] === $group) {
            $cur['handles'][] = $handle;
            continue;
        }
        if ($cur !== null) {
            $runs[] = $cur;
        }
        $cur = $ok ? ['group' => $group, 'handles' => [$handle]] : ['group' => null, 'handles' => [$handle]];
    }
    if ($cur !== null) {
        $runs[] = $cur;
    }
    return $runs;
}

add_filter('print_styles_array', static function (array $to_do): array {
    if (!pplus_bundle_enabled()) {
        return $to_do;
    }
    $styles = wp_styles();
    $groups = pplus_bundle_groups()['css'];
    $runs   = pplus_bundle_runs($styles, $to_do, $groups, static function (string $handle) use ($styles): bool {
        $dep = $styles->registered[$handle] ?? null;
        return $dep && is_string($dep->src) && in_array($dep->args ?: 'all', ['all', 'screen'], true)
            && empty($dep->extra['conditional']) && empty($dep->extra['alt']) && empty($dep->extra['title'])
            && pplus_bundle_local_path($dep->src) !== null;
    });

    $out = [];
    foreach ($runs as $run) {
        if ($run['group'] === null || count($run['handles']) < 2) {
            array_push($out, ...$run['handles']);
            continue;
        }
        $bundle = pplus_bundle_build_css($run['group'], $run['handles']);
        if ($bundle === null) {
            array_push($out, ...$run['handles']);
            continue;
        }
        $out[] = $bundle;
    }
    return $out;
});

/** Tao (neu chua co) bundle CSS cho 1 run, dang ky handle moi, danh dau cac handle goc da in. */
function pplus_bundle_build_css(string $group, array $handles): ?string {
    $styles = wp_styles();
    $loc    = pplus_bundle_dir();
    if ($loc === null) {
        return null;
    }
    $sig = 'css-v1';
    foreach ($handles as $handle) {
        $dep   = $styles->registered[$handle];
        $path  = (string) pplus_bundle_local_path($dep->src);
        $sig  .= '|' . $path . '|' . filemtime($path) . '|' . filesize($path) . '|' . implode("\n", (array) $styles->get_data($handle, 'after'));
    }
    $name   = $group . '-' . substr(md5($sig), 0, 12) . '.css';
    $file   = $loc['dir'] . '/' . $name;
    if (!is_file($file)) {
        $css = '';
        foreach ($handles as $handle) {
            $dep  = $styles->registered[$handle];
            $css .= '/* ' . $handle . " */\n" . pplus_bundle_css_piece((string) pplus_bundle_local_path($dep->src), $dep->src) . "\n";
            $after = (array) $styles->get_data($handle, 'after');
            if ($after) {
                $css .= pplus_bundle_minify_css(implode("\n", $after)) . "\n";
            }
        }
        if (!pplus_bundle_write($file, $css)) {
            return null;
        }
    }

    $bundle_handle = 'pplus-bundle-css-' . $group;
    $styles->add($bundle_handle, $loc['url'] . '/' . $name, [], null);
    foreach ($handles as $handle) {
        $styles->done[] = $handle; // da nam trong bundle - khong in lai (ke ca inline "after" cua no)
    }
    return $bundle_handle;
}

add_filter('print_scripts_array', static function (array $to_do): array {
    if (!pplus_bundle_enabled()) {
        return $to_do;
    }
    $scripts = wp_scripts();
    $groups  = pplus_bundle_groups()['js'];
    $runs    = pplus_bundle_runs($scripts, $to_do, $groups, static function (string $handle) use ($scripts): bool {
        $dep = $scripts->registered[$handle] ?? null;
        return $dep && is_string($dep->src) && pplus_bundle_local_path($dep->src) !== null
            && empty($dep->extra['strategy']) && empty($dep->extra['conditional']) && empty($dep->textdomain);
    });

    // Luot in hien tai: WordPress goi bo loc nay o CA luot <head> (do_head_items) LAN luot footer (do_footer_items).
    // Luot footer tinh lai $to_do tu HANG DOI GOC (do_items(false, 1)), khong dung lai danh sach da loc o luot head -
    // nen chi duoc gop dung trong luot ma cac script do THUC SU in (gop o luot head, handle bundle khong nam trong
    // hang doi -> luot footer khong bao gio in no, mat toan bo JS - da gap khi thu).
    $current_pass = doing_action('wp_print_footer_scripts') ? 1 : 0;

    $out = [];
    foreach ($runs as $run) {
        // Run phai cung 1 group in (head/footer) va dung luot dang in - khac thi khong gop.
        $print_groups = array_unique(array_map(static fn($h) => (int) ($scripts->groups[$h] ?? 0), $run['handles']));
        if ($run['group'] === null || count($run['handles']) < 2 || count($print_groups) !== 1 || (int) reset($print_groups) !== $current_pass) {
            array_push($out, ...$run['handles']);
            continue;
        }
        $bundle = pplus_bundle_build_js($run['group'], $run['handles'], (int) reset($print_groups));
        if ($bundle === null) {
            array_push($out, ...$run['handles']);
            continue;
        }
        $out[] = $bundle;
    }
    return $out;
});

/** Tao (neu chua co) bundle JS cho 1 run, chuyen inline before/localize/after sang handle bundle. */
function pplus_bundle_build_js(string $group, array $handles, int $print_group): ?string {
    $scripts = wp_scripts();
    $loc     = pplus_bundle_dir();
    if ($loc === null) {
        return null;
    }
    $sig = 'js-v1';
    foreach ($handles as $handle) {
        $path = (string) pplus_bundle_local_path($scripts->registered[$handle]->src);
        $sig .= '|' . $path . '|' . filemtime($path) . '|' . filesize($path);
    }
    $name = $group . '-' . substr(md5($sig), 0, 12) . '.js';
    $file = $loc['dir'] . '/' . $name;
    if (!is_file($file)) {
        $js = '';
        foreach ($handles as $handle) {
            $code = (string) file_get_contents((string) pplus_bundle_local_path($scripts->registered[$handle]->src));
            $code = preg_replace('/^\xEF\xBB\xBF/', '', $code);
            $code = (string) preg_replace('~^\s*//[#@]\s*sourceMappingURL=.*$~m', '', $code);
            $js  .= '/* ' . $handle . " */\n" . rtrim($code) . "\n;\n";
        }
        if (!pplus_bundle_write($file, $js)) {
            return null;
        }
    }

    $bundle_handle = 'pplus-bundle-js-' . $group;
    // Handle moi them SAU all_deps() nen phai tu dat group in (0 = head, 1 = footer) - thieu la in nham len <head>.
    $scripts->add($bundle_handle, $loc['url'] . '/' . $name, [], null, $print_group ?: null);
    $scripts->add_data($bundle_handle, 'group', $print_group);
    $scripts->groups[$bundle_handle] = $print_group;
    $data = [];
    $before = [];
    $after = [];
    foreach ($handles as $handle) {
        if ($d = $scripts->get_data($handle, 'data')) {
            $data[] = $d;
        }
        $before = array_merge($before, array_filter((array) $scripts->get_data($handle, 'before')));
        $after  = array_merge($after, array_filter((array) $scripts->get_data($handle, 'after')));
        $scripts->done[] = $handle;
    }
    if ($data) {
        $scripts->add_data($bundle_handle, 'data', implode("\n", $data));
    }
    if ($before) {
        $scripts->add_data($bundle_handle, 'before', $before);
    }
    if ($after) {
        $scripts->add_data($bundle_handle, 'after', $after);
    }
    return $bundle_handle;
}
