<?php
require_once '../db.php';
requireAdmin();

$db = getDB();
$admin = getCurrentUser();

$error = '';
$success = '';

// Handle Moderation actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_moderation'])) {
    $productId = intval($_POST['product_id']);
    $action = $_POST['action'];

    try {
        if ($action === 'approve') {
            $stmt = $db->prepare("UPDATE products SET status = 'active', rejection_reason = NULL WHERE id = ?");
            $stmt->execute([$productId]);
            $success = "Product listing approved successfully! It is now active on the student marketplace.";
        } elseif ($action === 'reject') {
            $reason = trim($_POST['rejection_reason']);
            if (empty($reason)) {
                $error = "A rejection reason is required to reject a product listing.";
            } else {
                $stmt = $db->prepare("UPDATE products SET status = 'rejected', rejection_reason = ? WHERE id = ?");
                $stmt->execute([$reason, $productId]);
                $success = "Product listing rejected. The seller will be notified of the reason.";
            }
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Handle Delete Listing
if (isset($_GET['delete_id'])) {
    $deleteId = intval($_GET['delete_id']);
    try {
        $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$deleteId]);
        $success = "Product listing deleted successfully from database.";
    } catch (PDOException $e) {
        $error = "Delete failed: " . $e->getMessage();
    }
}

// Fetch products with search and status filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$allowed_statuses = ['pending', 'active', 'rejected', 'expired'];

$sql = "SELECT p.*, u.username as seller_name, u.nim as seller_nim, c.name as category_name 
        FROM products p 
        JOIN users u ON p.seller_id = u.id 
        JOIN categories c ON p.category_id = c.id
        WHERE 1=1";

$params = [];

if (in_array($status_filter, $allowed_statuses)) {
    $sql .= " AND p.status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ? OR u.username LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$sql .= " ORDER BY p.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Product Moderation | Portalia</title>
  <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/vendors/bootstrap-icons/bootstrap-icons.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/portalia.css">
</head>
<body>
  <div class="admin-shell">
    <div class="sidebar-backdrop" data-sidebar-close></div>

    <aside class="admin-sidebar" id="adminSidebar" aria-label="Main navigation">
      <div class="sidebar-header">
        <a class="brand-mark" href="index.php" aria-label="Portalia dashboard">
          <span class="brand-icon"><i class="bi bi-grid-1x2-fill" aria-hidden="true"></i></span>
          <span class="brand-copy">
            <span class="brand-title">Portalia</span>
            <span class="brand-subtitle">Smart Campus</span>
          </span>
        </a>
      </div>

      <nav class="sidebar-nav">
        <a class="nav-link" href="index.php">
          <span class="nav-icon"><i class="bi bi-speedometer2" aria-hidden="true"></i></span>
          <span class="nav-text">Dashboard</span>
        </a>
        <a class="nav-link" href="users.php">
          <span class="nav-icon"><i class="bi bi-people" aria-hidden="true"></i></span>
          <span class="nav-text">Users</span>
        </a>
        <a class="nav-link active" href="products.php" aria-current="page">
          <span class="nav-icon"><i class="bi bi-shop" aria-hidden="true"></i></span>
          <span class="nav-text">Products Moderation</span>
        </a>
        <a class="nav-link" href="categories.php">
          <span class="nav-icon"><i class="bi bi-tags" aria-hidden="true"></i></span>
          <span class="nav-text">Categories CRUD</span>
        </a>
        <a class="nav-link" href="reports.php">
          <span class="nav-icon"><i class="bi bi-graph-up-arrow" aria-hidden="true"></i></span>
          <span class="nav-text">Reports & Monitoring</span>
        </a>
        <a class="nav-link" href="../marketplace/index.php">
          <span class="nav-icon"><i class="bi bi-arrow-left-circle" aria-hidden="true"></i></span>
          <span class="nav-text">Back to Marketplace</span>
        </a>
      </nav>

      <div class="sidebar-user">
        <img class="avatar-img avatar-md sidebar-user-avatar" src="../<?php echo sanitize($admin['avatar']); ?>" alt="Admin Profile" onerror="this.src='../assets/images/avatar/avatar.jpg'">
        <strong><?php echo sanitize($admin['username']); ?></strong>
        <small>Portal Administrator</small>
      </div>

      <div class="sidebar-footer">
        <span class="status-dot"></span>
        <span class="sidebar-footer-text">Portalia Admin Panel</span>
      </div>
    </aside>

    <div class="admin-main">
      <nav class="navbar admin-navbar navbar-expand bg-white">
        <div class="container-fluid px-3 px-lg-4">
          <button class="sidebar-toggle" type="button" data-sidebar-toggle aria-controls="adminSidebar" aria-expanded="true" aria-label="Toggle sidebar">
            <span></span>
            <span></span>
            <span></span>
          </button>

          <span class="navbar-text ms-3 fw-semibold text-primary">Marketplace Product Moderation</span>

          <div class="navbar-actions ms-auto">
            <button class="icon-button theme-toggle" type="button" data-theme-toggle aria-label="Switch color theme" title="Switch color theme">
              <i class="bi bi-moon-stars" data-theme-icon aria-hidden="true"></i>
            </button>
            <div class="dropdown">
              <button class="profile-button dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <img class="avatar-img avatar-sm" src="../<?php echo sanitize($admin['avatar']); ?>" alt="Admin Profile" onerror="this.src='../assets/images/avatar/avatar.jpg'">
                <span class="profile-name d-none d-sm-inline"><?php echo sanitize($admin['username']); ?></span>
              </button>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="../marketplace/logout.php">Sign out</a></li>
              </ul>
            </div>
          </div>
        </div>
      </nav>

      <main class="dashboard-content">
        <div class="container-fluid px-3 px-lg-4 py-4">
          
          <div class="page-heading">
            <div class="page-heading-copy">
              <span class="page-icon"><i class="bi bi-shop" aria-hidden="true"></i></span>
              <div>
                <p class="eyebrow mb-1">Products</p>
                <h1 class="h3 mb-1">Listing Moderation</h1>
                <p class="text-muted mb-0">Approve new listing requests to list them publicly, or reject inappropriate posts.</p>
              </div>
            </div>
          </div>

          <!-- SUCCESS & ERROR ALERTS -->
          <?php if (!empty($success)): ?>
            <div class="alert alert-success" style="border-radius: 8px;">
              <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success; ?>
            </div>
          <?php endif; ?>
          <?php if (!empty($error)): ?>
            <div class="alert alert-danger" style="border-radius: 8px;">
              <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
            </div>
          <?php endif; ?>

          <!-- FILTERS PANEL -->
          <div class="panel mb-3">
            <form action="products.php" method="GET" class="row g-2 align-items-center">
              <div class="col-12 col-md-3">
                <select name="status" class="form-select">
                  <option value="">All Statuses</option>
                  <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending Approval</option>
                  <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active / Approved</option>
                  <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected Listings</option>
                  <option value="expired" <?php echo $status_filter === 'expired' ? 'selected' : ''; ?>>Expired / Sold Out</option>
                </select>
              </div>
              <div class="col-12 col-md-4">
                <input class="form-control" type="text" name="search" placeholder="Search by name, description, or student..." value="<?php echo sanitize($search); ?>">
              </div>
              <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel-fill"></i> Filter</button>
              </div>
              <?php if (!empty($search) || !empty($status_filter)): ?>
                <div class="col-auto">
                  <a href="products.php" class="btn btn-outline-secondary btn-sm">Clear Filters</a>
                </div>
              <?php endif; ?>
            </form>
          </div>

          <!-- PRODUCTS TABLE -->
          <div class="panel">
            <div class="table-responsive">
              <table class="table align-middle mb-0">
                <thead>
                  <tr>
                    <th scope="col">Product Item</th>
                    <th scope="col">Category</th>
                    <th scope="col">Price</th>
                    <th scope="col">Student (Seller)</th>
                    <th scope="col">Listing Status</th>
                    <th scope="col" class="text-end">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (count($products) == 0): ?>
                    <tr>
                      <td colspan="6" class="text-center text-muted py-4">No product listing requests found matching criteria.</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($products as $p): ?>
                      <tr>
                        <td>
                          <div class="table-media">
                            <?php if (!empty($p['image']) && file_exists('../' . $p['image'])): ?>
                              <img src="../<?php echo sanitize($p['image']); ?>" alt="Thumbnail" class="product-thumb">
                            <?php else: ?>
                              <div class="product-thumb bg-secondary text-white d-flex align-items-center justify-content-center"><i class="bi bi-image" style="font-size: 20px;"></i></div>
                            <?php endif; ?>
                            <div class="min-width-0">
                              <p class="fw-semibold mb-0 text-truncate" style="max-width: 200px;"><?php echo sanitize($p['name']); ?></p>
                              <small class="text-muted d-block text-truncate" style="max-width: 200px; font-size: 11px;"><?php echo sanitize($p['description']); ?></small>
                              <span class="badge bg-light text-dark" style="font-size: 9px; padding: 2px 6px;">Condition: <?php echo ucfirst($p['item_condition']); ?> | Stock: <?php echo $p['stock']; ?></span>
                            </div>
                          </div>
                        </td>
                        <td><span class="fw-semibold"><?php echo sanitize($p['category_name']); ?></span></td>
                        <td><span class="fw-bold text-primary"><?php echo formatRupiah($p['price']); ?></span></td>
                        <td>
                          <span class="d-block fw-semibold" style="font-size: 13px;"><?php echo sanitize($p['seller_name']); ?></span>
                          <small class="text-muted d-block" style="font-size: 11px;">NIM: <?php echo sanitize($p['seller_nim']); ?></small>
                        </td>
                        <td>
                          <span class="badge <?php 
                            if($p['status'] === 'active') echo 'bg-success';
                            elseif($p['status'] === 'pending') echo 'bg-warning text-dark';
                            elseif($p['status'] === 'rejected') echo 'bg-danger';
                            else echo 'bg-secondary';
                          ?>">
                            <?php echo ucfirst($p['status']); ?>
                          </span>
                        </td>
                        <td class="text-end">
                          <div class="d-inline-flex gap-2">
                            <?php if ($p['status'] === 'pending'): ?>
                              <!-- Approve Action -->
                              <form action="products.php?status=<?php echo $status_filter; ?>" method="POST" class="d-inline">
                                <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" name="action_moderation" class="btn btn-success btn-sm"><i class="bi bi-check-circle"></i> Approve</button>
                              </form>

                              <!-- Reject Collapse Action trigger -->
                              <button class="btn btn-warning btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#rejectForm-<?php echo $p['id']; ?>" aria-expanded="false" aria-controls="rejectForm-<?php echo $p['id']; ?>">
                                <i class="bi bi-x-circle"></i> Reject
                              </button>
                            <?php endif; ?>

                            <!-- Permanent Delete -->
                            <a href="products.php?status=<?php echo $status_filter; ?>&delete_id=<?php echo $p['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this listing permanently from the database?');">
                              <i class="bi bi-trash"></i> Delete
                            </a>
                          </div>

                          <!-- Hidden Rejection Form collapse -->
                          <?php if ($p['status'] === 'pending'): ?>
                            <div class="collapse text-start mt-2" id="rejectForm-<?php echo $p['id']; ?>" style="max-width: 320px; margin-left: auto;">
                              <div class="card card-body p-2 border-warning bg-light">
                                <form action="products.php?status=<?php echo $status_filter; ?>" method="POST">
                                  <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                                  <input type="hidden" name="action" value="reject">
                                  <label class="form-label small fw-bold text-danger mb-1">Rejection Reason:</label>
                                  <input type="text" name="rejection_reason" class="form-control form-control-sm mb-2" placeholder="e.g. Inappropriate item, poor details" required>
                                  <button type="submit" name="action_moderation" class="btn btn-danger btn-xs py-1 px-2 w-100" style="font-size: 10px;">Submit Rejection</button>
                                </form>
                              </div>
                            </div>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>

        </div>
      </main>

      <footer class="admin-footer">
        <div class="container-fluid px-3 px-lg-4">
          <span>Copyright 2026 Portalia Smart Campus. Powered by AdminHMD.</span>
          <span>Information System Portal Admin.</span>
        </div>
      </footer>
    </div>
  </div>

  <script src="../assets/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/js/main.js"></script>
</body>
</html>
