<?php
/**
 * Sadaa - Sitemap Generator
 * Generate XML sitemap for SEO
 */

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
echo '        xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

// Base URLs with multilingual alternatives
$baseUrls = [];

try {
    // Get categories for dynamic URLs
    $stmt = $pdo->query("SELECT id FROM categories ORDER BY id ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $categories = [];
}

// Get active languages
$languages = getActiveLanguages();

// Homepage
echo "  <url>\n";
echo "    <loc>https://sadaa.me/</loc>\n";
echo "    <changefreq>daily</changefreq>\n";
echo "    <priority>1.0</priority>\n";
foreach ($languages as $lang) {
    echo '    <xhtml:link rel="alternate" hreflang="' . $lang['code'] . '" href="https://sadaa.me/?lang=' . $lang['code'] . '" />' . "\n";
}
echo "  </url>\n";

// Category pages
foreach ($categories as $catId) {
    echo "  <url>\n";
    echo "    <loc>https://sadaa.me/public/surah.php?category=" . $catId . "</loc>\n";
    echo "    <changefreq>weekly</changefreq>\n";
    echo "    <priority>0.8</priority>\n";
    foreach ($languages as $lang) {
        echo '    <xhtml:link rel="alternate" hreflang="' . $lang['code'] . '" href="https://sadaa.me/public/surah.php?category=' . $catId . '&amp;lang=' . $lang['code'] . '" />' . "\n";
    }
    echo "  </url>\n";
}

// Admin page (low priority, but needed for SEO crawlers)
echo "  <url>\n";
echo "    <loc>https://sadaa.me/admin/</loc>\n";
echo "    <changefreq>monthly</changefreq>\n";
echo "    <priority>0.3</priority>\n";
echo "  </url>\n";

echo '</urlset>';
