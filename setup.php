<?php
$host = '127.0.0.1';
$dbname = 'ssoCorp';
$username = 'user';
$password = 'password';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create users table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
        sso_token VARCHAR(255) DEFAULT NULL
    )";
    $pdo->exec($sql);
    echo "Users table checked/created successfully<br>";

    // Check if table is empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        // Insert sample users
        $users = [
            ['soltan', 'soltan@gmail.com', password_hash('soltan', PASSWORD_DEFAULT), 'admin'],
            ['mamad', 'mamad@gmail.com', password_hash('mamad', PASSWORD_DEFAULT), 'user']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        
        foreach ($users as $user) {
            $stmt->execute($user);
        }
        echo "Sample users inserted successfully<br>";
    } else {
        echo "Users table already contains data<br>";
    }

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
