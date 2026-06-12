<?php
require_once '../db.php';

// Access restrictions
if (!isAuthenticated()) {
    header("Location: welcome.php");
    exit;
}

$current_user_id = $_SESSION['user_id'];
$is_guest = ($current_user_id === 'guest');

if ($is_guest) {
    header("Location: login.php");
    exit;
}

$db = getDB();
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Retrieve wishlist products
$sql = "SELECT p.*, u.username as seller_name, u.avatar as seller_avatar, c.name as category_name 
        FROM wishlist w
        JOIN products p ON w.product_id = p.id
        JOIN users u ON p.seller_id = u.id
        JOIN categories c ON p.category_id = c.id
        WHERE w.user_id = ? AND p.status = 'active'";

$params = [$current_user_id];

if (!empty($search)) {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$sql .= " ORDER BY w.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="View your saved products in Portalia Wishlist">
  <title>Your Wishlist - Portalia</title>
  <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/vendors/bootstrap-icons/bootstrap-icons.css">
  <link rel="stylesheet" href="../assets/css/portalia.css">
  <style>
    .wishlist-nav {
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
    .search-bar-wrapper {
      padding: 8px 16px;
      background: var(--portalia-surface);
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
    .empty-wishlist {
      padding: 60px 24px;
      text-align: center;
    }
    .empty-wishlist-icon {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      background: #FFF5F5;
      color: var(--portalia-danger);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 36px;
      margin-bottom: 20px;
      box-shadow: 0 4px 12px rgba(239, 68, 68, 0.1);
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
          <span class="page-header-icon"><i class="bi bi-heart-fill"></i></span>
          <div>
            <span class="page-header-eyebrow">Saved picks</span>
            <h1 class="page-header-title">Wishlist</h1>
            <p class="page-header-subtitle">Saved products you want to check later.</p>
          </div>
        </div>
        <div class="page-header-actions">
          <a href="index.php" class="btn btn-portalia-secondary" style="height: 44px; padding: 0 18px !important; font-size: 13px;">
            <i class="bi bi-shop me-2"></i>Browse
          </a>
        </div>
      </section>

      <section class="wishlist-toolbar">
        <div class="search-bar-wrapper">
          <form action="wishlist.php" method="GET" class="search-box">
            <i class="bi bi-search"></i>
            <input type="text" name="search" class="input-portalia input-glow" placeholder="Search saved items..." value="<?php echo sanitize($search); ?>">
          </form>
        </div>
      </section>

      <?php if (count($products) == 0): ?>
        <section class="empty-state">
          <div class="empty-state-icon">
            <i class="bi bi-heartbreak"></i>
          </div>
          <h2>Your wishlist is empty</h2>
          <p>
            <?php echo !empty($search) ? "No saved products match your search keyword." : "Save products you like and find them here later."; ?>
          </p>
          <a href="index.php" class="btn btn-portalia-primary">Browse Marketplace</a>
        </section>
      <?php else: ?>
        <div class="product-grid">
          <?php foreach ($products as $prod): ?>
            <div class="product-card-portalia" id="product-card-<?php echo $prod['id']; ?>">
              <span class="product-card-badge condition-<?php echo str_replace('_', '-', $prod['item_condition']); ?>">
                <?php echo str_replace('_', ' ', $prod['item_condition']); ?>
              </span>

              <!-- Active Wishlist state since it's in this list -->
              <button type="button" class="product-card-wishlist active" data-product-id="<?php echo $prod['id']; ?>" aria-label="Remove from wishlist">
                <i class="bi bi-heart-fill"></i>
              </button>

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

    <!-- BOTTOM FLOATING NAVIGATION -->
    <nav class="bottom-nav">
      <a href="index.php" class="bottom-nav-item">
        <i class="bi bi-house-door"></i>
        <span>Home</span>
      </a>
      <a href="wishlist.php" class="bottom-nav-item active">
        <i class="bi bi-heart-fill"></i>
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
    // AJAX toggle wishlist logic
    document.querySelectorAll('.product-card-wishlist').forEach(button => {
      button.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const productId = this.getAttribute('data-product-id');
        const cardContainer = document.getElementById(`product-card-${productId}`);
        
        fetch('wishlist_toggle.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: `product_id=${productId}`
        })
        .then(response => response.json())
        .then(data => {
          if (data.status === 'success' && data.action === 'removed') {
            // Smoothly remove card from view
            cardContainer.style.transition = 'all 0.3s ease';
            cardContainer.style.opacity = '0';
            cardContainer.style.transform = 'scale(0.8)';
            setTimeout(() => {
              cardContainer.remove();
              // If no cards left, reload to show empty state
              if (document.querySelectorAll('.product-card-portalia').length === 0) {
                window.location.reload();
              }
            }, 300);
          } else {
            alert('Could not update wishlist item.');
          }
        })
        .catch(err => {
          console.error(err);
          alert('Network error.');
        });
      });
    });
  </script>
</body>
</html>
