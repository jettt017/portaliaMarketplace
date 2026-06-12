<?php
require_once '../db.php';
requireAdmin();

$db = getDB();

// Count stats
$total_users = $db->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$total_products = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
$active_listings = $db->query("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn();
$pending_listings = $db->query("SELECT COUNT(*) FROM products WHERE status = 'pending'")->fetchColumn();
$total_categories = $db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$total_orders = $db->query("SELECT COUNT(*) FROM transactions")->fetchColumn();
$total_revenue = $db->query("SELECT SUM(admin_fee) FROM transactions")->fetchColumn();
if (!$total_revenue) $total_revenue = 0;

// Fetch active admin details
$admin = getCurrentUser();

// Fetch recent active items (mix of new users and transactions)
$recent_activities = [];

// New Users
$new_users = $db->query("SELECT username, created_at FROM users WHERE role = 'student' ORDER BY created_at DESC LIMIT 3")->fetchAll();
foreach ($new_users as $nu) {
    $recent_activities[] = [
        'type' => 'user',
        'title' => 'New student registered',
        'desc' => 'Student ' . sanitize($nu['username']) . ' created an account.',
        'time' => strtotime($nu['created_at']),
        'color' => 'bg-primary'
    ];
}

// New Transactions
$new_sales = $db->query("SELECT t.*, u.username as buyer_name, p.name as product_name FROM transactions t JOIN users u ON t.buyer_id = u.id JOIN products p ON t.product_id = p.id ORDER BY t.created_at DESC LIMIT 3")->fetchAll();
foreach ($new_sales as $ns) {
    $recent_activities[] = [
        'type' => 'sale',
        'title' => 'Transaction completed',
        'desc' => sanitize($ns['buyer_name']) . ' bought ' . sanitize($ns['product_name']) . ' for ' . formatRupiah($ns['price']) . '.',
        'time' => strtotime($ns['created_at']),
        'color' => 'bg-success'
    ];
}

// New Uploads
$new_uploads = $db->query("SELECT name, created_at, status FROM products ORDER BY created_at DESC LIMIT 3")->fetchAll();
foreach ($new_uploads as $nu) {
    $recent_activities[] = [
        'type' => 'upload',
        'title' => 'Product listing uploaded',
        'desc' => 'Product "' . sanitize($nu['name']) . '" submitted (' . ucfirst($nu['status']) . ').',
        'time' => strtotime($nu['created_at']),
        'color' => $nu['status'] === 'pending' ? 'bg-warning' : 'bg-info'
    ];
}

// Sort activities by timestamp desc
usort($recent_activities, function($a, $b) {
    return $b['time'] - $a['time'];
});
$recent_activities = array_slice($recent_activities, 0, 5);

// Fetch recent user list for bottom table
$recent_users = $db->query("SELECT * FROM users WHERE role = 'student' ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Portalia adminHMD dashboard management panel">
  <title>Admin Dashboard | Portalia</title>
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
        <a class="nav-link active" href="index.php" aria-current="page">
          <span class="nav-icon"><i class="bi bi-speedometer2" aria-hidden="true"></i></span>
          <span class="nav-text">Dashboard</span>
        </a>
        <a class="nav-link" href="users.php">
          <span class="nav-icon"><i class="bi bi-people" aria-hidden="true"></i></span>
          <span class="nav-text">Users</span>
        </a>
        <a class="nav-link" href="products.php">
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
        <span class="status-dot animate-pulse"></span>
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

          <span class="navbar-text ms-3 fw-semibold text-primary">Admin Workspace</span>

          <div class="navbar-actions ms-auto">
            <a href="../marketplace/index.php" class="btn btn-primary btn-sm">
              <i class="bi bi-shop-window" aria-hidden="true"></i>
              <span class="d-none d-sm-inline">Menu Utama</span>
            </a>

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
              <span class="page-icon"><i class="bi bi-speedometer2" aria-hidden="true"></i></span>
              <div>
                <p class="eyebrow mb-1">Overview</p>
                <h1 class="h3 mb-1">Dashboard</h1>
                <p class="text-muted mb-0">Control student accounts, list requests, categories, and track platform revenue.</p>
              </div>
            </div>
            <div class="heading-actions">
              <a href="../marketplace/index.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left-circle" aria-hidden="true"></i>
                Kembali ke Marketplace
              </a>
            </div>
          </div>

          <!-- METRICS CARDS -->
          <section class="row g-3 mt-1" aria-label="Dashboard metrics">
            <div class="col-12 col-sm-6 col-xl-3">
              <article class="metric-card metric-primary">
                <div class="metric-top">
                  <span class="metric-label">Platform Revenue</span>
                  <span class="metric-icon"><i class="bi bi-cash-stack" aria-hidden="true"></i></span>
                </div>
                <div class="metric-value" style="font-size: 1.8rem;"><?php echo formatRupiah($total_revenue); ?></div>
                <div class="metric-meta">
                  <span class="text-success"><?php echo $total_orders; ?></span>
                  <span>sales completed</span>
                </div>
              </article>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
              <article class="metric-card metric-success">
                <div class="metric-top">
                  <span class="metric-label">Active Listings</span>
                  <span class="metric-icon"><i class="bi bi-shop-window" aria-hidden="true"></i></span>
                </div>
                <div class="metric-value"><?php echo $active_listings; ?></div>
                <div class="metric-meta">
                  <span>out of </span>
                  <span class="text-primary fw-bold"><?php echo $total_products; ?> total products</span>
                </div>
              </article>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
              <article class="metric-card metric-warning">
                <div class="metric-top">
                  <span class="metric-label">Pending Approval</span>
                  <span class="metric-icon"><i class="bi bi-hourglass-split" aria-hidden="true"></i></span>
                </div>
                <div class="metric-value text-warning"><?php echo $pending_listings; ?></div>
                <div class="metric-meta">
                  <a href="products.php?status=pending" class="text-warning text-decoration-none">needs moderation &rarr;</a>
                </div>
              </article>
            </div>

            <div class="col-12 col-sm-6 col-xl-3">
              <article class="metric-card metric-danger">
                <div class="metric-top">
                  <span class="metric-label">Registered Students</span>
                  <span class="metric-icon"><i class="bi bi-people" aria-hidden="true"></i></span>
                </div>
                <div class="metric-value"><?php echo $total_users; ?></div>
                <div class="metric-meta">
                  <span>active student profiles</span>
                </div>
              </article>
            </div>
          </section>

          <!-- GRAPHS AND RECENT ACTIVITY -->
          <section class="row g-3 mt-1">
            <div class="col-12 col-xl-8">
              <div class="panel">
                <div class="panel-header">
                  <div>
                    <h2 class="h5 mb-1 section-title"><i class="bi bi-graph-up-arrow" aria-hidden="true"></i><span>Platform Sales Distribution</span></h2>
                    <p class="text-muted mb-0">Overview of completed purchases and platform maintenance earnings.</p>
                  </div>
                  <a class="btn btn-light btn-sm" href="reports.php">View Reports</a>
                </div>

                <!-- Custom Bar graph visual simulation -->
                <div class="chart-bars" aria-label="Sales distribution chart">
                  <div class="chart-column bar-58"><span></span><small>Jan</small></div>
                  <div class="chart-column bar-42"><span></span><small>Feb</small></div>
                  <div class="chart-column bar-66"><span></span><small>Mar</small></div>
                  <div class="chart-column bar-51"><span></span><small>Apr</small></div>
                  <div class="chart-column bar-72"><span></span><small>May</small></div>
                  <div class="chart-column bar-83"><span></span><small>Jun</small></div>
                </div>
              </div>
            </div>

            <div class="col-12 col-xl-4">
              <div class="panel h-100">
                <div class="panel-header">
                  <div>
                    <h2 class="h5 mb-1 section-title"><i class="bi bi-activity" aria-hidden="true"></i><span>Live Feed Activity</span></h2>
                    <p class="text-muted mb-0">Recent events in the marketplace database.</p>
                  </div>
                </div>

                <div class="activity-list">
                  <?php if (count($recent_activities) == 0): ?>
                    <p class="text-muted small">No recent activity detected.</p>
                  <?php else: ?>
                    <?php foreach ($recent_activities as $act): ?>
                      <div class="activity-item">
                        <span class="activity-dot <?php echo $act['color']; ?>"></span>
                        <div>
                          <p class="mb-1 fw-semibold" style="font-size: 13px;"><?php echo $act['title']; ?></p>
                          <p class="text-muted small mb-0" style="font-size: 11px;"><?php echo $act['desc']; ?></p>
                          <small class="text-muted" style="font-size: 9px;"><?php echo date('d M Y, H:i', $act['time']); ?></small>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </section>

          <!-- RECENT USERS TABLE -->
          <section class="panel mt-3">
            <div class="panel-header">
              <div>
                <h2 class="h5 mb-1 section-title"><i class="bi bi-people" aria-hidden="true"></i><span>Newly Registered Students</span></h2>
                <p class="text-muted mb-0">Latest student accounts waiting for platform interaction.</p>
              </div>
              <a class="btn btn-outline-secondary btn-sm" href="users.php">Manage Users Directory</a>
            </div>
            <div class="table-responsive">
              <table class="table align-middle mb-0">
                <thead>
                  <tr>
                    <th scope="col">Student Name</th>
                    <th scope="col">NIM</th>
                    <th scope="col">Email</th>
                    <th scope="col">Phone</th>
                    <th scope="col">Account Status</th>
                    <th scope="col" class="text-end">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($recent_users as $ru): ?>
                    <tr>
                      <td>
                        <div class="d-flex align-items-center gap-2">
                          <img class="avatar-img avatar-sm" src="../<?php echo sanitize($ru['avatar']); ?>" alt="Student Avatar" onerror="this.src='../assets/images/avatar/avatar.jpg'">
                          <div>
                            <p class="fw-semibold mb-0"><?php echo sanitize($ru['username']); ?></p>
                          </div>
                        </div>
                      </td>
                      <td><?php echo sanitize($ru['nim']); ?></td>
                      <td><?php echo sanitize($ru['email']); ?></td>
                      <td><?php echo sanitize($ru['phone']); ?></td>
                      <td>
                        <span class="badge <?php echo $ru['status'] === 'active' ? 'bg-success' : 'bg-danger'; ?>">
                          <?php echo ucfirst($ru['status']); ?>
                        </span>
                      </td>
                      <td class="text-end">
                        <a class="btn btn-light btn-sm" href="users.php?search=<?php echo urlencode($ru['username']); ?>">Moderate</a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </section>
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
