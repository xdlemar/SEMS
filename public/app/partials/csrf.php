<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

function csrf_token(){
  $_SESSION['csrf'] = $_SESSION['csrf'] ?? bin2hex(random_bytes(16));
  return $_SESSION['csrf'];
}
function csrf_check(){
  if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? '')) {
    http_response_code(419); exit('CSRF validation failed');
  }
}
