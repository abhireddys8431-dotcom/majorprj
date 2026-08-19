<?php
class BookController {
    private $conn;

    public function __construct($database_connection) {
        $this->conn = $database_connection;
    }

    // Add Book
    public function addBook($isbn, $title, $author, $publisher = '', $year = null, $category = 'General', $description = '', $copies = 1, $shelf = 'Shelf A', $aisle = 'Aisle 1', $map_x = 100, $map_y = 100) {
        $added_by = $_SESSION['user_id'] ?? 1;
        $year = $year ? intval($year) : date('Y');
        $copies = intval($copies);

        $query = "INSERT INTO books (isbn, title, author, publisher, publication_year, category, description, total_copies, available_copies, location_shelf, location_aisle, map_x, map_y, added_by) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssssissiissiia", $isbn, $title, $author, $publisher, $year, $category, $description, $copies, $copies, $shelf, $aisle, $map_x, $map_y, $added_by);

        if ($stmt->execute()) {
            return ['success' => true, 'book_id' => $stmt->insert_id, 'message' => 'Book added successfully to catalog!'];
        }
        return ['success' => false, 'message' => $stmt->error];
    }

    // Search Books
    public function searchBooks($keyword = '', $filter = 'title') {
        $allowed_filters = ['title', 'author', 'isbn', 'category'];
        if (!in_array($filter, $allowed_filters)) {
            $filter = 'title';
        }

        if (empty($keyword)) {
            $query = "SELECT * FROM books ORDER BY book_id DESC LIMIT 50";
            $result = mysqli_query($this->conn, $query);
            return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        }

        $search = '%' . $keyword . '%';
        $query = "SELECT * FROM books WHERE $filter LIKE ? ORDER BY book_id DESC LIMIT 50";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $search);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Get Single Book by ID or ISBN
    public function getBookByISBN($isbn) {
        $query = "SELECT * FROM books WHERE isbn = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $isbn);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Update Book 2D Floor Map Coordinates
    public function updateBookLocation($book_id, $map_x, $map_y, $shelf = null, $aisle = null) {
        $map_x = intval($map_x);
        $map_y = intval($map_y);
        
        if ($shelf && $aisle) {
            $query = "UPDATE books SET map_x = ?, map_y = ?, location_shelf = ?, location_aisle = ? WHERE book_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("iissi", $map_x, $map_y, $shelf, $aisle, $book_id);
        } else {
            $query = "UPDATE books SET map_x = ?, map_y = ? WHERE book_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("iii", $map_x, $map_y, $book_id);
        }

        return ['success' => $stmt->execute(), 'message' => 'Visual map coordinates updated successfully'];
    }

    // Issue Book to Student
    public function issueBook($user_id, $book_id, $days = 14) {
        // Check availability
        $check = "SELECT available_copies, title FROM books WHERE book_id = ?";
        $stmt = $this->conn->prepare($check);
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        $book = $stmt->get_result()->fetch_assoc();

        if (!$book) {
            return ['success' => false, 'message' => 'Book not found in library database.'];
        }

        if ($book['available_copies'] <= 0) {
            return ['success' => false, 'message' => 'Sorry, all copies of "' . $book['title'] . '" are currently issued.'];
        }

        $due_date = date('Y-m-d', strtotime("+$days days"));
        $query = "INSERT INTO transactions (user_id, book_id, due_date, status) VALUES (?, ?, ?, 'issued')";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("iis", $user_id, $book_id, $due_date);

        if ($stmt->execute()) {
            $trans_id = $stmt->insert_id;
            // Decrement available copies
            $update = "UPDATE books SET available_copies = available_copies - 1 WHERE book_id = ?";
            $stmt2 = $this->conn->prepare($update);
            $stmt2->bind_param("i", $book_id);
            $stmt2->execute();

            // Log activity into reading_history
            $log = "INSERT INTO reading_history (user_id, book_id) VALUES (?, ?)";
            $stmt3 = $this->conn->prepare($log);
            $stmt3->bind_param("ii", $user_id, $book_id);
            $stmt3->execute();

            return [
                'success' => true, 
                'transaction_id' => $trans_id,
                'due_date' => $due_date,
                'message' => 'Book issued successfully! Due date: ' . $due_date
            ];
        }
        return ['success' => false, 'message' => 'Transaction failed: ' . $stmt->error];
    }

    // Return Book
    public function returnBook($transaction_id) {
        $get_trans = "SELECT transaction_id, book_id, status FROM transactions WHERE transaction_id = ?";
        $stmt = $this->conn->prepare($get_trans);
        $stmt->bind_param("i", $transaction_id);
        $stmt->execute();
        $trans = $stmt->get_result()->fetch_assoc();

        if (!$trans || $trans['status'] == 'returned') {
            return ['success' => false, 'message' => 'Invalid or already returned transaction.'];
        }

        $query = "UPDATE transactions SET return_date = NOW(), status = 'returned' WHERE transaction_id = ?";
        $stmt2 = $this->conn->prepare($query);
        $stmt2->bind_param("i", $transaction_id);

        if ($stmt2->execute()) {
            // Restore available copy
            $update = "UPDATE books SET available_copies = available_copies + 1 WHERE book_id = ?";
            $stmt3 = $this->conn->prepare($update);
            $stmt3->bind_param("i", $trans['book_id']);
            $stmt3->execute();

            return ['success' => true, 'message' => 'Book returned successfully!'];
        }
        return ['success' => false, 'message' => 'Failed to process book return.'];
    }

    // Get User's Active & Past Transactions
    public function getUserTransactions($user_id) {
        $query = "SELECT t.*, b.title, b.author, b.isbn, b.cover_image_url 
                  FROM transactions t 
                  JOIN books b ON t.book_id = b.book_id 
                  WHERE t.user_id = ? 
                  ORDER BY t.transaction_id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>
