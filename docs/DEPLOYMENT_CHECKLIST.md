# QueenLib Production Deployment Checklist
## Quick Reference Guide for Going Live

---

## 🚀 QUICK START - InfinityFree (5 minutes)

### Already done ✅:
- [x] Code is host-ready (env-based config)
- [x] Schema file ready
- [x] Security files in place

### Do these NOW:

**1. Get InfinityFree credentials:**
- MySQL Host: `sql000.infinityfree.com`
- Database Name: `if0_XXXXX_queenlib`  
- Username: `if0_XXXXX`
- Password: your MySQL password

**2. Edit `.env.production` with your values**

**3. Upload to htdocs**

**4. Import `backend/config/schema.sql` in phpMyAdmin**

**5. Test!**

---

## 📋 Pre-Deployment Phase (1-2 weeks before)

### Domain & Hosting Setup
- [ ] Domain registered and DNS configured
- [ ] Hosting provider selected (supports PHP 8.0+, MySQL 5.7+)
- [ ] SSH access configured
- [ ] SFTP/Git access configured
- [ ] Server environment matches requirements

### SSL/HTTPS Preparation
- [ ] SSL certificate obtained (Let's Encrypt or commercial)
- [ ] Certificate files downloaded and stored securely
- [ ] Auto-renewal setup configured
- [ ] Certificate validity verified (at least 1 year)
- [ ] Mixed content issues identified and resolved

### Email Service Setup
- [ ] Email service account created (Gmail, SendGrid, or Mailgun)
- [ ] SMTP credentials generated
- [ ] SPF record added to domain DNS
- [ ] DKIM configured
- [ ] DMARC policy configured
- [ ] Test email delivered successfully

### Database Preparation
- [ ] Production database server accessible
- [ ] Database user created with minimal permissions
- [ ] Strong database password generated (30+ chars)
- [ ] Database character encoding set to UTF8MB4
- [ ] Backup solution identified and tested

### Security Audit
- [ ] Code reviewed for security issues
- [ ] Sensitive data identified and moved to environment variables
- [ ] File permissions defaults reviewed
- [ ] Apache/.htaccess security rules prepared
- [ ] Database credentials moved out of code

### Backup & Recovery Strategy
- [ ] Backup location selected (local, remote, or S3)
- [ ] Backup script prepared and tested
- [ ] Recovery procedures documented
- [ ] Test restore from backup completed
- [ ] Rollback plan documented

---

## 🔧 Configuration Phase (1 week before)

### Environment Configuration
- [ ] `.env.production` file created from `.env.production.example`
- [ ] All required environment variables filled in
- [ ] Sensitive values stored securely
- [ ] File permissions set to 600 (owner read/write only)
- [ ] Database connection tested

### Server Configuration
- [ ] PHP version 8.0+ confirmed
- [ ] PHP extensions installed (mysql, mbstring, bcmath, curl, json, zip)
- [ ] Apache modules enabled (rewrite, headers, ssl)
- [ ] Virtual host configured
- [ ] .htaccess security rules in place
- [ ] Error logging configured outside webroot
- [ ] Access logging enabled

### Database Schema
- [ ] Production database created
- [ ] Schema imported successfully
- [ ] Indexes verified and optimized
- [ ] Test queries executed and timed
- [ ] Character encoding verified as UTF8MB4
- [ ] Sample users created for testing

### Email Testing
- [ ] SMTP connection tested
- [ ] Test email sent from registration form
- [ ] Test email sent from password reset
- [ ] Email verification token validated
- [ ] Bounce/reply addresses configured

### Application Preparation
- [ ] All dependencies installed (composer install --no-dev)
- [ ] Cache directories created and writable
- [ ] Upload directories created with proper permissions
- [ ] Log directory created with proper permissions
- [ ] Session directory created with proper permissions

---

## 🧪 Testing Phase (3 days before)

### Automated Testing
- [ ] All TestSprite tests pass (Phase 1 & Phase 2)
- [ ] Test results exported and documented
- [ ] Performance benchmarks reviewed
- [ ] Cross-browser compatibility verified
- [ ] Mobile responsiveness tested at 375px, 768px, 1920px

### Manual Testing in Staging
- [ ] User registration works end-to-end
- [ ] Email verification tokens functional
- [ ] Login/logout workflows functional
- [ ] Password reset flow works
- [ ] Account settings updates functional
- [ ] Session timeout works correctly
- [ ] Forms display and validate correctly
- [ ] Error messages display appropriately

### Security Testing
- [ ] SQL injection attempts blocked
- [ ] XSS attempts blocked
- [ ] CSRF tokens validated
- [ ] Direct URL access restrictions enforced
- [ ] Unauthorized access attempts blocked
- [ ] Rate limiting functional (if enabled)
- [ ] HTTPOnly cookies verified
- [ ] HTTPS redirect working

### Performance Testing
- [ ] Homepage loads in < 2 seconds
- [ ] Database queries average < 100ms
- [ ] Registration completes in < 500ms
- [ ] Login completes in < 500ms
- [ ] No N+1 query problems identified
- [ ] Memory usage within limits

### Backup Testing
- [ ] Backup script executes successfully
- [ ] Database backup file created
- [ ] Application backup files created
- [ ] Restore from backup tested
- [ ] Data integrity verified after restore

---

## 📅 Deployment Day Preparation (1 day before)

### Team Communication
- [ ] Deployment window announced to team
- [ ] Stakeholders notified of timing
- [ ] Support team briefed on changes
- [ ] Rollback contacts identified
- [ ] Emergency procedures reviewed

### Final Pre-Deployment Checks
- [ ] All tests passing
- [ ] Code review completed
- [ ] Security audit completed
- [ ] Performance benchmarks acceptable
- [ ] Database backups current
- [ ] Application backups current

### Deployment Runbook Prepared
- [ ] Deployment steps documented
- [ ] Rollback procedures documented
- [ ] Post-deployment verification steps listed
- [ ] Monitoring procedures documented
- [ ] Emergency contact numbers compiled

### Monitoring Setup
- [ ] Error tracking configured (Sentry)
- [ ] Health check endpoint created
- [ ] Log aggregation configured
- [ ] Alert thresholds set
- [ ] Dashboard created for monitoring

---

## 🚀 Deployment Day Execution

### Pre-Deployment (1 hour before)

- [ ] Final code commit confirmed
- [ ] All team members online
- [ ] Monitoring tools verified as active
- [ ] Backup created
- [ ] Maintenance window window announced (if needed)

### Deployment Execution (use deploy.sh)

```bash
sudo chmod +x deploy.sh rollback.sh
sudo ./deploy.sh
```

Important steps executed:
- [ ] Prerequisites checked
- [ ] Current system backed up
- [ ] Tests executed
- [ ] Git status verified
- [ ] Apache stopped
- [ ] Code deployed
- [ ] Dependencies installed
- [ ] Migrations executed
- [ ] Apache started
- [ ] Health checks passed

### Immediate Post-Deployment (30 minutes after)

- [ ] Health check endpoint returns 200
- [ ] Application homepage loads
- [ ] Registration form accessible
- [ ] Email sending working
- [ ] Database connection verified
- [ ] No critical errors in logs
- [ ] Error tracking configured and receiving errors
- [ ] Monitoring dashboard showing data

### Short-term Monitoring (2-4 hours after)

- [ ] Monitor error rates (target: < 0.1%)
- [ ] Monitor response times (target: < 200ms avg)
- [ ] Monitor database performance
- [ ] Monitor server resources (CPU, memory, disk)
- [ ] Check for spike in failed registrations
- [ ] Review user feedback channels

### Medium-term Monitoring (24 hours after)

- [ ] Monitor error trends
- [ ] Review user login success rates
- [ ] Check email delivery rates
- [ ] Verify backup completed successfully
- [ ] Review security logs
- [ ] Collect user feedback
- [ ] Document any issues encountered

---

## ✅ Post-Deployment Verification

### Functional Verification

**Registration Flow:**
```bash
curl -X POST https://yourdomain.com/backend/api/register.php \
  -d "first_name=Test&last_name=User&email=test@example.com&password=TestPass123"
# Expected: Success response with verification email sent
```

**Login Flow:**
```bash
curl -X POST https://yourdomain.com/backend/api/login.php \
  -d "email=test@example.com&password=TestPass123"
# Expected: Success response with session cookie
```

**Health Check:**
```bash
curl https://yourdomain.com/health-check.php
# Expected: 200 OK with health status JSON
```

### Security Verification

- [ ] HTTPS enforced (HTTP redirects to HTTPS)
- [ ] Security headers present:
  - [ ] X-Content-Type-Options: nosniff
  - [ ] X-Frame-Options: DENY
  - [ ] X-XSS-Protection: 1; mode=block
  - [ ] Strict-Transport-Security
- [ ] .env.production not accessible
- [ ] Source code not accessible
- [ ] Error details not exposed to users

### Performance Verification

```bash
# Test response times
ab -n 100 -c 10 https://yourdomain.com/login.php

# Expected: 
# - Mean time per request < 200ms
# - Transfer rate > 100 KB/sec
# - Failed requests: 0
```

### Database Verification

```bash
# Connect and verify tables
mysql -u queenlib_user -p queenlib_prod -e "SHOW TABLES; SELECT COUNT(*) as users FROM users;"
```

---

## 🔍 What to Monitor

### Critical Metrics

| Metric | Target | Alert Threshold |
|--------|--------|-----------------|
| Application Uptime | 99.9% | < 99.5% |
| API Response Time | < 200ms | > 500ms |
| Error Rate | < 0.1% | > 1% |
| Database Queries | < 100ms | > 500ms |
| Server CPU | 40-60% | > 80% |
| Server Memory | 50-70% | > 90% |
| Disk Space | < 70% used | > 85% used |
| Failed Logins | < 2% | > 5% |
| Registration Success | > 95% | < 90% |
| Email Delivery | > 99% | < 95% |

### Daily Monitoring Tasks

- [ ] Check error logs for critical issues
- [ ] Verify backups completed successfully
- [ ] Monitor performance metrics
- [ ] Review failed login attempts
- [ ] Check email delivery rates
- [ ] Monitor disk space usage
- [ ] Verify SSL certificate valid

### Weekly Reviews

- [ ] Review error trends
- [ ] Analyze performance trends
- [ ] Check security audit logs
- [ ] Test rollback procedure
- [ ] Review resource utilization
- [ ] Check backup integrity

---

## 🆘 Troubleshooting Guide

### Issue: 502 Bad Gateway

```bash
# Check PHP-FPM status
sudo systemctl status php8.1-fpm

# Restart PHP-FPM
sudo systemctl restart php8.1-fpm

# If still failing, rollback:
sudo ./rollback.sh <timestamp>
```

### Issue: Database Connection Failed

```bash
# Test connection
mysql -u queenlib_user -p -h localhost queenlib_prod

# Check MySQL status
sudo systemctl status mysql

# Verify credentials in .env.production
cat /var/www/queenlib/.env.production | grep DB_
```

### Issue: Emails Not Sending

```bash
# Test SMTP
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

# Check PHP-FPM pool config
cat /etc/php/8.1/fpm/pool.d/www.conf | grep -E "memory_limit|max_children"
```

---

## 📞 Contact & Escalation

### Support Contacts

- **DevOps Lead:** [Name/Phone/Email]
- **Database Admin:** [Name/Phone/Email]
- **Security Team:** [Name/Phone/Email]
- **Hosting Provider Support:** [Phone/Email]

### Escalation Path

1. **Level 1 (15 min):** Check logs, review error tracking
2. **Level 2 (30 min):** Review deploy.sh execution, check system resources
3. **Level 3 (45 min):** Execute rollback, investigate root cause
4. **Level 4 (60 min):** Post-mortem meeting, determine next steps

---

## 📊 Success Criteria

Deployment is considered successful when ALL of the following are true:

- ✅ No HTTP 5xx errors in application logs
- ✅ Health check endpoint returns 200 OK
- ✅ Database connection verified
- ✅ Email verification working
- ✅ Login/logout workflows functional
- ✅ Response times < 200ms (95th percentile)
- ✅ SSL certificate valid (HTTPS working)
- ✅ Backups completed successfully
- ✅ All security headers present
- ✅ Error tracking receiving events
- ✅ Monitoring dashboard populated with data
- ✅ No unusual resource consumption

---

## 🎯 Post-Deployment (3 days after)

- [ ] Application running stably for 72 hours
- [ ] Error rates normalized
- [ ] User feedback reviewed
- [ ] Performance metrics reviewed
- [ ] Deployment retrospective completed
- [ ] Documentation updated
- [ ] Team debriefing held
- [ ] Close change request

---

**Last Updated:** March 27, 2026  
**Version:** 1.0  
**Status:** Ready for Production  

For questions or updates, refer to:
- PRODUCTION_DEPLOYMENT.md (detailed guide)
- deploy.sh (automated deployment)
- rollback.sh (automated rollback)
