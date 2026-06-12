<?php
require_once '../db.php';

if (!isAuthenticated()) {
    header("Location: welcome.php");
    exit;
}

$current_user_id = $_SESSION['user_id'];
if ($current_user_id === 'guest') {
    header("Location: login.php");
    exit;
}

$db = getDB();
$error = '';
$success = '';

// Handle Profile Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = trim($_POST['username']);
    $nim = trim($_POST['nim']);
    $phone = trim($_POST['phone']);

    if (empty($username) || empty($nim) || empty($phone)) {
        $error = 'All profile fields are required.';
    } else {
        try {
            $stmt = $db->prepare("UPDATE users SET username = ?, nim = ?, phone = ? WHERE id = ?");
            $stmt->execute([$username, $nim, $phone, $current_user_id]);
            $_SESSION['user_username'] = $username;
            $success = 'Profile updated successfully!';
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Handle Product Deletion
if (isset($_GET['delete_id'])) {
    $deleteId = intval($_GET['delete_id']);
    
    // Ensure product belongs to logged-in user
    $stmt = $db->prepare("SELECT id FROM products WHERE id = ? AND seller_id = ?");
    $stmt->execute([$deleteId, $current_user_id]);
    if ($stmt->fetch()) {
        try {
            $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$deleteId]);
            $success = 'Listing deleted successfully!';
        } catch (PDOException $e) {
            $error = 'Failed to delete listing: ' . $e->getMessage();
        }
    } else {
        $error = 'Unauthorized deletion request.';
    }
}

$user = getCurrentUser();

// Count stats
// 1. Active Listings
$stmt = $db->prepare("SELECT COUNT(*) FROM products WHERE seller_id = ? AND status = 'active'");
$stmt->execute([$current_user_id]);
$active_count = $stmt->fetchColumn();

// 2. Total Sold Listings
$stmt = $db->prepare("SELECT COUNT(*) FROM transactions WHERE seller_id = ?");
$stmt->execute([$current_user_id]);
$sold_count = $stmt->fetchColumn();

// 3. Wishlisted items
$stmt = $db->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
$stmt->execute([$current_user_id]);
$wishlist_count = $stmt->fetchColumn();

// Determine active tab
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'active';
$allowed_tabs = ['active', 'pending', 'rejected', 'expired'];
if (!in_array($active_tab, $allowed_tabs)) {
    $active_tab = 'active';
}

// Fetch user's listings for the active tab
$stmt = $db->prepare("SELECT p.*, c.name as category_name 
                      FROM products p 
                      JOIN categories c ON p.category_id = c.id
                      WHERE p.seller_id = ? AND p.status = ? 
                      ORDER BY p.created_at DESC");
$stmt->execute([$current_user_id, $active_tab]);
$my_products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="View your Portalia profile, statistics, and listings">
  <title>My Profile - Portalia</title>
  <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/vendors/bootstrap-icons/bootstrap-icons.css">
  <link rel="stylesheet" href="../assets/css/portalia.css">
  <style>
    .profile-nav-header {
      position: sticky;
      top: 0;
      z-index: 100;
      background: var(--portalia-surface);
      padding: 16px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid var(--portalia-bg);
    }
    .profile-hero-section {
      background: linear-gradient(135deg, #F8FAFC 0%, #EFF6FF 100%);
      padding: 24px 16px;
      text-align: center;
      border-bottom: 1px solid var(--portalia-bg);
      position: relative;
    }
    .profile-avatar-lg {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      object-fit: cover;
      border: 4px solid #FFFFFF;
      box-shadow: var(--portalia-shadow-soft);
      margin-bottom: 12px;
    }
    .profile-username {
      font-size: 18px;
      font-weight: 700;
      margin-bottom: 4px;
    }
    .profile-meta-text {
      font-size: 12px;
      color: var(--portalia-text-secondary);
      margin-bottom: 16px;
    }
    .stats-row {
      display: flex;
      justify-content: space-around;
      background: var(--portalia-surface);
      border-radius: var(--portalia-radius-md);
      padding: 14px 10px;
      box-shadow: var(--portalia-shadow-soft);
      margin: -20px 16px 20px;
      position: relative;
      z-index: 10;
    }
    .stat-item {
      text-align: center;
      flex: 1;
    }
    .stat-item:not(:last-child) {
      border-right: 1px solid #E2E8F0;
    }
    .stat-val {
      font-size: 16px;
      font-weight: 700;
      color: var(--portalia-primary);
      display: block;
    }
    .stat-lbl {
      font-size: 10px;
      color: var(--portalia-text-secondary);
      text-transform: uppercase;
      font-weight: 600;
    }
    .profile-tabs {
      display: flex;
      border-bottom: 1px solid #E2E8F0;
      padding: 0 16px;
      background: var(--portalia-surface);
      overflow-x: auto;
      scrollbar-width: none;
    }
    .profile-tabs::-webkit-scrollbar {
      display: none;
    }
    .profile-tab-btn {
      padding: 12px 16px;
      font-size: 12px;
      font-weight: 600;
      color: var(--portalia-text-secondary);
      text-decoration: none;
      border-bottom: 2px solid transparent;
      white-space: nowrap;
      transition: var(--portalia-transition);
    }
    .profile-tab-btn.active {
      color: var(--portalia-primary);
      border-bottom-color: var(--portalia-primary);
    }
    .my-product-item {
      display: flex;
      align-items: center;
      gap: 16px;
      padding: 12px;
      border-radius: var(--portalia-radius-sm);
      background: var(--portalia-surface);
      box-shadow: var(--portalia-shadow-soft);
      margin-bottom: 12px;
    }
    .my-product-thumb {
      width: 64px;
      height: 64px;
      border-radius: 8px;
      object-fit: cover;
      flex-shrink: 0;
    }
    .my-product-placeholder-thumb {
      width: 64px;
      height: 64px;
      border-radius: 8px;
      background: var(--portalia-bg);
      color: var(--portalia-primary);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      flex-shrink: 0;
    }
    .my-product-info {
      flex-grow: 1;
      min-width: 0;
    }
    .my-product-title {
      font-size: 13px;
      font-weight: 600;
      margin-bottom: 4px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .my-product-price {
      font-size: 14px;
      font-weight: 700;
      color: var(--portalia-primary);
    }
  </style>
</head>
<body>

  <div class="app-container">

    <!-- DESKTOP NAVBAR (visible on ≥992px) -->
    <?php include '_desktop_navbar.php'; ?>

    <main class="feature-page">
      <section class="page-header-card">
        <div class="page-header-main">
          <span class="page-header-icon"><i class="bi bi-person-fill"></i></span>
          <div>
            <span class="page-header-eyebrow">Student account</span>
            <h1 class="page-header-title">My Profile</h1>
            <p class="page-header-subtitle">Manage your listings, wishlist, and student account.</p>
          </div>
        </div>
        <div class="page-header-actions">
          <a href="logout.php" class="compact-icon-link" title="Sign Out" aria-label="Sign out"><i class="bi bi-box-arrow-right"></i></a>
        </div>
      </section>

      <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-portalia mb-0">
          <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success; ?>
        </div>
      <?php endif; ?>
      <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-portalia mb-0">
          <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
        </div>
      <?php endif; ?>

      <section class="profile-dashboard-grid">
        <aside class="page-card profile-summary-card">
          <div class="profile-identity">
            <img src="../<?php echo sanitize($user['avatar']); ?>" alt="Profile avatar" class="profile-avatar-lg" onerror="this.src='../assets/images/avatar/avatar.jpg'">
            <div>
              <h2 class="profile-username"><?php echo sanitize($user['username']); ?></h2>
              <p class="profile-meta-text"><?php echo sanitize($user['email']); ?><br>NIM: <?php echo sanitize($user['nim']); ?></p>
            </div>
          </div>

          <div class="profile-actions">
            <button class="btn btn-portalia-secondary" style="height: 44px; font-size: 13px; padding: 0 18px !important;" data-bs-toggle="modal" data-bs-target="#editProfileModal">
              <i class="bi bi-pencil-square me-2"></i>Edit Profile
            </button>
            <a href="upload.php" class="btn btn-portalia-primary" style="height: 44px; font-size: 13px; padding: 0 18px !important;">
              <i class="bi bi-plus-circle me-2"></i>New Listing
            </a>
          </div>

          <div class="profile-stats-grid">
            <div class="profile-stat-card">
              <span class="profile-stat-icon"><i class="bi bi-bag-check"></i></span>
              <div>
                <span class="stat-val"><?php echo $active_count; ?></span>
                <span class="stat-lbl">Active</span>
              </div>
            </div>
            <div class="profile-stat-card">
              <span class="profile-stat-icon"><i class="bi bi-receipt"></i></span>
              <div>
                <span class="stat-val"><?php echo $sold_count; ?></span>
                <span class="stat-lbl">Sold</span>
              </div>
            </div>
            <div class="profile-stat-card">
              <span class="profile-stat-icon"><i class="bi bi-heart"></i></span>
              <div>
                <span class="stat-val"><?php echo $wishlist_count; ?></span>
                <span class="stat-lbl">Wishlist</span>
              </div>
            </div>
          </div>
        </aside>

        <section class="page-card listing-panel">
          <div class="listing-panel-header">
            <div>
              <h2>My Listings</h2>
              <span class="section-meta"><?php echo count($my_products); ?> items in this tab</span>
            </div>
          </div>

          <div class="profile-tabs">
            <a href="profile.php?tab=active" class="profile-tab-btn <?php echo $active_tab === 'active' ? 'active' : ''; ?>">
              Active (<?php echo $active_tab === 'active' ? count($my_products) : ( ($stmt = $db->prepare("SELECT COUNT(*) FROM products WHERE seller_id = ? AND status = 'active'")) && $stmt->execute([$current_user_id]) ? $stmt->fetchColumn() : 0 ); ?>)
            </a>
            <a href="profile.php?tab=pending" class="profile-tab-btn <?php echo $active_tab === 'pending' ? 'active' : ''; ?>">
              Pending (<?php echo $active_tab === 'pending' ? count($my_products) : ( ($stmt = $db->prepare("SELECT COUNT(*) FROM products WHERE seller_id = ? AND status = 'pending'")) && $stmt->execute([$current_user_id]) ? $stmt->fetchColumn() : 0 ); ?>)
            </a>
            <a href="profile.php?tab=rejected" class="profile-tab-btn <?php echo $active_tab === 'rejected' ? 'active' : ''; ?>">
              Rejected (<?php echo $active_tab === 'rejected' ? count($my_products) : ( ($stmt = $db->prepare("SELECT COUNT(*) FROM products WHERE seller_id = ? AND status = 'rejected'")) && $stmt->execute([$current_user_id]) ? $stmt->fetchColumn() : 0 ); ?>)
            </a>
            <a href="profile.php?tab=expired" class="profile-tab-btn <?php echo $active_tab === 'expired' ? 'active' : ''; ?>">
              Expired / Sold (<?php echo $active_tab === 'expired' ? count($my_products) : ( ($stmt = $db->prepare("SELECT COUNT(*) FROM products WHERE seller_id = ? AND status = 'expired'")) && $stmt->execute([$current_user_id]) ? $stmt->fetchColumn() : 0 ); ?>)
            </a>
          </div>

          <div class="listing-panel-body">
            <?php if (count($my_products) == 0): ?>
              <div class="empty-state">
                <div class="empty-state-icon">
                  <i class="bi bi-folder2-open"></i>
                </div>
                <h2>No items found</h2>
                <p>No listings are currently available in this status tab.</p>
                <a href="upload.php" class="btn btn-portalia-primary">Create Listing</a>
              </div>
            <?php else: ?>
              <div class="listing-list">
                <?php foreach ($my_products as $my_prod): ?>
                  <div class="my-product-item">
                    <?php if (!empty($my_prod['image']) && file_exists(__DIR__ . '/../' . $my_prod['image'])): ?>
                      <img src="../<?php echo sanitize($my_prod['image']); ?>" alt="Thumbnail" class="my-product-thumb">
                    <?php else: ?>
                      <div class="my-product-placeholder-thumb">
                        <i class="bi bi-image"></i>
                      </div>
                    <?php endif; ?>

                    <div class="my-product-info">
                      <h3 class="my-product-title"><?php echo sanitize($my_prod['name']); ?></h3>
                      <span class="my-product-price"><?php echo formatRupiah($my_prod['price']); ?></span>
                      <span class="text-muted d-block" style="font-size: 11px;">Stock: <?php echo $my_prod['stock']; ?> | <?php echo sanitize($my_prod['category_name']); ?></span>
                      <?php if ($active_tab === 'rejected' && !empty($my_prod['rejection_reason'])): ?>
                        <span class="text-danger d-block mt-1" style="font-size: 11px;">Reason: <?php echo sanitize($my_prod['rejection_reason']); ?></span>
                      <?php endif; ?>
                    </div>

                    <a href="profile.php?tab=<?php echo $active_tab; ?>&delete_id=<?php echo $my_prod['id']; ?>"
                       class="action-icon-btn delete-listing-btn"
                       onclick="return confirm('Are you sure you want to delete this listing permanently?');"
                       aria-label="Delete listing">
                      <i class="bi bi-trash"></i>
                    </a>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </section>
      </section>
    </main>

    <!-- EDIT PROFILE MODAL DIALOG -->
    <div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: var(--portalia-radius-md); border: none;">
          <div class="modal-header border-0 pb-0">
            <h2 class="modal-title" id="editProfileModalLabel" style="font-size: 16px; font-weight: 700;">Edit Profile</h2>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          
          <form action="profile.php?tab=<?php echo $active_tab; ?>" method="POST">
            <div class="modal-body py-3">
              <div class="form-group-portalia">
                <label for="username">Full Name</label>
                <input type="text" name="username" id="username" class="input-portalia input-glow" required value="<?php echo sanitize($user['username']); ?>">
              </div>
              <div class="form-group-portalia">
                <label for="nim">Student ID (NIM)</label>
                <input type="text" name="nim" id="nim" class="input-portalia input-glow" required value="<?php echo sanitize($user['nim']); ?>">
              </div>
              <div class="form-group-portalia">
                <label for="phone">Phone Number</label>
                <input type="text" name="phone" id="phone" class="input-portalia input-glow" required value="<?php echo sanitize($user['phone']); ?>">
              </div>
            </div>
            
            <div class="modal-footer border-0 pt-0">
              <button type="button" class="btn btn-portalia-secondary" style="height: 44px; padding: 0 16px !important; font-size: 13px;" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" name="update_profile" class="btn btn-portalia-primary" style="height: 44px; padding: 0 20px !important; font-size: 13px;">Save Changes</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- BOTTOM FLOATING NAVIGATION -->
    <nav class="bottom-nav">
      <a href="index.php" class="bottom-nav-item">
        <i class="bi bi-house-door"></i>
        <span>Home</span>
      </a>
      <a href="wishlist.php" class="bottom-nav-item">
        <i class="bi bi-heart"></i>
        <span>Wishlist</span>
      </a>
      <a href="upload.php" class="bottom-nav-item">
        <i class="bi bi-plus-circle"></i>
        <span>Upload</span>
      </a>
      <a href="chat.php" class="bottom-nav-item">
        <i class="bi bi-chat-dots"></i>
        <span>Chat</span>
      </a>
      <a href="profile.php" class="bottom-nav-item active">
        <i class="bi bi-person-fill"></i>
        <span>Profile</span>
      </a>
    </nav>

  </div>

  <script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
