<?php
/**
 * Sadaa (صدى) - Types Management
 */

require_once __DIR__ . '/layout.php';

$message = '';
$error = '';

// Handle form submissions
if ($_POST) {
    if (isset($_POST['add_type'])) {
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
            $icon = trim($_POST['icon'] ?? 'mdi:tag');
            $color = trim($_POST['color'] ?? '#C99B35');
            $slug = trim($_POST['slug'] ?? '');

            if (!$slug) {
                $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $_POST['name_en'] ?? $_POST['name_fr'] ?? 'type'));
            }

            $stmt = $pdo->prepare("INSERT INTO types (name, description, icon, color, slug, sort_order) 
                                   VALUES (?, ?, ?, ?, ?, (SELECT COALESCE(MAX(t.sort_order), 0) + 1 FROM types t))");
            $stmt->execute([$name, $description, $icon, $color, $slug]);
            $message = 'Type ajouté avec succès';
        } catch (PDOException $e) {
            $error = 'Erreur: ' . $e->getMessage();
        }
    }

    if (isset($_POST['delete_type'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM types WHERE id = ?");
            $stmt->execute([(int) $_POST['type_id']]);
            $message = 'Type supprimé';
        } catch (PDOException $e) {
            $error = 'Erreur: ' . $e->getMessage();
        }
    }

    if (isset($_POST['update_type'])) {
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

            $stmt = $pdo->prepare("UPDATE types SET name = ?, description = ?, icon = ?, color = ? WHERE id = ?");
            $stmt->execute([$name, $description, $_POST['icon'], $_POST['color'], (int) $_POST['type_id']]);
            $message = 'Type mis à jour';
        } catch (PDOException $e) {
            $error = 'Erreur: ' . $e->getMessage();
        }
    }
}

// Get all types
$types = [];
try {
    $stmt = $pdo->query("SELECT t.*, (SELECT COUNT(*) FROM categories c WHERE c.type_id = t.id) as category_count 
                         FROM types t ORDER BY t.sort_order ASC");
    $types = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Erreur de chargement: ' . $e->getMessage();
}

// Common icons for types
$commonIcons = [
    'mdi:emoticon-outline',
    'mdi:emoticon-happy-outline',
    'mdi:emoticon-sad-outline',
    'mdi:account-group',
    'mdi:account-heart',
    'mdi:account-off',
    'mdi:atom',
    'mdi:earth',
    'mdi:dna',
    'mdi:rocket-launch',
    'mdi:book-open-page-variant',
    'mdi:lightbulb',
    'mdi:heart',
    'mdi:star',
    'mdi:shield',
    'mdi:sword',
    'mdi:crown',
    'mdi:flower',
    'mdi:tree',
    'mdi:water',
    'mdi:fire',
    'mdi:moon-waning-crescent',
    'mdi:weather-sunny',
    'mdi:cloud',
    'mdi:shape',
    'mdi:tag',
    'mdi:label',
    'mdi:folder',
];

adminHeader('Gestion des Types');
?>

<div class="page-header">
    <h1 class="page-title">Types</h1>
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

<!-- Add Type Form -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Ajouter un Type</h2>
    </div>
    <form method="post">
        <div class="grid grid-3">
            <div class="form-group">
                <label class="form-label">Nom (Arabe)</label>
                <input type="text" name="name_ar" class="form-input font-arabic" placeholder="حالة نفسية" dir="rtl">
            </div>
            <div class="form-group">
                <label class="form-label">Nom (Français)</label>
                <input type="text" name="name_fr" class="form-input" placeholder="État d'esprit" required>
            </div>
            <div class="form-group">
                <label class="form-label">Nom (Anglais)</label>
                <input type="text" name="name_en" class="form-input" placeholder="State of Mind">
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

        <div class="grid grid-3">
            <div class="form-group">
                <label class="form-label">Slug</label>
                <input type="text" name="slug" class="form-input" placeholder="etat-esprit">
            </div>
            <div class="form-group">
                <label class="form-label">Couleur</label>
                <input type="color" name="color" class="form-input" value="#C99B35"
                    style="height: 42px; padding: 0.25rem;">
            </div>
            <div class="form-group">
                <label class="form-label">Icône</label>
                <input type="text" name="icon" id="icon-input" class="form-input" value="mdi:tag" placeholder="mdi:tag">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Choisir une icône</label>
            <div class="icon-picker">
                <?php foreach ($commonIcons as $icon): ?>
                    <div class="icon-option" data-icon="<?= $icon ?>">
                        <iconify-icon icon="<?= $icon ?>"></iconify-icon>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <button type="submit" name="add_type" class="btn btn-primary">
            <iconify-icon icon="mdi:plus"></iconify-icon>
            Ajouter
        </button>
    </form>
</div>

<!-- Types List -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Types existants</h2>
    </div>

    <?php if (count($types) === 0): ?>
        <p class="text-muted text-center" style="padding: 2rem;">Aucun type. Créez-en un ci-dessus.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Icône</th>
                    <th>Nom</th>
                    <th>Catégories</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($types as $type):
                    $name = json_decode($type['name'], true);
                    ?>
                    <tr>
                        <td>
                            <span style="font-size: 1.5rem; color: <?= $type['color'] ?>;">
                                <iconify-icon icon="<?= htmlspecialchars($type['icon']) ?>"></iconify-icon>
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
                            <span class="badge badge-primary">
                                <?= $type['category_count'] ?>
                            </span>
                        </td>
                        <td>
                            <form method="post" style="display: inline;" onsubmit="return confirm('Supprimer ce type?');">
                                <input type="hidden" name="type_id" value="<?= $type['id'] ?>">
                                <button type="submit" name="delete_type" class="btn btn-sm btn-danger">
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