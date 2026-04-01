# QueenLib - Library Management System

A professional PHP-based library management system with secure authentication, email verification, and account management.

## 🚀 Quick Start (Local)

1. **Setup environment file**

   - Copy `.env.production.example` to `.env`
   - Set local values (`DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`, `APP_URL`)

2. **Setup Database:**

   ```bash
   php backend/setup-db.php
   ```

3. **Configure Email** (`.env`):

   ```env
   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=587
   MAIL_USER=your-email@gmail.com
   MAIL_PASS=your-app-password
   MAIL_FROM=your-email@gmail.com
   MAIL_FROM_NAME=QueenLib
   ```

4. **Access Application:**
   - Visit: `http://localhost/library_betonio/`
   - Register: `http://localhost/library_betonio/register.php`
   - Login: `http://localhost/library_betonio/login.php`

## 🌐 InfinityFree Hosting (Production)

1. Upload project files to `htdocs` (or a subfolder inside `htdocs`)
2. Create MySQL database from InfinityFree control panel
3. Import `backend/config/schema.sql` using phpMyAdmin
4. Create `.env.production` in project root (same level as `index.php`)
5. Set production values from your InfinityFree panel:
   - `APP_URL=https://yourdomain.epizy.com`
   - `APP_BASE_PATH=` (or `/subfolder` if not at root)
   - `DB_HOST=sql###.infinityfree.com`
   - `DB_NAME=if0_xxxxxxxx_dbname`
   - `DB_USER=if0_xxxxxxxx`
   - `DB_PASS=your_mysql_password`
6. Update admin credentials in `.env.production`:
   - `ADMIN_USERNAME=...`
   - `ADMIN_PASSWORD=...`
7. Configure SMTP in `.env.production` (Gmail app password or provider SMTP)
8. Make sure `.env.production` is not publicly accessible (already protected by `.htaccess`)
9. Open your deployed URL and test register/login/reset flows

## 📚 Documentation

**All documentation has been consolidated into the `docs/` folder**

Start with: [`docs/INDEX.md`](docs/INDEX.md) - Documentation index and quick navigation

Key documentation files:
- [`docs/QUICK_START.md`](docs/QUICK_START.md) - Quick start guide
- [`docs/DOCUMENTATION.md`](docs/DOCUMENTATION.md) - Comprehensive system documentation
- [`docs/BACKEND.md`](docs/BACKEND.md) - Backend architecture and API documentation
- [`docs/PRODUCTION_DEPLOYMENT.md`](docs/PRODUCTION_DEPLOYMENT.md) - Deployment guide
- [`docs/SWEETALERT2_INTEGRATION.md`](docs/SWEETALERT2_INTEGRATION.md) - Alert system guide

## 🎯 Features

- ✓ User Registration with Email Verification
- ✓ OTP-based Email Verification (6-digit, 10-minute expiry)
- ✓ Secure Password Hashing (Bcrypt)
- ✓ Session-based Authentication
- ✓ Password Reset Functionality
- ✓ User Dashboard with Account Management
- ✓ Email Notifications via Gmail SMTP
- ✓ Rate Limiting on Verification Attempts
- ✓ Security Audit Trail (Login History)
- ✓ Responsive Design

## 🛠️ Tech Stack

- **Backend:** PHP 8.0+
- **Database:** MySQL 5.7+
- **Email:** PHPMailer 7.0 (Gmail SMTP)
- **Frontend:** HTML5, CSS3, JavaScript
- **Security:** Bcrypt, HttpOnly Cookies, SameSite=Strict

## 📁 Project Structure

```
library_betonio/
├── Core Authentication
│   ├── index.php              # Dashboard
│   ├── login.php              # Login
│   ├── register.php           # Registration
│   ├── verify-otp.php         # OTP Verification
│   ├── forgot-password.php    # Password Reset Request
│   ├── reset-password.php     # Password Reset Form
│   └── logout.php             # Logout
│
├── Backend
│   ├── includes/              # Core logic (auth, config, functions)
│   ├── backend/config/        # Database & email configuration
│   ├── backend/api/           # JSON API endpoints
│   ├── backend/classes/       # Auth & Email classes
│   └── backend/vendor/        # PHPMailer library
│
├── Frontend
│   ├── public/css/            # Styling (main, auth, dashboard)
│   └── public/js/             # JavaScript functionality
│
└── Documentation
    ├── DOCUMENTATION.md       # Complete documentation ✓
    └── CLEANUP_GUIDE.md       # Files cleanup guide
```

For detailed structure and file descriptions, see: [`DOCUMENTATION.md`](DOCUMENTATION.md)

## 🔐 Authentication Flow

```
Registration
    ↓
OTP Email Sent
    ↓
User Clicks Verification Link / Enters Code
    ↓
Account Verified (is_verified = 1)
    ↓
User Logs In
    ↓
Dashboard Access ✓
```

## 📞 Support

- **Setup Issues?** See Installation section in [`DOCUMENTATION.md`](DOCUMENTATION.md)
- **Feature Not Working?** Check Troubleshooting section in [`DOCUMENTATION.md`](DOCUMENTATION.md)
- **Need API Reference?** API Endpoints section in [`DOCUMENTATION.md`](DOCUMENTATION.md)

## ⚡ Common Commands

```bash
# Initialize database
php backend/setup-db.php

# Install dependencies
cd backend && composer install

# Check database connection
php -r "require 'includes/config.php'; echo 'Connected!'"
```

## ✅ Cleanup

Old debug and test files can be removed. See [`CLEANUP_GUIDE.md`](CLEANUP_GUIDE.md) for details.

## 📄 License

Educational purposes - modify as needed.

---

**Latest Version:** March 27, 2026

````

## 🔧 Backend Integration Guide

### 1. API Configuration

Edit `config/api.config.js` to set your backend API base URL:

```javascript
const API_CONFIG = {
  baseURL: "http://your-backend-api.com/api",
  endpoints: {
    auth: {
      /* ... */
    },
    users: {
      /* ... */
    },
    books: {
      /* ... */
    },
  },
};
````

### 2. Data Binding with HTML Elements

Elements with `data-bind` attributes will be automatically populated:

```html
<!-- In dashboard -->
<p data-bind="user.email">user@example.com</p>
<strong data-bind="stats.currently_borrowed">3</strong>
```

Use the `updateDashboardData()` function from JavaScript:

```javascript
const userData = {
  user: {
    email: "john@example.com",
    first_name: "John",
  },
  stats: {
    currently_borrowed: 5,
  },
};

updateDashboardData(userData);
```

### 3. Form Integration

Each form has `data-form-type` and `data-api-endpoint` attributes for easy processing:

```html
<form
  id="loginForm"
  data-form-type="login"
  data-api-endpoint="/auth/login"
  action="pages/dashboard/account.html"
  method="post"
></form>
```

To handle forms via API instead of form action:

1. Add `data-submit-handler="api"` to form
2. Listen to form submission in your JavaScript
3. Use `apiCall()` helper from `api.config.js`:

```javascript
const loginForm = document.getElementById("loginForm");
loginForm.addEventListener("submit", async (e) => {
  e.preventDefault();

  const formData = new FormData(loginForm);
  const data = Object.fromEntries(formData);

  try {
    const response = await apiCall(API_CONFIG.endpoints.auth.login, {
      method: "POST",
      body: JSON.stringify(data),
    });
    // Handle successful login
    window.location.href = "pages/dashboard/account.html";
  } catch (error) {
    // Handle error
    console.error("Login failed:", error);
  }
});
```

### 4. Authentication Flow

**Login Flow:**

1. User enters email & password on `/pages/auth/login.html`
2. Submit to `/api/auth/login`
3. On success, store user token & navigate to dashboard

**Registration Flow:**

1. User fills form on `/pages/auth/register.html`
2. Submit to `/api/auth/register`
3. Redirect to `/pages/auth/check-email.html`
4. User navigates to `/pages/auth/verify-email.html`
5. Submit OTP to `/api/auth/verify-email`
6. On success, redirect to `/pages/auth/login.html` for login

**Forgot Password Flow:**

1. User enters email on `/pages/auth/forgot-password.html`
2. Submit to `/api/auth/forgot-password`
3. Redirect to `/pages/auth/reset-sent.html`
4. User receives email with reset link
5. Link redirects to reset password page (not included in current UI)

### 5. Session Management

Store and retrieve user data from session:

```javascript
// After successful login
const userData = {
  id: 123,
  email: "user@example.com",
  first_name: "John",
  greeting: "Hi, John",
};

setUserSession(userData); // Stores in sessionStorage

// Later, retrieve user data
const user = getUserSession(); // Returns user object

// On logout
clearUserSession(); // Removes user data
```

### 6. Dashboard Data Loading

**Load dashboard stats and content:**

```javascript
async function loadDashboard() {
  try {
    // Fetch profile
    const profile = await apiCall(API_CONFIG.endpoints.users.getProfile);
    setUserSession(profile);

    // Fetch active loans
    const loans = await apiCall(API_CONFIG.endpoints.users.getLoans);
    populateLoans(loans);

    // Fetch loan history
    const history = await apiCall("/api/users/loans/history");
    populateHistory(history);

    // Fetch reservations
    const reservations = await apiCall(
      API_CONFIG.endpoints.users.getReservations,
    );
    populateReservations(reservations);
  } catch (error) {
    console.error("Failed to load dashboard:", error);
  }
}

// Helper function to populate loans table
function populateLoans(loans) {
  const tbody = document.getElementById("activeLoansBody");
  tbody.innerHTML = loans
    .map(
      (loan) => `
    <tr data-loan-id="${loan.id}">
      <td class="book-title">${loan.title}</td>
      <td>${formatDate(loan.checkout_date)}</td>
      <td>${formatDate(loan.due_date)}</td>
      <td>
        <span class="badge ${getStatusClass(loan.status)}">
          ${loan.status}
        </span>
      </td>
    </tr>
  `,
    )
    .join("");
}
```

### 7. Form Validation

Use `data-validation` attributes for client-side hints:

```html
<input type="email" name="email" data-validation="email" required />

<input
  type="password"
  name="password"
  data-validation="password"
  minlength="8"
  required
/>

<input
  type="password"
  name="confirm_password"
  data-validation="password-match"
  data-match-field="password"
  required
/>
```

### 8. OTP/Verification Code Handling

For email verification, the OTP boxes are auto-combined:

```javascript
// On verify-email.html form submit, this combines OTP:
const otpBoxes = document.querySelectorAll(".otp-box");
const code = Array.from(otpBoxes)
  .map((box) => box.value)
  .join("");
// Sends code as 'verification_code' field in form
```

## 🎯 Key Data Attributes

| Attribute           | Location    | Purpose                                      |
| ------------------- | ----------- | -------------------------------------------- |
| `data-form-type`    | Form        | Identifies form type (login, register, etc.) |
| `data-api-endpoint` | Form        | Backend API endpoint to POST to              |
| `data-bind`         | Any element | Path to bind data using dot notation         |
| `data-validation`   | Input       | Validation type hint                         |
| `data-stat-type`    | Stat card   | Type of statistic (borrowed, read, etc.)     |
| `data-loan-id`      | Table row   | Unique loan identifier                       |
| `data-collapsible`  | Section     | Marks section as collapsible                 |

## 🚀 Required API Endpoints

```
POST   /api/auth/login                      - User login
POST   /api/auth/register                   - User registration
POST   /api/auth/verify-email               - Verify email with code
POST   /api/auth/forgot-password            - Request password reset
GET    /api/users/profile                   - Get current user profile
POST   /api/users/profile                   - Update user profile
GET    /api/users/loans                     - Get active loans
GET    /api/users/loans/history             - Get loan history
GET    /api/users/reservations              - Get pending reservations
POST   /api/users/reservations/:id/cancel   - Cancel a reservation
```

## 📝 Notes for Backend Developers

1. **User Authentication**: Implement JWT or session-based auth. Store token in a secure HTTP-only cookie or localStorage.

2. **Email Verification**: After registration, send verification email with 6-digit code. Return this code when user accesses check-email page.

3. **Password Reset**: After forgot-password request, send reset email with a temporary token. Link should redirect to reset page (currently not implemented in UI).

4. **CORS**: Ensure your API allows requests from the frontend domain.

5. **Error Handling**: Return appropriate HTTP status codes and error messages in JSON format:

   ```json
   {
     "success": false,
     "error": "Email already exists",
     "code": "EMAIL_EXISTS"
   }
   ```

6. **Response Format**: Keep consistent JSON response structure:

   ```json
   {
     "success": true,
     "data": {
       /* actual data */
     },
     "message": "Operation successful"
   }
   ```

7. **Redirect After Login**: Frontend redirects to `pages/dashboard/account.html` after successful login. Ensure user profile data is available immediately.

## 🔐 Security Considerations

- Store sensitive data (tokens) in secure, HTTP-only cookies
- Validate all input on backend
- Use HTTPS in production
- Implement CSRF protection if using session-based auth
- Rate limit login/registration endpoints
- Use proper password hashing (bcrypt, argon2, etc.)
- Implement email verification before account activation

## 📱 Responsive Design

All pages use responsive CSS with breakpoints at:

- max-width: 1180px
- max-width: 920px
- max-width: 1024px
- max-width: 640px

Sidebar becomes horizontal navigation on mobile devices.

## 🎨 UI Components

- **Auth Card**: Compact, centered form containers
- **Stat Cards**: Display key metrics with icons
- **Panels**: Content sections with optional expand/collapse
- **Tables**: Responsive tables for data display
- **Badges**: Status indicators (success, warning, neutral, outline)
- **Buttons**: Primary (submit) and ghost (secondary) styles

---

**For detailed HTML structure inspection, refer to individual page files in `pages/` directory.**
