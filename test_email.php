<?php
require 'config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'Exception.php';
require 'PHPMailer.php';
require 'SMTP.php';

date_default_timezone_set('Asia/Manila');

$senderEmail    = getenv('GMAIL_USER');
$senderPassword = getenv('GMAIL_APP_PASSWORD');
$recipientEmail = getenv('ALERT_EMAIL');

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $senderEmail;
    $mail->Password   = $senderPassword;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->Timeout    = 30;

    $mail->setFrom($senderEmail, 'Plant Monitor');
    $mail->addAddress($recipientEmail);

    $mail->isHTML(false);
    $mail->Subject = 'Test Plant Monitor';
    $mail->Body    = 'This is a test email.';

    $mail->send();
    echo '✅ Email sent!';

} catch (Exception $e) {
    echo '❌ Error: ' . $mail->ErrorInfo;
}
