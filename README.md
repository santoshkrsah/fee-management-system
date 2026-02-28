# Student Fee Collection and Management System

A comprehensive web-based system for managing student fees, built with PHP, MySQL, HTML, CSS, JavaScript, and Bootstrap.

## Features

### Admin Panel
- Secure login system with password hashing
- Session management
- Responsive dashboard with statistics

### Student Management
- Add new students with complete information
- Edit student details
- Delete students (soft delete)
- View all students with search and filter options
- Assign class and section to students

### Fee Structure Management
- Define class-wise fee structure
- Include multiple fee components:
  - Tuition fee
  - Exam fee
  - Library fee
  - Sports fee
  - Lab fee
  - Transport fee
  - Other charges
- Edit existing fee structures
- Automatic total calculation

### Fee Collection
- Record student payments
- Support for partial payments
- Multiple payment modes (Cash, Card, UPI, Net Banking, Cheque)
- Fine and discount options
- Automatic calculation
- Transaction ID tracking
- Generate printable receipts
- View all payment history

### Reports
1. **Paid Students Report** - List of students who have paid full fees
2. **Unpaid Students Report** - List of students with pending fees
3. **Class-wise Collection Report** - Financial summary by class
4. **Date-wise Collection Report** - Daily collection tracking with charts

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 8.0+
- **Frontend**: HTML5, CSS3, JavaScript (jQuery)
- **CSS Framework**: Bootstrap 5.3
- **Icons**: Font Awesome 6.4
- **Charts**: Chart.js

## System Requirements

- PHP 7.4 or higher
- MySQL 8.0 or higher
- Apache/Nginx web server
- Web browser (Chrome, Firefox, Safari, Edge)

## Installation Instructions

### Step 1: Setup Database

1. Open MySQL Workbench or phpMyAdmin
2. Create a new connection with the following credentials:
   - Hostname: `localhost`
   - Port: `3306`
   - Username: `root`
   - Password: `Te@5219981998`

3. Import the database schema:
   ```sql
   source /path/to/fee_management_system/database_schema.sql
   ```

   Or execute the SQL file directly in MySQL Workbench:
   - Open `database_schema.sql` file
   - Execute the script

### Step 2: Setup Application

1. Copy the `fee_management_system` folder to your web server directory:
   - **XAMPP**: `C:/xampp/htdocs/`
   - **WAMP**: `C:/wamp64/www/`
   - **MAMP**: `/Applications/MAMP/htdocs/`
   - **Custom**: Your Apache `DocumentRoot` directory

2. Verify database credentials in `/config/database.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_PORT', '3306');
   define('DB_NAME', 'fee_management_system');
   define('DB_USER', 'root');
   define('DB_PASS', 'Te@5219981998');
   ```

### Step 3: Configure Web Server

1. Start Apache and MySQL services

2. Access the application in your browser:
   ```
   http://localhost/fee_management_system/
   ```

3. You will be redirected to the login page

### Step 4: Login

Use the default admin credentials:
- **Username**: `admin`
- **Password**: `admin123`

**Important**: Change the default password after first login for security.

## Folder Structure

```
fee_management_system/
├── admin/
│   ├── dashboard.php        # Admin dashboard
│   ├── login.php           # Login page
│   └── logout.php          # Logout handler
├── assets/
│   ├── css/
│   │   └── style.css       # Custom styles
│   ├── js/
│   │   └── script.js       # Custom JavaScript
│   └── img/                # Images folder
├── config/
│   └── database.php        # Database configuration
├── includes/
│   ├── header.php          # Common header
│   ├── footer.php          # Common footer
│   └── session.php         # Session management
├── modules/
│   ├── student/
│   │   ├── add_student.php
│   │   ├── edit_student.php
│   │   ├── view_students.php
│   │   ├── delete_student.php
│   │   └── ajax_get_sections.php
│   ├── fee_structure/
│   │   ├── view_fee_structure.php
│   │   └── ajax_get_fee_structure.php
│   ├── fee_collection/
│   │   ├── collect_fee.php
│   │   ├── view_payments.php
│   │   └── receipt.php
│   └── reports/
│       ├── paid_report.php
│       ├── unpaid_report.php
│       ├── class_wise_report.php
│       └── date_wise_report.php
├── receipts/               # Receipt storage folder
├── database_schema.sql     # Database schema
├── index.php              # Main entry point
└── README.md              # This file
```

## Usage Guide

### Adding a Student

1. Navigate to **Students > Add Student**
2. Fill in all required fields:
   - Admission number (unique)
   - Student name
   - Father's name
   - Date of birth
   - Gender
   - Class and section
   - Contact information
   - Address
3. Click **Save Student**

### Setting Up Fee Structure

1. Navigate to **Fee Management > Fee Structure**
2. Select a class without existing fee structure
3. Enter fee amounts for each component
4. The total is calculated automatically
5. Click **Add Fee Structure**

### Collecting Fees

1. Navigate to **Fee Management > Collect Fee**
2. Select a student from the dropdown
3. Fee structure loads automatically based on class
4. Adjust amounts if partial payment
5. Add fine or discount if applicable
6. Select payment mode
7. Add transaction ID (for digital payments)
8. Click **Collect Fee & Generate Receipt**
9. Receipt opens in new tab for printing

### Generating Reports

1. Navigate to **Reports** menu
2. Select desired report type
3. Apply filters (class, date range)
4. Click **Filter** or **Generate**
5. View and analyze data

## Security Features

- Password hashing using PHP `password_hash()`
- Prepared statements for SQL injection prevention
- Session management with secure cookies
- CSRF token protection
- Input sanitization and validation
- XSS prevention
- Secure authentication system

## Database Schema

### Main Tables

1. **admin** - Admin user accounts
2. **students** - Student information
3. **classes** - Class definitions
4. **sections** - Section definitions
5. **fee_structure** - Class-wise fee structure
6. **fee_collection** - Payment records

### Relationships

- Students belong to a class and section
- Fee structure is defined per class
- Payments are linked to students and fee structure
- All tables have proper foreign key constraints

## Default Data

The system comes with pre-populated data:

- **Admin Account**: username `admin`, password `admin123`
- **Classes**: Nursery to Class 12
- **Sections**: A, B, C (for higher classes)
- **Fee Structure**: Sample fee structure for all classes

## Customization

### Change School Name and Details

Edit the receipt template in `/modules/fee_collection/receipt.php`:

```php
<h2>Your School Name</h2>
<p>Your School Address</p>
<p>Phone: Your Phone | Email: Your Email</p>
```

### Modify Fee Components

To add/remove fee components:

1. Update database schema in `fee_structure` table
2. Update fee structure management page
3. Update fee collection form
4. Update receipt template

### Change Academic Year

The system automatically uses the current academic year format (2024-2025).
To change, modify the calculation in relevant files:

```php
$currentYear = date('Y') . '-' . (date('Y') + 1);
```

## Troubleshooting

### Database Connection Error

- Verify MySQL is running
- Check database credentials in `/config/database.php`
- Ensure database exists

### Login Not Working

- Clear browser cache and cookies
- Verify admin account exists in database
- Check PHP session is working

### Receipt Not Printing

- Check browser popup blocker
- Ensure receipt.php has proper permissions
- Use Print Preview in browser

### Sections Not Loading

- Verify JavaScript is enabled
- Check browser console for errors
- Ensure AJAX endpoints are accessible

## Browser Compatibility

- Google Chrome (recommended)
- Mozilla Firefox
- Microsoft Edge
- Safari
- Opera

## Support and Maintenance

### Regular Maintenance

1. Backup database regularly
2. Clear old session files
3. Monitor disk space for receipts
4. Update PHP and MySQL versions

### Backup Database

```bash
mysqldump -u root -p fee_management_system > backup.sql
```

### Restore Database

```bash
mysql -u root -p fee_management_system < backup.sql
```

## Future Enhancements

Possible features to add:

- SMS/Email notifications
- Parent login portal
- Online payment gateway integration
- Bulk SMS for fee reminders
- Attendance management
- Exam result management
- Generate PDF reports
- Excel export functionality
- Mobile app
- Multiple school support

## License

This is an educational project. Feel free to use and modify as needed.

## Credits

Developed using:
- PHP
- MySQL
- Bootstrap 5
- Font Awesome
- Chart.js
- jQuery

## Contact

For support or queries, please contact your system administrator.

---

**Version**: 1.0
**Last Updated**: February 2026
**Status**: Production Ready
