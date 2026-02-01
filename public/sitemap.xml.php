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

try {
    $stmt = $pdo->query("SELECT id, slug FROM categories ORDER BY id ASC");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

$languages = getActiveLanguages();

// Homepage
echo "  <url>\n";
echo "    <loc>https://sadaa.me/</loc>\n";
echo "    <changefreq>daily</changefreq>\n";
echo "    <priority>1.0</priority>\n";
foreach ($languages as $lang) {
    echo '    <xhtml:link rel="alternate" hreflang="' . $lang['code'] . '" href="https://sadaa.me/?lang=' . $lang['code'] . '" />' . "\n";
}
echo "    <xhtml:link rel=\"alternate\" hreflang=\"x-default\" href=\"https://sadaa.me/\" />\n";
echo "  </url>\n";

// Category pages (slug-based)
foreach ($categories as $cat) {
    if (empty($cat['slug'])) continue; // Skip if no slug

    echo "  <url>\n";
    echo "    <loc>https://sadaa.me/category/" . htmlspecialchars($cat['slug']) . "</loc>\n";
    echo "    <changefreq>weekly</changefreq>\n";
    echo "    <priority>0.8</priority>\n";
    foreach ($languages as $lang) {
        echo '    <xhtml:link rel="alternate" hreflang="' . $lang['code'] . '" href="https://sadaa.me/category/' . htmlspecialchars($cat['slug']) . '?lang=' . $lang['code'] . '" />' . "\n";
    }
    echo "    <xhtml:link rel=\"alternate\" hreflang=\"x-default\" href=\"https://sadaa.me/category/" . htmlspecialchars($cat['slug']) . "\" />\n";
    echo "  </url>\n";
}

echo '</urlset>';
