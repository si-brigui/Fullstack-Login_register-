<?php
session_start();
$active = $_SESSION['active_form'] ?? 'login'; // set by login_register.php on error
$login_error = $_SESSION['login_error'] ?? '';
$register_error = $_SESSION['register_error'] ?? '';
// clear flash
unset($_SESSION['active_form'], $_SESSION['login_error'], $_SESSION['register_error']);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width , initial-scale =1.0" />
    <title>Full-stack Login & register Form with User & Admin Page</title>
    <link rel="stylesheet" href="style.css" />
  </head>
  <body>
    <div class="container">
      <div class="form-box <?= $active === 'login' ? 'active' : '' ?>" id="login-form">
        <form action="login_register.php" method="POST">
          <h2>Login</h2>

          <?php if ($login_error): ?>
            <p style="color:#c00; text-align:center; margin-bottom:10px;">
              <?= htmlspecialchars($login_error) ?>
            </p>
          <?php endif; ?>

          <input type="email" name="email" placeholder="Email" required />
          <input type="password" name="password" placeholder="Password" required />
          <button type="submit" name="login">Login</button>
          <p>Dont't have an account?</p>
          <a href="#" onclick="showForm('register-form'); return false;">Register</a>
        </form>
      </div>

      <div class="form-box <?= $active === 'register' ? 'active' : '' ?>" id="register-form">
        <form action="login_register.php" method="POST">
          <h2>Register</h2>

          <?php if ($register_error): ?>
            <p style="color:#c00; text-align:center; margin-bottom:10px;">
              <?= htmlspecialchars($register_error) ?>
            </p>
          <?php endif; ?>

          <input type="text" name="name" placeholder="name" required />
          <input type="email" name="email" placeholder="Email" required />
          <input type="password" name="password" placeholder="Password" required />
          <select name="role" required>
            <option value="">--Select Role--</option>
            <option value="user">User</option>
            <option value="admin">Admin</option>
          </select>
          <button type="submit" name="register">Register</button>
          <p>Already have an account?</p>
          <a href="#" onclick="showForm('login-form'); return false;">Login</a>
        </form>
      </div>
    </div>

    <script src="script.js"></script>
    <script>
      // ensure correct tab on initial load (in case JS runs before PHP classes apply)
      <?php if ($active === 'register'): ?>
        showForm('register-form');
      <?php else: ?>
        showForm('login-form');
      <?php endif; ?>
    </script>
  </body>
</html>
