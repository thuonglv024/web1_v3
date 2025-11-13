<?php
require_once __DIR__ . '/../includes/functions.php';

$error = '';
$qid = (int)($_GET['question_id'] ?? $_POST['question_id'] ?? 0);

ensure_login();

if (isPost()) {
  $content = trim($_POST['content'] ?? '');
  $user_id = (int)($_SESSION['user_id'] ?? 1);
  if ($qid <= 0 || $content === '') {
    $error = 'Please provide content.';
  } else {
    $stmt = $pdo->prepare("INSERT INTO answers (question_id, user_id, content) VALUES (?,?,?)");
    $stmt->execute([$qid, $user_id, $content]);
    redirect('questions/view.php?id=' . $qid);
  }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<main class="container">
  <h1>Add Answer</h1>
  <?php if ($error) alert($error, 'error'); ?>
  <form method="post">
    <input type="hidden" name="question_id" value="<?php echo (int)$qid; ?>" />
    <label>Content <textarea name="content" rows="5" required><?php echo e($_POST['content'] ?? ''); ?></textarea></label>
    <button class="primary" type="submit">Submit</button>
  </form>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
