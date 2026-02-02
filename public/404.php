<?php
/**
 * Sadaa (صدى) - Dynamic 404 Error Page
 * Multilingual 404 page with language detection
 */

// Get current language from cookie or default to 'fr'
$lang = $_COOKIE['sadaa_lang'] ?? 'fr';

// Define translations
$translations = [
    'fr' => [
        'title' => '404 - Page non trouvée | Sadaa',
        'meta_desc' => 'La page que vous recherchez n\'existe pas sur Sadaa.',
        'error_title' => 'Page non trouvée',
        'error_desc' => 'La page que vous recherchez semble avoir disparu dans le silence.<br>Retournez à l\'accueil pour continuer votre voyage spirituel.',
        'btn_home' => 'Retour à l\'accueil',
        'lang_attr' => 'fr',
        'dir_attr' => 'ltr'
    ],
    'en' => [
        'title' => '404 - Page Not Found | Sadaa',
        'meta_desc' => 'The page you are looking for does not exist on Sadaa.',
        'error_title' => 'Page Not Found',
        'error_desc' => 'The page you are looking for seems to have disappeared into silence.<br>Return to the homepage to continue your spiritual journey.',
        'btn_home' => 'Back to Home',
        'lang_attr' => 'en',
        'dir_attr' => 'ltr'
    ],
    'ar' => [
        'title' => '٤٠٤ - الصفحة غير موجودة | صدى',
        'meta_desc' => 'الصفحة التي تبحث عنها غير موجودة على صدى.',
        'error_title' => 'الصفحة غير موجودة',
        'error_desc' => 'الصفحة التي تبحث عنها يبدو أنها اختفت في الصمت.<br>عد إلى الصفحة الرئيسية لمواصلة رحلتك الروحية.',
        'btn_home' => 'العودة إلى الصفحة الرئيسية',
        'lang_attr' => 'ar',
        'dir_attr' => 'rtl'
    ],
    'es' => [
        'title' => '404 - Página no encontrada | Sadaa',
        'meta_desc' => 'La página que buscas no existe en Sadaa.',
        'error_title' => 'Página no encontrada',
        'error_desc' => 'La página que buscas parece haber desaparecido en el silencio.<br>Vuelve a la página de inicio para continuar tu viaje espiritual.',
        'btn_home' => 'Volver al inicio',
        'lang_attr' => 'es',
        'dir_attr' => 'ltr'
    ],
    'de' => [
        'title' => '404 - Seite nicht gefunden | Sadaa',
        'meta_desc' => 'Die Seite, die Sie suchen, existiert nicht auf Sadaa.',
        'error_title' => 'Seite nicht gefunden',
        'error_desc' => 'Die Seite, die Sie suchen, scheint in der Stille verschwunden zu sein.<br>Kehren Sie zur Startseite zurück, um Ihre spirituelle Reise fortzusetzen.',
        'btn_home' => 'Zurück zur Startseite',
        'lang_attr' => 'de',
        'dir_attr' => 'ltr'
    ]
];

// Get translation for current language or fallback to French
$t = $translations[$lang] ?? $translations['fr'];

// Determine logo display: use Reem Kufi font for Arabic, text for others
$is_arabic = $lang === 'ar';
?>
<!DOCTYPE html>
<html lang="<?php echo $t['lang_attr']; ?>" dir="<?php echo $t['dir_attr']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['title']; ?></title>
    <meta name="description" content="<?php echo $t['meta_desc']; ?>">
    <meta name="robots" content="noindex, follow">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Noto+Naskh+Arabic:wght@400;500;600;700&family=Reem+Kufi:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --color-primary: #C99B35;
            --color-primary-dark: #B08A2D;
            --bg-dark: #0F0F0F;
            --bg-card: #1A1A1A;
            --bg-hover: #252525;
            --text-primary: #F9FAFB;
            --text-secondary: #9CA3AF;
            --border-color: #2D2D2D;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #0d0d0d 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-primary);
        }
        
        .container {
            text-align: center;
            padding: 2rem;
            max-width: 600px;
        }
        
        .logo {
            font-family: 'Reem Kufi', sans-serif;
            font-size: 5rem;
            color: var(--color-primary);
            margin-bottom: 1rem;
            font-weight: 600;
            letter-spacing: 0.05em;
        }
        
        .logo-img {
            width: 120px;
            height: auto;
            margin-bottom: 1rem;
            opacity: 0.9;
        }
        
        .error-code {
            font-size: 8rem;
            font-weight: 700;
            color: var(--color-primary);
            opacity: 0.3;
            line-height: 1;
            margin-bottom: 1rem;
        }
        
        .error-title {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }
        
        .error-desc {
            font-size: 1rem;
            color: var(--text-secondary);
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: var(--color-primary);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .btn:hover {
            background: var(--color-primary-dark);
            transform: translateY(-2px);
        }

        /* RTL specific adjustments */
        [dir="rtl"] .btn span {
            display: inline-block;
            transform: scaleX(-1);
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($is_arabic): ?>
            <!-- Use Reem Kufi font for Arabic -->
            <div class="logo">صَــدَى</div>
        <?php else: ?>
            <!-- Use logo image for other languages -->
            <img src="/assets/favicon.svg" alt="Sadaa" class="logo-img">
        <?php endif; ?>
        
        <div class="error-code">404</div>
        <h1 class="error-title"><?php echo $t['error_title']; ?></h1>
        <p class="error-desc">
            <?php echo $t['error_desc']; ?>
        </p>
        <a href="/" class="btn">
            <span>←</span> <?php echo $t['btn_home']; ?>
        </a>
    </div>
</body>
</html>
