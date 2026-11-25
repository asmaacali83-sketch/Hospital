<?php
require __DIR__ . '/config/config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit('Method not allowed.');
}

verify_csrf();
$_SESSION = [];
$params = session_get_cookie_params();
setcookie(session_name(), '', [
  'expires' => time() - 42000,
  'path' => $params['path'],
  'secure' => $params['secure'],
  'httponly' => $params['httponly'],
  'samesite' => $params['samesite']
]);
session_destroy();
header('Location: login.php');
exit;
