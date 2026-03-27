/**
 * API Configuration
 * Backend developers: Update these endpoints to match your API routes
 */

const API_CONFIG = {
  baseURL: "http://localhost/api", // Update to your backend URL
  endpoints: {
    // Authentication endpoints
    auth: {
      login: "/auth/login",
      register: "/auth/register",
      logout: "/auth/logout",
      verifyEmail: "/auth/verify-email",
      resendCode: "/auth/resend-verification-code",
      forgotPassword: "/auth/forgot-password",
      resetPassword: "/auth/reset-password",
    },
    // User endpoints
    users: {
      getProfile: "/users/profile",
      updateProfile: "/users/profile",
      getLoans: "/users/loans",
      getReservations: "/users/reservations",
    },
    // Book endpoints
    books: {
      search: "/books/search",
      getDetails: "/books/:id",
      borrow: "/books/:id/borrow",
      return: "/books/:id/return",
      reserve: "/books/:id/reserve",
    },
  },
  timeout: 30000, // 30 seconds
  headers: {
    "Content-Type": "application/json",
  },
};

// Helper function to construct full API URLs
function getApiUrl(endpoint) {
  return `${API_CONFIG.baseURL}${endpoint}`;
}

// Helper function for API calls
async function apiCall(endpoint, options = {}) {
  const url = getApiUrl(endpoint);
  const defaultOptions = {
    ...options,
    headers: {
      ...API_CONFIG.headers,
      ...options.headers,
    },
  };

  try {
    const response = await fetch(url, defaultOptions);
    if (!response.ok) {
      throw new Error(`API Error: ${response.statusText}`);
    }
    return await response.json();
  } catch (error) {
    console.error("API call failed:", error);
    throw error;
  }
}
