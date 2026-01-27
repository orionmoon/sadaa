<?php
/**
 * Sadaa (صدى) - Import History
 */

require_once __DIR__ . '/layout.php';

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

<?php adminFooter(); ?>