<?php
/**
 * UPI Payment Helper Functions
 * Provides UPI-related utilities for the fee management system
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/settings_helper.php';

/**
 * Get UPI settings (decrypted)
 * @return array ['upi_enabled' => bool, 'upi_id' => string, 'upi_payee_name' => string]
 */
function getUpiSettings() {
    $settings = SettingsHelper::getMultiple(['upi_enabled', 'upi_id', 'upi_payee_name']);
    $upiId = $settings['upi_id'] ?? '';
    if (!empty($upiId)) {
        $decrypted = decryptData($upiId);
        $upiId = ($decrypted !== false) ? $decrypted : '';
    }
    return [
        'upi_enabled' => (bool)($settings['upi_enabled'] ?? false),
        'upi_id' => $upiId,
        'upi_payee_name' => $settings['upi_payee_name'] ?? ''
    ];
}

/**
 * Check if UPI payments are enabled and configured
 * @return bool
 */
function isUpiEnabled() {
    try {
        $settings = getUpiSettings();
        return $settings['upi_enabled'] && !empty($settings['upi_id']);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Generate UPI payment URL for QR code
 * @param string $upiId The UPI VPA
 * @param string $payeeName Display name
 * @param float $amount Payment amount
 * @param string $transactionNote Remark (admission number)
 * @return string UPI deep link URL
 */
function generateUpiUrl($upiId, $payeeName, $amount, $transactionNote = '') {
    $params = [
        'pa' => $upiId,
        'pn' => $payeeName,
        'am' => number_format($amount, 2, '.', ''),
        'cu' => 'INR'
    ];
    if (!empty($transactionNote)) {
        $params['tn'] = $transactionNote;
    }
    return 'upi://pay?' . http_build_query($params);
}

/**
 * Check if student has a pending UPI payment
 * @param int $studentId
 * @param string $academicYear
 * @return array|false The pending payment record or false
 */
function getStudentPendingUpiPayment($studentId, $academicYear) {
    try {
        $db = getDB();
        return $db->fetchOne(
            "SELECT * FROM upi_payments WHERE student_id = :sid AND academic_year = :year AND status = 'Pending'",
            ['sid' => $studentId, 'year' => $academicYear]
        );
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Check if UTR number already exists
 * @param string $utr
 * @return bool
 */
function isUtrDuplicate($utr) {
    try {
        $db = getDB();
        $result = $db->fetchOne(
            "SELECT upi_payment_id FROM upi_payments WHERE utr_number = :utr",
            ['utr' => $utr]
        );
        return $result !== false;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get pending UPI payment count (for admin badge)
 * @return int
 */
function getPendingUpiCount() {
    static $count = null;
    if ($count === null) {
        try {
            $db = getDB();
            $result = $db->fetchOne("SELECT COUNT(*) as cnt FROM upi_payments WHERE status = 'Pending'");
            $count = (int)($result['cnt'] ?? 0);
        } catch (Exception $e) {
            $count = 0;
        }
    }
    return $count;
}

/**
 * Distribute payment proportionally across fee components
 *
 * For each component: component_paid = round((component_remaining / total_remaining) * payment_amount, 2)
 * Adjusts the largest component by the rounding difference to ensure exact total.
 *
 * @param float $paymentAmount Total amount being paid
 * @param array $feeStructure Fee structure row (tuition_fee, exam_fee, etc.)
 * @param array $paidSoFar Aggregated amounts already paid per component
 * @return array Component-wise distribution (keyed as tuition_fee_paid, exam_fee_paid, etc.)
 */
function distributePaymentProportionally($paymentAmount, $feeStructure, $paidSoFar) {
    $components = ['tuition_fee', 'exam_fee', 'library_fee', 'sports_fee',
                   'lab_fee', 'transport_fee', 'other_charges'];

    // Calculate remaining per component
    $remaining = [];
    $totalRemaining = 0;
    foreach ($components as $comp) {
        $structureAmount = (float)($feeStructure[$comp] ?? 0);
        $paidAmount = (float)($paidSoFar[$comp . '_paid'] ?? 0);
        $rem = max(0, $structureAmount - $paidAmount);
        $remaining[$comp] = $rem;
        $totalRemaining += $rem;
    }

    // Initialize result with zeros
    $result = [];
    foreach ($components as $comp) {
        $result[$comp . '_paid'] = 0;
    }

    if ($totalRemaining <= 0 || $paymentAmount <= 0) {
        return $result;
    }

    // Cap payment at total remaining
    $effectivePayment = min($paymentAmount, $totalRemaining);

    // Distribute proportionally
    $distributedTotal = 0;
    $maxComp = null;
    $maxAmount = -1;

    foreach ($components as $comp) {
        if ($remaining[$comp] > 0) {
            $share = round(($remaining[$comp] / $totalRemaining) * $effectivePayment, 2);
        } else {
            $share = 0;
        }
        $result[$comp . '_paid'] = $share;
        $distributedTotal += $share;

        if ($share > $maxAmount) {
            $maxAmount = $share;
            $maxComp = $comp . '_paid';
        }
    }

    // Fix rounding difference by adjusting the largest component
    $diff = round($effectivePayment - $distributedTotal, 2);
    if ($diff != 0 && $maxComp) {
        $result[$maxComp] = round($result[$maxComp] + $diff, 2);
    }

    return $result;
}

/**
 * Get the upload path for UPI screenshots
 * Creates directory if it doesn't exist
 * @return string Relative path from document root
 */
function getUpiScreenshotUploadPath() {
    $year = date('Y');
    $month = date('m');
    $relativePath = 'uploads/upi_screenshots/' . $year . '/' . $month;
    $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/' . $relativePath;

    if (!is_dir($fullPath)) {
        mkdir($fullPath, 0755, true);
    }

    return $relativePath;
}

/**
 * Handle UPI screenshot upload
 * @param array $file The $_FILES['screenshot'] array
 * @param int $studentId
 * @return string|null Relative file path or null if no upload
 * @throws Exception on validation failure
 */
function handleUpiScreenshotUpload($file, $studentId) {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Screenshot upload failed. Please try again.');
    }

    // Validate file size (max 5MB)
    $maxSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        throw new Exception('Screenshot file size must be less than 5MB.');
    }

    // Validate file type
    $allowedMimes = ['image/jpeg', 'image/png'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedMimes)) {
        throw new Exception('Only JPG and PNG images are allowed for screenshots.');
    }

    // Validate it's actually an image
    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        throw new Exception('Uploaded file is not a valid image.');
    }

    // Determine extension
    $ext = ($mimeType === 'image/png') ? 'png' : 'jpg';

    // Generate unique filename
    $filename = 'upi_' . $studentId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

    // Get upload path
    $uploadDir = getUpiScreenshotUploadPath();
    $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/' . $uploadDir . '/' . $filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
        throw new Exception('Failed to save screenshot. Please try again.');
    }

    return $uploadDir . '/' . $filename;
}
?>
