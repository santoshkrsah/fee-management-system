<?php
/**
 * Email Configuration
 * Configure SMTP settings for sending emails
 * Last updated: 2026-02-16 17:58:39
 */

// SMTP Configuration
define('SMTP_ENABLED', true); // Set to false to disable email sending
define('SMTP_HOST', 'smtp.hostinger.com'); // SMTP server (Gmail, Outlook, etc.)
define('SMTP_PORT', 465); // SMTP port (587 for TLS, 465 for SSL)
define('SMTP_ENCRYPTION', 'ssl'); // 'tls' or 'ssl'
define('SMTP_USERNAME', 'info@santoshkr.in'); // Your email address
define('SMTP_PASSWORD', 'Sk@5219981998'); // Your app password (not regular password!)
define('SMTP_FROM_EMAIL', 'info@santoshkr.in'); // From email address
define('SMTP_FROM_NAME', 'Fee Management System'); // From name

/**
 * IMPORTANT SETUP INSTRUCTIONS:
 *
 * For Gmail:
 * 1. Enable 2-factor authentication on your Google account
 * 2. Go to: https://myaccount.google.com/apppasswords
 * 3. Generate an "App Password" for "Mail"
 * 4. Use that 16-character password as SMTP_PASSWORD
 * 5. DO NOT use your regular Gmail password!
 *
 * For Outlook/Hotmail:
 * - SMTP_HOST: smtp.office365.com
 * - SMTP_PORT: 587
 * - SMTP_ENCRYPTION: tls
 *
 * For Yahoo:
 * - SMTP_HOST: smtp.mail.yahoo.com
 * - SMTP_PORT: 587
 * - SMTP_ENCRYPTION: tls
 *
 * For Custom Domain (like Hostinger):
 * - SMTP_HOST: smtp.hostinger.com (or your hosting provider's SMTP)
 * - SMTP_PORT: 465 (SSL) or 587 (TLS)
 * - Use your email account credentials
 */

// Email Templates Directory
define('EMAIL_TEMPLATES_DIR', __DIR__ . '/../email_templates/');

// Email Settings
define('EMAIL_DEBUG', false); // Set to true for debugging

?>
