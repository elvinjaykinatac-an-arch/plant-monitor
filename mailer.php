<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'Exception.php';
require 'PHPMailer.php';
require 'SMTP.php';

function sendEmailAlert($pumpStatus, $moisturePercent, $timestamp) {
    $recipientEmail = getenv('ALERT_EMAIL') ?: 'your@gmail.com';
    $senderEmail    = getenv('GMAIL_USER') ?: 'your@gmail.com';
    $senderPassword = getenv('GMAIL_APP_PASSWORD') ?: '';

    $soilStatus        = $pumpStatus === 'ON' ? 'Dry' : 'Wet';
    $moistureFormatted = number_format($moisturePercent, 1);

    $subject = $pumpStatus === 'ON'
        ? '⚠️ Plant Alert: Soil is Dry — Pump Turned ON'
        : '✅ Plant Alert: Soil is Wet — Pump Turned OFF';

    $body = "=============================\n"
          . "  PLANT MONITORING SYSTEM\n"
          . "=============================\n\n"
          . "Soil Status  : " . $soilStatus . "\n"
          . "Soil Moisture: " . $moistureFormatted . "%\n"
          . "Pump Status  : " . $pumpStatus . "\n"
          . "Date & Time  : " . $timestamp . "\n\n"
          . "=============================\n"
          . "Automatic Plant Watering System\n"
          . "This is an automated alert.\n";

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $senderEmail;
        $mail->Password   = $senderPassword;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // Recipients
        $mail->setFrom($senderEmail, 'Plant Monitor');
        $mail->addAddress($recipientEmail, 'Plant Owner');

        // Content
        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}
