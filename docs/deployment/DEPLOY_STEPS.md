# QueenLib Deployment Guide - InfinityFree

## Pre-Deployment Status ✅
- Code is deployment-ready
- All PHP files pass syntax validation  
- Environment configuration working
- Mail handler fixed for correct URLs

---

## Step 1: Get InfinityFree Credentials

1. Log in to **InfinityFree Control Panel**
2. Go to **MySQL Databases**
3. Note these values:
   - **MySQL Host**: (e.g., `sql123.infinityfree.com`)
   - **Database Name**: (e.g., `if0_12345678_queenlib`)
   - **Username**: (e.g., `if0_12345678`)
   - **Password**: Your MySQL password

---

## Step 2: Update .env.production

Edit the file `.env.production` in your project with your actual values:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.epizy.com
APP_BASE_PATH=
APP_TIMEZONE=UTC
APP_NAME=QueenLib
DB_HOST=sql123.infinityfree.com
DB_PORT=3306
DB_NAME=if0_12345678_queenlib
DB_USER=if0_12345678
DB_PASS=your_mysql_password
DB_CHARSET=utf8mb4
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USER=sordillamike1@gmail.com
MAIL_PASS=dxmlnmzqbmogepbv
MAIL_FROM=sordillamike1@gmail.com
MAIL_FROM_NAME=QueenLib
MAIL_ENCRYPTION=tls
ADMIN_USERNAME=admin
ADMIN_PASSWORD=your_secure_admin_password
SESSION_TIMEOUT=3600
BCRYPT_COST=12
OTP_EXPIRY=600
RATE_LIMIT_ENABLED=true
RATE_LIMIT_REQUESTS=100
RATE_LIMIT_WINDOW=60
```

**Important changes:**
- Replace `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` with your InfinityFree MySQL details
- Set `MAIL_USER` and `MAIL_PASS` to your Gmail and App Password
- Change `ADMIN_PASSWORD` to a secure password

---

## Step 3: Upload Files to InfinityFree

### Option A: File Manager (Recommended)

1. Go to InfinityFree Control Panel → **File Manager**
2. Navigate to `htdocs` folder
3. **Delete** everything in `htdocs` first (if any)
4. Click **Upload**
5. Upload ALL files and folders from your project **EXCEPT**:
   - `.git/` folder (delete before upload)
   - `testsprite_tests/` folder (delete before upload)
   - `_legacy/` folder (optional - can delete)
   - `.env` (local only - delete before upload)

### Option B: FTP

1. Use FTP client (FileZilla)
2. Connect to your InfinityFree FTP
3. Navigate to `htdocs`
4. Upload all project files

---

## Step 4: Import Database

1. In InfinityFree Panel, click **phpMyAdmin**
2. Log in with your MySQL credentials
3. Select your database from left sidebar
4. Click **Import** tab
5. Click **Choose File**
6. Select: `backend/config/schema.sql`
7. Click **Go** (at bottom)

---

## Step 5: Verify Deployment

Test these URLs:

| Page | Expected |
|------|----------|
| Home | `https://yourdomain.epizy.com/` |
| Register | `https://yourdomain.epizy.com/register.php` |
| Login | `https://yourdomain.epizy.com/login.php` |
| Admin | `https://yourdomain.epizy.com/admin-login.php` |

---

## Step 6: Test Core Features

### Test 1: User Registration
1. Go to register page
2. Fill in details
3. Check email for verification link
4. Click link to verify

### Test 2: Password Reset
1. Go to login page
2. Click "Forgot password?"
3. Enter your registered email
4. Check email for reset link
5. Click link and set new password

### Test 3: Admin Login
1. Go to `admin-login.php`
2. Use credentials from `.env.production`
   - Username: `admin`
   - Password: (what you set in ADMIN_PASSWORD)

---

## Troubleshooting

### "Database connection failed"
- Check DB credentials in `.env.production`
- Verify DB_PORT is `3306`
- Ensure database was created in InfinityFree

### "Email not sending"
- For Gmail, make sure you're using **App Password** (not your login password)
- Enable 2FA on Gmail, then generate App Password

### "Page not found errors"
- Ensure `APP_BASE_PATH=` is empty (if at root)
- If in subfolder, set `APP_BASE_PATH=/subfoldername`

### "Styles not loading"
- Clear browser cache
- Check browser console for 404 errors

---

## Security Reminders

1. **Change default admin password** - Don't use "admin123" in production
2. **Keep `.env.production` private** - It's protected by .htaccess
3. **Don't upload `.git/`** - Contains sensitive data

---

## URLs After Deployment

| Page | URL |
|------|-----|
| Home | `https://yourdomain.epizy.com/` |
| Register | `https://yourdomain.epizy.com/register.php` |
| Login | `https://yourdomain.epizy.com/login.php` |
| Forgot Password | `https://yourdomain.epizy.com/forgot-password.php` |
| Account Settings | `https://yourdomain.epizy.com/account.php` |
| Admin Login | `https://yourdomain.epizy.com/admin-login.php` |
| Admin Dashboard | `https://yourdomain.epizy.com/admin-dashboard.php` |

---

**Ready to deploy!** 🚀