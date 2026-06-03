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

// Fetch categories for select input
$stmt = $db->query("SELECT * FROM categories ORDER BY id ASC");
$categories = $stmt->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $category_id = intval($_POST['category_id']);
    $condition = $_POST['condition'];
    $stock = intval($_POST['stock']);
    $expiration_date = $_POST['expiration_date'];
    $price = floatval($_POST['price']);

    // Handle image upload
    $imagePath = null;
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $imagePath = uploadProductImage($_FILES['product_image']);
        if ($imagePath === null) {
            $error = 'Failed to upload image. Please ensure it is a valid JPEG/PNG/GIF/WebP image under 5MB.';
        }
    }

    if (empty($name) || empty($description) || $category_id <= 0 || empty($condition) || $stock <= 0 || empty($expiration_date) || $price <= 0) {
        $error = 'Please fill out all required fields with valid values.';
    }

    if (empty($error)) {
        try {
            $stmt = $db->prepare("INSERT INTO products (seller_id, category_id, name, description, price, image, item_condition, stock, status, expiration_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
            $stmt->execute([$current_user_id, $category_id, $name, $description, $price, $imagePath, $condition, $stock, $expiration_date]);
            
            header("Location: profile.php?tab=pending&uploaded=1");
            exit;
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="List a new product for sale in Portalia">
  <title>Upload Product - Portalia</title>
  <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/vendors/bootstrap-icons/bootstrap-icons.css">
  <link rel="stylesheet" href="../assets/css/portalia.css">
  <style>
    .upload-nav {
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
    .wizard-container {
      padding: 24px 16px;
    }
    .step-section {
      display: none;
    }
    .step-section.active {
      display: block;
    }
    .image-upload-area {
      border: 2px dashed #CBD5E1;
      border-radius: var(--portalia-radius-md);
      padding: 32px 16px;
      text-align: center;
      cursor: pointer;
      background: var(--portalia-bg);
      transition: var(--portalia-transition);
      position: relative;
    }
    .image-upload-area:hover {
      border-color: var(--portalia-primary);
      background: #F0F7FF;
    }
    .image-upload-area i {
      font-size: 36px;
      color: var(--portalia-text-secondary);
      margin-bottom: 8px;
    }
    .preview-box {
      display: none;
      width: 100%;
      height: 180px;
      border-radius: var(--portalia-radius-sm);
      overflow: hidden;
      margin-top: 12px;
      box-shadow: var(--portalia-shadow-soft);
    }
    .preview-box img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    /* Calculator card grouping */
    .calculator-card {
      background: linear-gradient(135deg, #F8FAFC 0%, #EFF6FF 100%);
      border: 1px solid rgba(79, 140, 255, 0.15);
    }
    .calculator-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px 0;
      border-bottom: 1px solid rgba(226, 232, 240, 0.6);
      font-size: 13px;
    }
    .calculator-row:last-child {
      border-bottom: none;
      padding-top: 16px;
    }
  </style>
</head>
<body>

  <div class="app-container">

    <!-- NAVIGATION -->
    <nav class="upload-nav">
      <div style="width: 40px;"></div>
      <span class="fw-bold" style="font-size: 16px;">Upload Product</span>
      <div style="width: 40px;"></div>
    </nav>

    <div class="wizard-container">
      
      <!-- WIZARD STEP WORKFLOW INDICATOR -->
      <div class="wizard-steps">
        <div class="wizard-step active" id="step-dot-1">1</div>
        <div class="wizard-step" id="step-dot-2">2</div>
        <div class="wizard-step" id="step-dot-3">3</div>
      </div>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger mb-4" style="border-radius: var(--portalia-radius-sm); font-size: 13px;">
          <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error; ?>
        </div>
      <?php endif; ?>

      <form action="upload.php" method="POST" enctype="multipart/form-data" id="uploadForm">
        
        <!-- STEP 1: IMAGES & BASIC INFO -->
        <div class="step-section active" id="step-1">
          <h2 style="font-size: 16px; font-weight: 700; margin-bottom: 16px;">Product Visuals & Name</h2>
          
          <div class="form-group-portalia">
            <label>Product Image</label>
            <div class="image-upload-area" onclick="document.getElementById('fileInput').click()">
              <i class="bi bi-cloud-arrow-up-fill"></i>
              <span class="d-block fw-semibold" style="font-size: 13px;">Click to select photo</span>
              <span class="text-muted d-block mt-1" style="font-size: 11px;">Supports JPEG, PNG, WebP up to 5MB</span>
              <input type="file" name="product_image" id="fileInput" accept="image/*" style="display: none;" required>
            </div>
            <div class="preview-box" id="previewBox">
              <img id="imgPreview" src="" alt="Product Preview">
            </div>
          </div>

          <div class="form-group-portalia">
            <label for="name">Listing Name</label>
            <input type="text" name="name" id="name" class="input-portalia input-glow" placeholder="e.g. Calculus Textbook, Keychron Keyboard" required>
          </div>

          <div class="form-group-portalia">
            <label for="description">Item Description</label>
            <textarea name="description" id="description" class="input-portalia input-glow" rows="4" placeholder="Detail condition, features, transaction method..." required></textarea>
          </div>

          <div class="d-flex justify-content-end mt-4">
            <button type="button" class="btn btn-portalia-primary" onclick="nextStep(1)">Next Step <i class="bi bi-arrow-right ms-2"></i></button>
          </div>
        </div>

        <!-- STEP 2: ATTRIBUTES -->
        <div class="step-section" id="step-2">
          <h2 style="font-size: 16px; font-weight: 700; margin-bottom: 16px;">Product Attributes</h2>

          <div class="form-group-portalia">
            <label for="category_id">Category</label>
            <select name="category_id" id="category_id" class="input-portalia input-glow" required style="background: url('data:image/svg+xml;charset=utf-8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%274%27 height=%275%27%3E%3Cpath fill=%27%236B7280%27 d=%27M2 0L0 2h4zm0 5L0 3h4z%27/%3E%3C/svg%3E') no-repeat right 16px center/8px 10px; background-color: var(--portalia-surface);">
              <option value="" disabled selected>Select category</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat['id']; ?>"><?php echo sanitize($cat['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group-portalia">
            <label for="condition">Condition</label>
            <select name="condition" id="condition" class="input-portalia input-glow" required style="background: url('data:image/svg+xml;charset=utf-8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%274%27 height=%275%27%3E%3Cpath fill=%27%236B7280%27 d=%27M2 0L0 2h4zm0 5L0 3h4z%27/%3E%3C/svg%3E') no-repeat right 16px center/8px 10px; background-color: var(--portalia-surface);">
              <option value="" disabled selected>Select item condition</option>
              <option value="new">Brand New</option>
              <option value="like_new">Like New</option>
              <option value="used">Used / Secondhand</option>
            </select>
          </div>

          <div class="row">
            <div class="col-6">
              <div class="form-group-portalia">
                <label for="stock">Stock Available</label>
                <input type="number" name="stock" id="stock" class="input-portalia input-glow" min="1" value="1" required>
              </div>
            </div>
            <div class="col-6">
              <div class="form-group-portalia">
                <label for="expiration_date">Listing Expiry Date</label>
                <input type="date" name="expiration_date" id="expiration_date" class="input-portalia input-glow" required value="<?php echo date('Y-m-d', strtotime('+3 months')); ?>">
              </div>
            </div>
          </div>

          <div class="d-flex justify-content-between mt-4">
            <button type="button" class="btn btn-portalia-secondary" onclick="prevStep(2)"><i class="bi bi-arrow-left me-2"></i> Back</button>
            <button type="button" class="btn btn-portalia-primary" onclick="nextStep(2)">Next Step <i class="bi bi-arrow-right ms-2"></i></button>
          </div>
        </div>

        <!-- STEP 3: FINANCIAL DETAILS & PRICE -->
        <div class="step-section" id="step-3">
          <h2 style="font-size: 16px; font-weight: 700; margin-bottom: 16px;">Product Pricing & Settings</h2>

          <div class="form-group-portalia">
            <label for="price">Selling Price (IDR)</label>
            <input type="number" name="price" id="price" class="input-portalia input-glow" placeholder="e.g. 50000" min="500" required>
          </div>

          <!-- Fintech-style card grouping -->
          <div class="card-portalia calculator-card mb-4">
            <h3 style="font-size: 13px; font-weight: 700; margin-bottom: 12px; color: var(--portalia-text-primary);"><i class="bi bi-calculator-fill me-2" style="color: var(--portalia-secondary);"></i>Income Calculator</h3>
            
            <div class="calculator-row">
              <span class="text-muted">Item Selling Price:</span>
              <span class="fw-semibold text-dark" id="calcPrice">Rp 0</span>
            </div>
            
            <div class="calculator-row">
              <span class="text-muted">Portalia Maintenance Fee (5%):</span>
              <span class="fw-semibold text-danger" id="calcFee">Rp 0</span>
            </div>

            <div class="calculator-row">
              <span class="fw-bold" style="font-size: 14px;">Your Net Earnings:</span>
              <span class="fw-bold text-success" style="font-size: 15px;" id="calcNet">Rp 0</span>
            </div>
          </div>

          <div class="d-flex justify-content-between mt-4">
            <button type="button" class="btn btn-portalia-secondary" onclick="prevStep(3)"><i class="bi bi-arrow-left me-2"></i> Back</button>
            <button type="submit" class="btn btn-portalia-primary" style="background: var(--portalia-gradient) !important;"><i class="bi bi-check-circle-fill me-2"></i> Publish Listing</button>
          </div>
        </div>

      </form>
    </div>

    <!-- BOTTOM NAVIGATION -->
    <nav class="bottom-nav">
      <a href="index.php" class="bottom-nav-item">
        <i class="bi bi-house-door"></i>
        <span>Home</span>
      </a>
      <a href="wishlist.php" class="bottom-nav-item">
        <i class="bi bi-heart"></i>
        <span>Wishlist</span>
      </a>
      <a href="upload.php" class="bottom-nav-item active">
        <i class="bi bi-plus-circle-fill"></i>
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
    // Handle step validations & screen toggles
    function nextStep(currentStep) {
      if (currentStep === 1) {
        const fileInput = document.getElementById('fileInput');
        const name = document.getElementById('name').value.trim();
        const desc = document.getElementById('description').value.trim();

        if (!fileInput.files.length) {
          alert("Please upload a product photo.");
          return;
        }
        if (!name || !desc) {
          alert("Please fill out the Listing Name and Description.");
          return;
        }
        
        showStep(2);
      } else if (currentStep === 2) {
        const category = document.getElementById('category_id').value;
        const condition = document.getElementById('condition').value;
        const stock = document.getElementById('stock').value;
        const expDate = document.getElementById('expiration_date').value;

        if (!category || !condition || !stock || !expDate) {
          alert("Please fill out all product attributes.");
          return;
        }

        showStep(3);
      }
    }

    function prevStep(currentStep) {
      showStep(currentStep - 1);
    }

    function showStep(stepNum) {
      // Hide all steps
      document.querySelectorAll('.step-section').forEach(sec => sec.classList.remove('active'));
      // Show active step
      document.getElementById(`step-${stepNum}`).classList.add('active');

      // Update wizard indicators
      document.querySelectorAll('.wizard-step').forEach((dot, idx) => {
        dot.classList.remove('active', 'completed');
        if (idx + 1 < stepNum) {
          dot.classList.add('completed');
        } else if (idx + 1 === stepNum) {
          dot.classList.add('active');
        }
      });
    }

    // Handle Image Preview
    document.getElementById('fileInput').addEventListener('change', function() {
      const file = this.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
          const previewBox = document.getElementById('previewBox');
          const imgPreview = document.getElementById('imgPreview');
          imgPreview.src = e.target.result;
          previewBox.style.display = 'block';
          document.querySelector('.image-upload-area').style.display = 'none';
        }
        reader.readAsDataURL(file);
      }
    });

    // Handle interactive pricing calculations (5% platform fee deduction)
    const priceInput = document.getElementById('price');
    priceInput.addEventListener('input', function() {
      const val = parseFloat(this.value) || 0;
      const fee = val * 0.05;
      const net = val - fee;

      document.getElementById('calcPrice').innerText = formatIDRCurrency(val);
      document.getElementById('calcFee').innerText = formatIDRCurrency(fee);
      document.getElementById('calcNet').innerText = formatIDRCurrency(net);
    });

    function formatIDRCurrency(amount) {
      return "Rp " + Math.round(amount).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }
  </script>
</body>
</html>
