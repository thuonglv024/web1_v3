<?php
require_once __DIR__ . '/../includes/functions.php';

ensure_admin();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM modules WHERE module_id = ?");
$stmt->execute([$id]);
$module = $stmt->fetch();
if (!$module) { echo 'Module not found'; exit; }

if (isPost()) {
  $code = sanitize($_POST['module_code'] ?? '');
  $name = sanitize($_POST['module_name'] ?? '');
  if ($code && $name) {
    $u = $pdo->prepare("UPDATE modules SET module_code=?, module_name=? WHERE module_id=?");
    $u->execute([$code, $name, $id]);
    redirect('modules/list.php');
  } else {
    $error = 'Please fill all fields.';
  }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<main class="container">
  <h1>Edit Module</h1>
  <?php if (!empty($error)) alert($error, 'error'); ?>
  <form method="post">
    <label>Code <input name="module_code" value="<?php echo e($module['module_code']); ?>" required></label>
    <label>Name <input name="module_name" value="<?php echo e($module['module_name']); ?>" required></label>
    <button class="primary" type="submit">Save</button>
  </form>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
