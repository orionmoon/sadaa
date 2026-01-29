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
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>" class="<?= $currentTheme ?>" dir="<?= isRtl() ? 'rtl' : 'ltr' ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Sada | Écho Spirituel</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="">
    <link
        href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400&amp;family=Inter:wght@200;300;400;500;600&amp;family=Noto+Naskh+Arabic:wght@400;500;700&amp;family=Reem+Kufi:wght@400;500;600;700&amp;family=Crimson+Text:wght@400;600;700&amp;display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="css/style.css">
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
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
                <select id="lang-select">
                    <?php foreach ($languages as $lang):
                        $langName = getLocalizedValue($lang['name'], $currentLang);
                        ?>
                        <option value="<?= $lang['code'] ?>" <?= $currentLang === $lang['code'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($langName) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
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
                <select id="book-select">
                    <option value=""><?= __('labels.book') ?>...</option>
                    <option value="quran"><?= __('public.quran') ?></option>
                </select>
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
            <!-- Type Tabs -->
            <div class="type-tabs">
                <?php foreach ($types as $index => $type):
                    $typeName = getLocalizedValue($type['name'], $currentLang);
                    $active = $index === 0 ? 'active' : '';
                    ?>
                    <button class="tab <?= $active ?>" data-type="<?= htmlspecialchars($type['slug']) ?>">
                        <iconify-icon icon="<?= htmlspecialchars($type['icon']) ?>"></iconify-icon>
                        <span><?= htmlspecialchars($typeName) ?></span>
                    </button>
                <?php endforeach; ?>
            </div>

            <!-- Picker -->
            <div class="picker-section">
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
            </div>

            <!-- Description -->
            <div class="description-section">
                <p class="profile-description visible" id="modal-description">description des croyants</p>
                <cite class="description-source" id="modal-source"></cite>
            </div>

            <!-- Confirm Button -->
            <button id="modal-confirm" class="cta-button">
                <span><?= __('actions.confirm') ?></span>
            </button>
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
        <a href="index.php" class="footer-signature fade-in" title="Retour à l'accueil">
            <span class="logo-arabic">صَــدَى</span>
        </a>
    </footer>

    <!-- Hidden select for backward compatibility -->
    <select id="category-select" style="display:none;"></select>

    <!-- JS Translations -->
    <script>
        window.translations = <?= json_encode(getJsTranslations()) ?>;
        window.currentLang = '<?= $currentLang ?>';
        window.languageEditions = <?= json_encode(array_column($languages, 'quran_edition', 'code')) ?>;
        window.importSource = '<?= $importSource ?>';
    </script>
    <!-- JS -->
    <script src="js/app.js"></script>
</body>

</html>