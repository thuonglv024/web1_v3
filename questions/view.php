<?php
require_once __DIR__ . '/../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT q.*, m.module_name, u.username FROM questions q JOIN modules m ON q.module_id=m.module_id JOIN users u ON q.user_id=u.id WHERE q.id=?");
$stmt->execute([$id]);
$q = $stmt->fetch();
if (!$q) { echo 'Question not found'; exit; }

$a = $pdo->prepare("SELECT a.id, a.content, a.created_at, u.username, a.user_id FROM answers a JOIN users u ON a.user_id=u.id WHERE a.question_id=? ORDER BY a.created_at ASC");
$a->execute([$id]);
$answers = $a->fetchAll();

// Fetch tags
$tstmt = $pdo->prepare("SELECT t.name FROM tags t JOIN question_tags qt ON qt.tag_id=t.id WHERE qt.question_id=? ORDER BY t.name");
$tstmt->execute([$id]);
$tags = array_column($tstmt->fetchAll(), 'name');

// Sidebar data - similar questions from same module
$similarStmt = $pdo->prepare(
  "SELECT q2.id, q2.title, q2.created_at
   FROM questions q2
   WHERE q2.module_id = ? AND q2.id != ? AND q2.status='approved'
   ORDER BY q2.created_at DESC
   LIMIT 5"
);
$similarStmt->execute([$q['module_id'], $id]);
$similarQuestions = $similarStmt->fetchAll();

// Trending tags with IDs
$trendingStmt = $pdo->query("SELECT t.id, t.name, COUNT(*) cnt
                      FROM question_tags qt
                      JOIN tags t ON t.id=qt.tag_id
                      GROUP BY t.id
                      ORDER BY cnt DESC, t.name ASC
                      LIMIT 8");
$trending = $trendingStmt->fetchAll();

// score and current user's vote
$score = (int)$pdo->query("SELECT COALESCE(SUM(value),0) FROM question_votes WHERE question_id=".$id)->fetchColumn();
$myVote = null;
if (is_logged_in()){
  $vs = $pdo->prepare("SELECT value FROM question_votes WHERE user_id=? AND question_id=?");
  $vs->execute([$_SESSION['user_id'], $id]);
  $v = $vs->fetchColumn();
  if ($v !== false) $myVote = ((int)$v === 1) ? 'up' : 'down';
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<main class="container home-light">
  <div class="layout-grid two-col">
    <section class="feed">
      <!-- Question Card -->
      <article class="card" style="margin-bottom:20px;">
        <!-- Question Header -->
        <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:16px;">
          <div style="flex:1;">
            <h1 style="margin:0 0 12px;font-size:28px;line-height:1.3;"><?php echo e($q['title']); ?></h1>
            <div class="item-meta">
              <span style="background:#0b1220;padding:4px 8px;border-radius:6px;font-weight:600;color:#22c55e;"><?php echo e($q['module_name']); ?></span>
              <span>by <?php echo e($q['username']); ?></span>
              <span><?php echo date('d M Y, H:i', strtotime($q['created_at'])); ?></span>
            </div>
          </div>
          <?php if (is_logged_in() && ((int)$q['user_id'] === (int)$_SESSION['user_id'] || is_admin())): ?>
            <div style="display:flex;gap:8px;">
              <a href="<?php echo BASE_URL; ?>questions/edit.php?id=<?php echo $id; ?>" class="tag-chip" style="padding:6px 12px;font-size:12px;">Edit</a>
              <a href="<?php echo BASE_URL; ?>questions/delete.php?id=<?php echo $id; ?>" class="tag-chip" style="padding:6px 12px;font-size:12px;border-color:#dc2626;" onclick="return confirm('Delete this question?')">Delete</a>
            </div>
          <?php endif; ?>
        </div>

        <!-- Vote Widget -->
        <div class="vote-widget" style="margin-bottom:16px;">
          <button class="btn-vote btn-up <?php echo $myVote==='up'?'active':''; ?>" data-question-id="<?php echo $id; ?>" aria-label="Like">👍</button>
          <span class="vote-score" data-score-for="<?php echo $id; ?>" style="font-size:18px;"><?php echo $score; ?></span>
          <button class="btn-vote btn-down <?php echo $myVote==='down'?'active':''; ?>" data-question-id="<?php echo $id; ?>" aria-label="Dislike">👎</button>
          <span style="color:#9ca3af;font-size:13px;margin-left:8px;"><?php echo abs($score); ?> votes</span>
        </div>

        <!-- Tags -->
        <?php if ($tags): ?>
          <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;">
            <?php foreach($tags as $tg): ?>
              <span class="tag-chip" style="font-size:12px;">#<?php echo e($tg); ?></span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <!-- Question Image -->
        <?php if (!empty($q['image'])): ?>
          <div style="margin-bottom:16px;">
            <img src="<?php echo BASE_URL; ?>assets/uploads/posts/<?php echo e($q['image']); ?>" alt="" style="max-width:100%;height:auto;border-radius:10px;border:1px solid #1f2937;" />
          </div>
        <?php endif; ?>

        <!-- Question Content -->
        <div style="padding:16px 0;border-top:1px solid #1f2937;border-bottom:1px solid #1f2937;">
          <div style="color:#696969;line-height:1.7;font-size:15px;"><?php echo nl2br(e($q['content'])); ?></div>
        </div>
      </article>

      <!-- Answers Section -->
      <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
          <h2 style="margin:0;font-size:22px;"><?php echo count($answers); ?> Answer<?php echo count($answers) !== 1 ? 's' : ''; ?></h2>
          <?php if (is_logged_in()): ?>
            <a href="<?php echo BASE_URL; ?>answers/add.php?question_id=<?php echo $q['id']; ?>" class="btn-ask" style="padding:8px 16px;font-size:13px;">+ Add Answer</a>
          <?php else: ?>
            <a href="<?php echo BASE_URL; ?>auth/login.php" class="btn-ask" style="padding:8px 16px;font-size:13px;">Login to Answer</a>
          <?php endif; ?>
        </div>

        <?php if (!$answers): ?>
          <div style="text-align:center;padding:40px 20px;">
            <p style="color:#9ca3af;margin:0;">No answers yet. Be the first to help!</p>
          </div>
        <?php else: ?>
          <div style="display:flex;flex-direction:column;gap:16px;">
            <?php foreach($answers as $ans): ?>
              <div style="padding:16px;background:#0b1220;border:1px solid #1f2937;border-radius:10px;">
                <div style="color:#e5e7eb;line-height:1.7;margin-bottom:12px;"><?php echo nl2br(e($ans['content'])); ?></div>
                <div style="display:flex;justify-content:space-between;align-items:center;padding-top:12px;border-top:1px solid #1f2937;">
                  <div style="display:flex;gap:12px;align-items:center;">
                    <span style="color:#9ca3af;font-size:13px;">by <strong style="color:#e5e7eb;"><?php echo e($ans['username']); ?></strong></span>
                    <span style="color:#9ca3af;font-size:13px;"><?php echo date('d M Y, H:i', strtotime($ans['created_at'])); ?></span>
                  </div>
                  <?php if (is_logged_in() && ((int)$ans['user_id'] === (int)$_SESSION['user_id'] || is_admin())): ?>
                    <div style="display:flex;gap:8px;">
                      <a href="<?php echo BASE_URL; ?>answers/edit.php?id=<?php echo $ans['id']; ?>" class="tag-chip" style="padding:4px 10px;font-size:11px;">Edit</a>
                      <a href="<?php echo BASE_URL; ?>answers/delete.php?id=<?php echo $ans['id']; ?>" class="tag-chip" style="padding:4px 10px;font-size:11px;border-color:#dc2626;" onclick="return confirm('Delete this answer?')">Delete</a>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <!-- Sidebar -->
    <aside class="right-rail">
      <!-- Question Actions -->
      <div class="card">
        <h3>Question Actions</h3>
        <div style="display:flex;flex-direction:column;gap:8px;margin-top:10px;">
          <?php if (is_logged_in()): ?>
            <a href="<?php echo BASE_URL; ?>answers/add.php?question_id=<?php echo $q['id']; ?>" class="tag-chip" style="text-align:center;display:block;border-color:#22c55e;">Answer Question</a>
          <?php endif; ?>
          <a href="<?php echo BASE_URL; ?>questions/list.php" class="tag-chip" style="text-align:center;display:block;">Go to Questions</a>
          <a href="<?php echo BASE_URL; ?>users/profile.php" class="tag-chip" style="text-align:center;display:block;">⬅ Back </a>
        </div>
      </div>

      <!-- Similar Questions -->
      <?php if ($similarQuestions): ?>
        <div class="card">
          <h3>Similar Questions</h3>
          <div style="display:flex;flex-direction:column;gap:10px;margin-top:10px;">
            <?php foreach($similarQuestions as $sq): ?>
              <a href="<?php echo BASE_URL; ?>questions/view.php?id=<?php echo (int)$sq['id']; ?>" style="display:block;padding:10px;background:#0b1220;border:1px solid #1f2937;border-radius:8px;text-decoration:none;transition:all .18s ease;" onmouseover="this.style.borderColor='#22c55e'" onmouseout="this.style.borderColor='#1f2937'">
                <div style="font-weight:500;color:#e5e7eb;font-size:13px;margin-bottom:4px;"><?php echo e($sq['title']); ?></div>
                <div style="color:#9ca3af;font-size:11px;"><?php echo date('d M Y', strtotime($sq['created_at'])); ?></div>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- Trending Topics -->
      <div class="card">
        <h3>Trending Topics</h3>
        <?php if (!$trending): ?>
          <p style="color:#9ca3af;font-size:14px;">No tags yet.</p>
        <?php else: ?>
          <div class="tag-cloud">
            <?php foreach($trending as $t): ?>
              <a class="tag-chip" href="<?php echo BASE_URL; ?>tags/list.php?tags=<?php echo (int)$t['id']; ?>" style="cursor:pointer;">#<?php echo e($t['name']); ?></a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </aside>
  </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
