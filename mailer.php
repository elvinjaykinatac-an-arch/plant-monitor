<?php
function sendEmailAlert($pumpStatus, $moisturePercent, $timestamp) {
    $recipientEmail = getenv('ALERT_EMAIL') ?: 'your@gmail.com';
    $senderEmail    = getenv('GMAIL_USER') ?: 'your@gmail.com';
    $senderPassword = getenv('GMAIL_APP_PASSWORD') ?: '';

    $subject = $pumpStatus === 'ON'
        ? '⚠️ Plant Alert: Soil is Dry — Pump Turned ON'
        : '✅ Plant Alert: Soil is Wet — Pump Turned OFF';

    $soilStatus = $pumpStatus === 'ON' ? 'Dry' : 'Wet';
    $moistureFormatted = number_format($moisturePercent, 1);

    $body = "=============================\r\n"
          . "  PLANT MONITORING SYSTEM\r\n"
          . "=============================\r\n\r\n"
          . "Soil Status  : " . $soilStatus . "\r\n"
          . "Soil Moisture: " . $moistureFormatted . "%\r\n"
          . "Pump Status  : " . $pumpStatus . "\r\n"
          . "Date & Time  : " . $timestamp . "\r\n\r\n"
          . "=============================\r\n"
          . "Automatic Plant Watering System\r\n"
          . "This is an automated alert.\r\n";

    return sendViaSSL($senderEmail, $senderPassword, $recipientEmail, $subject, $body);
}

function sendViaSSL($from, $password, $to, $subject, $body) {
    $smtpHost = 'ssl://smtp.gmail.com';
    $smtpPort = 465;

    $socket = fsockopen($smtpHost, $smtpPort, $errno, $errstr, 15);
    if (!$socket) {
        error_log("SMTP SSL Error: $errstr ($errno)");
        return false;
    }

    // Read greeting
    fgets($socket, 1024);

    // Say hello
    fputs($socket, "EHLO plant-monitor\r\n");
    while ($line = fgets($socket, 1024)) {
        if (substr($line, 3, 1) === ' ') break;
    }

    // Login
    fputs($socket, "AUTH LOGIN\r\n");
    fgets($socket, 1024);

    fputs($socket, base64_encode($from) . "\r\n");
    fgets($socket, 1024);

    fputs($socket, base64_encode($password) . "\r\n");
    $authResponse = fgets($socket, 1024);

    if (strpos($authResponse, '235') === false) {
        error_log("SMTP Auth Failed: " . $authResponse);
        fclose($socket);
        return false;
    }

    // Set sender and recipient
    fputs($socket, "MAIL FROM:<{$from}>\r\n");
    fgets($socket, 1024);

    fputs($socket, "RCPT TO:<{$to}>\r\n");
    fgets($socket, 1024);

    // Send data
    fputs($socket, "DATA\r\n");
    fgets($socket, 1024);

    $message = "From: Plant Monitor <{$from}>\r\n"
             . "To: {$to}\r\n"
             . "Subject: {$subject}\r\n"
             . "MIME-Version: 1.0\r\n"
             . "Content-Type: text/plain; charset=UTF-8\r\n"
             . "\r\n"
             . $body
             . "\r\n.\r\n";

    fputs($socket, $message);
    $dataResponse = fgets($socket, 1024);

    fputs($socket, "QUIT\r\n");
    fclose($socket);

    return strpos($dataResponse, '250') !== false;
}
