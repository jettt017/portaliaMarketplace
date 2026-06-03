<?php
require_once '../db.php';
requireAdmin();

$db = getDB();
$admin = getCurrentUser();

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Set headers to trigger file download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=portalia_transactions_report_' . date('Y-m-d') . '.csv');

    // Create a file pointer connected to the output stream
    $output = fopen('php://output', 'w');

    // Output the column headings
    fputcsv($output, ['Transaction ID', 'Date & Time', 'Buyer Name', 'Buyer NIM', 'Seller Name', 'Seller NIM', 'Product Name', 'Price (IDR)', 'Admin Fee (IDR)', 'Net Amount (IDR)']);

    // Fetch transactions
    $stmt = $db->query("SELECT t.id, t.created_at, t.price, t.admin_fee, t.net_amount,
                               b.username as buyer_name, b.nim as buyer_nim,
                               s.username as seller_name, s.nim as seller_nim,
                               p.name as product_name
                        FROM transactions t
                        JOIN users b ON t.buyer_id = b.id
                        JOIN users s ON t.seller_id = s.id
                        JOIN products p ON t.product_id = p.id
                        ORDER BY t.created_at DESC");
    
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['id'],
            $row['created_at'],
            $row['buyer_name'],
            $row['buyer_nim'],
            $row['seller_name'],
            $row['seller_nim'],
            $row['product_name'],
            $row['price'],
            $row['admin_fee'],
            $row['net_amount']
        ]);
    }
    
    fclose($output);
    exit;
}

// Fetch stats for cards
$total_gmv = $db->query("SELECT SUM(price) FROM transactions")->fetchColumn();
if (!$total_gmv) $total_gmv = 0;

$total_revenue = $db->query("SELECT SUM(admin_fee) FROM transactions")->fetchColumn();
if (!$total_revenue) $total_revenue = 0;

$total_volume = $db->query("SELECT COUNT(*) FROM transactions")->fetchColumn();

// Fetch transactions details list
$stmt = $db->query("SELECT t.*, 
                           b.username as buyer_name, b.nim as buyer_nim,
                           s.username as seller_name, s.nim as seller_nim,
                           p.name as product_name 
                    FROM transactions t 
                    JOIN users b ON t.buyer_id = b.id 
                    JOIN users s ON t.seller_id = s.id 
                    JOIN products p ON t.product_id = p.id 
                    ORDER BY t.created_at DESC");
$transactions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reports & Monitoring | Portalia</title>
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
        <a class="nav-link" href="products.php">
          <span class="nav-icon"><i class="bi bi-shop" aria-hidden="true"></i></span>
          <span class="nav-text">Products Moderation</span>
        </a>
        <a class="nav-link" href="categories.php">
          <span class="nav-icon"><i class="bi bi-tags" aria-hidden="true"></i></span>
          <span class="nav-text">Categories CRUD</span>
        </a>
        <a class="nav-link active" href="reports.php" aria-current="page">
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

          <span class="navbar-text ms-3 fw-semibold text-primary">Financial & System Reports</span>

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
              <span class="page-icon"><i class="bi bi-graph-up-arrow" aria-hidden="true"></i></span>
              <div>
                <p class="eyebrow mb-1">Monitoring</p>
                <h1 class="h3 mb-1">Reports & Logs</h1>
                <p class="text-muted mb-0">Track completed transaction history, calculate maintenance fees, and export data logs.</p>
              </div>
            </div>
            <div class="heading-actions">
              <a href="reports.php?export=csv" class="btn btn-primary btn-sm"><i class="bi bi-download"></i> Export CSV Report</a>
            </div>
          </div>

          <!-- SUMMARY CARDS -->
          <section class="row g-3 mt-1" aria-label="Financial summaries">
            <div class="col-12 col-md-4">
              <article class="metric-card metric-primary">
                <div class="metric-top">
                  <span class="metric-label">Gross Merchandise Value (GMV)</span>
                  <span class="metric-icon"><i class="bi bi-cart-check" aria-hidden="true"></i></span>
                </div>
                <div class="metric-value"><?php echo formatRupiah($total_gmv); ?></div>
                <div class="metric-meta">
                  <span>Total trading volume in student portal</span>
                </div>
              </article>
            </div>

            <div class="col-12 col-md-4">
              <article class="metric-card metric-success">
                <div class="metric-top">
                  <span class="metric-label">Platform Admin Revenue</span>
                  <span class="metric-icon"><i class="bi bi-bank" aria-hidden="true"></i></span>
                </div>
                <div class="metric-value text-success"><?php echo formatRupiah($total_revenue); ?></div>
                <div class="metric-meta">
                  <span>From 5% transaction fees</span>
                </div>
              </article>
            </div>

            <div class="col-12 col-md-4">
              <article class="metric-card metric-warning">
                <div class="metric-top">
                  <span class="metric-label">Transaction Volume</span>
                  <span class="metric-icon"><i class="bi bi-receipt" aria-hidden="true"></i></span>
                </div>
                <div class="metric-value text-warning"><?php echo $total_volume; ?></div>
                <div class="metric-meta">
                  <span>Completed order invoices</span>
                </div>
              </article>
            </div>
          </section>

          <!-- TRANSACTIONS LOGS TABLE -->
          <section class="panel mt-4">
            <h2 class="h5 mb-3 section-title"><i class="bi bi-list-task" aria-hidden="true"></i><span>Transaction Invoices Log</span></h2>
            <div class="table-responsive">
              <table class="table align-middle mb-0">
                <thead>
                  <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Invoice Date</th>
                    <th scope="col">Product Item</th>
                    <th scope="col">Buyer</th>
                    <th scope="col">Seller</th>
                    <th scope="col">Gross Price</th>
                    <th scope="col">Admin Fee</th>
                    <th scope="col">Net Earnings</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (count($transactions) == 0): ?>
                    <tr>
                      <td colspan="8" class="text-center text-muted py-4">No transactions logged in the database yet.</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($transactions as $t): ?>
                      <tr>
                        <td><span class="fw-semibold text-muted">#<?php echo $t['id']; ?></span></td>
                        <td style="font-size: 13px;"><?php echo date('d M Y, H:i', strtotime($t['created_at'])); ?></td>
                        <td><span class="fw-bold text-dark"><?php echo sanitize($t['product_name']); ?></span></td>
                        <td>
                          <span class="d-block fw-semibold" style="font-size: 13px;"><?php echo sanitize($t['buyer_name']); ?></span>
                          <small class="text-muted d-block" style="font-size: 11px;">NIM: <?php echo sanitize($t['buyer_nim']); ?></small>
                        </td>
                        <td>
                          <span class="d-block fw-semibold" style="font-size: 13px;"><?php echo sanitize($t['seller_name']); ?></span>
                          <small class="text-muted d-block" style="font-size: 11px;">NIM: <?php echo sanitize($t['seller_nim']); ?></small>
                        </td>
                        <td><span class="fw-bold"><?php echo formatRupiah($t['price']); ?></span></td>
                        <td><span class="fw-semibold text-danger">-<?php echo formatRupiah($t['admin_fee']); ?></span></td>
                        <td><span class="fw-bold text-success"><?php echo formatRupiah($t['net_amount']); ?></span></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
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
