<?php
require_once '../db.php';

// If already logged in, redirect to appropriate index
if (isAuthenticated()) {
    if (isAdmin()) {
        header("Location: ../admin/index.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] === 'suspended') {
                $error = 'Your account has been suspended by an administrator.';
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_email'] = $user['email'];

                if ($user['role'] === 'admin') {
                    header("Location: ../admin/index.php");
                } else {
                    header("Location: index.php");
                }
                exit;
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Sign in to your Portalia account">
  <title>Login - Portalia</title>
  <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/vendors/bootstrap-icons/bootstrap-icons.css">
  <link rel="stylesheet" href="../assets/css/portalia.css">
  <style>
    .auth-page-wrapper {
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      background: linear-gradient(135deg, #F8FAFC 0%, #EFF6FF 50%, #F5F3FF 100%);
      padding: 24px;
    }
    .auth-card-portalia {
      width: 100%;
      max-width: 440px;
      background: var(--portalia-surface);
      border-radius: var(--portalia-radius-lg);
      padding: 32px;
      box-shadow: var(--portalia-shadow-elevated);
    }
    .auth-title {
      font-size: 26px;
      font-weight: 700;
      margin-bottom: 8px;
    }
    .auth-subtitle {
      font-size: 14px;
      color: var(--portalia-text-secondary);
      margin-bottom: 32px;
    }
  </style>
</head>
<body>

  <div class="auth-page-wrapper">
    <div class="auth-card-portalia">
      <div class="text-center mb-4">
        <a href="welcome.php" class="text-decoration-none d-inline-flex align-items-center gap-2">
          <span style="font-size: 24px; font-weight: 800; background: var(--portalia-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Portalia</span>
        </a>
      </div>

      <h2 class="auth-title">Welcome back!</h2>
      <p class="auth-subtitle">Log in to buy and sell campus products.</p>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger" style="border-radius: var(--portalia-radius-sm); font-size: 13px;">
          <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
        </div>
      <?php endif; ?>

      <form action="login.php" method="POST">
        <div class="form-group-portalia">
          <label for="email">Campus Email</label>
          <input type="email" name="email" id="email" class="input-portalia input-glow" placeholder="e.g. budi@student.ac.id" required value="<?php echo isset($_POST['email']) ? sanitize($_POST['email']) : ''; ?>">
        </div>

        <div class="form-group-portalia">
          <label for="password">Password</label>
          <input type="password" name="password" id="password" class="input-portalia input-glow" placeholder="Enter your password" required>
        </div>

        <button type="submit" class="btn btn-portalia-primary w-100 mt-2">Log In</button>
      </form>

      <div class="text-center mt-4">
        <p class="mb-0" style="font-size: 13px; color: var(--portalia-text-secondary);">
          Don't have an account? <a href="register.php" style="color: var(--portalia-secondary); font-weight: 600; text-decoration: none;">Register here</a>
        </p>
        <p class="mt-2 mb-0" style="font-size: 12px;">
          <a href="welcome.php" class="text-muted text-decoration-none"><i class="bi bi-arrow-left"></i> Back to start</a>
        </p>
      </div>
    </div>
  </div>

</body>
</html>
