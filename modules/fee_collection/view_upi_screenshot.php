<?php
/**
 * Secure UPI Screenshot Viewer - Admin
 * Admins can view any student's screenshot
 */
require_once '../../config/database.php';
require_once '../../includes/session.php';

requireLogin();

$upiPaymentId = (int)($_GET['id'] ?? 0);

if ($upiPaymentId <= 0) {
    die('Invalid request.');
}

$db = getDB();
$payment = $db->fetchOne(
    "SELECT screenshot_path FROM upi_payments WHERE upi_payment_id = :id",
    ['id' => $upiPaymentId]
);

if (!$payment || empty($payment['screenshot_path'])) {
    die('Screenshot not found.');
}

$filePath = $_SERVER['DOCUMENT_ROOT'] . '/' . $payment['screenshot_path'];
if (!file_exists($filePath)) {
    die('File not found.');
}

$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
$mimeTypes = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];
$mimeType = $mimeTypes[$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $mimeType);
header('Content-Disposition: inline; filename="upi_screenshot.' . $ext . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, max-age=3600');
readfile($filePath);
exit;
