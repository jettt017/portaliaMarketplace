<?php
require_once '../db.php';
requireAdmin();

$db = getDB();
$admin = getCurrentUser();

$error = '';
$success = '';

// Handle Delete Category
if (isset($_GET['delete_id'])) {
    $deleteId = intval($_GET['delete_id']);
    try {
        $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$deleteId]);
        $success = "Category deleted successfully!";
    } catch (PDOException $e) {
        $error = "Failed to delete category. Ensure no products are currently associated with it: " . $e->getMessage();
    }
}

// Handle Add Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name']);
    $icon = trim($_POST['icon']);

    if (empty($name) || empty($icon)) {
        $error = "All fields are required to create a category.";
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO categories (name, icon) VALUES (?, ?)");
            $stmt->execute([$name, $icon]);
            $success = "Category '{$name}' created successfully!";
        } catch (PDOException $e) {
            $error = "Failed to create category: " . $e->getMessage();
        }
    }
}

// Handle Edit Category Form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category'])) {
    $categoryId = intval($_POST['category_id']);
    $name = trim($_POST['name']);
    $icon = trim($_POST['icon']);

    if (empty($name) || empty($icon) || $categoryId <= 0) {
        $error = "All fields are required to update the category.";
    } else {
        try {
            $stmt = $db->prepare("UPDATE categories SET name = ?, icon = ? WHERE id = ?");
            $stmt->execute([$name, $icon, $categoryId]);
            $success = "Category '{$name}' updated successfully!";
            // Redirect to clean parameters
            header("Location: categories.php?success=" . urlencode($success));
            exit;
        } catch (PDOException $e) {
            $error = "Failed to update category: " . $e->getMessage();
        }
    }
}

// Get success from redirect if applicable
if (isset($_GET['success'])) {
    $success = $_GET['success'];
}

// Check if currently editing a category
$edit_mode = false;
$edit_category = null;
if (isset($_GET['edit_id'])) {
    $editId = intval($_GET['edit_id']);
    $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$editId]);
    $edit_category = $stmt->fetch();
    if ($edit_category) {
        $edit_mode = true;
    }
}

// Fetch all categories
$stmt = $db->query("SELECT c.*, 
                           (SELECT COUNT(*) FROM products WHERE category_id = c.id) as product_count 
                    FROM categories c 
                    ORDER BY c.id ASC");
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Category Management | Portalia</title>
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
        <a class="nav-link active" href="categories.php" aria-current="page">
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

          <span class="navbar-text ms-3 fw-semibold text-primary">Category Management</span>

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
              <span class="page-icon"><i class="bi bi-tags" aria-hidden="true"></i></span>
              <div>
                <p class="eyebrow mb-1">Categories</p>
                <h1 class="h3 mb-1">Category CRUD Management</h1>
                <p class="text-muted mb-0">Create, Read, Update, and Delete marketplace product category filters.</p>
              </div>
            </div>
          </div>

          <!-- NOTIFICATIONS -->
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

          <div class="row g-3 mt-1">
            <!-- LEFT COLUMN: CATEGORIES TABLE LIST -->
            <div class="col-12 col-lg-8">
              <div class="panel">
                <h2 class="h5 mb-3 section-title"><i class="bi bi-list-task" aria-hidden="true"></i><span>Category Listing</span></h2>
                <div class="table-responsive">
                  <table class="table align-middle mb-0">
                    <thead>
                      <tr>
                        <th scope="col" style="width: 80px;">ID</th>
                        <th scope="col" style="width: 80px;">Icon</th>
                        <th scope="col">Category Name</th>
                        <th scope="col">Products Count</th>
                        <th scope="col" class="text-end">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($categories as $cat): ?>
                        <tr>
                          <td><span class="fw-semibold text-muted">#<?php echo $cat['id']; ?></span></td>
                          <td>
                            <div class="bg-light d-inline-flex align-items-center justify-content-center rounded" style="width: 40px; height: 40px; color: var(--portalia-primary);">
                              <i class="bi <?php echo sanitize($cat['icon']); ?>" style="font-size: 18px;"></i>
                            </div>
                          </td>
                          <td><span class="fw-bold"><?php echo sanitize($cat['name']); ?></span></td>
                          <td>
                            <span class="badge bg-secondary"><?php echo $cat['product_count']; ?> products</span>
                          </td>
                          <td class="text-end">
                            <div class="d-inline-flex gap-2">
                              <a href="categories.php?edit_id=<?php echo $cat['id']; ?>" class="btn btn-light btn-sm">
                                <i class="bi bi-pencil-square"></i> Edit
                              </a>
                              <a href="categories.php?delete_id=<?php echo $cat['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this category? Active products under it might encounter errors.');">
                                <i class="bi bi-trash"></i> Delete
                              </a>
                            </div>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <!-- RIGHT COLUMN: FORM CRUD (ADD/EDIT) -->
            <div class="col-12 col-lg-4">
              <div class="panel">
                <?php if ($edit_mode): ?>
                  <h2 class="h5 mb-3 section-title"><i class="bi bi-pencil-square text-warning" aria-hidden="true"></i><span>Edit Category</span></h2>
                  
                  <form action="categories.php" method="POST">
                    <input type="hidden" name="category_id" value="<?php echo $edit_category['id']; ?>">
                    
                    <div class="mb-3">
                      <label for="name" class="form-label small fw-bold text-muted">Category Name</label>
                      <input type="text" class="form-control" name="name" id="name" required value="<?php echo sanitize($edit_category['name']); ?>">
                    </div>

                    <div class="mb-3">
                      <label for="icon" class="form-label small fw-bold text-muted">Bootstrap Icon Code</label>
                      <input type="text" class="form-control" name="icon" id="icon" required value="<?php echo sanitize($edit_category['icon']); ?>">
                      <small class="text-muted" style="font-size: 11px;">e.g. <code>bi-laptop</code>, <code>bi-book-half</code>, <code>bi-tags-fill</code></small>
                    </div>

                    <div class="d-flex gap-2">
                      <button type="submit" name="edit_category" class="btn btn-primary btn-sm flex-grow-1"><i class="bi bi-check-lg"></i> Update</button>
                      <a href="categories.php" class="btn btn-outline-secondary btn-sm flex-grow-1">Cancel</a>
                    </div>
                  </form>
                <?php else: ?>
                  <h2 class="h5 mb-3 section-title"><i class="bi bi-plus-circle-fill text-success" aria-hidden="true"></i><span>Add Category</span></h2>
                  
                  <form action="categories.php" method="POST">
                    <div class="mb-3">
                      <label for="name" class="form-label small fw-bold text-muted">Category Name</label>
                      <input type="text" class="form-control" name="name" id="name" placeholder="e.g. Course Notes, Rent Equipment" required>
                    </div>

                    <div class="mb-3">
                      <label for="icon" class="form-label small fw-bold text-muted">Bootstrap Icon Code</label>
                      <input type="text" class="form-control" name="icon" id="icon" placeholder="e.g. bi-tags" required>
                      <small class="text-muted" style="font-size: 11px;">Find codes on <a href="https://icons.getbootstrap.com/" target="_blank">icons.getbootstrap.com</a></small>
                    </div>

                    <button type="submit" name="add_category" class="btn btn-primary btn-sm w-100"><i class="bi bi-plus-lg"></i> Save Category</button>
                  </form>
                <?php endif; ?>
              </div>
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
