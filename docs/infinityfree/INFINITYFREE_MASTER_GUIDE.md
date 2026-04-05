# QueenLib Single-File Deployment System
## Complete Guide Index

---

## 🚀 START HERE

You have ONE file to change to deploy QueenLib to InfinityFree:

### **The File:** `.env.production`

That's it! No code changes. No complex setup. Just:
1. Edit `.env.production` with your InfinityFree credentials
2. Upload all files via FTP
3. Create database in cPanel
4. Run init script
5. Done!

---

## 📚 Documentation Files

### **For First-Time Users**
Start with these files in order:

1. **`INFINITYFREE_CREDENTIALS_SHEET.md`** ⭐ START HERE
   - Print-friendly credential collection sheet
   - Fields to fill from InfinityFree
   - Complete `.env.production` template
   - **Read this first** and gather your credentials

2. **`INFINITYFREE_DEPLOY_SINGLE_FILE.md`** ⭐ MAIN GUIDE
   - Complete step-by-step deployment guide
   - Exactly what to put in each field
   - FTP upload instructions with FileZilla
   - Database setup in cPanel
   - Verification steps
   - Troubleshooting section
   - **This is the primary reference**

3. **`INFINITYFREE_DEPLOYMENT_CHECKLIST_DETAILED.md`**
   - Printable/mobile-friendly checklist
   - All steps in checkbox format
   - Quick fixes for common problems
   - Use this while deploying

### **For Quick Reference**
When you need reminders:

- **`INFINITYFREE_QUICK_REF.md`** - One-page quick reference
- **`INFINITYFREE_QUICK_CARD.md`** - 30-second overview

---

## 🎯 The Deployment Process

### **Before You Start** (Preparation)
1. Open `INFINITYFREE_CREDENTIALS_SHEET.md`
2. Gather info from InfinityFree welcome email
3. Log into cPanel and get database info
4. Fill in the checklist completely

### **Step 1: Edit One File** (5 min)
1. Open `.env.production` in your project
2. Fill in values from your credentials sheet
3. Save the file
4. Verify all required fields are filled

### **Step 2: Upload via FTP** (10 min)
1. Install FileZilla (free)
2. Connect to FTP using credentials from welcome email
3. Navigate to `public_html` on server
4. Upload all project files including `.env.production`

### **Step 3: Setup Database** (5 min)
1. Log into InfinityFree cPanel
2. Go to "MySQL Databases"
3. Create database (name from `.env.production`)
4. Create user (username from `.env.production`)
5. Grant ALL privileges to user

### **Step 4: Initialize** (2 min)
1. Visit: `https://yourdomain.infinityfree.com/init-database.php`
2. Wait for success message
3. Tables created automatically

### **Step 5: Verify** (2 min)
1. Visit homepage: `https://yourdomain.infinityfree.com`
2. Check login page works
3. Check registration page works
4. Done! 🎉

---

## 🔑 What Goes in `.env.production`

### **Absolutely Must Change**
These fields MUST be updated with YOUR values:

```ini
APP_URL=https://yourusername.infinityfree.com
DB_HOST=sql309.infinityfree.com
DB_NAME=if__________library_betonio
DB_USER=if__________admin
DB_PASS=YourDatabasePassword
ADMIN_PASSWORD=YourAdminPassword
```

### **Should Update**
These are optional but recommended:

```ini
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USER=your-email@gmail.com
MAIL_PASS=YourGmailAppPassword
```

### **Can Leave as Default**
These have sensible defaults:

```ini
APP_ENV=production
APP_DEBUG=false
DB_CHARSET=utf8mb4
SESSION_TIMEOUT=3600
BCRYPT_COST=12
```

---

## ✅ Complete Checklist

### Pre-Deployment
- [ ] Read `INFINITYFREE_CREDENTIALS_SHEET.md`
- [ ] Collected all credentials from InfinityFree
- [ ] Filled credentials in checklist

### File Editing
- [ ] Opened `.env.production`
- [ ] Filled in APP_URL with your domain
- [ ] Filled in all DB_* fields from cPanel
- [ ] Changed ADMIN_PASSWORD to something secure
- [ ] Saved file

### FTP Upload
- [ ] Installed FileZilla
- [ ] Connected to FTP server
- [ ] Navigated to `public_html`
- [ ] Uploaded all files
- [ ] Verified files are in `public_html`

### Database Setup
- [ ] Logged into cPanel
- [ ] Created MySQL database
- [ ] Created MySQL user
- [ ] Added user to database with ALL privileges

### Deployment
- [ ] Visited `init-database.php`
- [ ] Saw success message
- [ ] Homepage loads: `https://yourusername.infinityfree.com`
- [ ] Login page works
- [ ] Registration page works

### Post-Deployment
- [ ] Tested creating new user account
- [ ] Tested logging in
- [ ] Verified admin panel works
- [ ] Set up email (optional)

---

## 🐛 Troubleshooting Guide

| Problem | Solution | Help File |
|---------|----------|-----------|
| Database won't connect | Check credentials in `.env.production` match cPanel | `INFINITYFREE_DEPLOY_SINGLE_FILE.md` |
| Blank page | Files might be in wrong folder, check `public_html` | `INFINITYFREE_DEPLOY_SINGLE_FILE.md` |
| Can't upload files | Try different FTP port (21 or 2121) | `INFINITYFREE_DEPLOY_SINGLE_FILE.md` |
| init-database.php fails | Database not created, go back to cPanel | `INFINITYFREE_DEPLOY_SINGLE_FILE.md` |
| Email not working | Configure MAIL_* in `.env.production` (optional) | `INFINITYFREE_DEPLOY_SINGLE_FILE.md` |
| HTTPS shows warning | Normal - SSL takes ~30 min to activate | `INFINITYFREE_DEPLOY_SINGLE_FILE.md` |

---

## 📋 File Reference

### Deployment Guides
- `INFINITYFREE_DEPLOY_SINGLE_FILE.md` - Full step-by-step guide
- `INFINITYFREE_CREDENTIALS_SHEET.md` - Credential collection template
- `INFINITYFREE_DEPLOYMENT_CHECKLIST_DETAILED.md` - Detailed printable checklist
- `INFINITYFREE_QUICK_REF.md` - One-page quick reference
- `INFINITYFREE_QUICK_CARD.md` - 30-second overview

### Application Files
- `.env.production` - Configuration file (THE ONE FILE YOU CHANGE)
- `.env.production.example` - Template for reference
- `.env` - For localhost development only
- `includes/config.php` - Loads environment variables automatically

### Helper Scripts
- `init-database.php` - Creates database tables
- `test-connection.php` - Tests database connection

### Localhost Reference
- `DATABASE_CONNECTION_GUIDE.md` - Database troubleshooting for development

---

## 🔒 Security Best Practices

### Before Deployment
- [ ] Remove `.env` file from upload (only use on localhost)
- [ ] Never commit `.env.production` to Git
- [ ] Use strong passwords (15+ characters)
- [ ] Change admin username from "admin"

### After Deployment
- [ ] Keep `.env.production` secret
- [ ] Monitor error logs regularly
- [ ] Update passwords periodically
- [ ] Test security features

### Files to NOT Upload
- `.git` folder
- `.env` (localhost only)
- `.vscode` folder
- `testsprite_tests` folder
- `node_modules` folder
- Any `.md` files except optional reference

---

## 📊 Expected Result

After completing deployment, you'll have:

### ✅ Working Application
- Homepage accessible at your domain
- User registration working
- User login working
- Admin panel accessible

### ✅ Working Database
- 4 tables created: users, login_history, otp_codes, verification_attempts
- Database user with proper permissions
- Connection to InfinityFree MySQL server

### ✅ Security Features
- HTTPS enabled (free InfinityFree SSL)
- Database credentials secured in `.env.production`
- Admin password protected
- Sessions configured securely

---

## 💡 Important Insights

### Why Just One File?
- **Code is environment-agnostic** - Same code works everywhere
- **Configuration is external** - All settings in `.env.production`
- **No code changes needed** - Update configuration, not code
- **Security focus** - Secrets never in version control

### Why This Approach Works
1. ✅ Same application code on localhost and production
2. ✅ Different configuration files for different environments
3. ✅ No rebuilding or compilation needed
4. ✅ Easy to update later
5. ✅ Secure - secrets not in Git

### How It Works
```
Application starts
    ↓
Loads includes/config.php
    ↓
Looks for .env.production first
    ↓
Loads it (on InfinityFree) or .env (on localhost)
    ↓
Application has all config from environment variables
    ↓
Uses database credentials, URL, etc. from config
    ↓
Works! 🎉
```

---

## 🎯 Quick Start for Experienced Users

1. Grab your InfinityFree credentials
2. Edit `.env.production` (5 min)
3. Upload via FTP (10 min)
4. Create database in cPanel (5 min)
5. Run `init-database.php` (2 min)
6. Done! (22 min total)

---

## 📞 Need Help?

1. **Check troubleshooting:** `INFINITYFREE_DEPLOY_SINGLE_FILE.md`
2. **Review credentials:** `INFINITYFREE_CREDENTIALS_SHEET.md`
3. **Follow checklist:** `INFINITYFREE_DEPLOYMENT_CHECKLIST_DETAILED.md`
4. **Test connection:** Visit `/test-connection.php`

---

## 🎉 Next Steps After Deployment

1. **Create admin account** - Test admin login
2. **Configure email** (optional) - For password resets
3. **Invite users** - Share your domain
4. **Set library settings** - Configure library name, hours, etc.
5. **Import books** - Add book catalog
6. **Monitor system** - Check logs, backups

---

**Remember:** You only need to change ONE file. Everything else is automatic!

---

**Last Updated:** March 2026  
**Version:** 1.0 - Single File Deployment System  
**Application:** QueenLib Library Management System
