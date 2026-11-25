<?php
// Update these settings to match your XAMPP MySQL configuration.
define('DB_HOST', 'localhost');
define('DB_NAME', 'jazeera_hospital');
define('DB_USER', 'root');
define('DB_PASS', '');
define('APP_NAME', 'Jazeera Hospital');
if (session_status() === PHP_SESSION_NONE) {
  ini_set('session.use_strict_mode', '1');
  ini_set('session.use_only_cookies', '1');
  $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
  session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax'
  ]);
  session_start();
}
try {
  $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false
  ]);
} catch (PDOException $e) {
  http_response_code(500);
  exit('Database connection failed. Import backend/jazeera_hospital.sql in phpMyAdmin and check backend/config.php.');
}
function h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function current_language() {
  if (isset($_GET['lang']) && in_array($_GET['lang'], ['so', 'en'], true)) {
    $_SESSION['language'] = $_GET['lang'];
  } elseif (!isset($_SESSION['language_default_updated'])) {
    $_SESSION['language'] = 'en';
  }
  $_SESSION['language_default_updated'] = true;
  return $_SESSION['language'] ?? 'en';
}
function t($text) {
  static $somali = [
    'Dashboard' => 'Guudmar',
    'Patients' => 'Bukaanno',
    'Doctors' => 'Dhakhaatiir',
    'Appointments' => 'Ballamo',
    'Departments' => 'Waaxyaha',
    'Admissions' => 'Jiifinta',
    'Laboratory' => 'Shaybaar',
    'Pharmacy' => 'Farmashiye',
    'Billing & Payments' => 'Biilal iyo Lacag-bixin',
    'Staff Management' => 'Maamulka Shaqaalaha',
    'Staff Accounts' => 'Akoonnada Shaqaalaha',
    'Reports' => 'Warbixinno',
    'Demo' => 'Tijaabo',
    'Demo Viewer' => 'Daawade Tijaabo',
    'Logout' => 'Ka bax',
    'Emergency Call' => 'Gurmad degdeg ah',
    'Search records...' => 'Raadi xogta...',
    'Register Patient' => 'Diiwaangeli bukaan',
    'Book Appointment' => 'Qabso ballan',
    'Create Invoice' => 'Samee biil',
    'Medicines' => 'Daawooyin',
    'Staff' => 'Shaqaale',
    'Accounts' => 'Akoonno',
    'Lab Tests' => 'Baaritaannada shaybaarka',
    'Invoices' => 'Biilal',
    'Manage records' => 'Maamul xogta',
    'Page not found' => 'Bogga lama helin',
    'Return to dashboard' => 'Ku noqo guudmarka',
    'Emergency' => 'Gurmad',
    'Male' => 'Lab',
    'Female' => 'Dheddig',
    'Other' => 'Kale',
    'Pending' => 'Sugaya',
    'Confirmed' => 'La xaqiijiyey',
    'Completed' => 'La dhammeeyey',
    'Cancelled' => 'La baajiyey',
    'Admitted' => 'La jiifiyey',
    'Discharged' => 'La fasaxay',
    'Processing' => 'Socda',
    'Requested' => 'La codsaday',
    'Unpaid' => 'Lama bixin',
    'Partial' => 'Qayb ahaan',
    'Paid' => 'La bixiyey',
    'Cash' => 'Lacag caddaan ah',
    'Mobile Money' => 'Lacag telefoon',
    'Card' => 'Kaadh',
    'Active' => 'Firfircoon',
    'Deactivated' => 'La demiyey',
    'Super Admin' => 'Maamule Sare',
    'Admin' => 'Maamule',
    'Nurse' => 'Kalkaaliye',
    'Receptionist' => 'Soo-dhoweeye',
    'Name' => 'Magac',
    'Full Name' => 'Magaca oo buuxa',
    'Email' => 'Email',
    'Role' => 'Door',
    'Is Active' => 'Xaaladda akoonka',
    'Created At' => 'La sameeyey',
    'Gender' => 'Jinsi',
    'Phone' => 'Telefoon',
    'Blood Group' => 'Kooxda dhiigga',
    'Dob' => 'Taariikhda dhalashada',
    'Address' => 'Cinwaan',
    'Emergency Contact' => 'Xiriirka degdegga ah',
    'Medical History' => 'Taariikhda caafimaadka',
    'Specialization' => 'Takhasus',
    'Department Id' => 'Waax',
    'Availability' => 'Jadwalka',
    'Patient Id' => 'Bukaan',
    'Doctor Id' => 'Dhakhtar',
    'Appointment At' => 'Waqtiga ballanta',
    'Reason' => 'Sababta',
    'Ward' => 'Qaybta',
    'Bed Number' => 'Lambarka sariirta',
    'Admitted On' => 'La jiifiyey',
    'Discharged On' => 'La fasaxay',
    'Position' => 'Shaqo',
    'Salary' => 'Mushahar',
    'Test Name' => 'Magaca baaritaanka',
    'Test Date' => 'Taariikhda baaritaanka',
    'Result' => 'Natiijo',
    'Category' => 'Qayb',
    'Quantity' => 'Tiro',
    'Unit Price' => 'Qiimaha halkii xabbo',
    'Expiry Date' => 'Taariikhda dhicitaanka',
    'Description' => 'Faahfaahin',
    'Amount' => 'Qaddar',
    'Paid Amount' => 'Qaddarka la bixiyey',
    'Payment Method' => 'Habka lacag-bixinta',
    'Created At' => 'La sameeyey',
    'Patient name' => 'Magaca bukaanka',
    'Account status' => 'Xaaladda akoonka',
    'Demo sample' => 'Tusaale tijaabo',
    'Sample patients' => 'Bukaanno tusaale ah',
    'Sample appointments' => 'Ballamo tusaale ah',
    'Sample doctors' => 'Dhakhaatiir tusaale ah',
    'Sample medicines' => 'Daawooyin tusaale ah',
    'Sample patient' => 'Bukaan tusaale ah',
    'Sample doctor' => 'Dhakhtar tusaale ah',
    'Sample appointment' => 'Ballan tusaale ah',
    'Sample medicine' => 'Daawo tusaale ah',
    'Patient ID' => 'Bukaan',
    'Doctor ID' => 'Dhakhtar',
    'No real patient data is used on this page.' => 'Boggan kuma jiraan xog bukaan oo dhab ah.',
    'Practice and explore the hospital system using fictional sample data.' => 'Ku tababar oo sahami nidaamka adigoo adeegsanaya xog mala-awaal ah.',
    'Open demo' => 'Fur tijaabada',
    'Language' => 'Luqad',
    'Welcome to Jazeera Hospital' => 'Ku soo dhowow Isbitaalka Jazeera',
    'Hospital Management System' => 'Nidaamka Maamulka Isbitaalka',
    'Your Health, Our Priority' => 'Caafimaadkaagu waa mudnaantayada',
    'Upcoming Appointments' => 'Ballamaha soo socda',
    'Quick Actions' => 'Hawlaha degdegga ah',
    'Add New' => 'Ku dar cusub',
    'Actions' => 'Hawlaha',
    'Save' => 'Kaydi',
    'Cancel' => 'Jooji',
    'Edit Record' => 'Wax ka beddel xogta',
    'Delete this record?' => 'Ma tirtiraysaa xogtan?',
    'Record deleted.' => 'Xogta waa la tirtiray.',
    'Record saved successfully.' => 'Xogta waa la kaydiyey.',
    'Staff account created.' => 'Akoonka shaqaalaha waa la sameeyey.',
    'Staff account updated.' => 'Akoonka shaqaalaha waa la cusboonaysiiyey.',
    'Staff account deactivated.' => 'Akoonka shaqaalaha waa la demiyey.',
    'Staff account deleted.' => 'Akoonka shaqaalaha waa la tirtiray.',
    'Access denied' => 'Gelitaan lama oggola',
    'Your role is not permitted to open this page.' => 'Doorkaagu uma oggola inaad boggan furto.',
    'You are not authorized to change these records.' => 'Looma oggola inaad xogtan beddesho.',
    'Select a valid record.' => 'Dooro xog sax ah.',
    'This account cannot be deleted.' => 'Akoonkan lama tirtiri karo.',
    'Super Admin accounts cannot be changed here.' => 'Akoonka Super Admin halkan lagama beddeli karo.',
    'Enter a valid name, unique email address, and staff role.' => 'Geli magac, email gaar ah iyo doorka shaqaalaha.',
    'Select a valid staff role.' => 'Dooro door shaqaale oo sax ah.',
    'Select a valid account status.' => 'Dooro xaalad akoon oo sax ah.',
    'New staff passwords must match and contain at least 12 characters.' => 'Password-ka cusub waa inuu is waafaqaa oo ka koobnaadaa ugu yaraan 12 xaraf.',
    'Passwords must match and contain at least 12 characters.' => 'Password-yadu waa inay is waafaqaan oo ka koobnaadaan ugu yaraan 12 xaraf.',
    'That email address is already assigned to an account.' => 'Email-kan akoon kale ayaa isticmaala.',
    'Invalid form value.' => 'Xogta foomku sax ma aha.',
    'Patient name is required.' => 'Magaca bukaanka waa loo baahan yahay.',
    'Use the staff account form to manage user accounts.' => 'Adeegso foomka akoonnada shaqaalaha.',
    'Invalid action.' => 'Hawlgalka lama aqoonsan.',
    'No records found.' => 'Xog lama helin.',
    'No appointments yet.' => 'Weli ballamo ma jiraan.',
    'Select...' => 'Dooro...',
    'Patient' => 'Bukaan',
    'Doctor' => 'Dhakhtar',
    'Date & Time' => 'Taariikhda iyo waqtiga',
    'Status' => 'Xaalad',
    'Manage' => 'Maamul',
    'Welcome to the practice demo' => 'Ku soo dhowow tijaabada nidaamka',
    'Sample data only — no real patient information is shown.' => 'Xog tusaale ah oo keliya — xog bukaan oo dhab ah lama muujinayo.',
    'This demo is read-only. Create a Demo Viewer account to explore the sample dashboard.' => 'Tijaabadani wax lagama beddeli karo. Samee akoon Daawade Tijaabo si aad u aragto tusaalaha.',
    'Practice and explore the hospital system using fictional sample data.' => 'Ku tababar oo sahami nidaamka adigoo adeegsanaya xog mala-awaal ah.',
    'Sample patients' => 'Bukaanno tusaale ah',
    'Sample appointments' => 'Ballamo tusaale ah',
    'Sample doctors' => 'Dhakhaatiir tusaale ah',
    'Sample medicines' => 'Daawooyin tusaale ah',
    'Sample Patient 01' => 'Bukaan Tusaale 01',
    'Sample Patient 02' => 'Bukaan Tusaale 02',
    'Sample Patient 03' => 'Bukaan Tusaale 03',
    'Sample — 09:00' => 'Tusaale — 09:00',
    'Sample — 11:30' => 'Tusaale — 11:30',
    'Sample — 14:00' => 'Tusaale — 14:00',
    'Dr. Sample A' => 'Dr. Tusaale A',
    'Dr. Sample B' => 'Dr. Tusaale B',
    'Email address' => 'Cinwaanka email-ka',
    'Password' => 'Password',
    'Confirm password' => 'Xaqiiji password-ka',
    'Full name' => 'Magaca oo buuxa',
    'Sign In' => 'Gal',
    'Create demo account' => 'Samee akoon tijaabo',
    'Create account' => 'Samee akoon',
    'Already have an account?' => 'Akoon ma leedahay?',
    'Sign in' => 'Soo gal',
    'Demo accounts can only view synthetic sample information.' => 'Akoonnada tijaabadu waxay arki karaan xog tusaale ah oo keliya.',
    'Email or password is incorrect.' => 'Email-ka ama password-ku waa khalad.',
    'Please enter a valid name and email address.' => 'Geli magac iyo cinwaan email oo sax ah.',
    'Password must be at least 12 characters and match the confirmation.' => 'Password-ku waa inuu ugu yaraan 12 xaraf yahay, isla markaana xaqiijintu la mid noqotaa.',
    'That email address is already registered.' => 'Email-kan hore ayaa loo diiwaangeliyey.',
    'Account created. You are now signed in to the demo.' => 'Akoonka waa la sameeyey; hadda waxaad gashay tijaabada.',
    'Print Report' => 'Daabac warbixinta',
    'Summary of records available to your role.' => 'Soo koobidda xogta doorkaagu heli karo.',
    'Total' => 'Wadarta',
    'At least 12 characters; leave blank when keeping the current password.' => 'Ugu yaraan 12 xaraf; bannaanee haddii aadan beddelayn password-ka.',
    'All rights reserved.' => 'Xuquuqdu way xafidan tahay.',
    'Toggle navigation' => 'Fur ama qari liiska',
    'Edit record' => 'Wax ka beddel xogta',
    'Delete record' => 'Tirtir xogta'
  ];
  return current_language() === 'so' ? ($somali[$text] ?? $text) : $text;
}
function csrf_token() { if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function verify_csrf() {
  $expected = $_SESSION['csrf'] ?? '';
  $submitted = $_POST['csrf'] ?? '';
  if (!is_string($expected) || !is_string($submitted) || !hash_equals($expected, $submitted)) {
    http_response_code(419);
    exit('Invalid security token. Please go back and try again.');
  }
}
function role_pages($role = null) {
  $role = $role ?? ($_SESSION['user']['role'] ?? '');
  $all = ['dashboard', 'patients', 'doctors', 'appointments', 'departments', 'admissions', 'staff', 'laboratory', 'pharmacy', 'billing', 'reports'];
  $pages = [
    'Super Admin' => array_merge($all, ['users']),
    'Admin' => $all,
    'Doctor' => ['dashboard', 'patients', 'appointments', 'admissions', 'laboratory', 'reports'],
    'Nurse' => ['dashboard', 'patients', 'appointments', 'admissions', 'laboratory'],
    'Receptionist' => ['dashboard', 'patients', 'appointments', 'admissions', 'billing'],
    'Pharmacist' => ['dashboard', 'pharmacy'],
    'Demo Viewer' => ['dashboard', 'demo']
  ];
  return $pages[$role] ?? [];
}
function can_access_page($page) { return in_array($page, role_pages(), true); }
function is_super_admin() { return ($_SESSION['user']['role'] ?? '') === 'Super Admin'; }
function can_manage($page) {
  $role = $_SESSION['user']['role'] ?? '';
  $permissions = [
    'Super Admin' => ['patients', 'doctors', 'appointments', 'departments', 'admissions', 'staff', 'laboratory', 'pharmacy', 'billing', 'users'],
    'Admin' => ['patients', 'doctors', 'appointments', 'departments', 'admissions', 'staff', 'laboratory', 'pharmacy', 'billing'],
    'Doctor' => ['patients', 'appointments', 'laboratory'],
    'Nurse' => ['patients', 'admissions'],
    'Receptionist' => ['patients', 'appointments', 'admissions', 'billing'],
    'Pharmacist' => ['pharmacy']
  ];
  return in_array($page, $permissions[$role] ?? [], true);
}
function require_login() {
  global $pdo;
  if (empty($_SESSION['user']['id'])) { header('Location: login.php'); exit; }
  $st = $pdo->prepare('SELECT name,email,role,is_active,session_version FROM users WHERE id=? LIMIT 1');
  $st->execute([(int)$_SESSION['user']['id']]);
  $user = $st->fetch();
  if (!$user || !(int)$user['is_active'] || !in_array($user['role'], ['Super Admin','Admin','Doctor','Nurse','Receptionist','Pharmacist','Demo Viewer'], true)
    || !isset($_SESSION['user']['session_version'])
    || (int)$_SESSION['user']['session_version'] !== (int)$user['session_version']) {
    $_SESSION = [];
    session_regenerate_id(true);
    header('Location: login.php');
    exit;
  }
  $_SESSION['user'] = [
    'id' => (int)$_SESSION['user']['id'],
    'name' => $user['name'],
    'email' => $user['email'],
    'role' => $user['role'],
    'session_version' => (int)$user['session_version']
  ];
}
