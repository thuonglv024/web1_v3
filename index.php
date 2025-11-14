<?php
/**
 * Homepage (Landing Page)
 * 
 * Features:
 * - Hero section with CTA buttons
 * - Latest questions feed (12 most recent)
 * - Featured sections (Browse jobs, Find answers, Connect)
 * - Trending topics sidebar
 * - Top contributors by module
 */

require_once __DIR__ . '/includes/functions.php';

// ===== LATEST QUESTIONS FEED =====
// Fetch 12 most recent approved questions with answer counts
$stmt = $pdo->query(
  "SELECT q.id, q.title, q.content, q.image, q.created_at, m.module_name, u.username,
          (SELECT COUNT(*) FROM answers a WHERE a.question_id=q.id) AS answers_count
   FROM questions q
   JOIN modules m ON q.module_id = m.module_id
   JOIN users u ON q.user_id = u.id
   WHERE q.status='approved'
   ORDER BY q.created_at DESC
   LIMIT 12"
);
$qs = $stmt->fetchAll();

// ===== TRENDING TAGS =====
// Get top 8 most used tags across all questions
$tstmt = $pdo->query("SELECT t.name, COUNT(*) cnt
                      FROM question_tags qt
                      JOIN tags t ON t.id=qt.tag_id
                      GROUP BY t.id
                      ORDER BY cnt DESC, t.name ASC
                      LIMIT 8");
$trending = $tstmt->fetchAll();

// Top contributors (by total question vote score); optional module filter via ?tc_module=<id|all>
$tcModuleParam = $_GET['tc_module'] ?? 'all';
$tcModuleId = ctype_digit((string)$tcModuleParam) ? (int)$tcModuleParam : null;
$modules = $pdo->query("SELECT module_id, module_name FROM modules ORDER BY module_name")->fetchAll();

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

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/navbar.php';
?>
<main>
  <!-- Hero -->
  <section class="hero">
    <div class="container hero-inner">
      <h1>We <span class="heart">❤</span> people who code</h1>
      <p>We build products that empower developers and connect them to solutions that enable productivity, growth, and discovery.</p>
      <div class="hero-ctas">
        <a class="btn-hero" href="<?php echo BASE_URL; ?>questions/list.php">For developers</a>
        <a class="btn-hero outline" href="#business">For businesses</a>
      </div>
    </div>
  </section>

  <!-- Devs section -->
  <section class="section">
    <div class="container">
      <h2>For developers, by developers</h2>
      <p class="section-sub">Ask questions, share knowledge, and grow your skills with our community.</p>
      <div class="features-grid">
        <article class="feature-card">
          <h3>Public Q&amp;A</h3>
          <p>Get answers to your toughest coding questions and help others learn.</p>
          <a class="btn-link" href="<?php echo BASE_URL; ?>questions/list.php">Browse questions</a>
        </article>
        <article class="feature-card highlight">
          <h3>Private Q&amp;A</h3>
          <p>Collaborate with your classmates or team in a focused, private space.</p>
          <a class="btn-link warn" href="<?php echo BASE_URL; ?>includes/comingSoon.php">Learn more</a>
        </article>
        <article class="feature-card">
          <h3>Browse jobs</h3>
          <p>Discover roles that fit your skills and interests across tech domains.</p>
          <a class="btn-link" href="<?php echo BASE_URL; ?>includes/comingSoon.php">Find a job</a>
        </article>
      </div>
      <div class="card" id="tc-card">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
          <h3 style="margin:0;">Top Contributors</h3>
          <form method="get" action="#tc-card" style="margin:0;">
            <select name="tc_module" onchange="this.form.submit()" style="width:100%;background:#0b1220;color:#e5e7eb;border:1px solid #1f2937;border-radius:8px;padding:6px 8px;">
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
          <ul class="contributors" style="list-style:none;padding:0;margin:0;">
            <?php foreach($contributors as $c): ?>
              <li style="display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid #374151;overflow:hidden;">
                <span class="avatar">👤</span> 
                <span style="flex:1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo e($c['username']); ?></span> 
                <span class="points" style="flex-shrink:0;"><?php echo (int)$c['points']; ?> pts</span>
              </li>
            <?php endforeach; ?>
          </ul>
          <p style="margin-top:10px"><a class="tag-chip" href="<?php echo BASE_URL; ?>contributors/leaderboard.php">View all contributors</a></p>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- Question List section -->
  <section id="business" class="section light">
    <div class="container">
      <div class="flex justify-between items-center flex-wrap gap-16 mb-32">
        <div>
          <h2>Recent Questions</h2>
          <p class="section-sub">Latest questions from our community</p>
        </div>
        <a class="btn-hero" href="<?php echo BASE_URL; ?>questions/list.php">View All Questions</a>
      </div>

      <?php if (!$qs): ?>
        <div class="card text-center" style="padding:60px 20px;">
          <p style="color:#64748b;font-size:16px;margin:0 0 20px;">No questions yet. Be the first to ask!</p>
          <a class="btn-hero" href="<?php echo BASE_URL; ?>questions/add.php">Ask First Question</a>
        </div>
      <?php else: ?>
        <div class="questions-grid">
          <?php foreach ($qs as $q):
            // Get tags for this question
            $tagStmt = $pdo->prepare("SELECT t.name FROM tags t JOIN question_tags qt ON t.id = qt.tag_id WHERE qt.question_id = ? ORDER BY t.name");
            $tagStmt->execute([$q['id']]);
            $questionTags = $tagStmt->fetchAll(PDO::FETCH_COLUMN);

            // Get vote score for this question
            $voteStmt = $pdo->prepare("SELECT COALESCE(SUM(value), 0) as score FROM question_votes WHERE question_id = ?");
            $voteStmt->execute([$q['id']]);
            $voteScore = $voteStmt->fetch()['score'];
          ?>
            <article class="card question-card">
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
                    <span class="meta-info"><?php echo (int)$q['answers_count']; ?> answer<?php echo $q['answers_count'] != 1 ? 's' : ''; ?></span>
                  </div>
                </div>
                <!-- Vote Widget -->
                <div class="vote-pill">
                  <button class="btn-vote btn-up vote-btn" data-question-id="<?php echo (int)$q['id']; ?>" aria-label="Like">👍</button>
                  <span data-score-for="<?php echo (int)$q['id']; ?>" class="vote-count"><?php echo (int)$voteScore; ?></span>
                  <button class="btn-vote btn-down vote-btn" data-question-id="<?php echo (int)$q['id']; ?>" aria-label="Dislike">👎</button>
                </div>
              </div>

              <!-- Content Preview -->
              <p class="content-preview"><?php echo nl2br(e(mb_substr($q['content'],0,200))) . (mb_strlen($q['content']) > 200 ? '...' : ''); ?></p>

              <!-- Tags -->
              <?php if (!empty($questionTags)): ?>
                <div class="flex gap-6 flex-wrap mt-12" style="padding-top:12px;border-top:1px solid #1f2937;">
                  <?php foreach ($questionTags as $tag): ?>
                    <span class="tag-chip tag-chip-small">#<?php echo e($tag); ?></span>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </article>
          <?php endforeach; ?>
        </div>

        <div class="text-center mt-32">
          <a class="btn-hero outline" href="<?php echo BASE_URL; ?>questions/list.php">Browse All Questions</a>
        </div>
      <?php endif; ?>
    </div>
  </section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>

<script src="<?php echo BASE_URL; ?>assets/js/vote.js"></script>
