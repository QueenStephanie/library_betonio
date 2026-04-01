# QueenLib InfinityFree Deployment Checklist
## Copy, print, or use on your phone during deployment

---

## PHASE 1: GATHER INFORMATION (5 min)

### From InfinityFree Welcome Email
- [ ] Domain: `https://____________.infinityfree.com`
- [ ] FTP Host: `ftp____________.infinityfree.com`
- [ ] FTP Username: `_____________________`
- [ ] FTP Password: `_____________________`

### From InfinityFree cPanel
- [ ] MySQL Host: `sql___.infinityfree.com` (usually sql309)
- [ ] Database Name: `if__________library_betonio`
- [ ] Database User: `if__________admin`
- [ ] Database Password: `_____________________`

---

## PHASE 2: EDIT .env.production (5 min)

### Open File
- [ ] Open: `.env.production` (in project root)
- [ ] If doesn't exist, copy from `.env.production.example`

### Fill Required Fields
```
APP_URL=https://USERNAME.infinityfree.com
DB_HOST=sql309.infinityfree.com
DB_PORT=3306
DB_NAME=if__________library_betonio
DB_USER=if__________admin
DB_PASS=YourDatabasePassword
ADMIN_PASSWORD=YourAdminPassword
```

### Verify Settings
- [ ] APP_URL matches your domain
- [ ] DB_HOST is correct MySQL host
- [ ] DB_NAME starts with "if"
- [ ] DB_USER starts with "if"
- [ ] Passwords are strong (15+ chars)

### Save File
- [ ] Saved .env.production locally

---

## PHASE 3: UPLOAD VIA FTP (10 min)

### Launch FileZilla
- [ ] Opened FileZilla
- [ ] Click "File" → "Site Manager"

### Create Connection
- [ ] Host: `ftp____________.infinityfree.com`
- [ ] Username: (from welcome email)
- [ ] Password: (from welcome email)
- [ ] Port: 21
- [ ] Protocol: FTP
- [ ] Click "Connect"

### Upload Files
- [ ] In Remote Site, navigated to `public_html`
- [ ] Selected all project files locally
- [ ] Dragged files to `public_html`
- [ ] Wait for transfer to complete
- [ ] Files now visible in `public_html` on right panel

### Verify Upload
- [ ] Can see: `index.php` in public_html
- [ ] Can see: `.env.production` in public_html
- [ ] Can see: `includes/` folder in public_html
- [ ] Can see: `backend/` folder in public_html

---

## PHASE 4: CREATE DATABASE (5 min)

### Log into cPanel
- [ ] URL: Your InfinityFree account
- [ ] Username: _____________________
- [ ] Password: _____________________

### Create MySQL Database
- [ ] Clicked "MySQL Databases"
- [ ] Database name: `if__________library_betonio` (from your info)
- [ ] Clicked "Create Database"
- [ ] Saw: "Database created successfully"

### Create MySQL User
- [ ] Username: `if__________admin` (from your info)
- [ ] Password: (strong password you chose)
- [ ] Clicked "Create User"
- [ ] Saw: "User created successfully"

### Add User to Database
- [ ] Selected user: `if__________admin`
- [ ] Selected database: `if__________library_betonio`
- [ ] Checked ALL privileges
- [ ] Clicked "Make Changes"
- [ ] Saw: "User added to database successfully"

---

## PHASE 5: INITIALIZE DATABASE (2 min)

### Run Initialization Script
- [ ] Visited: `https://USERNAME.infinityfree.com/init-database.php`
- [ ] Saw: "✅ Database initialized successfully"
- [ ] Saw tables listed: users, login_history, otp_codes, verification_attempts

### If Failed
- [ ] Checked .env.production is uploaded
- [ ] Verified credentials match cPanel
- [ ] Tried again (reload page)
- [ ] If still fails, check `test-connection.php`

---

## PHASE 6: VERIFY APPLICATION (2 min)

### Test Homepage
- [ ] Visited: `https://USERNAME.infinityfree.com`
- [ ] Saw: QueenLib homepage
- [ ] Saw: "Log In" and "Get Started" buttons
- [ ] Links clickable

### Test Login Page
- [ ] Visited: `https://USERNAME.infinityfree.com/login.php`
- [ ] Saw: Login form with email and password fields

### Test Registration Page
- [ ] Visited: `https://USERNAME.infinityfree.com/register.php`
- [ ] Saw: Registration form with name, email, password fields

### Optional: Test Database Connection
- [ ] Visited: `https://USERNAME.infinityfree.com/test-connection.php`
- [ ] Saw: "✅ Database Connection Successful!"
- [ ] Saw: MySQL version and tables listed

---

## FINAL VERIFICATION

- [ ] All phases completed without errors
- [ ] Homepage loads correctly
- [ ] Application is live on InfinityFree
- [ ] Database is connected and initialized
- [ ] Users can access login and registration pages

---

## COMMON PROBLEMS & QUICK FIXES

### Problem: Page shows blank/white screen
**Fix:**
- [ ] Verified files in `public_html` (not in subfolder)
- [ ] Check that `.env.production` was uploaded
- [ ] Try `test-connection.php` for more info

### Problem: Database connection error
**Fix:**
- [ ] Double-check `.env.production` credentials match cPanel
- [ ] Verify database user has ALL privileges
- [ ] Confirm database was created
- [ ] Check Database name, user, password in `.env.production`

### Problem: Can't access `init-database.php`
**Fix:**
- [ ] Verify file uploaded to `public_html`
- [ ] Check URL is exactly: `https://USERNAME.infinityfree.com/init-database.php`
- [ ] Wait 5 minutes and try again (server may be initializing)

### Problem: HTTPS shows warning
**Fix:**
- [ ] This is normal - InfinityFree SSL takes ~30 min to activate
- [ ] Wait 30 minutes and refresh
- [ ] Or use HTTP (http://username.infinityfree.com) for now

---

## SUCCESS!

If all checkboxes are checked, your QueenLib application is now live on InfinityFree! 🎉

- Your users can access: `https://USERNAME.infinityfree.com`
- They can register and log in
- You can manage the library from the admin panel
- Database is secure and initialized

---

**Time to deploy:** ~30 minutes (first time)  
**Next deploys:** ~5 minutes (just upload changed files)

---

For detailed help, see: `INFINITYFREE_DEPLOY_SINGLE_FILE.md`
