<?php
/**
 * Sadaa (صدى) - Home Page
 * Main landing page with header/content/footer layout
 */

require_once __DIR__ . '/../config/db.php';

// Get current language (using detection from i18n.php) and theme
$currentLang = getCurrentLocale();
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

// Fetch tabs visible count setting
$tabsVisibleCount = (int) getSetting('tabs_visible_count') ?: 5;

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
    <link rel="stylesheet" href="/css/style.css">

    <!-- Driver.js for Guided Tour -->
    <script src="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.js.iife.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.css" />
</head>

<body class="<?= $currentTheme ?>">

    <!-- Header -->
    <header class="welcome-header">
        <div class="welcome-header-content">
            <h1 class="logo-arabic">صَــدَى</h1>
            <div class="tagline-with-info">
                <p class="tagline"><?= htmlspecialchars($dynamicTagline ?: __('public.tagline')) ?></p>
                <button type="button" id="about-info-btn" class="info-btn" title="<?= __('public.about') ?>">
                    <iconify-icon icon="mdi:information-circle"></iconify-icon>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="welcome-main">
        <div class="welcome-content-wrapper">
            <!-- Type Tabs -->
            <?php $needsScroll = count($types) > $tabsVisibleCount; ?>
            <div class="type-tabs-container<?= $needsScroll ? ' scrollable' : '' ?>"
                style="--tabs-visible: <?= $tabsVisibleCount ?>;">
                <?php if ($needsScroll): ?>
                    <button class="tab-nav-arrow tab-nav-left" id="tab-nav-left" aria-label="<?= __('common.previous') ?>">
                        <iconify-icon icon="mdi:chevron-left"></iconify-icon>
                    </button>
                <?php endif; ?>

                <section class="type-tabs" id="type-tabs-scroll" data-visible-count="<?= $tabsVisibleCount ?>"
                    style="--tabs-visible: <?= $tabsVisibleCount ?>">
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

                <?php if ($needsScroll): ?>
                    <button class="tab-nav-arrow tab-nav-right" id="tab-nav-right" aria-label="<?= __('common.next') ?>">
                        <iconify-icon icon="mdi:chevron-right"></iconify-icon>
                    </button>
                <?php endif; ?>
            </div>

            <!-- Active Type Label (Mobile) -->
            <div class="active-type-label" id="active-type-label">
                <?= htmlspecialchars(getLocalizedValue($types[0]['name'], $currentLang)) ?>
            </div>

            <!-- Picker Section -->
            <section class="picker-section">
                <div class="picker-wrapper">
                    <button class="picker-arrow up" id="arrow-up" aria-label="Précédent" disabled>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path d="M18 15l-6-6-6 6"></path>
                        </svg>
                    </button>

                    <div class="picker-viewport">
                        <div class="picker-track" id="picker-track">
                            <!-- Items injected by JS -->
                        </div>
                    </div>

                    <button class="picker-arrow down" id="arrow-down" aria-label="Suivant">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
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

        <a href="https://github.com/orionmoon/sadaa" target="_blank" class="github-link"
            title="<?= __('public.github_link') ?>">
            <iconify-icon icon="mdi:github"></iconify-icon>
        </a>
    </footer>

    <script>
        // Data passed from PHP
        const categoriesData = <?= json_encode($categoriesByType) ?>;
        const currentLang = '<?= $currentLang ?>';
        const translations = <?= json_encode(array_merge(getJsTranslations(), [
            'onboarding' => getTranslationSection('onboarding')
        ])) ?>;

        // State
        let currentCategories = [];
        let currentIndex = 0;
        let currentTypeId = null;

        // Elements
        const track = document.getElementById('picker-track');
        const descEl = document.getElementById('profile-description');
        const arrowUp = document.getElementById('arrow-up');
        const arrowDown = document.getElementById('arrow-down');

        // Tab scroll navigation
        const tabsScroll = document.getElementById('type-tabs-scroll');
        const tabNavLeft = document.getElementById('tab-nav-left');
        const tabNavRight = document.getElementById('tab-nav-right');

        // Calculate and set container width based on actual tab widths
        function getEffectiveVisibleCount() {
            const configCount = parseInt(tabsScroll.dataset.visibleCount) || 5;
            if (window.innerWidth < 768) return 2;
            return configCount;
        }

        function updateTabsContainerWidth() {
            if (!tabsScroll) return;
            const visibleCount = getEffectiveVisibleCount();
            const tabs = tabsScroll.querySelectorAll('.tab');
            if (tabs.length <= visibleCount) {
                tabsScroll.style.maxWidth = 'none';
                return;
            }
            const style = window.getComputedStyle(tabsScroll);
            const gap = parseFloat(style.gap) || parseFloat(style.columnGap) || 8;
            let totalWidth = 0;
            const countToMeasure = Math.min(visibleCount, tabs.length);
            for (let i = 0; i < countToMeasure; i++) {
                totalWidth += tabs[i].offsetWidth;
            }
            if (countToMeasure > 1) {
                totalWidth += gap * (countToMeasure - 1);
            }
            tabsScroll.style.maxWidth = totalWidth + 'px';
        }

        function updateTabNavArrows() {
            if (!tabsScroll || !tabNavLeft || !tabNavRight) return;
            const isRtl = document.documentElement.dir === 'rtl';
            const scrollLeft = tabsScroll.scrollLeft;
            const maxScroll = tabsScroll.scrollWidth - tabsScroll.clientWidth;
            if (isRtl) {
                tabNavRight.disabled = scrollLeft >= 0;
                tabNavLeft.disabled = scrollLeft <= -maxScroll + 5;
            } else {
                tabNavLeft.disabled = scrollLeft <= 5;
                tabNavRight.disabled = scrollLeft >= maxScroll - 5;
            }
        }

        function scrollTabsBy(direction) {
            if (!tabsScroll) return;
            const visibleCount = getEffectiveVisibleCount();
            const tabs = tabsScroll.querySelectorAll('.tab');
            let scrollAmount = 0;
            for (let i = 0; i < Math.min(visibleCount, tabs.length); i++) {
                scrollAmount += tabs[i].offsetWidth;
            }
            tabsScroll.scrollBy({ left: direction * scrollAmount, behavior: 'smooth' });
            setTimeout(updateTabNavArrows, 300);
        }

        if (tabsScroll) {
            if (document.fonts && document.fonts.ready) {
                document.fonts.ready.then(updateTabsContainerWidth);
            } else {
                setTimeout(updateTabsContainerWidth, 100);
            }
            if (tabNavLeft && tabNavRight) {
                tabNavLeft.addEventListener('click', () => scrollTabsBy(-1));
                tabNavRight.addEventListener('click', () => scrollTabsBy(1));
                tabsScroll.addEventListener('scroll', updateTabNavArrows);
                setTimeout(updateTabNavArrows, 150);
            }
            window.addEventListener('resize', updateTabsContainerWidth);
        }

        function getLocalized(jsonStr) {
            try {
                const obj = typeof jsonStr === 'string' ? JSON.parse(jsonStr) : jsonStr;
                return obj[currentLang] || obj['ar'] || obj['en'] || '';
            } catch (e) { return jsonStr; }
        }

        function initType(typeId) {
            currentTypeId = typeId;
            currentCategories = categoriesData[typeId] || [];
            currentIndex = 0;
            renderPicker();
            updateSelection();
        }

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
                    // Navigate directly on click
                    window.location.href = `/category/${cat.slug}`;
                };
                track.appendChild(item);
            });
        }

        function updateSelection() {
            const items = track.querySelectorAll('.picker-item');
            if (items.length === 0) return;

            items.forEach((item, i) => {
                item.classList.toggle('active', i === currentIndex);
            });

            void track.offsetHeight;
            const tempItem = items[0] || track.firstElementChild;
            const tempActive = items[currentIndex] || tempItem;
            if (!tempItem || !tempActive) return;

            const itemStyles = window.getComputedStyle(tempItem);
            const activeStyles = window.getComputedStyle(tempActive);
            const ITEM_HEIGHT = parseFloat(itemStyles.height) || 40;
            const ACTIVE_HEIGHT = parseFloat(activeStyles.height) || 60;
            const VIEWPORT_HEIGHT = parseFloat(window.getComputedStyle(track.parentElement).height) || 120;
            const VIEWPORT_CENTER = VIEWPORT_HEIGHT / 2;
            const position = (currentIndex * ITEM_HEIGHT) + (ACTIVE_HEIGHT / 2);
            const translateY = VIEWPORT_CENTER - position;
            track.style.transform = `translateY(${translateY}px)`;

            if (currentCategories[currentIndex]) {
                descEl.textContent = getLocalized(currentCategories[currentIndex].description);
            } else {
                descEl.textContent = translations.no_category || "No category available";
            }
            arrowUp.disabled = currentIndex === 0;
            arrowDown.disabled = currentIndex === currentCategories.length - 1;
        }

        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                initType(tab.dataset.typeId);
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

        document.getElementById('theme-toggle').addEventListener('click', () => {
            const html = document.documentElement;
            const current = html.classList.contains('dark') ? 'dark' : 'light';
            const next = current === 'dark' ? 'light' : 'dark';
            html.classList.remove(current);
            html.classList.add(next);
            document.body.classList.remove(current);
            document.body.classList.add(next);
            document.cookie = `sadaa_theme=${next};path=/;max-age=31536000`;
        });

        function changeLanguage(code) {
            document.cookie = `sadaa_lang=${code};path=/;max-age=31536000`;
            window.location.reload();
        }

        document.getElementById('btn-start').addEventListener('click', () => {
            if (currentCategories[currentIndex]) {
                window.location.href = `/category/${currentCategories[currentIndex].slug}`;
            }
        });

        const activeTab = document.querySelector('.tab.active');
        if (activeTab) {
            initType(activeTab.dataset.typeId);
            const typeLabel = document.getElementById('active-type-label');
            const tabText = activeTab.querySelector('span');
            if (typeLabel && tabText) {
                typeLabel.textContent = tabText.textContent;
            }
        }

        document.addEventListener('keydown', (e) => {
            if (['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) return;
            switch (e.key) {
                case 'ArrowLeft':
                case 'ArrowRight':
                    e.preventDefault();
                    navigateTypes(e.key === 'ArrowRight' ? 1 : -1);
                    break;
                case 'ArrowUp':
                case 'ArrowDown':
                    e.preventDefault();
                    navigateCategories(e.key === 'ArrowDown' ? 1 : -1);
                    break;
                case 'Enter':
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
            let newIndex = (activeIndex + direction + tabs.length) % tabs.length;
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
                        $allowedTags = '<h1><h2><h3><h4><h5><h6><p><br><strong><b><em>|<i><ul><ol><li>';
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

    <!-- Onboarding Modal -->
    <div id="onboarding-modal">
        <div class="onboarding-content">
            <div class="onboarding-logo">
                <span>صدى</span>
            </div>
            <h2 class="onboarding-title"><?= __('onboarding.welcome_title') ?></h2>
            <p class="onboarding-text"><?= __('onboarding.welcome_text') ?></p>
            <div class="onboarding-footer">
                <button id="close-onboarding" class="btn-onboarding">
                    <?= __('onboarding.btn_start') ?>
                </button>
            </div>
        </div>
    </div>

    <script>
        // About modal
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

        // Initialize onboarding
        const onboardingModal = document.getElementById('onboarding-modal');
        const closeOnboardingBtn = document.getElementById('close-onboarding');

        function startTour() {
            const driver = window.driver.js.driver({
                showProgress: true,
                animate: true,
                padding: 10,
                opacity: 0.75,
                nextBtnText: translations.onboarding.btn_next,
                prevBtnText: translations.onboarding.btn_prev,
                doneBtnText: translations.onboarding.btn_done,
                steps: [
                    {
                        element: '.type-tabs-container',
                        popover: {
                            title: translations.onboarding.steps.themes.title,
                            description: translations.onboarding.steps.themes.text,
                            side: "bottom",
                            align: 'start'
                        }
                    },
                    {
                        element: '.picker-section',
                        popover: {
                            title: translations.onboarding.steps.intentions.title,
                            description: translations.onboarding.steps.intentions.text,
                            side: "top",
                            align: 'center'
                        }
                    },
                    {
                        element: '.welcome-footer',
                        popover: {
                            title: translations.onboarding.steps.display.title,
                            description: translations.onboarding.steps.display.text,
                            side: "top",
                            align: 'center'
                        }
                    },
                    {
                        element: '.github-link',
                        popover: {
                            title: translations.onboarding.steps.github.title,
                            description: translations.onboarding.steps.github.text,
                            side: "top",
                            align: 'center'
                        }
                    },
                    {
                        element: '.cta-button',
                        popover: {
                            title: translations.onboarding.steps.go.title,
                            description: translations.onboarding.steps.go.text,
                            side: "top",
                            align: 'center'
                        }
                    }
                ]
            });

            driver.drive();
        }

        function initOnboarding() {
            const onboardingSeen = localStorage.getItem('sadaa_onboarding_seen');
            if (!onboardingSeen) {
                setTimeout(() => {
                    onboardingModal.classList.add('active');
                    document.body.style.overflow = 'hidden';
                }, 1000);
            }
        }

        if (closeOnboardingBtn) {
            closeOnboardingBtn.addEventListener('click', () => {
                onboardingModal.classList.remove('active');
                document.body.style.overflow = '';
                localStorage.setItem('sadaa_onboarding_seen', 'true');
                setTimeout(() => {
                    onboardingModal.style.display = 'none';
                    startTour();
                }, 500);
            });
        }

        initOnboarding();
    </script>
</body>

</html>