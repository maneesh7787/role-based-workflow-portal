# Where to Find Your Files

## 📍 Files Created in This PR

All files have been successfully created and committed to the branch `copilot/create-sales-request-form`.

### 1. **Exhibitor Sales Request Form** (Main Form Page)
**Location:** `/sales/exhibitor_sales_request.php`
- **Full Path:** `role-based-workflow-portal/sales/exhibitor_sales_request.php`
- **Size:** 27 KB (603 lines)
- **Description:** The main form page where sales users create exhibitor booth requests

### 2. **Database Schema**
**Location:** `/database/exhibitor_requests.sql`
- **Full Path:** `role-based-workflow-portal/database/exhibitor_requests.sql`
- **Size:** 1.7 KB (45 lines)
- **Description:** SQL schema to create the exhibitor_requests table

### 3. **Documentation**
**Location:** `/docs/EXHIBITOR_FORM_GUIDE.md`
- **Full Path:** `role-based-workflow-portal/docs/EXHIBITOR_FORM_GUIDE.md`
- **Size:** 2.4 KB (95 lines)
- **Description:** Complete setup and usage guide

---

## 🌐 How to View Files on GitHub

### Option 1: View on GitHub Web Interface

1. **Go to your repository:**
   ```
   https://github.com/maneesh7787/role-based-workflow-portal
   ```

2. **Switch to the PR branch:**
   - Click on the branch dropdown (currently shows "main")
   - Select `copilot/create-sales-request-form`

3. **Navigate to the files:**
   - **Form:** Click `sales` folder → `exhibitor_sales_request.php`
   - **Database:** Click `database` folder → `exhibitor_requests.sql`
   - **Docs:** Click `docs` folder → `EXHIBITOR_FORM_GUIDE.md`

### Option 2: View the Pull Request

1. **Go to Pull Requests tab:**
   ```
   https://github.com/maneesh7787/role-based-workflow-portal/pulls
   ```

2. **Open PR #3:** "Add sales user request generation form"

3. **Click on "Files changed" tab** to see all modified files with diff view

### Option 3: Direct Links (after PR is pushed)

- **Form:** `https://github.com/maneesh7787/role-based-workflow-portal/blob/copilot/create-sales-request-form/sales/exhibitor_sales_request.php`
- **Database:** `https://github.com/maneesh7787/role-based-workflow-portal/blob/copilot/create-sales-request-form/database/exhibitor_requests.sql`
- **Docs:** `https://github.com/maneesh7787/role-based-workflow-portal/blob/copilot/create-sales-request-form/docs/EXHIBITOR_FORM_GUIDE.md`

---

## 💻 How to Access Files Locally

If you have cloned the repository:

```bash
# Clone the repository (if not already done)
git clone https://github.com/maneesh7787/role-based-workflow-portal.git
cd role-based-workflow-portal

# Switch to the PR branch
git checkout copilot/create-sales-request-form

# View the form file
cat sales/exhibitor_sales_request.php

# View the database schema
cat database/exhibitor_requests.sql

# View the documentation
cat docs/EXHIBITOR_FORM_GUIDE.md
```

### Using Your Code Editor:
1. Open the repository folder in VS Code, PHPStorm, or any editor
2. Navigate to:
   - `sales/exhibitor_sales_request.php`
   - `database/exhibitor_requests.sql`
   - `docs/EXHIBITOR_FORM_GUIDE.md`

---

## 🚀 How to Use the Form

### Step 1: Set Up Database
```bash
# From your local repository
mysql -u your_username -p workflow_portal < database/exhibitor_requests.sql
```

### Step 2: Access the Form
Once deployed on a web server:
```
http://your-domain.com/sales/exhibitor_sales_request.php
```

**Requirements:**
- Must be logged in
- Must have 'Sales' role

### Step 3: View Documentation
Open `docs/EXHIBITOR_FORM_GUIDE.md` for complete setup instructions

---

## 📂 Complete Directory Structure

```
role-based-workflow-portal/
├── sales/
│   ├── create_request.php
│   ├── edit_request.php
│   ├── exhibitor_sales_request.php  ← NEW FILE (Main Form)
│   ├── my_requests.php
│   └── view_request.php
├── database/
│   ├── demo_data.sql
│   ├── exhibitor_requests.sql        ← NEW FILE (Database Schema)
│   └── schema.sql
├── docs/
│   └── EXHIBITOR_FORM_GUIDE.md       ← NEW FILE (Documentation)
└── ... (other folders)
```

---

## 🔗 Quick Access Summary

| File | Local Path | Description |
|------|------------|-------------|
| **Main Form** | `sales/exhibitor_sales_request.php` | 603 lines - Form interface |
| **Database** | `database/exhibitor_requests.sql` | 45 lines - Table schema |
| **Documentation** | `docs/EXHIBITOR_FORM_GUIDE.md` | 95 lines - Setup guide |

---

## ✅ Verification

All files have been:
- ✅ Created successfully
- ✅ Committed to git
- ✅ Pushed to GitHub
- ✅ Included in PR #3
- ✅ Syntax validated (PHP & SQL)
- ✅ Security scanned (0 vulnerabilities)

**Current Branch:** `copilot/create-sales-request-form`
**Status:** Ready for review and merge

---

**Need help accessing the files?** Let me know which method you prefer (GitHub web, local clone, or direct links).
