<?php
function sendEmailAlert($pumpStatus, $moisturePercent, $timestamp) {
    $recipientEmail = getenv('ALERT_EMAIL') ?: 'your@email.com';
    $senderEmail    = getenv('GMAIL_USER') ?: 'your@gmail.com';
    $senderPassword = getenv('GMAIL_APP_PASSWORD') ?: '';

    $subject = $pumpStatus === 'ON'
        ? '🌱 Plant Alert: Pump Turned ON — Soil is Dry'
        : '✅ Plant Alert: Pump Turned OFF — Soil is Wet';

    $soilStatus = $pumpStatus === 'ON' ? 'Dry' : 'Wet';
    $moistureFormatted = number_format($moisturePercent, 1);

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

    return sendViaSMTP($senderEmail, $senderPassword, $recipientEmail, $subject, $body);
}

function sendViaSMTP($from, $password, $to, $subject, $body) {
    $smtpHost = 'smtp.gmail.com';
    $smtpPort = 587;

    // Open socket connection to Gmail SMTP
    $socket = fsockopen($smtpHost, $smtpPort, $errno, $errstr, 10);
    if (!$socket) {
        error_log("SMTP Error: Could not connect to $smtpHost:$smtpPort - $errstr");
        return false;
    }

    // Read greeting
    fgets($socket, 1024);

    // Say hello
    fputs($socket, "EHLO plant-monitor\r\n");
    while ($line = fgets($socket, 1024)) {
        if (substr($line, 3, 1) === ' ') break;
    }

    // Start TLS
    fputs($socket, "STARTTLS\r\n");
    fgets($socket, 1024);

    // Upgrade connection to TLS
    stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

    // Say hello again after TLS
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

    // Check if login was successful (235 = success)
    if (strpos($authResponse, '235') === false) {
        error_log("SMTP Auth Failed: " . $authResponse);
        fclose($socket);
        return false;
    }

    // Set sender
    fputs($socket, "MAIL FROM:<{$from}>\r\n");
    fgets($socket, 1024);

    // Set recipient
    fputs($socket, "RCPT TO:<{$to}>\r\n");
    fgets($socket, 1024);

    // Start message data
    fputs($socket, "DATA\r\n");
    fgets($socket, 1024);

    // Send email headers and body
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

    // Quit
    fputs($socket, "QUIT\r\n");
    fclose($socket);

    // 250 = email sent successfully
    return strpos($dataResponse, '250') !== false;
}
