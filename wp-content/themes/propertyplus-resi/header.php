<?php if (!defined('ABSPATH')) exit; ?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php // Preload 2 weight SF Pro dung nhieu nhat o man dau (port tu ban Van phong cad3da9/97f1442) - font chi duoc phat hien SAU khi CSS tai+phan tich xong, preload cho tai song song voi CSS. URL PHAI khop dung URL trong sf-pro-display.css (ban -vn: Latin + tieng Viet, ~16KB) va co "crossorigin", neu khong se tai 2 lan. ?>
<link rel="preload" as="font" type="font/woff2" href="<?php echo esc_url(PPLUS_THEME_URI . '/assets/officebase/fonts/sf-pro-display/regular-vn.woff2'); ?>" crossorigin>
<link rel="preload" as="font" type="font/woff2" href="<?php echo esc_url(PPLUS_THEME_URI . '/assets/officebase/fonts/sf-pro-display/medium-vn.woff2'); ?>" crossorigin>
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="pplus-header">
    <?php get_template_part('parts/header'); ?>
</header>
<main class="pplus-main">
