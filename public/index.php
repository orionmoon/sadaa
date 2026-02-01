<?php
/**
 * Sadaa (صدى) - Home Page
 * Main landing page with correct design implementation
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
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>" class="<?= $currentTheme ?>" dir="<?= $currentLang === 'ar' ? 'rtl' : 'ltr' ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Sadaa | Écho Spirituel</title>

    <!-- Social Meta Tags -->
    <meta name="description"
        content="Découvrez les trésors du Coran à travers des thématiques inspirantes. Trouvez paix et guidance avec Sadaa.">
    <meta property="og:title" content="Sadaa | Écho Spirituel">
    <meta property="og:description"
        content="Explorez le Coran par thématique. Une expérience immersive et spirituelle.">
    <meta property="og:url" content="https://sadaa.me">
    <meta property="og:type" content="website">
    <meta property="og:image" content="https://sadaa.me/assets/og-image.jpg">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Sadaa | Écho Spirituel">
    <meta name="twitter:description"
        content="Explorez le Coran par thématique. Une expérience immersive et spirituelle.">
    <meta name="twitter:image" content="https://sadaa.me/assets/og-image.jpg">

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

<body class="<?= $currentTheme ?>" style="overflow: hidden;">

    <main class="welcome-container">

        <!-- Logo Section -->
        <header class="logo-section">
            <h1 class="logo-arabic">صَــدَى</h1>
            <p class="tagline"><?= __('public.tagline') ?></p>
        </header>

        <!-- Type Tabs -->
        <section class="type-tabs">
            <?php foreach ($types as $index => $type):
                $active = $index === 0 ? 'active' : '';
                $name = getLocalizedValue($type['name'], $currentLang);
                ?>
                <button class="tab <?= $active ?>" data-type-id="<?= $type['id'] ?>">
                    <iconify-icon icon="<?= htmlspecialchars($type['icon']) ?>"></iconify-icon>
                    <span><?= htmlspecialchars($name) ?></span>
                </button>
            <?php endforeach; ?>
        </section>

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

    </main>

    <!-- Footer -->
    <footer class="welcome-footer">
        <button id="theme-toggle" class="theme-toggle" aria-label="Changer thème">
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

            // 2. Scroll track
            // Height of item is 40px, active is 60px. Base is centered.
            // Simplified logic: translate so active item is centered in 120px viewport
            // Ideally: center of active item should be at 60px
            // Let's assume standard item height 40px + gap. 
            // Better: use relative index shift. 
            // Center is 60px.
            // If index 0 is active: translateY(0) -> assuming first item starts centered? 
            // Let's use simple logic: shift up by currentIndex * 50px roughly. 
            // We need to calculate exact height.
            // Let's rely on fixed heights defined in CSS.
            // Active item: 60px, others 40px. 
            // We want active item centered. Viewport is 120px. Center is 60px.
            // Active item center is at (previous items height) + 30px.
            // We need translateY = 60 - (previous items height + 30).

            let offset = 0;
            // Simply shift by index * 50px (approx) for now to simulate. 
            // Or better: (120/2) - (current item center position)
            // But item positions are dynamic because of scaling. 
            // Let's use a simpler fixed step for now: -50px per item.
            // Correct approach: 
            // center line - (index * 50px) - 30px (half active height) 
            // Actually, let's keep it simple: 
            // Translate = - (currentIndex * 50) + 35 (adjustment)
            const translateY = -(currentIndex * 50) + 30;
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
                window.location.href = `surah.php?category=${currentCategories[currentIndex].id}`;
            }
        });

        // Initialize
        const activeTab = document.querySelector('.tab.active');
        if (activeTab) initType(activeTab.dataset.typeId);

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
                        window.location.href = `surah.php?category=${currentCategories[currentIndex].id}`;
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