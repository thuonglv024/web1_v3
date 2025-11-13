<?php
require_once __DIR__ . '/../includes/functions.php';

ensure_admin();

if (isPost()) {
  $code = sanitize($_POST['module_code'] ?? '');
  $name = sanitize($_POST['module_name'] ?? '');
  if ($code && $name) {
    $stmt = $pdo->prepare("INSERT INTO modules (module_code, module_name) VALUES (?, ?)");
    $stmt->execute([$code, $name]);
    redirect('modules/list.php');
  } else {
    $error = 'Please fill all fields.';
  }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<main class="container">
  <h1>Add Module</h1>
  <?php if (!empty($error)) alert($error, 'error'); ?>
  <form method="post">
    <label>Code <input name="module_code" required></label>
    <label>Name <input name="module_name" required></label>
    <button class="primary" type="submit">Save</button>
  </form>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
