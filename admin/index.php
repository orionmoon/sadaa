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

// Get distribution by type
$typeStats = [];
$totalGroups = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM assignment_groups");
    $totalGroups = (int) $stmt->fetch()['total'];
} catch (PDOException $e) {
}

try {
    $stmt = $pdo->query("
        SELECT 
            t.id,
            t.name,
            COUNT(DISTINCT c.id) as category_count,
            COUNT(DISTINCT ag.id) as group_count,
            COALESCE(SUM(sub.ayah_count), 0) as ayah_assigned
        FROM types t
        LEFT JOIN categories c ON c.type_id = t.id
        LEFT JOIN assignment_groups ag ON ag.category_id = c.id
        LEFT JOIN (
            SELECT ac.assignment_group_id, COUNT(*) as ayah_count
            FROM ayah_categories ac
            GROUP BY ac.assignment_group_id
        ) sub ON sub.assignment_group_id = ag.id
        GROUP BY t.id, t.name
        ORDER BY t.sort_order ASC
    ");
    $typeStats = $stmt->fetchAll();
} catch (PDOException $e) {
}

// Get distribution by category
$categoryStats = [];
try {
    $stmt = $pdo->query("
        SELECT 
            c.id,
            c.name,
            t.name as type_name,
            COALESCE(GROUP_COUNT.group_count, 0) as group_count,
            COALESCE(AYAH_COUNT.ayah_count, 0) as ayah_assigned
        FROM categories c
        LEFT JOIN types t ON c.type_id = t.id
        LEFT JOIN (
            SELECT category_id, COUNT(*) as group_count
            FROM assignment_groups
            GROUP BY category_id
        ) GROUP_COUNT ON GROUP_COUNT.category_id = c.id
        LEFT JOIN (
            SELECT ac.category_id, COUNT(*) as ayah_count
            FROM ayah_categories ac
            GROUP BY ac.category_id
        ) AYAH_COUNT ON AYAH_COUNT.category_id = c.id
        ORDER BY t.sort_order ASC, c.sort_order ASC
    ");
    $categoryStats = $stmt->fetchAll();
} catch (PDOException $e) {
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
    <a href="/" target="_blank" class="btn btn-secondary">
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
        <a href="/admin/import" class="btn btn-primary">
            <iconify-icon icon="mdi:cloud-download"></iconify-icon>
            Importer le Coran
        </a>
        <a href="/admin/types" class="btn btn-secondary">
            <iconify-icon icon="mdi:plus"></iconify-icon>
            Nouveau Type
        </a>
        <a href="/admin/categories" class="btn btn-secondary">
            <iconify-icon icon="mdi:plus"></iconify-icon>
            Nouvelle Catégorie
        </a>
        <a href="/admin/assignments" class="btn btn-secondary">
            <iconify-icon icon="mdi:link-variant"></iconify-icon>
            Assigner des versets
        </a>
    </div>
</div>

<!-- Distribution by Type -->
<?php if (count($typeStats) > 0): ?>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <iconify-icon icon="mdi:chart-pie"></iconify-icon>
                Distribution par Type
            </h2>
        </div>
        <div class="grid grid-3">
            <?php foreach ($typeStats as $type):
                $typeName = json_decode($type['name'], true);
                $frName = $typeName['fr'] ?? $typeName['en'] ?? 'Type';
                $assigned = (int) $type['ayah_assigned'];
                $groups = (int) $type['group_count'];
                $categories = (int) $type['category_count'];
                $percent = $totalGroups > 0 ? round(($groups / $totalGroups) * 100, 1) : 0;
                ?>
                <div style="background: var(--bg-dark); border-radius: 0.5rem; padding: 1rem;">
                    <div class="flex justify-between items-center mb-1">
                        <span style="font-weight: 600;"><?= htmlspecialchars($frName) ?></span>
                        <span class="badge badge-primary"><?= $categories ?> cat.</span>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: 600; color: var(--color-primary);">
                        <?= number_format($assigned) ?>
                    </div>
                    <div class="text-muted" style="font-size: 0.8rem;">versets assignés</div>
                    <div class="flex justify-between items-center mt-2" style="font-size: 0.8rem;">
                        <span><?= $groups ?> groupes</span>
                        <span class="badge badge-secondary"><?= $percent ?>%</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Distribution by Category -->
<?php if (count($categoryStats) > 0): ?>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <iconify-icon icon="mdi:chart-bar"></iconify-icon>
                Distribution par Catégorie
            </h2>
            <span class="text-muted" style="font-size: 0.875rem;">(% groupes / total)</span>
        </div>
        <div style="max-height: 400px; overflow-y: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Catégorie</th>
                        <th>Type</th>
                        <th>Versets</th>
                        <th>Groupes</th>
                        <th>Progression</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categoryStats as $cat):
                        $catName = json_decode($cat['name'], true);
                        $frName = $catName['fr'] ?? $catName['en'] ?? 'Catégorie';
                        $typeName = json_decode($cat['type_name'], true);
                        $typeFr = $typeName['fr'] ?? $typeName['en'] ?? 'Type';
                        $assigned = (int) $cat['ayah_assigned'];
                        $groups = (int) $cat['group_count'];
                        $percent = $totalGroups > 0 ? round(($groups / $totalGroups) * 100, 1) : 0;
                        $progressColor = $percent < 10 ? '#ff6b6b' : ($percent < 50 ? '#ff9800' : '#4caf50');
                        ?>
                        <tr>
                            <td style="font-weight: 500;"><?= htmlspecialchars($frName) ?></td>
                            <td><span class="badge" style="background: var(--bg-dark);"><?= htmlspecialchars($typeFr) ?></span></td>
                            <td><?= number_format($assigned) ?></td>
                            <td><?= $groups ?></td>
                            <td style="width: 200px;">
                                <div class="flex items-center gap-1">
                                    <div style="flex: 1; background: var(--bg-dark); border-radius: 999px; height: 6px; overflow: hidden;">
                                        <div style="background: <?= $progressColor ?>; height: 100%; width: <?= $percent ?>%;"></div>
                                    </div>
                                    <span style="font-size: 0.75rem; color: var(--text-secondary); min-width: 40px;"><?= $percent ?>%</span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

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
            <li>Allez sur <a href="/admin/import" style="color: var(--color-primary);">Import Coran</a> pour télécharger les
                sourates</li>
            <li>Créez des <a href="/admin/types" style="color: var(--color-primary);">Types</a> et <a href="/admin/categories"
                    style="color: var(--color-primary);">Catégories</a></li>
            <li>Utilisez <a href="/admin/assignments" style="color: var(--color-primary);">Assignations</a> pour classer les
                versets par catégorie</li>
        </ol>
    </div>
<?php endif; ?>

<!-- Recent Imports -->
<?php if (count($recentImports) > 0): ?>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Imports récents</h2>
            <a href="/admin/imports" class="btn btn-sm btn-secondary">Voir tout</a>
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