<?php
/**
 * Sadaa (صدى) - About Page Content Management
 */

require_once __DIR__ . '/layout.php';

$message = '';
$error = '';

// Get all active languages
$activeLanguages = [];
try {
    $stmt = $pdo->query("SELECT * FROM languages WHERE is_active = 1 ORDER BY sort_order ASC");
    $activeLanguages = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Erreur chargement langues: ' . $e->getMessage();
}

// Get existing about content
$aboutContent = [];
try {
    $stmt = $pdo->query("SELECT * FROM about_content WHERE is_active = 1");
    while ($row = $stmt->fetch()) {
        $aboutContent[$row['language_code']] = $row;
    }
} catch (PDOException $e) {
    $error = 'Erreur chargement contenu: ' . $e->getMessage();
}

// Handle form submission
if ($_POST && isset($_POST['update_about'])) {
    try {
        foreach ($activeLanguages as $lang) {
            $code = $lang['code'];
            $title = trim($_POST['title_' . $code] ?? '');
            $content = trim($_POST['content_' . $code] ?? '');
            
            // Check if content exists for this language
            if (isset($aboutContent[$code])) {
                // Update existing
                $stmt = $pdo->prepare("UPDATE about_content SET title = ?, content = ?, updated_at = NOW() WHERE language_code = ?");
                $stmt->execute([$title, $content, $code]);
            } else {
                // Insert new
                $stmt = $pdo->prepare("INSERT INTO about_content (language_code, title, content, is_active) VALUES (?, ?, ?, 1)");
                $stmt->execute([$code, $title, $content]);
            }
        }
        
        // Refresh content after update
        $aboutContent = [];
        $stmt = $pdo->query("SELECT * FROM about_content WHERE is_active = 1");
        while ($row = $stmt->fetch()) {
            $aboutContent[$row['language_code']] = $row;
        }
        
        $message = 'Contenu mis à jour avec succès';
    } catch (PDOException $e) {
        $error = 'Erreur lors de la mise à jour: ' . $e->getMessage();
    }
}

adminHeader('Gestion de la Page À Propos');
?>

<div class="page-header">
    <h1 class="page-title">Page À Propos</h1>
</div>

<?php if ($message): ?>
    <div class="alert alert-success">
        <iconify-icon icon="mdi:check-circle"></iconify-icon>
        <?= htmlspecialchars($message) ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error">
        <iconify-icon icon="mdi:alert-circle"></iconify-icon>
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<form method="post">
    <?php foreach ($activeLanguages as $lang): 
        $code = $lang['code'];
        $langName = json_decode($lang['name'], true);
        $isRtl = $lang['is_rtl'];
        $currentContent = $aboutContent[$code] ?? null;
        $title = $currentContent['title'] ?? '';
        $content = $currentContent['content'] ?? '';
    ?>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <iconify-icon icon="mdi:translate"></iconify-icon>
                    <?= htmlspecialchars($langName['fr'] ?? $lang['code']) ?> 
                    <span class="badge badge-primary"><?= htmlspecialchars($code) ?></span>
                </h2>
            </div>
            
            <div class="form-group">
                <label class="form-label">Titre *</label>
                <input type="text" 
                       name="title_<?= $code ?>" 
                       class="form-input<?= $isRtl ? ' font-arabic' : '' ?>" 
                       value="<?= htmlspecialchars($title) ?>" 
                       <?= $isRtl ? 'dir="rtl"' : '' ?> 
                       required 
                       placeholder="Titre de la page">
            </div>
            
            <div class="form-group">
                <label class="form-label">Contenu *</label>
                <textarea name="content_<?= $code ?>" 
                          class="form-textarea<?= $isRtl ? ' font-arabic' : '' ?>" 
                          <?= $isRtl ? 'dir="rtl"' : '' ?> 
                          required 
                          rows="12"
                          placeholder="Contenu de la page À Propos..."><?= $content ?></textarea>
                <small class="form-help">
                    HTML autorisé : &lt;h1&gt; à &lt;h6&gt;, &lt;p&gt;, &lt;br&gt;, &lt;strong&gt;, &lt;em&gt;, &lt;ul&gt;, &lt;ol&gt;, &lt;li&gt;
                </small>
            </div>
        </div>
    <?php endforeach; ?>
    
    <div class="card">
        <button type="submit" name="update_about" class="btn btn-primary">
            <iconify-icon icon="mdi:content-save"></iconify-icon>
            Enregistrer les modifications
        </button>
    </div>
</form>

<?php adminFooter(); ?>
