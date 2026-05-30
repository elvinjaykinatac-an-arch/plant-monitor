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

echo "GMAIL_USER: " . ($senderEmail ? $senderEmail : '❌ NOT SET') . "<br>";
echo "ALERT_EMAIL: " . ($recipientEmail ? $recipientEmail : '❌ NOT SET') . "<br>";
echo "GMAIL_APP_PASSWORD: " . ($senderPassword ? '✅ SET (length: ' . strlen($senderPassword) . ')' : '❌ NOT SET') . "<br><br>";

$mail = new PHPMailer(true);

try {
    $mail->SMTPDebug  = SMTP::DEBUG_SERVER;
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $senderEmail;
    $mail->Password   = $senderPassword;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;

    $mail->setFrom($senderEmail, 'Plant Monitor');
    $mail->addAddress($recipientEmail);

    $mail->isHTML(false);
    $mail->Subject = 'Test Email';
    $mail->Body    = 'This is a test email from Plant Monitor.';

    $mail->send();
    echo '<br>✅ Email sent successfully!';

} catch (Exception $e) {
    echo '<br>❌ Error: ' . $mail->ErrorInfo;
}
