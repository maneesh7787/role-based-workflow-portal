# Quick Setup Guide

## Prerequisites Check
Before installation, ensure you have:
- [x] PHP 7.4 or higher installed
- [x] MySQL 5.7 or higher installed
- [x] Apache or Nginx web server configured
- [x] Git installed (for cloning)

## Quick Installation Steps

### 1. Download/Clone
```bash
git clone https://github.com/maneesh7787/role-based-workflow-portal.git
cd role-based-workflow-portal
```

### 2. Database Setup
```bash
# Login to MySQL
mysql -u root -p

# Create database and user
CREATE DATABASE workflow_portal;
CREATE USER 'workflow_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON workflow_portal.* TO 'workflow_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Import schema
mysql -u workflow_user -p workflow_portal < database/schema.sql
```

### 3. Configure Application
Edit `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'workflow_user');
define('DB_PASS', 'your_secure_password');
define('DB_NAME', 'workflow_portal');
```

Edit `config/config.php`:
```php
// Update BASE_URL to match your installation
define('BASE_URL', 'http://localhost/role-based-workflow-portal');

// Configure email settings (optional)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');
```

### 4. Set Permissions
```bash
# Make uploads directory writable
chmod 755 uploads
```

### 5. Access Application
Open browser: `http://localhost/role-based-workflow-portal/login.php`

**Default Login:**
- Username: `admin`
- Password: `admin123`

## Post-Installation Steps

### 1. Change Admin Password
- Login as admin
- Go to Profile → Change Password
- Set a strong password

### 2. Create Users
- Go to Admin → Manage Users
- Create users for Sales, Design, and Approval roles

### 3. Test Workflow
1. Login as Sales user → Create a request
2. Login as Design user → Upload design images
3. Login as Approval user → Review and approve
4. Login as Sales user → Final review and approve

## Configuration Options

### Email Notifications
To enable email notifications, configure SMTP settings in `config/config.php`:

**For Gmail:**
1. Enable 2-Factor Authentication
2. Generate App Password
3. Update config:
```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');
```

### File Upload Limits
Adjust in `config/config.php`:
```php
define('MAX_FILE_SIZE', 5242880); // 5MB in bytes
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);
```

Also check PHP settings in `php.ini`:
```ini
upload_max_filesize = 10M
post_max_size = 10M
```

### Security Settings
For production deployment:

1. **Disable Error Display**
```php
// In config/config.php
error_reporting(0);
ini_set('display_errors', 0);
```

2. **Use HTTPS**
```php
// Update BASE_URL
define('BASE_URL', 'https://yourdomain.com');
```

3. **Secure Database**
- Use strong passwords
- Restrict database user privileges
- Enable MySQL SSL if available

## Troubleshooting

### Issue: "Database connection error"
**Solution:** Check database credentials in `config/database.php`

### Issue: "Permission denied" for file uploads
**Solution:** 
```bash
chmod 755 uploads
chown www-data:www-data uploads  # For Apache
```

### Issue: "Session not working"
**Solution:** Check PHP session configuration
```bash
php -i | grep session.save_path
# Ensure directory exists and is writable
```

### Issue: Email not sending
**Solution:** 
- Verify SMTP settings
- Check firewall/port access
- For Gmail, use App Passwords
- Check PHP error logs

## File Structure Overview

```
role-based-workflow-portal/
├── admin/           - Admin module (user management, logs)
├── sales/           - Sales module (create/view requests)
├── design/          - Design module (upload designs)
├── approval/        - Approval module (review/approve)
├── assets/          - CSS, JavaScript files
├── config/          - Configuration files
├── database/        - Database schema
├── includes/        - Common includes (header/footer)
├── uploads/         - Uploaded design images
└── *.php           - Main pages (login, dashboard, etc.)
```

## Default User Roles

| Role     | Capabilities |
|----------|-------------|
| Admin    | Manage users, view all requests, view logs |
| Sales    | Create requests, view own requests, final approval |
| Design   | View pending requests, upload designs |
| Approval | Review requests, set pricing, approve |

## Workflow States

1. **Pending Design** - Request created, waiting for design
2. **Pending Approval** - Design uploaded, waiting for approval
3. **Sent to Sales** - Approved, sent back to sales for review
4. **Approved** - Sales approved the request
5. **Closed** - Request closed
6. **Returned to Design** - Sales sent back for changes

## Need Help?

For additional support:
1. Check the main README.md file
2. Review the database schema in `database/schema.sql`
3. Check application logs
4. Contact the development team

---

**Security Note:** This is a demonstration project. For production use, implement additional security measures including:
- Regular security audits
- Input validation enhancement
- Rate limiting
- CSRF protection
- XSS protection
- Regular backups
- Monitoring and logging
