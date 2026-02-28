<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f4f4f4; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #6366f1; margin: 0; }
        .content { margin: 20px 0; }
        .button { display: inline-block; background: #6366f1; color: #fff !important; padding: 15px 40px; text-decoration: none; border-radius: 5px; margin: 20px 0; font-weight: bold; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
        .footer { text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #999; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Password Reset Request</h1>
        </div>

        <div class="content">
            <p>Hello <strong><?= $name ?? 'User' ?></strong>,</p>

            <p>We received a request to reset your password for the Fee Management System account.</p>

            <p>Click the button below to reset your password:</p>

            <p style="text-align: center;">
                <a href="<?= $reset_link ?>" class="button">Reset Password</a>
            </p>

            <p>Or copy and paste this link into your browser:</p>
            <p style="background: #f8f9fa; padding: 15px; border-radius: 5px; word-break: break-all;">
                <?= $reset_link ?>
            </p>

            <div class="warning">
                <p style="margin: 0;"><strong>⏰ Important:</strong> This link will expire in <strong>1 hour</strong>.</p>
            </div>

            <p>If you didn't request this password reset, please ignore this email. Your password will remain unchanged.</p>

            <p>For security reasons, we recommend that you:</p>
            <ul>
                <li>Use a strong password (at least 8 characters)</li>
                <li>Include uppercase and lowercase letters</li>
                <li>Include numbers and special characters</li>
                <li>Don't reuse passwords from other accounts</li>
            </ul>
        </div>

        <div class="footer">
            <p>This is an automatically generated email. Please do not reply to this email.</p>
            <p>&copy; <?= date('Y') ?> Fee Management System. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
