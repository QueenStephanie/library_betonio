# QueenLib Documentation Index

Welcome to the QueenLib Library Management System documentation. All documentation has been consolidated into this single folder for easy access and maintenance.

## 📚 Documentation Files

### Getting Started
- **[QUICK_START.md](./QUICK_START.md)** - Quick start guide for new developers
- **[README.md](../README.md)** - Main project overview (in root)

### Core Documentation
- **[DOCUMENTATION.md](./DOCUMENTATION.md)** - Comprehensive system documentation
- **[BACKEND.md](./BACKEND.md)** - Backend architecture and API documentation

### Admin & Authentication
- **[SWEETALERT2_INTEGRATION.md](./SWEETALERT2_INTEGRATION.md)** - SweetAlert2 integration guide
- **[SWEETALERT2_QUICK_REFERENCE.md](./SWEETALERT2_QUICK_REFERENCE.md)** - Quick reference for SweetAlert2 usage
- **[SWEETALERT2_SUMMARY.md](./SWEETALERT2_SUMMARY.md)** - Visual implementation overview

### Deployment & Testing
- **[PRD_TESTING.md](./PRD_TESTING.md)** - Product requirement document and testing procedures
- **[PRODUCTION_DEPLOYMENT.md](./PRODUCTION_DEPLOYMENT.md)** - Production deployment guide
- **[DEPLOYMENT_CHECKLIST.md](./DEPLOYMENT_CHECKLIST.md)** - Pre-deployment checklist
- **[INFINITYFREE_DEPLOYMENT.md](./INFINITYFREE_DEPLOYMENT.md)** - Step-by-step InfinityFree deployment

## 🗂️ Project Structure

```
library_betonio/
├── docs/                    # 📖 All documentation (this folder)
├── public/                  # Frontend assets
│   ├── css/                # Stylesheets
│   └── js/                 # JavaScript files
├── includes/               # PHP includes
│   ├── config.php         # Configuration
│   ├── functions.php      # Utility functions
│   └── auth.php           # Authentication logic
├── backend/               # Backend API & services
│   ├── api/              # API endpoints
│   ├── classes/          # PHP classes
│   ├── config/           # Backend configuration
│   └── mail/             # Email services
├── images/               # Application images
├── _legacy/              # Legacy/archived files
├── admin-login.php       # Admin login page
├── admin-dashboard.php   # Admin dashboard
├── admin-profile.php     # Admin profile management
├── index.php            # User dashboard
├── login.php            # User login
├── register.php         # User registration
├── account.php          # Account settings
├── logout.php           # Logout handler
└── .env.production.example  # Environment configuration template
```

## 🚀 Quick Navigation

### For New Developers
1. Start with [QUICK_START.md](./QUICK_START.md)
2. Read [DOCUMENTATION.md](./DOCUMENTATION.md) for system overview
3. Check [BACKEND.md](./BACKEND.md) for API details

### For Admin Features
- Admin Login: `admin-login.php`
- Admin Dashboard: `admin-dashboard.php`
- Admin Profile: `admin-profile.php`
- Alert Handling: [SWEETALERT2_INTEGRATION.md](./SWEETALERT2_INTEGRATION.md)

### For Deployment
1. Read [PRODUCTION_DEPLOYMENT.md](./PRODUCTION_DEPLOYMENT.md)
2. Use [DEPLOYMENT_CHECKLIST.md](./DEPLOYMENT_CHECKLIST.md)
3. Follow [PRD_TESTING.md](./PRD_TESTING.md) for testing

## 📋 Key Features

- **Admin Authentication** - Secure admin login with session management
- **User Management** - Complete user registration, login, and account management
- **Library Management** - Book catalog and lending system
- **Email Notifications** - OTP and password reset via email
- **Responsive Design** - Mobile-friendly interface with Cormorant Garamond + Outfit fonts
- **Modern Alerts** - SweetAlert2 integration for beautiful notifications

## 🔧 Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL/MariaDB
- **Frontend**: HTML5, CSS3, JavaScript (vanilla)
- **Alerts**: SweetAlert2 v11
- **Email**: PHPMailer

## 📞 Support

For issues or questions, refer to the relevant documentation file or contact the development team.

---

**Last Updated**: March 28, 2026  
**Version**: 1.0
