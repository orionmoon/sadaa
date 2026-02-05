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
if (preg_match('#^/admin(/|$)#i', $uri)) {
    $adminPath = __DIR__ . $uri;

    // If it's a directory, serve index.php
    if (is_dir($adminPath)) {
        $indexFile = rtrim($adminPath, '/') . '/index.php';
        if (file_exists($indexFile)) {
            require $indexFile;
            exit;
        }
    }

    // Try with .php extension if it doesn't have one
    if (!str_ends_with($adminPath, '.php') && file_exists($adminPath . '.php')) {
        require $adminPath . '.php';
        exit;
    }

    // Try as is (especially if it ends in .php)
    if (file_exists($adminPath) && is_file($adminPath)) {
        require $adminPath;
        exit;
    }

    // 404 for admin
    http_response_code(404);
    echo "404 - Admin page not found";
    exit;
}

// Everything else is served from /public directory
$publicPath = __DIR__ . '/public' . $uri;

// Serve CSS, JS, assets directly from /public
if (preg_match('#^/(css/|js/|assets/)#', $uri)) {
    $staticPath = realpath(__DIR__ . '/public' . $uri);
    if ($staticPath && file_exists($staticPath) && is_file($staticPath)) {
        $ext = pathinfo($staticPath, PATHINFO_EXTENSION);
        $mimes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'ico' => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'webp' => 'image/webp'
        ];
        if (isset($mimes[$ext])) {
            header("Content-Type: " . $mimes[$ext]);
        }
        header("Content-Length: " . filesize($staticPath));
        readfile($staticPath);
        exit;
    }
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

// Serve .php files from /public
if (str_ends_with($uri, '.php')) {
    $phpFile = __DIR__ . '/public' . $uri;
    if (file_exists($phpFile)) {
        require $phpFile;
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
$fallbackPath = realpath(__DIR__ . '/public' . $uri);
if ($fallbackPath && file_exists($fallbackPath) && is_file($fallbackPath)) {
    $ext = pathinfo($fallbackPath, PATHINFO_EXTENSION);
    $mimes = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'webp' => 'image/webp'
    ];
    if (isset($mimes[$ext])) {
        header("Content-Type: " . $mimes[$ext]);
    }
    header("Content-Length: " . filesize($fallbackPath));
    readfile($fallbackPath);
    exit;
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
