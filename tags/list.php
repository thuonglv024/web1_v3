<?php
require_once __DIR__ . '/../includes/functions.php';

if (isPost()) {
  ensure_admin();
  $name = sanitize($_POST['name'] ?? '');
  if ($name) {
    $ins = $pdo->prepare("INSERT INTO tags (name) VALUES (?) ON DUPLICATE KEY UPDATE name=name");
    $ins->execute([$name]);
    redirect('tags/list.php');
  }
}

$tags = $pdo->query("SELECT id, name FROM tags ORDER BY name")->fetchAll();

// Sidebar data similar to Home
$tstmt = $pdo->query("SELECT t.name, COUNT(*) cnt
                      FROM question_tags qt
                      JOIN tags t ON t.id=qt.tag_id
                      GROUP BY t.id
                      ORDER BY cnt DESC, t.name ASC
                      LIMIT 8");
$trending = $tstmt->fetchAll();

// Module dropdown for Top Contributors in sidebar
$tcModuleParam = $_GET['tc_module'] ?? 'all';
$tcModuleId = ctype_digit((string)$tcModuleParam) ? (int)$tcModuleParam : null;
$modules = $pdo->query("SELECT module_id, module_name FROM modules ORDER BY module_name")->fetchAll();

// Top contributors by total vote score on approved questions, filtered by module if selected
$sqlTC = "SELECT u.username, COALESCE(SUM(v.value),0) AS points
          FROM users u
          JOIN questions q ON q.user_id = u.id AND q.status='approved'" .
          ($tcModuleId ? " AND q.module_id = :mid" : "") .
          " LEFT JOIN question_votes v ON v.question_id = q.id
          GROUP BY u.id
          HAVING points > 0
          ORDER BY points DESC, u.username ASC
          LIMIT 5";
$stTC = $pdo->prepare($sqlTC);
if ($tcModuleId) { $stTC->bindValue(':mid', $tcModuleId, PDO::PARAM_INT); }
$stTC->execute();
$contributors = $stTC->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<main class="container home-dark">
  <div class="layout-grid two-col">
    <section class="feed">
      <div class="feed-header">
        <h1>Tags</h1>
        <div class="actions">
          <div class="search-box">
            <input id="home-search" type="text" placeholder="Search questions or topics..." />
          </div>
        </div>
      </div>
      <?php if (is_admin()): ?>
        <form method="post" style="margin:0 0 12px 0;">
          <label>New Tag <input name="name" required></label>
          <button class="primary" type="submit">Add</button>
        </form>
      <?php endif; ?>

      <?php if (!$tags): ?>
        <p>No tags yet.</p>
      <?php else: ?>
        <div class="tag-cloud">
          <?php foreach($tags as $t): ?>
            <div class="card" style="display:inline-block;margin:6px;">
              <span class="tag-chip tag-chip-active">#<?php echo e($t['name']); ?></span>
              <?php if (is_admin()): ?>
                <div style="margin-top:6px;">
                  <a href="<?php echo BASE_URL; ?>tags/delete.php?id=<?php echo (int)$t['id']; ?>" onclick="return confirm('Delete this tag?')">Delete</a>
                </div>
              <?php endif; ?>
            </div>
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
        <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
          <h3 style="margin:0;">Top Contributors</h3>
          <form method="get" action="#tc-card" style="margin:0;">
            <select name="tc_module" onchange="this.form.submit()" style="background:#0b1220;color:#e5e7eb;border:1px solid #1f2937;border-radius:8px;padding:6px 8px;">
              <option value="all" <?php echo !$tcModuleId ? 'selected' : ''; ?>>All modules</option>
              <?php foreach($modules as $m): ?>
                <option value="<?php echo (int)$m['module_id']; ?>" <?php echo ($tcModuleId===(int)$m['module_id'])?'selected':''; ?>><?php echo e($m['module_name']); ?></option>
              <?php endforeach; ?>
            </select>
          </form>
        </div>
        <?php if (!$contributors): ?>
          <p>No contributor stats yet.</p>
        <?php else: ?>
          <ul class="contributors">
            <?php foreach($contributors as $c): ?>
              <li><span class="avatar">👤</span> <?php echo e($c['username']); ?> <span class="points"><?php echo (int)$c['points']; ?> pts</span></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </aside>
  </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
