<?php
require_once __DIR__ . '/../config/db.php';
$currentTheme = $_COOKIE['sadaa_theme'] ?? 'light';
?>
<!DOCTYPE html>
<html lang="fr" class="<?= $currentTheme ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Sada | Écho Spirituel</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="">
    <link
        href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400&amp;family=Inter:wght@200;300;400;500&amp;family=Noto+Naskh+Arabic:wght@400;500;700&amp;family=Reem+Kufi:wght@400;500;600;700&amp;display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="css/style.css">
    <script src="https://code.iconify.design/3/3.1.0/iconify.min.js"></script>
</head>

<body dir="ltr">
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
                    <option value="ar">العربية</option>
                    <option value="fr" selected>Français</option>
                    <option value="en">English</option>
                    <option value="es">Español</option>
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
                    <option value="">Livre...</option>
                    <option value="quran">Le Saint Coran</option>
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
                <button class="tab" data-type="mood">
                    <iconify-icon icon="teenyicons:mood-flat-outline"></iconify-icon> <span>État d'esprit</span>
                </button>
                <button class="tab active" data-type="type">
                    <iconify-icon icon="iconamoon:profile-duotone"></iconify-icon> <span>type</span>
                </button>
                <button class="tab" data-type="sciences">
                    <iconify-icon icon="streamline:science-molecule-structure-bold"></iconify-icon>
                    <span>sciences</span>
                </button>
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
            <button id="modal-confirm" class="modal-confirm-btn">
                <span data-i18n="confirm">Confirmer</span>
            </button>
        </div>
    </div>

    <main class="main-viewport">
        <!-- 1. Metadata TOP -->
        <div class="top-section">
            <h1 id="surah-title" class="surah-title fade-in">Chargement...</h1>
            <div id="verse-ref" class="verse-ref fade-in"></div>
        </div>

        <!-- 2. Screen (16:9 Container) -->
        <div class="card-container">
            <div class="card-16-9">
                <div class="scroll-content" id="scroll-container">
                    <div id="verse-text" class="verse-arabic fade-in"></div>
                    <div id="translation-text" class="verse-translation fade-in"></div>
                </div>
                <div class="scroll-mask"></div>
            </div>
        </div>

        <!-- 3. Footer BOTTOM -->
        <div class="bottom-section">
            <div id="source-attribution" class="source-attribution fade-in"></div>
            <div class="nav-controls">
                <button id="btn-prev" class="btn-icon large" title="Précédent" disabled="">
                    <iconify-icon icon="mdi:chevron-left"></iconify-icon>
                </button>
                <button class="btn-icon" id="btn-copy" title="Copier">
                    <iconify-icon icon="mdi:content-copy"></iconify-icon>
                </button>
                <button id="btn-next" class="btn-icon large" title="Suivant">
                    <iconify-icon icon="mdi:chevron-right"></iconify-icon>
                </button>
            </div>
        </div>
    </main>

    <footer>
        <a href="index.php" class="footer-signature fade-in" title="Retour à l'accueil">
            <span class="arabic">صَــدَى</span>
        </a>
    </footer>

    <!-- Hidden select for backward compatibility -->
    <select id="category-select" style="display:none;"></select>

    <!-- JS -->
    <script src="js/app.js"></script>
</body>

</html>