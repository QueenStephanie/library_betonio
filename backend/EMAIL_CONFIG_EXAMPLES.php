<?php

/**
 * Email Configuration Examples for Different Providers
 * Copy the example for your email provider to email.config.php
 */

// ============================================
// OPTION 1: Gmail (Recommended)
// ============================================

$config_gmail = [
  'smtp' => [
    'host' => 'smtp.gmail.com',
    'port' => 587,                    // TLS Port
    'username' => 'your-email@gmail.com',
    'password' => 'xxxx xxxx xxxx xxxx',  // 16-char App Password
    'from_email' => 'your-email@gmail.com',
    'from_name' => 'Library Betonio'
  ],
  'otp_validity' => 600,      // 10 minutes
  'max_otp_attempts' => 3,
  'otp_resend_delay' => 60,
  'rate_limit' => [
    'otp_requests' => 3,
    'otp_verifications' => 5
  ],
  'enable_ssl_verification' => true
];

/**
 * SETUP FOR GMAIL:
 * 1. Go to: https://myaccount.google.com/apppasswords
 * 2. Make sure 2-Step Verification is enabled
 * 3. Select "Mail" and "Windows Computer"
 * 4. Google will generate a 16-character password
 * 5. Copy and paste it into 'password' field above
 * 6. Use your full Gmail address (including @gmail.com)
 */

// ============================================
// OPTION 2: Gmail with SSL (Port 465)
// ============================================

$config_gmail_ssl = [
  'smtp' => [
    'host' => 'smtp.gmail.com',
    'port' => 465,                    // SSL Port
    'username' => 'your-email@gmail.com',
    'password' => 'xxxx xxxx xxxx xxxx',
    'from_email' => 'your-email@gmail.com',
    'from_name' => 'Library Betonio'
  ],
  'otp_validity' => 600,
  'max_otp_attempts' => 3,
  'otp_resend_delay' => 60,
  'rate_limit' => [
    'otp_requests' => 3,
    'otp_verifications' => 5
  ],
  'enable_ssl_verification' => true
];

// ============================================
// OPTION 3: Outlook / Hotmail
// ============================================

$config_outlook = [
  'smtp' => [
    'host' => 'smtp.office365.com',
    'port' => 587,                    // TLS
    'username' => 'your-email@outlook.com',
    'password' => 'your-outlook-password',
    'from_email' => 'your-email@outlook.com',
    'from_name' => 'Library Betonio'
  ],
  'otp_validity' => 600,
  'max_otp_attempts' => 3,
  'otp_resend_delay' => 60,
  'rate_limit' => [
    'otp_requests' => 3,
    'otp_verifications' => 5
  ],
  'enable_ssl_verification' => true
];

/**
 * SETUP FOR OUTLOOK:
 * 1. Use your Outlook.com email address
 * 2. Use your Outlook password
 * 3. Some Outlook accounts may need app password instead
 * 4. Enable "Less secure apps" if needed
 */

// ============================================
// OPTION 4: SendGrid
// ============================================

$config_sendgrid = [
  'smtp' => [
    'host' => 'smtp.sendgrid.net',
    'port' => 587,                    // TLS
    'username' => 'apikey',           // Always 'apikey'
    'password' => 'SG.xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',  // Your API Key
    'from_email' => 'noreply@betoniolibrary.com',
    'from_name' => 'Library Betonio'
  ],
  'otp_validity' => 600,
  'max_otp_attempts' => 3,
  'otp_resend_delay' => 60,
  'rate_limit' => [
    'otp_requests' => 3,
    'otp_verifications' => 5
  ],
  'enable_ssl_verification' => true
];

/**
 * SETUP FOR SENDGRID:
 * 1. Create account at: https://sendgrid.com
 * 2. Get API Key from: Settings > API Keys
 * 3. Username is always 'apikey'
 * 4. Password is your full API Key starting with 'SG.'
 * 5. Verify sender email in SendGrid dashboard
 */

// ============================================
// OPTION 5: Mailgun
// ============================================

$config_mailgun = [
  'smtp' => [
    'host' => 'smtp.mailgun.org',
    'port' => 587,                    // TLS
    'username' => 'postmaster@sandboxXXXXX.mailgun.org',
    'password' => 'postal-code-here',
    'from_email' => 'no-reply@sandboxXXXXX.mailgun.org',
    'from_name' => 'Library Betonio'
  ],
  'otp_validity' => 600,
  'max_otp_attempts' => 3,
  'otp_resend_delay' => 60,
  'rate_limit' => [
    'otp_requests' => 3,
    'otp_verifications' => 5
  ],
  'enable_ssl_verification' => true
];

/**
 * SETUP FOR MAILGUN:
 * 1. Create account at: https://www.mailgun.com
 * 2. Get SMTP Credentials from: Domain > SMTP Credentials
 * 3. Username: postmaster@yourSandbox.mailgun.org
 * 4. Password: shown in SMTP Credentials
 * 5. Can use custom domain after verification
 */

// ============================================
// OPTION 6: Amazon SES (Simple Email Service)
// ============================================

$config_amazon_ses = [
  'smtp' => [
    'host' => 'email-smtp.us-east-1.amazonaws.com',  // Change region as needed
    'port' => 587,                    // TLS
    'username' => 'AKIA22XXXXXXXXXXXXXX',  // SMTP username from SES
    'password' => 'xxxxxxxxxxxxxxxx',      // SMTP password from SES
    'from_email' => 'noreply@betoniolibrary.com',
    'from_name' => 'Library Betonio'
  ],
  'otp_validity' => 600,
  'max_otp_attempts' => 3,
  'otp_resend_delay' => 60,
  'rate_limit' => [
    'otp_requests' => 3,
    'otp_verifications' => 5
  ],
  'enable_ssl_verification' => true
];

/**
 * SETUP FOR AMAZON SES:
 * 1. Go to: AWS Console > SES > Email Addresses
 * 2. Verify your sender email
 * 3. Create SMTP Credentials (Security Credentials)
 * 4. Copy SMTP Username and Password
 * 5. Replace region (us-east-1, eu-west-1, etc.)
 * Note: Start in sandbox mode, request production access
 */

// ============================================
// OPTION 7: Local XAMPP Mail (Windows)
// ============================================

$config_local_xampp = [
  'use_sendmail' => true,     // Use local sendmail instead of SMTP
  'from_email' => 'noreply@betoniolibrary.com',
  'from_name' => 'Library Betonio'
];

/**
 * SETUP FOR LOCAL XAMPP:
 * 1. Edit php.ini (in XAMPP PHP folder)
 * 2. Find: [mail function]
 * 3. Set: SMTP = localhost, smtp_port = 25
 * 4. This uses local mail queue (requires installed SMTP)
 * NOT RECOMMENDED for production - use cloud service instead
 */

// ============================================
// OPTION 8: Custom SMTP Server
// ============================================

$config_custom = [
  'smtp' => [
    'host' => 'mail.yourdomain.com',
    'port' => 587,                    // or 465 for TLS
    'username' => 'your-username',
    'password' => 'your-password',
    'from_email' => 'noreply@yourdomain.com',
    'from_name' => 'Library Betonio'
  ],
  'otp_validity' => 600,
  'max_otp_attempts' => 3,
  'otp_resend_delay' => 60,
  'rate_limit' => [
    'otp_requests' => 3,
    'otp_verifications' => 5
  ],
  'enable_ssl_verification' => true
];

// ============================================
// HOW TO USE THESE CONFIGURATIONS
// ============================================

/**
 * Step 1: Choose your email provider from options above
 * 
 * Step 2: Copy the entire $config_xxx array
 * 
 * Step 3: Open file: backend/config/email.config.php
 * 
 * Step 4: Replace the existing return array with your config:
 *         return $config_xxx;
 * 
 * Step 5: Fill in your credentials:
 *         - 'username' => your email/username
 *         - 'password' => your password/API key
 *         - 'from_email' => verified sender email
 * 
 * Step 6: Test with: backend/config/init-db.php
 *         The page will show if email sending works
 */

// ============================================
// TROUBLESHOOTING
// ============================================

/**
 * Email Not Sending?
 * 1. Verify SMTP credentials are correct
 * 2. Check if sender email is verified in service
 * 3. Ensure firewall allows outbound SMTP (port 587/465)
 * 4. Check PHP error logs
 * 5. Test with simple mail() function first
 * 6. Enable PHP debugging: ini_set('display_errors', 1);
 * 
 * Connection Timeout?
 * 1. Check SMTP host spelling
 * 2. Verify correct port number
 * 3. Ensure TLS/SSL matches port:
 *    - Port 587 uses TLS
 *    - Port 465 uses SSL
 * 4. Check firewall rules
 * 5. Try different port if available
 * 
 * Authentication Error?
 * 1. Verify username/password
 * 2. For Gmail: Use 16-char App Password, NOT account password
 * 3. Check if account is locked or requires verification
 * 4. For services: Verify API key format
 * 5. Check if credentials recently changed
 */

// ============================================
// PRODUCTION RECOMMENDATIONS
// ============================================

/**
 * BEST PRACTICES:
 * 
 * 1. USE CLOUD SERVICE
 *    - Never use local SMTP
 *    - Services have better deliverability
 *    - Recommended: SendGrid, Mailgun, or AWS SES
 * 
 * 2. VERIFY SENDER EMAIL
 *    - All services require sender verification
 *    - Don't skip this step
 *    - Users won't see your emails without it
 * 
 * 3. USE APP PASSWORD, NOT ACCOUNT PASSWORD
 *    - For Gmail: Generate App Passwords
 *    - For Outlook: Consider app passwords
 *    - For services: Use generated API keys
 * 
 * 4. MONITOR DELIVERABILITY
 *    - Check bounce rates
 *    - Monitor SPAM folder issues
 *    - Track email delivery in service dashboard
 * 
 * 5. SET UP SPF/DKIM/DMARC
 *    - Add DNS records for your domain
 *    - Improves email authentication
 *    - Reduces SPAM folder placement
 * 
 * 6. USE ENVIRONMENT VARIABLES
 *    - Store credentials in .env file
 *    - Never commit passwords to git
 *    - Use getenv() to read credentials
 */

// ============================================
// TEMPLATE: ENVIRONMENT VARIABLE SETUP
// ============================================

/**
 * Create .env file in backend root:
 * 
 * SMTP_HOST=smtp.gmail.com
 * SMTP_PORT=587
 * SMTP_USER=your-email@gmail.com
 * SMTP_PASS=xxxx xxxx xxxx xxxx
 * FROM_EMAIL=your-email@gmail.com
 * FROM_NAME=Library Betonio
 * 
 * Then modify email.config.php:
 * 
 * return [
 *     'smtp' => [
 *         'host' => getenv('SMTP_HOST'),
 *         'port' => (int)getenv('SMTP_PORT'),
 *         'username' => getenv('SMTP_USER'),
 *         'password' => getenv('SMTP_PASS'),
 *         'from_email' => getenv('FROM_EMAIL'),
 *         'from_name' => getenv('FROM_NAME')
 *     ]
 * ];
 */

echo "Email Configuration Examples - Complete Reference\n";
echo "See inline comments for setup instructions\n";
