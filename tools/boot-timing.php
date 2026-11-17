<?php
/**
 * Do tung pha khoi dong cua web Resi tren SERVER THAT, de tim xem 5 giay/request nam o dau.
 *
 * CACH DUNG (Dev A):
 *   1. Chep file nay vao THU MUC GOC WordPress (cung cho voi wp-config.php).
 *   2. Doi gia tri $TOKEN ben duoi thanh 1 chuoi bat ky (file tu choi chay neu de nguyen).
 *   3. Mo:  https://propertyplus.com.vn/boot-timing.php?k=<TOKEN>   (chay 2-3 lan, ghi lai ket qua)
 *   4. XOA file sau khi do xong.
 *
 * File KHONG in mat khau/khoa nao, chi in thoi gian (ms). Chi doc, khong ghi gi vao CSDL.
 */
$TOKEN = 'DOI-CHUOI-NAY';

if (PHP_SAPI !== 'cli' && ($TOKEN === 'DOI-CHUOI-NAY' || !isset($_GET['k']) || !hash_equals($TOKEN, (string) $_GET['k']))) {
    http_response_code(404);
    exit;
}
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');

$t0 = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true);
$ms = static function (float $a, ?float $b = null): string { return str_pad(number_format((($b ?? microtime(true)) - $a) * 1000, 0), 6, ' ', STR_PAD_LEFT) . ' ms'; };
$line = static function (string $label, string $val): void { echo str_pad($label, 52) . $val . "\n"; };

echo 'PHP ' . PHP_VERSION . ' | ' . PHP_SAPI . ' | OPcache: ' . (function_exists('opcache_get_status') && ini_get('opcache.enable') ? 'on' : 'OFF') . ' | memory_limit ' . ini_get('memory_limit') . "\n\n";
$line('0. Tu luc IIS nhan request den khi file nay chay', $ms($t0));

// --- doc thong tin ket noi tu wp-config.php (khong in ra) ---
$cfg = @file_get_contents(__DIR__ . '/wp-config.php');
$get = static function (string $name) use ($cfg): string {
    return $cfg !== false && preg_match("/define\(\s*['\"]" . $name . "['\"]\s*,\s*['\"]([^'\"]*)['\"]/", $cfg, $m) ? $m[1] : '';
};
$host = $get('DB_HOST'); $user = $get('DB_USER'); $pass = $get('DB_PASSWORD'); $name = $get('DB_NAME');
$prefix = ($cfg !== false && preg_match('/\$table_prefix\s*=\s*[\'"]([^\'"]+)[\'"]/', $cfg, $m)) ? $m[1] : 'wp_';
if ($host === '' || $name === '') { echo "Khong doc duoc DB_HOST/DB_NAME tu wp-config.php\n"; exit; }
echo 'DB_HOST = ' . preg_replace('/\d+\.\d+\.\d+\.\d+/', 'x.x.x.x', $host) . ' (ten host, IP da che)' . "\n\n";

// --- 1. phan giai DNS cua host CSDL ---
$h = explode(':', $host)[0];
$t = microtime(true);
$ip = filter_var($h, FILTER_VALIDATE_IP) ? $h : gethostbyname($h);
$line('1. DNS phan giai host CSDL', $ms($t));

// --- 2. ket noi MySQL ---
mysqli_report(MYSQLI_REPORT_OFF);
$port = (int) (strpos($host, ':') !== false ? explode(':', $host)[1] : 3306);
$t = microtime(true);
$db = @mysqli_connect($h, $user, $pass, $name, $port);
$line('2. Ket noi MySQL (connect + auth)', $ms($t) . ($db ? '' : '   LOI: ' . mysqli_connect_error()));
if ($db) {
    $t = microtime(true); $db->query('SELECT 1');
    $line('3. SELECT 1 (khu ung dung <-> MySQL)', $ms($t));
    $t = microtime(true);
    $r = $db->query("SELECT LENGTH(option_value) FROM {$prefix}options WHERE option_name='cron'");
    $cronBytes = $r ? (int) $r->fetch_row()[0] : -1;
    $line('4. Doc dong "cron" trong options (' . number_format($cronBytes / 1024, 0) . ' KB)', $ms($t));
    $t = microtime(true);
    $r = $db->query("SELECT COUNT(*), ROUND(SUM(LENGTH(option_value))/1048576,1) FROM {$prefix}options WHERE autoload IN ('yes','on','auto-on','auto')");
    $a = $r ? $r->fetch_row() : [0, 0];
    $line('5. Tai cac option autoload (' . $a[0] . ' dong, ' . $a[1] . ' MB)', $ms($t));
    $r = $db->query('SELECT @@skip_name_resolve, @@version');
    if ($r) { $v = $r->fetch_row(); echo '   MySQL ' . $v[1] . ' | skip_name_resolve = ' . $v[0] . "  (0 = co the cham 3-5s do tra DNS nguoc moi ket noi)\n"; }
    $db->close();
}

// --- 3. server -> API admin (DNS, TCP, TLS, phan hoi) ---
if (function_exists('curl_init')) {
    $ch = curl_init('https://admin.propertyplus.com.vn/');
    // Chi do thoi gian mang nen khong can kiem tra chung chi (tranh loi thieu CA bundle lam sai so do).
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_NOBODY => true, CURLOPT_TIMEOUT => 15, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => 0]);
    curl_exec($ch);
    $err = curl_errno($ch) ? curl_error($ch) : '';
    $f = static function (int $k) use ($ch): string { return number_format((float) curl_getinfo($ch, $k) * 1000, 0) . ' ms'; };
    echo "\n";
    $line('6. Server -> admin: DNS', $f(CURLINFO_NAMELOOKUP_TIME));
    $line('   Server -> admin: TCP connect (tich luy)', $f(CURLINFO_CONNECT_TIME));
    $line('   Server -> admin: TLS xong (tich luy)', $f(CURLINFO_APPCONNECT_TIME));
    $line('   Server -> admin: byte dau tien (tich luy)', $f(CURLINFO_STARTTRANSFER_TIME) . ($err !== '' ? '   LOI: ' . $err : ''));
    curl_close($ch);
}

// --- 4. nap WordPress (khong render trang) ---
echo "\n";
$t = microtime(true);
define('WP_USE_THEMES', false);
require __DIR__ . '/wp-load.php';
$line('7. Nap WordPress (wp-load.php) DEN HET plugins_loaded', $ms($t));
$line('8. Tong tu luc IIS nhan request', $ms($t0));
$n = isset($GLOBALS['wpdb']) ? (int) $GLOBALS['wpdb']->num_queries : 0;
echo "   So truy van SQL luc khoi dong: $n\n";
echo "\nDoc ket qua: dong nao nhay vot len hang giay la cho chan. Gui nguyen van ket qua cho Dev B.\n";
