<?php
require_once __DIR__ . '/../includes/functions.php';
ensure_admin();

if (isPost()) {
  $name = sanitize($_POST['name'] ?? '');
  if ($name) {
    $ins = $pdo->prepare("INSERT INTO tags (name) VALUES (?) ON DUPLICATE KEY UPDATE name=name");
    $ins->execute([$name]);
    redirect('admin/manage_tags.php');
  }
}

$tags = $pdo->query("SELECT id, name FROM tags ORDER BY name")->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<main class="container home-dark">
  <div class="layout-grid">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <section class="feed" style="grid-column:2/4;">
      <div class="feed-header">
        <h1>Manage Tags</h1>
      </div>
      
      <div class="card tag-manager" style="margin-bottom:24px;">
        <h3 style="margin:0 0 16px;color:var(--text-primary);">Add New Tag</h3>
        <form method="post" class="tag-form">
          <div class="form-group">
            <label for="tagName">Tag Name</label>
            <input type="text" id="tagName" name="name" placeholder="e.g. javascript" required>
          </div>
          <button type="submit" class="btn-primary">
            <i class="fas fa-plus"></i> Add Tag
          </button>
        </form>
      </div>
      
      <div class="card admin-table-card">
        <h3 style="margin:0 0 20px;color:var(--text-primary);">All Tags</h3>
        <?php if (empty($tags)): ?>
          <div class="text-center" style="padding:40px 20px;color:var(--text-muted);">
            <i class="fas fa-tags" style="font-size:32px;margin-bottom:12px;opacity:0.6;"></i>
            <p style="margin:0;font-size:15px;">No tags found. Add your first tag above.</p>
          </div>
        <?php else: ?>
          <div class="admin-table-wrapper">
            <table class="admin-table">
              <thead>
                <tr>
                  <th class="text-left" style="width:80px;">ID</th>
                  <th class="text-left">Tag Name</th>
                  <th class="text-center" style="width:120px;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($tags as $t): ?>
                  <tr>
                    <td class="text-muted">#<?php echo (int)$t['id']; ?></td>
                    <td>
                      <span class="tag-chip">
                        <i class="fas fa-tag" style="margin-right:6px;opacity:0.7;"></i>
                        <?php echo e($t['name']); ?>
                      </span>
                    </td>
                    <td class="table-actions">
                      <a href="<?php echo BASE_URL; ?>tags/delete.php?id=<?php echo (int)$t['id']; ?>" 
                         class="tag-chip delete-tag" 
                         onclick="return confirm('Are you sure you want to delete this tag?')">
                        <i class="fas fa-trash-alt" style="margin-right:4px;"></i> Delete
                      </a>
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
