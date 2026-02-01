<?php
/**
 * Sadaa (صدى) - Settings
 */

require_once __DIR__ . '/layout.php';

$message = '';
$error = '';

// Get languages FIRST (needed for form processing)
$languages = [];
try {
    $stmt = $pdo->query("SELECT * FROM languages ORDER BY sort_order ASC");
    $languages = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Erreur chargement langues: ' . $e->getMessage();
}

// Handle form submissions
if ($_POST) {
    if (isset($_POST['save_tagline'])) {
        try {
            $taglineArray = [];
            foreach ($languages as $lang) {
                $taglineArray[$lang['code']] = trim($_POST['tagline_' . $lang['code']] ?? '');
            }
            $taglineJson = json_encode($taglineArray);

            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('tagline', ?) 
                                   ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$taglineJson, $taglineJson]);

            $message = 'Slogan enregistré';
        } catch (PDOException $e) {
            $error = 'Erreur: ' . $e->getMessage();
        }
    }

    if (isset($_POST['save_settings'])) {
        try {
            // Update settings
            $settings = [
                'app_name' => $_POST['app_name'] ?? 'Sadaa',
                'default_language' => $_POST['default_language'] ?? 'fr',
                'primary_color' => $_POST['primary_color'] ?? '#C99B35',
            ];

            foreach ($settings as $key => $value) {
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                                       ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                $stmt->execute([$key, $value]);
            }

            $message = 'Paramètres enregistrés';
        } catch (PDOException $e) {
            $error = 'Erreur: ' . $e->getMessage();
        }
    }

    if (isset($_POST['toggle_language'])) {
        try {
            $langId = (int) $_POST['language_id'];
            $isActive = (int) $_POST['is_active'];

            // Don't allow disabling Arabic (source language)
            $stmt = $pdo->prepare("SELECT is_source FROM languages WHERE id = ?");
            $stmt->execute([$langId]);
            $isSource = $stmt->fetchColumn();

            if ($isSource && !$isActive) {
                $error = 'La langue source (Arabe) ne peut pas être désactivée';
            } else {
                $stmt = $pdo->prepare("UPDATE languages SET is_active = ? WHERE id = ?");
                $stmt->execute([$isActive, $langId]);
                $message = 'Langue mise à jour';
            }
        } catch (PDOException $e) {
            $error = 'Erreur: ' . $e->getMessage();
        }
    }

    if (isset($_POST['save_language'])) {
        try {
            $id = !empty($_POST['language_id']) ? (int) $_POST['language_id'] : null;
            $code = trim($_POST['lang_code'] ?? '');

            // Build name array dynamically from all active languages
            $nameArray = [];
            foreach ($languages as $lang) {
                $langCode = $lang['code'];
                $nameArray[$langCode] = trim($_POST['lang_name_' . $langCode] ?? '');
            }
            $name = json_encode($nameArray);

            $edition = trim($_POST['quran_edition'] ?? '');
            $isRtl = isset($_POST['is_rtl']) ? 1 : 0;

            if ($id) {
                // Update existing
                $stmt = $pdo->prepare("UPDATE languages SET code = ?, name = ?, quran_edition = ?, is_rtl = ? WHERE id = ?");
                $stmt->execute([$code, $name, $edition, $isRtl, $id]);
                $message = 'Langue mise à jour';
            } else {
                // Insert new
                $stmt = $pdo->prepare("INSERT INTO languages (code, name, quran_edition, is_rtl, is_active, sort_order) 
                                       VALUES (?, ?, ?, ?, 1, (SELECT COALESCE(MAX(l.sort_order), 0) + 1 FROM languages l))");
                $stmt->execute([$code, $name, $edition, $isRtl]);
                $message = 'Langue ajoutée';
            }
        } catch (PDOException $e) {
            $error = 'Erreur: ' . $e->getMessage();
        }
    }

    if (isset($_POST['change_password'])) {
        $newPassword = $_POST['new_password'] ?? '';
        if (strlen($newPassword) < 6) {
            $error = 'Le mot de passe doit contenir au moins 6 caractères';
        } else {
            try {
                $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('admin_password', ?) 
                                       ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->execute([$hashed, $hashed]);
                $message = 'Le mot de passe a été mis à jour avec succès';
            } catch (PDOException $e) {
                $error = 'Erreur: ' . $e->getMessage();
            }
        }
    }
}

// Get current settings
$settings = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
}

adminHeader('Paramètres');
?>

<div class="page-header">
    <h1 class="page-title">Paramètres</h1>
</div>

<?php if ($message): ?>
    <div class="alert alert-success">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<!-- General Settings -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <iconify-icon icon="mdi:cog"></iconify-icon>
            Paramètres généraux
        </h2>
    </div>
    <form method="post">
        <div class="grid grid-3">
            <div class="form-group">
                <label class="form-label">Nom de l'application</label>
                <input type="text" name="app_name" class="form-input"
                    value="<?= htmlspecialchars($settings['app_name'] ?? 'Sadaa') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Langue par défaut</label>
                <select name="default_language" class="form-select">
                    <?php foreach ($languages as $lang):
                        $langName = json_decode($lang['name'], true);
                        ?>
                        <option value="<?= $lang['code'] ?>" <?= ($settings['default_language'] ?? 'fr') === $lang['code'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($langName['fr'] ?? $lang['code']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Couleur principale</label>
                <input type="color" name="primary_color" class="form-input"
                    value="<?= htmlspecialchars($settings['primary_color'] ?? '#C99B35') ?>"
                    style="height: 42px; padding: 0.25rem;">
            </div>
        </div>

        <!-- Tagline translations -->
        <div class="mb-2" style="margin-top: 1rem;">
            <label class="form-label" style="margin-bottom: 0.5rem;">
                <iconify-icon icon="mdi:text"></iconify-icon>
                Slogan (multilingue)
            </label>
            <div class="grid grid-2">
                <?php
                $taglineSetting = json_decode($settings['tagline'] ?? '{}', true);
                foreach ($languages as $lang):
                    $langName = json_decode($lang['name'], true);
                    $isRtlLang = $lang['is_rtl'];
                    $currentTagline = $taglineSetting[$lang['code']] ?? '';
                    if (empty($currentTagline)) {
                        $currentTagline = match($lang['code']) {
                            'ar' => 'صدى الحكمة للروح',
                            'fr' => 'Écho de sagesse pour l\'âme',
                            'en' => 'Echo of wisdom for the soul',
                            'es' => 'Eco de sabiduría para el alma',
                            'de' => 'Echo der Weisheit für die Seele',
                            default => '',
                        };
                    }
                    ?>
                    <div class="form-group">
                        <label class="form-label"><?= htmlspecialchars($langName['fr'] ?? $lang['code']) ?>
                            (<?= strtoupper($lang['code']) ?>)</label>
                        <input type="text" name="tagline_<?= $lang['code'] ?>" class="form-input<?= $isRtlLang ? ' font-arabic' : '' ?>"
                            value="<?= htmlspecialchars($currentTagline) ?>" <?= $isRtlLang ? 'dir="rtl"' : '' ?>>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <button type="submit" name="save_tagline" class="btn btn-primary mb-2">
            <iconify-icon icon="mdi:content-save"></iconify-icon>
            Enregistrer le slogan
        </button>

        <button type="submit" name="save_settings" class="btn btn-primary">
            <iconify-icon icon="mdi:content-save"></iconify-icon>
            Enregistrer
        </button>
    </form>
</div>

<!-- Languages -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <iconify-icon icon="mdi:translate"></iconify-icon>
            Langues
        </h2>
    </div>

    <table class="table mb-2">
        <thead>
            <tr>
                <th>Code</th>
                <th>Nom</th>
                <th>Édition Coran</th>
                <th>RTL</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($languages as $lang):
                $langName = json_decode($lang['name'], true);
                $langJson = htmlspecialchars(json_encode($lang), ENT_QUOTES, 'UTF-8');
                ?>
                <tr>
                    <td><code><?= strtoupper($lang['code']) ?></code></td>
                    <td>
                        <?= htmlspecialchars($langName['fr'] ?? '') ?>
                        <span class="text-muted"> /
                            <?= htmlspecialchars($langName['ar'] ?? '') ?>
                        </span>
                    </td>
                    <td><code><?= htmlspecialchars($lang['quran_edition'] ?? 'N/A') ?></code></td>
                    <td>
                        <?= $lang['is_rtl'] ? '✓' : '-' ?>
                    </td>
                    <td>
                        <?php if ($lang['is_source']): ?>
                            <span class="badge badge-success">Source</span>
                        <?php elseif ($lang['is_active']): ?>
                            <span class="badge badge-primary">Actif</span>
                        <?php else: ?>
                            <span class="badge" style="background: #666;">Inactif</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display: flex; gap: 0.5rem;">
                            <button type="button" class="btn btn-sm btn-secondary" onclick="editLanguage(<?= $langJson ?>)">
                                <iconify-icon icon="mdi:pencil"></iconify-icon>
                                Éditer
                            </button>

                            <?php if (!$lang['is_source']): ?>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="language_id" value="<?= $lang['id'] ?>">
                                    <input type="hidden" name="is_active" value="<?= $lang['is_active'] ? 0 : 1 ?>">
                                    <button type="submit" name="toggle_language"
                                        class="btn btn-sm <?= $lang['is_active'] ? 'btn-danger' : 'btn-primary' ?>">
                                        <?= $lang['is_active'] ? 'Désactiver' : 'Activer' ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Add/Edit Language -->
    <?php
    // Available editions from AlQuran.cloud API (grouped by language)
    $availableEditions = [
        'ar' => [
            'name' => 'العربية (Arabic)',
            'native' => 'العربية',
            'rtl' => true,
            'editions' => [
                'quran-uthmani' => 'Uthmani Script',
                'quran-simple' => 'Simple Script',
                'quran-simple-enhanced' => 'Simple Enhanced',
            ]
        ],
        'fr' => [
            'name' => 'Français (French)',
            'native' => 'Français',
            'rtl' => false,
            'editions' => [
                'fr.hamidullah' => 'Muhammad Hamidullah',
                'fr.leclerc' => 'Jean Leclerc',
            ]
        ],
        'en' => [
            'name' => 'English',
            'native' => 'English',
            'rtl' => false,
            'editions' => [
                'en.sahih' => 'Saheeh International',
                'en.ahmedali' => 'Ahmed Ali',
                'en.arberry' => 'A. J. Arberry',
                'en.asad' => 'Muhammad Asad',
                'en.daryabadi' => 'Abdul Majid Daryabadi',
                'en.hilali' => 'Hilali & Khan',
                'en.pickthall' => 'Pickthall',
                'en.yusufali' => 'Yusuf Ali',
            ]
        ],
        'es' => [
            'name' => 'Español (Spanish)',
            'native' => 'Español',
            'rtl' => false,
            'editions' => [
                'es.cortes' => 'Julio Cortes',
                'es.asad' => 'Muhammad Asad',
            ]
        ],
        'de' => [
            'name' => 'Deutsch (German)',
            'native' => 'Deutsch',
            'rtl' => false,
            'editions' => [
                'de.aburida' => 'Abu Rida',
                'de.bubenheim' => 'Bubenheim & Elyas',
                'de.khoury' => 'Adel Theodor Khoury',
            ]
        ],
        'tr' => [
            'name' => 'Türkçe (Turkish)',
            'native' => 'Türkçe',
            'rtl' => false,
            'editions' => [
                'tr.diyanet' => 'Diyanet İşleri',
                'tr.yazir' => 'Elmalılı Hamdi Yazır',
                'tr.yildirim' => 'Suat Yıldırım',
            ]
        ],
        'id' => [
            'name' => 'Bahasa Indonesia',
            'native' => 'Bahasa Indonesia',
            'rtl' => false,
            'editions' => [
                'id.indonesian' => 'Indonesian Ministry',
                'id.muntakhab' => 'Quraish Shihab',
            ]
        ],
        'ur' => [
            'name' => 'اردو (Urdu)',
            'native' => 'اردو',
            'rtl' => true,
            'editions' => [
                'ur.jalandhry' => 'Fateh Muhammad Jalandhry',
                'ur.ahmedali' => 'Ahmed Ali',
                'ur.junagarhi' => 'Muhammad Junagarhi',
            ]
        ],
        'bn' => [
            'name' => 'বাংলা (Bengali)',
            'native' => 'বাংলা',
            'rtl' => false,
            'editions' => [
                'bn.bengali' => 'Muhiuddin Khan',
                'bn.hoque' => 'Zohurul Hoque',
            ]
        ],
        'ru' => [
            'name' => 'Русский (Russian)',
            'native' => 'Русский',
            'rtl' => false,
            'editions' => [
                'ru.kuliev' => 'Elmir Kuliev',
                'ru.osmanov' => 'Magomed-Nuri Osmanov',
                'ru.krachkovsky' => 'Ignaty Krachkovsky',
            ]
        ],
        'pt' => [
            'name' => 'Português (Portuguese)',
            'native' => 'Português',
            'rtl' => false,
            'editions' => [
                'pt.elhayek' => 'Samir El-Hayek',
            ]
        ],
        'nl' => [
            'name' => 'Nederlands (Dutch)',
            'native' => 'Nederlands',
            'rtl' => false,
            'editions' => [
                'nl.keyzer' => 'Salomo Keyzer',
            ]
        ],
        'it' => [
            'name' => 'Italiano (Italian)',
            'native' => 'Italiano',
            'rtl' => false,
            'editions' => [
                'it.piccardo' => 'Hamza Piccardo',
            ]
        ],
        'fa' => [
            'name' => 'فارسی (Persian)',
            'native' => 'فارسی',
            'rtl' => true,
            'editions' => [
                'fa.makarem' => 'Makarem Shirazi',
                'fa.ansarian' => 'Hussain Ansarian',
                'fa.ayati' => 'AbdolMohammad Ayati',
                'fa.fooladvand' => 'Mohammad Mahdi Fooladvand',
            ]
        ],
        'zh' => [
            'name' => '中文 (Chinese)',
            'native' => '中文',
            'rtl' => false,
            'editions' => [
                'zh.majian' => 'Ma Jian',
            ]
        ],
        'ja' => [
            'name' => '日本語 (Japanese)',
            'native' => '日本語',
            'rtl' => false,
            'editions' => [
                'ja.japanese' => 'Japanese Translation',
            ]
        ],
        'ko' => [
            'name' => '한국어 (Korean)',
            'native' => '한국어',
            'rtl' => false,
            'editions' => [
                'ko.korean' => 'Korean Translation',
            ]
        ],
        'ml' => [
            'name' => 'മലയാളം (Malayalam)',
            'native' => 'മലയാളം',
            'rtl' => false,
            'editions' => [
                'ml.abdulhameed' => 'Cheriyamundam Abdul Hameed',
            ]
        ],
        'sw' => [
            'name' => 'Kiswahili (Swahili)',
            'native' => 'Kiswahili',
            'rtl' => false,
            'editions' => [
                'sw.barwani' => 'Ali Muhsin Al-Barwani',
            ]
        ],
        'th' => [
            'name' => 'ภาษาไทย (Thai)',
            'native' => 'ภาษาไทย',
            'rtl' => false,
            'editions' => [
                'th.thai' => 'Thai Translation',
            ]
        ],
    ];
    ?>
    <details id="langFormDetails" style="margin-top: 1rem;">
        <summary id="langFormSummary" style="cursor: pointer; color: var(--color-primary); font-weight: 500;">
            <iconify-icon icon="mdi:plus"></iconify-icon>
            Ajouter une langue
        </summary>
        <form id="langForm" method="post"
            style="margin-top: 1rem; padding: 1rem; background: var(--bg-dark); border-radius: 0.5rem;">
            <input type="hidden" name="language_id" id="lang_id">
            <input type="hidden" name="lang_code" id="lang_code">

            <div class="grid grid-3">
                <div class="form-group">
                    <label class="form-label">Langue</label>
                    <select id="lang_selector" class="form-input" onchange="onLanguageSelect()" required>
                        <option value="">-- Sélectionner une langue --</option>
                        <?php foreach ($availableEditions as $code => $langData): ?>
                            <option value="<?= $code ?>" data-rtl="<?= $langData['rtl'] ? '1' : '0' ?>"
                                data-native="<?= htmlspecialchars($langData['native']) ?>">
                                <?= htmlspecialchars($langData['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Édition Coran</label>
                    <select name="quran_edition" id="quran_edition" class="form-input" required>
                        <option value="">-- Choisir d'abord une langue --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">RTL (droite à gauche)</label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem;">
                        <input type="checkbox" name="is_rtl" id="is_rtl" style="accent-color: var(--color-primary);">
                        <span>Oui (détecté automatiquement)</span>
                    </label>
                </div>
            </div>
            <!-- Dynamic Name Fields for all active languages -->
            <div class="grid grid-3">
                <?php foreach ($languages as $lang):
                    $langName = json_decode($lang['name'], true);
                    $isRtlLang = $lang['is_rtl'];
                    $isRequired = $lang['code'] === 'fr';
                    ?>
                    <div class="form-group">
                        <label class="form-label">Nom
                            (<?= htmlspecialchars($langName['fr'] ?? $lang['code']) ?>)<?= $isRequired ? ' *' : '' ?></label>
                        <input type="text" name="lang_name_<?= $lang['code'] ?>" id="lang_name_<?= $lang['code'] ?>"
                            class="form-input<?= $isRtlLang ? ' font-arabic' : '' ?>" <?= $isRtlLang ? 'dir="rtl"' : '' ?>
                            <?= $isRequired ? 'required' : '' ?>>
                    </div>
                <?php endforeach; ?>
            </div>
            <div style="display: flex; gap: 1rem; align-items: center;">
                <button type="submit" name="save_language" class="btn btn-primary">
                    <iconify-icon icon="mdi:content-save"></iconify-icon>
                    <span id="langSubmitText">Enregistrer</span>
                </button>
                <button type="button" class="btn btn-sm btn-secondary" onclick="resetLangForm()" id="resetLangBtn"
                    style="display: none;">
                    Annuler
                </button>
            </div>
        </form>
    </details>
</div>

<script>
    // Language codes for dynamic form handling
    const languageCodes = <?= json_encode(array_column($languages, 'code')) ?>;

    // Available editions data from PHP
    const availableEditions = <?= json_encode($availableEditions) ?>;

    // Handle language selection
    function onLanguageSelect() {
        const selector = document.getElementById('lang_selector');
        const editionSelect = document.getElementById('quran_edition');
        const langCode = selector.value;
        const langCodeInput = document.getElementById('lang_code');

        // Update hidden lang_code field
        langCodeInput.value = langCode;

        // Clear edition options
        editionSelect.innerHTML = '';

        if (!langCode) {
            editionSelect.innerHTML = '<option value="">-- Choisir d\'abord une langue --</option>';
            return;
        }

        // Get selected option data
        const selectedOption = selector.options[selector.selectedIndex];
        const isRtl = selectedOption.dataset.rtl === '1';
        const nativeName = selectedOption.dataset.native;

        // Auto-set RTL checkbox
        document.getElementById('is_rtl').checked = isRtl;

        // Auto-fill native name in French field if empty
        const frNameField = document.getElementById('lang_name_fr');
        if (frNameField && !frNameField.value) {
            frNameField.value = nativeName;
        }

        // Populate editions for this language
        const langData = availableEditions[langCode];
        if (langData && langData.editions) {
            editionSelect.innerHTML = '<option value="">-- Sélectionner une édition --</option>';
            for (const [editionCode, editionName] of Object.entries(langData.editions)) {
                const option = document.createElement('option');
                option.value = editionCode;
                option.textContent = `${editionName} (${editionCode})`;
                editionSelect.appendChild(option);
            }
        } else {
            editionSelect.innerHTML = '<option value="">Aucune édition disponible</option>';
        }
    }

    function editLanguage(lang) {
        const details = document.getElementById('langFormDetails');
        const summary = document.getElementById('langFormSummary');
        const resetBtn = document.getElementById('resetLangBtn');

        // Open details
        details.open = true;

        // Update summary text
        summary.innerHTML = '<iconify-icon icon="mdi:pencil"></iconify-icon> Modifier la langue';

        // Show reset button
        resetBtn.style.display = 'inline-flex';

        // Parse name JSON if needed (it comes as string from PHP json_encode)
        let names = {};
        try {
            names = JSON.parse(lang.name);
        } catch (e) {
            console.error('Error parsing names', e);
        }

        // Populate form
        document.getElementById('lang_id').value = lang.id;
        document.getElementById('lang_code').value = lang.code;

        // Set language selector
        const langSelector = document.getElementById('lang_selector');
        langSelector.value = lang.code;
        onLanguageSelect(); // Load editions for this language

        // Set edition after loading options
        setTimeout(() => {
            document.getElementById('quran_edition').value = lang.quran_edition || '';
        }, 10);

        document.getElementById('is_rtl').checked = lang.is_rtl == 1;

        // Dynamically populate name fields for all languages
        languageCodes.forEach(code => {
            const field = document.getElementById('lang_name_' + code);
            if (field) field.value = (names && names[code]) || '';
        });

        document.getElementById('langSubmitText').textContent = 'Mettre à jour';

        // Scroll to form
        details.scrollIntoView({ behavior: 'smooth' });
    }

    function resetLangForm() {
        const details = document.getElementById('langFormDetails');
        const summary = document.getElementById('langFormSummary');
        const form = document.getElementById('langForm');
        const resetBtn = document.getElementById('resetLangBtn');

        form.reset();
        document.getElementById('lang_id').value = '';
        document.getElementById('lang_code').value = '';

        // Reset selectors
        document.getElementById('lang_selector').value = '';
        document.getElementById('quran_edition').innerHTML = '<option value="">-- Choisir d\'abord une langue --</option>';

        summary.innerHTML = '<iconify-icon icon="mdi:plus"></iconify-icon> Ajouter une langue';
        document.getElementById('langSubmitText').textContent = 'Ajouter';
        resetBtn.style.display = 'none';
    }
</script>

<!-- Change Password -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <iconify-icon icon="mdi:lock"></iconify-icon>
            Sécurité
        </h2>
    </div>
    <form method="post">
        <div class="form-group" style="max-width: 400px;">
            <label class="form-label">Nouveau mot de passe admin</label>
            <input type="password" name="new_password" class="form-input" placeholder="Minimum 6 caractères">
        </div>
        <button type="submit" name="change_password" class="btn btn-secondary">
            <iconify-icon icon="mdi:key"></iconify-icon>
            Changer le mot de passe
        </button>
    </form>
</div>

<?php adminFooter(); ?>