<?php
require_once __DIR__ . '/../includes/functions.php';
$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
  // Load question to check ownership
  $q = $pdo->prepare("SELECT user_id FROM questions WHERE id=?");
  $q->execute([$id]);
  $row = $q->fetch();
  if ($row && (is_admin() || (is_logged_in() && (int)$row['user_id'] === (int)$_SESSION['user_id']))) {
    $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['flash_message'] = 'Question deleted successfully!';
    $_SESSION['flash_type'] = 'success';
  } else {
    http_response_code(403);
    echo 'Forbidden';
    exit;
  }
}
// Redirect back to the previous page or fallback to list
if (isset($_SERVER['HTTP_REFERER'])) {
  header('Location: ' . $_SERVER['HTTP_REFERER']);
  exit;
}
redirect('questions/list.php');
?>
