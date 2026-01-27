<?php
/**
 * Sadaa (صدى) - Categories Management
 */

require_once __DIR__ . '/layout.php';

$message = '';
$error = '';

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
            $name = json_encode([
                'ar' => trim($_POST['name_ar'] ?? ''),
                'fr' => trim($_POST['name_fr'] ?? ''),
                'en' => trim($_POST['name_en'] ?? ''),
            ]);
            $description = json_encode([
                'ar' => trim($_POST['desc_ar'] ?? ''),
                'fr' => trim($_POST['desc_fr'] ?? ''),
                'en' => trim($_POST['desc_en'] ?? ''),
            ]);
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
        Vous devez d'abord <a href="types.php" style="color: inherit; font-weight: bold;">créer des Types</a> avant de
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

            <div class="grid grid-3">
                <div class="form-group">
                    <label class="form-label">Nom (Arabe)</label>
                    <input type="text" name="name_ar" class="form-input font-arabic" placeholder="المؤمنون" dir="rtl">
                </div>
                <div class="form-group">
                    <label class="form-label">Nom (Français) *</label>
                    <input type="text" name="name_fr" class="form-input" placeholder="Croyants" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Nom (Anglais)</label>
                    <input type="text" name="name_en" class="form-input" placeholder="Believers">
                </div>
            </div>

            <div class="grid grid-3">
                <div class="form-group">
                    <label class="form-label">Description (Arabe)</label>
                    <input type="text" name="desc_ar" class="form-input font-arabic" dir="rtl">
                </div>
                <div class="form-group">
                    <label class="form-label">Description (Français)</label>
                    <input type="text" name="desc_fr" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Description (Anglais)</label>
                    <input type="text" name="desc_en" class="form-input">
                </div>
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
                            <a href="assignments.php?category_id=<?= $cat['id'] ?>" class="btn btn-sm btn-secondary">
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
    // Icon picker
    document.querySelectorAll('.icon-option').forEach(option => {
        option.addEventListener('click', () => {
            document.querySelectorAll('.icon-option').forEach(o => o.classList.remove('selected'));
            option.classList.add('selected');
            document.getElementById('icon-input').value = option.dataset.icon;
        });
    });
</script>

<?php adminFooter(); ?>