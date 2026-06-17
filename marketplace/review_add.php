<?php
header('Content-Type: application/json');
require_once '../db.php';

if (!isAuthenticated()) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized. Please log in first.']);
    exit;
}

if ($_SESSION['user_id'] === 'guest') {
    echo json_encode(['status' => 'error', 'message' => 'Guest accounts cannot write reviews.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

if ($productId <= 0 || $rating < 1 || $rating > 5 || empty($comment)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid parameters or empty comment.']);
    exit;
}

$userId = $_SESSION['user_id'];
$db = getDB();

// Verify that the product exists
$stmt = $db->prepare("SELECT id FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    echo json_encode(['status' => 'error', 'message' => 'Product not found.']);
    exit;
}

try {
    // Insert the review
    $stmt = $db->prepare("INSERT INTO product_reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
    $stmt->execute([$productId, $userId, $rating, $comment]);

    // Fetch the inserted review details including user info for immediate display
    $userStmt = $db->prepare("SELECT username, avatar FROM users WHERE id = ?");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch();

    echo json_encode([
        'status' => 'success',
        'review' => [
            'username' => $user['username'],
            'avatar' => $user['avatar'],
            'rating' => $rating,
            'comment' => $comment,
            'date' => date('Y-m-d')
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
