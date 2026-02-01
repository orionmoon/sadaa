<?php
/**
 * Sadaa (صدى) - Categories Management
 */

require_once __DIR__ . '/layout.php';

$message = '';
$error = '';

// Get all active languages for dynamic form fields
$activeLanguages = [];
try {
    $stmt = $pdo->query("SELECT * FROM languages WHERE is_active = 1 ORDER BY sort_order ASC");
    $activeLanguages = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Erreur chargement langues: ' . $e->getMessage();
}

// Get all types for dropdown
$types = [];
try {
    $stmt = $pdo->query("SELECT * FROM types ORDER BY sort_order ASC");
    $types = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Erreur: ' . $e->getMessage();
}

// Handle form submissions
if ($_POST) {
    if (isset($_POST['add_category'])) {
        try {
            // Build name and description arrays dynamically from active languages
            $nameArray = [];
            $descArray = [];
            foreach ($activeLanguages as $lang) {
                $code = $lang['code'];
                $nameArray[$code] = trim($_POST['name_' . $code] ?? '');
                $descArray[$code] = trim($_POST['desc_' . $code] ?? '');
            }
            $name = json_encode($nameArray);
            $description = json_encode($descArray);
            $typeId = (int) $_POST['type_id'];
            $icon = trim($_POST['icon'] ?? 'mdi:tag-outline');
            $color = trim($_POST['color'] ?? '#C99B35');
            $slug = trim($_POST['slug'] ?? '');

            if (!$slug) {
                $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $_POST['name_en'] ?? $_POST['name_fr'] ?? 'category'));
            }

            $stmt = $pdo->prepare("INSERT INTO categories (type_id, name, description, icon, color, slug, sort_order) 
                                   VALUES (?, ?, ?, ?, ?, ?, (SELECT COALESCE(MAX(c.sort_order), 0) + 1 FROM categories c WHERE c.type_id = ?))");
            $stmt->execute([$typeId, $name, $description, $icon, $color, $slug, $typeId]);
            $message = 'Catégorie ajoutée avec succès';
        } catch (PDOException $e) {
            $error = 'Erreur: ' . $e->getMessage();
        }
    }

    if (isset($_POST['edit_category'])) {
        try {
            $categoryId = (int) $_POST['category_id'];
            // Build name and description arrays dynamically from active languages
            $nameArray = [];
            $descArray = [];
            foreach ($activeLanguages as $lang) {
                $code = $lang['code'];
                $nameArray[$code] = trim($_POST['name_' . $code] ?? '');
                $descArray[$code] = trim($_POST['desc_' . $code] ?? '');
            }
            $name = json_encode($nameArray);
            $description = json_encode($descArray);
            $typeId = (int) $_POST['type_id'];
            $icon = trim($_POST['icon'] ?? 'mdi:tag-outline');
            $color = trim($_POST['color'] ?? '#C99B35');
            $slug = trim($_POST['slug'] ?? '');

            if (!$slug) {
                $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $_POST['name_en'] ?? $_POST['name_fr'] ?? 'category'));
            }

            $stmt = $pdo->prepare("UPDATE categories SET type_id = ?, name = ?, description = ?, icon = ?, color = ?, slug = ? WHERE id = ?");
            $stmt->execute([$typeId, $name, $description, $icon, $color, $slug, $categoryId]);
            $message = 'Catégorie modifiée avec succès';
        } catch (PDOException $e) {
            $error = 'Erreur: ' . $e->getMessage();
        }
    }

    if (isset($_POST['delete_category'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([(int) $_POST['category_id']]);
            $message = 'Catégorie supprimée';
        } catch (PDOException $e) {
            $error = 'Erreur: ' . $e->getMessage();
        }
    }
}

// Get filter
$filterTypeId = isset($_GET['type_id']) ? (int) $_GET['type_id'] : null;

// Get categories
$categories = [];
try {
    $sql = "SELECT c.*, t.name as type_name, t.color as type_color,
                   (SELECT COUNT(*) FROM ayah_categories ac WHERE ac.category_id = c.id) as ayah_count
            FROM categories c 
            LEFT JOIN types t ON c.type_id = t.id";

    if ($filterTypeId) {
        $sql .= " WHERE c.type_id = ?";
        $stmt = $pdo->prepare($sql . " ORDER BY c.type_id, c.sort_order ASC");
        $stmt->execute([$filterTypeId]);
    } else {
        $stmt = $pdo->query($sql . " ORDER BY c.type_id, c.sort_order ASC");
    }
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Erreur de chargement: ' . $e->getMessage();
}

// Common icons for categories
$commonIcons = [
    'mdi:emoticon-happy-outline',
    'mdi:emoticon-sad-outline',
    'mdi:emoticon-confused-outline',
    'mdi:heart',
    'mdi:heart-broken',
    'mdi:hand-heart',
    'mdi:account-heart',
    'mdi:account-off',
    'mdi:account-group',
    'mdi:shield',
    'mdi:sword',
    'mdi:crown',
    'mdi:cross',
    'mdi:earth',
    'mdi:dna',
    'mdi:atom',
    'mdi:rocket-launch',
    'mdi:flower',
    'mdi:tree',
    'mdi:water',
    'mdi:fire',
    'mdi:star',
    'mdi:moon-waning-crescent',
    'mdi:weather-sunny',
    'mdi:book-open-page-variant',
    'mdi:lightbulb',
    'mdi:magnify',
    'mdi:tag-outline',
    'mdi:label-outline',
    'mdi:folder-outline',
];

adminHeader('Gestion des Catégories');
?>

<div class="page-header">
    <h1 class="page-title">Catégories</h1>
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

<?php if (count($types) === 0): ?>
    <div class="alert alert-info">
        <iconify-icon icon="mdi:information"></iconify-icon>
        Vous devez d'abord <a href="/admin/types" style="color: inherit; font-weight: bold;">créer des Types</a> avant de
        pouvoir ajouter des catégories.
    </div>
<?php else: ?>

    <!-- Add Category Form -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Ajouter une Catégorie</h2>
        </div>
        <form method="post">
            <div class="grid grid-3">
                <div class="form-group">
                    <label class="form-label">Type parent *</label>
                    <select name="type_id" class="form-select" required>
                        <option value="">-- Sélectionner --</option>
                        <?php foreach ($types as $type):
                            $typeName = json_decode($type['name'], true);
                            ?>
                            <option value="<?= $type['id'] ?>">
                                <?= htmlspecialchars($typeName['fr'] ?? $typeName['en'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" class="form-input" placeholder="croyants">
                </div>
                <div class="form-group">
                    <label class="form-label">Couleur</label>
                    <input type="color" name="color" class="form-input" value="#C99B35"
                        style="height: 42px; padding: 0.25rem;">
                </div>
            </div>

            <!-- Dynamic Name Fields -->
            <div class="grid grid-3">
                <?php foreach ($activeLanguages as $lang):
                    $langName = json_decode($lang['name'], true);
                    $isRtl = $lang['is_rtl'];
                    $isRequired = $lang['code'] === 'fr';
                    ?>
                    <div class="form-group">
                        <label class="form-label">Nom
                            (<?= htmlspecialchars($langName['fr'] ?? $lang['code']) ?>)<?= $isRequired ? ' *' : '' ?></label>
                        <input type="text" name="name_<?= $lang['code'] ?>"
                            class="form-input<?= $isRtl ? ' font-arabic' : '' ?>" <?= $isRtl ? 'dir="rtl"' : '' ?>         <?= $isRequired ? 'required' : '' ?>>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Dynamic Description Fields -->
            <div class="grid grid-3">
                <?php foreach ($activeLanguages as $lang):
                    $langName = json_decode($lang['name'], true);
                    $isRtl = $lang['is_rtl'];
                    ?>
                    <div class="form-group">
                        <label class="form-label">Description
                            (<?= htmlspecialchars($langName['fr'] ?? $lang['code']) ?>)</label>
                        <input type="text" name="desc_<?= $lang['code'] ?>"
                            class="form-input<?= $isRtl ? ' font-arabic' : '' ?>" <?= $isRtl ? 'dir="rtl"' : '' ?>>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="form-group">
                <label class="form-label">Icône</label>
                <input type="text" name="icon" id="icon-input" class="form-input" value="mdi:tag-outline"
                    placeholder="mdi:tag-outline">
                <div class="icon-picker mt-1">
                    <?php foreach ($commonIcons as $icon): ?>
                        <div class="icon-option" data-icon="<?= $icon ?>">
                            <iconify-icon icon="<?= $icon ?>"></iconify-icon>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" name="add_category" class="btn btn-primary">
                <iconify-icon icon="mdi:plus"></iconify-icon>
                Ajouter
            </button>
        </form>
    </div>
<?php endif; ?>

<!-- Edit Category Form (Hidden by default) -->
<div class="card" id="edit-category-form" style="display: none;">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h2 class="card-title">Modifier une Catégorie</h2>
        <button type="button" class="btn btn-sm" onclick="cancelEdit()">
            <iconify-icon icon="mdi:close"></iconify-icon>
            Annuler
        </button>
    </div>
    <form method="post" id="edit-form">
        <input type="hidden" name="category_id" id="edit-category-id">

        <div class="grid grid-3">
            <div class="form-group">
                <label class="form-label">Type parent *</label>
                <select name="type_id" id="edit-type-id" class="form-select" required>
                    <option value="">-- Sélectionner --</option>
                    <?php foreach ($types as $type):
                        $typeName = json_decode($type['name'], true);
                        ?>
                        <option value="<?= $type['id'] ?>">
                            <?= htmlspecialchars($typeName['fr'] ?? $typeName['en'] ?? '') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Slug</label>
                <input type="text" name="slug" id="edit-slug" class="form-input" placeholder="croyants">
            </div>
            <div class="form-group">
                <label class="form-label">Couleur</label>
                <input type="color" name="color" id="edit-color" class="form-input"
                    style="height: 42px; padding: 0.25rem;">
            </div>
        </div>

        <!-- Dynamic Name Fields -->
        <div class="grid grid-3">
            <?php foreach ($activeLanguages as $lang):
                $langName = json_decode($lang['name'], true);
                $isRtl = $lang['is_rtl'];
                $isRequired = $lang['code'] === 'fr';
                ?>
                <div class="form-group">
                    <label class="form-label">Nom
                        (<?= htmlspecialchars($langName['fr'] ?? $lang['code']) ?>)<?= $isRequired ? ' *' : '' ?></label>
                    <input type="text" name="name_<?= $lang['code'] ?>" id="edit-name-<?= $lang['code'] ?>"
                        class="form-input<?= $isRtl ? ' font-arabic' : '' ?>" <?= $isRtl ? 'dir="rtl"' : '' ?>     <?= $isRequired ? 'required' : '' ?>>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Dynamic Description Fields -->
        <div class="grid grid-3">
            <?php foreach ($activeLanguages as $lang):
                $langName = json_decode($lang['name'], true);
                $isRtl = $lang['is_rtl'];
                ?>
                <div class="form-group">
                    <label class="form-label">Description
                        (<?= htmlspecialchars($langName['fr'] ?? $lang['code']) ?>)</label>
                    <input type="text" name="desc_<?= $lang['code'] ?>" id="edit-desc-<?= $lang['code'] ?>"
                        class="form-input<?= $isRtl ? ' font-arabic' : '' ?>" <?= $isRtl ? 'dir="rtl"' : '' ?>>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="form-group">
            <label class="form-label">Icône</label>
            <input type="text" name="icon" id="edit-icon-input" class="form-input" placeholder="mdi:tag-outline">
            <div class="icon-picker mt-1" id="edit-icon-picker">
                <?php foreach ($commonIcons as $icon): ?>
                    <div class="icon-option" data-icon="<?= $icon ?>">
                        <iconify-icon icon="<?= $icon ?>"></iconify-icon>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <button type="submit" name="edit_category" class="btn btn-primary">
            <iconify-icon icon="mdi:content-save"></iconify-icon>
            Enregistrer les modifications
        </button>
    </form>
</div>

<!-- Filter by Type -->
<div class="card">
    <div class="flex items-center justify-between">
        <h2 class="card-title">Catégories existantes</h2>
        <div class="flex gap-1">
            <a href="?" class="btn btn-sm <?= !$filterTypeId ? 'btn-primary' : 'btn-secondary' ?>">Toutes</a>
            <?php foreach ($types as $type):
                $typeName = json_decode($type['name'], true);
                ?>
                <a href="?type_id=<?= $type['id'] ?>"
                    class="btn btn-sm <?= $filterTypeId == $type['id'] ? 'btn-primary' : 'btn-secondary' ?>">
                    <?= htmlspecialchars($typeName['fr'] ?? '') ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Categories List -->
<div class="card">
    <?php if (count($categories) === 0): ?>
        <p class="text-muted text-center" style="padding: 2rem;">Aucune catégorie.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Icône</th>
                    <th>Nom</th>
                    <th>Type</th>
                    <th>Versets</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat):
                    $name = json_decode($cat['name'], true);
                    $typeName = json_decode($cat['type_name'], true);
                    ?>
                    <tr>
                        <td>
                            <span style="font-size: 1.5rem; color: <?= $cat['color'] ?>;">
                                <iconify-icon icon="<?= htmlspecialchars($cat['icon']) ?>"></iconify-icon>
                            </span>
                        </td>
                        <td>
                            <strong>
                                <?= htmlspecialchars($name['fr'] ?? $name['en'] ?? '') ?>
                            </strong>
                            <span class="text-muted"> /
                                <?= htmlspecialchars($name['ar'] ?? '') ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge" style="background: <?= $cat['type_color'] ?? '#666' ?>;">
                                <?= htmlspecialchars($typeName['fr'] ?? '') ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-primary">
                                <?= $cat['ayah_count'] ?>
                            </span>
                        </td>
                        <td>
                            <?php
                            $catJson = htmlspecialchars(json_encode([
                                "id" => $cat["id"],
                                "type_id" => $cat["type_id"],
                                "name" => json_decode($cat["name"], true),
                                "description" => json_decode($cat["description"], true),
                                "icon" => $cat["icon"],
                                "color" => $cat["color"],
                                "slug" => $cat["slug"]
                            ]), ENT_QUOTES, 'UTF-8');
                            ?>
                            <button type="button" class="btn btn-sm btn-secondary" onclick='editCategory(<?= $catJson ?>)'>
                                <iconify-icon icon="mdi:pencil"></iconify-icon>
                            </button>
                            <a href="/admin/assignments?category_id=<?= $cat['id'] ?>" class="btn btn-sm btn-secondary">
                                <iconify-icon icon="mdi:link-variant"></iconify-icon>
                            </a>
                            <form method="post" style="display: inline;"
                                onsubmit="return confirm('Supprimer cette catégorie?');">
                                <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                                <button type="submit" name="delete_category" class="btn btn-sm btn-danger">
                                    <iconify-icon icon="mdi:delete"></iconify-icon>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>
    // Active language codes for dynamic form handling
    const languageCodes = <?= json_encode(array_column($activeLanguages, 'code')) ?>;

    // Icon picker for both add and edit forms
    document.querySelectorAll('.icon-option').forEach(option => {
        option.addEventListener('click', () => {
            const isEditForm = option.closest('#edit-icon-picker');
            document.querySelectorAll(isEditForm ? '#edit-icon-picker .icon-option' : '.icon-picker .icon-option')
                .forEach(o => o.classList.remove('selected'));
            option.classList.add('selected');
            const inputId = isEditForm ? 'edit-icon-input' : 'icon-input';
            document.getElementById(inputId).value = option.dataset.icon;
        });
    });

    function editCategory(category) {
        // Show edit form
        document.getElementById('edit-category-form').style.display = 'block';

        // Populate form fields
        document.getElementById('edit-category-id').value = category.id;
        document.getElementById('edit-type-id').value = category.type_id;

        // Dynamically populate name and description fields for all languages
        languageCodes.forEach(code => {
            const nameField = document.getElementById('edit-name-' + code);
            const descField = document.getElementById('edit-desc-' + code);
            if (nameField) nameField.value = (category.name && category.name[code]) || '';
            if (descField) descField.value = (category.description && category.description[code]) || '';
        });

        document.getElementById('edit-slug').value = category.slug || '';
        document.getElementById('edit-color').value = category.color || '#C99B35';
        document.getElementById('edit-icon-input').value = category.icon || 'mdi:tag-outline';

        // Select the icon in the picker
        document.querySelectorAll('#edit-icon-picker .icon-option').forEach(o => o.classList.remove('selected'));
        const selectedIcon = document.querySelector(`#edit-icon-picker .icon-option[data-icon="${category.icon}"]`);
        if (selectedIcon) {
            selectedIcon.classList.add('selected');
        }

        // Scroll to edit form
        document.getElementById('edit-category-form').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function cancelEdit() {
        // Hide edit form
        document.getElementById('edit-category-form').style.display = 'none';

        // Clear form fields
        document.getElementById('edit-form').reset();

        // Clear icon selection
        document.querySelectorAll('#edit-icon-picker .icon-option').forEach(o => o.classList.remove('selected'));
    }
</script>

<?php adminFooter(); ?>