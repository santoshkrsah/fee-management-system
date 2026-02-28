# 🚀 Hostinger Deployment Checklist - Ready to Deploy!

**Database:** `u638211070_demo_fms`
**Username:** `u638211070_demo_fms`
**Status:** ✅ Config updated and ready!

---

## ✅ PRE-DEPLOYMENT (Complete Before Uploading)

- [ ] Database credentials verified in `config/database.php`
  - DB_HOST: `localhost`
  - DB_NAME: `u638211070_demo_fms`
  - DB_USER: `u638211070_demo_fms`
  - DB_PASS: `Te@5219981998`

- [ ] Email configuration verified in `config/email_config.php`
  - SMTP_HOST: `smtp.hostinger.com`
  - SMTP_PORT: `465` (SSL)
  - Email: `info@santoshkr.in`
  - Password: Configured

- [ ] `.htaccess` file is ready (security rules in place)

- [ ] All sensitive files are in place

---

## 📤 STEP 1: Upload Files to Hostinger

### **Files & Folders to UPLOAD** ✅

Copy everything from your `fee_management_system` folder EXCEPT the items below:

```
✅ admin/                          (Admin dashboard & management)
✅ assets/                         (CSS, JS, images)
✅ config/                         (Database & email config)
✅ email_templates/                (Email notification templates)
✅ includes/                       (Utility functions & libraries)
✅ modules/                        (Feature modules)
✅ pdf_templates/                  (PDF generation templates)
✅ student/                        (Student portal)
✅ index.php                       (Main entry point)
✅ .htaccess                       (Security & HTTPS rules)
✅ manifest.json                   (PWA manifest)
✅ sw.js                           (Service worker for offline)
```

### **Files & Folders to SKIP/DELETE** ❌

Do NOT upload these:

```
❌ .git/                           (Version control - not needed on server)
❌ Guide/                          (Documentation only)
❌ backups/                        (Create on server only)
❌ .DS_Store                       (Mac system files)
❌ .gitignore                      (Git specific)
❌ README.md                       (Documentation)
❌ database_*.sql                  (SQL schema files)
❌ generate_students.php           (Test data generator)
❌ check_email_config.php          (Testing script)
❌ try_domain_smtp.php             (Testing script)
❌ config/database_hostinger.php   (Template - not needed)
```

---

## 🔧 STEP 2: Hostinger File Manager Upload

1. **Login to Hostinger** → hPanel
2. **Files** → **File Manager**
3. Navigate to **public_html** folder
4. **Delete** default index.html and placeholder files
5. **Upload all files** from your fee_management_system (use the list above)

   **OPTION A - Zip & Extract (FASTER):**
   - Create a ZIP file of all files you need to upload
   - Upload the ZIP to public_html
   - Extract it
   - Delete the ZIP file

   **OPTION B - Direct Upload:**
   - Use drag & drop or upload button
   - May be slower for many files

6. **Wait for upload to complete** ⏳

---

## 🗄️ STEP 3: Database Setup

1. **hPanel** → **Databases** → **phpMyAdmin**
2. Login with your credentials
3. Select database: `u638211070_demo_fms`
4. Click **Import** tab
5. Choose and import ONE of these SQL files:
   - `database_schema.sql` (recommended - clean schema)
   - `database_complete_with_students.sql` (includes 50 sample students)
6. Wait for success message ✅

---

## 🔐 STEP 4: Set File Permissions

1. In **File Manager**, select these folders:
   - `config/`
   - `assets/`
   - `includes/`
   - `modules/`
   - `admin/`

2. Right-click → **Permissions**
3. Set to **755** (Owner: read/write/execute, Group: read/execute, Public: read/execute)
4. Click **Apply**

---

## 🌐 STEP 5: Test Your Website

### **Access Your Site:**
- **Homepage:** `https://yourdomain.com`
- **Admin Login:** `https://yourdomain.com/admin`

### **Default Credentials:**
- **Username:** `admin`
- **Password:** `admin123`

### **Verification Checklist:**
- [ ] Page loads without errors
- [ ] Login page appears
- [ ] Can login with admin/admin123
- [ ] Dashboard displays correctly
- [ ] Can view students list
- [ ] Can view fees
- [ ] Navigation works
- [ ] Styles & images load correctly

---

## 🔒 STEP 6: Security Hardening (CRITICAL!)

### **Immediately After Testing:**

1. **Change Admin Password**
   - Login → Admin Panel → Settings
   - Change password from `admin123` to something strong
   - Format: At least 8 characters, with symbols & numbers

2. **Delete Test/Dev Files** (via File Manager)
   - Delete: `generate_students.php`
   - Delete: `check_email_config.php`
   - Delete: `try_domain_smtp.php`
   - Delete: `.sql` files (if any uploaded)

3. **Enable HTTPS/SSL** (if not auto-enabled)
   - hPanel → **Security** → **SSL**
   - Click **Install Free SSL** (Let's Encrypt)
   - Wait 5-10 minutes for activation
   - Force HTTPS: hPanel → **SSL** → Toggle "Force HTTPS"

4. **Verify Security Headers** (via hPanel)
   - File Manager → Right-click public_html → **Edit .htaccess**
   - Verify these rules are present:
     ```
     RewriteCond %{HTTPS} off
     RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

     RewriteRule ^config/ - [F,L]
     RewriteRule ^backups/ - [F,L]
     ```

5. **Change Encryption Key** (for new deployment)
   - In `config/database.php`, update the encryption key:
     - Replace `ENCRYPTION_KEY` with a new 32-byte base64 string
     - Keep `ENCRYPTION_METHOD` as `AES-256-CBC`
   - **Note:** Old encrypted data won't decrypt with new key, so reconfigure UPI settings in admin panel

6. **Create Regular Backups**
   - hPanel → **Files** → **Backups**
   - Set automatic daily/weekly backups
   - Download backup locally monthly

---

## ✅ STEP 7: Post-Deployment Configuration

### **Configure Fee System:**
1. Login as admin
2. Go to **Settings/Configuration**
3. Set:
   - School name
   - School logo (upload)
   - Fee structure
   - Payment settings

### **Add UPI Payment Settings:**
1. **Admin Panel** → **UPI Settings**
2. Enter your UPI ID (e.g., `upiid@bankname`)
3. Configure payment verification settings
4. Test payment flow with small test amount

### **Add Students/Users:**
1. **Admin Panel** → **Add Student/User**
2. Enter details and save
3. Or, if you imported `database_complete_with_students.sql`, 50 sample students are already added

---

## 🚨 Troubleshooting

### **Issue: "Database Connection Failed"**
**Solution:**
1. Verify credentials match exactly in `config/database.php`
2. Check database exists in Hostinger hPanel
3. Verify database username/password in phpMyAdmin
4. Restart PHP: hPanel → **Advanced → Restart PHP**

### **Issue: Blank White Page**
**Solution:**
1. Enable error display in `index.php` (temporarily):
   ```php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```
2. Check file permissions (should be 755)
3. Check Hostinger error logs: hPanel → **Logs**

### **Issue: CSS/Images Not Loading**
**Solution:**
1. Verify `assets/` folder uploaded
2. Check file permissions on `assets/` (755)
3. Clear browser cache: `Ctrl+Shift+Delete`
4. Check file paths in CSS files

### **Issue: Can't Upload Files / Upload Fails**
**Solution:**
1. Check Hostinger storage limit
2. Use ZIP method instead of direct upload
3. Try uploading in smaller batches
4. Use FileZilla FTP client instead

### **Issue: Email Not Sending**
**Solution:**
1. Verify SMTP settings in `config/email_config.php`
2. Check email account credentials
3. Verify Hostinger allows SMTP outgoing
4. Check spam folder

---

## 📋 Quick Reference

| Item | Value |
|------|-------|
| **Database Host** | localhost |
| **Database Name** | u638211070_demo_fms |
| **DB Username** | u638211070_demo_fms |
| **DB Password** | Te@5219981998 |
| **SMTP Host** | smtp.hostinger.com |
| **SMTP Port** | 465 (SSL) |
| **Default Admin Username** | admin |
| **Default Admin Password** | admin123 (CHANGE IMMEDIATELY!) |
| **SSH Port** | 22 (if you need it) |

---

## 🎉 Success Indicators

Your deployment is successful when:

✅ Website loads at `https://yourdomain.com`
✅ Login page appears without errors
✅ Can login with admin credentials
✅ Dashboard loads with all data
✅ Navigation between pages works
✅ Images and styles load correctly
✅ No console errors in browser
✅ No database connection errors

---

## 📞 Support

**Hostinger Support:**
- Live Chat: hPanel (24/7)
- Email: support@hostinger.com
- Knowledge Base: hPanel → Help Center

**Common Issues Video Tutorials:**
- "Hostinger upload PHP website"
- "Hostinger create MySQL database"
- "Hostinger phpMyAdmin import"

---

## ⏭️ Next Steps (Post-Deployment)

1. ✅ Change default admin password
2. ✅ Configure fee structure
3. ✅ Add school information & logo
4. ✅ Set up payment methods
5. ✅ Import students (if not done during DB setup)
6. ✅ Test complete fee collection workflow
7. ✅ Train staff on system usage
8. ✅ Schedule regular backups

---

**Status:** READY FOR DEPLOYMENT ✅
**Last Updated:** 2026-02-28
**Config Files:** All verified and updated

You're all set! Follow the steps above and your Fee Management System will be live on Hostinger! 🚀
