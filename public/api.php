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
                // Get surahs that have ayahs in this category
                $stmt = $pdo->prepare("
                    SELECT DISTINCT s.* FROM surahs s
                    INNER JOIN ayahs a ON a.surah_id = s.id
                    INNER JOIN ayah_categories ac ON ac.ayah_id = a.id
                    WHERE ac.category_id = ?
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
            $surahId = isset($_GET['surah_id']) ? (int) $_GET['surah_id'] : null;
            $surahNumber = isset($_GET['surah_number']) ? (int) $_GET['surah_number'] : null;
            $categoryId = isset($_GET['category_id']) ? (int) $_GET['category_id'] : null;

            // Get surah ID from number if needed
            if (!$surahId && $surahNumber) {
                $stmt = $pdo->prepare("SELECT id FROM surahs WHERE number = ? LIMIT 1");
                $stmt->execute([$surahNumber]);
                $surahId = $stmt->fetchColumn();
            }

            if (!$surahId) {
                $response = ['status' => 'error', 'message' => 'Surah not found'];
                break;
            }

            if ($categoryId) {
                $stmt = $pdo->prepare("
                    SELECT a.* FROM ayahs a
                    INNER JOIN ayah_categories ac ON ac.ayah_id = a.id
                    WHERE a.surah_id = ? AND ac.category_id = ?
                    ORDER BY a.ayah_number ASC
                ");
                $stmt->execute([$surahId, $categoryId]);
            } else {
                $stmt = $pdo->prepare("SELECT * FROM ayahs WHERE surah_id = ? ORDER BY ayah_number ASC");
                $stmt->execute([$surahId]);
            }
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

            // 2. Get Ayahs
            $ayahs = [];
            if ($categoryId) {
                $stmt = $pdo->prepare("
                    SELECT a.* FROM ayahs a 
                    INNER JOIN ayah_categories ac ON a.id = ac.ayah_id 
                    WHERE a.surah_id = ? AND ac.category_id = ?
                    ORDER BY a.ayah_number ASC
                ");
                $stmt->execute([$surah['id'], $categoryId]);
            } else {
                $stmt = $pdo->prepare("SELECT * FROM ayahs WHERE surah_id = ? ORDER BY ayah_number ASC");
                $stmt->execute([$surah['id']]);
            }
            $ayahs = $stmt->fetchAll();

            // 3. Find Next/Prev Surah (context aware of category)
            $prevSurah = null;
            $nextSurah = null;

            // Get list of available surahs numbers to find neighbors
            if ($categoryId) {
                $stmt = $pdo->prepare("
                    SELECT DISTINCT s.number FROM surahs s
                    INNER JOIN ayahs a ON a.surah_id = s.id
                    INNER JOIN ayah_categories ac ON ac.ayah_id = a.id
                    WHERE ac.category_id = ?
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
                'ayahs' => $ayahs,
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
