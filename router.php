<?php
/**
 * Development Router for PHP Built-in Server
 * Simulates .htaccess rewrite rules with support for both /public and /admin
 *
 * Usage: php -S localhost:8001 router.php (from project root)
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$query = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);

// Admin section - serve from /admin directory
if (preg_match('#^/admin(/|$)#', $uri)) {
    $adminPath = __DIR__ . $uri;

    // If it's a directory, serve index.php
    if (is_dir($adminPath)) {
        $indexFile = rtrim($adminPath, '/') . '/index.php';
        if (file_exists($indexFile)) {
            require $indexFile;
            exit;
        }
    }

    // Try with .php extension
    if (file_exists($adminPath . '.php')) {
        require $adminPath . '.php';
        exit;
    }

    // 404 for admin
    http_response_code(404);
    echo "404 - Admin page not found";
    exit;
}

// Everything else is served from /public directory
$publicPath = __DIR__ . '/public' . $uri;

// Serve static files directly from /public
if ($uri !== '/' && file_exists($publicPath) && is_file($publicPath)) {
    return false; // Serve the file as-is
}

// Serve API, CSS, JS, assets directly from /public
if (preg_match('#^/(api\.php|css/|js/|assets/)#', $uri)) {
    return false; // Let PHP serve from public directory
}

// Pretty URLs: /category/slug → public/surah.php?slug=slug
if (preg_match('#^/category/([a-z0-9\-]+)/?$#i', $uri, $matches)) {
    $_GET['slug'] = $matches[1];
    if ($query) {
        parse_str($query, $queryParams);
        $_GET = array_merge($_GET, $queryParams);
    }
    require __DIR__ . '/public/surah.php';
    exit;
}

// Sitemap: /sitemap.xml → public/sitemap.xml.php
if ($uri === '/sitemap.xml') {
    require __DIR__ . '/public/sitemap.xml.php';
    exit;
}

// Robots.txt
if ($uri === '/robots.txt') {
    if (file_exists(__DIR__ . '/public/robots.txt')) {
        readfile(__DIR__ . '/public/robots.txt');
        exit;
    }
}

// Remove .php extension: /surah → public/surah.php
$phpFile = __DIR__ . '/public' . $uri . '.php';
if ($uri !== '/' && !is_dir($publicPath) && file_exists($phpFile)) {
    require $phpFile;
    exit;
}

// Homepage: / or /index → public/index.php
if ($uri === '/' || $uri === '/index') {
    require __DIR__ . '/public/index.php';
    exit;
}

// Try to serve any other file from /public
if (file_exists($publicPath)) {
    return false;
}

// 404 - File not found
http_response_code(404);
$notFoundFile = __DIR__ . '/public/404.html';
if (file_exists($notFoundFile)) {
    require $notFoundFile;
} else {
    echo "404 - Page not found";
}
exit;
