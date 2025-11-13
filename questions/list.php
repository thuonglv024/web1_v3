<?php
require_once __DIR__ . '/../includes/functions.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';

// ===== MODULE FILTER =====
// Get module filter from URL parameter ('all' or specific module ID)
$rankModuleParam = $_GET['module'] ?? 'all';
$rankModuleId = ctype_digit((string)$rankModuleParam) ? (int)$rankModuleParam : null;

// ===== TAG FILTER =====
// Get selected tag IDs from URL parameter (comma-separated)
$selectedTags = isset($_GET['tags']) ? array_map('intval', explode(',', $_GET['tags'])) : [];

// Fetch all modules for dropdown filter
$modules = $pdo->query("SELECT module_id, module_name FROM modules ORDER BY module_name")->fetchAll();

// Fetch all tags for filter
$allTags = $pdo->query("SELECT id, name FROM tags ORDER BY name")->fetchAll();

// ===== FETCH QUESTIONS WITH RANKING =====
// Rank questions by vote count (number of distinct users who voted)
// Optionally filter by selected module and tags
// Include tags and metadata for display

// Build WHERE clause for tag filtering
$tagFilter = '';
$tagHaving = '';
if (!empty($selectedTags)) {
  $placeholders = implode(',', array_fill(0, count($selectedTags), '?'));
  $tagFilter = " AND EXISTS (
    SELECT 1 FROM question_tags qt2 
    WHERE qt2.question_id = q.id 
    AND qt2.tag_id IN ($placeholders)
    GROUP BY qt2.question_id
    HAVING COUNT(DISTINCT qt2.tag_id) = " . count($selectedTags) . "
  )";
}

$sqlRank = "SELECT q.id, q.title, q.content, q.image, q.created_at, m.module_name, u.username,
                   GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR ',') AS tags,
                   COALESCE(COUNT(DISTINCT v.user_id),0) AS score
                     FROM questions q
                     JOIN modules m ON q.module_id = m.module_id
                     JOIN users u ON q.user_id = u.id
                     LEFT JOIN question_tags qt ON qt.question_id = q.id
                     LEFT JOIN tags t ON t.id = qt.tag_id
            LEFT JOIN question_votes v ON v.question_id = q.id
            WHERE q.status='approved'" .
            ($rankModuleId ? " AND q.module_id = :mid" : "") .
            $tagFilter .
            " GROUP BY q.id
              ORDER BY score DESC, q.created_at DESC
              LIMIT 50";

$stRank = $pdo->prepare($sqlRank);
$paramIndex = 1;
if ($rankModuleId) { 
  $stRank->bindValue(':mid', $rankModuleId, PDO::PARAM_INT); 
}
if (!empty($selectedTags)) {
  foreach ($selectedTags as $tagId) {
    $stRank->bindValue($paramIndex++, $tagId, PDO::PARAM_INT);
  }
}
$stRank->execute();
$questions = $stRank->fetchAll();
// ===== SIDEBAR DATA =====

// Top Contributors Filter
// Allow filtering contributors by module
$tcModuleParam = $_GET['tc_module'] ?? 'all';
$tcModuleId = ctype_digit((string)$tcModuleParam) ? (int)$tcModuleParam : null;

// Fetch Top Contributors
// Calculate total points from all votes on their approved questions
// Filter by module if selected
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
if ($tcModuleId) { 
  $stTC->bindValue(':mid', $tcModuleId, PDO::PARAM_INT); 
}
$stTC->execute();
$contributors = $stTC->fetchAll();

// Fetch Trending Topics
// Get top 8 most used tags across all questions
$tstmt = $pdo->query("SELECT t.name, COUNT(*) cnt
                      FROM question_tags qt
                      JOIN tags t ON t.id=qt.tag_id
                      GROUP BY t.id
                      ORDER BY cnt DESC, t.name ASC
                      LIMIT 8");
$trending = $tstmt->fetchAll();
?>
<main class="container home-dark">
  <div class="layout-grid two-col">
    <section class="feed">
      <!-- Header -->
      <div class="feed-header mb-20">
        <div class="flex justify-between items-center flex-wrap gap-16">
          <h1 style="margin:0;">Questions</h1>
          <a class="btn-ask" href="<?php echo BASE_URL; ?>questions/add.php" style="padding:10px 20px;">+ Ask Question</a>
        </div>
      </div>

      <!-- Filters -->
      <div class="card mb-20">
        <form method="get">
          <?php if (!empty($selectedTags)): ?>
            <input type="hidden" name="tags" value="<?php echo implode(',', $selectedTags); ?>" />
          <?php endif; ?>
          <label>Filter by module</label>
          <select name="module" onchange="this.form.submit()" class="filter-select">
            <option value="all" <?php echo !$rankModuleId ? 'selected' : ''; ?>>All Modules</option>
            <?php foreach($modules as $m): ?>
              <option value="<?php echo (int)$m['module_id']; ?>" <?php echo ($rankModuleId===(int)$m['module_id'])?'selected':''; ?>><?php echo e($m['module_name']); ?></option>
            <?php endforeach; ?>
          </select>
        </form>
      </div>

      <!-- Selected Tags Display -->
      <?php if (!empty($selectedTags)): ?>
        <div class="card mb-20">
          <div class="card-header mb-12">
            <h3 class="card-header-title">Filtering by tags:</h3>
            <?php 
            $clearUrl = BASE_URL . 'questions/list.php';
            if ($rankModuleId) {
              $clearUrl .= '?module=' . $rankModuleId;
            }
            ?>
            <a href="<?php echo $clearUrl; ?>" class="tag-chip tag-chip-small">Clear Tags</a>
          </div>
          <div class="tag-cloud">
            <?php foreach($allTags as $t): ?>
              <?php if (in_array($t['id'], $selectedTags)): ?>
                <span class="tag-chip tag-chip-active">#<?php echo e($t['name']); ?></span>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- Tag Filter -->
      <div class="card mb-20">
        <h3>Filter by Tags</h3>
        <p class="card-subtext" style="margin:8px 0 12px;">Click tags to filter. Questions must have ALL selected tags.</p>
        <div class="tag-cloud" data-tag-module="<?php echo $rankModuleId ?? 'all'; ?>">
          <?php foreach($allTags as $t): ?>
            <span 
              class="tag-selector <?php echo in_array($t['id'], $selectedTags) ? 'selected' : ''; ?>" 
              data-tag-id="<?php echo (int)$t['id']; ?>"
              data-module-id="<?php echo $rankModuleId ?? 'all'; ?>"
            >
              #<?php echo e($t['name']); ?>
            </span>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Questions List -->
      <?php if (!$questions): ?>
        <div class="card text-center" style="padding:60px 20px;">
          <p style="color:#9ca3af;font-size:16px;margin:0 0 20px;">No questions found. Be the first to ask!</p>
          <a class="btn-ask" href="<?php echo BASE_URL; ?>questions/add.php" style="padding:12px 24px;">Ask First Question</a>
        </div>
      <?php else: ?>
        <div class="flex flex-col gap-16" data-search-scope>
          <?php foreach ($questions as $q): ?>
            <article class="card question-card" data-search-item>
              <!-- Header -->
              <div class="flex justify-between items-start gap-16 mb-12">
                <div style="flex:1;">
                  <h3 class="title-lg">
                    <a href="<?php echo BASE_URL; ?>questions/view.php?id=<?php echo (int)$q['id']; ?>" class="link-primary">
                      <?php echo e($q['title']); ?>
                    </a>
                  </h3>
                  <div class="item-meta mb-12">
                    <span class="module-badge"><?php echo e($q['module_name']); ?></span>
                    <span class="meta-info">by <strong><?php echo e($q['username']); ?></strong></span>
                    <span class="meta-info"><?php echo date('d M Y', strtotime($q['created_at'])); ?></span>
                  </div>
                </div>
                <!-- Vote Widget -->
                <div class="vote-pill">
                  <button class="btn-vote btn-up vote-btn" data-question-id="<?php echo (int)$q['id']; ?>" aria-label="Like">👍</button>
                  <span data-score-for="<?php echo (int)$q['id']; ?>" class="vote-count"><?php echo (int)$q['score']; ?></span>
                  <button class="btn-vote btn-down vote-btn" data-question-id="<?php echo (int)$q['id']; ?>" aria-label="Dislike">👎</button>
                </div>
              </div>

              <!-- Content Preview -->
              <?php if (!empty($q['image'])): ?>
                <div class="question-card-image-wrap">
                  <img src="<?php echo BASE_URL; ?>assets/uploads/posts/<?php echo e($q['image']); ?>" alt="Question image" class="question-card-image" loading="lazy">
                </div>
              <?php endif; ?>
              <p class="content-preview"><?php echo nl2br(e(mb_substr($q['content'],0,200))) . (mb_strlen($q['content']) > 200 ? '...' : ''); ?></p>

                <?php if (!empty($q['tags'])): ?>
                <div class="flex gap-6 flex-wrap mt-12 card-divider">
                    <?php foreach (explode(',', $q['tags']) as $tg): ?>
                    <span class="tag-chip tag-chip-small">#<?php echo e($tg); ?></span>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
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
            <?php foreach($trending as $t): 
              // Find tag ID for the link
              $tagId = null;
              foreach($allTags as $at) {
                if ($at['name'] === $t['name']) {
                  $tagId = $at['id'];
                  break;
                }
              }
              if ($tagId):
                $isSelected = in_array($tagId, $selectedTags);
            ?>
              <span 
                class="tag-selector <?php echo $isSelected ? 'selected' : ''; ?>" 
                data-tag-id="<?php echo $tagId; ?>"
                data-module-id="<?php echo $rankModuleId ?? 'all'; ?>"
              >
                #<?php echo e($t['name']); ?>
              </span>
            <?php endif; endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="card" id="tc-card">
        <div class="card-header">
          <h3 class="card-header-title">Top Contributors</h3>
          <form method="get" action="#tc-card">
            <select name="tc_module" onchange="this.form.submit()" class="filter-select filter-select--compact">
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
              <li>
                <span class="avatar">👤</span> 
                <span>
                  <?php echo e($c['username']); ?>
                </span> 
                <span class="points"><?php echo (int)$c['points']; ?> pts</span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </aside>
  </div>
</main>

<!-- Tag filter JavaScript -->
<script>
  // Set BASE_URL for questions-tag-filter.js
  const BASE_URL = '<?php echo BASE_URL; ?>';
</script>
<script src="<?php echo BASE_URL; ?>assets/js/questions-tag-filter.js"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
