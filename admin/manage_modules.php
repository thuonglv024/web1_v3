<?php
require_once __DIR__ . '/../includes/functions.php';
ensure_admin();

if (isPost()) {
  $code = sanitize($_POST['module_code'] ?? '');
  $name = sanitize($_POST['module_name'] ?? '');
  if ($code && $name) {
    try {
      $stmt = $pdo->prepare("INSERT INTO modules (module_code, module_name) VALUES (?, ?)");
      $stmt->execute([$code, $name]);
      $success = 'Module added successfully!';
    } catch (PDOException $e) {
      if ($e->getCode() == 23000) {
        $error = 'Module code already exists!';
      } else {
        $error = 'Error adding module.';
      }
    }
  } else {
    $error = 'Please fill all fields.';
  }
}

$modules = $pdo->query("SELECT module_id, module_code, module_name, created_at FROM modules ORDER BY module_name")->fetchAll();


include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<main class="container home-light">
  <div class="layout-grid">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <section class="feed" style="grid-column:2/4;">
      <div class="feed-header">
        <h1>Manage Modules</h1>
      </div>

      <?php if (!empty($success)) alert($success, 'success'); ?>
      <?php if (!empty($error)) alert($error, 'error'); ?>

      <!-- Add New Module Form -->
      <div class="card" style="margin-bottom:20px;">
        <h3 style="margin:0 0 14px;">Add New Module</h3>
        <form method="post" style="display:grid;grid-template-columns:1fr 2fr auto;gap:12px;align-items:end;">
          <div>
            <label style="margin:0 0 6px;display:block;font-size:13px;color:#9ca3af;">Module Code</label>
            <input name="module_code" placeholder="e.g. COMP1841" required style="width:100%;">
          </div>
          <div>
            <label style="margin:0 0 6px;display:block;font-size:13px;color:#9ca3af;">Module Name</label>
            <input name="module_name" placeholder="e.g. Web Development" required style="width:100%;">
          </div>
          <button class="btn-ask" type="submit" style="padding:10px 20px;">Add Module</button>
        </form>
      </div>

      <!-- Modules List -->
      <div class="card admin-table-card">
        <h3 style="margin:0 0 14px;">All Modules (<?php echo count($modules); ?>)</h3>
        <?php if (!$modules): ?>
          <p style="color:#9ca3af;">No modules yet.</p>
        <?php else: ?>
          <div class="admin-table-wrapper">
            <table class="admin-table">
              <thead>
                <tr>
                  <th class="text-left">ID</th>
                  <th class="text-left">Code</th>
                  <th class="text-left">Name</th>
                  <th class="text-left">Created</th>
                  <th class="text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($modules as $m): ?>
                  <tr>
                    <td><?php echo (int)$m['module_id']; ?></td>
                    <td>
                      <span class="module-badge">
                        <?php echo e($m['module_code']); ?>
                      </span>
                    </td>
                    <td><?php echo e($m['module_name']); ?></td>
                    <td><?php echo date('d M Y', strtotime($m['created_at'])); ?></td>
                    <td class="table-actions">
                      <a href="<?php echo BASE_URL; ?>modules/edit.php?id=<?php echo (int)$m['module_id']; ?>" class="tag-chip tag-chip-small">Edit</a>
                      <a href="<?php echo BASE_URL; ?>modules/delete.php?id=<?php echo (int)$m['module_id']; ?>" class="tag-chip tag-chip-small tag-chip-delete" onclick="return confirm('Delete this module? This will affect all questions associated with it.')">Delete</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </section>
  </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
