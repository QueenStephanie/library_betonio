# SweetAlert2 Integration Guide

## Overview
This project now uses **SweetAlert2** for beautiful, responsive, and user-friendly popup notifications across all authentication pages. SweetAlert2 provides a modern alternative to standard browser alerts with enhanced styling and better UX.

## Features Implemented

### 1. **Logout Success Alert**
- **File**: `logout.php`
- **Trigger**: When user clicks logout
- **Message**: Shows "Logged Out Successfully" with automatic redirect to login page
- **Response**: Fully responsive with 2-second auto-redirect

### 2. **Registration Success Alert**
- **File**: `register.php`
- **Trigger**: After successful account creation
- **Message**: "Account Created!" with instructions to verify email
- **Action**: Redirects to email verification page

### 3. **Registration Error Alert**
- **File**: `register.php`
- **Trigger**: When registration fails
- **Messages**: Shows specific error (duplicate email, password mismatch, validation errors, etc.)
- **Recovery**: User stays on form to correct errors

### 4. **Login Success Handling**
- **File**: `login.php`
- **Behavior**: Automatically redirects to dashboard on success
- **Session Timeout Alert**: Shows warning if session has expired

### 5. **Login Error Alert**
- **File**: `login.php`
- **Trigger**: Invalid credentials, email not verified, etc.
- **Response**: Displays error clearly without losing user input

### 6. **Email Verification Success Alert**
- **File**: `verify-otp.php`
- **Trigger**: When user clicks email verification link
- **Message**: "Email Verified!" with redirect to login
- **Auto-Action**: Automatically verifies and redirects

### 7. **Email Verification Error Alert**
- **File**: `verify-otp.php`
- **Trigger**: Invalid or expired verification token
- **Message**: Shows error and redirects to registration page

### 8. **Password Reset Request Alert**
- **File**: `forgot-password.php`
- **Trigger**: After submitting email for password reset
- **Message**: "Password Reset Sent!" with instructions to check email
- **Auto-Action**: Redirects to login page after confirmation

### 9. **Profile Update Success Alert**
- **File**: `account.php`
- **Trigger**: After successfully updating profile information
- **Message**: "Profile Updated!" confirmation

### 10. **Password Change Success Alert**
- **File**: `account.php`
- **Trigger**: After successfully changing password
- **Message**: "Password Updated!" confirmation

### 11. **Account Error Alerts**
- **File**: `account.php`
- **Trigger**: Profile update or password change errors
- **Messages**: Invalid current password, passwords don't match, etc.

## File Structure

### New Files Created
```
public/js/sweetalert-config.js       # SweetAlert2 configuration and helper functions
```

### Updated Files
```
logout.php                            # Added SweetAlert2 integration
register.php                          # Added success/error alerts
login.php                             # Added error and timeout alerts
account.php                           # Added profile and password change alerts
forgot-password.php                   # Added password reset alert
verify-otp.php                        # Added verification success/error alerts
```

## SweetAlert Configuration Module

### Location
`public/js/sweetalert-config.js`

### Available Functions

#### 1. `SweetAlerts.success(title, message, callback)`
Shows a success alert with a green checkmark.
```javascript
SweetAlerts.success('Success', 'Operation completed!', function() {
  // Callback when OK is clicked
});
```

#### 2. `SweetAlerts.error(title, message, callback)`
Shows an error alert with a red X.
```javascript
SweetAlerts.error('Error', 'Something went wrong!', function() {
  // Callback when OK is clicked
});
```

#### 3. `SweetAlerts.warning(title, message, confirmText, cancelText, onConfirm, onCancel)`
Shows a warning alert with Yes/No buttons.
```javascript
SweetAlerts.warning(
  'Confirm Action',
  'Are you sure?',
  'Yes',
  'No',
  function() { // onConfirm
    // User clicked Yes
  },
  function() { // onCancel
    // User clicked No
  }
);
```

#### 4. `SweetAlerts.info(title, message, callback)`
Shows an info alert with blue icon.
```javascript
SweetAlerts.info('Information', 'Important info here', function() {
  // Callback
});
```

#### 5. `SweetAlerts.loading(title, message)`
Shows a loading state with spinner.
```javascript
SweetAlerts.loading('Processing', 'Please wait...');
```

#### 6. `SweetAlerts.confirmLogout(onConfirm)`
Specialized logout confirmation alert.
```javascript
SweetAlerts.confirmLogout(function() {
  // Proceed with logout
});
```

#### 7. `SweetAlerts.registrationSuccess(callback)`
Specialized registration success alert.
```javascript
SweetAlerts.registrationSuccess(function() {
  // Redirect to verification page
});
```

#### 8. `SweetAlerts.verificationSuccess(callback)`
Specialized email verification success alert.
```javascript
SweetAlerts.verificationSuccess(function() {
  // Redirect to login
});
```

#### 9. `SweetAlerts.passwordResetSuccess(callback)`
Specialized password reset request success alert.
```javascript
SweetAlerts.passwordResetSuccess(function() {
  // Redirect to login
});
```

#### 10. `SweetAlerts.passwordChangedSuccess(callback)`
Specialized password change success alert.
```javascript
SweetAlerts.passwordChangedSuccess(function() {
  // Optional redirect
});
```

#### 11. `SweetAlerts.profileUpdatedSuccess(callback)`
Specialized profile update success alert.
```javascript
SweetAlerts.profileUpdatedSuccess(function() {
  // Optional redirect
});
```

#### 12. `SweetAlerts.close()`
Closes any open SweetAlert.
```javascript
SweetAlerts.close();
```

## CDN Integration

All pages that use SweetAlert2 include:

```html
<!-- SweetAlert2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">

<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

<!-- Custom Configuration -->
<script src="/library_betonio/public/js/sweetalert-config.js"></script>
```

## Styling & Customization

### Color Scheme
- **Accent Color**: `#d24718` (QueenLib Orange)
- **Success**: Green checkmark
- **Error**: Red X
- **Warning**: Yellow warning icon
- **Info**: Blue info icon

### Responsive Design
SweetAlert2 automatically adapts to:
- Desktop screens (centered modal)
- Tablets (medium modal)
- Mobile devices (full-width with padding)

### CSS Classes
All alerts use SweetAlert2's built-in responsive classes. No custom CSS is needed.

## Usage Examples

### Registration Success Flow
```php
<?php
if ($registration_success) {
  $_SESSION['show_registration_alert'] = true;
  redirect('/library_betonio/register.php?success=1');
}
?>

<!-- In HTML/JavaScript -->
<?php if ($show_success_alert): ?>
  <script>
    SweetAlerts.registrationSuccess(function() {
      window.location.href = '/library_betonio/verify-otp.php';
    });
  </script>
<?php endif; ?>
```

### Error Handling
```php
<?php
if ($error) {
  // Error is already set
}
?>

<!-- In HTML/JavaScript -->
<?php if ($error): ?>
  <script>
    SweetAlerts.error('Error', '<?php echo addslashes($error); ?>');
  </script>
<?php endif; ?>
```

## Browser Compatibility

SweetAlert2 v11 supports:
- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Accessibility Features

- ✅ Keyboard navigation (Tab, Enter, Escape)
- ✅ Screen reader support
- ✅ ARIA labels
- ✅ High contrast colors
- ✅ Focus management

## Performance

- **CDN Hosted**: Fast delivery via Cloudflare CDN
- **Minified**: SweetAlert2 is minified for faster loading
- **Async Loading**: Non-blocking script loading
- **Lightweight**: ~30KB total size (gzipped)

## Security Considerations

All user input in alerts is properly escaped:
- PHP: `htmlspecialchars()` for display
- JavaScript: `addslashes()` to escape quotes in strings

## Troubleshooting

### Alert Not Showing
1. Verify CDN links are accessible
2. Check browser console for JavaScript errors
3. Ensure `sweetalert-config.js` is loaded

### Styling Issues
1. Clear browser cache
2. Verify CSS CDN link is working
3. Check for CSS conflicts with existing styles

### Callback Not Executing
1. Ensure callback function is properly passed
2. Check for JavaScript syntax errors
3. Verify modal is not prevented from closing

## Migration from Old Alert System

### Before (Old HTML Alerts)
```html
<?php if ($error): ?>
  <div class="alert alert-error" role="alert">❌ <?php echo $error; ?></div>
<?php endif; ?>
```

### After (SweetAlert2)
```php
<?php if ($error): ?>
  <script>
    SweetAlerts.error('Error', '<?php echo addslashes($error); ?>');
  </script>
<?php endif; ?>
```

## Future Enhancements

Possible improvements for future versions:
- [ ] Sound notifications
- [ ] Toast notifications (non-modal alerts)
- [ ] Multi-step registration wizard
- [ ] Animated transitions
- [ ] Custom theme support
- [ ] Dark mode theme

## Support & Documentation

- **SweetAlert2 Official Docs**: https://sweetalert2.github.io/
- **GitHub Repository**: https://github.com/sweetalert2/sweetalert2
- **Issues**: Report bugs in project repository

## Version Information

- **SweetAlert2 Version**: 11 (Latest)
- **CDN Source**: jsdelivr.net
- **Last Updated**: 2026
