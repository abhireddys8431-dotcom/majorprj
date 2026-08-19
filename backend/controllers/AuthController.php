<?php
class AuthController {
    private $conn;

    public function __construct($database_connection) {
        $this->conn = $database_connection;
    }

    // Register User
    public function register($name, $email, $password, $roll_number, $role = 'student') {
        if (empty($name) || empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Please fill in all required fields.'];
        }

        // Check if email already exists
        $check_query = "SELECT user_id FROM users WHERE email = ?";
        $stmt_check = $this->conn->prepare($check_query);
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            return ['success' => false, 'message' => 'Email address is already registered.'];
        }

        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $query = "INSERT INTO users (name, email, password, roll_number, role) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sssss", $name, $email, $hashed_password, $roll_number, $role);

        if ($stmt->execute()) {
            return [
                'success' => true, 
                'user_id' => $stmt->insert_id,
                'message' => 'User registered successfully!'
            ];
        } else {
            return ['success' => false, 'message' => 'Registration error: ' . $stmt->error];
        }
    }

    // Login User
    public function login($email, $password) {
        if (empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Please enter email and password.'];
        }

        $query = "SELECT * FROM users WHERE email = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Allow password_verify or MD5 fallback for seed testing
            $pwd_valid = password_verify($password, $user['password']) || (md5($password) === $user['password']);
            
            if ($pwd_valid) {
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];

                return [
                    'success' => true,
                    'user' => [
                        'user_id' => $user['user_id'],
                        'name' => $user['name'],
                        'email' => $user['email'],
                        'role' => $user['role'],
                        'roll_number' => $user['roll_number']
                    ],
                    'message' => 'Login successful'
                ];
            }
        }
        return ['success' => false, 'message' => 'Invalid email or password.'];
    }

    // Logout User
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = array();
        session_destroy();
        return ['success' => true, 'message' => 'Logged out successfully'];
    }

    // Get User Profile by ID
    public function getUserById($user_id) {
        $query = "SELECT user_id, name, email, roll_number, role, profile_pic, created_at FROM users WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}
?>
