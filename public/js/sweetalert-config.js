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
  success: function (title, message, callback) {
    Swal.fire({
      icon: "success",
      title: title,
      text: message,
      confirmButtonText: "OK",
      confirmButtonColor: "#d24718",
      allowOutsideClick: false,
      allowEscapeKey: false,
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
  error: function (title, message, callback) {
    Swal.fire({
      icon: "error",
      title: title,
      text: message,
      confirmButtonText: "OK",
      confirmButtonColor: "#d24718",
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
  warning: function (
    title,
    message,
    confirmText,
    cancelText,
    onConfirm,
    onCancel,
  ) {
    Swal.fire({
      icon: "warning",
      title: title,
      text: message,
      showCancelButton: true,
      confirmButtonText: confirmText || "Yes",
      cancelButtonText: cancelText || "Cancel",
      confirmButtonColor: "#d24718",
      cancelButtonColor: "#999",
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
  loading: function (title, message) {
    Swal.fire({
      icon: "info",
      title: title,
      text: message,
      allowOutsideClick: false,
      allowEscapeKey: false,
      didOpen: () => {
        Swal.showLoading();
      },
    });
  },

  /**
   * Info Alert - For informational messages
   * @param {string} title - Alert title
   * @param {string} message - Alert message
   * @param {function} callback - Callback when OK is clicked
   */
  info: function (title, message, callback) {
    Swal.fire({
      icon: "info",
      title: title,
      text: message,
      confirmButtonText: "OK",
      confirmButtonColor: "#d24718",
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
  confirmLogout: function (onConfirm) {
    Swal.fire({
      icon: "question",
      title: "Logout",
      text: "Are you sure you want to logout?",
      showCancelButton: true,
      confirmButtonText: "Yes, Logout",
      cancelButtonText: "Cancel",
      confirmButtonColor: "#d24718",
      cancelButtonColor: "#999",
      allowOutsideClick: false,
      allowEscapeKey: false,
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
  registrationSuccess: function (callback) {
    Swal.fire({
      icon: "success",
      title: "Account Created!",
      text: "Please verify your email to activate your account.",
      confirmButtonText: "Go to Verification",
      confirmButtonColor: "#d24718",
      allowOutsideClick: false,
      allowEscapeKey: false,
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
  verificationSuccess: function (callback) {
    Swal.fire({
      icon: "success",
      title: "Email Verified!",
      text: "Your email has been verified successfully. You can now login.",
      confirmButtonText: "Go to Login",
      confirmButtonColor: "#d24718",
      allowOutsideClick: false,
      allowEscapeKey: false,
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
  passwordResetSuccess: function (callback) {
    Swal.fire({
      icon: "success",
      title: "Password Reset Sent!",
      text: "Check your email for password reset instructions.",
      confirmButtonText: "Back to Login",
      confirmButtonColor: "#d24718",
      allowOutsideClick: false,
      allowEscapeKey: false,
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
  passwordChangedSuccess: function (callback) {
    Swal.fire({
      icon: "success",
      title: "Password Updated!",
      text: "Your password has been changed successfully.",
      confirmButtonText: "OK",
      confirmButtonColor: "#d24718",
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
  profileUpdatedSuccess: function (callback) {
    Swal.fire({
      icon: "success",
      title: "Profile Updated!",
      text: "Your profile has been updated successfully.",
      confirmButtonText: "OK",
      confirmButtonColor: "#d24718",
    }).then((result) => {
      if (result.isConfirmed && callback) {
        callback();
      }
    });
  },

  /**
   * Unverified Login Attempt Alert
   * Shown when a user tries to login without verifying their email
   * @param {function} callback - Callback when "Back to Login" is clicked
   */
  unverifiedLoginAttempt: function (callback) {
    Swal.fire({
      icon: "warning",
      title: "Email Not Verified",
      html: "Your email address has not been verified yet.<br>Please check your inbox and click the verification link to activate your account.",
      showDenyButton: true,
      showCancelButton: true,
      confirmButtonText: "Resend Email",
      denyButtonText: "Go to Verification Page",
      cancelButtonText: "Back to Login",
      confirmButtonColor: "#d24718",
      denyButtonColor: "#3498db",
      cancelButtonColor: "#999",
      allowOutsideClick: false,
      allowEscapeKey: false,
    }).then((result) => {
      if (result.isConfirmed) {
        // Resend: submit hidden form on the page
        var form = document.getElementById("resend-verification-form");
        if (form) {
          form.submit();
        }
      } else if (result.isDenied) {
        // Go to verification page with current email
        var emailInput = document.getElementById("verify-email-hidden");
        var email = emailInput ? emailInput.value : "";
        if (email) {
          window.location.href =
            "verify-otp.php?email=" + encodeURIComponent(email);
        }
      } else if (result.isDismissed && callback) {
        callback();
      }
    });
  },

  /**
   * Book Reserved Success
   * @param {function} callback - Callback when OK is clicked
   */
  bookReserved: function (callback) {
    Swal.fire({
      icon: "success",
      title: "Book Reserved!",
      text: "Your reservation has been placed. Check your reservations page for updates.",
      confirmButtonText: "OK",
      confirmButtonColor: "#d24718",
      allowOutsideClick: false,
      allowEscapeKey: false,
    }).then((result) => {
      if (result.isConfirmed && callback) {
        callback();
      }
    });
  },

  /**
   * Loan Renewed Success
   * @param {function} callback - Callback when OK is clicked
   */
  loanRenewed: function (callback) {
    Swal.fire({
      icon: "success",
      title: "Loan Renewed!",
      text: "Your loan has been renewed successfully.",
      confirmButtonText: "OK",
      confirmButtonColor: "#d24718",
      allowOutsideClick: false,
      allowEscapeKey: false,
    }).then((result) => {
      if (result.isConfirmed && callback) {
        callback();
      }
    });
  },

  /**
   * Reservation Cancelled Success
   * @param {function} callback - Callback when OK is clicked
   */
  reservationCancelled: function (callback) {
    Swal.fire({
      icon: "success",
      title: "Reservation Cancelled",
      text: "Your reservation has been cancelled successfully.",
      confirmButtonText: "OK",
      confirmButtonColor: "#d24718",
      allowOutsideClick: false,
      allowEscapeKey: false,
    }).then((result) => {
      if (result.isConfirmed && callback) {
        callback();
      }
    });
  },

  /**
   * Generic Transaction Complete
   * @param {function} callback - Callback when OK is clicked
   */
  transactionComplete: function (callback) {
    Swal.fire({
      icon: "success",
      title: "Transaction Complete",
      text: "The operation was completed successfully.",
      confirmButtonText: "OK",
      confirmButtonColor: "#d24718",
      allowOutsideClick: false,
      allowEscapeKey: false,
    }).then((result) => {
      if (result.isConfirmed && callback) {
        callback();
      }
    });
  },

  /**
   * Generic Operation Failed
   * @param {function} callback - Callback when OK is clicked
   */
  operationFailed: function (callback) {
    Swal.fire({
      icon: "error",
      title: "Operation Failed",
      text: "Something went wrong. Please try again.",
      confirmButtonText: "OK",
      confirmButtonColor: "#d24718",
    }).then((result) => {
      if (result.isConfirmed && callback) {
        callback();
      }
    });
  },

  /**
   * Close any open alert
   */
  close: function () {
    Swal.close();
  },
};
