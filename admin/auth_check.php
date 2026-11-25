<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';

// Verify Admin Authentication
$isAdmin = false;
$adminUserId = isset($_SESSION['userid']) ? filter_var($_SESSION['userid'], FILTER_VALIDATE_INT) : false;

if ($adminUserId && $adminUserId > 0) {
    // If user_type is already confirmed in session
    if (isset($_SESSION['user_type']) && (int)$_SESSION['user_type'] === 1) {
        $isAdmin = true;
    } else {
        // Query database to authoritatively verify role
        $stmt = mysqli_prepare($conn, "SELECT type, name, email_id FROM user WHERE id = ? LIMIT 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $adminUserId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_bind_result($stmt, $dbRole, $dbName, $dbEmail);
            if (mysqli_stmt_fetch($stmt) && (int)$dbRole === 1) {
                $isAdmin = true;
                $_SESSION['user_type'] = 1;
                if (empty($_SESSION['name'])) {
                    $_SESSION['name'] = $dbName;
                }
                if (empty($_SESSION['email'])) {
                    $_SESSION['email'] = $dbEmail;
                }
            }
            mysqli_stmt_close($stmt);
        }
    }
}

if (!$isAdmin) {
    // If logged in as customer, redirect to customer home with notice
    if ($adminUserId && isset($_SESSION['user_type']) && (int)$_SESSION['user_type'] === 0) {
        $_SESSION['error'] = "Access denied. Administrator privileges required.";
        header("Location: ../index.php");
        exit();
    }
    // Otherwise redirect to login
    $_SESSION['error'] = "Please log in with an administrator account to continue.";
    header("Location: ../login.php");
    exit();
}

// Generate Admin CSRF Token
if (empty($_SESSION['admin_csrf_token']) || !is_string($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

function admin_csrf_token() {
    return $_SESSION['admin_csrf_token'] ?? '';
}

function verify_admin_csrf($token) {
    $sessionToken = $_SESSION['admin_csrf_token'] ?? '';
    if (!is_string($sessionToken) || $sessionToken === '' || !is_string($token) || $token === '') {
        return false;
    }
    return hash_equals($sessionToken, $token);
}

function admin_escape($val) {
    return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
}
?>
