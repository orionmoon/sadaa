<?php
/**
 * Sadaa (صدى) - API Endpoints
 * 
 * RESTful API for the application
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../config/db.php';

// Get action
$action = $_GET['action'] ?? $_GET['endpoint'] ?? '';

$response = ['status' => 'success', 'data' => null];

try {
    switch ($action) {
        case 'types':
        case 'get_types':
            $stmt = $pdo->query("SELECT * FROM types ORDER BY sort_order ASC");
            $response['data'] = $stmt->fetchAll();
            break;

        case 'categories':
        case 'get_categories':
            $typeId = isset($_GET['type_id']) ? (int) $_GET['type_id'] : null;

            if ($typeId) {
                $stmt = $pdo->prepare("SELECT * FROM categories WHERE type_id = ? ORDER BY sort_order ASC");
                $stmt->execute([$typeId]);
            } else {
                $stmt = $pdo->query("SELECT * FROM categories ORDER BY type_id, sort_order ASC");
            }
            $response['data'] = $stmt->fetchAll();
            break;

        case 'books':
        case 'get_books':
            $stmt = $pdo->query("SELECT * FROM books ORDER BY id ASC");
            $response['data'] = $stmt->fetchAll();
            break;

        case 'surahs':
        case 'get_surahs':
            $bookId = isset($_GET['book_id']) ? (int) $_GET['book_id'] : null;
            $categoryId = isset($_GET['category_id']) ? (int) $_GET['category_id'] : null;

            if ($categoryId) {
                // Get surahs that have assignment groups in this category
                $stmt = $pdo->prepare("
                    SELECT DISTINCT s.* FROM surahs s
                    INNER JOIN assignment_groups ag ON ag.surah_id = s.id
                    WHERE ag.category_id = ?
                    ORDER BY s.number ASC
                ");
                $stmt->execute([$categoryId]);
            } elseif ($bookId) {
                $stmt = $pdo->prepare("SELECT * FROM surahs WHERE book_id = ? ORDER BY number ASC");
                $stmt->execute([$bookId]);
            } else {
                $stmt = $pdo->query("SELECT * FROM surahs ORDER BY number ASC");
            }
            $response['data'] = $stmt->fetchAll();
            break;

        case 'ayahs':
        case 'get_ayahs':
            // Logic for flat ayahs list (if still needed)
            $surahId = isset($_GET['surah_id']) ? (int) $_GET['surah_id'] : null;
            $surahNumber = isset($_GET['surah_number']) ? (int) $_GET['surah_number'] : null;
            $categoryId = isset($_GET['category_id']) ? (int) $_GET['category_id'] : null;

            if (!$surahId && $surahNumber) {
                $stmt = $pdo->prepare("SELECT id FROM surahs WHERE number = ? LIMIT 1");
                $stmt->execute([$surahNumber]);
                $surahId = $stmt->fetchColumn();
            }

            if (!$surahId) {
                $response = ['status' => 'error', 'message' => 'Surah not found'];
                break;
            }

            $stmt = $pdo->prepare("SELECT * FROM ayahs WHERE surah_id = ? ORDER BY ayah_number ASC");
            $stmt->execute([$surahId]);
            $response['data'] = $stmt->fetchAll();
            break;

        case 'surah':
        case 'surah_details':
            $surahNumber = isset($_GET['surah']) ? (int) $_GET['surah'] : 1;
            $categoryId = isset($_GET['category']) ? (int) $_GET['category'] : null;

            // 1. Get Surah Info
            $stmt = $pdo->prepare("SELECT * FROM surahs WHERE number = ?");
            $stmt->execute([$surahNumber]);
            $surah = $stmt->fetch();

            if (!$surah) {
                $response = ['status' => 'error', 'message' => 'Surah not found'];
                break;
            }

            // 2. Get Groups or Ayahs
            $groups = [];
            $ayahs = [];

            if ($categoryId) {
                // Fetch groups with their ayahs
                $stmt = $pdo->prepare("
                    SELECT ag.* FROM assignment_groups ag
                    WHERE ag.category_id = ? AND ag.surah_id = ?
                    ORDER BY ag.created_at ASC
                ");
                $stmt->execute([$categoryId, $surah['id']]);
                $groups = $stmt->fetchAll();

                // Hydrate groups with ayahs and tags
                foreach ($groups as &$group) {
                    $stmt = $pdo->prepare("
                        SELECT a.* FROM ayahs a
                        INNER JOIN ayah_categories ac ON a.id = ac.ayah_id
                        WHERE ac.assignment_group_id = ?
                        ORDER BY a.ayah_number ASC
                    ");
                    $stmt->execute([$group['id']]);
                    $group['ayahs'] = $stmt->fetchAll();

                    // Fetch tags
                    $stmt = $pdo->prepare("
                        SELECT t.* FROM tags t
                        JOIN assignment_group_tags agt ON t.id = agt.tag_id
                        WHERE agt.assignment_group_id = ?
                    ");
                    $stmt->execute([$group['id']]);
                    $group['tags'] = $stmt->fetchAll();
                }

                // Also populate flat 'ayahs' mainly for fallback or raw access if needed, 
                // but frontend should now prefer 'groups'.
                // Ideally, we just return groups for category view.
            } else {
                // Fallback for full Quran reading (no category)
                $stmt = $pdo->prepare("SELECT * FROM ayahs WHERE surah_id = ? ORDER BY ayah_number ASC");
                $stmt->execute([$surah['id']]);
                $ayahs = $stmt->fetchAll();
            }

            // 3. Find Next/Prev Surah
            $prevSurah = null;
            $nextSurah = null;

            if ($categoryId) {
                $stmt = $pdo->prepare("
                    SELECT DISTINCT s.number FROM surahs s
                    INNER JOIN assignment_groups ag ON ag.surah_id = s.id
                    WHERE ag.category_id = ?
                    ORDER BY s.number ASC
                ");
                $stmt->execute([$categoryId]);
                $allSurahs = $stmt->fetchAll(PDO::FETCH_COLUMN);
            } else {
                $allSurahs = $pdo->query("SELECT number FROM surahs ORDER BY number ASC")->fetchAll(PDO::FETCH_COLUMN);
            }

            $currentIndex = array_search($surahNumber, $allSurahs);
            if ($currentIndex !== false) {
                if (isset($allSurahs[$currentIndex - 1])) {
                    $stmt = $pdo->prepare("SELECT * FROM surahs WHERE number = ?");
                    $stmt->execute([$allSurahs[$currentIndex - 1]]);
                    $prevSurah = $stmt->fetch();
                }
                if (isset($allSurahs[$currentIndex + 1])) {
                    $stmt = $pdo->prepare("SELECT * FROM surahs WHERE number = ?");
                    $stmt->execute([$allSurahs[$currentIndex + 1]]);
                    $nextSurah = $stmt->fetch();
                }
            }

            $response['data'] = [
                'surah' => $surah,
                'groups' => $groups,
                'ayahs' => $ayahs, // populated only if no category
                'prev_surah' => $prevSurah,
                'next_surah' => $nextSurah
            ];
            break;

        case 'languages':
        case 'get_languages':
            $activeOnly = isset($_GET['active']) ? (bool) $_GET['active'] : true;
            if ($activeOnly) {
                $stmt = $pdo->query("SELECT * FROM languages WHERE is_active = 1 ORDER BY sort_order ASC");
            } else {
                $stmt = $pdo->query("SELECT * FROM languages ORDER BY sort_order ASC");
            }
            $response['data'] = $stmt->fetchAll();
            break;

        case 'imports':
        case 'get_imports':
            $stmt = $pdo->query("SELECT * FROM imports ORDER BY created_at DESC LIMIT 50");
            $response['data'] = $stmt->fetchAll();
            break;

        case 'stats':
        case 'get_stats':
            $stats = [
                'types' => $pdo->query("SELECT COUNT(*) FROM types")->fetchColumn(),
                'categories' => $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn(),
                'surahs' => $pdo->query("SELECT COUNT(*) FROM surahs")->fetchColumn(),
                'ayahs' => $pdo->query("SELECT COUNT(*) FROM ayahs")->fetchColumn(),
                'languages' => $pdo->query("SELECT COUNT(*) FROM languages WHERE is_active = 1")->fetchColumn(),
            ];
            $response['data'] = $stats;
            break;

        default:
            $response = ['status' => 'error', 'message' => 'Invalid action'];
    }

    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error: ' . $e->getMessage()]);
}
