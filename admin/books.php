<?php
/**
 * Sadaa (صدى) - Books Management
 */

require_once __DIR__ . '/layout.php';

// Get all active languages
$languages = [];
try {
    $stmt = $pdo->query("SELECT code, name FROM languages WHERE is_active = 1 ORDER BY sort_order ASC, code ASC");
    $languages = $stmt->fetchAll();
} catch (PDOException $e) {
    // Fallback to default languages if table doesn't exist
    // Using JSON format to match database structure
    $languages = [
        ['code' => 'ar', 'name' => json_encode(['fr' => 'Arabe', 'en' => 'Arabic', 'ar' => 'العربية', 'es' => 'Árabe', 'de' => 'Arabisch'])],
        ['code' => 'fr', 'name' => json_encode(['fr' => 'Français', 'en' => 'French', 'ar' => 'الفرنسية', 'es' => 'Francés', 'de' => 'Französisch'])],
        ['code' => 'en', 'name' => json_encode(['fr' => 'Anglais', 'en' => 'English', 'ar' => 'الإنجليزية', 'es' => 'Inglés', 'de' => 'Englisch'])],
        ['code' => 'es', 'name' => json_encode(['fr' => 'Espagnol', 'en' => 'Spanish', 'ar' => 'الإسبانية', 'es' => 'Español', 'de' => 'Spanisch'])],
        ['code' => 'de', 'name' => json_encode(['fr' => 'Allemand', 'en' => 'German', 'ar' => 'الألمانية', 'es' => 'Alemán', 'de' => 'Deutsch'])],
    ];
}

$message = '';
$error = '';

// Handle form submissions
if ($_POST) {
    if (isset($_POST['add_book'])) {
        try {
            $titleData = [];
            foreach ($languages as $lang) {
                $code = $lang['code'];
                $titleData[$code] = trim($_POST["title_$code"] ?? '');
            }
            $title = json_encode($titleData);

            $descData = [];
            foreach ($languages as $lang) {
                $code = $lang['code'];
                $descData[$code] = trim($_POST["desc_$code"] ?? '');
            }
            $description = json_encode($descData);
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

    if (isset($_POST['edit_book'])) {
        try {
            $bookId = (int) $_POST['book_id'];
            $titleData = [];
            foreach ($languages as $lang) {
                $code = $lang['code'];
                $titleData[$code] = trim($_POST["title_$code"] ?? '');
            }
            $title = json_encode($titleData);

            $descData = [];
            foreach ($languages as $lang) {
                $code = $lang['code'];
                $descData[$code] = trim($_POST["desc_$code"] ?? '');
            }
            $description = json_encode($descData);

            // Check if this is the Quran (protected)
            $stmt = $pdo->prepare("SELECT slug FROM books WHERE id = ?");
            $stmt->execute([$bookId]);
            $currentSlug = $stmt->fetchColumn();

            if ($currentSlug === 'quran') {
                // Quran is protected - keep original slug
                $stmt = $pdo->prepare("UPDATE books SET title = ?, description = ? WHERE id = ?");
                $stmt->execute([$title, $description, $bookId]);
                $message = 'Le Coran a été modifié';
            } else {
                // Other books can change slug
                $slug = trim($_POST['slug'] ?? '');
                if (!$slug) {
                    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $_POST['title_en'] ?? $_POST['title_fr'] ?? 'book'));
                }
                $stmt = $pdo->prepare("UPDATE books SET title = ?, slug = ?, description = ? WHERE id = ?");
                $stmt->execute([$title, $slug, $description, $bookId]);
                $message = 'Livre modifié avec succès';
            }
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
            <?php foreach ($languages as $lang):
                $code = $lang['code'];
                $isArabic = $code === 'ar';
                $required = $code === 'fr' ? 'required' : '';
                $langName = json_decode($lang['name'], true);
                $displayName = $langName['fr'] ?? $langName['en'] ?? $code;
            ?>
            <div class="form-group">
                <label class="form-label">Titre (<?= htmlspecialchars($displayName) ?>)<?= $required ? ' *' : '' ?></label>
                <input type="text" name="title_<?= $code ?>" class="form-input<?= $isArabic ? ' font-arabic' : '' ?>"<?= $isArabic ? ' dir="rtl"' : '' ?> <?= $required ?>>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="form-group">
            <label class="form-label">Slug</label>
            <input type="text" name="slug" class="form-input" placeholder="mon-livre">
        </div>

        <div class="grid grid-3">
            <?php foreach ($languages as $lang):
                $code = $lang['code'];
                $isArabic = $code === 'ar';
                $langName = json_decode($lang['name'], true);
                $displayName = $langName['fr'] ?? $langName['en'] ?? $code;
            ?>
            <div class="form-group">
                <label class="form-label">Description (<?= htmlspecialchars($displayName) ?>)</label>
                <textarea name="desc_<?= $code ?>" class="form-textarea<?= $isArabic ? ' font-arabic' : '' ?>"<?= $isArabic ? ' dir="rtl"' : '' ?>></textarea>
            </div>
            <?php endforeach; ?>
        </div>

        <button type="submit" name="add_book" class="btn btn-primary">
            <iconify-icon icon="mdi:plus"></iconify-icon>
            Ajouter
        </button>
    </form>
</div>

<!-- Edit Book Form (Hidden by default) -->
<div class="card" id="edit-book-form" style="display: none;">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h2 class="card-title">Modifier un Livre</h2>
        <button type="button" class="btn btn-sm" onclick="cancelEdit()">
            <iconify-icon icon="mdi:close"></iconify-icon>
            Annuler
        </button>
    </div>
    <form method="post" id="edit-form">
        <input type="hidden" name="book_id" id="edit-book-id">

        <div class="grid grid-3">
            <?php foreach ($languages as $lang):
                $code = $lang['code'];
                $isArabic = $code === 'ar';
                $required = $code === 'fr' ? 'required' : '';
                $langName = json_decode($lang['name'], true);
                $displayName = $langName['fr'] ?? $langName['en'] ?? $code;
            ?>
            <div class="form-group">
                <label class="form-label">Titre (<?= htmlspecialchars($displayName) ?>)<?= $required ? ' *' : '' ?></label>
                <input type="text" name="title_<?= $code ?>" id="edit-title-<?= $code ?>" class="form-input<?= $isArabic ? ' font-arabic' : '' ?>"<?= $isArabic ? ' dir="rtl"' : '' ?> <?= $required ?>>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="form-group">
            <label class="form-label">Slug</label>
            <input type="text" name="slug" id="edit-slug" class="form-input" placeholder="mon-livre">
        </div>

        <div class="grid grid-3">
            <?php foreach ($languages as $lang):
                $code = $lang['code'];
                $isArabic = $code === 'ar';
                $langName = json_decode($lang['name'], true);
                $displayName = $langName['fr'] ?? $langName['en'] ?? $code;
            ?>
            <div class="form-group">
                <label class="form-label">Description (<?= htmlspecialchars($displayName) ?>)</label>
                <textarea name="desc_<?= $code ?>" id="edit-desc-<?= $code ?>" class="form-textarea<?= $isArabic ? ' font-arabic' : '' ?>"<?= $isArabic ? ' dir="rtl"' : '' ?>></textarea>
            </div>
            <?php endforeach; ?>
        </div>

        <button type="submit" name="edit_book" class="btn btn-primary">
            <iconify-icon icon="mdi:content-save"></iconify-icon>
            Enregistrer les modifications
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
                            <button type="button" class="btn btn-sm btn-secondary edit-book-btn"
                                data-book-id="<?= $book['id'] ?>"
                                data-book-slug="<?= htmlspecialchars($book['slug']) ?>"
                                data-book-title="<?= htmlspecialchars($book['title']) ?>"
                                data-book-description="<?= htmlspecialchars($book['description'] ?? '{}') ?>">
                                <iconify-icon icon="mdi:pencil"></iconify-icon>
                            </button>
                            <?php if ($book['slug'] !== 'quran'): ?>
                                <form method="post" style="display: inline;"
                                    onsubmit="return confirm('Supprimer ce livre et tout son contenu?');">
                                    <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                                    <button type="submit" name="delete_book" class="btn btn-sm btn-danger">
                                        <iconify-icon icon="mdi:delete"></iconify-icon>
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="badge badge-success" title="Le Coran ne peut pas être supprimé">
                                    <iconify-icon icon="mdi:shield-check"></iconify-icon>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>
// Add click handlers to all edit buttons
document.querySelectorAll('.edit-book-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const bookData = {
            id: this.dataset.bookId,
            slug: this.dataset.bookSlug,
            title: JSON.parse(this.dataset.bookTitle || '{}'),
            description: JSON.parse(this.dataset.bookDescription || '{}')
        };
        editBook(bookData);
    });
});

function editBook(book) {
    // Show edit form
    document.getElementById('edit-book-form').style.display = 'block';

    // Populate form fields
    document.getElementById('edit-book-id').value = book.id;
    document.getElementById('edit-slug').value = book.slug || '';

    // Populate title fields dynamically
    const title = book.title || {};
    <?php foreach ($languages as $lang): ?>
    document.getElementById('edit-title-<?= $lang['code'] ?>').value = title['<?= $lang['code'] ?>'] || '';
    <?php endforeach; ?>

    // Populate description fields dynamically
    const desc = book.description || {};
    <?php foreach ($languages as $lang): ?>
    document.getElementById('edit-desc-<?= $lang['code'] ?>').value = desc['<?= $lang['code'] ?>'] || '';
    <?php endforeach; ?>

    // Quran is protected - make slug readonly
    const slugField = document.getElementById('edit-slug');
    if (book.slug === 'quran') {
        slugField.readOnly = true;
        slugField.parentElement.querySelector('label').innerHTML = 'Slug <span class="text-muted">(protégé)</span>';
    } else {
        slugField.readOnly = false;
        slugField.parentElement.querySelector('label').innerHTML = 'Slug';
    }

    // Scroll to edit form
    document.getElementById('edit-book-form').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function cancelEdit() {
    // Hide edit form
    document.getElementById('edit-book-form').style.display = 'none';

    // Clear form fields
    document.getElementById('edit-form').reset();
}
</script>

<?php adminFooter(); ?>