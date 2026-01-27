<?php
/**
 * Sadaa (صدى) - Ayah Assignments
 */

require_once __DIR__ . '/layout.php';

$message = '';
$error = '';

// Get categories
$categories = [];
try {
    $stmt = $pdo->query("SELECT c.*, t.name as type_name FROM categories c LEFT JOIN types t ON c.type_id = t.id ORDER BY t.sort_order, c.sort_order ASC");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Erreur: ' . $e->getMessage();
}

// Get selected category
$selectedCategoryId = isset($_GET['category_id']) ? (int) $_GET['category_id'] : (count($categories) > 0 ? $categories[0]['id'] : null);

// Get surahs
$surahs = [];
try {
    $stmt = $pdo->query("SELECT * FROM surahs ORDER BY number ASC");
    $surahs = $stmt->fetchAll();
} catch (PDOException $e) {
}

// Get selected surah
$selectedSurahId = isset($_GET['surah_id']) ? (int) $_GET['surah_id'] : (count($surahs) > 0 ? $surahs[0]['id'] : null);

// Handle assignments
if ($_POST && isset($_POST['save_assignments'])) {
    $categoryId = (int) $_POST['category_id'];
    $surahId = (int) $_POST['surah_id'];
    $assignedAyahs = $_POST['ayahs'] ?? [];

    try {
        // Get all ayah IDs for this surah
        $stmt = $pdo->prepare("SELECT id FROM ayahs WHERE surah_id = ?");
        $stmt->execute([$surahId]);
        $allAyahIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Remove all existing assignments for this surah/category
        $stmt = $pdo->prepare("DELETE FROM ayah_categories WHERE category_id = ? AND ayah_id IN (SELECT id FROM ayahs WHERE surah_id = ?)");
        $stmt->execute([$categoryId, $surahId]);

        // Add new assignments
        if (count($assignedAyahs) > 0) {
            $stmt = $pdo->prepare("INSERT INTO ayah_categories (ayah_id, category_id) VALUES (?, ?)");
            foreach ($assignedAyahs as $ayahId) {
                $stmt->execute([(int) $ayahId, $categoryId]);
            }
        }

        $message = count($assignedAyahs) . ' verset(s) assigné(s)';
        $selectedCategoryId = $categoryId;
        $selectedSurahId = $surahId;

    } catch (PDOException $e) {
        $error = 'Erreur: ' . $e->getMessage();
    }
}

// Get ayahs for selected surah
$ayahs = [];
$assignedAyahIds = [];
if ($selectedSurahId && $selectedCategoryId) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM ayahs WHERE surah_id = ? ORDER BY ayah_number ASC");
        $stmt->execute([$selectedSurahId]);
        $ayahs = $stmt->fetchAll();

        // Get already assigned ayahs
        $stmt = $pdo->prepare("SELECT ayah_id FROM ayah_categories WHERE category_id = ? AND ayah_id IN (SELECT id FROM ayahs WHERE surah_id = ?)");
        $stmt->execute([$selectedCategoryId, $selectedSurahId]);
        $assignedAyahIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    } catch (PDOException $e) {
        $error = 'Erreur: ' . $e->getMessage();
    }
}

adminHeader('Assignation des versets');
?>

<div class="page-header">
    <h1 class="page-title">Assignation des versets</h1>
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

<?php if (count($categories) === 0 || count($surahs) === 0): ?>
    <div class="alert alert-info">
        <iconify-icon icon="mdi:information"></iconify-icon>
        <?php if (count($surahs) === 0): ?>
            Vous devez d'abord <a href="import.php" style="color: inherit; font-weight: bold;">importer le Coran</a>.
        <?php else: ?>
            Vous devez d'abord <a href="categories.php" style="color: inherit; font-weight: bold;">créer des catégories</a>.
        <?php endif; ?>
    </div>
<?php else: ?>

    <!-- Selection Form -->
    <div class="card">
        <form method="get" class="flex gap-2 items-center">
            <div class="form-group" style="margin-bottom: 0; flex: 1;">
                <label class="form-label">Catégorie</label>
                <select name="category_id" class="form-select" onchange="this.form.submit()">
                    <?php foreach ($categories as $cat):
                        $catName = json_decode($cat['name'], true);
                        $typeName = json_decode($cat['type_name'], true);
                        ?>
                        <option value="<?= $cat['id'] ?>" <?= $selectedCategoryId == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars(($typeName['fr'] ?? '') . ' → ' . ($catName['fr'] ?? $catName['en'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 0; flex: 1;">
                <label class="form-label">Sourate</label>
                <select name="surah_id" class="form-select" onchange="this.form.submit()">
                    <?php foreach ($surahs as $surah):
                        $surahName = json_decode($surah['name'], true);
                        ?>
                        <option value="<?= $surah['id'] ?>" <?= $selectedSurahId == $surah['id'] ? 'selected' : '' ?>>
                            <?= $surah['number'] ?>.
                            <?= htmlspecialchars($surahName['ar'] ?? '') ?> (
                            <?= htmlspecialchars($surahName['en'] ?? '') ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>

    <!-- Ayahs List -->
    <?php if (count($ayahs) > 0): ?>
        <form method="post">
            <input type="hidden" name="category_id" value="<?= $selectedCategoryId ?>">
            <input type="hidden" name="surah_id" value="<?= $selectedSurahId ?>">

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Versets</h2>
                    <div class="flex gap-1">
                        <button type="button" id="select-all" class="btn btn-sm btn-secondary">Tout sélectionner</button>
                        <button type="button" id="deselect-all" class="btn btn-sm btn-secondary">Tout désélectionner</button>
                    </div>
                </div>

                <div style="max-height: 500px; overflow-y: auto;">
                    <?php foreach ($ayahs as $ayah):
                        $text = json_decode($ayah['text'], true);
                        $isAssigned = in_array($ayah['id'], $assignedAyahIds);
                        ?>
                        <label
                            style="display: flex; gap: 1rem; padding: 0.75rem; border-bottom: 1px solid var(--border-color); cursor: pointer; transition: background 0.2s;"
                            onmouseover="this.style.background='var(--bg-hover)'" onmouseout="this.style.background='transparent'">
                            <input type="checkbox" name="ayahs[]" value="<?= $ayah['id'] ?>" <?= $isAssigned ? 'checked' : '' ?>
                            style="accent-color: var(--color-primary); transform: scale(1.2); margin-top: 0.5rem;">
                            <div style="flex: 1;">
                                <div style="font-size: 0.75rem; color: var(--text-muted);">Verset
                                    <?= $ayah['ayah_number'] ?>
                                </div>
                                <div class="font-arabic" style="font-size: 1.25rem; color: var(--color-primary); margin: 0.25rem 0;"
                                    dir="rtl">
                                    <?= htmlspecialchars($text['ar'] ?? '') ?>
                                </div>
                                <div style="font-size: 0.9rem; color: var(--text-secondary);">
                                    <?= htmlspecialchars($text['fr'] ?? $text['en'] ?? '') ?>
                                </div>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" name="save_assignments" class="btn btn-primary">
                <iconify-icon icon="mdi:content-save"></iconify-icon>
                Enregistrer les assignations
            </button>
        </form>
    <?php else: ?>
        <div class="card text-center" style="padding: 2rem;">
            <iconify-icon icon="mdi:text-box-remove-outline" style="font-size: 3rem; color: var(--text-muted);"></iconify-icon>
            <p class="text-muted mt-2">Aucun verset dans cette sourate.</p>
        </div>
    <?php endif; ?>

    <script>
        document.getElementById('select-all')?.addEventListener('click', () => {
            document.querySelectorAll('input[name="ayahs[]"]').forEach(cb => cb.checked = true);
        });
        document.getElementById('deselect-all')?.addEventListener('click', () => {
            document.querySelectorAll('input[name="ayahs[]"]').forEach(cb => cb.checked = false);
        });
    </script>
<?php endif; ?>

<?php adminFooter(); ?>