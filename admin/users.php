<?php
require_once '../db.php';
requireAdmin();

$db = getDB();
$admin = getCurrentUser();

$error = '';
$success = '';

// Handle Moderation actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = intval($_GET['id']);

    if ($id === $admin['id']) {
        $error = "You cannot suspend or delete your own administrator account!";
    } else {
        try {
            if ($action === 'suspend') {
                $stmt = $db->prepare("UPDATE users SET status = 'suspended' WHERE id = ?");
                $stmt->execute([$id]);
                $success = "Student account suspended successfully.";
            } elseif ($action === 'unsuspend') {
                $stmt = $db->prepare("UPDATE users SET status = 'active' WHERE id = ?");
                $stmt->execute([$id]);
                $success = "Student account activated successfully.";
            } elseif ($action === 'delete') {
                $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$id]);
                $success = "User account deleted successfully.";
            }
        } catch (PDOException $e) {
            $error = "Operation failed: " . $e->getMessage();
        }
    }
}

// Fetch users with search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sql = "SELECT u.*, 
               (SELECT COUNT(*) FROM products WHERE seller_id = u.id) as product_count 
        FROM users u 
        WHERE u.role = 'student'";

$params = [];
if (!empty($search)) {
    $sql .= " AND (u.username LIKE ? OR u.email LIKE ? OR u.nim LIKE ?)";
    $params = ['%' . $search . '%', '%' . $search . '%', '%' . $search . '%'];
}

$sql .= " ORDER BY u.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Management | Portalia</title>
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
        <a class="nav-link active" href="users.php" aria-current="page">
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

          <span class="navbar-text ms-3 fw-semibold text-primary">User Directory Management</span>

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
              <span class="page-icon"><i class="bi bi-people" aria-hidden="true"></i></span>
              <div>
                <p class="eyebrow mb-1">Users</p>
                <h1 class="h3 mb-1">User Management</h1>
                <p class="text-muted mb-0">Monitor and moderate registered campus student profiles, suspend accounts, or remove active listings.</p>
              </div>
            </div>
          </div>

          <!-- NOTIFICATION LOGS -->
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

          <!-- SEARCH TOOL -->
          <div class="panel mb-3">
            <form action="users.php" method="GET" class="row g-2 align-items-center">
              <div class="col-12 col-md-4">
                <input class="form-control" type="text" name="search" placeholder="Search by name, email, or NIM..." value="<?php echo sanitize($search); ?>">
              </div>
              <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search"></i> Search</button>
              </div>
              <?php if (!empty($search)): ?>
                <div class="col-auto">
                  <a href="users.php" class="btn btn-outline-secondary btn-sm">Clear Filter</a>
                </div>
              <?php endif; ?>
            </form>
          </div>

          <!-- USERS TABLE -->
          <div class="panel">
            <div class="table-responsive">
              <table class="table align-middle mb-0">
                <thead>
                  <tr>
                    <th scope="col">User Details</th>
                    <th scope="col">Student ID (NIM)</th>
                    <th scope="col">Contact Info</th>
                    <th scope="col">Total Listings</th>
                    <th scope="col">Account Status</th>
                    <th scope="col" class="text-end">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (count($users) == 0): ?>
                    <tr>
                      <td colspan="6" class="text-center text-muted py-4">No student records found matching the criteria.</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($users as $u): ?>
                      <tr>
                        <td>
                          <div class="d-flex align-items-center gap-2">
                            <img class="avatar-img avatar-sm" src="../<?php echo sanitize($u['avatar']); ?>" alt="Student Avatar" onerror="this.src='../assets/images/avatar/avatar.jpg'">
                            <div>
                              <p class="fw-semibold mb-0"><?php echo sanitize($u['username']); ?></p>
                              <small class="text-muted"><?php echo sanitize($u['email']); ?></small>
                            </div>
                          </div>
                        </td>
                        <td><span class="fw-semibold"><?php echo sanitize($u['nim']); ?></span></td>
                        <td>
                          <span class="d-block" style="font-size: 13px;"><i class="bi bi-telephone-fill me-1 text-muted"></i><?php echo sanitize($u['phone']); ?></span>
                        </td>
                        <td>
                          <span class="badge bg-secondary"><?php echo $u['product_count']; ?> uploaded</span>
                        </td>
                        <td>
                          <span class="badge <?php echo $u['status'] === 'active' ? 'bg-success' : 'bg-danger'; ?>">
                            <?php echo ucfirst($u['status']); ?>
                          </span>
                        </td>
                        <td class="text-end">
                          <div class="d-inline-flex gap-2">
                            <?php if ($u['status'] === 'active'): ?>
                              <a href="users.php?action=suspend&id=<?php echo $u['id']; ?>" class="btn btn-warning btn-sm" onclick="return confirm('Are you sure you want to suspend this student account?');">
                                <i class="bi bi-slash-circle"></i> Suspend
                              </a>
                            <?php else: ?>
                              <a href="users.php?action=unsuspend&id=<?php echo $u['id']; ?>" class="btn btn-success btn-sm">
                                <i class="bi bi-check-circle"></i> Activate
                              </a>
                            <?php endif; ?>
                            
                            <a href="users.php?action=delete&id=<?php echo $u['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this student account permanently? This will delete all their listings.');">
                              <i class="bi bi-trash"></i> Delete
                            </a>
                          </div>
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
