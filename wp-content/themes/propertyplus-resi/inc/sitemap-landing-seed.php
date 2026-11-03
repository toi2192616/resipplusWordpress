<?php
/**
 * Seed trang landing cho sitemap (3/11/2026) - cac URL landing/khu vuc co trong sitemap WEB CHINH (propertyplus.com.vn, tap "quan" + "type")
 * nhung KHONG co link tren trang chu/footer nen cron "areas" (harvest link noi bo - inc/sitemap.php) khong thay.
 *
 * Backend KHONG co API liet ke het alias landing, nen day la danh sach DA KIEM TRA tren WP luc 3/11/2026: moi URL tra 200, index,
 * canonical tu tro. Da loai: trang biet thu (410 - Resi bo biet thu), alias WP tra 404, va ~37 alias "tieu de bai blog" (web chinh dung tieu de blog lam alias landing; WP tra 200 nhung la danh sach 36 can ho chung = noi dung mong/trung lap).
 * Cap nhat: lay lai sitemap_quan.xml/sitemap_type.xml cua web chinh, loc theo cach tren, hoac them tay alias moi.
 * Dung o inc/sitemap.php -> pplus_sitemap_urls('areas'). Alias sau nay bi xoa chi con la 1 dong thua trong sitemap, khong anh huong trang.
 */

if (!defined('ABSPATH')) {
    exit;
}

return [
    // EN
    '/en/rental-apartment/ha-noi/03-bedrooms-apartment-for-rent-in-r2-royal-city-ready-to-move-in',
    '/en/rental-apartment/ha-noi/Apartment-for-rent-in-D-Le-Roi-Soleil-Hanoi',
    '/en/rental-apartment/ha-noi/Apartment-for-rent-in-Vinhomes-Nguyen-Chi-Thanh',
    '/en/rental-apartment/ha-noi/Get-an-overview-of-Elegant-Suites-Westlake-serviced-apartments',
    '/en/rental-apartment/ha-noi/Get-an-overview-of-apartments-for-rent-in-Sunshine-City',
    '/en/rental-apartment/ha-noi/Japanese-area',
    '/en/rental-apartment/ha-noi/Overview-of-Hanoi-Lake-View',
    '/en/rental-apartment/ha-noi/Overview-of-The-Hanoi-Club-hotel-and-serviced-apartment',
    '/en/rental-apartment/ha-noi/Overview-of-apartment-for-rent-in-The-Five-Residences-Hanoi',
    '/en/rental-apartment/ha-noi/Overview-of-apartments-for-rent-at-Pentstudio-West-Lake-Hano',
    '/en/rental-apartment/ha-noi/Overview-of-apartments-for-rent-in-Discovery-Complex',
    '/en/rental-apartment/ha-noi/Overview-of-apartments-for-rent-in-Hong-Kong-Tower',
    '/en/rental-apartment/ha-noi/Overview-of-apartments-for-rent-in-Vinhomes-Skylake',
    '/en/rental-apartment/ha-noi/Top-apartments-for-rent-in-Thanh-Xuan',
    '/en/rental-apartment/ha-noi/Vinhomes-Riverside--luxury-villas-and-apartments-in-Long-Bien',
    '/en/rental-apartment/ha-noi/apartment-for-rent-in-grandeur-palace-138b-giang-vo-ba-dinh',
    '/en/rental-apartment/ha-noi/apartment-for-rent-in-roygent-parks-hanoi-289-khuat-duy-tien-cau-giay',
    '/en/rental-apartment/ha-noi/ciputra-area',
    '/en/rental-apartment/ha-noi/dao-tan-linh-lang-kim-ma-area',
    '/en/rental-apartment/ha-noi/luxury-apartment-for-rent-in-ha-noi',
    '/en/rental-apartment/ha-noi/times-city-park-hill-area',
    // JA
    '/ja/rental-apartment/ha-noi/-138b',
    '/ja/rental-apartment/ha-noi/-289-',
    '/ja/rental-apartment/ha-noi/-サンシャインシティの賃貸アパートの概',
    '/ja/rental-apartment/ha-noi/Japanese-area',
    '/ja/rental-apartment/ha-noi/Vinhomes-Nguyen-Chi-Thanhハノイについて',
    '/ja/rental-apartment/ha-noi/VinhomesSkylakeの賃貸アパートの概',
    '/ja/rental-apartment/ha-noi/ciputra-area',
    '/ja/rental-apartment/ha-noi/dao-tan-linh-lang-kim-ma-area',
    '/ja/rental-apartment/ha-noi/r2--3-',
    '/ja/rental-apartment/ha-noi/times-city-park-hill-area',
    '/ja/rental-apartment/ha-noi/エレガントスイーツウェストレイクサービスアパートの概要',
    '/ja/rental-apartment/ha-noi/タンスアンの賃貸マンションのトップ',
    '/ja/rental-apartment/ha-noi/ディスカバリーコンプレックス賃貸アパート-',
    '/ja/rental-apartment/ha-noi/デルロワソレイユハノイの賃貸アパート',
    '/ja/rental-apartment/ha-noi/ハノイの高級アパートの賃貸',
    '/ja/rental-apartment/ha-noi/ハノイクラブホテルとマンションの概要',
    '/ja/rental-apartment/ha-noi/ハノイレックビューのオバービュー',
    '/ja/rental-apartment/ha-noi/ビンホームズリバーサイド-–-ロンビエンにある高級マンションと別荘',
    '/ja/rental-apartment/ha-noi/ファイブレジデンスハノイの賃貸アパートのオバービュー',
    '/ja/rental-apartment/ha-noi/ペンとスタジオウェストレイクハノイの概要',
    '/ja/rental-apartment/ha-noi/ホンコンタワーの賃貸アパートの概要',
];
