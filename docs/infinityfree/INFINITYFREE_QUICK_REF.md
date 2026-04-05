# InfinityFree Deployment - Quick Reference
## 30-Second Overview

### The ONE File to Change
📄 **File:** `.env.production`

### What to Fill In
```ini
# Your InfinityFree domain
APP_URL=https://yourusername.infinityfree.com

# Your database info (from cPanel)
DB_HOST=sql309.infinityfree.com
DB_NAME=ifXXXXXXX_libraryname
DB_USER=ifXXXXXXX_user
DB_PASS=YourPassword

# Admin login
ADMIN_USERNAME=admin
ADMIN_PASSWORD=YourAdminPassword
```

### 5-Step Deployment
1. ✏️ **Edit:** `.env.production` with your credentials
2. 📤 **Upload:** All files to `public_html` via FTP
3. 🗄️ **Create:** Database in cPanel (MySQL Databases)
4. 🔧 **Init:** Visit `/init-database.php` to create tables
5. ✅ **Verify:** Visit homepage - should work!

### Quick Checklist
- [ ] Collected InfinityFree info
- [ ] Edited `.env.production`
- [ ] Uploaded all files including `.env.production`
- [ ] Created database in cPanel
- [ ] Created database user with ALL privileges
- [ ] Ran `init-database.php`
- [ ] Homepage loads successfully

### Key URLs After Deploy
- Homepage: `https://yourusername.infinityfree.com`
- Login: `https://yourusername.infinityfree.com/login.php`
- Register: `https://yourusername.infinityfree.com/register.php`
- Test DB: `https://yourusername.infinityfree.com/test-connection.php`

### Common Issues
| Issue | Fix |
|-------|-----|
| Database connection error | Verify credentials in `.env.production` match cPanel |
| Blank page | Check files are in `public_html`, not in a subfolder |
| init-database.php fails | Confirm database & user created in cPanel |
| HTTPS certificate | InfinityFree provides free SSL automatically |

---

**Full guide:** See `INFINITYFREE_DEPLOY_SINGLE_FILE.md`
