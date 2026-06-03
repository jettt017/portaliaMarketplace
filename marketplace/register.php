<?php
require_once '../db.php';

// If already logged in, redirect to index
if (isAuthenticated()) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $nim = trim($_POST['nim']);
    $phone = trim($_POST['phone']);

    if (empty($username) || empty($email) || empty($password) || empty($nim) || empty($phone)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $db = getDB();
        // Check if email already registered
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email is already registered. Please use another email or log in.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $avatar = 'assets/images/avatar/avatar.jpg'; // default avatar
            
            try {
                $stmt = $db->prepare("INSERT INTO users (username, email, password, role, nim, phone, avatar, status) VALUES (?, ?, ?, 'student', ?, ?, ?, 'active')");
                $stmt->execute([$username, $email, $hashedPassword, $nim, $phone, $avatar]);
                
                // Get newly inserted user's ID
                $newUserId = $db->lastInsertId();

                // Log the user in immediately
                $_SESSION['user_id'] = $newUserId;
                $_SESSION['user_username'] = $username;
                $_SESSION['user_role'] = 'student';
                $_SESSION['user_email'] = $email;

                header("Location: index.php?registered=1");
                exit;
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Register a new Portalia account">
  <title>Register - Portalia</title>
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
      max-width: 480px;
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
      margin-bottom: 24px;
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

      <h2 class="auth-title">Create Account</h2>
      <p class="auth-subtitle">Join Portalia today and start trading with classmates.</p>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger" style="border-radius: var(--portalia-radius-sm); font-size: 13px;">
          <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
        </div>
      <?php endif; ?>

      <form action="register.php" method="POST">
        <div class="form-group-portalia">
          <label for="username">Full Name</label>
          <input type="text" name="username" id="username" class="input-portalia input-glow" placeholder="e.g. Budi Santoso" required value="<?php echo isset($_POST['username']) ? sanitize($_POST['username']) : ''; ?>">
        </div>

        <div class="row">
          <div class="col-md-6">
            <div class="form-group-portalia">
              <label for="nim">Student ID (NIM)</label>
              <input type="text" name="nim" id="nim" class="input-portalia input-glow" placeholder="e.g. 2201010041" required value="<?php echo isset($_POST['nim']) ? sanitize($_POST['nim']) : ''; ?>">
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group-portalia">
              <label for="phone">Phone Number</label>
              <input type="tel" name="phone" id="phone" class="input-portalia input-glow" placeholder="e.g. 0812XXXXXXXX" required value="<?php echo isset($_POST['phone']) ? sanitize($_POST['phone']) : ''; ?>">
            </div>
          </div>
        </div>

        <div class="form-group-portalia">
          <label for="email">Campus Email</label>
          <input type="email" name="email" id="email" class="input-portalia input-glow" placeholder="e.g. budi@student.ac.id" required value="<?php echo isset($_POST['email']) ? sanitize($_POST['email']) : ''; ?>">
        </div>

        <div class="form-group-portalia">
          <label for="password">Password</label>
          <input type="password" name="password" id="password" class="input-portalia input-glow" placeholder="Create a strong password" required>
        </div>

        <button type="submit" class="btn btn-portalia-primary w-100 mt-2">Sign Up & Log In</button>
      </form>

      <div class="text-center mt-4">
        <p class="mb-0" style="font-size: 13px; color: var(--portalia-text-secondary);">
          Already have an account? <a href="login.php" style="color: var(--portalia-secondary); font-weight: 600; text-decoration: none;">Log in here</a>
        </p>
        <p class="mt-2 mb-0" style="font-size: 12px;">
          <a href="welcome.php" class="text-muted text-decoration-none"><i class="bi bi-arrow-left"></i> Back to start</a>
        </p>
      </div>
    </div>
  </div>

</body>
</html>
