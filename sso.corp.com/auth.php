<?php
session_start();

include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['api_key'])) {
    if ($_POST['api_key'] === '1d97cd36bb31edbd207a486fdc94db10') {
        header('Content-Type: application/json');

        $stmt = $pdo->prepare("SELECT * FROM users WHERE sso_token = ?");
        $stmt->execute([$_POST['token']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            echo json_encode(['result' => true, 'user_id' => $user['id'], 'username' => $user['username'], 'email' => $user['email'], 'role' => $user['role']]);
        } else {
            echo json_encode(['result' => false]);
        }
    } else {
        echo json_encode(['result' => false]);
    }
    exit();
}

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
?>

<!DOCTYPE html>
<html>

<head>
    <title>SSO Auth</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            background-color: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 400px;
            width: 90%;
        }

        .status {
            color: #2c3e50;
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }

        .loading {
            display: inline-block;
            width: 30px;
            height: 30px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 1rem auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="status">Please wait while we authenticate...</div>
        <div class="loading"></div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // Listen for messages from the opener window
            window.addEventListener('message', function(event) {
                // Check if it's a login request
                if (event.data.action === 'login') {
                    // Prepare response object
                    const response = {
                        result: '<?php echo $isAuthenticated ? 'ok' : 'error' ?>'
                    };

                    // Add token if authenticated
                    <?php if ($isAuthenticated): ?>
                        response.token = '<?php echo $token ?>';
                    <?php else: ?>
                        response.message = 'Not authenticated';
                    <?php endif; ?>

                    // Send response back to opener
                    window.opener.postMessage(response, "*");
                }
            });
        });
    </script>
</body>

</html>