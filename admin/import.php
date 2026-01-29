<?php
/**
 * Sadaa (صدى) - Quran Import
 */

require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/../app/QuranApi.php';

$message = '';
$error = '';
$importResult = null;

// Get active languages
$languages = [];
try {
    $stmt = $pdo->query("SELECT * FROM languages WHERE is_active = 1 ORDER BY sort_order ASC");
    $languages = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Erreur: ' . $e->getMessage();
}

// Get Quran book (create if doesn't exist)
$quranBookId = null;
try {
    $stmt = $pdo->query("SELECT id FROM books WHERE slug = 'quran' LIMIT 1");
    $quranBookId = $stmt->fetchColumn();
    
    if (!$quranBookId) {
        $stmt = $pdo->prepare("INSERT INTO books (title, slug, description, language) VALUES (?, 'quran', ?, 'ar')");
        $stmt->execute([
            json_encode(['ar' => 'القرآن الكريم', 'fr' => 'Le Coran', 'en' => 'The Quran']),
            json_encode(['ar' => 'كتاب الله المنزل', 'fr' => 'Le Livre Saint', 'en' => 'The Holy Book'])
        ]);
        $quranBookId = $pdo->lastInsertId();
    }
} catch (PDOException $e) {
    $error = 'Erreur: ' . $e->getMessage();
}

// Handle import
if ($_POST && isset($_POST['start_import'])) {
    $importMode = $_POST['import_mode'] ?? 'full';
    $surahStart = (int)($_POST['surah_start'] ?? 1);
    $surahEnd = (int)($_POST['surah_end'] ?? 114);
    $notes = trim($_POST['notes'] ?? '');

    // Initialize API
    $api = new QuranApi();

    if ($importMode === 'add_language') {
        // ADD LANGUAGE MODE: Merge a new language into existing data
        $languageToAdd = $_POST['language_to_add'] ?? '';

        if (!$languageToAdd || $languageToAdd === 'ar') {
            $error = 'Veuillez sélectionner une langue à ajouter (autre que l\'arabe).';
        } else {
            // Get edition from database first, fallback to QuranApi static list
            $editionToUse = null;
            $stmt = $pdo->prepare("SELECT quran_edition FROM languages WHERE code = ? AND quran_edition IS NOT NULL AND quran_edition != ''");
            $stmt->execute([$languageToAdd]);
            $dbEdition = $stmt->fetchColumn();
            $editionToUse = $dbEdition ?: QuranApi::getEditionForLanguage($languageToAdd);

            if (!$editionToUse) {
                $error = "Aucune édition trouvée pour la langue: $languageToAdd. Configurez-la dans les paramètres.";
            } else {
                // Build translation references (with PDO to check database for custom editions)
                $translationRefs = $api->buildTranslationReferences([$languageToAdd], $pdo);

                $metadata = [
                    'api_version' => 'v1',
                    'api_source' => 'alquran.cloud',
                    'import_date' => date('Y-m-d H:i:s'),
                    'surah_range' => [$surahStart, $surahEnd],
                    'mode' => 'add_language',
                    'edition_source' => $dbEdition ? 'database' : 'api_default'
                ];

                try {
                    $stmt = $pdo->prepare("INSERT INTO imports (type, source, status, languages, total_surahs, quran_edition, quran_version, translation_references, metadata, notes) VALUES ('quran', 'alquran.cloud', 'running', ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([
                        json_encode([$languageToAdd]),
                        $surahEnd - $surahStart + 1,
                        $editionToUse,
                        'Language addition',
                        json_encode($translationRefs),
                        json_encode($metadata),
                        $notes ?: "Ajout de la langue: $languageToAdd"
                    ]);
                    $importId = $pdo->lastInsertId();

                    $totalUpdated = 0;
                    $ayahsUpdated = 0;
                    $errors = [];

                    for ($i = $surahStart; $i <= $surahEnd; $i++) {
                        try {
                            $result = $api->addLanguageToSurah($pdo, $i, $languageToAdd, $quranBookId, $editionToUse);
                            $totalUpdated++;
                            $ayahsUpdated += $result['ayahs_updated'];

                            // Update progress
                            $stmt = $pdo->prepare("UPDATE imports SET surahs_imported = ?, ayahs_imported = ? WHERE id = ?");
                            $stmt->execute([$totalUpdated, $ayahsUpdated, $importId]);

                        } catch (Exception $e) {
                            $errors[] = "Sourate $i: " . $e->getMessage();
                        }
                    }

                // Mark as complete
                $status = count($errors) === 0 ? 'completed' : (count($errors) < ($surahEnd - $surahStart + 1) ? 'completed' : 'failed');
                $errorMsg = count($errors) > 0 ? implode("\n", $errors) : null;

                $stmt = $pdo->prepare("UPDATE imports SET status = ?, completed_at = NOW(), error_message = ? WHERE id = ?");
                $stmt->execute([$status, $errorMsg, $importId]);

                $importResult = [
                    'success' => true,
                    'surahs_imported' => $totalUpdated,
                    'languages' => [$languageToAdd],
                    'mode' => 'add_language',
                    'ayahs_updated' => $ayahsUpdated,
                    'edition_used' => $editionToUse,
                    'edition_source' => $dbEdition ? 'base de données' : 'API par défaut',
                    'errors' => $errors
                ];

                $message = "Langue ajoutée: $totalUpdated sourates mises à jour ($ayahsUpdated versets) - Édition: $editionToUse";

            } catch (Exception $e) {
                $error = 'Erreur d\'ajout de langue: ' . $e->getMessage();
            }
            }
        }
    } else {
        // FULL IMPORT MODE: Original behavior
        $selectedLangs = $_POST['languages'] ?? ['ar'];
        if (!in_array('ar', $selectedLangs)) {
            $selectedLangs[] = 'ar';
        }
        $overwrite = isset($_POST['overwrite']);

        // Build translation references with full metadata from API (with PDO to check database for custom editions)
        $translationRefs = $api->buildTranslationReferences($selectedLangs, $pdo);

        // Get Arabic edition metadata (check database first, then fallback to static list)
        $stmt = $pdo->prepare("SELECT quran_edition FROM languages WHERE code = 'ar' AND quran_edition IS NOT NULL AND quran_edition != ''");
        $stmt->execute();
        $arabicEdition = $stmt->fetchColumn() ?: QuranApi::getEditionForLanguage('ar');
        $arabicMetadata = $api->getEditionMetadata($arabicEdition);

        $quranEdition = $arabicEdition;
        $quranVersion = $arabicMetadata['name'] ?? 'Uthmani';

        // Build additional metadata
        $metadata = [
            'api_version' => 'v1',
            'api_source' => 'alquran.cloud',
            'import_date' => date('Y-m-d H:i:s'),
            'surah_range' => [$surahStart, $surahEnd],
            'overwrite' => $overwrite,
            'mode' => 'full'
        ];

        // Create import record
        try {
            $stmt = $pdo->prepare("INSERT INTO imports (type, source, status, languages, total_surahs, quran_edition, quran_version, translation_references, metadata, notes) VALUES ('quran', 'alquran.cloud', 'running', ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                json_encode($selectedLangs),
                $surahEnd - $surahStart + 1,
                $quranEdition,
                $quranVersion,
                json_encode($translationRefs),
                json_encode($metadata),
                $notes
            ]);
            $importId = $pdo->lastInsertId();

            $totalImported = 0;
            $errors = [];

            for ($i = $surahStart; $i <= $surahEnd; $i++) {
                try {
                    $result = $api->importSurah($pdo, $i, $selectedLangs, $quranBookId, $overwrite);
                    $totalImported++;

                    // Update progress
                    $stmt = $pdo->prepare("UPDATE imports SET surahs_imported = ?, ayahs_imported = ayahs_imported + ? WHERE id = ?");
                    $stmt->execute([$totalImported, $result['ayahs_imported'], $importId]);

                } catch (Exception $e) {
                    $errors[] = "Sourate $i: " . $e->getMessage();
                }
            }

            // Mark as complete
            $status = count($errors) === 0 ? 'completed' : (count($errors) < ($surahEnd - $surahStart + 1) ? 'completed' : 'failed');
            $errorMsg = count($errors) > 0 ? implode("\n", $errors) : null;

            $stmt = $pdo->prepare("UPDATE imports SET status = ?, completed_at = NOW(), error_message = ? WHERE id = ?");
            $stmt->execute([$status, $errorMsg, $importId]);

            $importResult = [
                'success' => true,
                'surahs_imported' => $totalImported,
                'languages' => $selectedLangs,
                'mode' => 'full',
                'errors' => $errors
            ];

            $message = "Import terminé: $totalImported sourates importées";

        } catch (Exception $e) {
            $error = 'Erreur d\'import: ' . $e->getMessage();
        }
    }
}

// Get current stats
$stats = ['surahs' => 0, 'ayahs' => 0];
try {
    $stats['surahs'] = $pdo->query("SELECT COUNT(*) FROM surahs")->fetchColumn();
    $stats['ayahs'] = $pdo->query("SELECT COUNT(*) FROM ayahs")->fetchColumn();
} catch (PDOException $e) {}

adminHeader('Import du Coran');
?>

<div class="page-header">
    <h1 class="page-title">Import du Coran</h1>
</div>

<?php if ($message): ?>
<div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Current Status -->
<div class="grid grid-2 mb-2">
    <div class="card">
        <div class="stat-card">
            <div class="stat-icon">
                <iconify-icon icon="mdi:book-open-page-variant"></iconify-icon>
            </div>
            <div>
                <div class="stat-value"><?= $stats['surahs'] ?>/114</div>
                <div class="stat-label">Sourates importées</div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="stat-card">
            <div class="stat-icon">
                <iconify-icon icon="mdi:text-box-multiple"></iconify-icon>
            </div>
            <div>
                <div class="stat-value"><?= number_format($stats['ayahs']) ?></div>
                <div class="stat-label">Versets total</div>
            </div>
        </div>
    </div>
</div>

<!-- Import Form -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <iconify-icon icon="mdi:cloud-download"></iconify-icon>
            Importer depuis AlQuran.cloud API
        </h2>
    </div>
    
    <form method="post" id="import-form">
        <!-- Import Mode Selection -->
        <div class="form-group">
            <label class="form-label">Mode d'import</label>
            <div class="flex gap-2" style="flex-wrap: wrap;">
                <label class="mode-option" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1rem; border: 2px solid var(--color-border); border-radius: 8px; cursor: pointer; transition: all 0.2s;">
                    <input type="radio" name="import_mode" value="full" checked onchange="toggleImportMode()" style="accent-color: var(--color-primary);">
                    <div>
                        <strong>Import complet</strong>
                        <p style="margin: 0; font-size: 0.8rem; color: var(--color-text-muted);">Importer plusieurs langues (écrase les données existantes)</p>
                    </div>
                </label>
                <label class="mode-option" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1rem; border: 2px solid var(--color-border); border-radius: 8px; cursor: pointer; transition: all 0.2s;">
                    <input type="radio" name="import_mode" value="add_language" onchange="toggleImportMode()" style="accent-color: var(--color-primary);">
                    <div>
                        <strong>Ajouter une langue</strong>
                        <p style="margin: 0; font-size: 0.8rem; color: var(--color-text-muted);">Ajouter une traduction aux versets existants (conserve les assignations)</p>
                    </div>
                </label>
            </div>
        </div>

        <!-- Full Import: Language Selection -->
        <div id="full-import-options">
            <div class="form-group">
                <label class="form-label">Langues à importer</label>
                <p class="text-muted mb-1" style="font-size: 0.8rem;">L'arabe est toujours inclus. Sélectionnez les traductions à télécharger. L'édition configurée dans les paramètres sera utilisée.</p>

                <div class="grid grid-3">
                    <?php foreach ($languages as $lang):
                        $langName = json_decode($lang['name'], true);
                        $edition = $lang['quran_edition'] ?? QuranApi::getEditionForLanguage($lang['code']) ?? 'N/A';
                    ?>
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.5rem; border-radius: 4px; background: var(--bg-dark);">
                        <input type="checkbox" name="languages[]" value="<?= $lang['code'] ?>"
                               <?= $lang['is_source'] ? 'checked disabled' : '' ?>
                               style="accent-color: var(--color-primary);">
                        <div style="flex: 1;">
                            <span><?= htmlspecialchars($langName['fr'] ?? $langName['en'] ?? $lang['code']) ?></span>
                            <?php if ($lang['is_source']): ?>
                            <span class="badge badge-primary" style="font-size: 0.65rem;">Source</span>
                            <?php endif; ?>
                            <div style="font-size: 0.7rem; color: var(--color-text-muted);">
                                <code><?= htmlspecialchars($edition) ?></code>
                            </div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Add Language Mode: Single Language Selection -->
        <div id="add-language-options" style="display: none;">
            <div class="form-group">
                <label class="form-label">Langue à ajouter</label>
                <p class="text-muted mb-1" style="font-size: 0.8rem;">Sélectionnez la langue à ajouter aux versets existants. Les données actuelles seront préservées.</p>

                <?php
                // Get all languages with a Quran edition defined (from database + QuranApi fallback)
                $importableLanguages = [];

                // First, add languages from database that have a quran_edition
                foreach ($languages as $lang) {
                    if ($lang['is_source']) continue; // Skip Arabic (source)
                    if (!empty($lang['quran_edition'])) {
                        $langName = json_decode($lang['name'], true);
                        $importableLanguages[$lang['code']] = [
                            'name' => $langName['fr'] ?? $langName['en'] ?? $lang['code'],
                            'edition' => $lang['quran_edition'],
                            'from_db' => true
                        ];
                    }
                }

                // Add remaining languages from QuranApi::$editions that aren't in DB
                $apiEditions = QuranApi::$editions;
                $editionNames = [
                    'fr' => 'Français', 'en' => 'English', 'es' => 'Español', 'de' => 'Deutsch',
                    'tr' => 'Türkçe', 'id' => 'Bahasa Indonesia', 'ur' => 'اردو', 'bn' => 'বাংলা',
                    'ru' => 'Русский', 'pt' => 'Português', 'nl' => 'Nederlands', 'it' => 'Italiano',
                    'fa' => 'فارسی', 'zh' => '中文', 'ja' => '日本語', 'ko' => '한국어'
                ];
                foreach ($apiEditions as $code => $edition) {
                    if ($code === 'ar') continue; // Skip Arabic
                    if (!isset($importableLanguages[$code])) {
                        $importableLanguages[$code] = [
                            'name' => $editionNames[$code] ?? strtoupper($code),
                            'edition' => $edition,
                            'from_db' => false
                        ];
                    }
                }
                ?>
                <select name="language_to_add" class="form-input" style="max-width: 400px;">
                    <option value="">-- Sélectionner une langue --</option>
                    <?php foreach ($importableLanguages as $code => $info): ?>
                    <option value="<?= $code ?>">
                        <?= htmlspecialchars($info['name']) ?> (<?= $info['edition'] ?>)
                        <?= $info['from_db'] ? '' : ' ⚠️' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <div class="alert alert-warning" style="margin-top: 0.5rem; padding: 0.75rem; font-size: 0.85rem;">
                    <iconify-icon icon="mdi:information"></iconify-icon>
                    <div>
                        <strong>⚠️ = Édition par défaut</strong> - Cette langue n'est pas configurée dans l'application.
                        L'édition affichée entre parenthèses sera utilisée.
                        <br>
                        <strong>Pour choisir une autre édition</strong>, ajoutez d'abord la langue dans
                        <a href="settings.php" style="color: var(--color-primary); text-decoration: underline;">Paramètres → Langues</a>.
                    </div>
                </div>
            </div>

            <div class="alert alert-success" style="background: rgba(34, 197, 94, 0.1); border-color: rgba(34, 197, 94, 0.3);">
                <iconify-icon icon="mdi:shield-check"></iconify-icon>
                <div>
                    <strong>Mode sécurisé</strong>
                    <p style="margin: 0.25rem 0 0 0; font-size: 0.9rem;">
                        Ce mode fusionne la nouvelle traduction avec les données existantes. Vos assignations de catégories et les autres langues déjà importées seront préservées.
                    </p>
                </div>
            </div>
        </div>
        
        <div class="grid grid-2">
            <div class="form-group">
                <label class="form-label">Sourate de début</label>
                <input type="number" name="surah_start" class="form-input" value="1" min="1" max="114">
            </div>
            <div class="form-group">
                <label class="form-label">Sourate de fin</label>
                <input type="number" name="surah_end" class="form-input" value="114" min="1" max="114">
            </div>
        </div>

        <div class="alert alert-info">
            <iconify-icon icon="mdi:information"></iconify-icon>
            <div>
                <strong>Métadonnées automatiques</strong>
                <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem;">
                    Les références d'édition et de traduction seront récupérées automatiquement depuis l'API AlQuran.cloud :
                </p>
                <ul style="margin: 0.5rem 0 0 1.5rem; font-size: 0.85rem;">
                    <li>Arabe : <code>quran-uthmani</code></li>
                    <li>Français : <code>fr.hamidullah</code></li>
                    <li>Anglais : <code>en.sahih</code></li>
                    <li>+ autres traductions selon les langues sélectionnées</li>
                </ul>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Notes (optionnel)</label>
            <textarea name="notes" class="form-textarea" rows="3" placeholder="Notes personnelles sur cet import..."></textarea>
        </div>

        <div class="form-group" id="overwrite-option">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="overwrite" value="1" style="accent-color: var(--color-primary);">
                <span>Écraser les données existantes (Mise à jour)</span>
            </label>
            <p class="text-muted" style="font-size: 0.8rem; margin-left: 1.5rem;">
                Si décoché, les sourates déjà présentes dans la base de données seront ignorées.
            </p>
        </div>
        
        <div class="alert alert-info">
            <iconify-icon icon="mdi:information"></iconify-icon>
            <strong>Note:</strong> L'import peut prendre plusieurs minutes selon le nombre de sourates et de langues sélectionnées.
            Les données existantes seront mises à jour.
        </div>
        
        <input type="hidden" name="start_import" value="1">
        <button type="submit" class="btn btn-primary" id="import-btn">
            <iconify-icon icon="mdi:download"></iconify-icon>
            Démarrer l'import
        </button>
    </form>
</div>

<?php if ($importResult): ?>
<!-- Import Result -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <?php if (($importResult['mode'] ?? 'full') === 'add_language'): ?>
            <iconify-icon icon="mdi:translate"></iconify-icon> Résultat de l'ajout de langue
            <?php else: ?>
            <iconify-icon icon="mdi:check-circle"></iconify-icon> Résultat de l'import
            <?php endif; ?>
        </h2>
    </div>

    <?php if (($importResult['mode'] ?? 'full') === 'add_language'): ?>
    <p><strong>Sourates mises à jour:</strong> <?= $importResult['surahs_imported'] ?></p>
    <p><strong>Versets mis à jour:</strong> <?= $importResult['ayahs_updated'] ?? 0 ?></p>
    <p><strong>Langue ajoutée:</strong> <?= implode(', ', $importResult['languages']) ?></p>
    <p><strong>Édition utilisée:</strong> <code><?= htmlspecialchars($importResult['edition_used'] ?? 'N/A') ?></code>
        <span style="font-size: 0.8rem; color: var(--color-text-muted);">(source: <?= htmlspecialchars($importResult['edition_source'] ?? 'inconnue') ?>)</span>
    </p>
    <?php else: ?>
    <p><strong>Sourates importées:</strong> <?= $importResult['surahs_imported'] ?></p>
    <p><strong>Langues:</strong> <?= implode(', ', $importResult['languages']) ?></p>
    <?php endif; ?>

    <?php if (count($importResult['errors']) > 0): ?>
    <div class="alert alert-error mt-2">
        <strong>Erreurs (<?= count($importResult['errors']) ?>):</strong>
        <ul style="margin: 0.5rem 0 0 1rem;">
            <?php foreach (array_slice($importResult['errors'], 0, 5) as $err): ?>
            <li><?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
            <?php if (count($importResult['errors']) > 5): ?>
            <li>... et <?= count($importResult['errors']) - 5 ?> autres</li>
            <?php endif; ?>
        </ul>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Quick Import Options -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Import rapide</h2>
    </div>
    <div class="flex gap-2">
        <form method="post" style="display: inline;">
            <input type="hidden" name="languages[]" value="ar">
            <input type="hidden" name="surah_start" value="1">
            <input type="hidden" name="surah_end" value="1">

        </form>
        
        <form method="post" style="display: inline;">
            <input type="hidden" name="languages[]" value="ar">
            <input type="hidden" name="languages[]" value="fr">
            <input type="hidden" name="surah_start" value="1">
            <input type="hidden" name="surah_end" value="10">
            <button type="submit" name="start_import" class="btn btn-secondary">
                Sourates 1-10 (AR + FR)
            </button>
        </form>
    </div>
</div>

<script>
function toggleImportMode() {
    const mode = document.querySelector('input[name="import_mode"]:checked').value;
    const fullOptions = document.getElementById('full-import-options');
    const addLangOptions = document.getElementById('add-language-options');
    const overwriteOption = document.getElementById('overwrite-option');
    const btn = document.getElementById('import-btn');

    if (mode === 'add_language') {
        fullOptions.style.display = 'none';
        addLangOptions.style.display = 'block';
        overwriteOption.style.display = 'none';
        btn.innerHTML = '<iconify-icon icon="mdi:translate"></iconify-icon> Ajouter la langue';
    } else {
        fullOptions.style.display = 'block';
        addLangOptions.style.display = 'none';
        overwriteOption.style.display = 'block';
        btn.innerHTML = '<iconify-icon icon="mdi:download"></iconify-icon> Démarrer l\'import';
    }

    // Update mode option styling
    document.querySelectorAll('.mode-option').forEach(opt => {
        const input = opt.querySelector('input[type="radio"]');
        if (input.checked) {
            opt.style.borderColor = 'var(--color-primary)';
            opt.style.background = 'rgba(var(--color-primary-rgb), 0.05)';
        } else {
            opt.style.borderColor = 'var(--color-border)';
            opt.style.background = 'transparent';
        }
    });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', toggleImportMode);

document.getElementById('import-form').addEventListener('submit', function() {
    const btn = document.getElementById('import-btn');
    const mode = document.querySelector('input[name="import_mode"]:checked').value;
    if (mode === 'add_language') {
        btn.innerHTML = '<iconify-icon icon="mdi:loading" class="spin"></iconify-icon> Ajout en cours...';
    } else {
        btn.innerHTML = '<iconify-icon icon="mdi:loading" class="spin"></iconify-icon> Import en cours...';
    }
    btn.disabled = true;
});
</script>

<style>
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
.spin { animation: spin 1s linear infinite; }
</style>

<?php adminFooter(); ?>
