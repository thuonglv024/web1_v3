<?php
require_once __DIR__ . '/../includes/functions.php';
ensure_admin();
$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
  $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
  $stmt->execute([$id]);
  $_SESSION['flash_message'] = 'User deleted successfully!';
  $_SESSION['flash_type'] = 'success';
}
// Redirect back to the previous page or fallback to list
if (isset($_SERVER['HTTP_REFERER'])) {
  header('Location: ' . $_SERVER['HTTP_REFERER']);
  exit;
}
redirect('users/list.php');
?>
