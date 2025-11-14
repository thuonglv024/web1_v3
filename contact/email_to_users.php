<?php
require_once __DIR__ . '/../utils/EmailService.php';

if (isset($_POST['send'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        try {
            $emailService = new EmailService();
            $result = $emailService->sendWelcomeEmail($email);
            
            $style = $result['success'] ? 'color:green;' : 'color:red;';
            $icon = $result['success'] ? '✅' : '❌';
            echo "<p style='$style'>$icon {$result['message']}</p>";
        } catch (Exception $e) {
            echo "<p style='color:red;'>❌ Có lỗi xảy ra: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p style='color:red;'>Địa chỉ email không hợp lệ.</p>";
    }
}
?>
