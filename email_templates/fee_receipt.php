<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Receipt</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f4f4f4; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .header { text-align: center; border-bottom: 3px solid #6366f1; padding-bottom: 20px; margin-bottom: 30px; }
        .header h1 { color: #6366f1; margin: 0; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 10px; padding: 10px; background: #f8f9fa; border-radius: 5px; }
        .info-label { font-weight: bold; color: #666; }
        .info-value { color: #333; }
        .amount-box { background: #6366f1; color: #fff; padding: 20px; border-radius: 8px; text-align: center; margin: 20px 0; }
        .amount-box h2 { margin: 0; font-size: 32px; }
        .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #999; font-size: 12px; }
        .button { display: inline-block; background: #6366f1; color: #fff !important; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?= $school_name ?? 'Fee Management System' ?></h1>
            <p style="margin: 5px 0; color: #666;">Fee Payment Receipt</p>
        </div>

        <div class="info-row">
            <span class="info-label">Receipt No:</span>
            <span class="info-value"><?= $receipt_no ?></span>
        </div>

        <div class="info-row">
            <span class="info-label">Date:</span>
            <span class="info-value"><?= $payment_date ?></span>
        </div>

        <div class="info-row">
            <span class="info-label">Student Name:</span>
            <span class="info-value"><?= $student_name ?></span>
        </div>

        <div class="info-row">
            <span class="info-label">Father's Name:</span>
            <span class="info-value"><?= $father_name ?? '-' ?></span>
        </div>

        <div class="info-row">
            <span class="info-label">Admission No:</span>
            <span class="info-value"><?= $admission_no ?? '-' ?></span>
        </div>

        <div class="info-row">
            <span class="info-label">Class:</span>
            <span class="info-value"><?= $class_name ?></span>
        </div>

        <div class="info-row">
            <span class="info-label">Roll No:</span>
            <span class="info-value"><?= $roll_no ?? '-' ?></span>
        </div>

        <div class="amount-box">
            <p style="margin: 0; font-size: 14px;">Total Amount Paid</p>
            <h2>₹ <?= number_format($amount_paid, 2) ?></h2>
        </div>

        <?php if (!empty($fee_components) && is_array($fee_components)): ?>
        <?php foreach ($fee_components as $component): ?>
        <div class="info-row">
            <span class="info-label"><?= $component['name'] ?>:</span>
            <span class="info-value">₹ <?= number_format($component['amount'], 2) ?></span>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <div class="info-row">
            <span class="info-label">Payment Mode:</span>
            <span class="info-value"><?= $payment_mode ?></span>
        </div>

        <?php if (!empty($transaction_id)): ?>
        <div class="info-row">
            <span class="info-label">Transaction ID:</span>
            <span class="info-value"><?= htmlspecialchars($transaction_id) ?></span>
        </div>
        <?php endif; ?>

        <?php if (!empty($fine_amount) && $fine_amount > 0): ?>
        <div class="info-row">
            <span class="info-label">Fine:</span>
            <span class="info-value">₹ <?= number_format($fine_amount, 2) ?></span>
        </div>
        <?php endif; ?>

        <?php if (!empty($discount_amount) && $discount_amount > 0): ?>
        <div class="info-row">
            <span class="info-label">Discount:</span>
            <span class="info-value">₹ <?= number_format($discount_amount, 2) ?></span>
        </div>
        <?php endif; ?>

        <?php if (!empty($remarks)): ?>
        <div class="info-row">
            <span class="info-label">Remarks:</span>
            <span class="info-value"><?= htmlspecialchars($remarks) ?></span>
        </div>
        <?php endif; ?>

        <p style="text-align: center; margin-top: 30px; color: #666;">
            Thank you for your payment!
        </p>

        <div class="footer">
            <p>This is an automatically generated email. Please do not reply to this email.</p>
            <p>&copy; <?= date('Y') ?> <?= $school_name ?? 'Fee Management System' ?>. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
