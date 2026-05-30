<?php
function sendEmailAlert($pumpStatus, $moisturePercent, $timestamp) {
    $recipientEmail = getenv('ALERT_EMAIL') ?: 'elvinjaykinatacan99@gmail.com';
    $senderEmail    = getenv('GMAIL_USER') ?: 'elvinjaykinatacan99@gmail.com';
    $apiKey         = getenv('BREVO_API_KEY') ?: '';

    $subject = $pumpStatus === 'ON'
        ? '⚠️ Plant Alert: Soil is Dry — Pump Turned ON'
        : '✅ Plant Alert: Soil is Wet — Pump Turned OFF';

    $soilStatus        = $pumpStatus === 'ON' ? 'Dry' : 'Wet';
    $moistureFormatted = number_format($moisturePercent, 1);

    $textBody = "=============================\n"
              . "  PLANT MONITORING SYSTEM\n"
              . "=============================\n\n"
              . "Soil Status  : " . $soilStatus . "\n"
              . "Soil Moisture: " . $moistureFormatted . "%\n"
              . "Pump Status  : " . $pumpStatus . "\n"
              . "Date & Time  : " . $timestamp . "\n\n"
              . "=============================\n"
              . "Automatic Plant Watering System\n"
              . "This is an automated alert.\n";

    $data = json_encode([
        'sender'      => ['email' => $senderEmail, 'name' => 'Plant Monitor'],
        'to'          => [['email' => $recipientEmail, 'name' => 'Plant Owner']],
        'subject'     => $subject,
        'textContent' => $textBody
    ]);

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'api-key: ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $httpCode === 201;
}
