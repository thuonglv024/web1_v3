<?php
require_once __DIR__ . '/../includes/functions.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';

$stmt = $pdo->query("SELECT module_id, module_code, module_name, created_at FROM modules ORDER BY module_name");
$modules = $stmt->fetchAll();
// Sidebar data
$tstmt = $pdo->query("SELECT t.name, COUNT(*) cnt
                      FROM question_tags qt
                      JOIN tags t ON t.id=qt.tag_id
                      GROUP BY t.id
                      ORDER BY cnt DESC, t.name ASC
                      LIMIT 8");
$trending = $tstmt->fetchAll();

$cstmt = $pdo->query("SELECT u.username, COUNT(a.id) AS points
                      FROM users u
                      JOIN answers a ON a.user_id=u.id
                      GROUP BY u.id
                      ORDER BY points DESC
                      LIMIT 5");
$contributors = $cstmt->fetchAll();
?>
<main class="container home-light">
  <div class="layout-grid two-col">
    <section class="feed">
      <div class="feed-header">
        <h1>Modules</h1>
        <div class="actions">
          <?php if (is_admin()): ?>
            <a class="btn-ask" href="<?php echo BASE_URL; ?>modules/add.php">+ Add Module</a>
          <?php endif; ?>
        </div>
      </div>

      <?php if (!$modules): ?>
        <p>No modules yet.</p>
      <?php else: ?>
        <div class="feed-list" data-search-scope>
          <?php foreach($modules as $m): ?>
            <article class="feed-item card" data-search-item>
              <div class="item-content">
                <h3><?php echo e($m['module_code']); ?> — <?php echo e($m['module_name']); ?></h3>
                <div class="item-meta">
                  <span>Created: <?php echo date('d M Y', strtotime($m['created_at'])); ?></span>
                </div>
                <?php if (is_admin()): ?>
                  <div>
                    <a href="<?php echo BASE_URL; ?>modules/edit.php?id=<?php echo (int)$m['module_id']; ?>">Edit</a>
                    |
                    <a href="<?php echo BASE_URL; ?>modules/delete.php?id=<?php echo (int)$m['module_id']; ?>" onclick="return confirm('Delete this module?')">Delete</a>
                  </div>
                <?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <aside class="right-rail">
      <div class="card">
        <h3>Trending Topics</h3>
        <?php if (!$trending): ?>
          <p>No tags yet.</p>
        <?php else: ?>
          <div class="tag-cloud">
            <?php foreach($trending as $t): ?>
              <a class="tag-chip" href="#">#<?php echo e($t['name']); ?></a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="card" id="tc-card">
        <div class="tc-header">
          <h3>Top Contributors</h3>
        </div>
        <?php if (!$contributors): ?>
          <p>No contributor stats yet.</p>
        <?php else: ?>
          <ul class="contributors" style="list-style:none;padding:0;margin:0;">
            <?php foreach($contributors as $c): ?>
              <li style="display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid #374151;overflow:hidden;">
                <span class="avatar">👤</span> 
                <span style="flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">#<?php echo (int)$c['module_id']; ?> <?php echo e($c['username']); ?></span> 
                <span class="points" style="flex-shrink:0;">#<?php echo (int)$c['points']; ?> pts</span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </aside>
  </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
