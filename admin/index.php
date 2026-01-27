<?php
/**
 * Sadaa (صدى) - Admin Dashboard
 */

require_once __DIR__ . '/layout.php';

// Get stats
$stats = [
    'types' => 0,
    'categories' => 0,
    'surahs' => 0,
    'ayahs' => 0,
    'languages' => 0,
];

try {
    $stats['types'] = $pdo->query("SELECT COUNT(*) FROM types")->fetchColumn();
    $stats['categories'] = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    $stats['surahs'] = $pdo->query("SELECT COUNT(*) FROM surahs")->fetchColumn();
    $stats['ayahs'] = $pdo->query("SELECT COUNT(*) FROM ayahs")->fetchColumn();
    $stats['languages'] = $pdo->query("SELECT COUNT(*) FROM languages WHERE is_active = 1")->fetchColumn();
} catch (PDOException $e) {
    // Tables may not exist yet
}

// Get recent imports
$recentImports = [];
try {
    $stmt = $pdo->query("SELECT * FROM imports ORDER BY created_at DESC LIMIT 5");
    $recentImports = $stmt->fetchAll();
} catch (PDOException $e) {
    // Table may not exist
}

adminHeader('Tableau de bord');
?>

<div class="page-header">
    <h1 class="page-title">Tableau de bord</h1>
    <a href="../public/index.php" target="_blank" class="btn btn-secondary">
        <iconify-icon icon="mdi:open-in-new"></iconify-icon>
        Voir le site
    </a>
</div>

<!-- Stats Grid -->
<div class="grid grid-4 mb-2">
    <div class="card">
        <div class="stat-card">
            <div class="stat-icon">
                <iconify-icon icon="mdi:shape"></iconify-icon>
            </div>
            <div>
                <div class="stat-value">
                    <?= $stats['types'] ?>
                </div>
                <div class="stat-label">Types</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="stat-card">
            <div class="stat-icon">
                <iconify-icon icon="mdi:tag-multiple"></iconify-icon>
            </div>
            <div>
                <div class="stat-value">
                    <?= $stats['categories'] ?>
                </div>
                <div class="stat-label">Catégories</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="stat-card">
            <div class="stat-icon">
                <iconify-icon icon="mdi:book-open-page-variant"></iconify-icon>
            </div>
            <div>
                <div class="stat-value">
                    <?= $stats['surahs'] ?>
                </div>
                <div class="stat-label">Sourates</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="stat-card">
            <div class="stat-icon">
                <iconify-icon icon="mdi:text-box-multiple"></iconify-icon>
            </div>
            <div>
                <div class="stat-value">
                    <?= number_format($stats['ayahs']) ?>
                </div>
                <div class="stat-label">Versets</div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Actions rapides</h2>
    </div>
    <div class="flex gap-2">
        <a href="import.php" class="btn btn-primary">
            <iconify-icon icon="mdi:cloud-download"></iconify-icon>
            Importer le Coran
        </a>
        <a href="types.php" class="btn btn-secondary">
            <iconify-icon icon="mdi:plus"></iconify-icon>
            Nouveau Type
        </a>
        <a href="categories.php" class="btn btn-secondary">
            <iconify-icon icon="mdi:plus"></iconify-icon>
            Nouvelle Catégorie
        </a>
        <a href="assignments.php" class="btn btn-secondary">
            <iconify-icon icon="mdi:link-variant"></iconify-icon>
            Assigner des versets
        </a>
    </div>
</div>

<?php if ($stats['surahs'] === 0): ?>
    <!-- Getting Started -->
    <div class="card" style="border-color: var(--color-primary);">
        <div class="card-header">
            <h2 class="card-title" style="color: var(--color-primary);">
                <iconify-icon icon="mdi:rocket-launch"></iconify-icon>
                Pour commencer
            </h2>
        </div>
        <ol style="padding-left: 1.5rem; line-height: 2;">
            <li>Configurez la base de données MySQL avec le fichier <code>config/database_schema.sql</code></li>
            <li>Allez sur <a href="import.php" style="color: var(--color-primary);">Import Coran</a> pour télécharger les
                sourates</li>
            <li>Créez des <a href="types.php" style="color: var(--color-primary);">Types</a> et <a href="categories.php"
                    style="color: var(--color-primary);">Catégories</a></li>
            <li>Utilisez <a href="assignments.php" style="color: var(--color-primary);">Assignations</a> pour classer les
                versets par catégorie</li>
        </ol>
    </div>
<?php endif; ?>

<!-- Recent Imports -->
<?php if (count($recentImports) > 0): ?>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Imports récents</h2>
            <a href="imports.php" class="btn btn-sm btn-secondary">Voir tout</a>
        </div>
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Langues</th>
                    <th>Statut</th>
                    <th>Progression</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentImports as $import):
                    $languages = json_decode($import['languages'], true) ?? [];
                    ?>
                    <tr>
                        <td>
                            <?= date('d/m/Y H:i', strtotime($import['created_at'])) ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($import['type']) ?>
                        </td>
                        <td>
                            <?= implode(', ', $languages) ?>
                        </td>
                        <td>
                            <?php
                            $statusClass = match ($import['status']) {
                                'completed' => 'badge-success',
                                'running' => 'badge-warning',
                                'failed' => 'badge-danger',
                                default => 'badge-primary'
                            };
                            ?>
                            <span class="badge <?= $statusClass ?>">
                                <?= ucfirst($import['status']) ?>
                            </span>
                        </td>
                        <td>
                            <?= $import['surahs_imported'] ?>/
                            <?= $import['total_surahs'] ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php adminFooter(); ?>