<?php
// Include necessary functions and database connection
require_once __DIR__ . '/../includes/functions.php';

// Initialize error and success messages
$error = '';
$success = '';

// Check if the form is submitted via POST
if (isPost()) {
  // Retrieve and sanitize form inputs
  $username = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  
  // Validate required fields
  if (!$username || !$email || !$password) {
    $error = 'Please fill all fields.';
  } else {
    // Check if email or username already exists
    $exists = $pdo->prepare("SELECT id FROM users WHERE email=? OR username=?");
    $exists->execute([$email, $username]);
    if ($exists->fetch()) {
      $error = 'Email or username already exists.';
    } else {
      // Hash the password using bcrypt
      $hash = password_hash($password, PASSWORD_BCRYPT);
      // Insert new user into database
      $ins = $pdo->prepare("INSERT INTO users (username,email,password,role) VALUES (?,?,?,'user')");
      $ins->execute([$username, $email, $hash]);
      $success = 'Account created. You can now login.';
    }
  }
}

// Fetch trending tags for sidebar
$tstmt = $pdo->query("SELECT t.name, COUNT(*) cnt
                      FROM question_tags qt
                      JOIN tags t ON t.id=qt.tag_id
                      GROUP BY t.id
                      ORDER BY cnt DESC, t.name ASC
                      LIMIT 8");
$trending = $tstmt->fetchAll();

// Fetch top contributors for sidebar
$cstmt = $pdo->query("SELECT u.username, COUNT(a.id) AS points
                      FROM users u
                      JOIN answers a ON a.user_id=u.id
                      GROUP BY u.id
                      ORDER BY points DESC
                      LIMIT 5");
$contributors = $cstmt->fetchAll();

// Include header and navbar
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<!-- Main content: Registration form and sidebar -->
<main class="container home-dark">
  <div class="layout-grid two-col">
    <section class="feed">
      <div class="feed-header">
        <h1>Register</h1>
      </div>
      <!-- Display error or success messages -->
      <?php if ($error) alert($error, 'error'); ?>
      <?php if ($success) alert($success, 'success'); ?>
      <!-- Registration form -->
      <div class="card">
        <form method="post">
          <label>Name <input name="name" value="<?php echo e($_POST['name'] ?? ''); ?>" required></label>
          <label>Email <input name="email" type="email" value="<?php echo e($_POST['email'] ?? ''); ?>" required></label>
          <label>Password <input name="password" type="password" required></label>
          <button class="primary" type="submit">Create Account</button>
        </form>
        <p style="margin-top:10px;">Already have an account? <a href="<?php echo BASE_URL; ?>auth/login.php">Login</a></p>
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
