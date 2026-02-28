<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Receipt - <?= $receipt_no ?></title>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; }
        }
        @page {
            size: A4;
            margin: 15mm;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', sans-serif; font-size: 12px; line-height: 1.6; color: #333; background: #fff; }
        .receipt-container { max-width: 210mm; margin: 0 auto; padding: 20px; background: #fff; }
        .receipt-header { text-align: center; border-bottom: 3px solid #333; padding-bottom: 15px; margin-bottom: 20px; }
        .receipt-header h1 { font-size: 24px; margin-bottom: 5px; text-transform: uppercase; }
        .receipt-header .school-info { font-size: 11px; color: #666; margin-top: 5px; }
        .receipt-title { text-align: center; background: #f0f0f0; padding: 10px; margin-bottom: 20px; font-size: 16px; font-weight: bold; text-transform: uppercase; }
        .receipt-details { margin-bottom: 20px; }
        .detail-row { display: flex; justify-content: space-between; padding: 8px; border-bottom: 1px solid #eee; }
        .detail-row:nth-child(even) { background: #f8f8f8; }
        .detail-label { font-weight: bold; min-width: 150px; }
        .detail-value { text-align: right; flex: 1; }
        .amount-box { background: #333; color: #fff; padding: 20px; text-align: center; margin: 20px 0; border-radius: 5px; }
        .amount-box .label { font-size: 12px; margin-bottom: 5px; }
        .amount-box .amount { font-size: 28px; font-weight: bold; }
        .signatures { display: flex; justify-content: space-between; margin-top: 50px; padding-top: 20px; }
        .signature-block { text-align: center; flex: 1; }
        .signature-line { border-top: 2px solid #333; margin-top: 30px; padding-top: 5px; }
        .footer { text-align: center; margin-top: 30px; padding-top: 15px; border-top: 1px solid #ddd; font-size: 10px; color: #999; }
        .print-button { position: fixed; top: 20px; right: 20px; background: #6366f1; color: #fff; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; z-index: 1000; }
        .print-button:hover { background: #4f46e5; }
        @media print {
            .print-button { display: none; }
        }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">
        <i class="fas fa-print"></i> Print / Save as PDF
    </button>

    <div class="receipt-container">
        <div class="receipt-header">
            <h1><?= $school_name ?? 'School Name' ?></h1>
            <div class="school-info">
                <?php if (!empty($school_address)): ?>
                    <?= $school_address ?><br>
                <?php endif; ?>
                <?php if (!empty($school_phone)): ?>
                    Phone: <?= $school_phone ?>
                <?php endif; ?>
                <?php if (!empty($school_email)): ?>
                    | Email: <?= $school_email ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="receipt-title">Fee Payment Receipt</div>

        <div class="receipt-details">
            <div class="detail-row">
                <span class="detail-label">Receipt No:</span>
                <span class="detail-value"><strong><?= $receipt_no ?></strong></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Date:</span>
                <span class="detail-value"><?= date('d-m-Y', strtotime($payment_date)) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Academic Session:</span>
                <span class="detail-value"><?= $session_name ?? '' ?></span>
            </div>
        </div>

        <div class="receipt-title" style="font-size: 14px; background: #fff; border-top: 2px solid #333; border-bottom: 2px solid #333;">
            STUDENT DETAILS
        </div>

        <div class="receipt-details">
            <div class="detail-row">
                <span class="detail-label">Student Name:</span>
                <span class="detail-value"><?= $student_name ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Father's Name:</span>
                <span class="detail-value"><?= $father_name ?? '-' ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Admission No:</span>
                <span class="detail-value"><?= $admission_no ?? '-' ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Class:</span>
                <span class="detail-value"><?= $class_name ?> - <?= $section_name ?? '' ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Roll No:</span>
                <span class="detail-value"><?= $roll_no ?? '-' ?></span>
            </div>
            <?php if (!empty($contact_number)): ?>
            <div class="detail-row">
                <span class="detail-label">Contact:</span>
                <span class="detail-value"><?= $contact_number ?></span>
            </div>
            <?php endif; ?>
        </div>

        <div class="receipt-title" style="font-size: 14px; background: #fff; border-top: 2px solid #333; border-bottom: 2px solid #333;">
            PAYMENT DETAILS
        </div>

        <div class="receipt-details">
            <?php if (!empty($fee_components) && is_array($fee_components)): ?>
                <?php foreach ($fee_components as $component): ?>
                <div class="detail-row">
                    <span class="detail-label"><?= $component['name'] ?>:</span>
                    <span class="detail-value">₹ <?= number_format($component['amount'], 2) ?></span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!empty($fine_amount) && $fine_amount > 0): ?>
            <div class="detail-row" style="color: #dc2626;">
                <span class="detail-label">Late Fee / Fine:</span>
                <span class="detail-value">₹ <?= number_format($fine_amount, 2) ?></span>
            </div>
            <?php endif; ?>

            <?php if (!empty($discount_amount) && $discount_amount > 0): ?>
            <div class="detail-row" style="color: #16a34a;">
                <span class="detail-label">Discount:</span>
                <span class="detail-value">- ₹ <?= number_format($discount_amount, 2) ?></span>
            </div>
            <?php endif; ?>
        </div>

        <div class="amount-box">
            <div class="label">TOTAL AMOUNT PAID</div>
            <div class="amount">₹ <?= number_format($amount_paid, 2) ?></div>
        </div>

        <div class="receipt-details">
            <div class="detail-row">
                <span class="detail-label">Payment Mode:</span>
                <span class="detail-value"><?= $payment_mode ?></span>
            </div>
            <?php if (!empty($transaction_id)): ?>
            <div class="detail-row">
                <span class="detail-label">Transaction ID:</span>
                <span class="detail-value"><?= $transaction_id ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($remarks)): ?>
            <div class="detail-row">
                <span class="detail-label">Remarks:</span>
                <span class="detail-value"><?= htmlspecialchars($remarks) ?></span>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($amount_in_words)): ?>
        <div style="padding: 15px; background: #f8f8f8; margin: 20px 0; border-left: 4px solid #333;">
            <strong>Amount in Words:</strong> <?= $amount_in_words ?>
        </div>
        <?php endif; ?>

        <div class="signatures">
            <div class="signature-block">
                <div class="signature-line">Collected By</div>
                <div><?= $collected_by ?? '' ?></div>
            </div>
            <div class="signature-block">
                <div class="signature-line">Authorized Signature</div>
            </div>
        </div>

        <div class="footer">
            <p>This is a computer-generated receipt and does not require signature.</p>
            <p>&copy; <?= date('Y') ?> <?= $school_name ?? 'School' ?>. Powered by Fee Management System</p>
        </div>
    </div>

    <script>
        // Auto-print on load (optional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
