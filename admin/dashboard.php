<?php
require_once __DIR__ . '/../includes/functions.php';
ensure_admin();

// Fetch statistics counts
$usersCount = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$modulesCount = (int)$pdo->query("SELECT COUNT(*) FROM modules")->fetchColumn();
$questionsCount = (int)$pdo->query("SELECT COUNT(*) FROM questions")->fetchColumn();
$answersCount = (int)$pdo->query("SELECT COUNT(*) FROM answers")->fetchColumn();
$pendingCount = (int)$pdo->query("SELECT COUNT(*) FROM questions WHERE status='pending'")->fetchColumn();
$unreadContactsCount = (int)$pdo->query("SELECT COUNT(*) FROM contacts WHERE is_read=0")->fetchColumn();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<!-- Admin dashboard layout -->
<main class="container home-light">
  <div class="layout-grid">
    <!-- Admin sidebar navigation -->
    <aside class="left-rail card">
      <ul class="side-nav">
        <li><a href="<?php echo BASE_URL; ?>admin/dashboard.php">Dashboard</a></li>
        <li><a href="<?php echo BASE_URL; ?>admin/manage_questions.php">Manage Questions</a></li>
        <li><a href="<?php echo BASE_URL; ?>admin/manage_answers.php">Manage Answers</a></li>
        <li><a href="<?php echo BASE_URL; ?>admin/manage_users.php">Manage Users</a></li>
        <li><a href="<?php echo BASE_URL; ?>admin/manage_modules.php">Manage Modules</a></li>
        <li><a href="<?php echo BASE_URL; ?>admin/manage_tags.php">Manage Tags</a></li>
        <li><a href="<?php echo BASE_URL; ?>admin/manage_contacts.php">Contact Messages <?php if($unreadContactsCount > 0): ?><span style="background:#f59e0b;color:#111;padding:2px 6px;border-radius:999px;font-size:11px;margin-left:4px;"><?php echo $unreadContactsCount; ?></span><?php endif; ?></a></li>
      </ul>
    </aside>

    <!-- Main dashboard content -->
    <section class="feed">
      <div class="feed-header">
        <h1>Admin Dashboard</h1>
      </div>

      <!-- Statistics cards -->
      <div class="stats-cards" style="display:grid;grid-template-columns:repeat(4,minmax(160px,1fr));gap:12px;margin:12px 0;">
        <div class="card"><h3>Total Questions</h3><p style="font-size:28px;margin:.25rem 0;"><?php echo $questionsCount; ?></p></div>
        <div class="card"><h3>Total Users</h3><p style="font-size:28px;margin:.25rem 0;"><?php echo $usersCount; ?></p></div>
        <div class="card"><h3>Total Answers</h3><p style="font-size:28px;margin:.25rem 0;"><?php echo $answersCount; ?></p></div>
        <div class="card"><h3>Modules</h3><p style="font-size:28px;margin:.25rem 0;"><?php echo $modulesCount; ?></p></div>
      </div>

      <!-- Unread contacts alert -->
      <?php if ($unreadContactsCount > 0): ?>
        <div class="card contact-card has-unread" style="margin:12px 0;background:#92400e;border-color:#f59e0b;">
          <div style="display:flex;align-items:center;justify-content:space-between;">
            <div>
              <h3 class="contact-card__title" style="margin:0 0 8px;color:#fde68a;">Contact Messages</h3>
              <p class="contact-card__text" style="margin:0;font-size:14px;color:#fed7aa;">
                <strong style="font-size:32px;color:#fde68a;"><?php echo $unreadContactsCount; ?></strong> unread message<?php echo $unreadContactsCount > 1 ? 's' : ''; ?> awaiting reply
              </p>
            </div>
            <a href="<?php echo BASE_URL; ?>admin/manage_contacts.php" class="btn-ask contact-card__cta" style="background:#fde68a;color:#78350f;">Review Now</a>
          </div>
        </div>
      <?php endif; ?>

      <!-- Pending questions alert -->
      <?php if ($pendingCount > 0): ?>
        <div class="card" style="margin:12px 0;background:#92400e;border-color:#f59e0b;">
          <div style="display:flex;align-items:center;justify-content:space-between;">
            <div>
              <h3 style="margin:0 0 8px;color:#fde68a;">Pending Questions</h3>
              <p style="margin:0;color:#fed7aa;font-size:14px;">
                <strong style="font-size:32px;color:#fde68a;"><?php echo $pendingCount; ?></strong> question<?php echo $pendingCount > 1 ? 's' : ''; ?> waiting for approval
              </p>
            </div>
            <a href="<?php echo BASE_URL; ?>admin/manage_questions.php?status=pending" class="btn-ask" style="background:#fde68a;color:#78350f;">Review Now</a>
          </div>
        </div>
      <?php endif; ?>

      <!-- Placeholder sections -->
      <div class="feed-list" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div class="card">
          <h3>Weekly Activity</h3>
          <p style="color:#9ca3af;font-size:12px;">(Placeholder) Add charts here later.</p>
        </div>
        <div class="card">
          <h3>Top 5 Modules</h3>
          <p style="color:#9ca3af;font-size:12px;">(Placeholder) Bar chart area.</p>
        </div>
      </div>
    </section>
  </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
