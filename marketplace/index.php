<?php
require_once '../db.php';

// Handle guest session creation
if (isset($_GET['guest']) && $_GET['guest'] == '1') {
    $_SESSION['user_id'] = 'guest';
    $_SESSION['user_username'] = 'Guest';
    $_SESSION['user_role'] = 'guest';
    $_SESSION['user_email'] = '';
}

// Redirect to welcome if no session exists
if (!isAuthenticated()) {
    header("Location: welcome.php");
    exit;
}

$current_user_id = $_SESSION['user_id'];
$is_guest = ($current_user_id === 'guest');

$db = getDB();

// Fetch categories for filter list
$stmt = $db->query("SELECT * FROM categories ORDER BY id ASC");
$categories = $stmt->fetchAll();

// Handle search & category filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_id = isset($_GET['category']) ? intval($_GET['category']) : 0;

$sql = "SELECT p.*, u.username as seller_name, u.avatar as seller_avatar, c.name as category_name 
        FROM products p 
        JOIN users u ON p.seller_id = u.id 
        JOIN categories c ON p.category_id = c.id 
        WHERE p.status = 'active'";

$params = [];

if (!empty($search)) {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

if ($category_id > 0) {
    $sql .= " AND p.category_id = ?";
    $params[] = $category_id;
}

$sql .= " ORDER BY p.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Fetch user wishlist IDs to highlight active states
$wishlist_ids = [];
if (!$is_guest) {
    $stmt = $db->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
    $stmt->execute([$current_user_id]);
    $wishlist_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Get user profile details
$user_profile = null;
$unread_chats = 0;
if (!$is_guest) {
    $user_profile = getCurrentUser();
    $unread_chats = getUnreadChatCount($current_user_id);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Explore and buy products in Portalia Smart Campus Marketplace">
  <title>Portalia - Campus Marketplace</title>
  <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/vendors/bootstrap-icons/bootstrap-icons.css">
  <link rel="stylesheet" href="../assets/css/portalia.css">
  <style>
    .home-header {
      padding: 20px 16px 12px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: var(--portalia-surface);
    }
    .user-greeting {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .user-greeting-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid #52e7b8;
    }
    .greeting-text h2 {
      font-size: 14px;
      font-weight: 700;
      margin: 0;
      color: var(--portalia-text-primary);
    }
    .greeting-text p {
      font-size: 11px;
      margin: 0;
      color: var(--portalia-text-secondary);
    }
    .header-actions {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .action-icon-btn {
      width: 40px;
      height: 40px;
      border-radius: var(--portalia-radius-sm);
      background: var(--portalia-bg);
      border: none;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--portalia-text-primary);
      position: relative;
      text-decoration: none;
    }
    .action-icon-badge {
      position: absolute;
      top: 6px;
      right: 6px;
      background-color: var(--portalia-danger);
      color: #FFFFFF;
      font-size: 9px;
      font-weight: 700;
      padding: 2px 5px;
      border-radius: 50%;
    }
    .search-bar-wrapper {
      padding: 8px 16px;
      background: var(--portalia-surface);
      position: sticky;
      top: 0;
      z-index: 100;
    }
    .search-box {
      position: relative;
      display: flex;
      align-items: center;
    }
    .search-box i {
      position: absolute;
      left: 18px;
      color: var(--portalia-text-secondary);
    }
    .search-box input {
      padding-left: 46px;
    }
    .promo-banner {
      margin: 16px;
      padding: 20px;
      border-radius: var(--portalia-radius-md);
      background: var(--portalia-gradient);
      color: #FFFFFF;
      position: relative;
      overflow: hidden;
      box-shadow: var(--portalia-shadow-soft);
    }
    .promo-banner-content {
      position: relative;
      z-index: 2;
      max-width: 60%;
    }
    .promo-banner-content h3 {
      font-size: 18px;
      font-weight: 700;
      color: #FFFFFF !important;
      margin-bottom: 6px;
    }
    .promo-banner-content p {
      font-size: 11px;
      opacity: 0.9;
      margin-bottom: 0;
      line-height: 1.5;
    }
    .promo-banner-bg {
      position: absolute;
      right: -20px;
      bottom: -30px;
      font-size: 140px;
      color: rgba(255, 255, 255, 0.12);
      z-index: 1;
      transform: rotate(-15deg);
    }
    .placeholder-product-img {
      width: 100%;
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, #e0ecff 0%, #f4f7ff 100%);
      color: var(--portalia-primary);
      font-size: 32px;
    }
  </style>
</head>
<body>

  <div class="app-container">
    
    <!-- HEADER -->
    <header class="home-header">
      <div class="user-greeting">
        <?php if ($is_guest): ?>
          <a href="login.php" class="action-icon-btn"><i class="bi bi-person"></i></a>
          <div class="greeting-text">
            <h2>Welcome Guest</h2>
            <p>Log in to access all features</p>
          </div>
        <?php else: ?>
          <a href="profile.php">
            <img src="../<?php echo sanitize($user_profile['avatar']); ?>" alt="Profile" class="user-greeting-avatar" onerror="this.src='../assets/images/avatar/avatar.jpg'">
          </a>
          <div class="greeting-text">
            <h2>Hi, <?php echo sanitize($user_profile['username']); ?></h2>
            <p><?php echo !empty($user_profile['nim']) ? 'NIM: ' . sanitize($user_profile['nim']) : 'Portalia Student'; ?></p>
          </div>
        <?php endif; ?>
      </div>

      <div class="header-actions">
        <?php if ($is_guest): ?>
          <a href="login.php" class="btn btn-portalia-primary btn-sm py-2 px-3" style="height: auto; border-radius: var(--portalia-radius-sm) !important;">Log In</a>
        <?php else: ?>
          <a href="chat.php" class="action-icon-btn" aria-label="Messages">
            <i class="bi bi-chat-text-fill"></i>
            <?php if ($unread_chats > 0): ?>
              <span class="action-icon-badge"><?php echo $unread_chats; ?></span>
            <?php endif; ?>
          </a>
        <?php endif; ?>
      </div>
    </header>

    <!-- STICKY SEARCH BAR -->
    <div class="search-bar-wrapper">
      <form action="index.php" method="GET" class="search-box">
        <?php if ($category_id > 0): ?>
          <input type="hidden" name="category" value="<?php echo $category_id; ?>">
        <?php endif; ?>
        <i class="bi bi-search"></i>
        <input type="text" name="search" class="input-portalia input-glow" placeholder="Search textbooks, gadgets, services..." value="<?php echo sanitize($search); ?>">
      </form>
    </div>

    <!-- PROMO BANNER -->
    <div class="promo-banner">
      <div class="promo-banner-content">
        <h3>Smart Trading</h3>
        <p>Buy and sell items within your campus securely. Save money, reduce waste, support classmates.</p>
      </div>
      <i class="bi bi-shop-window promo-banner-bg"></i>
    </div>

    <!-- CATEGORY SLIDER -->
    <div class="category-slider">
      <a href="index.php<?php echo !empty($search) ? '?search=' . urlencode($search) : ''; ?>" class="category-pill <?php echo $category_id === 0 ? 'active' : ''; ?>">
        All Items
      </a>
      <?php foreach ($categories as $cat): ?>
        <a href="index.php?category=<?php echo $cat['id']; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
           class="category-pill <?php echo $category_id === $cat['id'] ? 'active' : ''; ?>">
          <i class="bi <?php echo sanitize($cat['icon']); ?>"></i>
          <?php echo sanitize($cat['name']); ?>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- PRODUCTS SECTION -->
    <main class="page-section">
      <div class="section-heading-row">
        <h2>Recommendations</h2>
        <span class="section-meta"><?php echo count($products); ?> items found</span>
      </div>

      <?php if (count($products) == 0): ?>
        <div class="text-center py-5">
          <i class="bi bi-search" style="font-size: 48px; color: var(--portalia-text-secondary); opacity: 0.5;"></i>
          <h3 class="mt-3" style="font-size: 16px; font-weight: 600;">No Products Found</h3>
          <p class="text-muted" style="font-size: 13px;">Try typing a different keyword or removing the filters.</p>
        </div>
      <?php else: ?>
        <div class="product-grid">
          <?php foreach ($products as $prod): ?>
            <div class="product-card-portalia">
              <!-- Condition Badge -->
              <span class="product-card-badge condition-<?php echo str_replace('_', '-', $prod['item_condition']); ?>">
                <?php echo str_replace('_', ' ', $prod['item_condition']); ?>
              </span>

              <!-- Wishlist Toggle Button -->
              <button type="button" class="product-card-wishlist <?php echo in_array($prod['id'], $wishlist_ids) ? 'active' : ''; ?>"
                      data-product-id="<?php echo $prod['id']; ?>" aria-label="Toggle wishlist">
                <i class="bi <?php echo in_array($prod['id'], $wishlist_ids) ? 'bi-heart-fill' : 'bi-heart'; ?>"></i>
              </button>

              <!-- Product Link -->
              <a href="product.php?id=<?php echo $prod['id']; ?>" class="text-decoration-none text-reset flex-grow-1 d-flex flex-column">
                <div class="product-card-img-wrapper">
                  <?php if (!empty($prod['image']) && file_exists(__DIR__ . '/../' . $prod['image'])): ?>
                    <img src="../<?php echo sanitize($prod['image']); ?>" alt="<?php echo sanitize($prod['name']); ?>" class="product-card-img">
                  <?php else: ?>
                    <div class="placeholder-product-img">
                      <i class="bi bi-image"></i>
                      <span class="placeholder-product-label"><?php echo sanitize($prod['category_name']); ?></span>
                    </div>
                  <?php endif; ?>
                </div>

                <div class="product-card-body">
                  <span class="product-card-category"><?php echo sanitize($prod['category_name']); ?></span>
                  <h3 class="product-card-title"><?php echo sanitize($prod['name']); ?></h3>
                  <span class="product-card-price"><?php echo formatRupiah($prod['price']); ?></span>

                  <div class="product-card-footer">
                    <img src="../<?php echo sanitize($prod['seller_avatar']); ?>" alt="Seller" class="product-card-avatar" onerror="this.src='../assets/images/avatar/avatar.jpg'">
                    <span class="product-card-seller"><?php echo sanitize($prod['seller_name']); ?></span>
                  </div>
                </div>
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </main>

    <!-- FLOATING BOTTOM NAVIGATION -->
    <nav class="bottom-nav">
      <a href="index.php" class="bottom-nav-item active">
        <i class="bi bi-house-door-fill"></i>
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
      <a href="profile.php" class="bottom-nav-item">
        <i class="bi bi-person"></i>
        <span>Profile</span>
      </a>
    </nav>

  </div>

  <script src="../assets/js/bootstrap.bundle.min.js"></script>
  <script>
    // Handle Wishlist Toggling dynamically via Fetch API
    document.querySelectorAll('.product-card-wishlist').forEach(button => {
      button.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();

        <?php if ($is_guest): ?>
          alert('You must log in to manage your wishlist!');
          window.location.href = 'login.php';
          return;
        <?php endif; ?>

        const productId = this.getAttribute('data-product-id');
        const icon = this.querySelector('i');
        
        fetch('wishlist_toggle.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: `product_id=${productId}`
        })
        .then(response => response.json())
        .then(data => {
          if (data.status === 'success') {
            if (data.action === 'added') {
              this.classList.add('active');
              icon.className = 'bi bi-heart-fill';
            } else {
              this.classList.remove('active');
              icon.className = 'bi bi-heart';
            }
          } else {
            alert(data.message || 'Something went wrong.');
          }
        })
        .catch(err => {
          console.error(err);
          alert('Network error. Failed to update wishlist.');
        });
      });
    });
  </script>
</body>
</html>
