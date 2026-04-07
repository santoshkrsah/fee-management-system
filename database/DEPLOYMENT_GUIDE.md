# Fee Management System - Hostinger Deployment Guide

## Files in this folder
- `fee_management_system.sql` - Full database dump (structure + data)
- `fee_management_system_structure.sql` - Structure only (no data, for clean installs)

---

## Step-by-Step Deployment on Hostinger

### STEP 1: Purchase Hostinger Plan
- Go to https://www.hostinger.com
- Purchase a **Premium** or **Business** Web Hosting plan
- You need: PHP 8.0+, MySQL 8.0+, SSL certificate
- Register/connect your domain (e.g., `yourdomain.com`)

### STEP 2: Set Up SSL Certificate
1. Log in to **hPanel** (Hostinger control panel)
2. Go to **Security > SSL**
3. Install the free SSL certificate for your domain
4. Wait for SSL to activate (can take up to 24 hours)

### STEP 3: Create MySQL Database
1. In hPanel, go to **Databases > MySQL Databases**
2. Create a new database:
   - **Database name**: `fee_management` (Hostinger will prefix it, e.g., `u123456789_fee_management`)
   - **Username**: `fee_admin` (will become `u123456789_fee_admin`)
   - **Password**: Use a strong password (save it securely)
3. Note down the full values:
   - Database name: `u123456789_fee_management`
   - Username: `u123456789_fee_admin`
   - Password: `your_password_here`
   - Host: `localhost` (Hostinger uses localhost for MySQL)

### STEP 4: Import Database
1. In hPanel, go to **Databases > phpMyAdmin**
2. Click **Enter phpMyAdmin** for your database
3. Select your database from the left sidebar
4. Click the **Import** tab
5. Click **Choose File** and select `fee_management_system.sql` (from this folder)
   - For a clean install with no sample data, use `fee_management_system_structure.sql` instead
6. Click **Go** to import
7. Verify all 21 tables appear in the sidebar

### STEP 5: Upload Application Files
**Option A: File Manager (easiest)**
1. Zip the entire `fee_management_system` folder on your computer
   - EXCLUDE `database/` folder and `.DS_Store` files from the zip
2. In hPanel, go to **Files > File Manager**
3. Navigate to `public_html/`
4. **Delete** any existing files in `public_html/` (default Hostinger files)
5. Upload the zip file to `public_html/`
6. Right-click the zip > **Extract**
7. Make sure all files are directly inside `public_html/`, NOT inside `public_html/fee_management_system/`
   - If extracted into a subfolder, move all files up to `public_html/`

**Option B: FTP (for large uploads)**
1. In hPanel, go to **Files > FTP Accounts**
2. Note your FTP credentials (or create a new FTP account)
3. Use FileZilla or any FTP client:
   - Host: `ftp.yourdomain.com`
   - Username: your FTP username
   - Password: your FTP password
   - Port: `21`
4. Upload all files to the `public_html/` directory
5. Ensure files go directly into `public_html/`, not a subdirectory

**IMPORTANT**: The app uses root-relative paths (`/admin/login.php`, `/assets/css/style.css`, etc.), so files MUST be in the document root (`public_html/`), not a subfolder.

### STEP 6: Update Database Configuration
1. In File Manager, navigate to `public_html/config/database.php`
2. Click to edit the file
3. Update lines 9-13 with your Hostinger database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'u123456789_fee_management');   // Your full DB name
define('DB_USER', 'u123456789_fee_admin');          // Your full DB username
define('DB_PASS', 'YourStrongPassword@123');         // Your DB password
```

4. Save the file

### STEP 7: Update Email Configuration
1. **Create an email account** in hPanel: **Emails > Email Accounts**
   - e.g., `info@yourdomain.com`
2. Edit `public_html/config/email_config.php`
3. Update SMTP settings:

```php
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 465);
define('SMTP_ENCRYPTION', 'ssl');
define('SMTP_USERNAME', 'info@yourdomain.com');     // Your Hostinger email
define('SMTP_PASSWORD', 'YourEmailPassword');         // Your email password
define('SMTP_FROM_EMAIL', 'info@yourdomain.com');
define('SMTP_FROM_NAME', 'Your School Name');
```

4. Save the file

### STEP 8: Set Directory Permissions
In File Manager or via SSH:
1. `public_html/uploads/` - Set to **755**
2. `public_html/uploads/upi_screenshots/` - Set to **755**
3. `public_html/assets/img/` - Set to **755** (for logo uploads)
4. `public_html/backups/` - Set to **755** (for database backups)
5. `public_html/config/` - Set to **755** (email_test.php writes to config)

To set permissions in File Manager:
- Right-click folder > **Permissions**
- Set to `755`
- Check "Apply to sub-directories"

### STEP 9: Verify .htaccess
The `.htaccess` file in `public_html/` should already be correct. Verify it exists and contains:
- HTTPS redirect rules
- Block rules for `config/`, `backups/`, `database/` directories
- Block rules for `.sql` and `.md` files
- PHP settings for upload size

**Note**: Hostinger uses LiteSpeed web server which is compatible with Apache `.htaccess` rules.

### STEP 10: Set PHP Version
1. In hPanel, go to **Advanced > PHP Configuration**
2. Set PHP version to **8.0**, **8.1**, or **8.2** (app requires 8.0+)
3. Under **PHP Options**, verify:
   - `upload_max_filesize` = 10M or higher
   - `post_max_size` = 12M or higher
   - `max_execution_time` = 120 or higher
   - `pdo_mysql` extension = enabled

### STEP 11: Initial Login & Setup
1. Visit `https://yourdomain.com`
2. You'll be redirected to the admin login page
3. Login credentials (if you imported the full dump with data):
   - Username: `sysadmin`
   - Password: (whatever was set in your local system)
4. If using the structure-only dump, you'll need to insert a default admin manually via phpMyAdmin:

```sql
INSERT INTO admin (username, password, email, full_name, role, status)
VALUES (
    'admin',
    '$2y$10$YourBcryptHashHere',
    'admin@yourdomain.com',
    'Administrator',
    'sysadmin',
    'active'
);
```

To generate the bcrypt hash, create a temporary PHP file, visit it, then delete it:
```php
<?php echo password_hash('YourDesiredPassword', PASSWORD_BCRYPT); ?>
```

5. After login:
   - Go to **Settings** to update school name, address, logo
   - Go to **Manage Sessions** to create your academic session
   - Go to **Manage Classes** to add classes and sections
   - Set up fee structure

---

## What Changes Are Needed (Summary)

### Must Change (will not work without these):
| File | What to Change | Why |
|------|---------------|-----|
| `config/database.php` (lines 9-13) | DB_HOST, DB_NAME, DB_USER, DB_PASS | Hostinger database credentials |

### Should Change (for proper functionality):
| File | What to Change | Why |
|------|---------------|-----|
| `config/email_config.php` (lines 10-16) | SMTP credentials | Use your Hostinger email account |

### Recommended (for security):
| File | What to Change | Why |
|------|---------------|-----|
| `config/database.php` (line 147) | ENCRYPTION_KEY | Generate a unique key per deployment |
| Delete `database/` folder from server | SQL dumps | Not needed on live server |

### No Changes Needed:
- `.htaccess` - Already configured for production (HTTPS, security blocks)
- `includes/session.php` - Auto-detects HTTPS for secure cookies
- All PHP files - Use relative includes and root-relative URLs
- CDN assets - Bootstrap, jQuery, Font Awesome load from CDN

---

## Generating a New Encryption Key
Run this in a temporary PHP file on the server, then delete it:
```php
<?php echo base64_encode(random_bytes(32)); ?>
```
Copy the output and paste into `config/database.php` line 147.

---

## Troubleshooting

### "500 Internal Server Error"
- Check PHP version is 8.0+
- Check `.htaccess` syntax (try removing `php_value` lines if LiteSpeed rejects them)
- Check file permissions (755 for directories, 644 for files)

### "Database connection failed"
- Verify DB credentials in `config/database.php`
- Hostinger DB host is `localhost` (not an IP)
- Check the full prefixed database name (e.g., `u123456789_fee_management`)

### "Page not found" or broken links
- Ensure files are in `public_html/` root, not a subdirectory
- All links expect the app at document root

### "Email not sending"
- Verify SMTP credentials in `config/email_config.php`
- Create the email account in hPanel first
- Test via **Admin > Email Settings** page

### "Cannot upload logo/files"
- Set `assets/img/` directory to 755
- Set `uploads/` directory to 755
- Check PHP `upload_max_filesize` in hPanel PHP Configuration

### CSS/JS not loading
- Verify files are in `public_html/assets/`
- Check browser console for 404 errors
- CDN assets (Bootstrap, jQuery) require internet connection

---

## Post-Deployment Checklist
- [ ] Site loads at `https://yourdomain.com`
- [ ] HTTPS redirect works (http -> https)
- [ ] Admin login works
- [ ] Student login works
- [ ] Fee collection works
- [ ] Receipts generate properly
- [ ] Email sending works (test from Admin > Email Settings)
- [ ] Logo upload works
- [ ] Database backup works
- [ ] UPI payment screenshots upload works
- [ ] Delete `database/` folder from live server
- [ ] Delete any temporary PHP files used for setup
