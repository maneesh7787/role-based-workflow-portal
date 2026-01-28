# Exhibitor Sales Request Form - Setup & Usage Guide

## Overview
The Exhibitor Sales Request Form allows sales users to create detailed booth requests for exhibitors with comprehensive specifications including client details, event information, booth requirements, design elements, and delivery priorities.

## Installation

### 1. Database Setup

Run the SQL schema to create the required database table:

```bash
mysql -u your_username -p workflow_portal < database/exhibitor_requests.sql
```

Or manually execute the SQL:
```sql
source database/exhibitor_requests.sql;
```

### 2. Access the Form

**URL:** `http://your-domain.com/sales/exhibitor_sales_request.php`

**Requirements:**
- User must be logged in
- User must have 'Sales' role

## Form Fields

### Requirement Type (Required)
- New Booth - Brand new booth construction
- Booth Renovation - Updates to existing booth
- Booth Rental - Temporary booth rental
- Graphics Only - Graphics and visual elements only

### Client Details
- **Client Name** (Required)
- **Company Name** (Optional)
- **Email Address** (Required) - Must be valid email format
- **Phone Number** (Required) - Auto-formats to XXX-XXX-XXXX

### Event Details
- **Event Name** (Required)
- **Event Date** (Required)
- **Event Location** (Required)

### Booth Specifications
- **Booth Size** (Optional) - e.g., 10x10, 20x20
- **Booth Type** (Required) - Standard, Custom, Island, Peninsula, Inline
- **Booth Number** (Optional)

### Design Requirements (Optional)
Select all that apply:
- Graphics & Banners
- Lighting
- Flooring
- Furniture
- A/V Equipment
- Storage
- Product Displays
- Signage

### Delivery Priority (Required)
- Standard (4-6 weeks)
- Expedited (2-3 weeks)
- Rush (1 week)

### Special Requests (Optional)
Free-form text for additional notes

## Security Features

1. **Authentication Required**: Only logged-in Sales users
2. **Input Sanitization**: All inputs sanitized
3. **XSS Protection**: htmlspecialchars() on outputs
4. **SQL Injection Prevention**: Prepared statements
5. **CSRF Protection**: Session-based authentication

## Troubleshooting

### Form Not Submitting
- Check all required fields (marked with *)
- Verify email format is valid
- Check phone follows XXX-XXX-XXXX format

### Database Errors
- Verify exhibitor_requests table exists
- Check database connection
- Ensure user has INSERT permissions

---

**Version:** 1.0  
**Date:** 2026-01-28
