<?php
// temp_fetch_data.php
require 'config/db.php';

// Fetch Categories
$stmt = $pdo->query("SELECT id, name, description FROM categories ORDER BY id");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Types
$stmtTypes = $pdo->query("SELECT id, name, description FROM types ORDER BY id");
$types = $stmtTypes->fetchAll(PDO::FETCH_ASSOC);

echo "---CATEGORIES---\n";
echo json_encode($categories, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n---TYPES---\n";
echo json_encode($types, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
