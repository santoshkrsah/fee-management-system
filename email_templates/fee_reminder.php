<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Payment Reminder</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f4f4f4; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .header { text-align: center; border-bottom: 3px solid #ef4444; padding-bottom: 20px; margin-bottom: 30px; }
        .header h1 { color: #ef4444; margin: 0; }
        .reminder-box { background: #fee2e2; border: 2px solid #ef4444; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .amount-box { background: #ef4444; color: #fff; padding: 20px; border-radius: 8px; text-align: center; margin: 20px 0; }
        .amount-box h2 { margin: 0; font-size: 32px; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 10px; padding: 10px; background: #f8f9fa; border-radius: 5px; }
        .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #999; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚠️ Fee Payment Reminder</h1>
        </div>

        <div class="reminder-box">
            <p style="margin: 0; text-align: center; font-size: 16px;"><strong>This is a friendly reminder about pending fee payment</strong></p>
        </div>

        <p>Dear Parent/Guardian,</p>

        <p>This is a reminder that the fee payment for <strong><?= $student_name ?></strong> is pending.</p>

        <div class="info-row">
            <span style="font-weight: bold; color: #666;">Student Name:</span>
            <span><?= $student_name ?></span>
        </div>

        <div class="info-row">
            <span style="font-weight: bold; color: #666;">Class:</span>
            <span><?= $class_name ?></span>
        </div>

        <div class="info-row">
            <span style="font-weight: bold; color: #666;">Roll No:</span>
            <span><?= $roll_no ?></span>
        </div>

        <div class="amount-box">
            <p style="margin: 0; font-size: 14px;">Pending Amount</p>
            <h2>₹ <?= number_format($pending_amount, 2) ?></h2>
        </div>

        <?php if (!empty($due_date)): ?>
        <div class="info-row">
            <span style="font-weight: bold; color: #666;">Due Date:</span>
            <span><?= $due_date ?></span>
        </div>
        <?php endif; ?>

        <p>Please make the payment at your earliest convenience to avoid any late fee charges.</p>

        <p><strong>Payment Methods Available:</strong></p>
        <ul>
            <li>Cash payment at school office</li>
            <li>Bank transfer/UPI</li>
            <li>Online payment (if enabled)</li>
        </ul>

        <p>If you have already made the payment, please ignore this reminder. If you have any questions, please contact the school office.</p>

        <p style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-top: 20px;">
            <strong>Contact Information:</strong><br>
            <?= $school_name ?? 'School' ?><br>
            <?php if (!empty($school_phone)): ?>Phone: <?= $school_phone ?><br><?php endif; ?>
            <?php if (!empty($school_email)): ?>Email: <?= $school_email ?><?php endif; ?>
        </p>

        <div class="footer">
            <p>This is an automatically generated email. Please do not reply to this email.</p>
            <p>&copy; <?= date('Y') ?> <?= $school_name ?? 'Fee Management System' ?>. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
