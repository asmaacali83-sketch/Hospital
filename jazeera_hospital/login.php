<?php
require __DIR__ . '/config/config.php';
current_language();

if (!empty($_SESSION['user']['id'])) {
  header('Location: index.php');
  exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf();
  $emailValue = $_POST['email'] ?? '';
  $passwordValue = $_POST['password'] ?? '';
  $email = is_string($emailValue) ? strtolower(trim($emailValue)) : '';
  $password = is_string($passwordValue) ? $passwordValue : '';
  $st = $pdo->prepare('SELECT id,name,email,password,role,is_active,session_version FROM users WHERE email=? LIMIT 1');
  $st->execute([$email]);
  $user = $st->fetch();
  $allowedRoles = ['Super Admin', 'Admin', 'Doctor', 'Nurse', 'Receptionist', 'Pharmacist', 'Demo Viewer'];

  if ($user && (int)$user['is_active'] === 1 && in_array($user['role'], $allowedRoles, true) && password_verify($password, $user['password'])) {
    if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
      $rehash = $pdo->prepare('UPDATE users SET password=? WHERE id=?');
      $rehash->execute([password_hash($password, PASSWORD_DEFAULT), (int)$user['id']]);
    }
    session_regenerate_id(true);
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    $_SESSION['user'] = [
      'id' => (int)$user['id'],
      'name' => $user['name'],
      'email' => $user['email'],
      'role' => $user['role'],
      'session_version' => (int)$user['session_version']
    ];
    header('Location: ' . ($user['role'] === 'Demo Viewer' ? 'index.php?page=demo' : 'index.php'));
    exit;
  }
  $error = 'Email or password is incorrect.';
}
?>
<!doctype html>
<html lang="<?=h(current_language())?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?=h(t('Sign In'))?> | Jazeera Hospital</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../frontend/assets/css/style.css" rel="stylesheet">
</head>
<body class="login-page">
  <form class="login-card" method="post" autocomplete="on">
    <div class="language-switch"><a href="?lang=so">SO</a><a href="?lang=en">EN</a></div>
    <img src="../frontend/assets/logo/jazeera.svg" alt="Jazeera Hospital">
    <h2>JAZEERA HOSPITAL</h2>
    <p><?=h(t('Hospital Management System'))?></p>
    <?php if ($error): ?><div class="alert alert-danger" role="alert"><?=h(t($error))?></div><?php endif; ?>
    <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
    <label for="email"><?=h(t('Email address'))?></label>
    <input class="form-control mb-3" id="email" type="email" name="email" required autocomplete="username" maxlength="160">
    <label for="password"><?=h(t('Password'))?></label>
    <input class="form-control mb-3" id="password" type="password" name="password" required autocomplete="current-password">
    <button class="btn btn-primary w-100"><?=h(t('Sign In'))?></button>
    <small><a href="register.php"><?=h(t('Create demo account'))?></a></small>
  </form>
</body>
</html>
