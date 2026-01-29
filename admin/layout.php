<?php
/**
 * Sadaa (صدى) - Admin Layout
 * 
 * Shared layout for admin pages with sidebar navigation
 */

// Start session for authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

// Handle logout
if (isset($_GET['logout'])) {
    $_SESSION['authenticated'] = false;
    header('Location: index.php');
    exit;
}


// Check authentication
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    if (isset($_POST['password'])) {
        $inputPassword = $_POST['password'];

        // Get hashed password from settings (fallback to 'admin123' hashed if not set)
        $hashedPassword = getSetting('admin_password');

        // If not in settings yet, allow 'admin123' as fallback constant for first time
        if (!$hashedPassword) {
            // Default: admin123
            $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        }

        if (password_verify($inputPassword, $hashedPassword)) {
            $_SESSION['authenticated'] = true;
        } else {
            $error = __('auth.wrong_password');
        }
    }

    if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
        // Show login form
        ?>
        <!DOCTYPE html>
        <html lang="<?= getCurrentLocale() ?>" dir="<?= isRtl() ? 'rtl' : 'ltr' ?>">

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?= __('auth.login') ?> - صدى Sadaa</title>
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
            <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
            <style>
                * {
                    box-sizing: border-box;
                    margin: 0;
                    padding: 0;
                }

                body {
                    font-family: 'Inter', sans-serif;
                    background: linear-gradient(135deg, #1a1a1a 0%, #0d0d0d 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }

                .login-card {
                    background: #1a1a1a;
                    border: 1px solid #333;
                    border-radius: 1rem;
                    padding: 2rem;
                    width: 100%;
                    max-width: 400px;
                    margin: 1rem;
                }

                .logo {
                    font-size: 3rem;
                    color: #C99B35;
                    text-align: center;
                    margin-bottom: 0.5rem;
                }

                .title {
                    color: #fff;
                    text-align: center;
                    margin-bottom: 2rem;
                    font-size: 1rem;
                    font-weight: 400;
                }

                .error {
                    background: #ff000020;
                    color: #ff6b6b;
                    padding: 0.75rem;
                    border-radius: 0.5rem;
                    margin-bottom: 1rem;
                    font-size: 0.875rem;
                }

                .form-group {
                    margin-bottom: 1rem;
                }

                label {
                    display: block;
                    color: #999;
                    font-size: 0.875rem;
                    margin-bottom: 0.5rem;
                }

                input {
                    width: 100%;
                    padding: 0.75rem 1rem;
                    background: #0d0d0d;
                    border: 1px solid #333;
                    border-radius: 0.5rem;
                    color: #fff;
                    font-size: 1rem;
                }

                input:focus {
                    outline: none;
                    border-color: #C99B35;
                }

                button {
                    width: 100%;
                    padding: 0.75rem;
                    background: #C99B35;
                    border: none;
                    border-radius: 0.5rem;
                    color: #fff;
                    font-size: 1rem;
                    font-weight: 500;
                    cursor: pointer;
                    transition: background 0.2s;
                }

                button:hover {
                    background: #B08A2D;
                }
            </style>
        </head>

        <body>
            <div class="login-card">
                <div class="logo">صدى</div>
                <p class="title"><?= __('auth.administration') ?></p>

                <?php if (isset($_POST['password'])): ?>
                    <div class="error"><?= __('auth.wrong_password') ?></div>
                <?php endif; ?>

                <form method="post">
                    <div class="form-group">
                        <label for="password"><?= __('auth.password') ?></label>
                        <input type="password" id="password" name="password" required autofocus>
                    </div>
                    <button type="submit">
                        <iconify-icon icon="mdi:login" style="vertical-align: middle; margin-right: 0.5rem;"></iconify-icon>
                        <?= __('auth.login') ?>
                    </button>
                </form>
            </div>
        </body>

        </html>
        <?php
        exit;
    }
}

// Define current page
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// Admin navigation items
$navItems = [
    ['page' => 'index', 'icon' => 'mdi:view-dashboard', 'label' => __('nav.dashboard')],
    ['page' => 'books', 'icon' => 'mdi:book-open-page-variant', 'label' => __('nav.books')],
    ['page' => 'types', 'icon' => 'mdi:shape', 'label' => __('nav.types')],
    ['page' => 'categories', 'icon' => 'mdi:tag-multiple', 'label' => __('nav.categories')],
    ['page' => 'assignments', 'icon' => 'mdi:link-variant', 'label' => __('nav.assignments')],
    ['page' => 'import', 'icon' => 'mdi:cloud-download', 'label' => __('nav.import')],
    ['page' => 'imports', 'icon' => 'mdi:history', 'label' => __('nav.history')],
    ['page' => 'backup', 'icon' => 'mdi:database-sync', 'label' => __('nav.backup')],
    ['page' => 'settings', 'icon' => 'mdi:cog', 'label' => __('nav.settings')],
];

// Function to render the layout
function adminHeader($title = 'Administration')
{
    global $currentPage, $navItems;
    $currentLang = getCurrentLocale();
    ?>
    <!DOCTYPE html>
    <html lang="<?= $currentLang ?>" dir="<?= isRtl() ? 'rtl' : 'ltr' ?>">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>
            <?= htmlspecialchars($title) ?> - صدى Sadaa Admin
        </title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link
            href="https://fonts.googleapis.com/css2?family=Noto+Naskh+Arabic:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap"
            rel="stylesheet">
        <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>

        <style>
            * {
                box-sizing: border-box;
                margin: 0;
                padding: 0;
            }

            :root {
                --color-primary: #C99B35;
                --color-primary-dark: #B08A2D;
                --bg-dark: #0d0d0d;
                --bg-card: #1a1a1a;
                --bg-hover: #252525;
                --text-primary: #f5f5f5;
                --text-secondary: #999;
                --border-color: #333;
            }

            body {
                font-family: 'Inter', sans-serif;
                background-color: var(--bg-dark);
                color: var(--text-primary);
                min-height: 100vh;
            }

            .admin-layout {
                display: flex;
                min-height: 100vh;
            }

            /* Sidebar */
            .sidebar {
                width: 250px;
                background-color: var(--bg-card);
                border-right: 1px solid var(--border-color);
                padding: 1.5rem 0;
                position: fixed;
                height: 100vh;
                overflow-y: auto;
            }

            .sidebar-logo {
                font-family: 'Noto Naskh Arabic', serif;
                font-size: 2.5rem;
                color: var(--color-primary);
                text-align: center;
                padding: 0 1.5rem 1.5rem;
                border-bottom: 1px solid var(--border-color);
            }

            .sidebar-nav {
                padding: 1rem 0;
            }

            .nav-item {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                padding: 0.75rem 1.5rem;
                color: var(--text-secondary);
                text-decoration: none;
                transition: all 0.2s;
            }

            .nav-item:hover {
                background-color: var(--bg-hover);
                color: var(--text-primary);
            }

            .nav-item.active {
                background-color: var(--color-primary);
                color: white;
            }

            .nav-item iconify-icon {
                font-size: 1.25rem;
            }

            .sidebar-footer {
                position: absolute;
                bottom: 0;
                left: 0;
                right: 0;
                padding: 1rem 1.5rem;
                border-top: 1px solid var(--border-color);
            }

            .logout-btn {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                color: var(--text-secondary);
                text-decoration: none;
                font-size: 0.875rem;
            }

            .logout-btn:hover {
                color: #ff6b6b;
            }

            /* Main Content */
            .main-content {
                flex: 1;
                margin-left: 250px;
                padding: 2rem;
            }

            .page-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 2rem;
            }

            .page-title {
                font-size: 1.5rem;
                font-weight: 600;
            }

            /* Cards */
            .card {
                background-color: var(--bg-card);
                border: 1px solid var(--border-color);
                border-radius: 0.75rem;
                padding: 1.5rem;
                margin-bottom: 1.5rem;
            }

            .card-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 1rem;
                padding-bottom: 1rem;
                border-bottom: 1px solid var(--border-color);
            }

            .card-title {
                font-size: 1.125rem;
                font-weight: 500;
            }

            /* Grid */
            .grid {
                display: grid;
                gap: 1rem;
            }

            .grid-2 {
                grid-template-columns: repeat(2, 1fr);
            }

            .grid-3 {
                grid-template-columns: repeat(3, 1fr);
            }

            .grid-4 {
                grid-template-columns: repeat(4, 1fr);
            }

            @media (max-width: 1024px) {
                .grid-4 {
                    grid-template-columns: repeat(2, 1fr);
                }
            }

            @media (max-width: 768px) {
                .sidebar {
                    display: none;
                }

                .main-content {
                    margin-left: 0;
                }

                .grid-2,
                .grid-3,
                .grid-4 {
                    grid-template-columns: 1fr;
                }
            }

            /* Stats Card */
            .stat-card {
                display: flex;
                align-items: center;
                gap: 1rem;
            }

            .stat-icon {
                width: 50px;
                height: 50px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: var(--color-primary);
                border-radius: 0.5rem;
                font-size: 1.5rem;
                color: white;
            }

            .stat-value {
                font-size: 1.75rem;
                font-weight: 600;
            }

            .stat-label {
                font-size: 0.875rem;
                color: var(--text-secondary);
            }

            /* Forms */
            .form-group {
                margin-bottom: 1rem;
            }

            .form-label {
                display: block;
                font-size: 0.875rem;
                color: var(--text-secondary);
                margin-bottom: 0.5rem;
            }

            .form-input,
            .form-select,
            .form-textarea {
                width: 100%;
                padding: 0.75rem 1rem;
                background-color: var(--bg-dark);
                border: 1px solid var(--border-color);
                border-radius: 0.5rem;
                color: var(--text-primary);
                font-family: inherit;
                font-size: 0.9rem;
            }

            .form-input:focus,
            .form-select:focus,
            .form-textarea:focus {
                outline: none;
                border-color: var(--color-primary);
            }

            .form-textarea {
                min-height: 100px;
                resize: vertical;
            }

            /* Buttons */
            .btn {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0.625rem 1.25rem;
                border-radius: 0.5rem;
                border: none;
                font-family: inherit;
                font-size: 0.875rem;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.2s;
                text-decoration: none;
            }

            .btn-primary {
                background-color: var(--color-primary);
                color: white;
            }

            .btn-primary:hover {
                background-color: var(--color-primary-dark);
            }

            .btn-secondary {
                background-color: var(--bg-hover);
                color: var(--text-primary);
            }

            .btn-secondary:hover {
                background-color: #333;
            }

            .btn-danger {
                background-color: #dc3545;
                color: white;
            }

            .btn-danger:hover {
                background-color: #c82333;
            }

            .btn-sm {
                padding: 0.375rem 0.75rem;
                font-size: 0.8rem;
            }

            /* Tables */
            .table {
                width: 100%;
                border-collapse: collapse;
            }

            .table th,
            .table td {
                padding: 0.75rem;
                text-align: left;
                border-bottom: 1px solid var(--border-color);
            }

            .table th {
                font-size: 0.75rem;
                text-transform: uppercase;
                color: var(--text-secondary);
                font-weight: 500;
            }

            .table tr:hover td {
                background-color: var(--bg-hover);
            }

            /* Alerts */
            .alert {
                padding: 0.75rem 1rem;
                border-radius: 0.5rem;
                margin-bottom: 1rem;
                font-size: 0.875rem;
            }

            .alert-success {
                background: #00800020;
                color: #4caf50;
            }

            .alert-error {
                background: #ff000020;
                color: #ff6b6b;
            }

            .alert-info {
                background: #0000ff20;
                color: #64b5f6;
            }

            /* Badge */
            .badge {
                display: inline-flex;
                align-items: center;
                gap: 0.25rem;
                padding: 0.25rem 0.5rem;
                border-radius: 9999px;
                font-size: 0.75rem;
                font-weight: 500;
            }

            .badge-primary {
                background: var(--color-primary);
                color: white;
            }

            .badge-success {
                background: #4caf50;
                color: white;
            }

            .badge-warning {
                background: #ff9800;
                color: white;
            }

            .badge-danger {
                background: #f44336;
                color: white;
            }

            /* Icon Picker */
            .icon-picker {
                display: flex;
                gap: 0.5rem;
                flex-wrap: wrap;
                padding: 0.5rem;
                background: var(--bg-dark);
                border-radius: 0.5rem;
                max-height: 200px;
                overflow-y: auto;
            }

            .icon-option {
                width: 40px;
                height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                border: 1px solid var(--border-color);
                border-radius: 0.5rem;
                cursor: pointer;
                font-size: 1.25rem;
                color: var(--text-secondary);
                transition: all 0.2s;
            }

            .icon-option:hover,
            .icon-option.selected {
                border-color: var(--color-primary);
                color: var(--color-primary);
                background: var(--bg-hover);
            }

            /* Utilities */
            .text-center {
                text-align: center;
            }

            .text-muted {
                color: var(--text-secondary);
            }

            .mt-1 {
                margin-top: 0.5rem;
            }

            .mt-2 {
                margin-top: 1rem;
            }

            .mb-1 {
                margin-bottom: 0.5rem;
            }

            .mb-2 {
                margin-bottom: 1rem;
            }

            .flex {
                display: flex;
            }

            .items-center {
                align-items: center;
            }

            .justify-between {
                justify-content: space-between;
            }

            .gap-1 {
                gap: 0.5rem;
            }

            .gap-2 {
                gap: 1rem;
            }

            .font-arabic {
                font-family: 'Noto Naskh Arabic', serif;
            }

            /* Language selector for admin */
            .lang-select-admin {
                width: 100%;
                padding: 0.5rem;
                margin-bottom: 0.75rem;
                background-color: var(--bg-dark);
                border: 1px solid var(--border-color);
                border-radius: 0.5rem;
                color: var(--text-primary);
                font-size: 0.875rem;
                cursor: pointer;
            }

            .lang-select-admin:focus {
                outline: none;
                border-color: var(--color-primary);
            }

            /* RTL Support */
            [dir="rtl"] .sidebar {
                right: 0;
                left: auto;
                border-right: none;
                border-left: 1px solid var(--border-color);
            }

            [dir="rtl"] .main-content {
                margin-left: 0;
                margin-right: 250px;
            }

            [dir="rtl"] .nav-item {
                flex-direction: row-reverse;
            }

            [dir="rtl"] .logout-btn {
                flex-direction: row-reverse;
            }

            [dir="rtl"] .table th,
            [dir="rtl"] .table td {
                text-align: right;
            }
        </style>
        <script>
            function changeAdminLang(code) {
                document.cookie = `sadaa_lang=${code};path=/;max-age=31536000`;
                window.location.reload();
            }
        </script>
    </head>

    <body>
        <div class="admin-layout">
            <!-- Sidebar -->
            <aside class="sidebar">
                <div class="sidebar-logo">صدى</div>
                <nav class="sidebar-nav">
                    <?php foreach ($navItems as $item): ?>
                        <a href="<?= $item['page'] ?>.php"
                            class="nav-item <?= $currentPage === $item['page'] ? 'active' : '' ?>">
                            <iconify-icon icon="<?= $item['icon'] ?>"></iconify-icon>
                            <span>
                                <?= $item['label'] ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </nav>
                <div class="sidebar-footer">
                    <select class="lang-select-admin" onchange="changeAdminLang(this.value)">
                        <?php
                        $languages = getActiveLanguages();
                        $currentLang = getCurrentLocale();
                        foreach ($languages as $lang):
                            $langName = getLocalizedValue($lang['name'], $currentLang);
                            ?>
                            <option value="<?= $lang['code'] ?>" <?= $currentLang === $lang['code'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($langName) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <a href="?logout=1" class="logout-btn">
                        <iconify-icon icon="mdi:logout"></iconify-icon>
                        <span><?= __('nav.logout') ?></span>
                    </a>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="main-content">
                <?php
}

function adminFooter()
{
    ?>
            </main>
        </div>
    </body>

    </html>
    <?php
}
