<?php
require_once __DIR__ . '/../includes/functions.php';

// Initialize error
$error = '';

// Handle login form submission
if (isPost()) {
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  if (!$email || !$password) {
    $error = 'Please enter email and password.';
  } else {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
      $_SESSION['user_id'] = (int)$user['id'];
      $_SESSION['username'] = $user['username'];
      $_SESSION['role'] = $user['role'];
      redirect(($user['role'] === 'admin') ? 'admin/dashboard.php' : '');
    } else {
      $error = 'Invalid credentials.';
    }
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
<!-- Main content: Login form and sidebar -->
<main class="container home-dark">
  <div class="layout-grid two-col">
    <section class="feed">
      <div class="feed-header">
        <h1>Login</h1>
      </div>
      <!-- Display error message -->
      <?php if ($error) alert($error,'error'); ?>
      <!-- Login form -->
      <div class="card">
        <form method="post">
          <label>Email <input name="email" type="email" value="<?php echo e($_POST['email'] ?? ''); ?>" required></label>
          <label>Password <input name="password" type="password" required></label>
          <button class="primary" type="submit">Login</button>
        </form>
        <p style="margin-top:10px;">Don't have an account? <a href="<?php echo BASE_URL; ?>auth/register.php">Register</a></p>
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
<!-- Include footer -->
<?php include __DIR__ . '/../includes/footer.php'; ?>
