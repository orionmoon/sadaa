<?php
/**
 * Sadaa (صدى) - About Page
 * À propos / About page with multilingual content
 */

require_once __DIR__ . '/../config/db.php';

// Get current language and theme
$currentLang = $_COOKIE['sadaa_lang'] ?? 'fr';
$currentTheme = $_COOKIE['sadaa_theme'] ?? 'light';

// Fetch about content for current language
$aboutContent = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM about_content WHERE language_code = ? AND is_active = 1");
    $stmt->execute([$currentLang]);
    $aboutContent = $stmt->fetch();
    
    // If no content for current language, try Arabic as fallback
    if (!$aboutContent) {
        $stmt = $pdo->prepare("SELECT * FROM about_content WHERE language_code = 'ar' AND is_active = 1");
        $stmt->execute();
        $aboutContent = $stmt->fetch();
    }
} catch (PDOException $e) {
}

// SEO
$siteName = getSetting('app_name', 'Sadaa');
$pageTitle = $aboutContent ? $aboutContent['title'] : __('about.title', 'À propos');
$seoTitle = $pageTitle . ' | ' . $siteName;
$seoDesc = strip_tags($aboutContent ? substr($aboutContent['content'], 0, 160) : __('about.description', 'Découvrez Sadaa'));

// Helper function to get localized value
function getLocalizedValue($json, $lang) {
    $data = json_decode($json, true);
    return $data[$lang] ?? $data['en'] ?? $data['fr'] ?? '';
}

// Get site logo from settings
$logoUrl = getSetting('site_logo', '/assets/favicon.svg');
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>" dir="<?= $currentLang === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($seoTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($seoDesc) ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/apple-touch-icon.png">
    
    <!-- Manifest -->
    <link rel="manifest" href="/assets/manifest.php">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Amiri:ital,wght@0,400;0,700;1,400&family=Noto+Naskh+Arabic:wght@400;500;600;700&family=Noto+Naskh+Arabic+UI&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="/css/style.css">
    
    <!-- Iconify -->
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    
    <!-- Theme -->
    <script>
        (function() {
            const theme = localStorage.getItem('sadaa_theme') || 'light';
            document.documentElement.classList.add(theme);
        })();
    </script>
    
    <style>
        .about-page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .about-header {
            padding: 3rem 1.5rem 2rem;
            text-align: center;
            background: var(--bg-primary);
        }
        
        .about-logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
        }
        
        .about-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .about-title {
            font-family: var(--font-noto);
            font-size: 2rem;
            color: var(--color-primary);
            margin: 0;
            font-weight: 600;
        }
        
        .about-content {
            flex: 1;
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
            line-height: 1.6;
            font-size: 1.1rem;
            color: var(--text-primary);
        }
        
        .about-content[dir="rtl"] {
            font-family: var(--font-noto);
            text-align: right;
        }
        
        .about-content p {
            margin: 0 0 0.75rem 0;
        }
        
        .about-content p:last-child {
            margin-bottom: 0;
        }
        
        .about-content h1,
        .about-content h2,
        .about-content h3,
        .about-content h4,
        .about-content h5,
        .about-content h6 {
            margin: 1.5rem 0 0.75rem 0;
        }
        
        .about-content h1:first-child,
        .about-content h2:first-child,
        .about-content h3:first-child {
            margin-top: 0;
        }
        
        .about-content ul,
        .about-content ol {
            margin: 0.75rem 0;
            padding-left: 1.5rem;
        }
        
        .about-content li {
            margin: 0.25rem 0;
        }
        
        .about-footer {
            padding: 2rem 1.5rem;
            text-align: center;
            background: var(--bg-secondary);
            border-top: 1px solid var(--border-color);
        }
        
        .about-footer p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .about-footer .logo-text {
            font-family: var(--font-noto);
            font-size: 1.5rem;
            color: var(--color-primary);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .about-title {
                font-size: 1.5rem;
            }
            
            .about-content {
                font-size: 1rem;
                padding: 1.5rem 1rem;
            }
        }
    </style>
</head>
<body class="<?= $currentTheme ?>">
    <div class="about-page">
        <!-- Header with Logo -->
        <header class="about-header">
            <div class="about-logo">
                <img src="<?= htmlspecialchars($logoUrl) ?>" alt="<?= htmlspecialchars($siteName) ?>">
            </div>
            <h1 class="about-title"><?= htmlspecialchars($pageTitle) ?></h1>
        </header>
        
        <!-- Main Content -->
        <main class="about-content" dir="<?= $currentLang === 'ar' ? 'rtl' : 'ltr' ?>">
            <?php if ($aboutContent && $aboutContent['content']): ?>
                <?php
                $allowedTags = '<h1><h2><h3><h4><h5><h6><p><br><strong><b><em><i><ul><ol><li>';
                echo strip_tags($aboutContent['content'], $allowedTags);
                ?>
            <?php else: ?>
                <p>Contenu à venir...</p>
            <?php endif; ?>
        </main>
        
        <!-- Footer -->
        <footer class="about-footer">
            <div class="logo-text">صَــدَى</div>
            <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($siteName) ?></p>
        </footer>
    </div>
</body>
</html>
