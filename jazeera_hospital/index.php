<?php
require __DIR__ . '/config/config.php';
require_login();

$modules = [
  'patients' => ['Patients', 'patients', 'full_name,gender,phone,blood_group', 'full_name,gender,dob,phone,address,blood_group,emergency_contact,medical_history'],
  'doctors' => ['Doctors', 'doctors', 'full_name,specialization,phone,email', 'full_name,specialization,department_id,phone,email,availability'],
  'appointments' => ['Appointments', 'appointments', 'patient_id,doctor_id,appointment_at,status', 'patient_id,doctor_id,appointment_at,reason,status'],
  'departments' => ['Departments', 'departments', 'name,description', 'name,description'],
  'admissions' => ['Admissions', 'admissions', 'patient_id,ward,bed_number,admitted_on,status', 'patient_id,ward,bed_number,admitted_on,discharged_on,status'],
  'staff' => ['Staff', 'staff', 'full_name,position,phone,email', 'full_name,position,phone,email,salary'],
  'laboratory' => ['Laboratory', 'lab_tests', 'patient_id,test_name,test_date,status', 'patient_id,test_name,test_date,result,status'],
  'pharmacy' => ['Pharmacy', 'medicines', 'name,category,quantity,unit_price,expiry_date', 'name,category,quantity,unit_price,expiry_date'],
  'billing' => ['Billing & Payments', 'invoices', 'patient_id,description,amount,paid_amount,status', 'patient_id,description,amount,paid_amount,payment_method,status'],
  'users' => ['Staff Accounts', 'users', 'name,email,role,is_active,created_at', 'name,email,role,is_active']
];

$navigation = [
  'dashboard' => ['Dashboard', 'fa-house'],
  'patients' => ['Patients', 'fa-users'],
  'doctors' => ['Doctors', 'fa-user-doctor'],
  'appointments' => ['Appointments', 'fa-calendar-check'],
  'departments' => ['Departments', 'fa-building'],
  'admissions' => ['Admissions', 'fa-bed'],
  'laboratory' => ['Laboratory', 'fa-flask'],
  'pharmacy' => ['Pharmacy', 'fa-pills'],
  'billing' => ['Billing & Payments', 'fa-file-invoice-dollar'],
  'staff' => ['Staff Management', 'fa-people-group'],
  'reports' => ['Reports', 'fa-chart-column'],
  'users' => ['Staff Accounts', 'fa-user-shield'],
  'demo' => ['Demo', 'fa-eye']
];

$language = current_language();
$page = (string)($_GET['page'] ?? 'dashboard');
$error = '';
$isKnownPage = in_array($page, ['dashboard', 'reports', 'demo'], true) || isset($modules[$page]);
$isAllowedPage = $isKnownPage && can_access_page($page);
$clinicalPatientAccess = in_array($_SESSION['user']['role'], ['Super Admin', 'Admin', 'Doctor', 'Nurse'], true);

if (!$isKnownPage) {
  http_response_code(404);
} elseif (!$isAllowedPage) {
  http_response_code(403);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAllowedPage) {
  verify_csrf();
  if (!isset($modules[$page]) || !can_manage($page)) {
    http_response_code(403);
    $error = 'You are not authorized to change these records.';
  } else {
    [$title, $table, $listFieldText, $formFieldText] = $modules[$page];
    $listFields = explode(',', $listFieldText);
    $formFields = explode(',', $formFieldText);
    if ($page === 'patients' && !$clinicalPatientAccess) {
      $listFields = ['full_name', 'gender', 'phone'];
      $formFields = ['full_name', 'gender', 'dob', 'phone', 'address'];
    }

    $action = (string)($_POST['action'] ?? '');
    $recordId = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);

    if ($action === 'delete') {
      if (!$recordId || $recordId < 1) {
        $error = 'Select a valid record.';
      } elseif ($page === 'users') {
        $targetQuery = $pdo->prepare('SELECT id,role FROM users WHERE id=? LIMIT 1');
        $targetQuery->execute([$recordId]);
        $target = $targetQuery->fetch();
        if (!$target || $target['role'] === 'Super Admin' || (int)$target['id'] === (int)$_SESSION['user']['id']) {
          $error = 'This account cannot be deleted.';
        } else {
          $delete = $pdo->prepare('DELETE FROM users WHERE id=?');
          $delete->execute([$recordId]);
          $_SESSION['flash'] = 'Staff account deleted.';
          header('Location: index.php?page=users');
          exit;
        }
      } else {
        $delete = $pdo->prepare("DELETE FROM `$table` WHERE id=?");
        $delete->execute([$recordId]);
        $_SESSION['flash'] = 'Record deleted.';
        header("Location: index.php?page=$page");
        exit;
      }
    } elseif ($action === 'save' && $page === 'users') {
      $name = trim((string)($_POST['name'] ?? ''));
      $email = strtolower(trim((string)($_POST['email'] ?? '')));
      $role = trim((string)($_POST['role'] ?? ''));
      $allowedRoles = ['Admin', 'Doctor', 'Nurse', 'Receptionist', 'Pharmacist'];
      $password = (string)($_POST['password'] ?? '');
      $passwordConfirmation = (string)($_POST['password_confirmation'] ?? '');
      $activeValue = (string)($_POST['is_active'] ?? '1');
      $target = null;
      $isSelf = false;

      if ($recordId && $recordId > 0) {
        $targetQuery = $pdo->prepare('SELECT id,role,is_active FROM users WHERE id=? LIMIT 1');
        $targetQuery->execute([$recordId]);
        $target = $targetQuery->fetch();
        $isSelf = $target && (int)$target['id'] === (int)$_SESSION['user']['id'];
        if (!$target || ($target['role'] === 'Super Admin' && !$isSelf)) {
          $error = 'Super Admin accounts cannot be changed here.';
        }
      }

      if ($error === '' && ($name === '' || strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 160)) {
        $error = 'Enter a valid name, unique email address, and staff role.';
      } elseif ($error === '' && !$isSelf && !in_array($role, $allowedRoles, true)) {
        $error = 'Select a valid staff role.';
      } elseif ($error === '' && !$isSelf && !in_array($activeValue, ['0', '1'], true)) {
        $error = 'Select a valid account status.';
      } elseif ($isSelf) {
        $role = $target['role'];
        $activeValue = '1';
      }

      if ($error === '' && (!$recordId || $recordId < 1) && (strlen($password) < 12 || !hash_equals($password, $passwordConfirmation))) {
        $error = 'New staff passwords must match and contain at least 12 characters.';
      } elseif ($error === '' && $password !== '' && (strlen($password) < 12 || !hash_equals($password, $passwordConfirmation))) {
        $error = 'Passwords must match and contain at least 12 characters.';
      }

      if ($error === '') {
        try {
          if ($recordId && $recordId > 0) {
            $values = [$name, $email, $role, (int)$activeValue];
            $setPassword = $password !== '';
            if ($setPassword) $values[] = password_hash($password, PASSWORD_DEFAULT);
            $values[] = $recordId;
            $sql = 'UPDATE users SET name=?,email=?,role=?,is_active=?,session_version=session_version+1';
            if ($setPassword) $sql .= ',password=?';
            $sql .= ' WHERE id=?';
            $save = $pdo->prepare($sql);
            $save->execute($values);
            if ($isSelf) {
              $_SESSION['user']['session_version']++;
              session_regenerate_id(true);
            }
          } else {
            $save = $pdo->prepare('INSERT INTO users(name,email,password,role,is_active) VALUES(?,?,?,?,?)');
            $save->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role, (int)$activeValue]);
          }
          $_SESSION['flash'] = $recordId && $recordId > 0
            ? ((int)$activeValue ? 'Staff account updated.' : 'Staff account deactivated.')
            : 'Staff account created.';
          header('Location: index.php?page=users');
          exit;
        } catch (PDOException $e) {
          if ($e->getCode() === '23000') {
            $error = 'That email address is already assigned to an account.';
          } else {
            throw $e;
          }
        }
      }
    } elseif ($action === 'save') {
      $data = [];
      foreach ($formFields as $field) {
        $rawValue = $_POST[$field] ?? '';
        if (!is_scalar($rawValue)) {
          $error = 'Invalid form value.';
          break;
        }
        $value = trim((string)$rawValue);
        if ($field === 'appointment_at' && $value !== '') $value = str_replace('T', ' ', $value) . (strlen($value) === 16 ? ':00' : '');
        if ($value === '' && in_array($field, ['department_id', 'dob', 'expiry_date', 'discharged_on', 'payment_method'], true)) {
          $data[$field] = null;
        } else {
          $data[$field] = $value;
        }
      }

      if ($error === '' && $page === 'patients' && trim((string)($data['full_name'] ?? '')) === '') {
        $error = 'Patient name is required.';
      }
      if ($error === '' && $page === 'users') {
        $error = 'Use the staff account form to manage user accounts.';
      }
      if ($error === '') {
        if ($page === 'patients' && (!$recordId || $recordId < 1)) {
          $data['patient_code'] = 'JH-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        }
        if ($page === 'appointments' && empty($data['status'])) $data['status'] = 'Pending';

        if ($recordId && $recordId > 0) {
          $sets = array_map(static function ($field) { return "`$field`=?"; }, array_keys($data));
          $values = array_values($data);
          $values[] = $recordId;
          $save = $pdo->prepare("UPDATE `$table` SET " . implode(',', $sets) . ' WHERE id=?');
          $save->execute($values);
        } else {
          $fields = array_keys($data);
          $columns = implode('`,`', $fields);
          $placeholders = implode(',', array_fill(0, count($fields), '?'));
          $save = $pdo->prepare("INSERT INTO `$table` (`$columns`) VALUES ($placeholders)");
          $save->execute(array_values($data));
        }
        $_SESSION['flash'] = 'Record saved successfully.';
        header("Location: index.php?page=$page");
        exit;
      }
    } else {
      $error = 'Invalid action.';
    }
  }
}

function countRows(PDO $pdo, string $table): int {
  return (int)$pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
}

function optionsFor(PDO $pdo, string $field): array {
  $queries = [
    'patient_id' => 'SELECT id,full_name AS label FROM patients ORDER BY full_name',
    'doctor_id' => 'SELECT id,full_name AS label FROM doctors ORDER BY full_name',
    'department_id' => 'SELECT id,name AS label FROM departments ORDER BY name'
  ];
  return isset($queries[$field]) ? $pdo->query($queries[$field])->fetchAll() : [];
}
?>
<!doctype html>
<html lang="<?=h($language)?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?=h(APP_NAME)?> | <?=h(t(ucfirst($page)))?></title>
  <link rel="icon" href="../frontend/assets/logo/jazeera.svg">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <link href="../frontend/assets/css/style.css" rel="stylesheet">
</head>
<body>
<aside class="sidebar" id="sidebar">
  <div class="brand">
    <img src="../frontend/assets/logo/jazeera.svg" alt="">
    <div>JAZEERA<br>HOSPITAL</div>
    <small><?=h(t('Your Health, Our Priority'))?></small>
  </div>
  <nav>
    <?php foreach ($navigation as $key => [$label, $icon]): ?>
      <?php if (can_access_page($key)): ?>
        <a class="<?= $page === $key ? 'active' : '' ?>" href="index.php?page=<?=h($key)?>">
          <i class="fa-solid <?=h($icon)?>"></i><?=h(t($label))?>
        </a>
      <?php endif; ?>
    <?php endforeach; ?>
    <form method="post" action="logout.php">
      <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
      <button class="btn btn-link text-white text-decoration-none"><i class="fa-solid fa-right-from-bracket"></i> <?=h(t('Logout'))?></button>
    </form>
  </nav>
  <div class="emergency"><i class="fa-solid fa-truck-medical"></i><b><?=h(t('Emergency Call'))?></b><strong>+252 61 123 4567</strong></div>
</aside>
<main class="main">
  <header class="topbar">
    <button class="btn btn-light" id="menu" aria-label="<?=h(t('Toggle navigation'))?>" aria-controls="sidebar" aria-expanded="true"><i class="fa-solid fa-bars"></i></button>
    <div class="search"><i class="fa fa-search"></i><input id="globalSearch" placeholder="<?=h(t('Search records...'))?>" aria-label="<?=h(t('Search records...'))?>"></div>
    <div class="language-switch"><a href="?page=<?=h($page)?>&amp;lang=so" lang="so">SO</a><a href="?page=<?=h($page)?>&amp;lang=en" lang="en">EN</a></div>
    <div class="ms-auto user">
      <span><?=h($_SESSION['user']['name'])?></span>
      <small><?=h(t($_SESSION['user']['role']))?></small>
      <form method="post" action="logout.php">
        <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
        <button class="btn btn-link p-0" title="<?=h(t('Logout'))?>" aria-label="<?=h(t('Logout'))?>"><i class="fa-solid fa-power-off"></i></button>
      </form>
    </div>
  </header>
  <section class="content">
    <?php if (!empty($_SESSION['flash'])): ?>
      <div class="alert alert-success" role="status"><?=h(t($_SESSION['flash']))?></div>
      <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger" role="alert"><?=h(t($error))?></div><?php endif; ?>

    <?php if (!$isKnownPage): ?>
      <div class="panel"><h3><?=h(t('Page not found'))?></h3><a href="index.php"><?=h(t('Return to dashboard'))?></a></div>
    <?php elseif (!$isAllowedPage): ?>
      <div class="panel"><h3><?=h(t('Access denied'))?></h3><p><?=h(t('Your role is not permitted to open this page.'))?></p></div>
    <?php elseif ($page === 'dashboard'): ?>
      <?php
        $demoViewer = $_SESSION['user']['role'] === 'Demo Viewer';
        $statsByRole = [
          'Super Admin' => ['Patients' => 'patients', 'Doctors' => 'doctors', 'Appointments' => 'appointments', 'Admissions' => 'admissions', 'Staff' => 'staff', 'Medicines' => 'medicines', 'Invoices' => 'invoices', 'Accounts' => 'users'],
          'Admin' => ['Patients' => 'patients', 'Doctors' => 'doctors', 'Appointments' => 'appointments', 'Admissions' => 'admissions', 'Staff' => 'staff', 'Medicines' => 'medicines', 'Invoices' => 'invoices'],
          'Doctor' => ['Patients' => 'patients', 'Appointments' => 'appointments', 'Admissions' => 'admissions', 'Lab Tests' => 'lab_tests'],
          'Nurse' => ['Patients' => 'patients', 'Appointments' => 'appointments', 'Admissions' => 'admissions'],
          'Receptionist' => ['Patients' => 'patients', 'Appointments' => 'appointments', 'Invoices' => 'invoices'],
          'Pharmacist' => ['Medicines' => 'medicines'],
          'Demo Viewer' => ['Sample patients' => 128, 'Sample doctors' => 12, 'Sample appointments' => 24, 'Sample medicines' => 76]
        ];
        $stats = $statsByRole[$_SESSION['user']['role']] ?? [];
      ?>
      <div class="hero"><div><h1><?=h(t('Welcome to Jazeera Hospital'))?></h1><p><?=h(t('Hospital Management System'))?></p><span><?=h(t('Your Health, Our Priority'))?></span></div><i class="fa-solid fa-hospital"></i></div>
      <div class="row g-3 mt-1">
        <?php foreach ($stats as $label => $table): ?>
          <div class="col-6 col-xl-4"><div class="stat"><div><small><?=h(t($label))?></small><h2><?=$demoViewer ? (int)$table : countRows($pdo, $table)?></h2></div><i class="fa-solid fa-chart-line"></i></div></div>
        <?php endforeach; ?>
      </div>
      <div class="row g-3 mt-2">
        <?php if (can_access_page('appointments')): ?>
          <div class="col-lg-7"><div class="panel">
            <h4><i class="fa-solid fa-calendar-days"></i> <?=h(t('Upcoming Appointments'))?></h4>
            <?php $rows = $pdo->query("SELECT a.appointment_at,a.status,p.full_name AS patient,d.full_name AS doctor FROM appointments a JOIN patients p ON p.id=a.patient_id JOIN doctors d ON d.id=a.doctor_id ORDER BY a.appointment_at ASC LIMIT 8")->fetchAll(); ?>
            <div class="table-responsive"><table class="table"><thead><tr><th><?=h(t('Patient'))?></th><th><?=h(t('Doctor'))?></th><th><?=h(t('Date & Time'))?></th><th><?=h(t('Status'))?></th></tr></thead><tbody>
              <?php foreach ($rows as $row): ?><tr><td><?=h($row['patient'])?></td><td><?=h($row['doctor'])?></td><td><?=h($row['appointment_at'])?></td><td><span class="pill"><?=h(t($row['status']))?></span></td></tr><?php endforeach; ?>
              <?php if (!$rows): ?><tr><td colspan="4" class="text-muted"><?=h(t('No appointments yet.'))?></td></tr><?php endif; ?>
            </tbody></table></div>
          </div></div>
        <?php endif; ?>
        <div class="col-lg-5"><div class="panel"><h4><i class="fa-solid fa-bolt"></i> <?=h(t('Quick Actions'))?></h4><div class="quick">
          <?php if ($demoViewer): ?><a href="index.php?page=demo"><i class="fa-solid fa-eye"></i><?=h(t('Open demo'))?></a><?php endif; ?>
          <?php foreach (['patients' => 'Register Patient', 'appointments' => 'Book Appointment', 'doctors' => 'Doctors', 'billing' => 'Create Invoice', 'pharmacy' => 'Pharmacy'] as $key => $label): ?>
            <?php if (can_access_page($key)): ?><a href="index.php?page=<?=h($key)?>"><i class="fa fa-arrow-right"></i><?=h(t($label))?></a><?php endif; ?>
          <?php endforeach; ?>
        </div></div></div>
      </div>

    <?php elseif ($page === 'demo'): ?>
      <h2><?=h(t('Demo'))?></h2>
      <p class="text-muted"><?=h(t('Practice and explore the hospital system using fictional sample data.'))?></p>
      <div class="alert alert-info" role="note"><?=h(t('Sample data only — no real patient information is shown.'))?></div>
      <div class="panel"><h4><?=h(t('Sample appointments'))?></h4>
        <div class="table-responsive"><table class="table">
          <thead><tr><th><?=h(t('Patient'))?></th><th><?=h(t('Doctor'))?></th><th><?=h(t('Date & Time'))?></th><th><?=h(t('Status'))?></th></tr></thead>
          <tbody>
            <tr><td><?=h(t('Sample Patient 01'))?></td><td><?=h(t('Dr. Sample A'))?></td><td><?=h(t('Sample — 09:00'))?></td><td><?=h(t('Confirmed'))?></td></tr>
            <tr><td><?=h(t('Sample Patient 02'))?></td><td><?=h(t('Dr. Sample B'))?></td><td><?=h(t('Sample — 11:30'))?></td><td><?=h(t('Pending'))?></td></tr>
            <tr><td><?=h(t('Sample Patient 03'))?></td><td><?=h(t('Dr. Sample A'))?></td><td><?=h(t('Sample — 14:00'))?></td><td><?=h(t('Completed'))?></td></tr>
          </tbody>
        </table></div>
        <p class="text-muted mb-0"><?=h(t('No real patient data is used on this page.'))?></p>
      </div>

    <?php elseif (isset($modules[$page])): ?>
      <?php
        [$title, $table, $listFieldText, $formFieldText] = $modules[$page];
        $listFields = explode(',', $listFieldText);
        $formFields = explode(',', $formFieldText);
        if ($page === 'patients' && !$clinicalPatientAccess) {
          $listFields = ['full_name', 'gender', 'phone'];
          $formFields = ['full_name', 'gender', 'dob', 'phone', 'address'];
        }
        $selectFields = array_values(array_unique(array_merge(['id'], $listFields, $formFields)));
        $selectedColumns = '`' . implode('`,`', $selectFields) . '`';
        $rows = $pdo->query("SELECT $selectedColumns FROM `$table` ORDER BY id DESC LIMIT 300")->fetchAll();
      ?>
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div><h2><?=h(t($title))?></h2><p class="text-muted"><?=h(t('Manage records'))?></p></div>
        <?php if (can_manage($page)): ?>
          <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#recordModal"><i class="fa fa-plus"></i> <?=h(t('Add New'))?></button>
        <?php endif; ?>
      </div>
      <div class="panel"><div class="table-responsive"><table class="table table-hover" id="records">
        <thead><tr><?php foreach ($listFields as $field): ?><th><?=h(t(ucwords(str_replace('_', ' ', $field))))?></th><?php endforeach; ?><th><?=h(t('Actions'))?></th></tr></thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <tr>
              <?php foreach ($listFields as $field): ?>
                <?php
                  $value = $row[$field] ?? '';
                  if (in_array($field, ['patient_id', 'doctor_id', 'department_id'], true)) {
                    $reference = $field === 'patient_id' ? 'patients' : ($field === 'doctor_id' ? 'doctors' : 'departments');
                    $labelField = $field === 'department_id' ? 'name' : 'full_name';
                    $lookup = $pdo->prepare("SELECT `$labelField` FROM `$reference` WHERE id=?");
                    $lookup->execute([(int)$value]);
                    $value = $lookup->fetchColumn() ?: $value;
                  }
                  if ($field === 'is_active') $value = (int)$value === 1 ? 'Active' : 'Deactivated';
                ?>
                <td><?=h(t((string)$value))?></td>
              <?php endforeach; ?>
              <td>
                <?php if (can_manage($page) && !($page === 'users' && $row['role'] === 'Super Admin' && (int)$row['id'] !== (int)$_SESSION['user']['id'])): ?>
                  <button class="btn btn-sm btn-outline-primary edit" data-self-account="<?= $page === 'users' && (int)$row['id'] === (int)$_SESSION['user']['id'] ? '1' : '0' ?>" data-row="<?=h(json_encode($row, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG))?>" aria-label="<?=h(t('Edit record'))?>"><i class="fa fa-pen"></i></button>
                  <?php if (!($page === 'users' && (int)$row['id'] === (int)$_SESSION['user']['id'])): ?>
                  <form method="post" class="d-inline" onsubmit="return confirm('<?=h(t('Delete this record?'))?>')">
                    <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger" aria-label="<?=h(t('Delete record'))?>"><i class="fa fa-trash"></i></button>
                  </form>
                  <?php endif; ?>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$rows): ?><tr><td colspan="<?=count($listFields) + 1?>" class="text-center text-muted py-4"><?=h(t('No records found.'))?></td></tr><?php endif; ?>
        </tbody>
      </table></div></div>

      <?php if (can_manage($page)): ?>
        <div class="modal fade" id="recordModal" data-record-title="<?=h($title)?>" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-lg"><form method="post" class="modal-content" id="recordForm">
            <div class="modal-header"><h5 class="modal-title"><?=h(t('Add New'))?> <?=h(t($title))?></h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?=h(t('Cancel'))?>"></button></div>
            <div class="modal-body">
              <input type="hidden" name="csrf" value="<?=h(csrf_token())?>">
              <input type="hidden" name="action" value="save">
              <input type="hidden" name="id" id="recordId">
              <?php foreach ($formFields as $field): ?>
                <div class="mb-3">
                  <label class="form-label" for="field-<?=h($field)?>"><?=h(t(ucwords(str_replace('_', ' ', $field))))?></label>
                  <?php if (in_array($field, ['patient_id', 'doctor_id', 'department_id'], true)): ?>
                    <?php $options = optionsFor($pdo, $field); ?>
                    <select class="form-select" id="field-<?=h($field)?>" name="<?=h($field)?>" required><option value=""><?=h(t('Select...'))?></option>
                      <?php foreach ($options as $option): ?><option value="<?=(int)$option['id']?>"><?=h($option['label'])?></option><?php endforeach; ?>
                    </select>
                  <?php elseif ($field === 'role'): ?>
                    <select class="form-select" id="field-role" name="role" required>
                      <option value="Super Admin" disabled><?=h(t('Super Admin'))?></option>
                      <?php foreach (['Admin', 'Doctor', 'Nurse', 'Receptionist', 'Pharmacist'] as $option): ?><option value="<?=h($option)?>"><?=h(t($option))?></option><?php endforeach; ?>
                    </select>
                  <?php elseif ($field === 'is_active'): ?>
                    <select class="form-select" id="field-is_active" name="is_active" required><option value="1"><?=h(t('Active'))?></option><option value="0"><?=h(t('Deactivated'))?></option></select>
                  <?php elseif (in_array($field, ['gender', 'status', 'payment_method'], true)): ?>
                    <?php
                      $options = $field === 'gender' ? ['Male', 'Female', 'Other']
                        : ($field === 'payment_method' ? ['Cash', 'Mobile Money', 'Card']
                        : ($page === 'appointments' ? ['Pending', 'Confirmed', 'Completed', 'Cancelled']
                        : ($page === 'admissions' ? ['Admitted', 'Discharged']
                        : ($page === 'laboratory' ? ['Requested', 'Processing', 'Completed'] : ['Unpaid', 'Partial', 'Paid']))));
                    ?>
                    <select class="form-select" id="field-<?=h($field)?>" name="<?=h($field)?>"><option value=""><?=h(t('Select...'))?></option><?php foreach ($options as $option): ?><option value="<?=h($option)?>"><?=h(t($option))?></option><?php endforeach; ?></select>
                  <?php elseif (in_array($field, ['dob', 'expiry_date', 'admitted_on', 'discharged_on', 'test_date', 'appointment_at'], true)): ?>
                    <input class="form-control" id="field-<?=h($field)?>" type="<?=$field === 'appointment_at' ? 'datetime-local' : 'date'?>" name="<?=h($field)?>" <?=in_array($field, ['admitted_on', 'test_date', 'appointment_at'], true) ? 'required' : ''?>>
                  <?php elseif (in_array($field, ['medical_history', 'result', 'description'], true)): ?>
                    <textarea class="form-control" id="field-<?=h($field)?>" name="<?=h($field)?>" rows="3"></textarea>
                  <?php else: ?>
                    <input class="form-control" id="field-<?=h($field)?>" name="<?=h($field)?>" type="<?=in_array($field, ['amount', 'paid_amount', 'unit_price', 'salary', 'quantity'], true) ? 'number' : ($field === 'email' ? 'email' : 'text')?>" <?=in_array($field, ['full_name', 'name', 'test_name', 'amount', 'email'], true) ? 'required' : ''?> <?=in_array($field, ['amount', 'paid_amount', 'unit_price', 'salary', 'quantity'], true) ? 'step="0.01"' : ''?>>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
              <?php if ($page === 'users'): ?>
                <div class="mb-3"><label class="form-label" for="field-password"><?=h(t('Password'))?></label><input class="form-control" id="field-password" name="password" type="password" minlength="12" autocomplete="new-password"><small class="text-muted"><?=h(t('At least 12 characters; leave blank when keeping the current password.'))?></small></div>
                <div class="mb-3"><label class="form-label" for="field-password_confirmation"><?=h(t('Confirm password'))?></label><input class="form-control" id="field-password_confirmation" name="password_confirmation" type="password" minlength="12" autocomplete="new-password"></div>
              <?php endif; ?>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal"><?=h(t('Cancel'))?></button><button class="btn btn-primary"><?=h(t('Save'))?></button></div>
          </form></div>
        </div>
      <?php endif; ?>

    <?php else: ?>
      <?php
        $reportTables = ['patients' => 'Patients', 'doctors' => 'Doctors', 'appointments' => 'Appointments', 'admissions' => 'Admissions', 'invoices' => 'Invoices', 'medicines' => 'Medicines'];
      ?>
      <h2><?=h(t('Reports'))?></h2>
      <div class="panel"><p><?=h(t('Summary of records available to your role.'))?></p><ul>
        <?php foreach ($reportTables as $table => $label): ?>
          <?php if (can_access_page($table)): ?><li><?=h(t($label))?>: <?=countRows($pdo, $table)?></li><?php endif; ?>
        <?php endforeach; ?>
      </ul><button class="btn btn-primary" onclick="window.print()"><?=h(t('Print Report'))?></button></div>
    <?php endif; ?>
  </section>
  <footer>© <?=date('Y')?> Jazeera Hospital. <?=h(t('All rights reserved.'))?> <span><?=h(t('Your Health, Our Priority'))?></span></footer>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../frontend/assets/js/app.js"></script>
</body>
</html>
