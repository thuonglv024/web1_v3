<?php
/**
 * Search Page
 * 
 * Features:
 * - Search across questions, tags, and modules
 * - Filter results by type (all, questions, tags, modules)
 * - Show result counts
 * - Clickable trending topics
 */

require_once __DIR__ . '/../includes/functions.php';

// ===== SEARCH PARAMETERS =====
// Get and sanitize search query from URL
$query = $_GET['q'] ?? '';
$query = trim($query);

// Get filter type (all, questions, tags, modules)
// Default to 'all' if invalid type provided
$filterType = $_GET['type'] ?? 'all';
if (!in_array($filterType, ['all', 'questions', 'tags', 'modules'], true)) {
  $filterType = 'all';
}

$results = [];
$questionResults = [];
$tagResults = [];
$moduleResults = [];

if ($query !== '') {
  // Search questions (title and content)
  if ($filterType === 'all' || $filterType === 'questions') {
    $qStmt = $pdo->prepare(
      "SELECT q.id, q.title, q.content, q.created_at, q.image, u.username, m.module_name,
              GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR ',') AS tags,
              (SELECT COUNT(*) FROM answers a WHERE a.question_id=q.id) AS answers_count
       FROM questions q
       JOIN users u ON q.user_id = u.id
       JOIN modules m ON q.module_id = m.module_id
       LEFT JOIN question_tags qt ON qt.question_id = q.id
       LEFT JOIN tags t ON t.id = qt.tag_id
       WHERE q.status='approved' AND (q.title LIKE ? OR q.content LIKE ? OR t.name LIKE ?)
       GROUP BY q.id
       ORDER BY q.created_at DESC
       LIMIT 20"
    );
    $searchTerm = '%' . $query . '%';
    $qStmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    $questionResults = $qStmt->fetchAll();
  }

  // Search tags
  if ($filterType === 'all' || $filterType === 'tags') {
    $tStmt = $pdo->prepare(
      "SELECT t.id, t.name, COUNT(qt.question_id) AS question_count
       FROM tags t
       LEFT JOIN question_tags qt ON qt.tag_id = t.id
       WHERE t.name LIKE ?
       GROUP BY t.id
       ORDER BY question_count DESC, t.name ASC
       LIMIT 10"
    );
    $tStmt->execute(['%' . $query . '%']);
    $tagResults = $tStmt->fetchAll();
  }

  // Search modules
  if ($filterType === 'all' || $filterType === 'modules') {
    $mStmt = $pdo->prepare(
      "SELECT m.module_id, m.module_code, m.module_name,
              (SELECT COUNT(*) FROM questions WHERE module_id=m.module_id AND status='approved') AS question_count
       FROM modules m
       WHERE m.module_code LIKE ? OR m.module_name LIKE ?
       ORDER BY question_count DESC, m.module_name ASC
       LIMIT 10"
    );
    $mStmt->execute(['%' . $query . '%', '%' . $query . '%']);
    $moduleResults = $mStmt->fetchAll();
  }
}

$totalResults = count($questionResults) + count($tagResults) + count($moduleResults);

// Sidebar data
$tstmt = $pdo->query("SELECT t.name, COUNT(*) cnt
                      FROM question_tags qt
                      JOIN tags t ON t.id=qt.tag_id
                      GROUP BY t.id
                      ORDER BY cnt DESC, t.name ASC
                      LIMIT 8");
$trending = $tstmt->fetchAll();

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
      <div class="feed-header">
        <h1>Search</h1>
      </div>

      <!-- Search Form -->
      <div class="card" style="margin-bottom:20px;">
        <form method="get" action="<?php echo BASE_URL; ?>search/search.php">
          <div style="display:flex;gap:12px;">
            <input name="q" value="<?php echo e($query); ?>" placeholder="Search questions, tags, or modules..." required style="flex:1;" autofocus />
            <button class="btn-ask" type="submit" style="padding:10px 20px;">Search</button>
          </div>
        </form>
      </div>

      <?php if ($query !== ''): ?>
        <!-- Filter Tabs -->
        <div class="card" style="margin-bottom:16px;padding:12px;">
          <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <a href="?q=<?php echo urlencode($query); ?>&type=all" class="tag-chip <?php echo $filterType==='all'?'tag-chip-active':''; ?>">
              All (<?php echo $totalResults; ?>)
            </a>
            <a href="?q=<?php echo urlencode($query); ?>&type=questions" class="tag-chip <?php echo $filterType==='questions'?'tag-chip-active':''; ?>">
              Questions (<?php echo count($questionResults); ?>)
            </a>
            <a href="?q=<?php echo urlencode($query); ?>&type=tags" class="tag-chip <?php echo $filterType==='tags'?'tag-chip-active':''; ?>">
              Tags (<?php echo count($tagResults); ?>)
            </a>
            <a href="?q=<?php echo urlencode($query); ?>&type=modules" class="tag-chip <?php echo $filterType==='modules'?'tag-chip-active':''; ?>">
              Modules (<?php echo count($moduleResults); ?>)
            </a>
          </div>
        </div>

        <?php if ($totalResults === 0): ?>
          <div class="card">
            <div style="text-align:center;padding:40px 20px;">
              <h3 style="margin:0 0 8px;">No results found</h3>
              <p style="color:#9ca3af;margin:0;">Try different keywords or check your spelling</p>
            </div>
          </div>
        <?php else: ?>
          <!-- Questions Results -->
          <?php if (($filterType === 'all' || $filterType === 'questions') && count($questionResults) > 0): ?>
            <div class="card" style="margin-bottom:20px;">
              <h3 style="margin:0 0 14px;">Questions (<?php echo count($questionResults); ?>)</h3>
              <div style="display:flex;flex-direction:column;gap:12px;">
                <?php foreach($questionResults as $q): ?>
                  <article class="feed-item" style="padding:12px;background:#0b1220;border:1px solid #1f2937;border-radius:10px;">
                    <div class="item-content">
                      <h3 style="margin:0 0 8px;font-size:18px;">
                        <a href="<?php echo BASE_URL; ?>questions/view.php?id=<?php echo (int)$q['id']; ?>" style="text-decoration:none;"><?php echo e($q['title']); ?></a>
                      </h3>
                      <div class="item-meta" style="margin-bottom:8px;">
                        <span>[<?php echo e($q['module_name']); ?>]</span>
                        <span>by <?php echo e($q['username']); ?></span>
                        <span><?php echo date('d M Y', strtotime($q['created_at'])); ?></span>
                        <span>| <?php echo (int)$q['answers_count']; ?> answers</span>
                      </div>
                      <?php if (!empty($q['tags'])): ?>
                        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:8px;">
                          <?php foreach (explode(',', $q['tags']) as $tg): ?>
                            <span class="tag-chip tag-chip-small">#<?php echo e($tg); ?></span>
                          <?php endforeach; ?>
                        </div>
                      <?php endif; ?>
                      <p style="margin:0;color:#9ca3af;font-size:14px;"><?php echo nl2br(e(mb_substr($q['content'],0,200))); ?>...</p>
                    </div>
                  </article>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

          <!-- Tags Results -->
          <?php if (($filterType === 'all' || $filterType === 'tags') && count($tagResults) > 0): ?>
            <div class="card" style="margin-bottom:20px;">
              <h3 style="margin:0 0 14px;">Tags (<?php echo count($tagResults); ?>)</h3>
              <div class="tag-cloud">
                <?php foreach($tagResults as $t): ?>
                  <a class="tag-chip" href="#">
                    #<?php echo e($t['name']); ?>
                    <span style="background:var(--bg-strong);padding:2px 6px;border-radius:999px;font-size:10px;margin-left:4px;"><?php echo (int)$t['question_count']; ?></span>
                  </a>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

          <!-- Modules Results -->
          <?php if (($filterType === 'all' || $filterType === 'modules') && count($moduleResults) > 0): ?>
            <div class="card">
              <h3 style="margin:0 0 14px;">Modules (<?php echo count($moduleResults); ?>)</h3>
              <div style="display:flex;flex-direction:column;gap:10px;">
                <?php foreach($moduleResults as $m): ?>
                  <div style="padding:12px;background:#0b1220;border:1px solid #1f2937;border-radius:10px;display:flex;justify-content:space-between;align-items:center;">
                    <div>
                      <span style="background:#065f46;color:#bbf7d0;padding:4px 8px;border-radius:6px;font-size:12px;font-weight:600;margin-right:8px;"><?php echo e($m['module_code']); ?></span>
                      <span style="font-weight:500;"><?php echo e($m['module_name']); ?></span>
                    </div>
                    <span style="color:#9ca3af;font-size:13px;"><?php echo (int)$m['question_count']; ?> questions</span>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      <?php else: ?>
        <div class="card">
          <div style="text-align:center;padding:40px 20px;">
            <h3 style="margin:0 0 8px;">Search for anything</h3>
            <p style="color:#9ca3af;margin:0;">Find questions, tags, or modules by entering keywords above</p>
          </div>
        </div>
      <?php endif; ?>
    </section>

    <aside class="right-rail">
      <div class="card">
        <h3>Trending Topics</h3>
        <?php if (!$trending): ?>
          <p style="color:#9ca3af;font-size:14px;">No tags yet.</p>
        <?php else: ?>
          <div class="tag-cloud">
            <?php foreach($trending as $t): ?>
              <a class="tag-chip" href="<?php echo BASE_URL; ?>search/search.php?q=<?php echo urlencode($t['name']); ?>&type=tags">#<?php echo e($t['name']); ?></a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="card" id="tc-card">
        <div class="tc-header">
          <h3>Top Contributors</h3>
          <form method="get" action="#tc-card">
            <input type="hidden" name="q" value="<?php echo e($query); ?>">
            <input type="hidden" name="type" value="<?php echo e($filterType); ?>">
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

      <div class="card">
        <h3>Search Tips</h3>
        <ul style="color:#9ca3af;font-size:13px;line-height:1.8;padding-left:20px;margin:10px 0 0;">
          <li>Use specific keywords</li>
          <li>Try different terms</li>
          <li>Search by module name</li>
          <li>Filter by type for better results</li>
        </ul>
      </div>
    </aside>
  </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
