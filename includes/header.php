<?php
require_once __DIR__ . '/functions.php';
security_headers();
$pageTitle = $pageTitle ?? SITE_NAME;
$metaDescription = $metaDescription ?? 'Stories celebrating the entrepreneurs, creators, and local businesses shaping Central Florida.';
$canonicalPath = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$canonicalUrl = url($canonicalPath);
$shareImage = $shareImage ?? url('assets/images/market.svg');
$robots = $robots ?? 'index,follow,max-image-preview:large';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#293d38">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($metaDescription) ?>">
<meta name="robots" content="<?= e($robots) ?>">
<link rel="canonical" href="<?= e($canonicalUrl) ?>">
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= e(SITE_NAME) ?>">
<meta property="og:title" content="<?= e($pageTitle) ?>">
<meta property="og:description" content="<?= e($metaDescription) ?>">
<meta property="og:url" content="<?= e($canonicalUrl) ?>">
<meta property="og:image" content="<?= e($shareImage) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($pageTitle) ?>">
<meta name="twitter:description" content="<?= e($metaDescription) ?>">
<meta name="twitter:image" content="<?= e($shareImage) ?>">
<link rel="stylesheet" href="<?= url('assets/css/site.css') ?>">
<script type="application/ld+json"><?= json_encode(['@context'=>'https://schema.org','@type'=>'Organization','name'=>SITE_NAME,'url'=>SITE_URL,'email'=>ADMIN_EMAIL,'areaServed'=>['@type'=>'AdministrativeArea','name'=>'Central Florida']], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>
<header class="site-header"><a class="brand" href="<?= url() ?>"><span>Local Legends</span><strong>Orlando</strong></a><button class="nav-toggle" aria-expanded="false" aria-controls="site-nav">Menu</button><nav id="site-nav" class="site-nav" aria-label="Primary"><a href="<?= url('stories/') ?>">Stories</a><a href="<?= url('search/') ?>">Search</a><a href="<?= url('categories/') ?>">Categories</a><a href="<?= url('about/') ?>">About</a><a href="<?= url('contact/') ?>">Contact</a><a class="button button-small" href="<?= url('get-featured/') ?>">Get Featured</a></nav></header>
<main id="main">
