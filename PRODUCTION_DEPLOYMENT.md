# QueenLib Production Deployment Guide
## Comprehensive Guide for Deploying to Production

---

## 📋 Table of Contents

1. [Pre-Deployment Checklist](#pre-deployment-checklist)
2. [Security Configuration](#security-configuration)
3. [Server Setup](#server-setup)
4. [Database Configuration](#database-configuration)
5. [Email Service Setup](#email-service-setup)
6. [Environment Variables](#environment-variables)
7. [Performance Optimization](#performance-optimization)
8. [Monitoring & Logging](#monitoring--logging)
9. [Deployment Process](#deployment-process)
10. [Post-Deployment Verification](#post-deployment-verification)
11. [Rollback Procedures](#rollback-procedures)

---

## ✅ Pre-Deployment Checklist

### Phase 1: Security Audit (Complete Before Deploy)

- [ ] **HTTPS/SSL Certificate**
  - [ ] Obtain SSL certificate (Let's Encrypt free or commercial)
  - [ ] Configure Apache SSL module
  - [ ] Test certificate validity
  - [ ] Setup automatic renewal (Let's Encrypt)

- [ ] **Environment Hardening**
  - [ ] Disable PHP error display (.env or php.ini)
  - [ ] Enable PHP error logging (outside webroot)
  - [ ] Set restrictive file permissions (644 files, 755 dirs)
  - [ ] Hide PHP version info (.htaccess)
  - [ ] Disable dangerous functions (eval, exec, system)

- [ ] **Database Security**
  - [ ] Create dedicated database user (not root)
  - [ ] Set strong password (30+ characters, mixed case, symbols)
  - [ ] Restrict database user permissions to single DB
  - [ ] Enable MySQL password encryption
  - [ ] Setup automated backups

- [ ] **Application Security**
  - [ ] Review `includes/config.php` for hardcoded secrets
  - [ ] All sensitive data in environment variables (.env)
  - [ ] Session cookie flags verified (HTTPOnly, Secure, SameSite)
  - [ ] CSRF tokens enabled on all forms
  - [ ] Input validation in place on all endpoints
  - [ ] Rate limiting ready for implementation

### Phase 2: Infrastructure Review

- [ ] **Hosting Provider**
  - [ ] PHP 8.0+ support confirmed
  - [ ] MySQL 5.7+ support confirmed
  - [ ] HTTPS/SSL available
  - [ ] Automated backups available
  - [ ] SSH access available
  - [ ] Cron job support for scheduled tasks

- [ ] **Domain & DNS**
  - [ ] Domain registered and paid for (1+ year)
  - [ ] DNS A record points to server IP
  - [ ] MX records configured for email
  - [ ] SPF record set for email authentication
  - [ ] DKIM configured for email domain

- [ ] **Email Service**
  - [ ] Gmail App Password generated or email service account created
  - [ ] SMTP credentials secured in environment variables
  - [ ] Test email sent successfully
  - [ ] Email templates reviewed
  - [ ] Bounce/reply address configured

### Phase 3: Data Migration

- [ ] **Database Setup**
  - [ ] Production database created
  - [ ] `schema.sql` executed successfully
  - [ ] Sample data migrated (if applicable)
  - [ ] Indexes verified
  - [ ] Database character set UTF8MB4

- [ ] **File System**
  - [ ] File permissions set correctly (644, 755)
  - [ ] No sensitive files in webroot
  - [ ] Upload directories writable but outside webroot
  - [ ] .htaccess security rules in place

### Phase 4: Testing in Staging

- [ ] **Staging Environment Created**
  - [ ] Matches production configuration
  - [ ] All tests pass in staging first
  - [ ] Performance benchmarked
  - [ ] User acceptance testing complete

---

## 🔒 Security Configuration

### 1. Create Production Environment File

Create `includes/env.php` (ignore in git):

```php
<?php
/**
 * Production Environment Configuration
 * KEEP THIS FILE SECRET - Never commit to version control
 */

// Database Configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'queenlib_prod');
define('DB_USER', getenv('DB_USER') ?: 'queenlib_user');
define('DB_PASS', getenv('DB_PASS') ?: '');

// Application Configuration
define('APP_ENV', getenv('APP_ENV') ?: 'production');
define('APP_URL', getenv('APP_URL') ?: 'https://yourdomain.com');
define('APP_DEBUG', getenv('APP_DEBUG') ?: false);

// Email Configuration
define('MAIL_HOST', getenv('MAIL_HOST') ?: 'smtp.gmail.com');
define('MAIL_PORT', getenv('MAIL_PORT') ?: '587');
define('MAIL_USER', getenv('MAIL_USER') ?: '');
define('MAIL_PASS', getenv('MAIL_PASS') ?: '');
define('MAIL_FROM', getenv('MAIL_FROM') ?: 'noreply@yourdomain.com');
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: 'QueenLib');

// Security Configuration
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('TOKEN_EXPIRY', 86400); // 24 hours for email verification
define('RESET_TOKEN_EXPIRY', 3600); // 1 hour for password reset
define('BCRYPT_COST', 12);

// API Configuration
define('API_RATE_LIMIT_ENABLED', true);
define('API_RATE_LIMIT_REQUESTS', 100); // per minute
define('API_RATE_LIMIT_WINDOW', 60); // seconds

// Logging Configuration
define('LOG_PATH', '/var/log/queenlib/');
define('LOG_LEVEL', 'warning'); // production should log warnings and above
```

### 2. Secure Apache Configuration

Create `.htaccess` in project root:

```apache
# Redirect HTTP to HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Remove PHP version exposure
Header always unset X-Powered-By
Header always unset Server

# Security Headers
Header always set X-Content-Type-Options "nosniff"
Header always set X-Frame-Options "DENY"
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
Header always set Permissions-Policy "geolocation=(), microphone=(), camera=()"

# Prevent directory listing
Options -Indexes

# Deny access to sensitive files
<FilesMatch "\.(env|git|sql|md|log)$">
    Order deny,allow
    Deny from all
</FilesMatch>

# Cache control
<FilesMatch "\.(jpg|jpeg|png|gif|css|js|ico)$">
    Header set Cache-Control "max-age=31536000, public"
</FilesMatch>

# Prevent execution in upload directories
<Directory "uploads">
    php_flag engine off
</Directory>
```

### 3. PHP Configuration (php.ini)

```ini
; Production PHP Configuration
display_errors = Off
log_errors = On
error_log = /var/log/php/error.log
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT

; Session Security
session.use_strict_mode = 1
session.cookie_httponly = 1
session.cookie_secure = 1
session.cookie_samesite = Strict
session.gc_maxlifetime = 3600

; Database
mysqli.default_socket = /var/run/mysqld/mysqld.sock

; Execution
max_execution_time = 30
max_input_time = 60
memory_limit = 256M
upload_max_filesize = 10M
post_max_size = 10M

; Security
expose_php = Off
disable_functions = exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source
```

---

## 🖥️ Server Setup

### 1. Install Required Packages (Ubuntu/Debian)

```bash
# Update system
sudo apt-get update && sudo apt-get upgrade -y

# Install Apache
sudo apt-get install -y apache2 libapache2-mod-php

# Install PHP and extensions
sudo apt-get install -y php8.1-fpm php8.1-mysql php8.1-mbstring php8.1-bcmath php8.1-curl php8.1-json php8.1-zip

# Install MySQL
sudo apt-get install -y mysql-server

# Install SSL support
sudo apt-get install -y certbot python3-certbot-apache

# Install Git
sudo apt-get install -y git

# Install Composer
sudo apt-get install -y composer
```

### 2. Configure Apache Virtual Host

Create `/etc/apache2/sites-available/queenlib.conf`:

```apache
<VirtualHost *:443>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    ServerAdmin admin@yourdomain.com

    # Document Root
    DocumentRoot /var/www/queenlib

    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/yourdomain.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/yourdomain.com/privkey.pem

    # PHP Configuration
    <FilesMatch \.php$>
        SetHandler "proxy:unix:/run/php-fpm.sock|fcgi://localhost"
    </FilesMatch>

    # Directory Configuration
    <Directory /var/www/queenlib>
        Options -MultiViews
        AllowOverride All
        Require all granted
        
        # Rewrite rules for clean URLs
        RewriteEngine On
        RewriteBase /
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]
    </Directory>

    # Log Configuration
    ErrorLog ${APACHE_LOG_DIR}/queenlib_error.log
    CustomLog ${APACHE_LOG_DIR}/queenlib_access.log combined

    # Security Headers
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
</VirtualHost>

# Redirect HTTP to HTTPS
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    Redirect permanent / https://yourdomain.com/
</VirtualHost>
```

Enable the site:

```bash
sudo a2ensite queenlib
sudo a2enmod rewrite
sudo a2enmod headers
sudo a2enmod ssl
sudo apache2ctl configtest
sudo systemctl restart apache2
```

### 3. Setup SSL Certificate (Let's Encrypt)

```bash
# Generate certificate
sudo certbot certonly --apache -d yourdomain.com -d www.yourdomain.com

# Setup auto-renewal
sudo systemctl enable certbot.timer
sudo systemctl start certbot.timer

# Verify renewal
sudo certbot renew --dry-run
```

---

## 🗄️ Database Configuration

### 1. Create Production Database

```bash
# Connect to MySQL
mysql -u root -p

# Execute in MySQL:
CREATE DATABASE queenlib_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'queenlib_user'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';
GRANT SELECT, INSERT, UPDATE, DELETE ON queenlib_prod.* TO 'queenlib_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 2. Import Database Schema

```bash
# Import schema
mysql -u queenlib_user -p queenlib_prod < /path/to/backend/config/schema.sql

# Verify tables
mysql -u queenlib_user -p queenlib_prod -e "SHOW TABLES;"
```

### 3. Setup Automated Backups

Create `/usr/local/bin/backup-queenlib.sh`:

```bash
#!/bin/bash

# Database backup configuration
DB_USER="queenlib_user"
DB_PASSWORD="your_password"
DB_NAME="queenlib_prod"
BACKUP_DIR="/backups/queenlib"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u $DB_USER -p$DB_PASSWORD $DB_NAME | gzip > $BACKUP_DIR/db_backup_$TIMESTAMP.sql.gz

# Backup application files
tar -czf $BACKUP_DIR/app_backup_$TIMESTAMP.tar.gz /var/www/queenlib

# Keep only last 30 days of backups
find $BACKUP_DIR -type f -mtime +30 -delete

# Upload to S3 (optional)
# aws s3 cp $BACKUP_DIR/db_backup_$TIMESTAMP.sql.gz s3://your-backup-bucket/

echo "Backup completed: $TIMESTAMP"
```

Make executable and add to crontab:

```bash
sudo chmod +x /usr/local/bin/backup-queenlib.sh

# Add to crontab (daily at 2 AM)
sudo crontab -e
# Add line: 0 2 * * * /usr/local/bin/backup-queenlib.sh
```

---

## 📧 Email Service Setup

### Option 1: Gmail SMTP

```php
// Generate App Password from Gmail account:
// 1. Enable 2-factor authentication
// 2. Go to https://myaccount.google.com/apppasswords
// 3. Generate app password for Mail > Other (custom name)

// Environment variables:
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', '587');
define('MAIL_USER', 'your-email@gmail.com');
define('MAIL_PASS', 'xxxx xxxx xxxx xxxx'); // 16-character app password
define('MAIL_FROM', 'your-email@gmail.com');
```

### Option 2: SendGrid

```php
// Signup at https://sendgrid.com
// Generate API key from dashboard

define('MAIL_HOST', 'smtp.sendgrid.net');
define('MAIL_PORT', '587');
define('MAIL_USER', 'apikey');
define('MAIL_PASS', 'SG.xxxxxxxxxxxxxxxxxxxxx'); // SendGrid API key
define('MAIL_FROM', 'noreply@yourdomain.com');
```

### Option 3: Mailgun

```php
// Signup at https://mailgun.com
// Get SMTP credentials

define('MAIL_HOST', 'smtp.mailgun.org');
define('MAIL_PORT', '587');
define('MAIL_USER', 'postmaster@yourdomain.com');
define('MAIL_PASS', 'your-mailgun-password');
define('MAIL_FROM', 'noreply@yourdomain.com');
```

### Test Email Configuration

Create `test-email.php` in project root:

```php
<?php
require_once 'backend/vendor/autoload.php';
require_once 'includes/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    // Server configuration
    $mail->SMTPDebug = 2;
    $mail->isSMTP();
    $mail->Host = MAIL_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = MAIL_USER;
    $mail->Password = MAIL_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = MAIL_PORT;

    // Sender
    $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
    $mail->addAddress('you@yourdomain.com', 'Test User');

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'QueenLib Test Email';
    $mail->Body = '<h1>Test Email</h1><p>This is a test email from QueenLib production server.</p>';

    if ($mail->send()) {
        echo "✅ Email sent successfully!";
    } else {
        echo "❌ Email failed: " . $mail->ErrorInfo;
    }
} catch (Exception $e) {
    echo "❌ Error: {$mail->ErrorInfo}";
}
?>
```

Run test:
```bash
php test-email.php
```

---

## 🔧 Environment Variables

### 1. Create `.env.production` File

```bash
# .env.production (keep this secure, outside webroot)
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_HOST=localhost
DB_PORT=3306
DB_NAME=queenlib_prod
DB_USER=queenlib_user
DB_PASS=your_strong_password_here

MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USER=your-email@gmail.com
MAIL_PASS=xxxx xxxx xxxx xxxx
MAIL_FROM=noreply@yourdomain.com
MAIL_FROM_NAME=QueenLib

SESSION_TIMEOUT=3600
TOKEN_EXPIRY=86400
RESET_TOKEN_EXPIRY=3600

LOG_PATH=/var/log/queenlib/
LOG_LEVEL=warning

RATE_LIMIT_ENABLED=true
RATE_LIMIT_REQUESTS=100
RATE_LIMIT_WINDOW=60
```

### 2. Load Environment Variables in `includes/config.php`

Add at the top of file:

```php
<?php
// Load environment variables
if (file_exists(__DIR__ . '/../.env.production')) {
    $env = parse_ini_file(__DIR__ . '/../.env.production');
    foreach ($env as $key => $value) {
        putenv("$key=$value");
    }
}

// Rest of existing config...
```

### 3. Set File Permissions

```bash
# Make .env.production unreadable by web server
chmod 600 /var/www/queenlib/.env.production

# Owner only can read
sudo chown root:root /var/www/queenlib/.env.production
```

---

## ⚡ Performance Optimization

### 1. Enable Caching

```php
// Add to includes/config.php
define('CACHE_ENABLED', true);
define('CACHE_DRIVER', 'file'); // or 'redis'
define('CACHE_TTL', 3600); // 1 hour
```

### 2. Database Query Optimization

Install Query Cache (MySQL):

```sql
-- Check if query cache is enabled
SHOW VARIABLES LIKE 'query_cache%';

-- Enable in /etc/mysql/mysql.conf.d/mysqld.cnf
query_cache_type = 1
query_cache_size = 64M
query_cache_limit = 2M
```

### 3. Enable Gzip Compression

Add to Apache config or `.htaccess`:

```apache
mod_gzip_on Yes
mod_gzip_dechunk Yes
mod_gzip_item_include file \.(html?|txt|css|js|json|xml)$
mod_gzip_item_include handler ^cgi-script$
mod_gzip_item_exclude mime ^image/
mod_gzip_minimum_file_size 500
mod_gzip_comp_level 6
```

### 4. Optimize PHP-FPM

Edit `/etc/php/8.1/fpm/pool.d/www.conf`:

```ini
; Maximum number of child processes to be created
pm.max_children = 50

; Desired minimum spare server processes
pm.start_servers = 10

; Desired maximum spare server processes
pm.max_spare_servers = 20

; Process idle timeout
pm.process_idle_timeout = 10s

; Maximum requests before restart
pm.max_requests = 1000
```

---

## 📊 Monitoring & Logging

### 1. Setup Application Logging

Create `includes/Logger.php`:

```php
<?php
class Logger {
    private static $logPath;

    public static function init($path) {
        self::$logPath = $path;
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    public static function info($message, $data = []) {
        self::log('INFO', $message, $data);
    }

    public static function warning($message, $data = []) {
        self::log('WARNING', $message, $data);
    }

    public static function error($message, $data = []) {
        self::log('ERROR', $message, $data);
    }

    private static function log($level, $message, $data) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [$level] $message";
        
        if (!empty($data)) {
            $logEntry .= " " . json_encode($data);
        }
        
        $logFile = self::$logPath . date('Y-m-d') . '.log';
        file_put_contents($logFile, $logEntry . "\n", FILE_APPEND);
    }
}
?>
```

### 2. Setup Monitoring Tools

```bash
# Install Nagios/Icinga for monitoring
sudo apt-get install -y nagios3

# Install munin for system stats
sudo apt-get install -y munin munin-node

# Install logrotate for log management
sudo apt-get install -y logrotate
```

### 3. Configure Log Rotation

Create `/etc/logrotate.d/queenlib`:

```
/var/log/queenlib/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
    postrotate
        systemctl reload apache2 >/dev/null 2>&1 || true
    endscript
}
```

### 4. Setup Health Check Endpoint

Create `health-check.php`:

```php
<?php
header('Content-Type: application/json');

$health = [
    'status' => 'healthy',
    'timestamp' => date('c'),
    'checks' => []
];

// Database check
try {
    $db = new PDO(...);
    $health['checks']['database'] = 'ok';
} catch (Exception $e) {
    $health['checks']['database'] = 'failed';
    $health['status'] = 'unhealthy';
}

// Email check (don't actually send, just validate config)
if (defined('MAIL_HOST') && defined('MAIL_USER')) {
    $health['checks']['email_config'] = 'ok';
} else {
    $health['checks']['email_config'] = 'failed';
    $health['status'] = 'unhealthy';
}

// Disk space check
$freeSpace = disk_free_space('/');
$totalSpace = disk_total_space('/');
$percentUsed = (($totalSpace - $freeSpace) / $totalSpace) * 100;

$health['checks']['disk'] = $percentUsed < 90 ? 'ok' : 'low_space';
if ($percentUsed > 90) {
    $health['status'] = 'warning';
}

http_response_code($health['status'] === 'healthy' ? 200 : 503);
echo json_encode($health, JSON_PRETTY_PRINT);
?>
```

---

## 🚀 Deployment Process

### Step 1: Pre-Deployment Tasks

```bash
# 1. Create backup of current production
sudo /usr/local/bin/backup-queenlib.sh

# 2. Clone repository to staging directory
cd /home/deploy
git clone https://github.com/yourusername/queenlib.git queenlib-new

# 3. Run tests in staging
cd queenlib-new
composer install --no-dev
php vendor/bin/phpunit tests/

# 4. Run TestSprite tests
npx testsprite generateCodeAndExecute
```

### Step 2: Database Migration

```bash
# Create backup before migration
mysqldump -u queenlib_user -p queenlib_prod > /backups/pre-deploy-backup.sql

# Run any necessary schema updates
mysql -u queenlib_user -p queenlib_prod < backend/config/schema-migrations.sql
```

### Step 3: Code Deployment

```bash
# Stop Apache
sudo systemctl stop apache2

# Deploy new code
cd /var/www
sudo rm -rf queenlib-old
sudo mv queenlib queenlib-old
sudo mv /home/deploy/queenlib-new queenlib

# Set permissions
sudo chown -R www-data:www-data /var/www/queenlib
sudo chmod -R 755 /var/www/queenlib
sudo chmod -R 644 /var/www/queenlib/*
sudo chmod -R 755 /var/www/queenlib/*/

# Set sensitive file permissions
sudo chmod 600 /var/www/queenlib/.env.production
sudo chmod 600 /var/www/queenlib/.htaccess

# Create required directories
sudo mkdir -p /var/www/queenlib/uploads
sudo chmod 755 /var/www/queenlib/uploads
sudo mkdir -p /var/log/queenlib
sudo chmod 755 /var/log/queenlib

# Start Apache
sudo systemctl start apache2
```

### Step 4: Post-Deployment Verification

```bash
# Test health check endpoint
curl https://yourdomain.com/health-check.php

# Check Apache status
sudo systemctl status apache2

# Verify database connection
mysql -u queenlib_user -p queenlib_prod -e "SELECT COUNT(*) FROM users;"

# Check log files
sudo tail -f /var/log/queenlib/app.log
```

---

## ✅ Post-Deployment Verification

### 1. Functional Testing

```bash
# Test registration
curl -X POST https://yourdomain.com/backend/api/register.php \
  -d "first_name=Test&last_name=User&email=test@example.com&password=Test1234!"

# Test login
curl -X POST https://yourdomain.com/backend/api/login.php \
  -d "email=test@example.com&password=Test1234!"

# Test email verification
# (Check email for verification link)
```

### 2. Security Verification

```bash
# Check SSL certificate
openssl s_client -connect yourdomain.com:443 -showcerts

# Verify security headers
curl -I https://yourdomain.com
# Should show: X-Content-Type-Options, X-Frame-Options, etc.

# Check file permissions
ls -la /var/www/queenlib | head -20

# Verify .env file is not accessible
curl https://yourdomain.com/.env.production
# Should return 403 Forbidden
```

### 3. Performance Verification

```bash
# Test response times
ab -n 100 -c 10 https://yourdomain.com/login.php

# Check database query times
mysql -u queenlib_user -p queenlib_prod -e "SET SESSION SESSION_VARIABLES.sql_mode = ''; SELECT QUERIES;SHOW PROCESSLIST;"

# Monitor system resources
htop
# Or
vmstat 1 10
```

### 4. Monitoring Setup Verification

```bash
# Verify backups are running
ls -lah /backups/queenlib/

# Check log rotation
ls -lah /var/log/queenlib/

# Verify monitoring is active
sudo systemctl status nagios3
```

---

## 🔄 Rollback Procedures

### Quick Rollback (if deployment fails)

```bash
# Step 1: Stop Apache
sudo systemctl stop apache2

# Step 2: Restore previous version
cd /var/www
sudo rm -rf queenlib
sudo mv queenlib-old queenlib

# Step 3: Restore database (if schema changed)
mysql -u queenlib_user -p queenlib_prod < /backups/pre-deploy-backup.sql

# Step 4: Start Apache
sudo systemctl start apache2

# Step 5: Verify
curl https://yourdomain.com/health-check.php
```

### Complete Rollback (from backup)

```bash
# Step 1: Restore from dated backup
BACKUP_DATE="20260327_020000"
tar -xzf /backups/queenlib/app_backup_$BACKUP_DATE.tar.gz -C /var/www

# Step 2: Restore database
zcat /backups/queenlib/db_backup_$BACKUP_DATE.sql.gz | mysql -u queenlib_user -p queenlib_prod

# Step 3: Restart services
sudo systemctl restart apache2 mysql

# Step 4: Notify team
# (Send notification about rollback)
```

---

## 📞 Production Deployment Checklist - Final

### Before Going Live

- [ ] SSL certificate installed and tested
- [ ] Environment variables configured securely
- [ ] Database created and schema imported
- [ ] Email service configured and tested
- [ ] Backups automated and tested
- [ ] Monitoring tools installed and configured
- [ ] Health check endpoint working
- [ ] Security headers configured
- [ ] Rate limiting implemented
- [ ] All tests passing (TestSprite)
- [ ] Performance benchmarks acceptable
- [ ] Staging deployment matches production
- [ ] Rollback plan documented and tested
- [ ] Team trained on deployment procedures
- [ ] Documentation updated

### After Going Live

- [ ] Monitor application logs hourly for first 24h
- [ ] Monitor system resources (CPU, Memory, Disk)
- [ ] Monitor error rates and response times
- [ ] Verify backups are completing successfully
- [ ] Test disaster recovery procedure
- [ ] Send team notification with deployment details
- [ ] Update runbooks and documentation
- [ ] Schedule post-deployment review meeting

---

## 🆘 Troubleshooting Common Issues

### Issue: 502 Bad Gateway

```bash
# Check PHP-FPM status
sudo systemctl status php8.1-fpm

# Restart PHP-FPM
sudo systemctl restart php8.1-fpm

# Check PHP-FPM socket
ls -la /run/php-fpm.sock
```

### Issue: Database Connection Failed

```bash
# Test MySQL connection
mysql -u queenlib_user -p -h localhost queenlib_prod

# Check MySQL status
sudo systemctl status mysql

# Verify credentials in .env.production
sudo cat /var/www/queenlib/.env.production | grep DB_
```

### Issue: Emails Not Sending

```bash
# Test mail configuration
php test-email.php

# Check mail logs
sudo tail -f /var/log/mail.log

# Verify SMTP credentials
telnet smtp.gmail.com 587
```

### Issue: High Memory Usage

```bash
# Check process memory
ps aux --sort=-%mem | head

# Check PHP-FPM config
cat /etc/php/8.1/fpm/pool.d/www.conf | grep memory_limit

# Monitor memory in real-time
free -h -s 5
```

---

**Deployment Status:** Ready After Configuration  
**For Support:** Contact your hosting provider or DevOps team
