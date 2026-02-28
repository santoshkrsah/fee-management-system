<?php
/**
 * Student Receipt View
 * Displays a single payment receipt with print capability
 * Access control: student can only view their own receipts
 */
require_once '../config/database.php';
require_once '../includes/session.php';

requireStudentLogin();

$pageTitle = 'Fee Receipt';
$payment_id = (int)($_GET['id'] ?? 0);
$studentId = getStudentId();

if ($payment_id <= 0) {
    setFlashMessage('error', 'Invalid payment ID.');
    header("Location: receipts.php");
    exit();
}

$db = getDB();

// Fetch payment details - CRITICAL: WHERE student_id enforces access control
$payment = $db->fetchOne("
    SELECT
        fc.*,
        s.admission_no,
        s.roll_number,
        CONCAT(s.first_name, ' ', s.last_name) as student_name,
        s.father_name,
        s.mother_name,
        s.contact_number,
        s.gender,
        c.class_name,
        sec.section_name,
        a.full_name as collected_by_name
    FROM fee_collection fc
    JOIN students s ON fc.student_id = s.student_id
    JOIN classes c ON s.class_id = c.class_id
    JOIN sections sec ON s.section_id = sec.section_id
    JOIN admin a ON fc.collected_by = a.admin_id
    WHERE fc.payment_id = :id AND fc.student_id = :student_id
", ['id' => $payment_id, 'student_id' => $studentId]);

if (!$payment) {
    setFlashMessage('error', 'Receipt not found or access denied.');
    header("Location: receipts.php");
    exit();
}

// Log receipt view
logStudentAudit($studentId, 'STUDENT_VIEW_RECEIPT', 'fee_collection', $payment_id);

$pageTitle = 'Receipt - ' . $payment['receipt_no'];
$receiptSettings = getSettings();

// Build fee items array
$feeItems = [];
$sn = 1;
if ($payment['tuition_fee_paid'] > 0) $feeItems[] = ['sn' => $sn++, 'name' => 'Tuition Fee', 'amount' => $payment['tuition_fee_paid']];
if ($payment['exam_fee_paid'] > 0) $feeItems[] = ['sn' => $sn++, 'name' => 'Exam Fee', 'amount' => $payment['exam_fee_paid']];
if ($payment['library_fee_paid'] > 0) $feeItems[] = ['sn' => $sn++, 'name' => 'Library Fee', 'amount' => $payment['library_fee_paid']];
if ($payment['sports_fee_paid'] > 0) $feeItems[] = ['sn' => $sn++, 'name' => 'Sports Fee', 'amount' => $payment['sports_fee_paid']];
if ($payment['lab_fee_paid'] > 0) $feeItems[] = ['sn' => $sn++, 'name' => 'Lab Fee', 'amount' => $payment['lab_fee_paid']];
if ($payment['transport_fee_paid'] > 0) $feeItems[] = ['sn' => $sn++, 'name' => 'Transport Fee', 'amount' => $payment['transport_fee_paid']];
if ($payment['other_charges_paid'] > 0) $feeItems[] = ['sn' => $sn++, 'name' => 'Other Charges', 'amount' => $payment['other_charges_paid']];
if ($payment['fine'] > 0) $feeItems[] = ['sn' => $sn++, 'name' => 'Late Fee / Fine', 'amount' => $payment['fine'], 'type' => 'fine'];
if ($payment['discount'] > 0) $feeItems[] = ['sn' => $sn++, 'name' => 'Discount', 'amount' => $payment['discount'], 'type' => 'discount'];

// Amount in words
$amount = $payment['total_paid'];
$formatter = new NumberFormatter("en_IN", NumberFormatter::SPELLOUT);
$amountInWords = ucwords($formatter->format((int)$amount));
$paise = round(($amount - floor($amount)) * 100);
if ($paise > 0) {
    $amountInWords .= ' Rupees and ' . ucwords($formatter->format($paise)) . ' Paise Only';
} else {
    $amountInWords .= ' Rupees Only';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --accent: #1a56db;
            --accent-light: #e8eefb;
            --dark: #1e293b;
            --gray: #64748b;
            --gray-light: #f1f5f9;
            --border: #cbd5e1;
            --success: #059669;
            --danger: #dc2626;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            font-size: 13px;
            color: var(--dark);
            background: #f0f2f5;
            line-height: 1.5;
        }

        .actions-bar {
            max-width: 800px;
            margin: 20px auto 0;
            padding: 0 15px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .actions-bar .btn {
            padding: 8px 20px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-print { background: var(--accent); color: #fff; }
        .btn-print:hover { background: #1648b8; }
        .btn-back { background: #e2e8f0; color: var(--dark); }
        .btn-back:hover { background: #cbd5e1; }

        .receipt {
            max-width: 800px;
            margin: 15px auto 30px;
            background: #fff;
            border: 1px solid var(--border);
            padding: 0;
        }

        .receipt-accent {
            height: 6px;
            background: linear-gradient(90deg, var(--accent), #7c3aed, var(--accent));
        }

        .receipt-header {
            padding: 20px 30px 15px;
            display: flex;
            align-items: center;
            gap: 15px;
            border-bottom: 2px solid var(--dark);
        }

        .receipt-header .school-logo {
            width: 60px;
            height: 60px;
            object-fit: contain;
            flex-shrink: 0;
        }

        .receipt-header .school-info {
            flex: 1;
            text-align: center;
        }

        .receipt-header .school-info h1 {
            font-size: 20px;
            font-weight: 800;
            color: var(--dark);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .receipt-header .school-info .address {
            font-size: 11px;
            color: var(--gray);
            margin-bottom: 1px;
        }

        .receipt-header .school-info .contact-info {
            font-size: 11px;
            color: var(--gray);
        }

        .receipt-title-bar {
            background: var(--dark);
            color: #fff;
            text-align: center;
            padding: 6px;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .receipt-meta {
            display: flex;
            justify-content: space-between;
            padding: 10px 30px;
            background: var(--gray-light);
            border-bottom: 1px solid var(--border);
            font-size: 12px;
        }

        .receipt-meta span { color: var(--gray); }
        .receipt-meta strong { color: var(--dark); }

        .student-details {
            padding: 12px 30px;
            border-bottom: 1px solid var(--border);
        }

        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px 30px;
        }

        .detail-item {
            display: flex;
            padding: 3px 0;
            font-size: 12px;
        }

        .detail-item .label {
            color: var(--gray);
            min-width: 110px;
            flex-shrink: 0;
        }

        .detail-item .value {
            font-weight: 600;
            color: var(--dark);
        }

        .fee-table-section { padding: 0 30px; }

        .fee-table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0 0;
        }

        .fee-table thead th {
            background: var(--accent);
            color: #fff;
            padding: 7px 12px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .fee-table thead th:first-child { width: 45px; text-align: center; }
        .fee-table thead th:last-child { text-align: right; }

        .fee-table tbody td {
            padding: 6px 12px;
            border-bottom: 1px solid #e9ecef;
            font-size: 12px;
        }

        .fee-table tbody td:first-child { text-align: center; color: var(--gray); }
        .fee-table tbody td:last-child { text-align: right; font-weight: 500; font-variant-numeric: tabular-nums; }
        .fee-table tbody tr:nth-child(even) { background: #fafbfc; }
        .fee-table tbody tr.fine-row td { color: var(--danger); }
        .fee-table tbody tr.discount-row td { color: var(--success); }

        .fee-table tfoot td {
            padding: 8px 12px;
            font-size: 13px;
            font-weight: 800;
            border-top: 2px solid var(--dark);
        }

        .fee-table tfoot td:last-child {
            text-align: right;
            font-size: 15px;
            color: var(--accent);
            font-variant-numeric: tabular-nums;
        }

        .amount-words {
            margin: 8px 30px 0;
            padding: 8px 12px;
            background: var(--accent-light);
            border-left: 3px solid var(--accent);
            font-size: 11.5px;
            color: var(--dark);
        }

        .amount-words strong { color: var(--accent); }

        .payment-info {
            display: flex;
            justify-content: space-between;
            padding: 10px 30px;
            margin-top: 10px;
            border-top: 1px solid var(--border);
            font-size: 12px;
        }

        .payment-info .info-group {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .payment-info .label {
            color: var(--gray);
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .payment-info .value { font-weight: 600; color: var(--dark); }

        .receipt-footer {
            padding: 15px 30px 20px;
            margin-top: 15px;
            border-top: 1px dashed var(--border);
        }

        .signatures {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 20px;
        }

        .sig-block { text-align: center; min-width: 160px; }

        .sig-line {
            border-top: 1.5px solid var(--dark);
            padding-top: 4px;
            font-size: 11px;
            font-weight: 600;
            color: var(--dark);
        }

        .receipt-note {
            text-align: center;
            margin-top: 12px;
            padding-top: 10px;
            border-top: 1px solid var(--border);
            font-size: 10px;
            color: var(--gray);
        }

        @media print {
            .no-print { display: none !important; }
            body { background: #fff; margin: 0; padding: 0; }
            .receipt { margin: 0; border: none; box-shadow: none; max-width: 100%; }
            @page { size: A4; margin: 10mm 12mm; }
        }
    </style>
</head>
<body>

<!-- Action Buttons -->
<div class="actions-bar no-print">
    <a href="receipts.php" class="btn btn-back">
        <i class="fas fa-arrow-left"></i> Back to Receipts
    </a>
    <button onclick="window.print()" class="btn btn-print">
        <i class="fas fa-print"></i> Print / Download PDF
    </button>
</div>

<!-- Receipt -->
<div class="receipt">
    <!-- Accent Bar -->
    <div class="receipt-accent"></div>

    <!-- Header -->
    <div class="receipt-header">
        <?php if (!empty($receiptSettings['school_logo']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $receiptSettings['school_logo'])): ?>
            <img src="/<?php echo htmlspecialchars($receiptSettings['school_logo']); ?>" alt="Logo" class="school-logo">
        <?php endif; ?>
        <div class="school-info">
            <h1><?php echo htmlspecialchars($receiptSettings['school_name']); ?></h1>
            <?php if (!empty($receiptSettings['school_address'])): ?>
            <div class="address"><?php echo htmlspecialchars($receiptSettings['school_address']); ?></div>
            <?php endif; ?>
            <?php if (!empty($receiptSettings['school_phone']) || !empty($receiptSettings['school_email'])): ?>
            <div class="contact-info">
                <?php if (!empty($receiptSettings['school_phone'])): ?>Phone: <?php echo htmlspecialchars($receiptSettings['school_phone']); ?><?php endif; ?>
                <?php if (!empty($receiptSettings['school_phone']) && !empty($receiptSettings['school_email'])): ?> &bull; <?php endif; ?>
                <?php if (!empty($receiptSettings['school_email'])): ?>Email: <?php echo htmlspecialchars($receiptSettings['school_email']); ?><?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php if (!empty($receiptSettings['school_logo']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $receiptSettings['school_logo'])): ?>
            <div style="width:60px;"></div>
        <?php endif; ?>
    </div>

    <!-- Title Bar -->
    <div class="receipt-title-bar">Fee Payment Receipt</div>

    <!-- Receipt Meta -->
    <div class="receipt-meta">
        <div><span>Receipt No:</span> <strong><?php echo htmlspecialchars($payment['receipt_no']); ?></strong></div>
        <div><span>Date:</span> <strong><?php echo formatDate($payment['payment_date']); ?></strong></div>
        <div><span>Academic Year:</span> <strong><?php echo htmlspecialchars($payment['academic_year']); ?></strong></div>
        <?php if (!empty($payment['fee_month'])): ?>
        <?php $months = getAcademicMonths(); ?>
        <div><span>Fee Month:</span> <strong><?php echo htmlspecialchars($months[$payment['fee_month']] ?? 'N/A'); ?></strong></div>
        <?php endif; ?>
    </div>

    <!-- Student Details -->
    <div class="student-details">
        <div class="detail-grid">
            <div class="detail-item">
                <span class="label">Student Name</span>
                <span class="value"><?php echo htmlspecialchars($payment['student_name']); ?></span>
            </div>
            <div class="detail-item">
                <span class="label">Admission No</span>
                <span class="value"><?php echo htmlspecialchars($payment['admission_no']); ?></span>
            </div>
            <div class="detail-item">
                <span class="label">Father's Name</span>
                <span class="value"><?php echo htmlspecialchars($payment['father_name']); ?></span>
            </div>
            <div class="detail-item">
                <span class="label">Roll No</span>
                <span class="value"><?php echo htmlspecialchars($payment['roll_number'] ?? '-'); ?></span>
            </div>
            <div class="detail-item">
                <span class="label">Class / Section</span>
                <span class="value"><?php echo htmlspecialchars($payment['class_name'] . ' - ' . $payment['section_name']); ?></span>
            </div>
            <?php if (!empty($payment['contact_number'])): ?>
            <div class="detail-item">
                <span class="label">Contact</span>
                <span class="value"><?php echo htmlspecialchars($payment['contact_number']); ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Fee Breakdown Table -->
    <div class="fee-table-section">
        <table class="fee-table">
            <thead>
                <tr>
                    <th>S.No</th>
                    <th>Fee Description</th>
                    <th>Amount (&#8377;)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($feeItems as $item): ?>
                <tr class="<?php echo ($item['type'] ?? '') === 'fine' ? 'fine-row' : (($item['type'] ?? '') === 'discount' ? 'discount-row' : ''); ?>">
                    <td><?php echo $item['sn']; ?></td>
                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                    <td><?php
                        if (($item['type'] ?? '') === 'discount') {
                            echo '- ' . formatCurrency($item['amount']);
                        } else {
                            echo formatCurrency($item['amount']);
                        }
                    ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2">Total Amount Paid</td>
                    <td><?php echo formatCurrency($payment['total_paid']); ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Amount in Words -->
    <div class="amount-words">
        <strong>Amount in Words:</strong> <?php echo $amountInWords; ?>
    </div>

    <!-- Payment Info -->
    <div class="payment-info">
        <div class="info-group">
            <span class="label">Payment Mode</span>
            <span class="value"><?php echo htmlspecialchars($payment['payment_mode']); ?></span>
        </div>
        <?php if (!empty($payment['transaction_id'])): ?>
        <div class="info-group">
            <span class="label">Transaction ID</span>
            <span class="value"><?php echo htmlspecialchars($payment['transaction_id']); ?></span>
        </div>
        <?php endif; ?>
        <?php if (!empty($payment['remarks'])): ?>
        <div class="info-group">
            <span class="label">Remarks</span>
            <span class="value"><?php echo htmlspecialchars($payment['remarks']); ?></span>
        </div>
        <?php endif; ?>
        <div class="info-group">
            <span class="label">Collected By</span>
            <span class="value"><?php echo htmlspecialchars($payment['collected_by_name']); ?></span>
        </div>
    </div>

    <!-- Footer -->
    <div class="receipt-footer">
        <div class="signatures">
            <div class="sig-block">
                <div class="sig-line">Receiver's Signature</div>
            </div>
            <div class="sig-block">
                <div class="sig-line">Authorized Signatory</div>
            </div>
        </div>
        <div class="receipt-note">
            This is a computer-generated receipt. &bull; Printed on <?php echo date('d-M-Y h:i A'); ?>
        </div>
    </div>
</div>

</body>
</html>
