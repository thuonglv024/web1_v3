<?php
require_once __DIR__ . '/../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
  $stmt = $pdo->prepare("SELECT question_id, user_id FROM answers WHERE id=?");
  $stmt->execute([$id]);
  $row = $stmt->fetch();
  if ($row) {
    $qid = (int)$row['question_id'];
    if (is_admin() || (is_logged_in() && (int)$row['user_id'] === (int)$_SESSION['user_id'])) {
      $del = $pdo->prepare("DELETE FROM answers WHERE id=?");
      $del->execute([$id]);
      $_SESSION['flash_message'] = 'Answer deleted successfully!';
      $_SESSION['flash_type'] = 'success';
      // Redirect back to the previous page or fallback to question view
      if (isset($_SERVER['HTTP_REFERER'])) {
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
      }
      redirect('questions/view.php?id=' . $qid);
    } else {
      http_response_code(403);
      echo 'Forbidden';
      exit;
    }
  }
}
echo 'Answer not found';
?>
