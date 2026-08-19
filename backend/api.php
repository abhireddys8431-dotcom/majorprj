<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

define('API_CONTEXT', true);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/BookController.php';
require_once __DIR__ . '/controllers/RecommendationEngine.php';
require_once __DIR__ . '/utils/OpenLibraryAPI.php';
require_once __DIR__ . '/utils/RSSParser.php';

$request = $_GET['action'] ?? $_POST['action'] ?? null;

// Initialize Controllers
$auth = new AuthController($conn);
$books = new BookController($conn);
$recommendations = new RecommendationEngine($conn);
$openlib = new OpenLibraryAPI();
$rss = new RSSParser();

switch ($request) {
    // Auth Actions
    case 'login':
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        echo json_encode($auth->login($email, $password));
        break;

    case 'register':
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $roll = $_POST['roll_number'] ?? '';
        $role = $_POST['role'] ?? 'student';
        echo json_encode($auth->register($name, $email, $password, $roll, $role));
        break;

    case 'logout':
        echo json_encode($auth->logout());
        break;

    case 'get_user_info':
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            break;
        }
        $user = $auth->getUserById($_SESSION['user_id']);
        echo json_encode(['success' => true, 'user' => $user]);
        break;

    // Book Actions
    case 'search_books':
        $keyword = $_GET['keyword'] ?? '';
        $filter = $_GET['filter'] ?? 'title';
        $res = $books->searchBooks($keyword, $filter);
        echo json_encode(['success' => true, 'books' => $res]);
        break;

    case 'add_book':
        $isbn = $_POST['isbn'] ?? '';
        $title = $_POST['title'] ?? '';
        $author = $_POST['author'] ?? '';
        $publisher = $_POST['publisher'] ?? '';
        $year = $_POST['year'] ?? date('Y');
        $category = $_POST['category'] ?? 'General';
        $desc = $_POST['description'] ?? '';
        $copies = $_POST['quantity'] ?? $_POST['total_copies'] ?? 1;
        $shelf = $_POST['location_shelf'] ?? 'Shelf A1';
        $aisle = $_POST['location_aisle'] ?? 'Aisle 1';
        $map_x = $_POST['map_x'] ?? 100;
        $map_y = $_POST['map_y'] ?? 100;

        echo json_encode($books->addBook($isbn, $title, $author, $publisher, $year, $category, $desc, $copies, $shelf, $aisle, $map_x, $map_y));
        break;

    case 'issue_book':
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Please login to issue books.']);
            break;
        }
        $book_id = $_POST['book_id'] ?? 0;
        echo json_encode($books->issueBook($_SESSION['user_id'], $book_id));
        break;

    case 'return_book':
        $transaction_id = $_POST['transaction_id'] ?? 0;
        echo json_encode($books->returnBook($transaction_id));
        break;

    case 'get_user_transactions':
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            break;
        }
        echo json_encode(['success' => true, 'transactions' => $books->getUserTransactions($_SESSION['user_id'])]);
        break;

    case 'get_floor_map':
        $res = $books->searchBooks('', 'title');
        echo json_encode(['success' => true, 'books' => $res]);
        break;

    case 'update_book_location':
        $book_id = $_POST['book_id'] ?? 0;
        $map_x = $_POST['map_x'] ?? 100;
        $map_y = $_POST['map_y'] ?? 100;
        $shelf = $_POST['shelf'] ?? null;
        $aisle = $_POST['aisle'] ?? null;
        echo json_encode($books->updateBookLocation($book_id, $map_x, $map_y, $shelf, $aisle));
        break;

    // Recommendation Actions
    case 'get_recommendations':
        $user_id = $_SESSION['user_id'] ?? 1;
        $cat_recs = $recommendations->getRecommendationsByCategory($user_id, 3);
        $peer_recs = $recommendations->getRecommendationsByPeers($user_id, 3);
        $all_recs = array_merge($cat_recs, $peer_recs);
        echo json_encode(['success' => true, 'recommendations' => $all_recs]);
        break;

    // Barcode Actions
    case 'scan_isbn':
        $isbn = $_POST['isbn'] ?? $_GET['isbn'] ?? '';
        $book_data = $openlib->fetchBookByISBN($isbn);
        if ($book_data) {
            echo json_encode(['success' => true, 'book' => $book_data]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Book metadata not found for ISBN ' . $isbn]);
        }
        break;

    // RSS Feed Actions
    case 'get_rss_feed':
        echo json_encode(['success' => true, 'feed' => $rss->getTrendingBooksForDashboard(5)]);
        break;

    // Analytics Actions
    case 'get_analytics':
        echo json_encode([
            'success' => true,
            'months' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
            'issued_count' => [12, 19, 15, 25, 22, 30, 28],
            'categories' => ['Computer Science', 'Software Eng.', 'Mathematics', 'Electronics', 'Physics'],
            'category_count' => [45, 30, 20, 15, 10],
            'popular_books' => [
                ['title' => 'Clean Code', 'issue_count' => 38],
                ['title' => 'Introduction to Algorithms', 'issue_count' => 31],
                ['title' => 'The C Programming Language', 'issue_count' => 27]
            ]
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action requested']);
        break;
}

if ($conn) {
    mysqli_close($conn);
}
?>
