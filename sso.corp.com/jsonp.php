<?php

session_start();
include 'db.php';

// Check if user has a valid session
$isAuthenticated = isset($_SESSION['user_id']);
$token = $isAuthenticated ? bin2hex(random_bytes(32)) : null;

if ($isAuthenticated) {
    // Update token in database
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("UPDATE users SET sso_token = ? WHERE id = ?");
        $stmt->execute([$token, $_SESSION['user_id']]);
    } catch (PDOException $e) {
        // Log error but continue with token generation
        error_log("Token update failed: " . $e->getMessage());
    }
}

if (isset($_GET['callback'])) {
    $callback = $_GET['callback'];
    echo $callback . '(' . json_encode(['result' => true, 'token' => $token]) . ');';
} else {
    echo json_encode(['result' => true, 'token' => $token]);
}
