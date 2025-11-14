<?php
/**
 * Admin: Manage Contact Messages
 * View and delete contact form submissions
 */

require_once __DIR__ . '/../includes/functions.php';

// Sử dụng PHPMailer từ thư mục includes
require_once __DIR__ . '/../includes/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../includes/PHPMailer/SMTP.php';
require_once __DIR__ . '/../includes/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

ensure_admin();

// Handle email reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_email'])) {
    $to = $_POST['to_email'];
    $subject = $_POST['subject'];
    $message = $_POST['message'];
    $from = 'thuong.workspace@gmail.com';
    $from_name = 'Admin';

    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'thuonglv.workspace@gmail.com';
        $mail->Password = 'bwapxnrsfhzsbuyg'; // You need to use App Password from Google Account
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        // Recipients
        $mail->setFrom($from, $from_name);
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = nl2br(htmlspecialchars($message));
        $mail->AltBody = $message;

        $mail->send();
        $_SESSION['flash_message'] = 'Email đã được gửi thành công!';
        $_SESSION['flash_type'] = 'success';
    } catch (Exception $e) {
        $_SESSION['flash_message'] = "Không thể gửi email. Lỗi: {$mail->ErrorInfo}";
        $_SESSION['flash_type'] = 'error';
    }
    
    redirect('admin/manage_contacts.php');
}

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

              <!-- Reply Button and Form -->
              <div style="margin-top:12px;">
                <button onclick="toggleReplyForm(<?php echo $c['id']; ?>)" 
                        class="btn-ask" 
                        style="padding:8px 16px;font-size:13px;display:inline-block;cursor:pointer;">
                   Reply via Email
                </button>
                
                <div id="replyForm<?php echo $c['id']; ?>" style="display:none; margin-top:12px; background:#0b1220; padding:16px; border-radius:8px; border:1px solid #1f2937;">
                  <form method="POST" action="">
                    <input type="hidden" name="to_email" value="<?php echo e($c['email']); ?>">
                    <div style="margin-bottom:12px;">
                      <input type="text" name="subject" placeholder="Subject" required 
                             style="width:100%; padding:8px 12px; background:#111827; border:1px solid #1f2937; border-radius:4px; color:#e5e7eb; margin-bottom:8px;">
                    </div>
                    <div style="margin-bottom:12px;">
                      <textarea name="message" rows="4" required 
                                style="width:100%; padding:8px 12px; background:#111827; border:1px solid #1f2937; border-radius:4px; color:#e5e7eb; resize:vertical;"></textarea>
                    </div>
                    <button type="submit" name="send_email" 
                            style="padding:8px 16px; background:#3b82f6; color:white; border:none; border-radius:4px; cursor:pointer; font-weight:500;">
                      Send Email
                    </button>
                    <button type="button" 
                            onclick="toggleReplyForm(<?php echo $c['id']; ?>)" 
                            onmouseover="this.style.background='#ef4444'; this.style.color='white'"
                            onmouseout="this.style.background='#6b7280'; this.style.color='white'"
                            style="padding:8px 16px; background:#6b7280; color:white; border:none; border-radius:4px; cursor:pointer; transition: all 0.2s ease;">
                      Cancel
                    </button>
                  </form>
                </div>
              </div>
              
              <script>
              function toggleReplyForm(id) {
                const form = document.getElementById('replyForm' + id);
                if (form.style.display === 'none') {
                  form.style.display = 'block';
                } else {
                  form.style.display = 'none';
                }
              }
              </script>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
