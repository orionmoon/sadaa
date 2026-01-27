<?php
/**
 * Sadaa (صدى) - Settings
 */

require_once __DIR__ . '/layout.php';

$message = '';
$error = '';

// Handle form submissions
if ($_POST) {
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

    if (isset($_POST['add_language'])) {
        try {
            $code = trim($_POST['lang_code'] ?? '');
            $name = json_encode([
                'ar' => trim($_POST['lang_name_ar'] ?? ''),
                'fr' => trim($_POST['lang_name_fr'] ?? ''),
                'en' => trim($_POST['lang_name_en'] ?? ''),
            ]);
            $edition = trim($_POST['quran_edition'] ?? '');
            $isRtl = isset($_POST['is_rtl']) ? 1 : 0;

            $stmt = $pdo->prepare("INSERT INTO languages (code, name, quran_edition, is_rtl, is_active, sort_order) 
                                   VALUES (?, ?, ?, ?, 1, (SELECT COALESCE(MAX(l.sort_order), 0) + 1 FROM languages l))");
            $stmt->execute([$code, $name, $edition, $isRtl]);
            $message = 'Langue ajoutée';
        } catch (PDOException $e) {
            $error = 'Erreur: ' . $e->getMessage();
        }
    }

    if (isset($_POST['change_password'])) {
        $newPassword = $_POST['new_password'] ?? '';
        if (strlen($newPassword) < 6) {
            $error = 'Le mot de passe doit contenir au moins 6 caractères';
        } else {
            // In a real app, you would hash this and store in database
            $message = 'Pour changer le mot de passe, modifiez la constante ADMIN_PASSWORD dans admin/layout.php';
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

// Get languages
$languages = [];
try {
    $stmt = $pdo->query("SELECT * FROM languages ORDER BY sort_order ASC");
    $languages = $stmt->fetchAll();
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
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Add Language -->
    <details style="margin-top: 1rem;">
        <summary style="cursor: pointer; color: var(--color-primary); font-weight: 500;">
            <iconify-icon icon="mdi:plus"></iconify-icon>
            Ajouter une langue
        </summary>
        <form method="post" style="margin-top: 1rem; padding: 1rem; background: var(--bg-dark); border-radius: 0.5rem;">
            <div class="grid grid-3">
                <div class="form-group">
                    <label class="form-label">Code (ex: es, de, tr)</label>
                    <input type="text" name="lang_code" class="form-input" maxlength="10" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Édition Coran (AlQuran.cloud)</label>
                    <input type="text" name="quran_edition" class="form-input" placeholder="es.cortes">
                </div>
                <div class="form-group">
                    <label class="form-label">RTL (droite à gauche)</label>
                    <label style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem;">
                        <input type="checkbox" name="is_rtl" style="accent-color: var(--color-primary);">
                        <span>Oui</span>
                    </label>
                </div>
            </div>
            <div class="grid grid-3">
                <div class="form-group">
                    <label class="form-label">Nom (Arabe)</label>
                    <input type="text" name="lang_name_ar" class="form-input font-arabic" dir="rtl">
                </div>
                <div class="form-group">
                    <label class="form-label">Nom (Français)</label>
                    <input type="text" name="lang_name_fr" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Nom (Anglais)</label>
                    <input type="text" name="lang_name_en" class="form-input">
                </div>
            </div>
            <button type="submit" name="add_language" class="btn btn-primary">
                <iconify-icon icon="mdi:plus"></iconify-icon>
                Ajouter
            </button>
        </form>
    </details>
</div>

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
        <p class="text-muted mt-1" style="font-size: 0.8rem;">
            Note: Dans cette version, le mot de passe est stocké dans le fichier <code>admin/layout.php</code>
        </p>
    </form>
</div>

<?php adminFooter(); ?>