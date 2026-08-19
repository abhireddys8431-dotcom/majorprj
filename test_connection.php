<?php
include 'backend/config/db.php';

header('Content-Type: text/html; charset=utf-8');
echo "<div style='font-family: Arial, sans-serif; padding: 20px; border: 1px solid #ccc; border-radius: 8px; max-width: 600px; margin: 40px auto;'>";
echo "<h2 style='color: #c41e3a;'>Smart Library Database Connection Diagnostic</h2>";

if (isset($conn) && $conn) {
    echo "<p style='color: green; font-weight: bold;'>✓ Database Connected Successfully!</p>";
    echo "<p><strong>Host:</strong> " . DB_HOST . "</p>";
    echo "<p><strong>Database Name:</strong> " . DB_NAME . "</p>";

    // Test query for created tables
    $result = mysqli_query($conn, "SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = '" . DB_NAME . "'");
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        echo "<p><strong>Tables Provisioned:</strong> " . $row['table_count'] . "</p>";
    }
    
    // Test count of users
    $user_res = mysqli_query($conn, "SELECT COUNT(*) as user_count FROM users");
    if ($user_res) {
        $u_row = mysqli_fetch_assoc($user_res);
        echo "<p><strong>Registered Users Count:</strong> " . $u_row['user_count'] . "</p>";
    }
} else {
    echo "<p style='color: red; font-weight: bold;'>✗ Connection Failed!</p>";
    echo "<p>Please ensure MySQL is running in XAMPP or update config in <code>backend/config/db.php</code>.</p>";
}
echo "</div>";
?>
