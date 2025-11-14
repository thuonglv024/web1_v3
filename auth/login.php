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
<!-- Main content: Login form -->
<main style="display: flex; align-items: center; margin:-40px 0 -100px 0">
  <div class="container">
    <section class="auth-form">
      <div class="card">
        <h1>Welcome Back</h1>
        
        <!-- Display error message -->
        <?php if ($error) alert($error, 'error'); ?>
        
        <!-- Login form -->
        <form method="post" class="auth-form-fields">
          <div class="form-group">
            <label for="email" style="margin-bottom: -2px;">Email Address</label>
            <input id="email" name="email" type="email" value="<?php echo e($_POST['email'] ?? ''); ?>" required
                   placeholder="your@email.com">
          </div>
          
          <div class="form-group">
            <label for="password" style="margin-bottom: -2px;">Password</label>
            <div style="position: relative;">
              <input id="password" name="password" type="password" required
                     placeholder="••••••••">
            </div>
          </div>
          
          <div class="form-group" style="text-align: right; margin: -10px 0 15px;">
            <a href="#" style="font-size: 14px; color: var(--accent); text-decoration: none;">
              Forgot password?
            </a>
          </div>
          
          <button type="submit" class="btn-primary">
            Sign In
          </button>
          
          <p class="auth-form-footer">
            Don't have an account? 
            <a href="<?php echo BASE_URL; ?>auth/register.php" class="auth-link">Register here</a>
          </p>
        </form>
      </div>
    </section>
    

  </div>
</main>
<!-- Include footer -->
<?php include __DIR__ . '/../includes/footer.php'; ?>
