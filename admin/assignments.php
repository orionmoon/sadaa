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

// Handle group actions
if ($_POST) {
    if (isset($_POST['save_group'])) {
        try {
            $groupId = !empty($_POST['group_id']) ? (int) $_POST['group_id'] : null;
            $categoryId = (int) $_POST['category_id'];
            $surahId = (int) $_POST['surah_id'];
            $title = trim($_POST['title'] ?? '');
            $assignedAyahs = $_POST['ayahs'] ?? [];

            $pdo->beginTransaction();

            if ($groupId) {
                // Update Group
                $stmt = $pdo->prepare("UPDATE assignment_groups SET title = ? WHERE id = ?");
                $stmt->execute([$title, $groupId]);

                // Clear existing assignments for this group
                $stmt = $pdo->prepare("DELETE FROM ayah_categories WHERE assignment_group_id = ?");
                $stmt->execute([$groupId]);

                $message = 'Groupe mis à jour';
            } else {
                // Create Group
                $stmt = $pdo->prepare("INSERT INTO assignment_groups (category_id, surah_id, title) VALUES (?, ?, ?)");
                $stmt->execute([$categoryId, $surahId, $title]);
                $groupId = $pdo->lastInsertId();
                $message = 'Nouveau groupe créé';
            }

            // Insert Ayahs
            if (count($assignedAyahs) > 0) {
                $stmt = $pdo->prepare("INSERT INTO ayah_categories (ayah_id, category_id, assignment_group_id) VALUES (?, ?, ?)");
                foreach ($assignedAyahs as $ayahId) {
                    $stmt->execute([(int) $ayahId, $categoryId, $groupId]);
                }
            }

            $pdo->commit();
            $selectedCategoryId = $categoryId;
            $selectedSurahId = $surahId;

        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Erreur: ' . $e->getMessage();
        }
    }

    if (isset($_POST['delete_group'])) {
        try {
            $groupId = (int) $_POST['group_id'];
            $stmt = $pdo->prepare("DELETE FROM assignment_groups WHERE id = ?");
            $stmt->execute([$groupId]);
            $message = 'Groupe supprimé';
        } catch (PDOException $e) {
            $error = 'Erreur: ' . $e->getMessage();
        }
    }
}

// Get ayahs for selected surah
$ayahs = [];
$groups = [];
$editGroup = null;

if ($selectedSurahId && $selectedCategoryId) {
    try {
        // Fetch all ayahs
        $stmt = $pdo->prepare("SELECT * FROM ayahs WHERE surah_id = ? ORDER BY ayah_number ASC");
        $stmt->execute([$selectedSurahId]);
        $ayahs = $stmt->fetchAll();

        // Fetch existing groups
        $stmt = $pdo->prepare("
            SELECT ag.*, COUNT(ac.id) as ayah_count 
            FROM assignment_groups ag 
            LEFT JOIN ayah_categories ac ON ag.id = ac.assignment_group_id 
            WHERE ag.category_id = ? AND ag.surah_id = ?
            GROUP BY ag.id
            ORDER BY ag.created_at ASC
        ");
        $stmt->execute([$selectedCategoryId, $selectedSurahId]);
        $groups = $stmt->fetchAll();

        // Check if editing
        if (isset($_GET['edit_group'])) {
            $editGroupId = (int) $_GET['edit_group'];
            $stmt = $pdo->prepare("SELECT * FROM assignment_groups WHERE id = ?");
            $stmt->execute([$editGroupId]);
            $editGroup = $stmt->fetch();

            if ($editGroup) {
                $stmt = $pdo->prepare("SELECT ayah_id FROM ayah_categories WHERE assignment_group_id = ?");
                $stmt->execute([$editGroupId]);
                $editGroup['ayahs'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
            }
        }

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

    <div class="grid grid-2" style="grid-template-columns: 1fr 2fr; align-items: start;">

        <!-- Existing Groups List -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Assignations existantes</h2>
                <?php if ($editGroup): ?>
                    <a href="assignments.php?category_id=<?= $selectedCategoryId ?>&surah_id=<?= $selectedSurahId ?>"
                        class="btn btn-sm btn-secondary">
                        <iconify-icon icon="mdi:plus"></iconify-icon> Nouveau
                    </a>
                <?php endif; ?>
            </div>

            <?php if (count($groups) > 0): ?>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <?php foreach ($groups as $group): ?>
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: var(--bg-dark); border-radius: 0.5rem; border: 1px solid <?= ($editGroup && $editGroup['id'] == $group['id']) ? 'var(--color-primary)' : 'transparent' ?>;">
                            <div>
                                <div style="font-weight: 500;">
                                    <?= htmlspecialchars($group['title'] ?: 'Groupe #' . $group['id']) ?>
                                </div>
                                <div class="text-muted" style="font-size: 0.8rem;">
                                    <?= $group['ayah_count'] ?> versets
                                </div>
                            </div>
                            <div class="flex gap-1">
                                <a href="assignments.php?category_id=<?= $selectedCategoryId ?>&surah_id=<?= $selectedSurahId ?>&edit_group=<?= $group['id'] ?>"
                                    class="btn btn-sm btn-secondary" title="Éditer">
                                    <iconify-icon icon="mdi:pencil"></iconify-icon>
                                </a>
                                <form method="post" onsubmit="return confirm('Supprimer ce groupe ?');">
                                    <input type="hidden" name="group_id" value="<?= $group['id'] ?>">
                                    <input type="hidden" name="category_id" value="<?= $selectedCategoryId ?>">
                                    <input type="hidden" name="surah_id" value="<?= $selectedSurahId ?>">
                                    <button type="submit" name="delete_group" class="btn btn-sm btn-danger" title="Supprimer">
                                        <iconify-icon icon="mdi:delete"></iconify-icon>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted text-center" style="padding: 2rem;">Aucune assignation pour cette sourate.</p>
            <?php endif; ?>
        </div>

        <!-- Editor Form -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <?= $editGroup ? 'Modifier le groupe' : 'Nouvelle assignation' ?>
                </h2>
            </div>

            <form method="post">
                <input type="hidden" name="category_id" value="<?= $selectedCategoryId ?>">
                <input type="hidden" name="surah_id" value="<?= $selectedSurahId ?>">
                <?php if ($editGroup): ?>
                    <input type="hidden" name="group_id" value="<?= $editGroup['id'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label class="form-label">Titre (optionnel)</label>
                    <input type="text" name="title" class="form-input" placeholder="Ex: Versets sur la patience"
                        value="<?= htmlspecialchars($editGroup['title'] ?? '') ?>">
                </div>

                <div style="margin-bottom: 0.5rem; font-weight: 500;">Sélectionner les versets :</div>

                <div
                    style="max-height: 400px; overflow-y: auto; border: 1px solid var(--border-color); border-radius: 0.5rem;">
                    <?php if (count($ayahs) > 0): ?>
                        <div
                            style="padding: 0.5rem; border-bottom: 1px solid var(--border-color); background: var(--bg-dark); position: sticky; top: 0; z-index: 10;">
                            <div class="flex gap-1">
                                <button type="button" id="select-all" class="btn btn-sm btn-secondary">Tout</button>
                                <button type="button" id="deselect-all" class="btn btn-sm btn-secondary">Rien</button>
                            </div>
                        </div>
                        <?php foreach ($ayahs as $ayah):
                            $text = json_decode($ayah['text'], true);
                            $isChecked = $editGroup && in_array($ayah['id'], $editGroup['ayahs']);
                            ?>
                            <label
                                style="display: flex; gap: 1rem; padding: 0.75rem; border-bottom: 1px solid var(--border-color); cursor: pointer; transition: background 0.2s;"
                                onmouseover="this.style.background='var(--bg-hover)'"
                                onmouseout="this.style.background='transparent'">
                                <input type="checkbox" name="ayahs[]" value="<?= $ayah['id'] ?>" <?= $isChecked ? 'checked' : '' ?>
                                    style="accent-color: var(--color-primary); transform: scale(1.2); margin-top: 0.5rem;">
                                <div style="flex: 1;">
                                    <div
                                        style="font-size: 0.75rem; color: var(--text-muted); display: flex; justify-content: space-between;">
                                        <span>Verset <?= $ayah['ayah_number'] ?></span>
                                        <span>#<?= $ayah['id'] ?></span>
                                    </div>
                                    <div class="font-arabic"
                                        style="font-size: 1.25rem; color: var(--color-primary); margin: 0.25rem 0;" dir="rtl">
                                        <?= htmlspecialchars($text['ar'] ?? '') ?>
                                    </div>
                                    <div style="font-size: 0.9rem; color: var(--text-secondary);">
                                        <?= htmlspecialchars($text['fr'] ?? $text['en'] ?? '') ?>
                                    </div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="padding: 2rem; text-align: center;">Aucun verset trouvé.</div>
                    <?php endif; ?>
                </div>

                <div style="margin-top: 1rem; display: flex; gap: 1rem;">
                    <button type="submit" name="save_group" class="btn btn-primary">
                        <iconify-icon icon="mdi:content-save"></iconify-icon>
                        <?= $editGroup ? 'Mettre à jour' : 'Enregistrer' ?>
                    </button>
                    <?php if ($editGroup): ?>
                        <a href="assignments.php?category_id=<?= $selectedCategoryId ?>&surah_id=<?= $selectedSurahId ?>"
                            class="btn btn-secondary">Annuler</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

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