<?php
/**
 * Sadaa (صدى) - Types Management
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

// Handle form submissions
if ($_POST) {
    if (isset($_POST['add_type'])) {
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
        <!-- Dynamic Name Fields -->
        <div class="grid grid-3">
            <?php foreach ($activeLanguages as $lang):
                $langName = json_decode($lang['name'], true);
                $isRtl = $lang['is_rtl'];
                $isRequired = $lang['code'] === 'fr'; // French is required
                ?>
                <div class="form-group">
                    <label class="form-label">Nom
                        (<?= htmlspecialchars($langName['fr'] ?? $lang['code']) ?>)<?= $isRequired ? ' *' : '' ?></label>
                    <input type="text" name="name_<?= $lang['code'] ?>"
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
                    <input type="text" name="desc_<?= $lang['code'] ?>"
                        class="form-input<?= $isRtl ? ' font-arabic' : '' ?>" <?= $isRtl ? 'dir="rtl"' : '' ?>>
                </div>
            <?php endforeach; ?>
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

<!-- Edit Type Form (Hidden by default) -->
<div class="card" id="edit-type-form" style="display: none;">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h2 class="card-title">Modifier un Type</h2>
        <button type="button" class="btn btn-sm" onclick="cancelEdit()">
            <iconify-icon icon="mdi:close"></iconify-icon>
            Annuler
        </button>
    </div>
    <form method="post" id="edit-form">
        <input type="hidden" name="type_id" id="edit-type-id">

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

        <div class="grid grid-2">
            <div class="form-group">
                <label class="form-label">Couleur</label>
                <input type="color" name="color" id="edit-color" class="form-input"
                    style="height: 42px; padding: 0.25rem;">
            </div>
            <div class="form-group">
                <label class="form-label">Icône</label>
                <input type="text" name="icon" id="edit-icon-input" class="form-input" placeholder="mdi:tag">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Choisir une icône</label>
            <div class="icon-picker" id="edit-icon-picker">
                <?php foreach ($commonIcons as $icon): ?>
                    <div class="icon-option" data-icon="<?= $icon ?>">
                        <iconify-icon icon="<?= $icon ?>"></iconify-icon>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <button type="submit" name="update_type" class="btn btn-primary">
            <iconify-icon icon="mdi:content-save"></iconify-icon>
            Enregistrer les modifications
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
                            <?php
                            $typeJson = htmlspecialchars(json_encode([
                                "id" => $type["id"],
                                "name" => json_decode($type["name"], true),
                                "description" => json_decode($type["description"], true),
                                "icon" => $type["icon"],
                                "color" => $type["color"]
                            ]), ENT_QUOTES, 'UTF-8');
                            ?>
                            <button type="button" class="btn btn-sm btn-secondary" onclick='editType(<?= $typeJson ?>)'>
                                <iconify-icon icon="mdi:pencil"></iconify-icon>
                            </button>
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
    // Active language codes for dynamic form handling
    const languageCodes = <?= json_encode(array_column($activeLanguages, 'code')) ?>;

    // Icon picker for add form
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

    function editType(type) {
        // Show edit form
        document.getElementById('edit-type-form').style.display = 'block';

        // Populate form fields
        document.getElementById('edit-type-id').value = type.id;

        // Dynamically populate name and description fields for all languages
        languageCodes.forEach(code => {
            const nameField = document.getElementById('edit-name-' + code);
            const descField = document.getElementById('edit-desc-' + code);
            if (nameField) nameField.value = (type.name && type.name[code]) || '';
            if (descField) descField.value = (type.description && type.description[code]) || '';
        });

        document.getElementById('edit-color').value = type.color || '#C99B35';
        document.getElementById('edit-icon-input').value = type.icon || 'mdi:tag';

        // Select the icon in the picker
        document.querySelectorAll('#edit-icon-picker .icon-option').forEach(o => o.classList.remove('selected'));
        const selectedIcon = document.querySelector(`#edit-icon-picker .icon-option[data-icon="${type.icon}"]`);
        if (selectedIcon) {
            selectedIcon.classList.add('selected');
        }

        // Scroll to edit form
        document.getElementById('edit-type-form').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function cancelEdit() {
        // Hide edit form
        document.getElementById('edit-type-form').style.display = 'none';

        // Clear form fields
        document.getElementById('edit-form').reset();

        // Clear icon selection
        document.querySelectorAll('#edit-icon-picker .icon-option').forEach(o => o.classList.remove('selected'));
    }
</script>

<?php adminFooter(); ?>