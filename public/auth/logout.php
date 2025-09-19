<?php
// logout.php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// wipe everything
$_SESSION = [];
if (ini_get("session.use_cookies")) {
  $params = session_get_cookie_params();
  setcookie(session_name(), '', time() - 42000,
    $params["path"], $params["domain"],
    $params["secure"], $params["httponly"]
  );
}
session_destroy();

// go to login
header('Location: /sems/public/auth/login.php');
exit;
