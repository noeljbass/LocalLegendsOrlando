<?php
require_once __DIR__ . '/functions.php';
security_headers();
$pageTitle = $pageTitle ?? SITE_NAME;
$metaDescription = $metaDescription ?? 'Discover the entrepreneurs, creators, and local businesses making Orlando and Central Florida extraordinary.';
$metaKeywords = $metaKeywords ?? 'Orlando local businesses, Central Florida entrepreneurs, Orlando community stories, local business features, Orlando creators';
$canonicalPath = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$canonicalUrl = url($canonicalPath);
$shareImage = url('assets/images/socialbanner.webp');
$headerLogo = url('assets/images/local-legends-long-logo.webp');
$robots = $robots ?? 'index,follow,max-image-preview:large';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#0B2348">
<meta name="application-name" content="<?= e(SITE_NAME) ?>">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($metaDescription) ?>">
<meta name="keywords" content="<?= e($metaKeywords) ?>">
<meta name="robots" content="<?= e($robots) ?>">
<link rel="canonical" href="<?= e($canonicalUrl) ?>">
<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="manifest" href="/site.webmanifest">
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= e(SITE_NAME) ?>">
<meta property="og:title" content="<?= e($pageTitle) ?>">
<meta property="og:description" content="<?= e($metaDescription) ?>">
<meta property="og:url" content="<?= e($canonicalUrl) ?>">
<meta property="og:image" content="<?= e($shareImage) ?>">
<meta property="og:image:alt" content="Local Legends Orlando">
<meta property="og:locale" content="en_US">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($pageTitle) ?>">
<meta name="twitter:description" content="<?= e($metaDescription) ?>">
<meta name="twitter:image" content="<?= e($shareImage) ?>">
<link rel="stylesheet" href="<?= url('assets/css/site.css') ?>">
<script type="application/ld+json"><?= json_encode(['@context'=>'https://schema.org','@type'=>'Organization','name'=>SITE_NAME,'url'=>SITE_URL,'email'=>ADMIN_EMAIL,'areaServed'=>['@type'=>'AdministrativeArea','name'=>'Central Florida']], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>
<header class="site-header"><a class="brand" href="<?= url() ?>" aria-label="Local Legends Orlando home"><img src="<?= e($headerLogo) ?>" alt="Local Legends Orlando"></a><button class="nav-toggle" aria-expanded="false" aria-controls="site-nav">Menu</button><nav id="site-nav" class="site-nav" aria-label="Primary"><a href="<?= url('stories/') ?>">Stories</a><a href="<?= url('search/') ?>">Search</a><a href="<?= url('categories/') ?>">Categories</a><a href="<?= url('about/') ?>">About</a><a href="<?= url('contact/') ?>">Contact</a><a class="button button-small" href="<?= url('get-featured/') ?>">Get Featured</a></nav></header>
<main id="main">
