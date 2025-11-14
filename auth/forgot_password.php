<?php
require_once __DIR__ . '/../includes/functions.php';

// Handle forgot password form submission
// Note: Password reset functionality is not yet implemented
// This is a placeholder for future implementation
if (isPost()) {
  $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
  if ($email) {
    // Future implementation: Generate reset token, send email, store token in database
    // For now, just show a message
    $_SESSION['flash_message'] = 'Password reset functionality is not yet available. Please contact the administrator.';
    $_SESSION['flash_type'] = 'info';
    redirect('auth/login.php');
  }
}

// Fetch sidebar data
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

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<!-- Main content: Forgot password form and sidebar -->
<main class="container home-light">
  <div class="layout-grid two-col">
    <section class="feed">
      <div class="feed-header">
        <h1>Forgot Password</h1>
      </div>
      <!-- Forgot password form -->
      <div class="card">
        <form method="post">
          <label>Email <input name="email" type="email" required></label>
          <button class="primary" type="submit">Send reset link</button>
        </form>
      </div>
    </section>

    <!-- Sidebar with trending topics and top contributors -->
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

      <div class="card">
        <h3>Top Contributors</h3>
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
        <?php endif; ?>
      </div>
    </aside>
  </div>
</main>
<!-- Include footer -->
<?php include __DIR__ . '/../includes/footer.php'; ?>
