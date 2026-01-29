<?php
/**
 * Sadaa (صدى) - Database Backup & Restore
 *
 * Export and import database functionality
 */

require_once 'layout.php';

// Create backup directory if it doesn't exist
$backupDir = __DIR__ . '/../backups';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// Tables to backup (in order for foreign key constraints)
$tables = [
    'settings',
    'languages',
    'types',
    'categories',
    'books',
    'surahs',
    'ayahs',
    'ayah_categories',
    'imports'
];

$message = '';
$messageType = '';

// Handle Export
if (isset($_POST['action']) && $_POST['action'] === 'export') {
    $format = $_POST['format'] ?? 'sql';
    $timestamp = date('Y-m-d_H-i-s');

    try {
        if ($format === 'sql') {
            $filename = "sadaa_backup_{$timestamp}.sql";
            $filepath = "{$backupDir}/{$filename}";

            $sql = "-- Sadaa Database Backup\n";
            $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
            $sql .= "-- ----------------------------------------\n\n";
            $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

            foreach ($tables as $table) {
                // Check if table exists
                $check = $pdo->query("SHOW TABLES LIKE '{$table}'");
                if ($check->rowCount() === 0) continue;

                // Get create table statement
                $create = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch();
                $sql .= "-- Table: {$table}\n";
                $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
                $sql .= $create['Create Table'] . ";\n\n";

                // Get data
                $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
                if (count($rows) > 0) {
                    $columns = array_keys($rows[0]);
                    $columnList = '`' . implode('`, `', $columns) . '`';

                    foreach ($rows as $row) {
                        $values = array_map(function($val) use ($pdo) {
                            if ($val === null) return 'NULL';
                            return $pdo->quote($val);
                        }, array_values($row));
                        $sql .= "INSERT INTO `{$table}` ({$columnList}) VALUES (" . implode(', ', $values) . ");\n";
                    }
                    $sql .= "\n";
                }
            }

            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

            file_put_contents($filepath, $sql);

            // Download the file
            header('Content-Type: application/sql');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);
            exit;

        } elseif ($format === 'json') {
            $filename = "sadaa_backup_{$timestamp}.json";
            $filepath = "{$backupDir}/{$filename}";

            $data = [
                'meta' => [
                    'app' => 'Sadaa',
                    'version' => '1.0',
                    'generated' => date('Y-m-d H:i:s'),
                    'tables' => []
                ],
                'data' => []
            ];

            foreach ($tables as $table) {
                $check = $pdo->query("SHOW TABLES LIKE '{$table}'");
                if ($check->rowCount() === 0) continue;

                $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
                $data['data'][$table] = $rows;
                $data['meta']['tables'][$table] = count($rows);
            }

            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            file_put_contents($filepath, $json);

            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);
            exit;
        }

    } catch (Exception $e) {
        $message = __('backup.export_error') . ': ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Handle Import
if (isset($_POST['action']) && $_POST['action'] === 'import') {
    if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
        $message = __('backup.upload_error');
        $messageType = 'error';
    } else {
        $file = $_FILES['backup_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        try {
            $pdo->beginTransaction();

            if ($ext === 'sql') {
                $sql = file_get_contents($file['tmp_name']);

                // Split into individual statements
                $statements = array_filter(array_map('trim', explode(';', $sql)));

                foreach ($statements as $statement) {
                    if (!empty($statement) && !preg_match('/^--/', $statement)) {
                        $pdo->exec($statement);
                    }
                }

            } elseif ($ext === 'json') {
                $json = file_get_contents($file['tmp_name']);
                $data = json_decode($json, true);

                if (!$data || !isset($data['data'])) {
                    throw new Exception(__('backup.invalid_json'));
                }

                // Disable foreign key checks
                $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

                foreach ($tables as $table) {
                    if (!isset($data['data'][$table])) continue;

                    // Clear existing data
                    $pdo->exec("DELETE FROM `{$table}`");

                    // Insert new data
                    foreach ($data['data'][$table] as $row) {
                        $columns = array_keys($row);
                        $columnList = '`' . implode('`, `', $columns) . '`';
                        $placeholders = ':' . implode(', :', $columns);

                        $stmt = $pdo->prepare("INSERT INTO `{$table}` ({$columnList}) VALUES ({$placeholders})");
                        foreach ($row as $key => $value) {
                            $stmt->bindValue(":{$key}", $value);
                        }
                        $stmt->execute();
                    }
                }

                // Re-enable foreign key checks
                $pdo->exec('SET FOREIGN_KEY_CHECKS=1');

            } else {
                throw new Exception(__('backup.invalid_format'));
            }

            $pdo->commit();
            $message = __('backup.import_success');
            $messageType = 'success';

        } catch (Exception $e) {
            $pdo->rollBack();
            $message = __('backup.import_error') . ': ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Get existing backups
$backups = [];
if (is_dir($backupDir)) {
    $files = glob("{$backupDir}/sadaa_backup_*");
    foreach ($files as $file) {
        $backups[] = [
            'name' => basename($file),
            'path' => $file,
            'size' => filesize($file),
            'date' => filemtime($file),
            'format' => pathinfo($file, PATHINFO_EXTENSION)
        ];
    }
    // Sort by date descending
    usort($backups, fn($a, $b) => $b['date'] - $a['date']);
}

// Get database stats
$stats = [];
foreach ($tables as $table) {
    try {
        $count = $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
        $stats[$table] = $count;
    } catch (Exception $e) {
        $stats[$table] = 0;
    }
}

adminHeader(__('nav.backup'));
?>

<div class="page-header">
    <h1 class="page-title"><?= __('backup.title') ?></h1>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<div class="grid grid-2">
    <!-- Export Card -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <iconify-icon icon="mdi:database-export" style="vertical-align: middle; margin-right: 0.5rem;"></iconify-icon>
                <?= __('backup.export') ?>
            </h2>
        </div>

        <p class="text-muted mb-2"><?= __('backup.export_desc') ?></p>

        <form method="post">
            <input type="hidden" name="action" value="export">

            <div class="form-group">
                <label class="form-label"><?= __('backup.format') ?></label>
                <select name="format" class="form-select">
                    <option value="sql">SQL (.sql)</option>
                    <option value="json">JSON (.json)</option>
                </select>
            </div>

            <div class="card" style="background: var(--bg-dark); padding: 1rem;">
                <h4 class="mb-1" style="font-size: 0.875rem; color: var(--text-secondary);">
                    <?= __('backup.tables_included') ?>
                </h4>
                <div class="grid grid-2" style="gap: 0.5rem; font-size: 0.875rem;">
                    <?php foreach ($stats as $table => $count): ?>
                        <div class="flex items-center justify-between">
                            <span><?= $table ?></span>
                            <span class="badge badge-primary"><?= number_format($count) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" class="btn btn-primary mt-2">
                <iconify-icon icon="mdi:download"></iconify-icon>
                <?= __('backup.download') ?>
            </button>
        </form>
    </div>

    <!-- Import Card -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <iconify-icon icon="mdi:database-import" style="vertical-align: middle; margin-right: 0.5rem;"></iconify-icon>
                <?= __('backup.import') ?>
            </h2>
        </div>

        <p class="text-muted mb-2"><?= __('backup.import_desc') ?></p>

        <div class="alert alert-warning" style="background: #ff980020; color: #ff9800;">
            <iconify-icon icon="mdi:alert" style="vertical-align: middle; margin-right: 0.5rem;"></iconify-icon>
            <?= __('backup.import_warning') ?>
        </div>

        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="import">

            <div class="form-group">
                <label class="form-label"><?= __('backup.select_file') ?></label>
                <input type="file" name="backup_file" class="form-input" accept=".sql,.json" required>
                <small class="text-muted"><?= __('backup.accepted_formats') ?>: .sql, .json</small>
            </div>

            <button type="submit" class="btn btn-danger" onclick="return confirm('<?= __('backup.confirm_import') ?>')">
                <iconify-icon icon="mdi:upload"></iconify-icon>
                <?= __('backup.restore') ?>
            </button>
        </form>
    </div>
</div>

<!-- Existing Backups -->
<?php if (!empty($backups)): ?>
<div class="card mt-2">
    <div class="card-header">
        <h2 class="card-title">
            <iconify-icon icon="mdi:history" style="vertical-align: middle; margin-right: 0.5rem;"></iconify-icon>
            <?= __('backup.recent_backups') ?>
        </h2>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th><?= __('backup.filename') ?></th>
                <th><?= __('backup.format') ?></th>
                <th><?= __('backup.size') ?></th>
                <th><?= __('backup.date') ?></th>
                <th><?= __('labels.actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (array_slice($backups, 0, 10) as $backup): ?>
                <tr>
                    <td><?= htmlspecialchars($backup['name']) ?></td>
                    <td>
                        <span class="badge <?= $backup['format'] === 'sql' ? 'badge-primary' : 'badge-success' ?>">
                            <?= strtoupper($backup['format']) ?>
                        </span>
                    </td>
                    <td><?= number_format($backup['size'] / 1024, 2) ?> KB</td>
                    <td><?= date('Y-m-d H:i', $backup['date']) ?></td>
                    <td>
                        <a href="?download=<?= urlencode($backup['name']) ?>" class="btn btn-sm btn-secondary">
                            <iconify-icon icon="mdi:download"></iconify-icon>
                        </a>
                        <a href="?delete=<?= urlencode($backup['name']) ?>"
                           class="btn btn-sm btn-danger"
                           onclick="return confirm('<?= __('backup.confirm_delete') ?>')">
                            <iconify-icon icon="mdi:delete"></iconify-icon>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php
// Handle download of existing backup
if (isset($_GET['download'])) {
    $filename = basename($_GET['download']);
    $filepath = "{$backupDir}/{$filename}";
    if (file_exists($filepath) && strpos($filename, 'sadaa_backup_') === 0) {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        header('Content-Type: ' . ($ext === 'sql' ? 'application/sql' : 'application/json'));
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    }
}

// Handle delete of backup
if (isset($_GET['delete'])) {
    $filename = basename($_GET['delete']);
    $filepath = "{$backupDir}/{$filename}";
    if (file_exists($filepath) && strpos($filename, 'sadaa_backup_') === 0) {
        unlink($filepath);
        header('Location: backup.php?deleted=1');
        exit;
    }
}

adminFooter();
?>
