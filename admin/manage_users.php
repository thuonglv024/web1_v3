<?php
// Include functions and ensure admin access
require_once __DIR__ . '/../includes/functions.php';
ensure_admin();

// Fetch all users ordered by ID ascending
$users = $pdo->query("SELECT id, username, email, role, created_at FROM users ORDER BY id ASC")->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<main class="container home-dark">
  <div class="layout-grid">
    <!-- Include admin sidebar -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <section class="feed" style="grid-column:2/4;">
      <div class="feed-header mb-20">
        <h1>Manage Users</h1>
      </div>
      <!-- Users table -->
      <div class="card admin-table-card">
        <div class="admin-table-wrapper">
          <table class="admin-table">
            <thead>
              <tr>
                <th class="text-left">ID</th>
                <th class="text-left">USERNAME</th>
                <th class="text-left">EMAIL</th>
                <th class="text-left">ROLE</th>
                <th class="text-center">ACTIONS</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($users as $u): ?>
                <tr>
                  <td><?php echo (int)$u['id']; ?></td>
                  <td><?php echo e($u['username']); ?></td>
                  <td><?php echo e($u['email']); ?></td>
                  <td>
                    <?php
                      $roleClass = 'role-badge--user';
                      if ($u['role'] === 'admin') {
                        $roleClass = 'role-badge--admin';
                      } elseif ($u['role'] === 'moderator') {
                        $roleClass = 'role-badge--moderator';
                      }
                    ?>
                    <span class="role-badge <?php echo $roleClass; ?>"><?php echo e($u['role']); ?></span>
                  </td>
                  <td class="table-actions">
                    <a href="<?php echo BASE_URL; ?>users/edit.php?id=<?php echo (int)$u['id']; ?>" class="tag-chip">Edit</a>
                    <a href="<?php echo BASE_URL; ?>users/delete.php?id=<?php echo (int)$u['id']; ?>" class="tag-chip" style="border-color:#dc2626;" onclick="return confirm('Delete this user?')">Delete</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
