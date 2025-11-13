<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

if (isset($_POST['send'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mail = new PHPMailer(true);
        try {
            // Cấu hình SMTP Gmail
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'thuonglvgcd220213@fpt.edu.vn'; // Email của bạn
            $mail->Password = 'qyzn chfq digy sykb';   // App password (không phải mật khẩu Gmail!)
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            // Người gửi và người nhận
            $mail->setFrom('yourgmail@gmail.com', 'Your Website');
            $mail->addAddress($email);

            // Nội dung email
            $mail->isHTML(true);
            $mail->Subject = 'Xin chào từ website của chúng tôi!';
            $mail->Body    = '<h3>Cảm ơn bạn đã đăng ký!</h3><p>Chúng tôi sẽ gửi thông tin mới nhất cho bạn.</p>';

            $mail->send();
            echo "<p style='color:green;'>✅ Email đã được gửi thành công!</p>";
        } catch (Exception $e) {
            echo "<p style='color:red;'>❌ Gửi thất bại. Lỗi: {$mail->ErrorInfo}</p>";
        }
    } else {
        echo "<p style='color:red;'>Địa chỉ email không hợp lệ.</p>";
    }
}
?>
