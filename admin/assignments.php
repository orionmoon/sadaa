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

// Get filters
$view = $_GET['view'] ?? 'list'; // 'list' (global) or 'edit' (selection)
$filterCategoryId = isset($_GET['category_id']) ? (int) $_GET['category_id'] : null;
$filterSurahId = isset($_GET['surah_id']) ? (int) $_GET['surah_id'] : null;
$filterTag = $_GET['tag'] ?? null;

// For the selection view, we need a surah
$selectedSurahId = $filterSurahId;
$selectedCategoryId = $filterCategoryId;

// Handle group actions
if ($_POST) {
    if (isset($_POST['save_group'])) {
        try {
            $groupId = !empty($_POST['group_id']) ? (int) $_POST['group_id'] : null;
            $categoryIds = $_POST['category_ids'] ?? [];
            if (!is_array($categoryIds))
                $categoryIds = [$categoryIds];

            $surahId = (int) $_POST['surah_id'];
            $title = trim($_POST['title'] ?? '');
            $assignedAyahs = $_POST['ayahs'] ?? [];
            $sortOrder = (int) ($_POST['sort_order'] ?? 0);

            $pdo->beginTransaction();

            // If we are editing a single group, we only update THAT group
            if ($groupId) {
                $stmt = $pdo->prepare("UPDATE assignment_groups SET title = ?, sort_order = ? WHERE id = ?");
                $stmt->execute([$title, $sortOrder, $groupId]);

                // Clear and re-insert ayahs
                $stmt = $pdo->prepare("DELETE FROM ayah_categories WHERE assignment_group_id = ?");
                $stmt->execute([$groupId]);

                if (count($assignedAyahs) > 0) {
                    $stmt = $pdo->prepare("INSERT INTO ayah_categories (ayah_id, category_id, assignment_group_id) VALUES (?, ?, ?)");
                    foreach ($assignedAyahs as $ayahId) {
                        $stmt->execute([(int) $ayahId, $_POST['category_id'], $groupId]);
                    }
                }

                $targetGroupIds = [$groupId];
            } else {
                // Creating new - might be multiple categories
                $targetGroupIds = [];
                foreach ($categoryIds as $catId) {
                    $stmt = $pdo->prepare("INSERT INTO assignment_groups (category_id, surah_id, title, sort_order) VALUES (?, ?, ?, ?)");
                    $stmt->execute([(int) $catId, $surahId, $title, $sortOrder]);
                    $newGroupId = $pdo->lastInsertId();
                    $targetGroupIds[] = $newGroupId;

                    // Insert Ayahs for this cat/group
                    if (count($assignedAyahs) > 0) {
                        $stmt = $pdo->prepare("INSERT INTO ayah_categories (ayah_id, category_id, assignment_group_id) VALUES (?, ?, ?)");
                        foreach ($assignedAyahs as $ayahId) {
                            $stmt->execute([(int) $ayahId, (int) $catId, $newGroupId]);
                        }
                    }
                }
            }

            // Handle Tags for all affected groups
            $tagsInput = $_POST['tags'] ?? '';
            foreach ($targetGroupIds as $tid) {
                // Clear existing tags
                $stmt = $pdo->prepare("DELETE FROM assignment_group_tags WHERE assignment_group_id = ?");
                $stmt->execute([$tid]);

                if (!empty($tagsInput)) {
                    $tags = array_map('trim', explode(',', $tagsInput));
                    foreach ($tags as $tagName) {
                        if (empty($tagName))
                            continue;

                        $stmt = $pdo->prepare("INSERT INTO tags (name) VALUES (?) ON DUPLICATE KEY UPDATE name = name");
                        $stmt->execute([$tagName]);

                        $stmt = $pdo->prepare("SELECT id FROM tags WHERE name = ?");
                        $stmt->execute([$tagName]);
                        $tagId = $stmt->fetchColumn();

                        $stmt = $pdo->prepare("INSERT IGNORE INTO assignment_group_tags (assignment_group_id, tag_id) VALUES (?, ?)");
                        $stmt->execute([$tid, $tagId]);
                    }
                }
            }

            $pdo->commit();
            $message = count($targetGroupIds) > 1 ? 'Groupes créés avec succès' : 'Assignation enregistrée';

            if (!$groupId && count($categoryIds) == 1) {
                $selectedCategoryId = $categoryIds[0];
            }
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

// Get all surahs for dropdowns
$surahs = [];
try {
    $stmt = $pdo->query("SELECT * FROM surahs ORDER BY number ASC");
    $surahs = $stmt->fetchAll();
} catch (PDOException $e) {
}

// Get ayahs for selected surah (if in edit view)
$ayahs = [];
if ($view === 'edit' && $selectedSurahId) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM ayahs WHERE surah_id = ? ORDER BY ayah_number ASC");
        $stmt->execute([$selectedSurahId]);
        $ayahs = $stmt->fetchAll();
    } catch (PDOException $e) {
    }
}

// Get pagination
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage = 25;
$offset = ($page - 1) * $perPage;

// Fetch Groups (Global or Filtered) - with pagination
$groups = [];
$totalCount = 0;
try {
    // Count query
    $countSql = "SELECT COUNT(DISTINCT ag.id) as total
                 FROM assignment_groups ag
                 JOIN surahs s ON ag.surah_id = s.id
                 JOIN categories c ON ag.category_id = c.id
                 JOIN types t ON c.type_id = t.id";

    $where = [];
    $params = [];

    if ($filterCategoryId) {
        $where[] = "ag.category_id = ?";
        $params[] = $filterCategoryId;
    }
    if ($filterSurahId) {
        $where[] = "ag.surah_id = ?";
        $params[] = $filterSurahId;
    }
    if ($filterTag) {
        $countSql .= " JOIN assignment_group_tags agt ON ag.id = agt.assignment_group_id
                       JOIN tags tg ON agt.tag_id = tg.id";
        $where[] = "tg.name = ?";
        $params[] = $filterTag;
    }

    if (!empty($where)) {
        $countSql .= " WHERE " . implode(" AND ", $where);
    }

    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $totalCount = (int) $stmt->fetch()['total'];

    // Main query with pagination
    $sql = "SELECT ag.*, s.number as surah_number, s.name as surah_name, c.name as category_name, t.name as type_name,
            (SELECT COUNT(*) FROM ayah_categories ac WHERE ac.assignment_group_id = ag.id) as ayah_count
            FROM assignment_groups ag
            JOIN surahs s ON ag.surah_id = s.id
            JOIN categories c ON ag.category_id = c.id
            JOIN types t ON c.type_id = t.id";

    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $sql .= " ORDER BY ag.sort_order ASC, ag.created_at DESC LIMIT $perPage OFFSET $offset";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $groups = $stmt->fetchAll();

    // Hydrate groups with tags
    if (count($groups) > 0) {
        foreach ($groups as &$group) {
            $stmt = $pdo->prepare("
                SELECT t.* FROM tags t
                JOIN assignment_group_tags agt ON t.id = agt.tag_id
                WHERE agt.assignment_group_id = ?
            ");
            $stmt->execute([$group['id']]);
            $group['tags'] = $stmt->fetchAll();
        }
        unset($group);
    }
} catch (PDOException $e) {
    $error = 'Erreur lors de la récupération des groupes: ' . $e->getMessage();
}

// Check if editing a specific group
$editGroup = null;
if (isset($_GET['edit_group'])) {
    $editGroupId = (int) $_GET['edit_group'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM assignment_groups WHERE id = ?");
        $stmt->execute([$editGroupId]);
        $editGroup = $stmt->fetch();

        if ($editGroup) {
            $stmt = $pdo->prepare("SELECT ayah_id FROM ayah_categories WHERE assignment_group_id = ?");
            $stmt->execute([$editGroupId]);
            $editGroup['ayahs'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $stmt = $pdo->prepare("
                SELECT t.name FROM tags t
                JOIN assignment_group_tags agt ON t.id = agt.tag_id
                WHERE agt.assignment_group_id = ?
            ");
            $stmt->execute([$editGroupId]);
            $editGroup['tags'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // If editing, we MUST be in edit view for the right surah
            $view = 'edit';
            $selectedSurahId = $editGroup['surah_id'];
            $selectedCategoryId = $editGroup['category_id'];

            // Reload ayahs for this surah
            $stmt = $pdo->prepare("SELECT * FROM ayahs WHERE surah_id = ? ORDER BY ayah_number ASC");
            $stmt->execute([$selectedSurahId]);
            $ayahs = $stmt->fetchAll();
        }
    } catch (PDOException $e) {
    }
}

adminHeader('Assignation des versets');
?>

<div class="page-headerflex justify-between items-center">
    <h1 class="page-title">Assignation des versets</h1>
    <div class="flex gap-1 bg-dark-soft p-1 rounded-lg">
        <a href="?view=list" class="btn btn-sm <?= $view === 'list' ? 'btn-primary' : 'btn-ghost' ?>">
            <iconify-icon icon="mdi:format-list-bulleted"></iconify-icon> Liste Globale
        </a>
        <a href="?view=edit" class="btn btn-sm <?= $view === 'edit' ? 'btn-primary' : 'btn-ghost' ?>">
            <iconify-icon icon="mdi:plus-circle"></iconify-icon> Nouvelle Assignation
        </a>
    </div>
</div>

<?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if (count($categories) === 0 || count($surahs) === 0): ?>
        <div class="alert alert-info">
            <iconify-icon icon="mdi:information"></iconify-icon>
            <?php if (count($surahs) === 0): ?>
                    Vous devez d'abord <a href="/admin/import" style="color: inherit; font-weight: bold;">importer le Coran</a>.
            <?php else: ?>
                    Vous devez d'abord <a href="/admin/categories" style="color: inherit; font-weight: bold;">créer des catégories</a>.
            <?php endif; ?>
        </div>
<?php else: ?>

        <?php if ($view === 'list'): ?>
                <!-- GLOBAL LIST VIEW -->
                <div class="card mb-2">
                    <form method="get" class="flex gap-2 items-end flex-wrap">
                        <input type="hidden" name="view" value="list">
                        <div class="form-group mb-0" style="min-width: 200px;">
                            <label class="form-label">Filtrer par Catégorie</label>
                            <select name="category_id" class="form-select" onchange="this.form.submit()">
                                <option value="">Toutes les catégories</option>
                                <?php foreach ($categories as $cat):
                                    $catName = json_decode($cat['name'], true);
                                    $typeName = json_decode($cat['type_name'], true);
                                    ?>
                                        <option value="<?= $cat['id'] ?>" <?= $filterCategoryId == $cat['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars(($typeName['fr'] ?? '') . ' → ' . ($catName['fr'] ?? $catName['en'] ?? '')) ?>
                                        </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group mb-0" style="min-width: 200px;">
                            <label class="form-label">Filtrer par Sourate</label>
                            <select name="surah_id" class="form-select" onchange="this.form.submit()">
                                <option value="">Toutes les sourates</option>
                                <?php foreach ($surahs as $surah):
                                    $surahName = json_decode($surah['name'], true);
                                    ?>
                                        <option value="<?= $surah['id'] ?>" <?= $filterSurahId == $surah['id'] ? 'selected' : '' ?>>
                                            <?= $surah['number'] ?>. <?= htmlspecialchars($surahName['ar'] ?? '') ?>
                                        </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label">Tag</label>
                            <input type="text" name="tag" class="form-input" placeholder="patience..." value="<?= htmlspecialchars($filterTag ?? '') ?>">
                        </div>
                        <button type="submit" class="btn btn-secondary">Filtrer</button>
                        <?php if ($filterCategoryId || $filterSurahId || $filterTag): ?>
                                <a href="?view=list" class="btn btn-ghost" title="Effacer les filtres">
                                    <iconify-icon icon="mdi:filter-off"></iconify-icon>
                                </a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="card p-0">
                    <div class="table-container" style="max-height: 500px; overflow-y: auto; overflow-x: hidden;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th width="50">Ordre</th>
                                    <th>Groupe / Titre</th>
                                    <th>Sourate</th>
                                    <th>Catégorie</th>
                                    <th>Contenu</th>
                                    <th width="100">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($groups) > 0): ?>
                                        <?php foreach ($groups as $group):
                                            $gSurahName = json_decode($group['surah_name'], true);
                                            $gCatName = json_decode($group['category_name'], true);
                                            $gTypeName = json_decode($group['type_name'], true);
                                            ?>
                                                <tr>
                                                    <td class="text-center">
                                                        <span class="badge badge-secondary"><?= $group['sort_order'] ?></span>
                                                    </td>
                                                    <td>
                                                        <div style="font-weight: 600;"><?= htmlspecialchars($group['title'] ?: 'Groupe #' . $group['id']) ?></div>
                                                        <?php if (!empty($group['tags'])): ?>
                                                                <div class="flex gap-1 mt-1">
                                                                    <?php foreach ($group['tags'] as $tg): ?>
                                                                            <span class="badge" style="background: <?= $tg['color'] ?>20; color: <?= $tg['color'] ?>; font-size: 0.65rem; border: 1px solid <?= $tg['color'] ?>40;">
                                                                                <?= htmlspecialchars($tg['name']) ?>
                                                                            </span>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div style="font-size: 0.85rem;">
                                                            <?= $group['surah_number'] ?>. <?= htmlspecialchars($gSurahName['ar'] ?? '') ?>
                                                        </div>
                                                        <div class="text-muted" style="font-size: 0.75rem;">(<?= htmlspecialchars($gSurahName['en'] ?? '') ?>)</div>
                                                    </td>
                                                    <td>
                                                        <span class="badge" style="background: var(--bg-dark); border: 1px solid var(--border-color);">
                                                            <?= htmlspecialchars($gTypeName['fr'] ?? '') ?> → <?= htmlspecialchars($gCatName['fr'] ?? $gCatName['en'] ?? '') ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="text-primary" style="font-weight: 600;"><?= $group['ayah_count'] ?></span> versets
                                                    </td>
                                                    <td>
                                                        <div class="flex gap-1">
                                                            <a href="?view=edit&edit_group=<?= $group['id'] ?>" class="btn btn-sm btn-secondary" title="Éditer">
                                                                <iconify-icon icon="mdi:pencil"></iconify-icon>
                                                            </a>
                                                            <form method="post" onsubmit="return confirm('Supprimer ce groupe ?');" style="display:inline;">
                                                                <input type="hidden" name="group_id" value="<?= $group['id'] ?>">
                                                                <button type="submit" name="delete_group" class="btn btn-sm btn-danger" title="Supprimer">
                                                                    <iconify-icon icon="mdi:delete"></iconify-icon>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                        <?php endforeach; ?>
                                <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center" style="padding: 3rem;">
                                                <div class="text-muted">Aucun groupe trouvé avec ces critères.</div>
                                            </td>
                                        </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalCount > $perPage): ?>
                        <?php
                        $totalPages = ceil($totalCount / $perPage);
                        $queryParams = $_GET;
                        unset($queryParams['page']);
                        $queryString = http_build_query($queryParams);
                        $baseUrl = '?view=list' . ($queryString ? '&' . $queryString : '');
                        ?>
                        <div class="pagination" style="display: flex; justify-content: center; align-items: center; gap: 0.5rem; padding: 1rem; border-top: 1px solid var(--border-color); background: var(--bg-card);">
                            <?php if ($page > 1): ?>
                                <a href="<?= $baseUrl ?>&page=<?= $page - 1 ?>" class="btn btn-sm btn-secondary">
                                    <iconify-icon icon="mdi:chevron-left"></iconify-icon>
                                </a>
                            <?php endif; ?>

                            <span style="padding: 0 1rem; font-size: 0.875rem; color: var(--text-secondary);">
                                Page <?= $page ?> sur <?= $totalPages ?> (<?= $totalCount ?> total)
                            </span>

                            <?php if ($page < $totalPages): ?>
                                <a href="<?= $baseUrl ?>&page=<?= $page + 1 ?>" class="btn btn-sm btn-secondary">
                                    <iconify-icon icon="mdi:chevron-right"></iconify-icon>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

        <?php elseif ($view === 'edit'): ?>
                <!-- EDITOR VIEW -->
                <div class="grid grid-2" style="grid-template-columns: 1.2fr 0.8fr; gap: 2rem;">
            
                    <!-- Left: Ayah Selector -->
                    <div class="card">
                        <div class="card-header flex justify-between items-center">
                            <h2 class="card-title">Sélection des Versets</h2>
                            <form method="get" id="surah-form" class="mb-0">
                                <input type="hidden" name="view" value="edit">
                                <?php if ($editGroup): ?><input type="hidden" name="edit_group" value="<?= $editGroup['id'] ?>"><?php endif; ?>
                                <select name="surah_id" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 200px;">
                                    <?php foreach ($surahs as $surah):
                                        $surahName = json_decode($surah['name'], true);
                                        ?>
                                            <option value="<?= $surah['id'] ?>" <?= $selectedSurahId == $surah['id'] ? 'selected' : '' ?>>
                                                <?= $surah['number'] ?>. <?= htmlspecialchars($surahName['ar'] ?? '') ?> (<?= htmlspecialchars($surahName['en'] ?? '') ?>)
                                            </option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </div>

                        <!-- Range Selector Helper -->
                        <div class="bg-dark-soft p-1 rounded-lg mb-1 flex items-center gap-2" style="border: 1px solid var(--border-color);">
                            <div style="font-size: 0.8rem; font-weight: 600; margin-left: 0.5rem;">Sélection rapide :</div>
                            <input type="number" id="range-start" class="form-input form-input-sm" style="width: 70px;" placeholder="De">
                            <iconify-icon icon="mdi:arrow-right-thin"></iconify-icon>
                            <input type="number" id="range-end" class="form-input form-input-sm" style="width: 70px;" placeholder="À">
                            <button type="button" id="apply-range" class="btn btn-sm btn-secondary">Appliquer</button>
                            <div class="flex-1"></div>
                            <button type="button" id="select-all" class="btn btn-sm btn-ghost">Tout</button>
                            <button type="button" id="deselect-all" class="btn btn-sm btn-ghost">Rien</button>
                        </div>

                        <div class="ayah-scroll-container" style="max-height: 600px; overflow-y: auto; border: 1px solid var(--border-color); border-radius: 0.5rem; background: var(--bg-card);">
                            <?php if (count($ayahs) > 0): ?>
                                    <form id="main-group-form" method="post">
                                        <?php foreach ($ayahs as $ayah):
                                            $text = json_decode($ayah['text'], true);
                                            $isChecked = $editGroup && in_array($ayah['id'], $editGroup['ayahs']);
                                            ?>
                                                <label class="ayah-select-item" style="display: flex; gap: 1rem; padding: 0.75rem; border-bottom: 1px solid var(--border-color); cursor: pointer; transition: background 0.2s;">
                                                    <input type="checkbox" name="ayahs[]" value="<?= $ayah['id'] ?>" data-number="<?= $ayah['ayah_number'] ?>" <?= $isChecked ? 'checked' : '' ?> 
                                                           style="accent-color: var(--color-primary); transform: scale(1.1); margin-top: 0.5rem;">
                                                    <div style="flex: 1;">
                                                        <div style="font-size: 0.75rem; color: var(--text-muted); display: flex; justify-content: space-between;">
                                                            <span>Verset <?= $ayah['ayah_number'] ?></span>
                                                            <span>#<?= $ayah['id'] ?></span>
                                                        </div>
                                                        <div class="font-arabic" style="font-size: 1.25rem; color: var(--color-primary); margin: 0.25rem 0;" dir="rtl">
                                                            <?= htmlspecialchars($text['ar'] ?? '') ?>
                                                        </div>
                                                    </div>
                                                </label>
                                        <?php endforeach; ?>
                                <?php else: ?>
                                        <div style="padding: 3rem; text-align: center; color: var(--text-muted);">Sélectionnez une sourate pour voir les versets.</div>
                                <?php endif; ?>
                        </div>
                    </div>

                    <!-- Right: Group details -->
                    <div class="card" style="position: sticky; top: 1rem;">
                        <h2 class="card-title mb-2"><?= $editGroup ? 'Éditer le groupe' : 'Paramètres de l\'assignation' ?></h2>
                
                        <input type="hidden" name="surah_id" value="<?= $selectedSurahId ?>">
                        <?php if ($editGroup): ?>
                                <input type="hidden" name="group_id" value="<?= $editGroup['id'] ?>">
                                <input type="hidden" name="category_id" value="<?= $editGroup['category_id'] ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label class="form-label">Titre du groupe (Ex: Versets sur la patience)</label>
                            <input type="text" name="title" class="form-input" placeholder="Optionnel" value="<?= htmlspecialchars($editGroup['title'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Catégorie(s)</label>
                            <?php if ($editGroup): ?>
                                    <div class="badge badge-primary p-1 mb-1" style="display:block; width: 100%; border-radius: 0.5rem;">
                                        <?php
                                        $cat = array_filter($categories, fn($c) => $c['id'] == $editGroup['category_id']);
                                        $cat = reset($cat);
                                        $catName = json_decode($cat['name'], true);
                                        echo htmlspecialchars($catName['fr'] ?? $catName['en'] ?? '');
                                        ?>
                                    </div>
                                    <small class="text-muted">La catégorie ne peut pas être changée lors de l'édition.</small>
                            <?php else: ?>
                                    <div style="max-height: 180px; overflow-y: auto; border: 1px solid var(--border-color); border-radius: 0.5rem; padding: 0.5rem;">
                                        <?php foreach ($categories as $cat):
                                            $catName = json_decode($cat['name'], true);
                                            $typeName = json_decode($cat['type_name'], true);
                                            ?>
                                                <label style="display: flex; align-items: center; gap: 0.5rem; padding: 0.25rem; cursor: pointer;">
                                                    <input type="checkbox" name="category_ids[]" value="<?= $cat['id'] ?>" <?= $selectedCategoryId == $cat['id'] ? 'checked' : '' ?>>
                                                    <span style="font-size: 0.85rem;">
                                                        <small class="text-muted"><?= htmlspecialchars($typeName['fr'] ?? '') ?> ›</small> 
                                                        <?= htmlspecialchars($catName['fr'] ?? $catName['en'] ?? '') ?>
                                                    </span>
                                                </label>
                                        <?php endforeach; ?>
                                    </div>
                                    <small class="text-muted">Sélectionnez une ou plusieurs catégories.</small>
                            <?php endif; ?>
                        </div>

                        <div class="grid grid-2 gap-1 mb-2">
                            <div class="form-group mb-0">
                                <label class="form-label">Ordre d'affichage</label>
                                <input type="number" name="sort_order" class="form-input" value="<?= $editGroup['sort_order'] ?? 0 ?>">
                            </div>
                            <div class="form-group mb-0">
                                <label class="form-label">Tags (séparés par ,)</label>
                                <input type="text" name="tags" class="form-input" placeholder="foi, espoir" value="<?= htmlspecialchars(isset($editGroup['tags']) ? implode(', ', $editGroup['tags']) : '') ?>">
                            </div>
                        </div>

                        <div class="flex gap-1 mt-2">
                            <button type="submit" name="save_group" class="btn btn-primary flex-1">
                                <iconify-icon icon="mdi:check-circle"></iconify-icon> <?= $editGroup ? 'Mettre à jour' : 'Créer l\'assignation' ?>
                            </button>
                            <?php if ($editGroup): ?>
                                    <a href="?view=list" class="btn btn-secondary">Annuler</a>
                            <?php endif; ?>
                        </div>
                        </form> <!-- End form started in Ayah Selector if Edit Mode -->
                    </div>
                </div>

                <script>
                    // Range Selection Logic
                    document.getElementById('apply-range')?.addEventListener('click', () => {
                        const start = parseInt(document.getElementById('range-start').value);
                        const end = parseInt(document.getElementById('range-end').value);
                        if (isNaN(start) || isNaN(end)) return;
                
                        document.querySelectorAll('input[name="ayahs[]"]').forEach(cb => {
                            const num = parseInt(cb.getAttribute('data-number'));
                            if (num >= start && num <= end) cb.checked = true;
                        });
                    });

                    document.getElementById('select-all')?.addEventListener('click', () => {
                        document.querySelectorAll('input[name="ayahs[]"]').forEach(cb => cb.checked = true);
                    });
                    document.getElementById('deselect-all')?.addEventListener('click', () => {
                        document.querySelectorAll('input[name="ayahs[]"]').forEach(cb => cb.checked = false);
                    });
                </script>
        <?php endif; ?>

<?php endif; ?>

<style>
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th, .data-table td { padding: 0.75rem 1rem; border-bottom: 1px solid var(--border-color); text-align: left; }
    .data-table thead { position: sticky; top: 0; background: var(--bg-dark); z-index: 1; }
    .data-table tbody tr:hover { background: var(--bg-hover); }
    .ayah-select-item:hover { background: var(--bg-hover) !important; }
    .bg-dark-soft { background: rgba(0,0,0,0.1); }
</style>

<?php adminFooter(); ?>