<?php
/**
 * Development Router for PHP Built-in Server
 * Simulates .htaccess rewrite rules
 *
 * Usage: php -S localhost:8001 router.php
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$query = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);

// Serve static files directly
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false; // Serve the file as-is
}

// Exclude API, CSS, JS, assets from rewriting
if (preg_match('#^/(api\.php|css/|js/|assets/|admin/)#', $uri)) {
    return false;
}

// Pretty URLs: /category/slug → surah.php?slug=slug
if (preg_match('#^/category/([a-z0-9\-]+)/?$#i', $uri, $matches)) {
    $_GET['slug'] = $matches[1];
    if ($query) {
        parse_str($query, $queryParams);
        $_GET = array_merge($_GET, $queryParams);
    }
    require __DIR__ . '/surah.php';
    exit;
}

// Sitemap: /sitemap.xml → sitemap.xml.php
if ($uri === '/sitemap.xml') {
    require __DIR__ . '/sitemap.xml.php';
    exit;
}

// Robots.txt
if ($uri === '/robots.txt') {
    if (file_exists(__DIR__ . '/robots.txt')) {
        return false;
    }
}

// Remove .php extension: /surah → surah.php
$phpFile = __DIR__ . $uri . '.php';
if ($uri !== '/' && !is_dir(__DIR__ . $uri) && file_exists($phpFile)) {
    require $phpFile;
    exit;
}

// Homepage: / or /index → index.php
if ($uri === '/' || $uri === '/index') {
    require __DIR__ . '/index.php';
    exit;
}

// If we reach here, it's a 404 - page doesn't exist
// This handles /hghg and any other non-existent URLs
http_response_code(404);
require __DIR__ . '/404.php';
exit;
