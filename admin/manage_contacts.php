<?php
/**
 * Admin: Manage Contact Messages
 * View and delete contact form submissions
 */

require_once __DIR__ . '/../includes/functions.php';
ensure_admin();

// Handle delete action
if (isset($_GET['delete'])) {
  $id = (int)$_GET['delete'];
  $pdo->prepare("DELETE FROM contacts WHERE id=?")->execute([$id]);
  $_SESSION['flash_message'] = 'Tin nhắn đã được xóa!';
  $_SESSION['flash_type'] = 'success';
  redirect('admin/manage_contacts.php');
}

// Handle mark as read
if (isset($_GET['mark_read'])) {
  $id = (int)$_GET['mark_read'];
  $pdo->prepare("UPDATE contacts SET is_read=1 WHERE id=?")->execute([$id]);
  redirect('admin/manage_contacts.php');
}

// Fetch all contact messages, ordered by newest first
$stmt = $pdo->query("SELECT * FROM contacts ORDER BY created_at DESC");
$contacts = $stmt->fetchAll();

// Count unread messages
$unreadCount = (int)$pdo->query("SELECT COUNT(*) FROM contacts WHERE is_read=0")->fetchColumn();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<main class="container home-dark">
  <div class="layout-grid">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <section class="feed" style="grid-column:2/4;">
      <div class="feed-header mb-20">
        <h1>Contact Messages (<?php echo count($contacts); ?>)</h1>
        <?php if ($unreadCount > 0): ?>
          <span style="background:#f59e0b;color:#111;padding:6px 12px;border-radius:999px;font-size:14px;font-weight:600;">
            <?php echo $unreadCount; ?> Unread
          </span>
        <?php endif; ?>
      </div>

      <?php if (empty($contacts)): ?>
        <div class="card text-center" style="padding:60px 20px;">
          <p style="color:#9ca3af;font-size:16px;margin:0;">No contact messages yet.</p>
        </div>
      <?php else: ?>
        <div class="flex flex-col gap-12">
          <?php foreach ($contacts as $c): ?>
            <article class="card" style="<?php echo !$c['is_read'] ? 'border-left:4px solid #f59e0b;' : ''; ?>">
              <!-- Header -->
              <div class="flex justify-between items-start gap-16 mb-12">
                <div style="flex:1;">
                  <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;">
                    <h3 style="margin:0;font-size:18px;color:#e5e7eb;">
                      <?php echo e($c['name']); ?>
                    </h3>
                    <?php if (!$c['is_read']): ?>
                      <span style="background:#f59e0b;color:#111;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:600;">NEW</span>
                    <?php endif; ?>
                  </div>
                  <div class="item-meta">
                    <span><?php echo e($c['email']); ?></span>
                    <span><?php echo date('d M Y, H:i', strtotime($c['created_at'])); ?></span>
                  </div>
                </div>
                <!-- Actions -->
                <div style="display:flex;gap:8px;">
                  <?php if (!$c['is_read']): ?>
                    <a href="<?php echo BASE_URL; ?>admin/manage_contacts.php?mark_read=<?php echo (int)$c['id']; ?>" 
                       class="tag-chip" 
                       style="padding:6px 12px;font-size:12px;background:#22c55e;border-color:#22c55e;color:#0b1220;">
                      Mark Read
                    </a>
                  <?php endif; ?>
                  <a href="<?php echo BASE_URL; ?>admin/manage_contacts.php?delete=<?php echo (int)$c['id']; ?>" 
                     class="tag-chip" 
                     style="padding:6px 12px;font-size:12px;border-color:#dc2626;" 
                     onclick="return confirm('Delete this message?')">
                    Delete
                  </a>
                </div>
              </div>

              <!-- Message Content -->
              <div style="background:#0b1220;padding:16px;border-radius:8px;border:1px solid #1f2937;">
                <p style="color:#cbd5e1;line-height:1.6;margin:0;white-space:pre-wrap;"><?php echo e($c['message']); ?></p>
              </div>

              <!-- Reply Button -->
              <div style="margin-top:12px;">
                <a href="../includes/comingSoon.php" 
                   class="btn-ask" 
                   style="padding:8px 16px;font-size:13px;display:inline-block;">
                   Reply via Email
                </a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
