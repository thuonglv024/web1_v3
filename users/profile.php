<?php
require_once __DIR__ . '/../includes/functions.php';
if (!is_logged_in()) { redirect('auth/login.php'); }

$uid = (int)$_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT id, username, email, role, created_at FROM users WHERE id=?");
$stmt->execute([$uid]);
$user = $stmt->fetch();

$qstmt = $pdo->prepare("SELECT q.id, q.title, q.created_at FROM questions q WHERE q.user_id=? ORDER BY q.created_at DESC LIMIT 5");
$qstmt->execute([$uid]);
$myQs = $qstmt->fetchAll();

$astmt = $pdo->prepare("SELECT a.id, a.created_at, q.title, q.id AS qid FROM answers a JOIN questions q ON a.question_id=q.id WHERE a.user_id=? ORDER BY a.created_at DESC LIMIT 5");
$astmt->execute([$uid]);
$myAs = $astmt->fetchAll();

// User stats
$qStmt = $pdo->prepare("SELECT COUNT(*) FROM questions WHERE user_id=?");
$qStmt->execute([$uid]);
$qCount = (int)$qStmt->fetchColumn();

$aStmt = $pdo->prepare("SELECT COUNT(*) FROM answers WHERE user_id=?");
$aStmt->execute([$uid]);
$aCount = (int)$aStmt->fetchColumn();

$pStmt = $pdo->prepare("SELECT COALESCE(SUM(v.value),0) FROM question_votes v JOIN questions q ON v.question_id=q.id WHERE q.user_id=? AND q.status='approved'");
$pStmt->execute([$uid]);
$points = (int)$pStmt->fetchColumn();

// Trending tags (top 8 by usage)
$tstmt = $pdo->query("SELECT t.name, COUNT(*) cnt
                      FROM question_tags qt
                      JOIN tags t ON t.id=qt.tag_id
                      GROUP BY t.id
                      ORDER BY cnt DESC, t.name ASC
                      LIMIT 8");
$trending = $tstmt->fetchAll();

// Top contributors (by total question vote score)
$modules = $pdo->query("SELECT module_id, module_name FROM modules ORDER BY module_name")->fetchAll();
$tcModuleParam = $_GET['tc_module'] ?? 'all';
$tcModuleId = ctype_digit((string)$tcModuleParam) ? (int)$tcModuleParam : null;

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
<main class="container home-light">
  <div class="layout-grid two-col">
    <section class="feed">
      <!-- Profile Header Card -->
      <div class="card" style="margin-bottom:20px;">
        <div style="display:flex;gap:20px;align-items:start;">
          <div style="flex:1;">
            <h1 style="margin:0 0 8px;font-size:32px;"><?php echo e($user['username']); ?></h1>
            <p style="color:#9ca3af;margin:0 0 12px;"><?php echo e($user['email']); ?></p>
            <div style="display:flex;gap:16px;flex-wrap:wrap;">
              <span class="tag-chip">
                <?php echo ucfirst($user['role']); ?>
              </span>
              <span class="tag-chip">
                Member since <?php echo date('M Y', strtotime($user['created_at'])); ?>
              </span>
            </div>
          </div>
        </div>
        
        <!-- Stats Bar -->
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:20px;padding-top:20px;border-top:1px solid #1f2937;">
          <div style="text-align:center;">
            <div style="font-size:24px;font-weight:700;color:#22c55e;"><?php echo $qCount; ?></div>
            <div style="color:#9ca3af;font-size:12px;">Questions</div>
          </div>
          <div style="text-align:center;">
            <div style="font-size:24px;font-weight:700;color:#3b82f6;"><?php echo $aCount; ?></div>
            <div style="color:#9ca3af;font-size:12px;">Answers</div>
          </div>
          <div style="text-align:center;">
            <div style="font-size:24px;font-weight:700;color:#f59e0b;"><?php echo $points; ?></div>
            <div style="color:#9ca3af;font-size:12px;">Points</div>
          </div>
        </div>
      </div>

      <!-- Recent Questions -->
      <div class="card" style="margin-bottom:20px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
          <h3 style="margin:0;">Recent Questions</h3>
          <a class="btn-ask" href="<?php echo BASE_URL; ?>questions/add.php" style="padding:6px 12px;font-size:13px;">+ Ask Question</a>
        </div>
        <?php if (!$myQs): ?>
          <p style="color:#9ca3af;">No questions yet. Start by asking your first question!</p>
        <?php else: ?>
          <div style="display:flex;flex-direction:column;gap:10px;">
            <?php foreach($myQs as $rq): ?>
              <div style="padding:10px;background:#0b1220;border:1px solid #1f2937;border-radius:10px;transition:all .18s ease;" onmouseover="this.style.borderColor='#22c55e'" onmouseout="this.style.borderColor='#1f2937'">
                <a href="<?php echo BASE_URL; ?>questions/view.php?id=<?php echo (int)$rq['id']; ?>" style="text-decoration:none;font-weight:600;display:block;margin-bottom:4px;"><?php echo e($rq['title']); ?></a>
                <div style="color:#9ca3af;font-size:12px;">
                  <?php echo date('d M Y, H:i', strtotime($rq['created_at'])); ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <a href="<?php echo BASE_URL; ?>questions/list.php" class="tag-chip" style="margin-top:10px;">View all questions →</a>
        <?php endif; ?>
      </div>

      <!-- Recent Answers -->
      <div class="card">
        <h3 style="margin:0 0 12px;">Recent Answers</h3>
        <?php if (!$myAs): ?>
          <p style="color:#9ca3af;">No answers yet. Help the community by answering questions!</p>
        <?php else: ?>
          <div style="display:flex;flex-direction:column;gap:10px;">
            <?php foreach($myAs as $ra): ?>
              <div style="padding:10px;background:#0b1220;border:1px solid #1f2937;border-radius:10px;transition:all .18s ease;" onmouseover="this.style.borderColor='#3b82f6'" onmouseout="this.style.borderColor='#1f2937'">
                <div style="color:#9ca3af;font-size:12px;margin-bottom:4px;">Answered on</div>
                <a href="<?php echo BASE_URL; ?>questions/view.php?id=<?php echo (int)$ra['qid']; ?>" style="text-decoration:none;font-weight:600;display:block;margin-bottom:4px;"><?php echo e($ra['title']); ?></a>
                <div style="color:#9ca3af;font-size:12px;">
                  <?php echo date('d M Y, H:i', strtotime($ra['created_at'])); ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <!-- Right Sidebar -->
    <aside class="right-rail">
      <div class="card">
        <h3>Trending Topics</h3>
        <?php if (!$trending): ?>
          <p style="color:#9ca3af;font-size:14px;">No tags yet.</p>
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
          <form method="get" action="#tc-card">
            <select class="tc-select" name="tc_module" onchange="this.form.submit()">
              <option value="all" <?php echo !$tcModuleId ? 'selected' : ''; ?>>All modules</option>
              <?php foreach($modules as $m): ?>
                <option value="<?php echo (int)$m['module_id']; ?>" <?php echo ($tcModuleId===(int)$m['module_id'])?'selected':''; ?>><?php echo e($m['module_name']); ?></option>
              <?php endforeach; ?>
            </select>
          </form>
        </div>
        <?php if (!$contributors): ?>
          <p style="color:#9ca3af;font-size:14px;margin-top:10px;">No contributor stats yet.</p>
        <?php else: ?>
          <ul class="contributors" style="list-style:none;padding:0;margin:0;">
            <?php foreach($contributors as $c): ?>
              <li style="display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid #374151;overflow:hidden;">
                <span class="avatar">👤</span> 
                <span style="flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo e($c['username']); ?></span> 
                <span class="points" style="flex-shrink:0;"><?php echo (int)$c['points']; ?> pts</span>
              </li>
            <?php endforeach; ?>
          </ul>
          <p style="margin-top:10px"><a class="tag-chip" href="<?php echo BASE_URL; ?>contributors/leaderboard.php">View full leaderboard</a></p>
        <?php endif; ?>
      </div>

      <!-- Quick Actions Card -->
      <div class="card">
        <h3>Quick Actions</h3>
        <div style="display:flex;flex-direction:column;gap:8px;margin-top:10px;">
          <a href="<?php echo BASE_URL; ?>users/edit.php" class="tag-chip tag-chip-block">Edit Profile</a>
          <a href="<?php echo BASE_URL; ?>questions/list.php" class="tag-chip tag-chip-block">Browse Questions</a>
          <a href="<?php echo BASE_URL; ?>tags/list.php" class="tag-chip tag-chip-block">Browse Tags</a>
        </div>
      </div>
    </aside>
  </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
