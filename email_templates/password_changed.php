<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Changed</title>
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
            <h1>✅ Password Changed Successfully</h1>
        </div>

        <div class="success-box">
            <p style="font-size: 18px; margin: 0;"><strong>Your password has been changed successfully!</strong></p>
        </div>

        <p>Hello <strong><?= $name ?? 'User' ?></strong>,</p>

        <p>This email confirms that your password was changed on <strong><?= date('F d, Y \a\t h:i A') ?></strong>.</p>

        <p><strong>Security Details:</strong></p>
        <ul>
            <li>Changed from IP: <?= $_SERVER['REMOTE_ADDR'] ?? 'Unknown' ?></li>
            <li>Date & Time: <?= date('Y-m-d H:i:s') ?></li>
        </ul>

        <p>If you did not make this change, please contact your system administrator immediately and secure your account.</p>

        <p style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;">
            <strong>Security Tip:</strong> Remember to keep your password secure and never share it with anyone.
        </p>

        <div class="footer">
            <p>This is an automatically generated email. Please do not reply to this email.</p>
            <p>&copy; <?= date('Y') ?> Fee Management System. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
