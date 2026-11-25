<?php
require __DIR__ . '/config/config.php';
current_language();

if (!empty($_SESSION['user']['id'])) {
  $destination = ($_SESSION['user']['role'] ?? '') === 'Demo Viewer' ? 'index.php?page=demo' : 'index.php';
  header('Location: ' . $destination);
  exit;
}

$error = '';
$submittedName = is_string($_POST['name'] ?? null) ? (string)$_POST['name'] : '';
$submittedEmail = is_string($_POST['email'] ?? null) ? (string)$_POST['email'] : '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf();
  $nameValue = $_POST['name'] ?? '';
  $emailValue = $_POST['email'] ?? '';
  $passwordValue = $_POST['password'] ?? '';
  $confirmationValue = $_POST['password_confirmation'] ?? '';
  $name = is_string($nameValue) ? trim($nameValue) : '';
  $email = is_string($emailValue) ? strtolower(trim($emailValue)) : '';
  $password = is_string($passwordValue) ? $passwordValue : '';
  $confirmation = is_string($confirmationValue) ? $confirmationValue : '';

  if ($name === '' || strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 160) {
    $error = 'Please enter a valid name and email address.';
  } elseif (strlen($password) < 12 || !hash_equals($password, $confirmation)) {
    $error = 'Password must be at least 12 characters and match the confirmation.';
  } else {
    try {
      $create = $pdo->prepare("INSERT INTO users(name,email,password,role,is_active,session_version) VALUES(?,?,?,'Demo Viewer',1,0)");
      $create->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
      session_regenerate_id(true);
      $_SESSION['csrf'] = bin2hex(random_bytes(32));
      $_SESSION['user'] = [
        'id' => (int)$pdo->lastInsertId(),
        'name' => $name,
        'email' => $email,
        'role' => 'Demo Viewer',
        'session_version' => 0
      ];
      header('Location: index.php?page=demo');
      exit;
    } catch (PDOException $e) {
      if ($e->getCode() === '23000') {
        $error = 'That email address is already registered.';
      } else {
        throw $e;
      }
    }
  }
}
?>
<!doctype html>
<html lang="<?=h(current_language())?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?=h(t('Create demo account'))?> | Jazeera Hospital</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../frontend/assets/css/style.css" rel="stylesheet">
</head>
<body class="login-page">
  <form class="login-card" method="post" autocomplete="on">
    <div class="language-switch"><a href="?lang=so">SO</a><a href="?lang=en">EN</a></div>
    <img src="../frontend/assets/logo/jazeera.svg" alt="Jazeera Hospital">
    <h2>JAZEERA HOSPITAL</h2>
    <p><?=h(t('This demo is read-only. Create a Demo Viewer account to explore the sample dashboard.'))?></p>
    <div class="alert alert-info" role="note"><?=h(t('Demo accounts can only view synthetic sample information.'))?></div>
    <?php if ($error): ?><div class="alert alert-danger" role="alert"><?=h(t($error))?></div><?php endif; ?>
    <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
    <label for="name"><?=h(t('Full name'))?></label>
    <input class="form-control mb-3" id="name" name="name" required maxlength="120" autocomplete="name" value="<?=h($submittedName)?>">
    <label for="email"><?=h(t('Email address'))?></label>
    <input class="form-control mb-3" id="email" type="email" name="email" required maxlength="160" autocomplete="email" value="<?=h($submittedEmail)?>">
    <label for="password"><?=h(t('Password'))?></label>
    <input class="form-control mb-3" id="password" type="password" name="password" required minlength="12" autocomplete="new-password">
    <label for="password_confirmation"><?=h(t('Confirm password'))?></label>
    <input class="form-control mb-3" id="password_confirmation" type="password" name="password_confirmation" required minlength="12" autocomplete="new-password">
    <button class="btn btn-primary w-100"><?=h(t('Create account'))?></button>
    <small><?=h(t('Already have an account?'))?> <a href="login.php"><?=h(t('Sign in'))?></a></small>
  </form>
</body>
</html>
