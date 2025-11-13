<?php
require_once __DIR__ . '/../includes/functions.php';
ensure_admin();
$users = $pdo->query("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC")->fetchAll();
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<main class="container">
  <h1>Users</h1>
  <table>
    <thead>
      <tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Actions</th></tr>
    </thead>
    <tbody>
      <?php foreach($users as $u): ?>
        <tr>
          <td><?php echo (int)$u['id']; ?></td>
          <td><?php echo e($u['username']); ?></td>
          <td><?php echo e($u['email']); ?></td>
          <td><?php echo e($u['role']); ?></td>
          <td>
            <a href="<?php echo BASE_URL; ?>users/edit.php?id=<?php echo (int)$u['id']; ?>">Edit</a> |
            <a href="<?php echo BASE_URL; ?>users/delete.php?id=<?php echo (int)$u['id']; ?>" onclick="return confirm('Delete this user?')">Delete</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
