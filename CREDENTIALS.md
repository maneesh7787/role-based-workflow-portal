# Test Credentials

## Default Admin Account
- **Username:** admin
- **Password:** admin123
- **Email:** admin@workflowportal.com
- **Role:** Admin

## Demo User Accounts (if demo_data.sql is loaded)

### Sales Team
- **Username:** john_sales
- **Password:** password123
- **Email:** john@sales.com
- **Role:** Sales

- **Username:** sarah_sales
- **Password:** password123
- **Email:** sarah@sales.com
- **Role:** Sales

### Design Team
- **Username:** mike_design
- **Password:** password123
- **Email:** mike@design.com
- **Role:** Design

- **Username:** lisa_design
- **Password:** password123
- **Email:** lisa@design.com
- **Role:** Design

### Approval Team
- **Username:** david_approval
- **Password:** password123
- **Email:** david@approval.com
- **Role:** Approval

- **Username:** emily_approval
- **Password:** password123
- **Email:** emily@approval.com
- **Role:** Approval

## Loading Demo Data

To populate the database with demo users and sample requests:

```bash
mysql -u your_username -p workflow_portal < database/demo_data.sql
```

## Important Security Notes

⚠️ **WARNING:** These are test credentials for development only!

For production deployment:
1. Change all default passwords
2. Delete or disable demo accounts
3. Create new users with strong passwords
4. Use unique passwords for each user
5. Enable two-factor authentication if possible

## Password Policy Recommendations

For production users:
- Minimum 8 characters
- Mix of uppercase and lowercase
- Include numbers
- Include special characters
- Avoid common words or patterns
- Change passwords regularly

## Creating New Users

Admin users can create new accounts through:
1. Login as admin
2. Navigate to Admin → Manage Users
3. Click "Create New User"
4. Fill in user details and assign role
5. Set temporary password
6. Instruct user to change password on first login
