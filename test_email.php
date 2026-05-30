<?php
require 'config.php';
require 'mailer.php';

date_default_timezone_set('Asia/Manila');
$timestamp = date('m-d-Y h:i A');

$result = sendEmailAlert('ON', 25.5, $timestamp);

if ($result) {
    echo "✅ Email sent successfully!";
} else {
    echo "❌ Email failed to send. Check your Gmail credentials.";
}
