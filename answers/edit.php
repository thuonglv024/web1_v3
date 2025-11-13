<?php
require_once __DIR__ . '/../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM answers WHERE id=?");
$stmt->execute([$id]);
$ans = $stmt->fetch();
if (!$ans) { echo 'Answer not found'; exit; }

// Owner-or-admin gate
if (!is_admin() && (!is_logged_in() || (int)$_SESSION['user_id'] !== (int)$ans['user_id'])) { http_response_code(403); echo 'Forbidden'; exit; }

$error = '';
if (isPost()) {
  $content = trim($_POST['content'] ?? '');
  if ($content === '') {
    $error = 'Content is required.';
  } else {
    $u = $pdo->prepare("UPDATE answers SET content=?, updated_at=NOW() WHERE id=?");
    $u->execute([$content, $id]);
    redirect('questions/view.php?id=' . (int)$ans['question_id']);
  }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<main class="container">
  <h1>Edit Answer</h1>
  <?php if ($error) alert($error, 'error'); ?>
  <form method="post">
    <label>Content <textarea name="content" rows="5" required><?php echo e($ans['content']); ?></textarea></label>
    <button class="primary" type="submit">Save</button>
  </form>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
