<?php
/**
 * Sadaa (صدى) - Migration Script
 * Fix missing sort_order columns
 */

require_once __DIR__ . '/config/db.php';

try {
    echo "Starting migration...\n";

    // 1. Fix ayah_categories
    echo "Checking table: ayah_categories...\n";
    $stmt = $pdo->query("DESC ayah_categories");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('sort_order', $cols)) {
        echo "Adding sort_order to ayah_categories...\n";
        $pdo->exec("ALTER TABLE ayah_categories ADD COLUMN sort_order INT DEFAULT 0 AFTER assignment_group_id");
        echo "Successfully added sort_order to ayah_categories.\n";
    } else {
        echo "sort_order already exists in ayah_categories.\n";
    }

    // 2. Fix backgrounds
    echo "Checking table: backgrounds...\n";
    try {
        $stmt = $pdo->query("DESC backgrounds");
        $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('sort_order', $cols)) {
            echo "Adding sort_order to backgrounds...\n";
            $pdo->exec("ALTER TABLE backgrounds ADD COLUMN sort_order INT DEFAULT 0 AFTER category_id");
            echo "Successfully added sort_order to backgrounds.\n";
        } else {
            echo "sort_order already exists in backgrounds.\n";
        }
    } catch (PDOException $e) {
        echo "Table 'backgrounds' error (maybe doesn't exist yet): " . $e->getMessage() . "\n";
    }

    echo "Migration completed successfully.\n";

} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
