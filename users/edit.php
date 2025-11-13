<?php
require_once __DIR__ . '/../includes/functions.php';
ensure_admin();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$id]);
$user = $stmt->fetch();
if (!$user) { echo 'User not found'; exit; }

$error = '';
if (isPost()) {
  $username = trim($_POST['username'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $role = ($_POST['role'] ?? 'user') === 'admin' ? 'admin' : 'user';
  if (!$username || !$email) {
    $error = 'Please fill all fields';
  } else {
    $u = $pdo->prepare("UPDATE users SET username=?, email=?, role=? WHERE id=?");
    $u->execute([$username, $email, $role, $id]);
    redirect('users/list.php');
  }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<main class="container">
  <h1>Edit User</h1>
  <?php if ($error) alert($error,'error'); ?>
  <form method="post">
    <label>Username <input name="username" value="<?php echo e($user['username']); ?>" required></label>
    <label>Email <input name="email" type="email" value="<?php echo e($user['email']); ?>" required></label>
    <label>Role
      <select name="role">
        <option value="user" <?php echo $user['role']==='user'?'selected':''; ?>>user</option>
        <option value="admin" <?php echo $user['role']==='admin'?'selected':''; ?>>admin</option>
      </select>
    </label>
    <button class="primary" type="submit">Save</button>
  </form>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
