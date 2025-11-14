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
<!-- Main content: Registration form -->
<main class="home-light" style="min-height: 100vh; display: flex; align-items: center;">
  <div class="container">
    <section class="auth-form">
      <div class="card">
        <h1>Create Your Account</h1>
        
        <!-- Display error or success messages -->
        <?php if ($error) alert($error, 'error'); ?>
        <?php if ($success) alert($success, 'success'); ?>
        
        <!-- Registration form -->
        <form method="post" class="auth-form-fields">
          <div class="form-group">
            <label for="name">Full Name</label>
            <input id="name" name="name" type="text" value="<?php echo e($_POST['name'] ?? ''); ?>" required 
                   placeholder="Enter your full name">
          </div>
          
          <div class="form-group">
            <label for="email">Email Address</label>
            <input id="email" name="email" type="email" value="<?php echo e($_POST['email'] ?? ''); ?>" required
                   placeholder="your@email.com">
          </div>
          
          <div class="form-group">
            <label for="password">Create Password</label>
            <input id="password" name="password" type="password" required
                   placeholder="••••••••">
          </div>
          
          <button type="submit" class="btn-primary">
            Create Account
          </button>
          
          <p class="auth-form-footer">
            Already have an account? 
            <a href="<?php echo BASE_URL; ?>auth/login.php" class="auth-link">Sign in here</a>
          </p>
        </form>
      </div>
    </section>


  </div>
</main>
<!-- Include footer -->
<?php include __DIR__ . '/../includes/footer.php'; ?>
