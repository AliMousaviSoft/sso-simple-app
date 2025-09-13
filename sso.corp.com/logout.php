<?php
session_start();

// Clear the remember me cookie if it exists
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, '/');
}

session_destroy();
header("Location: login.php");
exit();
