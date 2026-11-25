<?php
require __DIR__ . '/config.php';

if (PHP_SAPI !== 'cli') {
  http_response_code(404);
  exit;
}

$columns = $pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_COLUMN, 0);
if (!in_array('is_active', $columns, true)) {
  $pdo->exec('ALTER TABLE users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1');
}
if (!in_array('session_version', $columns, true)) {
  $pdo->exec('ALTER TABLE users ADD COLUMN session_version INT NOT NULL DEFAULT 0');
}

$pdo->exec("ALTER TABLE users MODIFY role ENUM('Super Admin','Admin','Doctor','Nurse','Receptionist','Pharmacist','Demo Viewer','Laboratory') NOT NULL DEFAULT 'Receptionist'");
$pdo->exec("UPDATE users SET is_active=0,role='Receptionist' WHERE role='Laboratory'");
$pdo->exec("ALTER TABLE users MODIFY role ENUM('Super Admin','Admin','Doctor','Nurse','Receptionist','Pharmacist','Demo Viewer') NOT NULL DEFAULT 'Receptionist'");

$existingUsers = $pdo->query('SELECT id,password FROM users')->fetchAll();
$disable = $pdo->prepare('UPDATE users SET is_active=0 WHERE id=?');
foreach ($existingUsers as $existingUser) {
  if (password_verify('Admin@123', $existingUser['password']) || password_verify('ChangeMe123!', $existingUser['password'])) {
    $disable->execute([(int)$existingUser['id']]);
  }
}

$admins = $pdo->query("SELECT is_active FROM users WHERE role='Super Admin'")->fetchAll();
if ($admins) {
  fwrite(STDOUT, count($admins) === 1 && (int)$admins[0]['is_active']
    ? "A Super Admin account already exists; no account was created.\n"
    : "A Super Admin record already exists. Review it in the database; no second account was created.\n");
  exit(0);
}

function prompt(string $label): string {
  fwrite(STDOUT, $label);
  return trim((string)fgets(STDIN));
}

$name = prompt('Super Admin name: ');
$email = strtolower(prompt('Super Admin email: '));
$password = prompt('Super Admin password (minimum 12 characters): ');
$confirmation = prompt('Confirm password: ');

if ($name === '' || strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 160) {
  fwrite(STDERR, "Enter a valid name and email address.\n");
  exit(1);
}
if (strlen($password) < 12 || !hash_equals($password, $confirmation)) {
  fwrite(STDERR, "Passwords must match and contain at least 12 characters.\n");
  exit(1);
}

try {
  $hash = password_hash($password, PASSWORD_DEFAULT);
  $emailCheck = $pdo->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
  $emailCheck->execute([$email]);
  $existing = $emailCheck->fetch();
  if ($existing) {
    $create = $pdo->prepare("UPDATE users SET name=?,password=?,role='Super Admin',is_active=1 WHERE id=?");
    $create->execute([$name, $hash, (int)$existing['id']]);
  } else {
    $create = $pdo->prepare('INSERT INTO users(name,email,password,role,is_active) VALUES(?,?,?, ?,1)');
    $create->execute([$name, $email, $hash, 'Super Admin']);
  }
} catch (PDOException $e) {
  if ($e->getCode() === '23000') {
    fwrite(STDERR, "That email already belongs to an account. No account was created.\n");
    exit(1);
  }
  throw $e;
}

fwrite(STDOUT, "Super Admin created. Sign in at http://localhost/jazeera_hospital/login.php\n");
