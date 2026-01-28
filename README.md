# Role-Based Workflow Portal

A comprehensive web-based workflow management system with role-based access control, designed to streamline request processing across different departments (Sales, Design, Approval).

## Features

### 1. Authentication & Authorization
- Secure login system with session-based authentication
- Password hashing using PHP's `password_hash()`
- Role-based access control (Admin, Sales, Design, Approval)
- Protected routes with unauthorized access prevention

### 2. Admin Module
- User management (create, edit, activate/deactivate users)
- Role assignment and management
- View all requests across the system
- Activity logs and audit trail
- Analytics dashboard with request statistics

### 3. Sales Request Creation
- Create new event requests with detailed information
- View personal request history
- Track request status throughout the workflow
- Review and take action on approved requests

### 4. Design Team Workflow
- View pending design requests
- Upload multiple design images per request
- Automatic status updates to "Pending Approval"
- View previously uploaded designs

### 5. Approval Team Workflow
- Review pending approval requests
- View uploaded design images
- Add pricing and remarks
- Approve and forward to sales team

### 6. Sales Final Review
- Review approved designs with pricing
- Three action options:
  - Approve and close request
  - Close request
  - Return to design team with feedback

### 7. Notifications System
- Real-time portal notifications
- Email notifications for workflow transitions
- Notification types:
  - New Request
  - Design Uploaded
  - Approval Completed
  - Returned for Changes
  - Approved
  - Closed

### 8. Security Features
- Session validation on all protected pages
- Role-based access checks
- SQL injection prevention with prepared statements
- File upload validation (type, size)
- Password security with bcrypt hashing
- Activity logging for audit purposes

### 9. UI/UX Features
- Responsive design with Bootstrap 5
- Role-specific dashboards
- Color-coded status badges
- Interactive image galleries
- Real-time notifications
- Clean and intuitive interface

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript
- **Frameworks**: Bootstrap 5, jQuery
- **Icons**: Font Awesome 6

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Git

### Step 1: Clone the Repository
```bash
git clone https://github.com/maneesh7787/role-based-workflow-portal.git
cd role-based-workflow-portal
```

### Step 2: Database Setup
1. Create a MySQL database:
```sql
CREATE DATABASE workflow_portal;
```

2. Import the database schema:
```bash
mysql -u root -p workflow_portal < database/schema.sql
```

Or manually execute the SQL file in phpMyAdmin or MySQL Workbench.

### Step 3: Configure Database Connection
Edit `config/database.php` and update the database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'workflow_portal');
```

### Step 4: Configure Application Settings
Edit `config/config.php` and update:
- `BASE_URL` - Set to your application URL
- Email settings (SMTP) for notifications

### Step 5: Set Up Upload Directory
Ensure the `uploads` directory has write permissions:
```bash
chmod 755 uploads
```

### Step 6: Access the Application
Open your browser and navigate to:
```
http://localhost/role-based-workflow-portal/login.php
```

## Default Login Credentials

**Admin Account:**
- Username: `admin`
- Password: `admin123`

⚠️ **Important**: Change the default admin password after first login!

## Database Schema

### Tables
- **users** - Store user information and credentials
- **requests** - Event requests created by sales team
- **request_designs** - Design images uploaded by design team
- **approvals** - Approval information with pricing and remarks
- **notifications** - System notifications for users
- **activity_logs** - Audit trail of all user actions

### Relationships
- Users can create multiple requests (1:N)
- Requests can have multiple design images (1:N)
- Requests can have multiple approval records (1:N)
- Users receive multiple notifications (1:N)

## Workflow Process

1. **Sales** creates a new event request → Status: "Pending Design"
2. **Design** team uploads design images → Status: "Pending Approval"
3. **Approval** team reviews and adds pricing → Status: "Sent to Sales"
4. **Sales** reviews and either:
   - Approves → Status: "Approved"
   - Closes → Status: "Closed"
   - Returns to Design → Status: "Returned to Design"

## Directory Structure

```
role-based-workflow-portal/
├── admin/              # Admin module pages
├── sales/              # Sales module pages
├── design/             # Design module pages
├── approval/           # Approval module pages
├── assets/             # CSS, JS, and other assets
│   ├── css/
│   └── js/
├── config/             # Configuration files
├── database/           # Database schema
├── includes/           # Common includes (header, footer)
├── uploads/            # Uploaded design images
├── index.php           # Dashboard
├── login.php           # Login page
├── logout.php          # Logout handler
├── notifications.php   # Notifications page
├── profile.php         # User profile
└── README.md           # This file
```

## Security Considerations

1. **Production Deployment**:
   - Disable error reporting in `config/config.php`
   - Use HTTPS for secure communication
   - Change all default credentials
   - Set proper file permissions
   - Configure secure email settings

2. **Database Security**:
   - Use strong database passwords
   - Restrict database user privileges
   - Regular backups

3. **File Upload Security**:
   - Validate file types and sizes
   - Store uploads outside web root (recommended)
   - Implement virus scanning (optional)

## Customization

### Adding New Roles
1. Update the `role` ENUM in the `users` table
2. Add role checks in `config/config.php`
3. Create role-specific pages
4. Update navigation in `includes/header.php`

### Email Configuration
Update SMTP settings in `config/config.php`:
```php
define('SMTP_HOST', 'your-smtp-host');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email');
define('SMTP_PASS', 'your-password');
```

## Troubleshooting

### Database Connection Issues
- Verify database credentials in `config/database.php`
- Ensure MySQL service is running
- Check database user permissions

### File Upload Issues
- Verify `uploads/` directory exists and has write permissions
- Check PHP `upload_max_filesize` and `post_max_size` settings
- Ensure `MAX_FILE_SIZE` in config matches PHP settings

### Session Issues
- Check PHP session configuration
- Verify session save path is writable
- Clear browser cookies and cache

## License

This project is open-source and available under the MIT License.

## Support

For issues, questions, or contributions, please contact the development team or create an issue on GitHub.

## Contributors

- Development Team
- Project maintained by maneesh7787

---

**Note**: This is a demonstration project. For production use, implement additional security measures, testing, and optimization.