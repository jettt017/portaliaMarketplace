<?php
require_once '../db.php';

if (!isAuthenticated()) {
    header("Location: welcome.php");
    exit;
}

$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$current_user_id = $_SESSION['user_id'];
$is_guest = ($current_user_id === 'guest');

$db = getDB();

// Fetch product details with seller and category
$stmt = $db->prepare("SELECT p.*, u.username as seller_name, u.email as seller_email, u.phone as seller_phone, u.avatar as seller_avatar, u.nim as seller_nim, c.name as category_name 
                      FROM products p 
                      JOIN users u ON p.seller_id = u.id 
                      JOIN categories c ON p.category_id = c.id 
                      WHERE p.id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    die("Product not found.");
}

$error = '';
$success = '';

// Handle Buy Action
if (isset($_GET['action']) && $_GET['action'] === 'buy') {
    if ($is_guest) {
        header("Location: login.php");
        exit;
    }
    
    if ($product['seller_id'] == $current_user_id) {
        $error = "You cannot buy your own listing!";
    } elseif ($product['stock'] < 1 || $product['status'] !== 'active') {
        $error = "This product is no longer available.";
    } else {
        // Calculate fees
        $price = $product['price'];
        $admin_fee = $price * 0.05; // 5% fee
        $net_amount = $price - $admin_fee;

        try {
            $db->beginTransaction();

            // Insert transaction
            $stmt = $db->prepare("INSERT INTO transactions (buyer_id, seller_id, product_id, price, admin_fee, net_amount) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$current_user_id, $product['seller_id'], $productId, $price, $admin_fee, $net_amount]);

            // Update product stock
            $newStock = $product['stock'] - 1;
            if ($newStock <= 0) {
                $stmt = $db->prepare("UPDATE products SET stock = 0, status = 'expired' WHERE id = ?");
            } else {
                $stmt = $db->prepare("UPDATE products SET stock = ? WHERE id = ?");
                $stmt->execute([$newStock, $productId]);
            }
            $stmt->execute([$productId]);

            $db->commit();
            header("Location: product.php?id=" . $productId . "&bought=1");
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            $error = "Transaction failed: " . $e->getMessage();
        }
    }
}

// Check if already in wishlist
$in_wishlist = false;
if (!$is_guest) {
    $stmt = $db->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$current_user_id, $productId]);
    $in_wishlist = (bool)$stmt->fetch();
}

$bought = isset($_GET['bought']) && $_GET['bought'] == 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="View product details on Portalia">
  <title><?php echo sanitize($product['name']); ?> - Portalia</title>
  <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/vendors/bootstrap-icons/bootstrap-icons.css">
  <link rel="stylesheet" href="../assets/css/portalia.css">
  <style>
    .detail-nav {
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
    .product-gallery-wrapper {
      position: relative;
      width: 100%;
      padding-top: 75%; /* 4:3 Aspect Ratio */
      overflow: hidden;
      background-color: var(--portalia-bg);
    }
    .product-gallery-img {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    .seller-profile-card {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 16px;
      border-radius: var(--portalia-radius-md);
      background: var(--portalia-bg);
      margin-top: 24px;
    }
    .seller-info {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .seller-avatar {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      object-fit: cover;
    }
    .placeholder-product-img-detail {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, #e0ecff 0%, #f4f7ff 100%);
      color: var(--portalia-primary);
      font-size: 64px;
    }
    /* Receipt Modal styling */
    .receipt-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.5);
      z-index: 1050;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 16px;
    }
    .receipt-card {
      width: 100%;
      max-width: 400px;
      background: #FFFFFF;
      border-radius: var(--portalia-radius-lg);
      padding: 28px;
      box-shadow: var(--portalia-shadow-elevated);
      text-align: center;
      position: relative;
    }
  </style>
</head>
<body>

  <div class="app-container">

    <!-- DESKTOP NAVBAR (visible on ≥992px) -->
    <?php include '_desktop_navbar.php'; ?>

    <!-- TOP NAVIGATION -->
    <nav class="detail-nav">
      <a href="index.php" class="action-icon-btn" aria-label="Go back"><i class="bi bi-arrow-left"></i></a>
      <span class="fw-bold" style="font-size: 16px;">Product Detail</span>
      <div style="width: 40px;"></div> <!-- Spacer -->
    </nav>

    <!-- SUCCESS PURCHASE MODAL -->
    <?php if ($bought): ?>
      <div class="receipt-overlay">
        <div class="receipt-card">
          <div class="mb-3" style="font-size: 54px; color: var(--portalia-success);">
            <i class="bi bi-check-circle-fill"></i>
          </div>
          <h3 class="mb-1" style="font-size: 20px; font-weight: 700;">Purchase Successful!</h3>
          <p class="text-muted" style="font-size: 13px;">The transaction details have been logged in the information system database.</p>
          
          <div class="my-4 p-3" style="background: var(--portalia-bg); border-radius: var(--portalia-radius-sm); text-align: left;">
            <div class="d-flex justify-content-between mb-2" style="font-size: 12px;">
              <span class="text-muted">Item:</span>
              <span class="fw-semibold text-end"><?php echo sanitize($product['name']); ?></span>
            </div>
            <div class="d-flex justify-content-between mb-2" style="font-size: 12px;">
              <span class="text-muted">Seller:</span>
              <span class="fw-semibold"><?php echo sanitize($product['seller_name']); ?></span>
            </div>
            <hr class="my-2" style="border-top: 1px dashed #CBD5E1;">
            <div class="d-flex justify-content-between" style="font-size: 14px; font-weight: 700;">
              <span>Total Paid:</span>
              <span style="color: var(--portalia-primary);"><?php echo formatRupiah($product['price']); ?></span>
            </div>
          </div>

          <a href="chat.php?product_id=<?php echo $productId; ?>&receiver_id=<?php echo $product['seller_id']; ?>&auto_msg=1" class="btn btn-portalia-primary w-100 mb-2">
            <i class="bi bi-chat-fill me-2"></i> Chat Seller for Delivery
          </a>
          <a href="index.php" class="btn btn-portalia-secondary w-100">Back to Marketplace</a>
        </div>
      </div>
    <?php endif; ?>

    <!-- ERROR MESSAGES -->
    <?php if (!empty($error)): ?>
      <div class="alert alert-danger m-3" style="border-radius: var(--portalia-radius-sm); font-size: 13px;">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
      </div>
    <?php endif; ?>

    <!-- PRODUCT CONTENT -->
    <main class="product-detail-layout">
      <section class="product-detail-media">
        <div class="product-gallery-wrapper">
          <?php if (!empty($product['image']) && file_exists(__DIR__ . '/../' . $product['image'])): ?>
            <img src="../<?php echo sanitize($product['image']); ?>" alt="<?php echo sanitize($product['name']); ?>" class="product-gallery-img">
          <?php else: ?>
            <div class="placeholder-product-img-detail">
              <i class="bi bi-image"></i>
              <span class="placeholder-product-label"><?php echo sanitize($product['category_name']); ?></span>
            </div>
          <?php endif; ?>
        </div>
      </section>

      <section class="product-detail-content">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <div>
            <span class="badge condition-<?php echo str_replace('_', '-', $product['item_condition']); ?> mb-2" style="font-size: 10px; font-weight: 700; text-transform: uppercase; padding: 4px 10px; border-radius: var(--portalia-radius-pill); background-color: var(--portalia-bg); color: var(--portalia-text-secondary);">
              Condition: <?php echo str_replace('_', ' ', $product['item_condition']); ?>
            </span>
            <h1 class="product-detail-title" style="font-size: 22px; line-height: 1.3; margin-bottom: 8px;"><?php echo sanitize($product['name']); ?></h1>
            <p class="text-muted" style="font-size: 12px; margin-bottom: 0;">Category: <span class="fw-semibold text-dark"><?php echo sanitize($product['category_name']); ?></span></p>
          </div>
        </div>

        <div class="my-3 py-2">
          <span class="d-block text-muted" style="font-size: 12px;">Price</span>
          <span class="product-detail-price" style="font-size: 26px; font-weight: 800; color: var(--portalia-primary);"><?php echo formatRupiah($product['price']); ?></span>
        </div>

        <hr style="border-top: 1px solid #E2E8F0;">

        <!-- Description -->
        <div class="detail-info-card mb-4">
          <h2 style="font-size: 15px; font-weight: 700; margin-bottom: 8px;">Description</h2>
          <p style="font-size: 13px; line-height: 1.7; color: var(--portalia-text-secondary); white-space: pre-wrap;"><?php echo sanitize($product['description']); ?></p>
        </div>

        <!-- Item Specifications -->
        <div class="card-portalia mb-4">
          <h2 style="font-size: 14px; font-weight: 700; margin-bottom: 12px;"><i class="bi bi-info-circle me-2" style="color: var(--portalia-primary);"></i>Specifications</h2>
          <div class="row g-2" style="font-size: 12px;">
            <div class="col-6">
              <span class="text-muted">Stock Quantity:</span>
              <span class="fw-semibold text-dark d-block"><?php echo $product['stock']; ?> items</span>
            </div>
            <div class="col-6">
              <span class="text-muted">Expiration Date:</span>
              <span class="fw-semibold text-dark d-block"><?php echo !empty($product['expiration_date']) ? date('d M Y', strtotime($product['expiration_date'])) : '-'; ?></span>
            </div>
          </div>
        </div>

        <!-- SELLER INFO CARD -->
        <div class="seller-profile-card">
          <div class="seller-info">
            <img src="../<?php echo sanitize($product['seller_avatar']); ?>" alt="Seller" class="seller-avatar" onerror="this.src='../assets/images/avatar/avatar.jpg'">
            <div>
              <span class="fw-bold d-block" style="font-size: 13px;"><?php echo sanitize($product['seller_name']); ?></span>
              <span class="text-muted" style="font-size: 11px;"><?php echo !empty($product['seller_nim']) ? 'NIM: ' . sanitize($product['seller_nim']) : 'Portalia Student'; ?></span>
            </div>
          </div>
          <div class="text-end" style="font-size: 12px;">
            <span class="badge bg-success" style="border-radius: var(--portalia-radius-pill); font-size: 10px; padding: 4px 8px;">Verified Student</span>
          </div>
        </div>

        <!-- STICKY CTA BAR -->
        <div class="sticky-cta-bar">
          <button type="button" class="action-icon-btn product-detail-wishlist-btn <?php echo $in_wishlist ? 'active' : ''; ?>"
                  data-product-id="<?php echo $productId; ?>" aria-label="Toggle wishlist">
            <i class="bi <?php echo $in_wishlist ? 'bi-heart-fill text-danger' : 'bi-heart'; ?>" style="font-size: 20px;"></i>
          </button>

          <div class="d-flex gap-2 w-100 ms-3">
            <?php if ($is_guest): ?>
              <a href="login.php" class="btn btn-portalia-secondary flex-grow-1">Log in to Chat</a>
              <a href="login.php" class="btn btn-portalia-primary flex-grow-1">Log in to Buy</a>
            <?php elseif ($product['seller_id'] == $current_user_id): ?>
              <a href="profile.php" class="btn btn-portalia-secondary w-100">Manage Your Listing</a>
            <?php else: ?>
              <a href="chat.php?product_id=<?php echo $productId; ?>&receiver_id=<?php echo $product['seller_id']; ?>" class="btn btn-portalia-secondary flex-grow-1" style="height: 52px; font-size: 14px;">
                <i class="bi bi-chat-text-fill me-2"></i> Chat
              </a>
              <a href="product.php?id=<?php echo $productId; ?>&action=buy" class="btn btn-portalia-primary flex-grow-1" style="height: 52px; font-size: 14px;" onclick="return confirm('Are you sure you want to buy this item?');">
                Buy Now
              </a>
            <?php endif; ?>
          </div>
        </div>

        <!-- Space for mobile bottom CTA -->
        <div class="mobile-cta-spacer"></div>
      </section>
    </main>

  </div>

  <script src="../assets/js/bootstrap.bundle.min.js"></script>
  <script>
    // Handle wishlist toggle via AJAX
    document.querySelector('.product-detail-wishlist-btn').addEventListener('click', function(e) {
      e.preventDefault();
      
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
            icon.className = 'bi bi-heart-fill text-danger';
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
        alert('Network error.');
      });
    });
  </script>
</body>
</html>
