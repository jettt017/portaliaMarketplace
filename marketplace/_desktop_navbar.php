<?php
/**
 * Desktop Navbar Component for Portalia
 * Include this file inside <div class="app-container"> on each page.
 * 
 * Expects: db.php already included (session started, helpers available).
 * Uses existing session variables — no new backend logic.
 */

// Determine current page for active state
$_nav_current_page = basename($_SERVER['PHP_SELF']);

// Determine user state from existing session
$_nav_is_guest = !isset($_SESSION['user_id']) || $_SESSION['user_id'] === 'guest';
$_nav_username = 'Student';
$_nav_avatar = '../assets/images/avatar/avatar.jpg';

if (!$_nav_is_guest) {
    // Try to use already-fetched profile variables if available
    if (isset($user_profile) && is_array($user_profile)) {
        $_nav_username = $user_profile['username'] ?? $_SESSION['user_username'] ?? 'Student';
        $_nav_avatar = !empty($user_profile['avatar']) ? '../' . $user_profile['avatar'] : $_nav_avatar;
    } elseif (isset($user) && is_array($user)) {
        $_nav_username = $user['username'] ?? $_SESSION['user_username'] ?? 'Student';
        $_nav_avatar = !empty($user['avatar']) ? '../' . $user['avatar'] : $_nav_avatar;
    } elseif (isset($_SESSION['user_username'])) {
        $_nav_username = $_SESSION['user_username'];
    }
}
?>
<header class="desktop-navbar" id="desktopNavbar">
  <div class="desktop-navbar-inner">
    <a class="desktop-brand" href="index.php">
      <i class="bi bi-shop" style="margin-right: 6px;"></i>Portalia
    </a>

    <nav class="desktop-nav-links">
      <a href="index.php" class="<?php echo $_nav_current_page === 'index.php' ? 'active' : ''; ?>">
        <i class="bi bi-house-door<?php echo $_nav_current_page === 'index.php' ? '-fill' : ''; ?>"></i> Home
      </a>
      <a href="wishlist.php" class="<?php echo $_nav_current_page === 'wishlist.php' ? 'active' : ''; ?>">
        <i class="bi bi-heart<?php echo $_nav_current_page === 'wishlist.php' ? '-fill' : ''; ?>"></i> Wishlist
      </a>
      <a href="upload.php" class="<?php echo $_nav_current_page === 'upload.php' ? 'active' : ''; ?>">
        <i class="bi bi-plus-circle<?php echo $_nav_current_page === 'upload.php' ? '-fill' : ''; ?>"></i> Upload
      </a>
      <a href="chat.php" class="<?php echo $_nav_current_page === 'chat.php' ? 'active' : ''; ?>">
        <i class="bi bi-chat-dots<?php echo $_nav_current_page === 'chat.php' ? '-fill' : ''; ?>"></i> Chat
      </a>
      <a href="profile.php" class="<?php echo $_nav_current_page === 'profile.php' ? 'active' : ''; ?>">
        <i class="bi bi-person<?php echo $_nav_current_page === 'profile.php' ? '-fill' : ''; ?>"></i> Profile
      </a>
    </nav>

    <div class="desktop-nav-action">
      <?php if ($_nav_is_guest): ?>
        <a href="login.php" class="btn btn-portalia-primary" style="height: 40px; padding: 0 24px !important; font-size: 13px;">
          <i class="bi bi-box-arrow-in-right me-2"></i>Log In
        </a>
      <?php else: ?>
        <a href="profile.php" class="desktop-user-profile">
          <img src="<?php echo htmlspecialchars($_nav_avatar); ?>" alt="Profile" class="desktop-user-avatar" onerror="this.src='../assets/images/avatar/avatar.jpg'">
          <span class="desktop-user-name"><?php echo htmlspecialchars($_nav_username); ?></span>
          <i class="bi bi-chevron-down" style="font-size: 10px; color: var(--portalia-text-secondary);"></i>
        </a>
      <?php endif; ?>
    </div>
  </div>
</header>
