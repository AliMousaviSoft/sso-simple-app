<?php
session_start();

include 'db.php';

// Secret key for HMAC - should be stored securely in environment variables in production
define('HMAC_SECRET_KEY', 'a1b2c3d4e5f6g7h8i9j0k1');

// Function to generate HMAC
function generateHmac($data) {
    return hash_hmac('sha256', $data, HMAC_SECRET_KEY);
}

// Function to verify HMAC
function verifyHmac($data, $hmac) {
    return hash_equals(generateHmac($data), $hmac);
}

// If user is already logged in, redirect to index
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Check for remember me cookie
if (isset($_COOKIE['remember_me'])) {
    $cookie_data = json_decode(base64_decode($_COOKIE['remember_me']), true);
    
    if ($cookie_data && isset($cookie_data['user_id']) && isset($cookie_data['hmac'])) {
        $user_id = $cookie_data['user_id'];
        $hmac = $cookie_data['hmac'];
        
        // Verify HMAC
        if (verifyHmac($user_id, $hmac)) {
            try {
                $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $stmt = $pdo->prepare("SELECT id, username FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();

                if ($user) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    header("Location: index.php");
                    exit();
                }
            } catch(PDOException $e) {
                // If there's an error, just continue to show login form
            }
        } else {
            echo "HMAC verification failed";
            die();
        }
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        $stmt->execute([$_POST['username']]);
        $user = $stmt->fetch();

        if ($user && password_verify($_POST['password'], $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Handle remember me with HMAC
            if (isset($_POST['remember_me']) && $_POST['remember_me'] == '1') {
                $user_id = $user['id'];
                $hmac = generateHmac($user_id);
                
                $cookie_data = [
                    'user_id' => $user_id,
                    'hmac' => $hmac
                ];
                
                $cookie_value = base64_encode(json_encode($cookie_data));
                setcookie('remember_me', $cookie_value, time() + (30 * 24 * 60 * 60), '/', '', false, true); // 30 days, secure, httponly
            }

            header("Location: index.php");
            exit();
        } else {
            $error = "Invalid username or password";
        }
    } catch(PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 400px;
            margin: 0 auto;
            padding: 20px;
        }
        .error {
            color: red;
            margin-bottom: 10px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
        }
        .remember-me {
            margin: 15px 0;
        }
        button {
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <h2>Login</h2>
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>

        <div class="remember-me">
            <input type="checkbox" id="remember_me" name="remember_me" value="1">
            <label for="remember_me">Remember me</label>
        </div>
        
        <button type="submit">Login</button>
    </form>
</body>
</html>
