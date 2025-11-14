<!-- Navigation bar -->
<nav class="navbar">
  <div class="container">
    <!-- Helper function to check if URI contains a string for active class -->
    <?php 
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $current_path = parse_url($uri, PHP_URL_PATH);
    
    // Xác định trang hiện tại
    $is_home = ($current_path === '/' || $current_path === BASE_URL || $current_path === '/COMP1841/courseworkWeb1V2/');
    
    // Function to check active state
    $is = function($path) use ($current_path, $is_home) {
        // Handle home page
        if ($path === BASE_URL) {
            return $is_home ? 'active' : '';
        }
        
        // Remove trailing slashes and BASE_URL for comparison
        $current = trim(str_replace(BASE_URL, '', $current_path), '/');
        $path = trim($path, '/');
        
        // For admin section
        if ($path === 'admin' || $path === 'admin/') {
            return (strpos($current, 'admin') === 0) ? 'active' : '';
        }
        
        // For questions section
        if ($path === 'questions' || $path === 'questions/') {
            return (strpos($current, 'questions') === 0) ? 'active' : '';
        }
        
        // For contact section
        if ($path === 'contact' || $path === 'contact/contact.php') {
            return (strpos($current, 'contact') === 0) ? 'active' : '';
        }
        
        // Direct match for other pages
        return ($current === $path) ? 'active' : '';
    };
    ?>
    <!-- Brand link -->
    <a href="<?php echo BASE_URL; ?>" class="brand"><?php echo e(APP_NAME); ?></a>
    <!-- Main navigation links -->
    <ul class="nav">
      <li><a class="<?php echo $is(BASE_URL); ?> tag-chip" href="<?php echo BASE_URL; ?>" style="<?php echo $is(BASE_URL)?'font-size:16px':''; ?>">Home</a></li>
      <li><a class="<?php echo $is('questions'); ?> tag-chip" href="<?php echo BASE_URL; ?>questions/list.php" style="<?php echo $is(BASE_URL)?'font-size:16px':''; ?>">Questions</a></li>
      <li><a class="<?php echo $is('contact'); ?> tag-chip" href="<?php echo BASE_URL; ?>contact/contact.php" style="<?php echo $is(BASE_URL)?'font-size:16px':''; ?>">Contact</a></li>
      <!-- Admin link if user is admin -->
      <?php if (is_admin()): ?>
        <li><a class="<?php echo $is('admin'); ?> tag-chip" href="<?php echo BASE_URL; ?>admin/dashboard.php" style="<?php echo $is(BASE_URL)?'font-size:16px':''; ?>">Admin</a></li>
      <?php endif; ?>
    </ul>
    <!-- Global search form -->
    <form class="nav-search" method="get" action="<?php echo BASE_URL; ?>search/search.php" style="flex:1;max-width:320px;margin-left:16px;">
      <input name="q" id="global-search" type="search" placeholder="Search questions or topics..." style="width:100%;background:#0b1220;color:#e5e7eb;border:1px solid #1f2937;border-radius:8px;padding:8px 12px;font-size:14px;" />
      <input type="hidden" name="type" value="all">
      <button type="submit" style="display:none;">Search</button>
    </form>
    <!-- User action buttons -->
    <div class="nav-cta">
      <?php if (is_logged_in()): ?>
        <a class="btn-ask" href="<?php echo BASE_URL; ?>questions/add.php">+ Ask</a>
        <a class="profile-link" href="<?php echo BASE_URL; ?>users/profile.php">Profile</a>
        <a class="logout-link" href="<?php echo BASE_URL; ?>auth/logout.php">Logout</a>
      <?php else: ?>
        <a class="login-link " href="<?php echo BASE_URL; ?>auth/login.php">Login</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
