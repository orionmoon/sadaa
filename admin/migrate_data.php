<?php
/**
 * Sadaa - Data Migration: Move existing assignments to groups
 */
require_once __DIR__ . '/../config/db.php';

try {
    $pdo->beginTransaction();

    // Find all distinct category+surah combinations in ayah_categories that don't have a group
    $stmt = $pdo->query("
        SELECT DISTINCT ac.category_id, a.surah_id 
        FROM ayah_categories ac
        JOIN ayahs a ON ac.ayah_id = a.id
        WHERE ac.assignment_group_id IS NULL
    ");
    $combinations = $stmt->fetchAll();

    echo "Found " . count($combinations) . " legacy assignment combinations.\n";

    foreach ($combinations as $comb) {
        $catId = $comb['category_id'];
        $surahId = $comb['surah_id'];

        // Create a default group for this combination
        $groupStmt = $pdo->prepare("INSERT INTO assignment_groups (category_id, surah_id, title) VALUES (?, ?, 'Imported Group')");
        $groupStmt->execute([$catId, $surahId]);
        $groupId = $pdo->lastInsertId();

        // Update ayah_categories for this group
        $updateStmt = $pdo->prepare("
            UPDATE ayah_categories ac
            JOIN ayahs a ON ac.ayah_id = a.id
            SET ac.assignment_group_id = ?
            WHERE ac.category_id = ? AND a.surah_id = ? AND ac.assignment_group_id IS NULL
        ");
        $updateStmt->execute([$groupId, $catId, $surahId]);

        echo "Migrated assignments for Category $catId, Surah $surahId into Group $groupId.\n";
    }

    $pdo->commit();
    echo "Data migration completed successfully.\n";

} catch (Exception $e) {
    if ($pdo->inTransaction())
        $pdo->rollBack();
    echo "Data Migration Failed: " . $e->getMessage() . "\n";
}
