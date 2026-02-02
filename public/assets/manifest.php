<?php
/**
 * Sadaa (صدى) - Dynamic Web Manifest
 * Generates language-specific web app manifest
 */

// Set JSON content type
header('Content-Type: application/manifest+json');
header('Cache-Control: public, max-age=3600');

// Get current language from cookie or default to 'fr'
$lang = $_COOKIE['sadaa_lang'] ?? 'fr';

// Define translations for the manifest
$manifests = [
    'fr' => [
        'name' => 'Sadaa — Écho de la sagesse pour l’âme du Coran',
        'short_name' => 'Sadaa',
        'description' => 'Le Coran vous parle tel que vous êtes, résonne avec vos émotions et vous guide sur votre chemin.',
        'lang' => 'fr',
        'dir' => 'ltr'
    ],
    'en' => [
        'name' => 'Sadaa — Echo of Wisdom for the Soul from the Quran',
        'short_name' => 'Sadaa',
        'description' => 'The Quran speaks to you as you are, resonates with your emotions, and guides you on your journey.',
        'lang' => 'en',
        'dir' => 'ltr'
    ],
    'ar' => [
        'name' => 'صدى — صدى الحكمة لروح القرآن',
        'short_name' => 'صدى',
        'description' => 'القرآن يخاطبك كما أنت، ويواكب مشاعرك، ويهديك في مسيرتك.',
        'lang' => 'ar',
        'dir' => 'rtl'
    ],
    'es' => [
        'name' => 'Sadaa — Eco de la sabiduría para el alma del Corán',
        'short_name' => 'Sadaa',
        'description' => 'El Corán te habla tal como eres, acompaña tus emociones y te guía en tu camino.',
        'lang' => 'es',
        'dir' => 'ltr'
    ],
    'de' => [
        'name' => 'Sadaa — Echo der Weisheit für die Seele aus dem Koran',
        'short_name' => 'Sadaa',
        'description' => 'Der Koran spricht dich so an, wie du bist, begleitet deine Gefühle und leitet dich auf deinem Weg.',
        'lang' => 'de',
        'dir' => 'ltr'
    ]
];

// Get manifest for current language or fallback to French
$manifestData = $manifests[$lang] ?? $manifests['fr'];

// Build the complete manifest
$manifest = [
    'name' => $manifestData['name'],
    'short_name' => $manifestData['short_name'],
    'description' => $manifestData['description'],
    'start_url' => '/',
    'display' => 'standalone',
    'background_color' => '#0F0F0F',
    'theme_color' => '#C99B35',
    'orientation' => 'portrait',
    'icons' => [
        [
            'src' => '/assets/favicon.svg',
            'sizes' => '32x32',
            'type' => 'image/svg+xml'
        ],
        [
            'src' => '/assets/android-chrome-192x192.png',
            'sizes' => '192x192',
            'type' => 'image/png'
        ],
        [
            'src' => '/assets/android-chrome-512x512.png',
            'sizes' => '512x512',
            'type' => 'image/png'
        ]
    ],
    'lang' => $manifestData['lang'],
    'dir' => $manifestData['dir']
];

// Output JSON
echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);