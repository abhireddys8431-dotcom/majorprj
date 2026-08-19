-- Smart Library Management System SQL Schema
-- Engineering Major Project 2025-2026

CREATE DATABASE IF NOT EXISTS library_management_db;
USE library_management_db;

-- 1. Users Table (Students & Admins)
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    roll_number VARCHAR(20) UNIQUE,
    phone VARCHAR(15),
    role ENUM('student', 'admin') DEFAULT 'student',
    profile_pic VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Books Table
CREATE TABLE IF NOT EXISTS books (
    book_id INT AUTO_INCREMENT PRIMARY KEY,
    isbn VARCHAR(20) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    publisher VARCHAR(255),
    publication_year INT,
    category VARCHAR(100),
    description LONGTEXT,
    total_copies INT DEFAULT 1,
    available_copies INT DEFAULT 1,
    cover_image_url VARCHAR(255),
    location_shelf VARCHAR(50),
    location_aisle VARCHAR(50),
    map_x INT DEFAULT 100,
    map_y INT DEFAULT 100,
    added_by INT,
    added_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (added_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_isbn (isbn),
    INDEX idx_title (title),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Book Transactions (Issue / Return)
CREATE TABLE IF NOT EXISTS transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    issue_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    due_date DATE NOT NULL,
    return_date DATE,
    status ENUM('issued', 'returned', 'overdue') DEFAULT 'issued',
    fine_amount DECIMAL(10, 2) DEFAULT 0.00,
    fine_paid BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Book Ratings & Reviews
CREATE TABLE IF NOT EXISTS reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    review_text LONGTEXT,
    review_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    helpful_count INT DEFAULT 0,
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_review (book_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Reading History (for AI Recommendation Engine)
CREATE TABLE IF NOT EXISTS reading_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    view_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    time_spent_minutes INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE,
    INDEX idx_user_history (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Floor Plans / Visual Maps
CREATE TABLE IF NOT EXISTS floor_maps (
    map_id INT AUTO_INCREMENT PRIMARY KEY,
    floor_number INT DEFAULT 1,
    floor_name VARCHAR(100) DEFAULT 'Main Reading Floor',
    floor_image_url VARCHAR(255),
    map_width INT DEFAULT 800,
    map_height INT DEFAULT 600,
    created_by INT,
    created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Wishlist Table
CREATE TABLE IF NOT EXISTS wishlist (
    wishlist_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    added_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist (user_id, book_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample Admin & Student User Seed Data (Passwords hashed with MD5/bcrypt for dev seed)
INSERT INTO users (name, email, password, roll_number, role) VALUES 
('System Administrator', 'admin@library.com', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe11yv19XlS6Z4J9447v8gJ1m6/Wj6p6e', 'ADMIN001', 'admin'),
('Alex Student', 'student@library.com', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe11yv19XlS6Z4J9447v8gJ1m6/Wj6p6e', 'STU2025001', 'student')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Sample Books Seed Data
INSERT INTO books (isbn, title, author, publisher, publication_year, category, description, total_copies, available_copies, location_shelf, location_aisle, map_x, map_y, added_by) VALUES
('9780131103627', 'The C Programming Language', 'Brian W. Kernighan, Dennis M. Ritchie', 'Prentice Hall', 1988, 'Computer Science', 'Classic guide to C programming language and UNIX operating systems.', 5, 4, 'Shelf A1', 'Aisle 1', 150, 120, 1),
('9780262033848', 'Introduction to Algorithms', 'Thomas H. Cormen, Charles E. Leiserson', 'MIT Press', 2009, 'Computer Science', 'Comprehensive textbook covering dynamic programming, graph algorithms, and data structures.', 4, 3, 'Shelf A2', 'Aisle 1', 280, 180, 1),
('9780132350884', 'Clean Code: A Handbook of Agile Software Craftsmanship', 'Robert C. Martin', 'Prentice Hall', 2008, 'Software Engineering', 'Best practices for writing readable, maintainable, and efficient software code.', 6, 6, 'Shelf B1', 'Aisle 2', 420, 250, 1),
('9780596007126', 'Head First Design Patterns', 'Eric Freeman, Elisabeth Robson', 'O\'Reilly Media', 2004, 'Software Engineering', 'Visual guide to software design patterns including Singleton, Observer, and Factory patterns.', 3, 2, 'Shelf B2', 'Aisle 2', 550, 310, 1),
('9781449331818', 'Learning Python', 'Mark Lutz', 'O\'Reilly Media', 2013, 'Programming', 'In-depth introduction to core Python programming language and data structures.', 8, 7, 'Shelf C1', 'Aisle 3', 680, 420, 1)
ON DUPLICATE KEY UPDATE title=VALUES(title);
