# SQL Import Instructions

## Complete Database Setup for Fee Management System

### Database Information
- **Database Name:** `u638211070_demo_fms`
- **File to Import:** `MASTER_IMPORT_ALL_TABLES.sql`

---

## Step-by-Step Import Instructions for phpMyAdmin

### Step 1: Access phpMyAdmin
1. Go to your Hostinger cPanel
2. Find phpMyAdmin and click "Launch"
3. Log in with your database credentials

### Step 2: Select Your Database
1. Click on your database: **`u638211070_demo_fms`**
2. You should see the database name at the top left

### Step 3: Import the SQL File
1. Click the **"Import"** tab at the top
2. Click **"Choose File"**
3. Select **`MASTER_IMPORT_ALL_TABLES.sql`**
4. Leave all other settings as default
5. Click **"Import"** button at the bottom

### Step 4: Wait for Completion
- The import will take a few seconds
- You'll see a success message if completed
- You can verify by going to the "Databases" tab to see all tables created

---

## What Gets Imported

This master SQL file includes:

✅ **All Database Tables**
- admin, students, classes, sections, academic_sessions
- fee_structure, monthly_fee_structure, fee_collection
- settings, system_settings, subscription

✅ **Fee Types Management System**
- fee_types, fee_types_audit tables
- 7 default fee types (Tuition, Exam, Library, Sports, Lab, Transport, Other Charges)

✅ **Security Features**
- audit_log (activity tracking)
- login_attempts (rate limiting)
- password_reset_tokens (for password recovery)

✅ **Additional Features**
- Student portal support (password columns)
- WhatsApp number and Aadhar number fields
- Email configuration settings
- UPI payment support (optional, disabled by default)

✅ **Default Data**
- Default SysAdmin user (username: sysadmin, password: sysadmin123)
- Default Admin user (username: admin, password: admin123)
- 15 classes (Nursery to Class 12)
- 3 sections per class (A, B, C)
- Sample fee structure for academic year 2026-2027

---

## Login Credentials (After Import)

### Admin Logins
```
SysAdmin:
  Username: sysadmin
  Password: sysadmin123

Admin:
  Username: admin
  Password: admin123
```

⚠️ **IMPORTANT:** Change these passwords immediately after first login for security!

---

## Important Notes

### About sample_fee_structure_data.sql
The user mentioned they cannot import `sample_fee_structure_data.sql`. This is now included in the master file, so **you don't need to import it separately**. The same fee structure data is included in this consolidated file.

### If Import Fails
If you get an error during import:

1. **"Table already exists"** - This is normal if you're re-importing. The master file uses `CREATE TABLE IF NOT EXISTS`, so it won't fail on existing tables.

2. **"Foreign key constraint error"** - Make sure you import the complete file without stopping. All dependencies are in the correct order.

3. **"Access denied"** - Make sure you've selected the correct database before importing.

### File Size
The consolidated master file is approximately 50-60 KB, so upload should be quick.

---

## Troubleshooting

### If specific table doesn't exist
All tables are created with `CREATE TABLE IF NOT EXISTS`, so if a table didn't import, try:
1. Refresh the page in phpMyAdmin
2. Check if there are error messages at the bottom
3. Re-import the file

### If you want to start fresh
1. Go to your database in phpMyAdmin
2. Click "Operations" tab
3. Scroll down and click "Drop the database"
4. Confirm
5. Create a new empty database with the same name
6. Import the master file again

---

## Next Steps After Import

1. ✅ Update school information in **Admin > Settings**
2. ✅ Configure email settings if needed (Admin > Email Settings)
3. ✅ Create academic sessions for different years
4. ✅ Setup fee structures for your classes
5. ✅ Add students to the system
6. ✅ Create staff/admin accounts with appropriate roles
7. ✅ Enable UPI feature if campus allows student online payments

---

## Features Implemented with This Import

### 1. Fee Type Management
- Dynamic fee types (not hardcoded)
- Add custom fee types beyond the default 7
- Edit labels and descriptions
- Deactivate fee types (soft delete)

### 2. Security & Audit Trail
- Complete audit log of all actions
- Login attempt tracking
- Account lockout after failed attempts
- Password reset token system

### 3. Student Portal
- Students can login with admission number
- View their own fee status
- View payment history
- Download receipts (password must be set first)

### 4. Payment Management
- Multiple payment modes (Cash, Card, UPI, Net Banking, Cheque)
- Receipt generation and printing
- Payment history tracking
- Monthly and annual fee modes

### 5. Reporting
- Student fee summary view
- Payment history reports
- Monthly breakdown view
- Balance and payment status tracking

---

## Support

If you encounter any issues:
1. Check the error message carefully
2. Verify database name is correct
3. Make sure you have proper database permissions
4. Check phpMyAdmin logs for detailed error information
5. Contact hosting support if there are permission issues

---

**Created:** 2026-03-07
**Database Version:** MySQL 5.7+
**Compatibility:** Hostinger, cPanel, phpMyAdmin
