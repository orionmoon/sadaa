<?php
/**
 * Sadaa (صدى) - Import History
 */

require_once __DIR__ . '/layout.php';

$message = '';
$error = '';

// Handle form submissions
if ($_POST) {
    if (isset($_POST['edit_import'])) {
        try {
            $importId = (int) $_POST['import_id'];
            $notes = trim($_POST['notes'] ?? '');

            $stmt = $pdo->prepare("UPDATE imports SET notes = ? WHERE id = ?");
            $stmt->execute([$notes, $importId]);
            $message = 'Notes de l\'import modifiées avec succès';
        } catch (PDOException $e) {
            $error = 'Erreur: ' . $e->getMessage();
        }
    }
}

// Get all imports
$imports = [];
try {
    $stmt = $pdo->query("SELECT * FROM imports ORDER BY created_at DESC");
    $imports = $stmt->fetchAll();
} catch (PDOException $e) {
}

adminHeader('Historique des imports');
?>

<div class="page-header">
    <h1 class="page-title">Historique des imports</h1>
    <a href="import.php" class="btn btn-primary">
        <iconify-icon icon="mdi:plus"></iconify-icon>
        Nouvel import
    </a>
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

<!-- Edit Import Form (Hidden by default) -->
<div class="card" id="edit-import-form" style="display: none;">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h2 class="card-title">Modifier les notes de l'import</h2>
        <button type="button" class="btn btn-sm" onclick="cancelEdit()">
            <iconify-icon icon="mdi:close"></iconify-icon>
            Annuler
        </button>
    </div>
    <form method="post" id="edit-form">
        <input type="hidden" name="import_id" id="edit-import-id">

        <div class="alert alert-info">
            <iconify-icon icon="mdi:information"></iconify-icon>
            <div>
                <strong>Métadonnées automatiques</strong>
                <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem;">
                    Les références d'édition et de traduction ont été récupérées automatiquement depuis l'API lors de l'import.
                    Seules les notes peuvent être modifiées.
                </p>
            </div>
        </div>

        <div class="grid grid-3">
            <div class="form-group">
                <label class="form-label">Édition (lecture seule)</label>
                <input type="text" id="edit-quran-edition" class="form-input" readonly style="background: var(--bg-secondary);">
            </div>
            <div class="form-group">
                <label class="form-label">Version (lecture seule)</label>
                <input type="text" id="edit-quran-version" class="form-input" readonly style="background: var(--bg-secondary);">
            </div>
            <div class="form-group">
                <label class="form-label">Langues (lecture seule)</label>
                <input type="text" id="edit-languages-display" class="form-input" readonly style="background: var(--bg-secondary);">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Notes personnelles</label>
            <textarea name="notes" id="edit-notes" class="form-textarea" rows="5" placeholder="Ajoutez vos notes sur cet import..."></textarea>
        </div>

        <button type="submit" name="edit_import" class="btn btn-primary">
            <iconify-icon icon="mdi:content-save"></iconify-icon>
            Enregistrer les notes
        </button>
    </form>
</div>

<div class="card">
    <?php if (count($imports) === 0): ?>
        <p class="text-muted text-center" style="padding: 2rem;">
            <iconify-icon icon="mdi:history" style="font-size: 3rem;"></iconify-icon><br>
            Aucun import effectué.<br>
            <a href="import.php" style="color: var(--color-primary);">Commencer un import</a>
        </p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Source</th>
                    <th>Langues</th>
                    <th>Statut</th>
                    <th>Progression</th>
                    <th>Durée</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($imports as $import):
                    $languages = json_decode($import['languages'], true) ?? [];
                    $duration = '';
                    if ($import['completed_at'] && $import['created_at']) {
                        $start = strtotime($import['created_at']);
                        $end = strtotime($import['completed_at']);
                        $diff = $end - $start;
                        $duration = $diff < 60 ? $diff . 's' : round($diff / 60, 1) . 'min';
                    }
                    ?>
                    <tr>
                        <td>
                            <?= date('d/m/Y', strtotime($import['created_at'])) ?><br>
                            <small class="text-muted">
                                <?= date('H:i:s', strtotime($import['created_at'])) ?>
                            </small>
                        </td>
                        <td>
                            <?= htmlspecialchars(ucfirst($import['type'])) ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($import['source'] ?? 'N/A') ?>
                        </td>
                        <td>
                            <?php foreach ($languages as $lang): ?>
                                <span class="badge badge-primary" style="font-size: 0.65rem;">
                                    <?= strtoupper($lang) ?>
                                </span>
                            <?php endforeach; ?>
                        </td>
                        <td>
                            <?php
                            $statusClass = match ($import['status']) {
                                'completed' => 'badge-success',
                                'running' => 'badge-warning',
                                'failed' => 'badge-danger',
                                default => 'badge-primary'
                            };
                            $statusIcon = match ($import['status']) {
                                'completed' => 'mdi:check-circle',
                                'running' => 'mdi:loading',
                                'failed' => 'mdi:alert-circle',
                                default => 'mdi:clock-outline'
                            };
                            ?>
                            <span class="badge <?= $statusClass ?>">
                                <iconify-icon icon="<?= $statusIcon ?>"></iconify-icon>
                                <?= ucfirst($import['status']) ?>
                            </span>
                        </td>
                        <td>
                            <?= $import['surahs_imported'] ?>/
                            <?= $import['total_surahs'] ?>
                            <div style="background: var(--border-color); border-radius: 4px; height: 4px; margin-top: 4px;">
                                <div
                                    style="background: var(--color-primary); height: 100%; border-radius: 4px; width: <?= ($import['surahs_imported'] / max($import['total_surahs'], 1)) * 100 ?>%;">
                                </div>
                            </div>
                        </td>
                        <td>
                            <?= $duration ?: '-' ?>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-secondary"
                                onclick='editImport(<?= json_encode([
                                    "id" => $import["id"],
                                    "quran_edition" => $import["quran_edition"],
                                    "quran_version" => $import["quran_version"],
                                    "translation_references" => $import["translation_references"],
                                    "languages" => $languages,
                                    "notes" => $import["notes"]
                                ]) ?>)'>
                                <iconify-icon icon="mdi:pencil"></iconify-icon>
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary"
                                onclick="toggleDetails(<?= $import['id'] ?>)">
                                <iconify-icon icon="mdi:information-outline"></iconify-icon>
                            </button>
                        </td>
                    </tr>
                    <!-- Details row -->
                    <tr id="details-<?= $import['id'] ?>" style="display: none;">
                        <td colspan="8" style="background: var(--bg-secondary); padding: 1rem;">
                            <div style="display: grid; gap: 1rem;">
                                <div class="grid grid-2" style="gap: 1rem;">
                                    <div>
                                        <strong>Édition du Coran:</strong>
                                        <code><?= htmlspecialchars($import['quran_edition'] ?? 'N/A') ?></code>
                                    </div>
                                    <div>
                                        <strong>Version:</strong>
                                        <?= htmlspecialchars($import['quran_version'] ?? 'N/A') ?>
                                    </div>
                                </div>

                                <div>
                                    <strong>Références des traductions:</strong>
                                    <?php
                                    $transRefs = json_decode($import['translation_references'] ?? '{}', true);
                                    if ($transRefs && count($transRefs) > 0):
                                    ?>
                                        <div style="margin-top: 0.5rem; display: grid; gap: 0.5rem;">
                                            <?php foreach ($transRefs as $lang => $ref): ?>
                                                <div style="background: var(--bg-primary); padding: 0.75rem; border-radius: 4px; border-left: 3px solid var(--color-primary);">
                                                    <div style="display: flex; justify-content: space-between; align-items: start; gap: 1rem;">
                                                        <div>
                                                            <span class="badge badge-primary" style="font-size: 0.7rem;"><?= strtoupper($lang) ?></span>
                                                            <strong style="margin-left: 0.5rem;">
                                                                <?php
                                                                if (is_array($ref)) {
                                                                    echo htmlspecialchars($ref['englishName'] ?? $ref['name'] ?? $lang);
                                                                } else {
                                                                    echo htmlspecialchars($ref);
                                                                }
                                                                ?>
                                                            </strong>
                                                        </div>
                                                        <code style="font-size: 0.8rem;">
                                                            <?= htmlspecialchars(is_array($ref) ? $ref['identifier'] : $ref) ?>
                                                        </code>
                                                    </div>
                                                    <?php if (is_array($ref) && isset($ref['type'])): ?>
                                                        <div style="margin-top: 0.25rem; font-size: 0.8rem; color: var(--text-muted);">
                                                            Type: <?= htmlspecialchars(ucfirst($ref['type'])) ?>
                                                            <?php if (isset($ref['format'])): ?>
                                                                | Format: <?= htmlspecialchars($ref['format']) ?>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <p style="margin-top: 0.5rem; color: var(--text-muted); font-style: italic;">Aucune référence disponible</p>
                                    <?php endif; ?>
                                </div>

                                <?php if ($import['metadata']):
                                    $metadata = json_decode($import['metadata'], true);
                                    if ($metadata):
                                ?>
                                <div>
                                    <strong>Métadonnées de l'import:</strong>
                                    <div style="background: var(--bg-primary); padding: 0.5rem; border-radius: 4px; margin-top: 0.5rem; font-size: 0.85rem;">
                                        <?php if (isset($metadata['api_source'])): ?>
                                            <div>Source API: <code><?= htmlspecialchars($metadata['api_source']) ?></code></div>
                                        <?php endif; ?>
                                        <?php if (isset($metadata['import_date'])): ?>
                                            <div>Date d'import: <?= htmlspecialchars($metadata['import_date']) ?></div>
                                        <?php endif; ?>
                                        <?php if (isset($metadata['surah_range'])): ?>
                                            <div>Sourates: <?= htmlspecialchars($metadata['surah_range'][0]) ?> à <?= htmlspecialchars($metadata['surah_range'][1]) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; endif; ?>

                                <?php if ($import['notes']): ?>
                                <div>
                                    <strong>Notes:</strong>
                                    <p style="margin-top: 0.5rem; white-space: pre-wrap; background: var(--bg-primary); padding: 0.75rem; border-radius: 4px;"><?= htmlspecialchars($import['notes']) ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php if ($import['error_message']): ?>
                        <tr>
                            <td colspan="7" style="background: #ff000010; padding: 0.5rem 1rem;">
                                <small class="text-muted">
                                    <iconify-icon icon="mdi:alert"></iconify-icon>
                                    <?= nl2br(htmlspecialchars(substr($import['error_message'], 0, 200))) ?>
                                    <?= strlen($import['error_message']) > 200 ? '...' : '' ?>
                                </small>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>
function editImport(importData) {
    // Show edit form
    document.getElementById('edit-import-form').style.display = 'block';

    // Populate form fields
    document.getElementById('edit-import-id').value = importData.id;
    document.getElementById('edit-quran-edition').value = importData.quran_edition || 'N/A';
    document.getElementById('edit-quran-version').value = importData.quran_version || 'N/A';
    document.getElementById('edit-notes').value = importData.notes || '';

    // Display languages (readonly)
    const langs = importData.languages.map(l => l.toUpperCase()).join(', ');
    document.getElementById('edit-languages-display').value = langs;

    // Scroll to edit form
    document.getElementById('edit-import-form').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function cancelEdit() {
    // Hide edit form
    document.getElementById('edit-import-form').style.display = 'none';

    // Clear form fields
    document.getElementById('edit-form').reset();
}

function toggleDetails(importId) {
    const detailsRow = document.getElementById('details-' + importId);
    if (detailsRow.style.display === 'none') {
        detailsRow.style.display = 'table-row';
    } else {
        detailsRow.style.display = 'none';
    }
}
</script>

<?php adminFooter(); ?>