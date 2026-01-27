<?php
/**
 * Sadaa (صدى) - Books Management
 */

require_once __DIR__ . '/layout.php';

$message = '';
$error = '';

// Handle form submissions
if ($_POST) {
    if (isset($_POST['add_book'])) {
        try {
            $title = json_encode([
                'ar' => trim($_POST['title_ar'] ?? ''),
                'fr' => trim($_POST['title_fr'] ?? ''),
                'en' => trim($_POST['title_en'] ?? ''),
            ]);
            $description = json_encode([
                'ar' => trim($_POST['desc_ar'] ?? ''),
                'fr' => trim($_POST['desc_fr'] ?? ''),
                'en' => trim($_POST['desc_en'] ?? ''),
            ]);
            $slug = trim($_POST['slug'] ?? '');

            if (!$slug) {
                $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $_POST['title_en'] ?? $_POST['title_fr'] ?? 'book'));
            }

            $stmt = $pdo->prepare("INSERT INTO books (title, slug, description, language) VALUES (?, ?, ?, 'ar')");
            $stmt->execute([$title, $slug, $description]);
            $message = 'Livre ajouté avec succès';
        } catch (PDOException $e) {
            $error = 'Erreur: ' . $e->getMessage();
        }
    }

    if (isset($_POST['delete_book'])) {
        try {
            $bookId = (int) $_POST['book_id'];
            // Check if it's the Quran (protected)
            $stmt = $pdo->prepare("SELECT slug FROM books WHERE id = ?");
            $stmt->execute([$bookId]);
            $slug = $stmt->fetchColumn();

            if ($slug === 'quran') {
                $error = 'Le Coran ne peut pas être supprimé';
            } else {
                $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
                $stmt->execute([$bookId]);
                $message = 'Livre supprimé';
            }
        } catch (PDOException $e) {
            $error = 'Erreur: ' . $e->getMessage();
        }
    }
}

// Get all books
$books = [];
try {
    $stmt = $pdo->query("SELECT b.*, 
                         (SELECT COUNT(*) FROM surahs s WHERE s.book_id = b.id) as surah_count,
                         (SELECT COUNT(*) FROM ayahs a INNER JOIN surahs s ON a.surah_id = s.id WHERE s.book_id = b.id) as ayah_count
                         FROM books b ORDER BY b.id ASC");
    $books = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Erreur: ' . $e->getMessage();
}

adminHeader('Gestion des Livres');
?>

<div class="page-header">
    <h1 class="page-title">Livres</h1>
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

<!-- Add Book Form -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Ajouter un Livre</h2>
    </div>
    <form method="post">
        <div class="grid grid-3">
            <div class="form-group">
                <label class="form-label">Titre (Arabe)</label>
                <input type="text" name="title_ar" class="form-input font-arabic" dir="rtl">
            </div>
            <div class="form-group">
                <label class="form-label">Titre (Français) *</label>
                <input type="text" name="title_fr" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Titre (Anglais)</label>
                <input type="text" name="title_en" class="form-input">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Slug</label>
            <input type="text" name="slug" class="form-input" placeholder="mon-livre">
        </div>

        <div class="grid grid-3">
            <div class="form-group">
                <label class="form-label">Description (Arabe)</label>
                <textarea name="desc_ar" class="form-textarea font-arabic" dir="rtl"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Description (Français)</label>
                <textarea name="desc_fr" class="form-textarea"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Description (Anglais)</label>
                <textarea name="desc_en" class="form-textarea"></textarea>
            </div>
        </div>

        <button type="submit" name="add_book" class="btn btn-primary">
            <iconify-icon icon="mdi:plus"></iconify-icon>
            Ajouter
        </button>
    </form>
</div>

<!-- Books List -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Livres existants</h2>
    </div>

    <?php if (count($books) === 0): ?>
        <p class="text-muted text-center" style="padding: 2rem;">Aucun livre.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Titre</th>
                    <th>Slug</th>
                    <th>Sourates</th>
                    <th>Versets</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($books as $book):
                    $title = json_decode($book['title'], true);
                    ?>
                    <tr>
                        <td>
                            <strong>
                                <?= htmlspecialchars($title['fr'] ?? $title['en'] ?? '') ?>
                            </strong>
                            <span class="text-muted font-arabic"> /
                                <?= htmlspecialchars($title['ar'] ?? '') ?>
                            </span>
                        </td>
                        <td><code><?= htmlspecialchars($book['slug']) ?></code></td>
                        <td><span class="badge badge-primary">
                                <?= $book['surah_count'] ?>
                            </span></td>
                        <td><span class="badge badge-primary">
                                <?= number_format($book['ayah_count']) ?>
                            </span></td>
                        <td>
                            <?php if ($book['slug'] !== 'quran'): ?>
                                <form method="post" style="display: inline;"
                                    onsubmit="return confirm('Supprimer ce livre et tout son contenu?');">
                                    <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                                    <button type="submit" name="delete_book" class="btn btn-sm btn-danger">
                                        <iconify-icon icon="mdi:delete"></iconify-icon>
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="badge badge-success">
                                    <iconify-icon icon="mdi:shield-check"></iconify-icon>
                                    Protégé
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php adminFooter(); ?>