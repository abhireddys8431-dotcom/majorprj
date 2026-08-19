<?php
class RecommendationEngine {
    private $conn;

    public function __construct($database_connection) {
        $this->conn = $database_connection;
    }

    // Content-Based Filtering (by category preferences)
    public function getRecommendationsByCategory($user_id, $limit = 5) {
        $limit = intval($limit);
        $query = "SELECT DISTINCT b.* FROM books b 
                  INNER JOIN reading_history rh ON b.category = (
                      SELECT b2.category FROM books b2 
                      WHERE b2.book_id IN (SELECT rh2.book_id FROM reading_history rh2 WHERE rh2.user_id = ?) 
                      ORDER BY rh.view_date DESC LIMIT 1
                  ) 
                  WHERE b.book_id NOT IN (SELECT rh3.book_id FROM reading_history rh3 WHERE rh3.user_id = ?) 
                    AND b.available_copies > 0 
                  LIMIT ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("iii", $user_id, $user_id, $limit);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Fallback to latest available books if user history is empty
        if (empty($res)) {
            $fallback = "SELECT * FROM books WHERE available_copies > 0 ORDER BY book_id DESC LIMIT ?";
            $stmt_f = $this->conn->prepare($fallback);
            $stmt_f->bind_param("i", $limit);
            $stmt_f->execute();
            return $stmt_f->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        return $res;
    }

    // Collaborative Filtering (by similar peer readers)
    public function getRecommendationsByPeers($user_id, $limit = 5) {
        $limit = intval($limit);
        $query = "SELECT DISTINCT b.* FROM books b 
                  WHERE b.book_id IN (
                      SELECT rh.book_id FROM reading_history rh WHERE rh.user_id IN (
                          SELECT DISTINCT rh_peer.user_id FROM reading_history rh_peer 
                          WHERE rh_peer.book_id IN (
                              SELECT rh_user.book_id FROM reading_history rh_user WHERE rh_user.user_id = ?
                          ) AND rh_peer.user_id != ?
                      )
                  ) 
                  AND b.book_id NOT IN (SELECT rh_own.book_id FROM reading_history rh_own WHERE rh_own.user_id = ?) 
                  AND b.available_copies > 0 
                  LIMIT ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("iiii", $user_id, $user_id, $user_id, $limit);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        if (empty($res)) {
            return $this->getTopRatedBooks($limit);
        }
        return $res;
    }

    // Top Rated / Trending Books
    public function getTopRatedBooks($limit = 5) {
        $limit = intval($limit);
        $query = "SELECT b.*, COALESCE(AVG(r.rating), 4.5) as avg_rating, COUNT(r.review_id) as review_count 
                  FROM books b 
                  LEFT JOIN reviews r ON b.book_id = r.book_id 
                  WHERE b.available_copies > 0 
                  GROUP BY b.book_id 
                  ORDER BY avg_rating DESC, book_id DESC 
                  LIMIT ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Log User Reading / Viewing Activity
    public function logActivity($user_id, $book_id, $time_spent = 0) {
        $query = "INSERT INTO reading_history (user_id, book_id, time_spent_minutes) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("iii", $user_id, $book_id, $time_spent);
        return $stmt->execute();
    }
}
?>
