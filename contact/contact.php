<?php
require_once __DIR__ . '/../includes/functions.php';

$success = '';
$error = '';
$errors = ['name'=>'','email'=>'','message'=>''];
if (isPost()) {
  $name = sanitize($_POST['name'] ?? '');
  $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL) ? $_POST['email'] : '';
  $message = trim($_POST['message'] ?? '');
  if (!$name || !$email || !$message) {
    if (!$name) $errors['name'] = 'The field is required';
    if (!$email) $errors['email'] = 'Please enter a valid email address';
    if (!$message) $errors['message'] = 'The field is required';
    $error = 'Please correct the highlighted fields.';
  } else {
    // Insert contact message into database
    $stmt = $pdo->prepare("INSERT INTO contacts (name, email, message) VALUES (?,?,?)");
    $stmt->execute([$name, $email, $message]);
    
    // Try to send email notification (optional)
    $subject = '[Q&A Contact] New message from ' . $name;
    $messageBody = "Name: $name\nEmail: $email\n\n$message";
    
    // Try using PHPMailer if available
    $emailSent = false;
    if (file_exists(__DIR__ . '/../assets/phpmailer/PHPMailer.php')) {
      try {
        require_once __DIR__ . '/../assets/phpmailer/PHPMailer.php';
        require_once __DIR__ . '/../assets/phpmailer/SMTP.php';
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;
        
        $mail->setFrom($email, $name);
        $mail->addAddress(ADMIN_EMAIL);
        $mail->Subject = $subject;
        $mail->Body = nl2br(htmlspecialchars($messageBody));
        $mail->isHTML(true);
        
        $emailSent = $mail->send();
      } catch (Exception $e) {
        error_log('PHPMailer Error: ' . $e->getMessage());
      }
    }
    
    // Fallback to PHP mail() if PHPMailer not available or failed
    if (!$emailSent) {
      $headers = "From: $name <$email>\r\n";
      $headers .= "Reply-To: $email\r\n";
      $headers .= "X-Mailer: PHP/" . phpversion();
      $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
      @mail(ADMIN_EMAIL, $subject, $messageBody, $headers);
    }
    
    $success = 'Your message has been sent. Thank you!';
  }
}

// Sidebar data similar to Home
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
<main class="container home-light">
  <section class="contact-two-col">
    <div class="contact-left">
      <?php if ($error) alert($error, 'error'); ?>
      <?php if ($success) alert($success, 'success'); ?>
      <form method="post" novalidate class="contact-grid">
        <div class="form-group <?php echo $errors['email'] ? 'has-error' : ''; ?>">
          <input name="email" type="email" placeholder="Enter a valid email address" value="<?php echo e($_POST['email'] ?? ''); ?>" class="<?php echo $errors['email'] ? 'is-invalid' : ''; ?>" required>
          <?php if ($errors['email']): ?><div class="field-error"><?php echo e($errors['email']); ?></div><?php endif; ?>
        </div>
        <div class="form-group <?php echo $errors['name'] ? 'has-error' : ''; ?>">
          <input name="name" placeholder="Enter your Name" value="<?php echo e($_POST['name'] ?? ''); ?>" class="<?php echo $errors['name'] ? 'is-invalid' : ''; ?>" required>
          <?php if ($errors['name']): ?><div class="field-error">The field is required</div><?php endif; ?>
        </div>
        <div class="form-group">
          <input name="address" placeholder="Enter your address" value="<?php echo e($_POST['address'] ?? ''); ?>">
        </div>
        <div class="form-group">
          <input name="phone" placeholder="Enter your phone (e.g. +14155)" value="<?php echo e($_POST['phone'] ?? ''); ?>">
        </div>
        <div class="form-group form-full <?php echo $errors['message'] ? 'has-error' : ''; ?>">
          <textarea name="message" rows="6" placeholder="Enter your message" class="<?php echo $errors['message'] ? 'is-invalid' : ''; ?>" required><?php echo e($_POST['message'] ?? ''); ?></textarea>
          <?php if ($errors['message']): ?><div class="field-error">The field is required</div><?php endif; ?>
        </div>
        <div class="form-full">
          <button class="btn-orange" type="submit">Submit</button>
        </div>
      </form>

    </div>

    <div class="contact-right">
      <p class="eyebrow">We would love to hear from you!</p>
      <h1><span>Contact </span><span class="accent-orange">Us</span></h1>
      <p>Right now there is no us, I'm running the show alone. So every feedback you provide, any suggestions you have and any new idea you like to share — please don't hesitate, write to me immediately.</p>
      <p>I'm a social animal. Animal because I've some degree of randomness in my behaviour. Social because I love to hear and share with people.</p>
    </div>
  </section>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
