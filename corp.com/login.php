<?php
session_start();

// Handle token verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'])) {
    $token = $_POST['token'];
    $api_key = '1d97cd36bb31edbd207a486fdc94db10';
    
    // Make curl request to verify token
    $ch = curl_init('http://sso.corp.com:9001/auth.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'action' => 'verify_token',
        'token' => $token,
        'api_key' => $api_key
    ]));
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if ($result && $result['result'] === true) {
        // Token is valid, issue session
        $_SESSION['user_id'] = $result['user_id'];
        $_SESSION['username'] = $result['username'];
        $_SESSION['email'] = $result['email'];
        $_SESSION['role'] = $result['role'];
        header("Location: index.php");
        exit();
    } else {
        $error = "Invalid token";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Corp Main Website</title>
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
            max-width: 500px;
            width: 90%;
        }

        h1 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
        }

        .status {
            margin: 1rem 0;
            padding: 1rem;
            border-radius: 5px;
            background-color: #f8f9fa;
        }

        .status.waiting {
            color: #f39c12;
        }

        .status.success {
            color: #27ae60;
        }

        .status.error {
            color: #e74c3c;
        }

        button {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #2980b9;
        }

        button:disabled {
            background-color: #95a5a6;
            cursor: not-allowed;
        }

        .token-display {
            margin-top: 1rem;
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: 5px;
            word-break: break-all;
            display: none;
        }

        .token-display.visible {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Corp Main Website</h1>
        <?php if (isset($error)): ?>
            <div class="status error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <div class="status waiting">Ready to connect...</div>
        <button id="connectBtn">Connect to SSO</button>
        <div class="token-display" id="tokenDisplay"></div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const connectBtn = document.getElementById('connectBtn');
            const statusDiv = document.querySelector('.status');
            const tokenDisplay = document.getElementById('tokenDisplay');
            let popup = null;

            connectBtn.addEventListener('click', function() {
                // Open the SSO window
                popup = window.open('http://sso.corp.com:9001/auth.php', 'SSO Window', 'width=600,height=400');
                
                if (!popup) {
                    statusDiv.textContent = 'Failed to open popup. Please allow popups for this site.';
                    statusDiv.className = 'status error';
                    return;
                }

                statusDiv.textContent = 'Waiting for response...';
                statusDiv.className = 'status waiting';
                connectBtn.disabled = true;

                setTimeout(() => {
                    // Send message to the popup
                    const message = { action: 'login' };
                    popup.postMessage(message, 'http://sso.corp.com:9001');
                }, 2000);

                // Listen for response
                window.addEventListener('message', function(event) {
                    // Check origin
                    if (event.origin !== 'http://sso.corp.com:9001') {
                        return;
                    }

                    const response = event.data;
                    
                    if (response.result === 'ok' && response.token) {
                        statusDiv.textContent = 'Verifying token, please wait...';
                        statusDiv.className = 'status success';
                        tokenDisplay.className = 'token-display';

                        // Send token to server using XHR
                        const xhr = new XMLHttpRequest();
                        xhr.open('POST', window.location.href, true);
                        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                        
                        xhr.onload = function() {
                            if (xhr.status === 200) {
                                // If the response is a redirect, follow it
                                if (xhr.responseURL !== window.location.href) {
                                    window.location.href = xhr.responseURL;
                                }
                            } else {
                                statusDiv.textContent = 'Failed to verify token';
                                statusDiv.className = 'status error';
                                connectBtn.disabled = false;
                            }
                        };
                        
                        xhr.onerror = function() {
                            statusDiv.textContent = 'Network error occurred';
                            statusDiv.className = 'status error';
                            connectBtn.disabled = false;
                        };
                        
                        xhr.send('token=' + encodeURIComponent(response.token));
                    } else {
                        statusDiv.textContent = 'Failed to get token';
                        statusDiv.className = 'status error';
                        connectBtn.disabled = false;
                    }

                    // Close the popup
                    if (popup) {
                        popup.close();
                    }
                });
            });
        });
    </script>
</body>
</html>