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

// Determine view mode: List of threads OR active conversation
$receiver_id = isset($_GET['receiver_id']) ? intval($_GET['receiver_id']) : 0;
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : null;

// Handle auto message initialization if coming from successful purchase
if (isset($_GET['auto_msg']) && $_GET['auto_msg'] == 1 && $receiver_id > 0 && $product_id > 0) {
    // Check if we already sent the system/purchase notification
    $stmt = $db->prepare("SELECT id FROM chat_messages WHERE sender_id = ? AND receiver_id = ? AND product_id = ? AND message LIKE 'I just purchased your product%'");
    $stmt->execute([$current_user_id, $receiver_id, $product_id]);
    if (!$stmt->fetch()) {
        // Send initial message
        $stmt = $db->prepare("INSERT INTO chat_messages (sender_id, receiver_id, product_id, message, is_read) VALUES (?, ?, ?, 'I just purchased your product! Let\'s coordinate delivery details.', 0)");
        $stmt->execute([$current_user_id, $receiver_id, $product_id]);
        
        // Auto reply simulation: Insert a seller reply
        $stmt = $db->prepare("INSERT INTO chat_messages (sender_id, receiver_id, product_id, message, is_read) VALUES (?, ?, ?, 'Awesome! Thank you for buying my item. We can meet near the library or the student union building tomorrow morning. What time suits you?', 0)");
        // The seller replies to current user
        $stmt->execute([$receiver_id, $current_user_id, $product_id]);
    }
    // Clean up query param
    header("Location: chat.php?receiver_id=" . $receiver_id . "&product_id=" . $product_id);
    exit;
}

// Handle sending message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message']) && $receiver_id > 0) {
    $message = trim($_POST['message']);
    if (!empty($message)) {
        $stmt = $db->prepare("INSERT INTO chat_messages (sender_id, receiver_id, product_id, message, is_read) VALUES (?, ?, ?, ?, 0)");
        $stmt->execute([$current_user_id, $receiver_id, $product_id, $message]);

        // Simulated auto-reply logic (if first conversation or keyword matched)
        $lower_msg = strtolower($message);
        $reply = '';
        if (strpos($lower_msg, 'price') !== false || strpos($lower_msg, 'nego') !== false) {
            $reply = "The price is already very fair, but we can negotiate a small student discount when we meet!";
        } elseif (strpos($lower_msg, 'meet') !== false || strpos($lower_msg, 'where') !== false || strpos($lower_msg, 'lib') !== false || strpos($lower_msg, 'perpus') !== false) {
            $reply = "Sure! The Central Library lawn or the lobby of the main building works best for me.";
        } elseif (strpos($lower_msg, 'hello') !== false || strpos($lower_msg, 'hi') !== false || strpos($lower_msg, 'ready') !== false) {
            $reply = "Hello! Yes, the item is still available and ready. Let's arrange a time to meet.";
        }

        if (!empty($reply)) {
            // Wait-less insert for simulation
            $stmt = $db->prepare("INSERT INTO chat_messages (sender_id, receiver_id, product_id, message, is_read) VALUES (?, ?, ?, ?, 0)");
            $stmt->execute([$receiver_id, $current_user_id, $product_id, $reply]);
        }

        header("Location: chat.php?receiver_id=" . $receiver_id . ($product_id ? "&product_id=" . $product_id : ""));
        exit;
    }
}

// If active conversation, fetch thread messages & mark received messages as read
if ($receiver_id > 0) {
    // Mark as read
    $stmt = $db->prepare("UPDATE chat_messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?");
    $stmt->execute([$receiver_id, $current_user_id]);

    // Fetch conversation messages
    $stmt = $db->prepare("SELECT * FROM chat_messages 
                          WHERE (sender_id = ? AND receiver_id = ?) 
                             OR (sender_id = ? AND receiver_id = ?) 
                          ORDER BY created_at ASC");
    $stmt->execute([$current_user_id, $receiver_id, $receiver_id, $current_user_id]);
    $messages = $stmt->fetchAll();

    // Fetch receiver details
    $stmt = $db->prepare("SELECT username, avatar, nim FROM users WHERE id = ?");
    $stmt->execute([$receiver_id]);
    $receiver_user = $stmt->fetch();

    // Fetch product context details if available
    $product_context = null;
    if ($product_id > 0) {
        $stmt = $db->prepare("SELECT name, price, image FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product_context = $stmt->fetch();
    }
} else {
    // Fetch conversations list with latest message summary
    // Query aggregates threads involving current user
    $sql = "SELECT u.id as contact_id, u.username as contact_name, u.avatar as contact_avatar, u.nim as contact_nim,
                   m.message as last_msg, m.created_at as last_time, m.sender_id as last_sender,
                   (SELECT COUNT(*) FROM chat_messages WHERE sender_id = u.id AND receiver_id = ? AND is_read = 0) as unread_count
            FROM users u
            JOIN (
                SELECT LEAST(sender_id, receiver_id) as val1, GREATEST(sender_id, receiver_id) as val2, MAX(created_at) as max_time
                FROM chat_messages
                WHERE sender_id = ? OR receiver_id = ?
                GROUP BY 1, 2
            ) t ON 1=1
            JOIN chat_messages m ON m.created_at = t.max_time AND (
                (m.sender_id = u.id AND m.receiver_id = ?) OR
                (m.sender_id = ? AND m.receiver_id = u.id)
            )
            WHERE u.id != ?
            ORDER BY m.created_at DESC";
            
    $stmt = $db->prepare($sql);
    $stmt->execute([$current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id]);
    $threads = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Secure in-app student chat messaging on Portalia">
  <title>Messages - Portalia</title>
  <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/vendors/bootstrap-icons/bootstrap-icons.css">
  <link rel="stylesheet" href="../assets/css/portalia.css">
  <style>
    .chat-header {
      position: sticky;
      top: 0;
      z-index: 100;
      background: var(--portalia-surface);
      padding: 12px 16px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid var(--portalia-bg);
    }
    .chat-header-user {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .chat-header-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
    }
    .chat-header-info h2 {
      font-size: 13px;
      margin: 0;
      font-weight: 700;
    }
    .chat-header-info span {
      font-size: 10px;
      color: var(--portalia-success);
      font-weight: 600;
    }
    .product-context-bar {
      background: var(--portalia-bg);
      padding: 8px 16px;
      display: flex;
      align-items: center;
      gap: 10px;
      border-bottom: 1px solid #E2E8F0;
    }
    .product-context-thumb {
      width: 32px;
      height: 32px;
      border-radius: 6px;
      object-fit: cover;
    }
    .chat-input-bar {
      position: fixed;
      bottom: 0;
      left: 50%;
      transform: translateX(-50%);
      width: 100%;
      max-width: 540px;
      background: var(--portalia-surface);
      border-top: 1px solid #E2E8F0;
      padding: 12px 16px;
      z-index: 999;
      display: flex;
      gap: 10px;
      align-items: center;
    }
    .chat-thread-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 16px;
      border-bottom: 1px solid var(--portalia-bg);
      text-decoration: none;
      color: inherit;
      transition: var(--portalia-transition);
    }
    .chat-thread-item:hover {
      background-color: var(--portalia-bg);
    }
    .thread-user-info {
      display: flex;
      align-items: center;
      gap: 12px;
      flex-grow: 1;
      min-width: 0;
    }
    .thread-avatar {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      object-fit: cover;
      position: relative;
    }
    .online-indicator {
      position: absolute;
      bottom: 0;
      right: 0;
      width: 12px;
      height: 12px;
      background: var(--portalia-success);
      border: 2px solid #FFFFFF;
      border-radius: 50%;
    }
    .thread-details {
      flex-grow: 1;
      min-width: 0;
    }
    .thread-header {
      display: flex;
      justify-content: space-between;
      align-items: baseline;
      margin-bottom: 4px;
    }
    .thread-name {
      font-size: 13px;
      font-weight: 700;
      margin: 0;
      color: var(--portalia-text-primary);
    }
    .thread-time {
      font-size: 10px;
      color: var(--portalia-text-secondary);
    }
    .thread-preview {
      font-size: 12px;
      color: var(--portalia-text-secondary);
      margin: 0;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .empty-threads {
      padding: 80px 24px;
      text-align: center;
    }
    .empty-threads-icon {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      background: #EFF6FF;
      color: var(--portalia-primary);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 36px;
      margin-bottom: 20px;
      box-shadow: 0 4px 12px rgba(79, 140, 255, 0.1);
    }
  </style>
</head>
<body>

  <div class="app-container">

    <!-- DESKTOP NAVBAR (visible on ≥992px) -->
    <?php include '_desktop_navbar.php'; ?>

    <?php if ($receiver_id > 0): ?>
      <main class="feature-page">
        <section class="page-card chat-conversation-card">
          <header class="chat-header">
            <div class="chat-header-user">
              <a href="chat.php" class="action-icon-btn me-1" aria-label="Back"><i class="bi bi-arrow-left"></i></a>
              <img src="../<?php echo sanitize($receiver_user['avatar']); ?>" alt="Avatar" class="chat-header-avatar" onerror="this.src='../assets/images/avatar/avatar.jpg'">
              <div class="chat-header-info">
                <h2><?php echo sanitize($receiver_user['username']); ?></h2>
                <span><i class="bi bi-dot" style="font-size: 20px; vertical-align: middle; line-height: 1;"></i>Active Now</span>
              </div>
            </div>
            <a href="index.php" class="action-icon-btn" aria-label="Marketplace"><i class="bi bi-shop"></i></a>
          </header>

          <?php if ($product_context): ?>
            <div class="product-context-bar">
              <?php if (!empty($product_context['image']) && file_exists(__DIR__ . '/../' . $product_context['image'])): ?>
                <img src="../<?php echo sanitize($product_context['image']); ?>" alt="Product" class="product-context-thumb">
              <?php else: ?>
                <div class="product-context-thumb bg-secondary text-white d-flex align-items-center justify-content-center" style="font-size: 12px;"><i class="bi bi-image"></i></div>
              <?php endif; ?>
              <div class="flex-grow-1 min-width-0">
                <span class="fw-semibold d-block text-truncate" style="font-size: 12px;"><?php echo sanitize($product_context['name']); ?></span>
                <span class="text-primary fw-bold" style="font-size: 11px;"><?php echo formatRupiah($product_context['price']); ?></span>
              </div>
            </div>
          <?php endif; ?>

          <main class="chat-bubble-container" id="chatBox" style="padding-bottom: 90px;">
            <?php if (count($messages) == 0): ?>
              <div class="empty-state">
                <div class="empty-state-icon">
                  <i class="bi bi-chat-dots"></i>
                </div>
                <h2>No messages yet</h2>
                <p>Send a greeting to start coordinating this campus transaction.</p>
              </div>
            <?php else: ?>
              <?php foreach ($messages as $msg): ?>
                <?php $is_me = ($msg['sender_id'] == $current_user_id); ?>
                <div class="chat-bubble <?php echo $is_me ? 'sent' : 'received'; ?>">
                  <?php echo sanitize($msg['message']); ?>
                  <span class="chat-time"><?php echo date('H:i', strtotime($msg['created_at'])); ?></span>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </main>
        </section>

        <form action="chat.php?receiver_id=<?php echo $receiver_id; ?><?php echo $product_id ? '&product_id=' . $product_id : ''; ?>" method="POST" class="chat-input-bar">
          <input type="text" name="message" class="input-portalia input-glow" placeholder="Type a message..." required autocomplete="off">
          <button type="submit" name="send_message" class="btn btn-portalia-primary" style="height: 52px; width: 52px; padding: 0 !important; border-radius: 50% !important; flex-shrink: 0;" aria-label="Send message">
            <i class="bi bi-send-fill" style="margin: 0;"></i>
          </button>
        </form>
      </main>

      <script>
        // Auto scroll messages to the bottom
        const box = document.getElementById('chatBox');
        box.scrollTop = box.scrollHeight;
      </script>

    <?php else: ?>
      <main class="feature-page">
        <section class="page-header-card">
          <div class="page-header-main">
            <span class="page-header-icon"><i class="bi bi-chat-dots-fill"></i></span>
            <div>
              <span class="page-header-eyebrow">Messages</span>
              <h1 class="page-header-title">Chats</h1>
              <p class="page-header-subtitle">Continue conversations with buyers and sellers.</p>
            </div>
          </div>
          <div class="page-header-actions">
            <a href="index.php" class="btn btn-portalia-secondary" style="height: 44px; padding: 0 18px !important; font-size: 13px;">
              <i class="bi bi-shop me-2"></i>Browse
            </a>
          </div>
        </section>

        <?php if (count($threads) == 0): ?>
          <section class="empty-state">
            <div class="empty-state-icon">
              <i class="bi bi-chat-heart"></i>
            </div>
            <h2>No chats yet</h2>
            <p>Start a conversation from a product detail page.</p>
            <a href="index.php" class="btn btn-portalia-primary">Browse Marketplace</a>
          </section>
        <?php else: ?>
          <section class="chat-inbox-layout">
            <div class="page-card chat-thread-list">
              <div class="chat-thread-list-header">
                <h2>Inbox</h2>
                <p><?php echo count($threads); ?> active conversations</p>
              </div>
              <div class="chat-thread-items">
                <?php foreach ($threads as $th): ?>
                  <a href="chat.php?receiver_id=<?php echo $th['contact_id']; ?>" class="chat-thread-item">
                    <div class="thread-user-info">
                      <div class="thread-avatar">
                        <img src="../<?php echo sanitize($th['contact_avatar']); ?>" alt="Avatar" class="w-100 h-100 rounded-circle" onerror="this.src='../assets/images/avatar/avatar.jpg'">
                        <span class="online-indicator"></span>
                      </div>
                      <div class="thread-details">
                        <div class="thread-header">
                          <h3 class="thread-name"><?php echo sanitize($th['contact_name']); ?></h3>
                          <span class="thread-time"><?php echo date('H:i', strtotime($th['last_time'])); ?></span>
                        </div>
                        <p class="thread-preview">
                          <?php if ($th['last_sender'] == $current_user_id): ?><span class="text-dark fw-semibold">You: </span><?php endif; ?>
                          <?php echo sanitize($th['last_msg']); ?>
                        </p>
                      </div>
                    </div>

                    <?php if ($th['unread_count'] > 0): ?>
                      <span class="badge rounded-pill bg-danger" style="font-size: 10px; padding: 4px 8px; margin-left: 8px;">
                        <?php echo $th['unread_count']; ?>
                      </span>
                    <?php endif; ?>
                  </a>
                <?php endforeach; ?>
              </div>
            </div>

            <aside class="page-card chat-preview-panel">
              <div>
                <div class="empty-state-icon">
                  <i class="bi bi-chat-square-text"></i>
                </div>
                <h2>Select a conversation</h2>
                <p>Open a thread to coordinate meeting points, item details, and delivery plans.</p>
              </div>
            </aside>
          </section>
        <?php endif; ?>

      <!-- FLOATING BOTTOM NAVIGATION -->
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
        <a href="chat.php" class="bottom-nav-item active">
          <i class="bi bi-chat-dots-fill"></i>
          <span>Chat</span>
        </a>
        <a href="profile.php" class="bottom-nav-item">
          <i class="bi bi-person"></i>
          <span>Profile</span>
        </a>
      </nav>
      </main>
    <?php endif; ?>

  </div>

  <script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
