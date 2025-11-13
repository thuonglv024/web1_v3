<?php
// Admin Sidebar Navigation
// Include this file in all admin pages for consistent navigation

// Get unread contacts count for badge
$unreadContactsCount = 0;
try {
  $unreadContactsCount = (int)$pdo->query("SELECT COUNT(*) FROM contacts WHERE is_read=0")->fetchColumn();
} catch (Exception $e) {
  // If is_read column doesn't exist yet, ignore error
}

// Get current page for active state
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="left-rail card">
  <ul class="side-nav">
    <li><a href="<?php echo BASE_URL; ?>admin/dashboard.php" <?php echo $currentPage === 'dashboard.php' ? 'style="background:#22c55e;color:#0b1220;"' : ''; ?>>Dashboard</a></li>
    <li><a href="<?php echo BASE_URL; ?>admin/manage_questions.php" <?php echo $currentPage === 'manage_questions.php' ? 'style="background:#22c55e;color:#0b1220;"' : ''; ?>>Manage Questions</a></li>
    <li><a href="<?php echo BASE_URL; ?>admin/manage_answers.php" <?php echo $currentPage === 'manage_answers.php' ? 'style="background:#22c55e;color:#0b1220;"' : ''; ?>>Manage Answers</a></li>
    <li><a href="<?php echo BASE_URL; ?>admin/manage_users.php" <?php echo $currentPage === 'manage_users.php' ? 'style="background:#22c55e;color:#0b1220;"' : ''; ?>>Manage Users</a></li>
    <li><a href="<?php echo BASE_URL; ?>admin/manage_modules.php" <?php echo $currentPage === 'manage_modules.php' ? 'style="background:#22c55e;color:#0b1220;"' : ''; ?>>Manage Modules</a></li>
    <li><a href="<?php echo BASE_URL; ?>admin/manage_tags.php" <?php echo $currentPage === 'manage_tags.php' ? 'style="background:#22c55e;color:#0b1220;"' : ''; ?>>Manage Tags</a></li>
    <li>
      <a href="<?php echo BASE_URL; ?>admin/manage_contacts.php"
         class="nav-badge-link"
         style="<?php echo $currentPage === 'manage_contacts.php' ? 'background:#22c55e;color:#0b1220;' : ''; ?>">
        Manage Contacts
        <?php if($unreadContactsCount > 0): ?>
          <span class="nav-badge"><?php echo $unreadContactsCount; ?></span>
        <?php endif; ?>
      </a>
    </li>
  </ul>
</aside>
