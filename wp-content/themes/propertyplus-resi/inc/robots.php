<?php
/**
 * robots.txt: giu cac Disallow cua web chinh (https://propertyplus.com.vn/robots.txt) + chan wp-admin, tro Sitemap ve /sitemap.xml cua domain chinh.
 * (Chi co tac dung khi KHONG co file robots.txt that o goc site; inc/noindex.php (uu tien 10) van de len khi bat noindex cho server test.)
 *
 * 5/11/2026: tach ra khoi inc/sitemap.php de dung chung cho ca hai nguon sitemap (Admin - mac dinh - va bo sinh cu cua theme).
 */

if (!defined('ABSPATH')) {
    exit;
}

add_filter('robots_txt', function ($output, $public) {
    $lines = [
        'User-agent: *',
        'Disallow: /wp-admin/',
        'Allow: /wp-admin/admin-ajax.php',
        'Disallow: */admin/*',
        'Disallow: *?culture=*',
        'Disallow: *?statusCode=*',
        'Disallow: */Project/_GetProjects',
        'Disallow: */EasyLiving/_GetFacilities',
        'Disallow: */Apartment/CompleteRentApartmentModal',
        'Disallow: */Project/_ProjectSearchBar',
        'Disallow: */Apartment/IssueLeaseApartmentSuccses',
        'Disallow: */trackclick.asp*',
        '',
        'Sitemap: ' . home_url('/sitemap.xml'),
    ];
    return implode("\n", $lines) . "\n";
}, 5, 2);
