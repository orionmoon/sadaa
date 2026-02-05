<?php
// update_tr_db.php
require 'config/db.php';

$trData = [
    'categories' => [
        3 => ['name' => 'Müminler', 'desc' => 'Müminler hakkında ayetler'],
        4 => ['name' => 'Kâfirler', 'desc' => 'Kâfirler hakkında ayetler'],
        9 => ['name' => 'İnsanlık', 'desc' => ''],
        10 => ['name' => 'Tarih', 'desc' => 'Tarih hakkında ayetler'],
        11 => ['name' => 'İsrailoğulları', 'desc' => 'İsrailoğulları hakkında ayetler']
    ],
    'types' => [
        2 => ['name' => 'Tür', 'desc' => 'Kuran\'da bahsedilen insan türleri'],
        5 => ['name' => 'Bilimler', 'desc' => '']
    ]
];

function updateTable($pdo, $table, $updates)
{
    echo "Updating $table...\n";
    foreach ($updates as $id => $data) {
        $stmt = $pdo->prepare("SELECT name, description FROM $table WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            echo "Row ID $id not found in $table.\n";
            continue;
        }

        $nameJson = json_decode($row['name'], true) ?: [];
        $descJson = json_decode($row['description'], true) ?: [];

        // Update TR
        if (isset($data['name'])) {
            $nameJson['tr'] = $data['name'];
        }
        if (isset($data['desc'])) {
            $descJson['tr'] = $data['desc'];
        }

        // Save back
        $newName = json_encode($nameJson, JSON_UNESCAPED_UNICODE);
        $newDesc = json_encode($descJson, JSON_UNESCAPED_UNICODE);

        $update = $pdo->prepare("UPDATE $table SET name = ?, description = ? WHERE id = ?");
        $update->execute([$newName, $newDesc, $id]);
        echo "Updated ID $id: $newName\n";
    }
}

try {
    updateTable($pdo, 'categories', $trData['categories']);
    updateTable($pdo, 'types', $trData['types']);
    echo "Update complete.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
