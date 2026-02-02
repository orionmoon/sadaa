<?php
/**
 * Sadaa (صدى) - Home Page
 * Main landing page with header/content/footer layout
 */

require_once __DIR__ . '/../config/db.php';

// Get current language and theme
$currentLang = $_COOKIE['sadaa_lang'] ?? 'fr';
$currentTheme = $_COOKIE['sadaa_theme'] ?? 'light';

// Fetch types
$types = [];
try {
    $stmt = $pdo->query("SELECT * FROM types ORDER BY sort_order ASC");
    $types = $stmt->fetchAll();
} catch (PDOException $e) {
}

// Fetch categories grouped by type
$categoriesByType = [];
try {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY type_id, sort_order ASC");
    while ($cat = $stmt->fetch()) {
        $categoriesByType[$cat['type_id']][] = $cat;
    }
} catch (PDOException $e) {
}

// Fetch active languages
$languages = getActiveLanguages();

// Fetch dynamic tagline from settings
$taglineSetting = getSetting('tagline');
$taglineArray = $taglineSetting ? json_decode($taglineSetting, true) : null;
$dynamicTagline = '';

if ($taglineArray && isset($taglineArray[$currentLang])) {
    $dynamicTagline = $taglineArray[$currentLang];
} elseif ($taglineArray && isset($taglineArray['ar'])) {
    $dynamicTagline = $taglineArray['ar'];
} else {
    $dynamicTagline = __('public.tagline');
}

// SEO: Get site name from settings
$siteName = getSetting('app_name', 'Sadaa');
$siteTagline = $dynamicTagline;
$seoDesc = htmlspecialchars($siteTagline . ' - ' . $siteName);
$seoTitle = htmlspecialchars($siteName . ' | ' . $siteTagline);
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>" class="<?= $currentTheme ?>" dir="<?= $currentLang === 'ar' ? 'rtl' : 'ltr' ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= $seoTitle ?></title>

    <!-- SEO Meta Tags -->
    <meta name="description" content="<?= $seoDesc ?>">
    <meta name="keywords" content="Coran, Quran, Islam, Spiritualité, Sagesse, Thématiques, Versets">
    <link rel="canonical" href="https://sadaa.me/">

    <!-- Social Meta Tags -->
    <meta property="og:title" content="<?= $seoTitle ?>">
    <meta property="og:description" content="<?= $seoDesc ?>">
    <meta property="og:url" content="https://sadaa.me">
    <meta property="og:type" content="website">
    <meta property="og:image" content="https://sadaa.me/assets/og-image.jpg">
    <meta property="og:site_name" content="<?= htmlspecialchars($siteName) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= $seoTitle ?>">
    <meta name="twitter:description" content="<?= $seoDesc ?>">
    <meta name="twitter:image" content="https://sadaa.me/assets/og-image.jpg">

    <!-- Hreflang for multilingual -->
    <?php foreach ($languages as $lang): ?>
        <link rel="alternate" hreflang="<?= $lang['code'] ?>" href="https://sadaa.me/?lang=<?= $lang['code'] ?>">
    <?php endforeach; ?>
    <link rel="alternate" hreflang="x-default" href="https://sadaa.me/">

    <!-- JSON-LD Structured Data -->
    <script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "name": "<?= htmlspecialchars($siteName) ?>",
  "url": "https://sadaa.me",
  "description": "<?= htmlspecialchars($siteTagline) ?>",
  "inLanguage": ["ar", "fr", "en"],
  "potentialAction": {
    "@type": "SearchAction",
    "target": {
      "@type": "EntryPoint",
      "urlTemplate": "https://sadaa.me/category/{search_term_string}"
    },
    "query-input": "required name=search_term_string"
  }
}
    </script>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/apple-touch-icon.png">
    <link rel="manifest" href="/assets/manifest.php">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400&family=Inter:wght@200;300;400;500;600&family=Noto+Naskh+Arabic:wght@400;500;700&family=Reem+Kufi:wght@400;500;600;700&family=Crimson+Text:wght@400;600;700&display=swap"
        rel="stylesheet">

    <!-- Iconify -->
    <script src="https://code.iconify.design/iconify-icon/1.0.8/iconify-icon.min.js"></script>
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="<?= $currentTheme ?>">

    <!-- Header -->
    <header class="welcome-header">
        <div class="welcome-header-content">
            <h1 class="logo-arabic">صَــدَى</h1>
            <p class="tagline"><?= htmlspecialchars($dynamicTagline ?: __('public.tagline')) ?></p>
        </div>
    </header>

    <!-- Main Content -->
    <main class="welcome-main">
        <div class="welcome-content-wrapper">
            <!-- Type Tabs -->
            <section class="type-tabs">
                <?php 
                $totalTypes = count($types);
                foreach ($types as $index => $type):
                    $active = $index === 0 ? 'active' : '';
                    $position = $index === 0 ? 'first' : ($index === $totalTypes - 1 ? 'last' : 'middle');
                    $name = getLocalizedValue($type['name'], $currentLang);
                    ?>
                    <button class="tab <?= $active ?> <?= $position ?>" data-type-id="<?= $type['id'] ?>">
                        <iconify-icon icon="<?= htmlspecialchars($type['icon']) ?>"></iconify-icon>
                        <span><?= htmlspecialchars($name) ?></span>
                    </button>
                <?php endforeach; ?>
            </section>

            <!-- Active Type Label (Mobile) -->
            <div class="active-type-label" id="active-type-label">
                <?= htmlspecialchars(getLocalizedValue($types[0]['name'], $currentLang)) ?>
            </div>

            <!-- Picker Section -->
            <section class="picker-section">
                <div class="picker-wrapper">
                    <button class="picker-arrow up" id="arrow-up" aria-label="Précédent" disabled>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 15l-6-6-6 6"></path>
                        </svg>
                    </button>

                    <div class="picker-viewport">
                        <div class="picker-track" id="picker-track">
                            <!-- Items injected by JS -->
                        </div>
                    </div>

                    <button class="picker-arrow down" id="arrow-down" aria-label="Suivant">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M6 9l6 6 6-6"></path>
                        </svg>
                    </button>
                </div>
            </section>

            <!-- Description Section -->
            <section class="description-section">
                <p class="profile-description" id="profile-description">
                    <?= __('public.select_intention') ?>
                </p>
            </section>

            <!-- CTA Button -->
            <section class="cta-section">
                <button class="cta-button" id="btn-start">
                    <?= __('actions.start') ?>
                </button>
            </section>
        </div>
    </main>

    <!-- Footer -->
    <footer class="welcome-footer">
        <button id="theme-toggle" class="theme-toggle" aria-label="<?= __('actions.toggle_theme') ?>">
            <svg class="icon-sun" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2">
                <circle cx="12" cy="12" r="5"></circle>
                <line x1="12" y1="1" x2="12" y2="3"></line>
                <line x1="12" y1="21" x2="12" y2="23"></line>
                <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                <line x1="1" y1="12" x2="3" y2="12"></line>
                <line x1="21" y1="12" x2="23" y2="12"></line>
                <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
            </svg>
            <svg class="icon-moon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2">
                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
            </svg>
        </button>

        <select id="lang-select" class="lang-select" onchange="changeLanguage(this.value)">
            <?php foreach ($languages as $lang):
                $langName = getLocalizedValue($lang['name'], $currentLang);
                ?>
                <option value="<?= $lang['code'] ?>" <?= $currentLang === $lang['code'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($langName) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </footer>

    <script>
        // Data passed from PHP
        const categoriesData = <?= json_encode($categoriesByType) ?>;
        const currentLang = '<?= $currentLang ?>';
        const translations = <?= json_encode(getJsTranslations()) ?>;

        // State
        let currentCategories = [];
        let currentIndex = 0;
        let currentTypeId = null;

        // Elements
        const track = document.getElementById('picker-track');
        const descEl = document.getElementById('profile-description');
        const arrowUp = document.getElementById('arrow-up');
        const arrowDown = document.getElementById('arrow-down');

        function getLocalized(jsonStr) {
            try {
                const obj = typeof jsonStr === 'string' ? JSON.parse(jsonStr) : jsonStr;
                return obj[currentLang] || obj['ar'] || obj['en'] || '';
            } catch (e) { return jsonStr; }
        }

        // Initialize content based on active tab
        function initType(typeId) {
            currentTypeId = typeId;
            currentCategories = categoriesData[typeId] || [];
            currentIndex = 0;
            renderPicker();
            updateSelection();
        }

        // Render picker items
        function renderPicker() {
            track.innerHTML = '';
            if (currentCategories.length === 0) {
                track.innerHTML = '<div class="picker-item active">-</div>';
                return;
            }

            currentCategories.forEach((cat, idx) => {
                const item = document.createElement('div');
                item.className = `picker-item ${idx === 0 ? 'active' : ''}`;
                item.dataset.index = idx;
                item.innerHTML = `
                    <iconify-icon icon="${cat.icon || 'mdi:tag'}"></iconify-icon>
                    ${getLocalized(cat.name)}
                `;
                item.onclick = () => {
                    currentIndex = idx;
                    updateSelection();
                };
                track.appendChild(item);
            });
        }

        // Update view state (transform, active classes, description, arrows)
        function updateSelection() {
            const items = track.querySelectorAll('.picker-item');

            // 1. Update active class
            items.forEach((item, i) => {
                item.classList.toggle('active', i === currentIndex);
            });

            // 2. Scroll track - Fixed positioning based on index
            // Force browser to recalculate layout after class changes
            void track.offsetHeight;
            
            // Get actual heights from computed styles to support responsive design
            const tempItem = items[0] || track.firstElementChild;
            const tempActive = items[currentIndex] || tempItem;
            
            // Force layout recalculation to ensure we get updated styles
            track.getBoundingClientRect();
            tempActive.getBoundingClientRect();
            
            // Get computed styles
            const itemStyles = window.getComputedStyle(tempItem);
            const activeStyles = window.getComputedStyle(tempActive);
            
            const ITEM_HEIGHT = parseFloat(itemStyles.height) || 40;
            const ACTIVE_HEIGHT = parseFloat(activeStyles.height) || 60;
            const VIEWPORT_HEIGHT = parseFloat(window.getComputedStyle(track.parentElement).height) || 120;
            const VIEWPORT_CENTER = VIEWPORT_HEIGHT / 2;
            
            // Calculate position: sum of normal items + half of active item height
            const position = (currentIndex * ITEM_HEIGHT) + (ACTIVE_HEIGHT / 2);
            const translateY = VIEWPORT_CENTER - position;
            
            track.style.transform = `translateY(${translateY}px)`;

            // 3. Update description
            if (currentCategories[currentIndex]) {
                descEl.textContent = getLocalized(currentCategories[currentIndex].description);
            } else {
                descEl.textContent = translations.no_category || "No category available";
            }

            // 4. Update arrows
            arrowUp.disabled = currentIndex === 0;
            arrowDown.disabled = currentIndex === currentCategories.length - 1;
        }

        // Events
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                initType(tab.dataset.typeId);
                
                // Update active type label for mobile
                const typeLabel = document.getElementById('active-type-label');
                const tabText = tab.querySelector('span');
                if (typeLabel && tabText) {
                    typeLabel.textContent = tabText.textContent;
                }
            });
        });

        arrowUp.addEventListener('click', () => {
            if (currentIndex > 0) { currentIndex--; updateSelection(); }
        });

        arrowDown.addEventListener('click', () => {
            if (currentIndex < currentCategories.length - 1) { currentIndex++; updateSelection(); }
        });

        // Theme Toggle
        document.getElementById('theme-toggle').addEventListener('click', () => {
            const html = document.documentElement;
            const body = document.body;
            const current = html.classList.contains('dark') ? 'dark' : 'light';
            const next = current === 'dark' ? 'light' : 'dark';

            html.classList.remove(current);
            html.classList.add(next);
            body.classList.remove(current);
            body.classList.add(next);
            document.cookie = `sadaa_theme=${next};path=/;max-age=31536000`;
        });

        // Language
        function changeLanguage(code) {
            document.cookie = `sadaa_lang=${code};path=/;max-age=31536000`;
            window.location.reload();
        }

        // Start
        document.getElementById('btn-start').addEventListener('click', () => {
            if (currentCategories[currentIndex]) {
                window.location.href = `/category/${currentCategories[currentIndex].slug}`;
            }
        });

        // Initialize
        const activeTab = document.querySelector('.tab.active');
        if (activeTab) {
            initType(activeTab.dataset.typeId);
            
            // Set initial active type label for mobile
            const typeLabel = document.getElementById('active-type-label');
            const tabText = activeTab.querySelector('span');
            if (typeLabel && tabText) {
                typeLabel.textContent = tabText.textContent;
            }
        }

        // Keyboard Navigation
        document.addEventListener('keydown', (e) => {
            // Only handle if no other interactive element is focused
            if (['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) return;

            switch (e.key) {
                case 'ArrowLeft':
                case 'ArrowRight':
                    // Switch Type
                    e.preventDefault();
                    navigateTypes(e.key === 'ArrowRight' ? 1 : -1);
                    break;
                case 'ArrowUp':
                case 'ArrowDown':
                    // Switch Category
                    e.preventDefault();
                    navigateCategories(e.key === 'ArrowDown' ? 1 : -1);
                    break;
                case 'Enter':
                    // Start
                    if (currentCategories[currentIndex]) {
                        window.location.href = `/category/${currentCategories[currentIndex].slug}`;
                    }
                    break;
            }
        });

        function navigateTypes(direction) {
            const tabs = Array.from(document.querySelectorAll('.tab'));
            const activeIndex = tabs.findIndex(t => t.classList.contains('active'));
            if (activeIndex === -1) return;

            let newIndex = activeIndex + direction;
            if (newIndex < 0) newIndex = tabs.length - 1;
            if (newIndex >= tabs.length) newIndex = 0;

            tabs[newIndex].click();
        }

        function navigateCategories(direction) {
            let newIndex = currentIndex + direction;
            if (newIndex >= 0 && newIndex < currentCategories.length) {
                currentIndex = newIndex;
                updateSelection();
            }
        }
    </script>
</body>

</html>