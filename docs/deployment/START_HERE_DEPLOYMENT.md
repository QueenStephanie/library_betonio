# START HERE: Your First Steps 👈
## QueenLib InfinityFree Deployment - 3 Easy Steps

---

## 🎯 Your Mission

Deploy QueenLib to InfinityFree by changing **ONE file**.

---

## ✨ How It Works

```
Edit .env.production ← Only this file!
         ↓
Upload all files via FTP
         ↓
Create database in cPanel
         ↓
Run init script
         ↓
Done! 🎉
```

---

## 📋 STEP-BY-STEP (30 minutes total)

### **STEP 1: Gather Your Information** (5 min)

**From your InfinityFree Welcome Email, write down:**
```
Domain:         https://_________________________.infinityfree.com
FTP Host:       ftp________________________.infinityfree.com
FTP Username:   _________________________________
FTP Password:   _________________________________
```

**From InfinityFree cPanel, write down:**
```
MySQL Host:     sql___.infinityfree.com
Database Name:  if__________library_betonio
Database User:  if__________admin
Database Pass:  _________________________________
```

👉 **See:** `INFINITYFREE_CREDENTIALS_SHEET.md` for detailed collection instructions

---

### **STEP 2: Edit .env.production** (5 min)

**In your project, open:** `.env.production`

**Replace these values ONLY:**

```ini
# Your domain from Step 1
APP_URL=https://yourusername.infinityfree.com

# Your database info from Step 1
DB_HOST=sql309.infinityfree.com
DB_PORT=3306
DB_NAME=if__________library_betonio
DB_USER=if__________admin
DB_PASS=YourDatabasePassword

# Create a strong admin password
ADMIN_PASSWORD=YourAdminPassword123
```

**Save the file!**

👉 **See:** `INFINITYFREE_DEPLOY_SINGLE_FILE.md` - Step 2 for detailed instructions

---

### **STEP 3: Upload via FTP** (10 min)

1. **Download FileZilla** (free): https://filezilla-project.org/

2. **Connect to FTP:**
   - Host: `ftpXX.infinityfree.com` (from Step 1)
   - Username: (from Step 1)
   - Password: (from Step 1)
   - Port: 21
   - Click "Quickconnect"

3. **Navigate to `public_html` on the server** (right panel)

4. **Upload all files:**
   - Select all files in your project (left panel)
   - Drag to `public_html` (right panel)
   - **Include:** `.env.production` ✅
   - **Exclude:** `.env` (localhost only), `.git` folder

5. **Wait for transfer to complete**

👉 **See:** `INFINITYFREE_DEPLOY_SINGLE_FILE.md` - Step 3 for FileZilla screenshots

---

### **STEP 4: Create Database** (5 min)

1. **Log into InfinityFree cPanel**

2. **Find "MySQL Databases"**

3. **Create new database:**
   - Name: `if__________library_betonio` (from your info)
   - Click "Create Database"

4. **Create new user:**
   - Username: `if__________admin` (from your info)
   - Password: (your strong password)
   - Click "Create User"

5. **Add user to database:**
   - Select the user
   - Select the database
   - Check "ALL" privileges
   - Click "Make Changes"

👉 **See:** `INFINITYFREE_DEPLOY_SINGLE_FILE.md` - Step 4 for cPanel screenshots

---

### **STEP 5: Initialize Database** (2 min)

1. **Visit:** `https://yourusername.infinityfree.com/init-database.php`
   (Replace `yourusername` with your actual username)

2. **You should see:**
   ```
   ✅ Database initialized successfully!
   Tables: users, login_history, otp_codes, verification_attempts
   ```

3. **If you see an error:**
   - Check `.env.production` uploaded
   - Verify credentials match cPanel
   - Wait 5 minutes and try again

👉 **See:** `INFINITYFREE_DEPLOY_SINGLE_FILE.md` - Step 5 for troubleshooting

---

### **STEP 6: Verify It Works** (2 min)

Visit these URLs:

1. **Homepage:** `https://yourusername.infinityfree.com`
   - Should see QueenLib welcome page ✅

2. **Login:** `https://yourusername.infinityfree.com/login.php`
   - Should see login form ✅

3. **Register:** `https://yourusername.infinityfree.com/register.php`
   - Should see registration form ✅

**If all work:** 🎉 **You're done!**

---

## ✅ Quick Checklist

- [ ] Collected all credentials from InfinityFree
- [ ] Edited `.env.production` with credentials
- [ ] Uploaded all files to `public_html` via FTP
- [ ] Created database in cPanel
- [ ] Created database user in cPanel
- [ ] Granted ALL privileges to user
- [ ] Ran `init-database.php` successfully
- [ ] Homepage loads at your domain
- [ ] Login page works
- [ ] Registration page works

**All checked?** Your application is LIVE! 🚀

---

## 🐛 Something Not Working?

### Database won't connect
```
Check:
1. Is .env.production uploaded?
2. Do credentials match cPanel exactly?
3. Did you grant ALL privileges?
```
👉 See: `INFINITYFREE_DEPLOY_SINGLE_FILE.md` - Troubleshooting

### Blank page
```
Check:
1. Are files in public_html (not in subfolder)?
2. Is index.php in public_html?
3. Is .env.production in public_html?
```
👉 See: `INFINITYFREE_DEPLOYMENT_CHECKLIST_DETAILED.md` - Common Problems

### Can't upload files
```
Check:
1. FTP host from welcome email?
2. FTP username and password correct?
3. Try port 21 or 2121
4. Make sure in public_html folder
```
👉 See: `INFINITYFREE_DEPLOY_SINGLE_FILE.md` - FTP section

---

## 📚 Full Guides (If You Need More Details)

| Guide | When to Read |
|-------|--------------|
| `INFINITYFREE_DEPLOY_SINGLE_FILE.md` | For complete instructions |
| `INFINITYFREE_DEPLOYMENT_CHECKLIST_DETAILED.md` | To use while deploying |
| `INFINITYFREE_CREDENTIALS_SHEET.md` | To collect credentials |
| `INFINITYFREE_QUICK_REF.md` | For quick reminders |
| `INFINITYFREE_MASTER_GUIDE.md` | To understand the system |

---

## 🔒 Important Security Notes

⚠️ **Protect `.env.production`**
- Contains your database password
- Contains your admin password
- Keep it secret!
- Never commit to Git

✅ **Use strong passwords**
- Database password: 15+ characters
- Admin password: 15+ characters
- Mix uppercase, lowercase, numbers, symbols

---

## 🎉 When It's Working

You'll have:
- ✅ Live website at your domain
- ✅ Users can register
- ✅ Users can login
- ✅ Admin panel working
- ✅ Database secure
- ✅ HTTPS/SSL certificate (free)

---

## 💡 Remember

**Only ONE file changes:** `.env.production`

Everything else is automatic!

---

**You've got this!** 🚀

Questions? Check the full guides listed above!
