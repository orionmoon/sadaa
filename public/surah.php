<?php
require_once __DIR__ . '/../config/db.php';
$currentTheme = $_COOKIE['sadaa_theme'] ?? 'light';
$currentLang = getCurrentLocale();
$languages = getActiveLanguages();

// Fetch types from database
$types = [];
try {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM types ORDER BY sort_order ASC");
    $types = $stmt->fetchAll();
} catch (PDOException $e) {
}

// Get import source
$importSource = 'alquran.cloud';
try {
    $stmt = $pdo->query("SELECT source FROM imports WHERE status = 'completed' ORDER BY completed_at DESC LIMIT 1");
    $import = $stmt->fetch();
    if ($import) {
        $importSource = $import['source'];
    }
} catch (PDOException $e) {
}

// Fetch about content for modal
$aboutContent = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM about_content WHERE language_code = ? AND is_active = 1");
    $stmt->execute([$currentLang]);
    $aboutContent = $stmt->fetch();

    // Fallback to Arabic
    if (!$aboutContent) {
        $stmt = $pdo->prepare("SELECT * FROM about_content WHERE language_code = 'ar' AND is_active = 1");
        $stmt->execute();
        $aboutContent = $stmt->fetch();
    }
} catch (PDOException $e) {
}
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>" class="<?= $currentTheme ?>" dir="<?= isRtl() ? 'rtl' : 'ltr' ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

    <?php
    $pageTitle = "Sadaa | Écho Spirituel";
    $pageDesc = "Découvrez les trésors du Coran à travers des thématiques inspirantes.";
    $categoryId = null;
    $categorySlug = null;
    $translatedName = '';

    // Priority: slug parameter, then fallback to ID for backward compatibility
    if (isset($_GET['slug'])) {
        $categorySlug = $_GET['slug'];
        try {
            $stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ?");
            $stmt->execute([$categorySlug]);
            $categoryData = $stmt->fetch();
            if ($categoryData) {
                $categoryId = $categoryData['id'];
                $catName = json_decode($categoryData['name'], true);
                $translatedName = $catName[$currentLang] ?? $catName['en'] ?? $catName['fr'] ?? '';
                if ($translatedName) {
                    $pageTitle = htmlspecialchars($translatedName) . " - Sadaa";
                    $pageDesc = "Explorez les versets du Coran sur le thème : " . htmlspecialchars($translatedName);
                }
            } else {
                http_response_code(404);
                header("Location: /404.html");
                exit;
            }
        } catch (PDOException $e) {
        }
    } elseif (isset($_GET['category'])) {
        // Backward compatibility: redirect to slug-based URL
        $categoryId = (int) $_GET['category'];
        try {
            $stmt = $pdo->prepare("SELECT slug FROM categories WHERE id = ?");
            $stmt->execute([$categoryId]);
            $cat = $stmt->fetch();
            if ($cat && !empty($cat['slug'])) {
                $redirectUrl = "/category/{$cat['slug']}";
                if (isset($_GET['lang'])) {
                    $redirectUrl .= "?lang=" . urlencode($_GET['lang']);
                }
                header("Location: $redirectUrl", true, 301);
                exit;
            }
        } catch (PDOException $e) {
        }
    }
    ?>
    <title><?= $pageTitle ?></title>

    <!-- Social Meta Tags -->
    <meta name="description" content="<?= $pageDesc ?>">
    <meta property="og:title" content="<?= $pageTitle ?>">
    <meta property="og:description" content="<?= $pageDesc ?>">
    <meta property="og:url" content="https://sadaa.me/category/<?= htmlspecialchars($categorySlug ?? '') ?>">
    <meta property="og:type" content="article">
    <meta property="og:image" content="https://sadaa.me/assets/og-image.jpg">
    <meta property="og:site_name" content="Sadaa">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= $pageTitle ?>">
    <meta name="twitter:description" content="<?= $pageDesc ?>">
    <meta name="twitter:image" content="https://sadaa.me/assets/og-image.jpg">

    <!-- Canonical URL -->
    <?php if ($categorySlug): ?>
        <link rel="canonical" href="https://sadaa.me/category/<?= htmlspecialchars($categorySlug) ?>">
    <?php endif; ?>

    <!-- Hreflang for multilingual -->
    <?php if ($categorySlug): ?>
        <?php foreach ($languages as $lang): ?>
            <link rel="alternate" hreflang="<?= $lang['code'] ?>"
                href="https://sadaa.me/category/<?= htmlspecialchars($categorySlug) ?><?= $lang['code'] !== $currentLang ? '?lang=' . $lang['code'] : '' ?>">
        <?php endforeach; ?>
        <link rel="alternate" hreflang="x-default" href="https://sadaa.me/category/<?= htmlspecialchars($categorySlug) ?>">
    <?php endif; ?>

    <!-- JSON-LD Structured Data -->
    <?php if ($categorySlug && !empty($translatedName)): ?>
        <script type="application/ld+json">
                {
                  "@context": "https://schema.org",
                  "@type": "WebPage",
                  "name": "<?= htmlspecialchars($translatedName) ?>",
                  "description": "<?= htmlspecialchars($pageDesc) ?>",
                  "url": "https://sadaa.me/category/<?= htmlspecialchars($categorySlug) ?>",
                  "inLanguage": "<?= $currentLang ?>",
                  "isPartOf": {
                    "@type": "WebSite",
                    "name": "Sadaa",
                    "url": "https://sadaa.me"
                  },
                  "breadcrumb": {
                    "@type": "BreadcrumbList",
                    "itemListElement": [
                      {
                        "@type": "ListItem",
                        "position": 1,
                        "name": "Accueil",
                        "item": "https://sadaa.me"
                      },
                      {
                        "@type": "ListItem",
                        "position": 2,
                        "name": "<?= htmlspecialchars($translatedName) ?>",
                        "item": "https://sadaa.me/category/<?= htmlspecialchars($categorySlug) ?>"
                      }
                    ]
                  }
                }
                    </script>
    <?php endif; ?>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/apple-touch-icon.png">
    <link rel="manifest" href="/assets/manifest.php">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="">
    <link
        href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400&amp;family=Inter:wght@200;300;400;500;600&amp;family=Noto+Naskh+Arabic:wght@400;500;700&amp;family=Reem+Kufi:wght@400;500;600;700&amp;family=Crimson+Text:wght@400;600;700&amp;display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="/css/style.css">
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <!-- Library for generating images from HTML -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <!-- Driver.js for Guided Tour -->
    <script src="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.js.iife.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.css" />
</head>

<body dir="<?= isRtl() ? 'rtl' : 'ltr' ?>">
    <!-- Redirect to Welcome if not onboarded -->
    <script>
        // Simple hydration check or redirection if needed
        const urlParams = new URLSearchParams(window.location.search);
        if (!localStorage.getItem('sada_onboarded') && !urlParams.has('category')) {
            // window.location.href = 'index.php'; // Uncomment if strict
        }
    </script>

    <header>
        <div class="header-grid">
            <div class="h-left">
                <!-- Desktop: Select dropdown -->
                <select id="lang-select" class="desktop-only">
                    <?php foreach ($languages as $lang):
                        $langName = getLocalizedValue($lang['name'], $currentLang);
                        ?>
                        <option value="<?= $lang['code'] ?>" <?= $currentLang === $lang['code'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($langName) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <!-- Mobile: Language icon button -->
                <button id="lang-toggle-mobile" class="btn-icon mobile-only" title="<?= __('labels.language') ?>">
                    <iconify-icon icon="mdi:translate"></iconify-icon>
                </button>
            </div>
            <div class="h-center">
                <!-- Picker Trigger Button -->
                <button id="picker-trigger" class="picker-trigger">
                    <span id="current-category-icon"><iconify-icon icon="mdi:lightbulb-outline"></iconify-icon></span>
                    <span id="current-category-name">Croyants</span>
                    <iconify-icon icon="mdi:chevron-down"></iconify-icon>
                </button>
            </div>
            <div class="h-right">
                <!-- Desktop: Book select -->
                <select id="book-select" class="desktop-only">
                    <option value=""><?= __('labels.book') ?>...</option>
                    <option value="quran"><?= __('public.quran') ?></option>
                </select>
                <!-- Mobile: Book icon button -->
                <button id="book-toggle-mobile" class="btn-icon mobile-only" title="<?= __('labels.book') ?>">
                    <iconify-icon icon="mdi:book-open-variant"></iconify-icon>
                </button>
                <button id="theme-toggle" class="btn-icon theme-toggle" title="Mode Sombre/Clair">
                    <iconify-icon class="icon-sun" icon="mdi:white-balance-sunny"></iconify-icon>
                    <iconify-icon class="icon-moon" icon="mdi:moon-waning-crescent"></iconify-icon>
                </button>
            </div>
        </div>
    </header>

    <!-- Category Picker Modal -->
    <div id="picker-modal" class="picker-modal hidden">
        <div class="picker-modal-backdrop"></div>
        <div class="picker-modal-content">
            <div class="welcome-content-wrapper">
                <!-- Type Tabs -->
                <?php
                $tabsVisibleCount = getSetting('tabs_visible_count', 5);
                $needsScroll = count($types) > $tabsVisibleCount;
                ?>
                <div class="type-tabs-container<?= $needsScroll ? ' scrollable' : '' ?>"
                    style="--tabs-visible: <?= $tabsVisibleCount ?>;">
                    <?php if ($needsScroll): ?>
                        <button class="tab-nav-arrow tab-nav-left" id="modal-tab-nav-left"
                            aria-label="<?= __('common.previous') ?>">
                            <iconify-icon icon="mdi:chevron-left"></iconify-icon>
                        </button>
                    <?php endif; ?>

                    <section class="type-tabs" id="modal-type-tabs-scroll" data-visible-count="<?= $tabsVisibleCount ?>"
                        style="--tabs-visible: <?= $tabsVisibleCount ?>">
                        <?php
                        $totalTypes = count($types);
                        foreach ($types as $index => $type):
                            $typeName = getLocalizedValue($type['name'], $currentLang);
                            $active = $index === 0 ? 'active' : '';
                            $position = $index === 0 ? 'first' : ($index === $totalTypes - 1 ? 'last' : 'middle');
                            ?>
                            <button class="tab <?= $active ?> <?= $position ?>"
                                data-type="<?= htmlspecialchars($type['slug']) ?>">
                                <iconify-icon icon="<?= htmlspecialchars($type['icon']) ?>"></iconify-icon>
                                <span><?= htmlspecialchars($typeName) ?></span>
                            </button>
                        <?php endforeach; ?>
                    </section>

                    <?php if ($needsScroll): ?>
                        <button class="tab-nav-arrow tab-nav-right" id="modal-tab-nav-right"
                            aria-label="<?= __('common.next') ?>">
                            <iconify-icon icon="mdi:chevron-right"></iconify-icon>
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Active Type Label (Mobile) -->
                <div class="active-type-label" id="modal-active-type-label">
                    <?= htmlspecialchars(getLocalizedValue($types[0]['name'], $currentLang)) ?>
                </div>

                <!-- Picker Section -->
                <section class="picker-section">
                    <div class="picker-wrapper">
                        <button class="picker-arrow up" id="modal-arrow-up" disabled="">
                            <iconify-icon icon="mdi:chevron-up"></iconify-icon>
                        </button>
                        <div class="picker-viewport">
                            <div class="picker-track" id="modal-picker-track" style="transform: translateY(0px);">
                                <!-- Items populated by JS -->
                            </div>
                        </div>
                        <button class="picker-arrow down" id="modal-arrow-down">
                            <iconify-icon icon="mdi:chevron-down"></iconify-icon>
                        </button>
                    </div>
                </section>

                <!-- Description Section -->
                <section class="description-section">
                    <p class="profile-description visible" id="modal-description">description des croyants</p>
                    <cite class="description-source" id="modal-source"></cite>
                </section>

                <!-- Confirm Button -->
                <section class="cta-section">
                    <button id="modal-confirm" class="cta-button">
                        <span><?= __('actions.confirm') ?></span>
                    </button>
                </section>
            </div>
        </div>
    </div>

    <!-- Language Selector Modal (Mobile) -->
    <div id="lang-modal" class="picker-modal hidden">
        <div class="picker-modal-backdrop"></div>
        <div class="picker-modal-content simple-modal">
            <h3 class="modal-title"><?= __('labels.language') ?></h3>
            <div class="simple-options-list" id="lang-options">
                <?php foreach ($languages as $lang):
                    $langName = getLocalizedValue($lang['name'], $currentLang);
                    $isActive = $currentLang === $lang['code'];
                    ?>
                    <button class="simple-option <?= $isActive ? 'active' : '' ?>" data-lang="<?= $lang['code'] ?>">
                        <span class="option-icon">
                            <?php if ($isActive): ?>
                                <iconify-icon icon="mdi:check"></iconify-icon>
                            <?php endif; ?>
                        </span>
                        <span class="option-label"><?= htmlspecialchars($langName) ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php
    // Fetch active books from database
    $books = [];
    try {
        $stmt = $pdo->query("SELECT * FROM books ORDER BY id ASC");
        $books = $stmt->fetchAll();
    } catch (PDOException $e) {
        // Fallback: at least include Quran
        $books = [
            ['id' => 1, 'slug' => 'quran', 'title' => json_encode(['fr' => 'Le Coran', 'en' => 'The Quran', 'ar' => 'القرآن الكريم', 'es' => 'El Corán', 'de' => 'Der Koran']), 'icon' => 'mdi:book-open-page-variant']
        ];
    }
    ?>

    <!-- Book Selector Modal (Mobile) -->
    <div id="book-modal" class="picker-modal hidden">
        <div class="picker-modal-backdrop"></div>
        <div class="picker-modal-content simple-modal">
            <h3 class="modal-title"><?= __('labels.book') ?></h3>
            <div class="simple-options-list" id="book-options">
                <?php foreach ($books as $book):
                    $bookTitle = json_decode($book['title'], true);
                    $displayTitle = $bookTitle[$currentLang] ?? $bookTitle['en'] ?? $bookTitle['fr'] ?? $book['slug'];
                    $bookIcon = $book['icon'] ?? 'mdi:book-open-page-variant';
                    ?>
                    <button class="simple-option" data-book="<?= htmlspecialchars($book['slug']) ?>">
                        <span class="option-icon"><iconify-icon
                                icon="<?= htmlspecialchars($bookIcon) ?>"></iconify-icon></span>
                        <span class="option-label"><?= htmlspecialchars($displayTitle) ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Quran Reader Modal -->
    <div id="reader-modal" class="reader-modal hidden">
        <div class="reader-modal-backdrop"></div>
        <div class="reader-modal-content">
            <!-- Header -->
            <div class="reader-header">
                <button id="reader-close" class="reader-close-btn" title="<?= __('actions.close') ?>">
                    <iconify-icon icon="mdi:close"></iconify-icon>
                </button>
                <div class="reader-surah-info">
                    <h2 id="reader-surah-name" class="reader-surah-name"></h2>
                    <span id="reader-verse-indicator" class="reader-verse-indicator"></span>
                </div>
                <div class="reader-settings">
                    <button id="reader-font-decrease" class="reader-settings-btn"
                        title="<?= __('public.decrease_font') ?>">
                        <iconify-icon icon="mdi:format-font-size-decrease"></iconify-icon>
                    </button>
                    <button id="reader-font-increase" class="reader-settings-btn"
                        title="<?= __('public.increase_font') ?>">
                        <iconify-icon icon="mdi:format-font-size-increase"></iconify-icon>
                    </button>
                </div>
            </div>

            <!-- Surah Selector -->
            <div class="reader-surah-selector">
                <button id="reader-prev-surah" class="reader-nav-surah" title="<?= __('public.prev_surah') ?>">
                    <iconify-icon icon="mdi:chevron-left"></iconify-icon>
                </button>
                <select id="reader-surah-select" class="reader-surah-select">
                    <!-- Populated by JS -->
                </select>
                <button id="reader-next-surah" class="reader-nav-surah" title="<?= __('public.next_surah') ?>">
                    <iconify-icon icon="mdi:chevron-right"></iconify-icon>
                </button>
            </div>

            <!-- Reading Area -->
            <div class="reader-content">
                <div id="reader-text" class="reader-text">
                    <!-- Quran text will be displayed here -->
                </div>
            </div>

            <!-- Navigation Footer -->
            <div class="reader-footer">
                <button id="reader-prev-page" class="reader-nav-btn" title="<?= __('public.prev_page') ?>">
                    <iconify-icon icon="mdi:chevron-left"></iconify-icon>
                    <span><?= __('public.previous') ?></span>
                </button>
                <div class="reader-progress">
                    <span id="reader-current-ayah">1</span>
                    <span class="reader-progress-separator">/</span>
                    <span id="reader-total-ayahs">7</span>
                </div>
                <button id="reader-next-page" class="reader-nav-btn" title="<?= __('public.next_page') ?>">
                    <span><?= __('public.next') ?></span>
                    <iconify-icon icon="mdi:chevron-right"></iconify-icon>
                </button>
            </div>
        </div>
    </div>

    <main class="main-viewport">
        <!-- 1. Metadata TOP -->
        <div class="top-section">
            <h1 id="surah-title" class="surah-title fade-in"><?= __('messages.loading') ?></h1>
            <div id="verse-ref" class="verse-ref fade-in"></div>
            <div id="group-tags" class="group-tags fade-in flex justify-center gap-1 mt-1"></div>
        </div>

        <!-- 2. Screen (16:9 Container) -->
        <div class="card-container">
            <div class="card-16-9">
                <div class="scroll-content" id="scroll-container">
                    <!-- Translation text with scroll -->
                    <div id="translation-wrapper" class="translation-block">
                        <div id="translation-text" class="translation-text-area">
                            <div id="translation-inner"></div>
                        </div>
                        <div id="text-scroll-arrows" class="text-scroll-arrows hidden">
                            <button id="text-scroll-up" class="text-scroll-arrow" disabled>
                                <iconify-icon icon="mdi:chevron-up"></iconify-icon>
                            </button>
                            <button id="text-scroll-down" class="text-scroll-arrow">
                                <iconify-icon icon="mdi:chevron-down"></iconify-icon>
                            </button>
                        </div>
                    </div>
                    <!-- Arabic text with scroll -->
                    <div id="arabic-wrapper" class="arabic-block">
                        <div id="arabic-scroll-arrows" class="arabic-scroll-arrows hidden">
                            <button id="arabic-scroll-up" class="arabic-scroll-arrow" disabled>
                                <iconify-icon icon="mdi:chevron-up"></iconify-icon>
                            </button>
                            <button id="arabic-scroll-down" class="arabic-scroll-arrow">
                                <iconify-icon icon="mdi:chevron-down"></iconify-icon>
                            </button>
                        </div>
                        <div id="arabic-text-area" class="arabic-text-area">
                            <div id="verse-text" class="arabic-inner"></div>
                        </div>
                    </div>
                </div>
                <div class="scroll-mask"></div>
            </div>
        </div>

        <!-- 3. Footer BOTTOM -->
        <div class="bottom-section">
            <div id="source-attribution" class="source-attribution fade-in"></div>
            <div class="nav-controls">
                <button id="btn-prev" class="btn-icon large" title="<?= __('actions.previous') ?>" disabled="">
                    <iconify-icon icon="mdi:chevron-left"></iconify-icon>
                </button>
                <button class="btn-icon" id="btn-copy" title="<?= __('public.copy') ?>">
                    <iconify-icon icon="mdi:content-copy"></iconify-icon>
                </button>
                <button class="btn-icon" id="btn-share" title="Partager">
                    <iconify-icon icon="mdi:share-variant"></iconify-icon>
                </button>
                <button id="btn-read-quran" class="btn-icon" title="<?= __('public.read_quran') ?>">
                    <iconify-icon icon="mdi:book-open-page-variant"></iconify-icon>
                </button>
                <button id="btn-next" class="btn-icon large" title="<?= __('actions.next') ?>">
                    <iconify-icon icon="mdi:chevron-right"></iconify-icon>
                </button>
            </div>
        </div>
    </main>

    <footer>
        <div class="footer-content">
            <button type="button" id="about-info-btn" class="info-btn footer-info-btn"
                title=" <?= __('public.about') ?>">
                <iconify-icon icon="mdi:information-circle"></iconify-icon>
            </button>
            <a href="/" class="footer-signature fade-in" title="<?= __('actions.back_home') ?>">
                <span class="logo-arabic">صَــدَى</span>
            </a>
            <a href="https://github.com/orionmoon/sadaa" target="_blank" class="github-link"
                title="<?= __('public.github_link') ?>">
                <iconify-icon icon="mdi:github"></iconify-icon>
            </a>
        </div>
    </footer>

    <!-- Hidden select for backward compatibility -->
    <select id="category-select" style="display:none;">
        <?php if ($categoryId): ?>
            <option value="<?= $categoryId ?>" selected><?= $categoryId ?></option>
        <?php endif; ?>
    </select>

    <!-- JS Translations -->
    <script>
        window.translations = <?= json_encode(getJsTranslations()) ?>;
        window.currentLang = '<?= $currentLang ?>';
        window.languageEditions = <?= json_encode(array_column($languages, 'quran_edition', 'code')) ?>;
        window.importSource = '<?= $importSource ?>';
        <?php if ($categoryId): ?>
            // Set current category ID for JavaScript
            window.initialCategoryId = <?= $categoryId ?>;
        <?php endif; ?>
    </script>
    <!-- JS -->
    <!-- Share Modal -->
    <div id="share-modal" class="picker-modal hidden">
        <div class="picker-modal-backdrop"></div>
        <div class="picker-modal-content share-modal-content">
            <h2 class="modal-title"><?= __('public.share_verse') ?></h2>

            <!-- Format & Theme Switchers - Same Line -->
            <div class="share-controls-row">
                <div class="share-control-group">
                    <label><?= __('js.share_format') ?></label>
                    <div class="share-format-tabs">
                        <button class="btn-format active" data-format="story">
                            <iconify-icon icon="mdi:smartphone"></iconify-icon> <?= __('js.story') ?>
                        </button>
                        <button class="btn-format" data-format="square">
                            <iconify-icon icon="mdi:crop-square"></iconify-icon> <?= __('js.square') ?>
                        </button>
                    </div>
                </div>

                <div class="share-control-group">
                    <label><?= __('js.share_theme') ?></label>
                    <div class="share-theme-tabs">
                        <button class="btn-theme active" data-theme="dark">
                            <iconify-icon icon="mdi:moon-waning-crescent"></iconify-icon> <?= __('js.theme_dark') ?>
                        </button>
                        <button class="btn-theme" data-theme="light">
                            <iconify-icon icon="mdi:white-balance-sunny"></iconify-icon> <?= __('js.theme_light') ?>
                        </button>
                    </div>
                </div>
            </div>

            <div id="share-preview-container" class="share-preview-container">
                <!-- Secret area for image generation -->
                <div id="share-card-story" class="share-card theme-dark format-story">
                    <div class="card-bg-pattern"></div>
                    <div class="card-gradient"></div>
                    <div class="card-custom-bg"></div>
                    <!-- Header avec infos en haut -->
                    <div class="card-header">
                        <div class="card-category" id="card-category">La Patience</div>
                        <div class="card-surah-title" id="card-surah-title">Al-Baqarah</div>
                        <div class="card-ayah-number" id="card-ayah-ref">2:153</div>
                    </div>
                    <!-- Contenu centré -->
                    <div class="card-content">
                        <div class="card-arabic" id="card-ayah-arabic">يَا أَيُّهَا الَّذِينَ آمَنُوا اسْتَعِينُوا
                            بِالصَّبْرِ وَالصَّلَاةِ</div>
                        <div class="card-translation" id="card-ayah-translation">O vous qui croyez ! Cherchez secours
                            dans la patience et la prière.</div>
                    </div>
                    <!-- Footer en bas -->
                    <div class="card-footer">
                        <div class="card-logo">صَــدَى</div>
                        <div class="card-url">sadaa.me</div>
                    </div>
                </div>
            </div>

            <!-- Arabic Text Toggle (only for non-Arabic languages) -->
            <?php if ($currentLang !== 'ar'): ?>
                <div class="share-arabic-toggle" id="share-arabic-toggle-container">
                    <label class="toggle-label">
                        <input type="checkbox" id="toggle-arabic-text" checked>
                        <span class="toggle-slider"></span>
                        <span class="toggle-text"><?= __('js.show_arabic') ?></span>
                    </label>
                </div>
            <?php endif; ?>

            <!-- Background Gallery (Optional) -->
            <div class="share-bg-gallery" id="share-bg-gallery">
                <button class="btn-bg-none active" data-bg="">
                    <iconify-icon icon="mdi:close"></iconify-icon>
                </button>
                <!-- JS will populate these if backgrounds exist -->
            </div>

            <!-- Action Buttons -->
            <div class="share-action-buttons">
                <button id="btn-download-image" class="btn-share-action btn-share-download"
                    title="<?= __('actions.download') ?>">
                    <iconify-icon icon="mdi:download"></iconify-icon>
                </button>
                <button id="btn-close-share" class="btn-share-action btn-share-close"
                    title="<?= __('actions.close') ?>">
                    <iconify-icon icon="mdi:close"></iconify-icon>
                </button>
            </div>
        </div>
    </div>

    <!-- About Modal -->
    <div id="about-modal" class="modal hidden">
        <div class="modal-backdrop"></div>
        <div class="modal-content about-modal-content">
            <button type="button" id="close-about-modal" class="modal-close-btn">
                <iconify-icon icon="mdi:close"></iconify-icon>
            </button>
            <div class="modal-body">
                <?php if ($aboutContent): ?>
                    <h2 class="about-modal-title" dir="<?= $currentLang === 'ar' ? 'rtl' : 'ltr' ?>">
                        <?= htmlspecialchars($aboutContent['title']) ?>
                    </h2>
                    <div class="about-modal-text" dir="<?= $currentLang === 'ar' ? 'rtl' : 'ltr' ?>">
                        <?php
                        $allowedTags = '<h1><h2><h3><h4><h5><h6><p><br><strong><b><em><i><ul><ol><li>';
                        echo strip_tags($aboutContent['content'], $allowedTags);
                        ?>
                    </div>
                    <div class="about-community-section" dir="<?= $currentLang === 'ar' ? 'rtl' : 'ltr' ?>">
                        <hr>
                        <h3><?= __('public.community_title') ?></h3>
                        <p><?= __('public.community_text') ?></p>
                        <a href="https://github.com/orionmoon/sadaa/blob/main/CONTRIBUTING.md" target="_blank"
                            class="btn-community">
                            <iconify-icon icon="mdi:github"></iconify-icon>
                            <?= __('public.contribute_action') ?>
                        </a>
                    </div>
                <?php else: ?>
                    <p class="about-modal-text">À propos de Sadaa</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // About modal for surah page
        const aboutBtn = document.getElementById('about-info-btn');
        const aboutModal = document.getElementById('about-modal');
        const closeAboutBtn = document.getElementById('close-about-modal');
        const aboutBackdrop = aboutModal.querySelector('.modal-backdrop');

        function openAboutModal() {
            aboutModal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeAboutModal() {
            aboutModal.classList.add('hidden');
            document.body.style.overflow = '';
        }

        if (aboutBtn) {
            aboutBtn.addEventListener('click', openAboutModal);
        }

        if (closeAboutBtn) {
            closeAboutBtn.addEventListener('click', closeAboutModal);
        }

        if (aboutBackdrop) {
            aboutBackdrop.addEventListener('click', closeAboutModal);
        }

        // Close on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !aboutModal.classList.contains('hidden')) {
                closeAboutModal();
            }
        });

        // Surah page tour guide
        document.addEventListener('DOMContentLoaded', () => {
            const tourSeen = localStorage.getItem('sadaa_surah_tour_seen');
            if (!tourSeen) {
                const driver = window.driver.js.driver({
                    showProgress: true,
                    animate: true,
                    padding: 10,
                    opacity: 0.75,
                    nextBtnText: '<?= __("onboarding.btn_next") ?>',
                    prevBtnText: '<?= __("onboarding.btn_prev") ?>',
                    doneBtnText: '<?= __("onboarding.btn_done") ?>',
                    steps: [
                        {
                            element: '#picker-trigger',
                            popover: {
                                title: '<?= __("surah_tour.category_title") ?>',
                                description: '<?= __("surah_tour.category_text") ?>',
                                side: "bottom",
                                align: 'center'
                            }
                        },
                        {
                            element: '.card-container',
                            popover: {
                                title: '<?= __("surah_tour.reader_title") ?>',
                                description: '<?= __("surah_tour.reader_text") ?>',
                                side: "top",
                                align: 'center'
                            }
                        },
                        {
                            element: '.nav-controls',
                            popover: {
                                title: '<?= __("surah_tour.navigation_title") ?>',
                                description: '<?= __("surah_tour.navigation_text") ?>',
                                side: "top",
                                align: 'center'
                            }
                        },
                        {
                            element: '#btn-copy',
                            popover: {
                                title: '<?= __("surah_tour.copy_title") ?>',
                                description: '<?= __("surah_tour.copy_text") ?>',
                                side: "top",
                                align: 'center'
                            }
                        },
                        {
                            element: '#btn-share',
                            popover: {
                                title: '<?= __("surah_tour.share_title") ?>',
                                description: '<?= __("surah_tour.share_text") ?>',
                                side: "top",
                                align: 'center'
                            }
                        },
                        {
                            element: '#btn-read-quran',
                            popover: {
                                title: '<?= __("surah_tour.read_mode_title") ?>',
                                description: '<?= __("surah_tour.read_mode_text") ?>',
                                side: "top",
                                align: 'center'
                            }
                        }
                    ]
                });

                driver.drive();
                localStorage.setItem('sadaa_surah_tour_seen', 'true');
            }
        });
    </script>

    <script src="/js/app.js"></script>
</body>

</html>