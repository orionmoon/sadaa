<?php
/**
 * Sadaa - Migration Script for Assignment Groups
 */
require_once __DIR__ . '/../config/db.php';

try {
    echo "Creating assignment_groups table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS assignment_groups (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_id INT NOT NULL,
            surah_id INT NOT NULL,
            title VARCHAR(255),
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
            FOREIGN KEY (surah_id) REFERENCES surahs(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    echo "Checking ayah_categories table structure...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM ayah_categories LIKE 'assignment_group_id'");
    if (!$stmt->fetch()) {
        echo "Adding assignment_group_id column to ayah_categories...\n";
        $pdo->exec("ALTER TABLE ayah_categories ADD COLUMN assignment_group_id INT NULL AFTER category_id");

        // Add constraint separately to be safe with name
        try {
            $pdo->exec("ALTER TABLE ayah_categories ADD CONSTRAINT fk_ac_group FOREIGN KEY (assignment_group_id) REFERENCES assignment_groups(id) ON DELETE CASCADE");
        } catch (Exception $e) {
            echo "Constraint might already exist: " . $e->getMessage() . "\n";
        }

        // Drop old unique index if exists
        try {
            $pdo->exec("ALTER TABLE ayah_categories DROP INDEX unique_ayah_category");
            echo "Dropped old unique index.\n";
        } catch (Exception $e) {
            echo "Old index might not exist or already dropped.\n";
        }

        // Add new unique index
        try {
            $pdo->exec("ALTER TABLE ayah_categories ADD UNIQUE KEY unique_ayah_category_group (ayah_id, category_id, assignment_group_id)");
            echo "Added new unique index.\n";
        } catch (Exception $e) {
            echo "Unique index may already exist: " . $e->getMessage() . "\n";
        }
    }

    echo "Migration completed successfully.\n";

} catch (PDOException $e) {
    echo "Migration Failed: " . $e->getMessage() . "\n";
}
