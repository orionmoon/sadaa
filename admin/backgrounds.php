<?php
/**
 * Sadaa (صدى) - Backgrounds Management
 */

require_once __DIR__ . '/layout.php';

$message = '';
$error = '';
$backgroundsDir = __DIR__ . '/../public/assets/backgrounds/';

// Ensure directory exists
if (!is_dir($backgroundsDir)) {
    mkdir($backgroundsDir, 0755, true);
}

// Handle Upload
if (isset($_POST['upload'])) {
    if (isset($_FILES['background']) && $_FILES['background']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['background']['tmp_name'];
        $fileName = $_FILES['background']['name'];
        $fileSize = $_FILES['background']['size'];
        $fileType = $_FILES['background']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($fileExtension, $allowedExtensions)) {
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $destPath = $backgroundsDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $message = 'Image uploadée avec succès.';
            } else {
                $error = 'Erreur lors de l\'enregistrement du fichier.';
            }
        } else {
            $error = 'Extension non autorisée. Utilisez jpg, png ou webp.';
        }
    } else {
        $error = 'Veuillez sélectionner une image valide.';
    }
}

// Handle Delete
if (isset($_POST['delete'])) {
    $fileToDelete = basename($_POST['filename']);
    $filePath = $backgroundsDir . $fileToDelete;

    if (file_exists($filePath)) {
        if (unlink($filePath)) {
            $message = 'Image supprimée.';
        } else {
            $error = 'Erreur lors de la suppression.';
        }
    }
}

// Get backgrounds
$backgrounds = [];
if (is_dir($backgroundsDir)) {
    $files = scandir($backgroundsDir);
    foreach ($files as $file) {
        if (preg_match('/\.(jpg|jpeg|png|webp)$/i', $file)) {
            $backgrounds[] = [
                'name' => $file,
                'path' => '/assets/backgrounds/' . $file,
                'size' => round(filesize($backgroundsDir . $file) / 1024, 2) . ' KB'
            ];
        }
    }
}

adminHeader('Gestion des Fonds d\'écran');
?>

<div class="page-header">
    <h1 class="page-title">Fonds d'écran pour le Partage</h1>
</div>

<?php if ($message): ?>
    <div class="alert alert-success">
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Ajouter un fond d'écran</h2>
    </div>
    <form method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label class="form-label">Sélectionner une image (JPG, PNG, WEBP)</label>
            <input type="file" name="background" class="form-input" accept="image/*" required>
        </div>
        <button type="submit" name="upload" class="btn btn-primary">
            <iconify-icon icon="mdi:upload"></iconify-icon>
            Uploader
        </button>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Galerie des fonds d'écran</h2>
    </div>

    <?php if (empty($backgrounds)): ?>
        <p class="text-muted text-center" style="padding: 2rem;">Aucune image disponible.</p>
    <?php else: ?>
        <div class="grid grid-4">
            <?php foreach ($backgrounds as $bg): ?>
                <div class="card" style="padding: 0.5rem; position: relative;">
                    <img src="<?= htmlspecialchars($bg['path']) ?>" alt="Background"
                        style="width: 100%; height: 150px; object-fit: cover; border-radius: 0.5rem; background: #333;">
                    <div class="mt-1 flex justify-between items-center">
                        <span class="text-muted"
                            style="font-size: 0.75rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 120px;">
                            <?= htmlspecialchars($bg['name']) ?>
                        </span>
                        <span class="badge">
                            <?= $bg['size'] ?>
                        </span>
                    </div>
                    <form method="post" onsubmit="return confirm('Supprimer cette image ?');" style="margin-top: 0.5rem;">
                        <input type="hidden" name="filename" value="<?= htmlspecialchars($bg['name']) ?>">
                        <button type="submit" name="delete" class="btn btn-sm btn-danger w-full"
                            style="justify-content: center;">
                            <iconify-icon icon="mdi:delete"></iconify-icon>
                            Supprimer
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
    .w-full {
        width: 100%;
    }
</style>

<?php adminFooter(); ?>