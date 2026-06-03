<?php
require_once '../db.php';
// If already logged in, redirect to homepage
if (isAuthenticated()) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Welcome to Portalia - Smart Campus Marketplace for students">
  <title>Welcome to Portalia</title>
  <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/vendors/bootstrap-icons/bootstrap-icons.css">
  <link rel="stylesheet" href="../assets/css/portalia.css">
  <style>
    .welcome-screen {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 32px 24px;
      text-align: center;
      background: linear-gradient(135deg, #F8FAFC 0%, #EFF6FF 50%, #F5F3FF 100%);
    }
    .welcome-illustration {
      position: relative;
      width: 200px;
      height: 200px;
      margin-bottom: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .ill-circle-1 {
      position: absolute;
      width: 140px;
      height: 140px;
      border-radius: 50%;
      background: linear-gradient(135deg, rgba(79, 140, 255, 0.2), rgba(123, 97, 255, 0.2));
      animation: float 4s ease-in-out infinite;
    }
    .ill-circle-2 {
      position: absolute;
      width: 80px;
      height: 80px;
      border-radius: 50%;
      background: linear-gradient(135deg, rgba(123, 97, 255, 0.4), rgba(79, 140, 255, 0.4));
      top: 30px;
      right: 20px;
      animation: float 6s ease-in-out infinite reverse;
    }
    .ill-box {
      position: absolute;
      width: 90px;
      height: 90px;
      background: #FFFFFF;
      border-radius: 20px;
      box-shadow: 0 12px 32px rgba(79, 140, 255, 0.15);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 42px;
      color: #4F8CFF;
      z-index: 5;
      transform: rotate(-10deg);
      animation: float-box 5s ease-in-out infinite;
    }
    @keyframes float {
      0%, 100% { transform: translateY(0px) scale(1); }
      50% { transform: translateY(-10px) scale(1.05); }
    }
    @keyframes float-box {
      0%, 100% { transform: translateY(0px) rotate(-10deg); }
      50% { transform: translateY(-15px) rotate(5deg); }
    }
    .welcome-logo {
      font-size: 32px;
      font-weight: 800;
      background: var(--portalia-gradient);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      margin-bottom: 8px;
      letter-spacing: -0.5px;
    }
    .welcome-tagline {
      font-size: 14px;
      color: var(--portalia-text-secondary);
      margin-bottom: 48px;
      max-width: 320px;
      line-height: 1.6;
    }
    .action-buttons {
      width: 100%;
      max-width: 320px;
      display: flex;
      flex-direction: column;
      gap: 16px;
    }
  </style>
</head>
<body>

  <div class="welcome-screen">
    <div class="welcome-illustration">
      <div class="ill-circle-1"></div>
      <div class="ill-circle-2"></div>
      <div class="ill-box">
        <i class="bi bi-cart-dash-fill"></i>
      </div>
    </div>

    <h1 class="welcome-logo">Portalia</h1>
    <p class="welcome-tagline">Smart campus marketplace to buy, sell, and share products with fellow university students.</p>

    <div class="action-buttons">
      <a href="login.php" class="btn btn-portalia-primary w-100">Log In</a>
      <a href="register.php" class="btn btn-portalia-secondary w-100">Sign Up / Register</a>
      <a href="index.php?guest=1" class="text-decoration-none mt-2 fw-semibold" style="color: var(--portalia-secondary); font-size: 13px;">Browse as Guest</a>
    </div>
  </div>

</body>
</html>
