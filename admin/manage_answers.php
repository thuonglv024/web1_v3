<?php
require_once __DIR__ . '/../includes/functions.php';
ensure_admin();
$stmt = $pdo->query("SELECT a.id, a.created_at, u.username, q.title, q.id AS qid
                     FROM answers a
                     JOIN users u ON a.user_id=u.id
                     JOIN questions q ON a.question_id=q.id
                     ORDER BY a.created_at DESC");
$rows = $stmt->fetchAll();
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<main class="container home-light">
  <div class="layout-grid">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <section class="feed" style="grid-column:2/4;">
      <div class="feed-header mb-20">
        <h1>Manage Answers</h1>
      </div>
      <div class="card admin-table-card">
        <div class="admin-table-wrapper">
          <table class="admin-table">
            <thead>
              <tr>
                <th class="text-left">ID</th>
                <th class="text-left">User</th>
                <th class="text-left">Question</th>
                <th class="text-left">Created</th>
                <th class="text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($rows as $r): ?>
                <tr>
                  <td><?php echo (int)$r['id']; ?></td>
                  <td><?php echo e($r['username']); ?></td>
                  <td><a href="<?php echo BASE_URL; ?>questions/view.php?id=<?php echo (int)$r['qid']; ?>" class="link-primary"><?php echo e($r['title']); ?></a></td>
                  <td><?php echo date('d M Y H:i', strtotime($r['created_at'])); ?></td>
                  <td class="table-actions">
                    <a href="<?php echo BASE_URL; ?>answers/edit.php?id=<?php echo (int)$r['id']; ?>" class="tag-chip">Edit</a>
                    <a href="<?php echo BASE_URL; ?>answers/delete.php?id=<?php echo (int)$r['id']; ?>" class="tag-chip" style="border-color:#dc2626;" onclick="return confirm('Delete this answer?')">Delete</a>
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
