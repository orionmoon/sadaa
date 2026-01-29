<?php
/**
 * Sadaa - Emergency Password Reset
 * 
 * Instructions:
 * 1. Upload this file to your 'admin' folder.
 * 2. Access it via your browser: yourdomain.com/admin/reset_pwd.php
 * 3. Delete this file immediately after use for security.
 */

require_once __DIR__ . '/../config/db.php';

try {
    $newPassword = 'admin123';
    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('admin_password', ?) 
                           ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->execute([$hashed, $hashed]);

    echo "<h1>Mot de passe réinitialisé !</h1>";
    echo "<p>Le mot de passe pour le compte administrateur est désormais : <strong>$newPassword</strong></p>";
    echo "<p style='color: red;'><strong>IMPORTANT : Supprimez ce fichier (admin/reset_pwd.php) de votre serveur immédiatement.</strong></p>";
    echo "<a href='index.php'>Retour à la connexion</a>";

} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
