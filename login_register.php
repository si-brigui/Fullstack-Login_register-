<?php
/* ====== DEBUG SWITCH ====== */
define('DEBUG', true);     // set to false after it works
define('HARD_DIAG', false);

if (DEBUG) {
  ini_set('display_errors', 1);
  ini_set('display_startup_errors', 1);
  mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
  error_reporting(E_ALL);
}

session_start();
require_once __DIR__ . '/config.php';

/* --- tiny logger --- */
function log_line($s) {
  file_put_contents(__DIR__ . '/app.log', "[".date('Y-m-d H:i:s')."] $s\n", FILE_APPEND);
}
log_line("hit login_register.php with method=" . ($_SERVER['REQUEST_METHOD'] ?? '?'));

/* helpers */
function back_with($key, $msg, $form) {
  $_SESSION[$key] = $msg;
  $_SESSION['active_form'] = $form;
  header('Location: index.php');
  exit();
}

/* ===== REGISTER ===== */
$is_register = ($_SERVER['REQUEST_METHOD'] === 'POST') &&
               (isset($_POST['register']) || isset($_POST['name'], $_POST['email'], $_POST['password'], $_POST['role']));
if ($is_register) {
  $name  = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $pass  = $_POST['password'] ?? '';
  $role  = $_POST['role'] ?? '';

  log_line("REGISTER attempt email={$email}, role={$role}");

  if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 3 || !in_array($role, ['user','admin'], true)) {
    back_with('register_error', 'Please fill all fields (valid email, password ≥ 3, role).', 'register');
  }

  // Ensure table exists (no IF NOT EXISTS on column for old MariaDB)
  /*$conn->query("
    CREATE TABLE IF NOT EXISTS users (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(100) NOT NULL,
      email VARCHAR(190) NOT NULL UNIQUE,
      password VARCHAR(255) NOT NULL,
      role ENUM('user','admin') NOT NULL DEFAULT 'user',
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  ");*/

  // email unique
  $stmt = $conn->prepare("SELECT 1 FROM users WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $stmt->store_result();
  if ($stmt->num_rows > 0) {
    $stmt->close();
    back_with('register_error', 'Email is already registered!', 'register');
  }
  $stmt->close();

  $hash = password_hash($pass, PASSWORD_DEFAULT);

  $stmt = $conn->prepare("INSERT INTO users (name,email,password,role,created_at) VALUES (?,?,?,?,NOW())");
  $stmt->bind_param("ssss", $name, $email, $hash, $role);
  $stmt->execute();
  $stmt->close();

  // session
  $_SESSION['name']  = $name;
  $_SESSION['email'] = $email;

  log_line("REGISTER success email={$email}, role={$role}");
  header('Location: ' . ($role === 'admin' ? 'admin_page.php' : 'user_page.php'));
  exit();
}

/* ===== LOGIN ===== */
$is_login = ($_SERVER['REQUEST_METHOD'] === 'POST') &&
            (isset($_POST['login']) || isset($_POST['email'], $_POST['password']));
if ($is_login) {
  $email = trim($_POST['email'] ?? '');
  $pass  = $_POST['password'] ?? '';

  log_line("LOGIN attempt email={$email}");

  if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $pass === '') {
    back_with('login_error', 'Invalid email or password.', 'login');
  }

  $stmt = $conn->prepare("SELECT name,email,password,role FROM users WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $res = $stmt->get_result();

  if ($res && $res->num_rows) {
    $u = $res->fetch_assoc();
    if (password_verify($pass, $u['password'])) {
      $_SESSION['name']  = $u['name'];
      $_SESSION['email'] = $u['email'];

      log_line("LOGIN success email={$email}, role={$u['role']}");
      $stmt->close();
      header('Location: ' . ($u['role'] === 'admin' ? 'admin_page.php' : 'user_page.php'));
      exit();
    } else {
      log_line("LOGIN failed (bad password) email={$email}");
    }
  } else {
    log_line("LOGIN failed (no user) email={$email}");
  }
  $stmt->close();

  back_with('login_error', 'Incorrect email or password', 'login');
}

/* default */
log_line("fallthrough redirect to index.php");
header('Location: index.php');
exit();
