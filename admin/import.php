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
    $selectedLangs = $_POST['languages'] ?? ['ar'];
    if (!in_array('ar', $selectedLangs)) {
        $selectedLangs[] = 'ar';
    }
    
    $surahStart = (int)($_POST['surah_start'] ?? 1);
    $surahEnd = (int)($_POST['surah_end'] ?? 114);
    $overwrite = isset($_POST['overwrite']);
    
    // Create import record
    try {
        $stmt = $pdo->prepare("INSERT INTO imports (type, source, status, languages, total_surahs) VALUES ('quran', 'alquran.cloud', 'running', ?, ?)");
        $stmt->execute([json_encode($selectedLangs), $surahEnd - $surahStart + 1]);
        $importId = $pdo->lastInsertId();
        
        $api = new QuranApi();
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
            'errors' => $errors
        ];
        
        $message = "Import terminé: $totalImported sourates importées";
        
    } catch (Exception $e) {
        $error = 'Erreur d\'import: ' . $e->getMessage();
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
        <div class="form-group">
            <label class="form-label">Langues à importer</label>
            <p class="text-muted mb-1" style="font-size: 0.8rem;">L'arabe est toujours inclus. Sélectionnez les traductions à télécharger.</p>
            
            <div class="grid grid-4">
                <?php foreach ($languages as $lang): 
                    $langName = json_decode($lang['name'], true);
                ?>
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="languages[]" value="<?= $lang['code'] ?>" 
                           <?= $lang['is_source'] ? 'checked disabled' : '' ?>
                           style="accent-color: var(--color-primary);">
                    <span><?= htmlspecialchars($langName['fr'] ?? $langName['en'] ?? $lang['code']) ?></span>
                    <?php if ($lang['is_source']): ?>
                    <span class="badge badge-primary" style="font-size: 0.65rem;">Source</span>
                    <?php endif; ?>
                </label>
                <?php endforeach; ?>
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
        
        <div class="form-group">
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
        <h2 class="card-title">Résultat de l'import</h2>
    </div>
    
    <p><strong>Sourates importées:</strong> <?= $importResult['surahs_imported'] ?></p>
    <p><strong>Langues:</strong> <?= implode(', ', $importResult['languages']) ?></p>
    
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
document.getElementById('import-form').addEventListener('submit', function() {
    const btn = document.getElementById('import-btn');
    btn.innerHTML = '<iconify-icon icon="mdi:loading" class="spin"></iconify-icon> Import en cours...';
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
