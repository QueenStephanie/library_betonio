/**
 * SweetAlert2 Configuration Module
 * Provides reusable alert functions with consistent styling
 */

const SweetAlerts = {
  /**
   * Success Alert - Used for successful operations
   * @param {string} title - Alert title
   * @param {string} message - Alert message
   * @param {function} callback - Callback when OK is clicked
   */
  success: function(title, message, callback) {
    Swal.fire({
      icon: 'success',
      title: title,
      text: message,
      confirmButtonText: 'OK',
      confirmButtonColor: '#d24718',
      allowOutsideClick: false,
      allowEscapeKey: false
    }).then((result) => {
      if (result.isConfirmed && callback) {
        callback();
      }
    });
  },

  /**
   * Error Alert - Used for error messages
   * @param {string} title - Alert title
   * @param {string} message - Alert message
   * @param {function} callback - Callback when OK is clicked
   */
  error: function(title, message, callback) {
    Swal.fire({
      icon: 'error',
      title: title,
      text: message,
      confirmButtonText: 'OK',
      confirmButtonColor: '#d24718'
    }).then((result) => {
      if (result.isConfirmed && callback) {
        callback();
      }
    });
  },

  /**
   * Warning Alert - Used for confirmations
   * @param {string} title - Alert title
   * @param {string} message - Alert message
   * @param {string} confirmText - Confirm button text
   * @param {string} cancelText - Cancel button text
   * @param {function} onConfirm - Callback when confirmed
   * @param {function} onCancel - Callback when cancelled
   */
  warning: function(title, message, confirmText, cancelText, onConfirm, onCancel) {
    Swal.fire({
      icon: 'warning',
      title: title,
      text: message,
      showCancelButton: true,
      confirmButtonText: confirmText || 'Yes',
      cancelButtonText: cancelText || 'Cancel',
      confirmButtonColor: '#d24718',
      cancelButtonColor: '#999'
    }).then((result) => {
      if (result.isConfirmed) {
        if (onConfirm) onConfirm();
      } else if (result.isDismissed) {
        if (onCancel) onCancel();
      }
    });
  },

  /**
   * Loading Alert - Shows a loading state
   * @param {string} title - Alert title
   * @param {string} message - Alert message
   */
  loading: function(title, message) {
    Swal.fire({
      icon: 'info',
      title: title,
      text: message,
      allowOutsideClick: false,
      allowEscapeKey: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });
  },

  /**
   * Info Alert - For informational messages
   * @param {string} title - Alert title
   * @param {string} message - Alert message
   * @param {function} callback - Callback when OK is clicked
   */
  info: function(title, message, callback) {
    Swal.fire({
      icon: 'info',
      title: title,
      text: message,
      confirmButtonText: 'OK',
      confirmButtonColor: '#d24718'
    }).then((result) => {
      if (result.isConfirmed && callback) {
        callback();
      }
    });
  },

  /**
   * Logout Confirmation - Special logout alert
   * @param {function} onConfirm - Callback when logout is confirmed
   */
  confirmLogout: function(onConfirm) {
    Swal.fire({
      icon: 'question',
      title: 'Logout',
      text: 'Are you sure you want to logout?',
      showCancelButton: true,
      confirmButtonText: 'Yes, Logout',
      cancelButtonText: 'Cancel',
      confirmButtonColor: '#d24718',
      cancelButtonColor: '#999',
      allowOutsideClick: false,
      allowEscapeKey: false
    }).then((result) => {
      if (result.isConfirmed) {
        if (onConfirm) onConfirm();
      }
    });
  },

  /**
   * Registration Success Alert
   * @param {function} callback - Callback when OK is clicked
   */
  registrationSuccess: function(callback) {
    Swal.fire({
      icon: 'success',
      title: 'Account Created!',
      text: 'Please check your email to verify your account before logging in.',
      confirmButtonText: 'Go to Login',
      confirmButtonColor: '#d24718',
      allowOutsideClick: false,
      allowEscapeKey: false
    }).then((result) => {
      if (result.isConfirmed && callback) {
        callback();
      }
    });
  },

  /**
   * Email Verification Success Alert
   * @param {function} callback - Callback when OK is clicked
   */
  verificationSuccess: function(callback) {
    Swal.fire({
      icon: 'success',
      title: 'Email Verified!',
      text: 'Your email has been verified successfully. You can now login.',
      confirmButtonText: 'Go to Login',
      confirmButtonColor: '#d24718',
      allowOutsideClick: false,
      allowEscapeKey: false
    }).then((result) => {
      if (result.isConfirmed && callback) {
        callback();
      }
    });
  },

  /**
   * Password Reset Success Alert
   * @param {function} callback - Callback when OK is clicked
   */
  passwordResetSuccess: function(callback) {
    Swal.fire({
      icon: 'success',
      title: 'Password Reset Sent!',
      text: 'Check your email for password reset instructions.',
      confirmButtonText: 'Back to Login',
      confirmButtonColor: '#d24718',
      allowOutsideClick: false,
      allowEscapeKey: false
    }).then((result) => {
      if (result.isConfirmed && callback) {
        callback();
      }
    });
  },

  /**
   * Password Changed Success Alert
   * @param {function} callback - Callback when OK is clicked
   */
  passwordChangedSuccess: function(callback) {
    Swal.fire({
      icon: 'success',
      title: 'Password Updated!',
      text: 'Your password has been changed successfully.',
      confirmButtonText: 'OK',
      confirmButtonColor: '#d24718'
    }).then((result) => {
      if (result.isConfirmed && callback) {
        callback();
      }
    });
  },

  /**
   * Profile Updated Success Alert
   * @param {function} callback - Callback when OK is clicked
   */
  profileUpdatedSuccess: function(callback) {
    Swal.fire({
      icon: 'success',
      title: 'Profile Updated!',
      text: 'Your profile has been updated successfully.',
      confirmButtonText: 'OK',
      confirmButtonColor: '#d24718'
    }).then((result) => {
      if (result.isConfirmed && callback) {
        callback();
      }
    });
  },

  /**
   * Close any open alert
   */
  close: function() {
    Swal.close();
  }
};
