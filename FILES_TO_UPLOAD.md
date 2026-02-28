# 📤 Hostinger File Upload Checklist

## Quick Copy-Paste List of Files to Upload

Copy & paste these commands to easily generate a list, or manually verify against your folder:

---

## ✅ UPLOAD THESE FOLDERS/FILES

```
admin/
assets/
config/
email_templates/
includes/
modules/
pdf_templates/
student/

index.php
.htaccess
manifest.json
sw.js
```

---

## ❌ DO NOT UPLOAD

```
.git/
.DS_Store
.gitignore
backups/
Guide/
check_email_config.php
config/database_hostinger.php
database_*.sql (all SQL files)
generate_students.php
try_domain_smtp.php
README.md
HOSTINGER_DEPLOYMENT_CHECKLIST.md (this file)
```

---

## 📚 Database Import Files

Choose ONE to import via phpMyAdmin:

**Option 1 - Fresh Start (Recommended):**
- File: `database_schema.sql`
- Creates: Empty database with all tables
- Students: None (you add them manually)
- Time to import: ~10 seconds

**Option 2 - With Sample Data:**
- File: `database_complete_with_students.sql`
- Creates: All tables + sample data
- Students: 50 pre-loaded students
- Time to import: ~20 seconds

**Option 3 - With UPI Payment Support:**
- File: `database_upi_payments.sql`
- Must run AFTER database_schema.sql
- Adds: UPI payment tables
- Time to import: ~5 seconds

---

## 🗂️ Folder Structure Overview

What each folder contains:

| Folder | Purpose | Upload? |
|--------|---------|---------|
| `admin/` | Admin dashboard & management pages | ✅ YES |
| `assets/` | CSS, JavaScript, images | ✅ YES |
| `config/` | Database & email configuration | ✅ YES |
| `email_templates/` | Email notification templates | ✅ YES |
| `includes/` | Core utility functions & classes | ✅ YES |
| `modules/` | Feature modules (students, fees, etc.) | ✅ YES |
| `pdf_templates/` | PDF generation templates | ✅ YES |
| `student/` | Student portal pages | ✅ YES |
| `backups/` | Database backups (create on server) | ❌ NO |
| `.git/` | Version control (not needed on server) | ❌ NO |
| `Guide/` | Documentation (local only) | ❌ NO |

---

## 🚀 Upload Strategy Options

### **FAST METHOD (Recommended):**
1. On your computer, create a ZIP file with:
   - All folders from the list above
   - All files from the list above
2. Upload the ZIP to `public_html/`
3. Extract it
4. Delete the ZIP file
5. Done! ⚡

### **SLOW METHOD (Direct Upload):**
1. Open File Manager
2. Navigate to `public_html/`
3. Drag & drop folders one by one
4. Upload individual files
5. Wait for each to complete
⏳ (Takes longer, but more control)

### **EXPERT METHOD (FileZilla FTP):**
1. Download FileZilla
2. Get FTP credentials from hPanel
3. Connect via FTP
4. Drag-drop files from computer to server
5. Quality upload experience

---

## ✋ Stop! Before You Upload

**Verify these are done:**

- [ ] `config/database.php` updated with your Hostinger credentials
- [ ] `config/email_config.php` has correct SMTP settings
- [ ] You have your Hostinger domain/URL ready
- [ ] You created the database in Hostinger hPanel
- [ ] You have the SQL file ready to import

---

## 📦 Zip Using Command Line (Optional)

**On Mac/Linux:**
```bash
cd /path/to/fee_management_system
zip -r fms-upload.zip admin/ assets/ config/ email_templates/ includes/ modules/ pdf_templates/ student/ index.php .htaccess manifest.json sw.js

# This creates: fms-upload.zip ready to upload
```

**On Windows (PowerShell):**
```powershell
Compress-Archive -Path admin, assets, config, email_templates, includes, modules, pdf_templates, student, index.php, .htaccess, manifest.json, sw.js -DestinationPath fms-upload.zip
```

---

## 🔍 Verify Upload Completeness

After uploading, verify all files are there:

**Check these exist in public_html/:**
- ✅ `/admin/` folder with PHP files
- ✅ `/assets/` folder with CSS, JS, images
- ✅ `/config/database.php` (with your credentials)
- ✅ `/config/email_config.php` (with SMTP settings)
- ✅ `/includes/` folder
- ✅ `/modules/` folder
- ✅ `/student/` folder
- ✅ `/index.php` (main entry file)
- ✅ `/.htaccess` (security rules)

**Files that should NOT exist:**
- ❌ No `.sql` files
- ❌ No `generate_students.php`
- ❌ No `.git/` folder
- ❌ No `Guide/` folder
- ❌ No `README.md` or `.md` files

---

## 📝 Upload Checklist

- [ ] Created ZIP file with all required files
- [ ] Logged into Hostinger hPanel
- [ ] Opened File Manager
- [ ] Navigated to public_html/
- [ ] Deleted default index.html
- [ ] Uploaded ZIP file
- [ ] Extracted ZIP file
- [ ] Deleted ZIP file
- [ ] Verified all folders exist
- [ ] Set file permissions (755 for folders)
- [ ] Verified database.php has correct credentials
- [ ] Ready to test!

---

## ⏭️ Next Step

After uploading all files:
1. Go to: `https://yourdomain.com`
2. You should see the login page
3. Login with: `admin` / `admin123`
4. Change password immediately
5. Configure your system

---

**Status:** Ready to Deploy ✅
**Generated:** 2026-02-28
