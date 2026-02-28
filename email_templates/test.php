<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Email</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f4f4f4; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #10b981; margin: 0; }
        .success-box { background: #d1fae5; border: 2px solid #10b981; padding: 20px; border-radius: 8px; text-align: center; margin: 20px 0; }
        .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #999; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ Email System Test</h1>
        </div>

        <div class="success-box">
            <h2 style="margin: 0; color: #10b981;">Email Configuration Working!</h2>
        </div>

        <p>Congratulations! Your email system is configured correctly.</p>

        <p><strong>Test Details:</strong></p>
        <ul>
            <li><strong>Sent To:</strong> <?= $to ?></li>
            <li><strong>Timestamp:</strong> <?= $timestamp ?></li>
            <li><strong>SMTP Host:</strong> <?= SMTP_HOST ?></li>
            <li><strong>From:</strong> <?= SMTP_FROM_NAME ?> &lt;<?= SMTP_FROM_EMAIL ?>&gt;</li>
        </ul>

        <p>Your Fee Management System can now send email notifications for:</p>
        <ul>
            <li>Fee payment receipts</li>
            <li>Payment reminders</li>
            <li>Password reset requests</li>
            <li>Account notifications</li>
        </ul>

        <p style="background: #dbeafe; border-left: 4px solid #3b82f6; padding: 15px; margin: 20px 0;">
            <strong>Next Steps:</strong><br>
            - Configure email notifications in system settings<br>
            - Set up automatic payment reminders<br>
            - Enable email receipts for parents
        </p>

        <div class="footer">
            <p>This is a test email from Fee Management System</p>
            <p>&copy; <?= date('Y') ?> Fee Management System. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
