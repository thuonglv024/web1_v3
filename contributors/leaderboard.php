<?php
require_once __DIR__ . '/../includes/functions.php';

// Range filter: all, 30d, 7d (apply to vote timestamp)
$range = $_GET['range'] ?? 'all';
if (!in_array($range, ['all','30d','7d'], true)) { $range = 'all'; }
$rangeSql = '';
if ($range === '30d') { $rangeSql = "AND v.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"; }
if ($range === '7d')  { $rangeSql = "AND v.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"; }

// Module filter: 'all' or specific module_id
$moduleParam = $_GET['module'] ?? 'all';
$moduleId = ctype_digit((string)$moduleParam) ? (int)$moduleParam : null;

// Fetch modules for filter chips
$mods = $pdo->query("SELECT module_id, module_name FROM modules ORDER BY module_name")->fetchAll();

// Build SQL with optional module filter
$sql = "SELECT u.id, u.username, COALESCE(SUM(v.value),0) AS points
        FROM users u
        JOIN questions q ON q.user_id = u.id AND q.status='approved'" .
        ($moduleId ? " AND q.module_id = :mid" : "") .
        " LEFT JOIN question_votes v ON v.question_id = q.id $rangeSql
        GROUP BY u.id
        HAVING points > 0
        ORDER BY points DESC, u.username ASC
        LIMIT 100";

$stmt = $pdo->prepare($sql);
if ($moduleId) { $stmt->bindValue(':mid', $moduleId, PDO::PARAM_INT); }
$stmt->execute();
$leaders = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<main class="container home-dark">
  <div class="layout-grid">
    <section class="feed">
      <div class="feed-header">
        <h1>Top Contributors</h1>
        <div class="actions" style="flex-wrap:wrap; gap:8px;">
          <form method="get" style="display:flex;gap:8px;flex-wrap:wrap;margin:0;">
            <label style="display:flex;align-items:center;gap:6px;">
              <span style="color:#9ca3af;font-size:12px;">Range</span>
              <select name="range" onchange="this.form.submit()" style="background:#0b1220;color:#e5e7eb;border:1px solid #1f2937;border-radius:8px;padding:6px 8px;">
                <option value="all" <?php echo $range==='all'?'selected':''; ?>>All time</option>
                <option value="30d" <?php echo $range==='30d'?'selected':''; ?>>Last 30 days</option>
                <option value="7d" <?php echo $range==='7d'?'selected':''; ?>>Last 7 days</option>
              </select>
            </label>
            <label style="display:flex;align-items:center;gap:6px;">
              <span style="color:#9ca3af;font-size:12px;">Module</span>
              <select name="module" onchange="this.form.submit()" style="background:#0b1220;color:#e5e7eb;border:1px solid #1f2937;border-radius:8px;padding:6px 8px;">
                <option value="all" <?php echo !$moduleId ? 'selected' : ''; ?>>All modules</option>
                <?php foreach($mods as $m): ?>
                  <option value="<?php echo (int)$m['module_id']; ?>" <?php echo ($moduleId===(int)$m['module_id'])?'selected':''; ?>><?php echo e($m['module_name']); ?></option>
                <?php endforeach; ?>
              </select>
            </label>
          </form>
        </div>
      </div>

      <div class="card">
        <?php if (!$leaders): ?>
          <p>No contributors found for this range.</p>
        <?php else: ?>
          <?php $rank=1; ?>
          <ul class="contributors">
            <?php foreach($leaders as $row): ?>
              <li>
                <span class="avatar">#<?php echo $rank++; ?></span>
                <strong><?php echo e($row['username']); ?></strong>
                <span class="points"><?php echo (int)$row['points']; ?> pts</span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </section>
  </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
