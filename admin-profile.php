<?php

require_once 'includes/config.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
  redirect('admin-login.php');
}

// Admin profile data (static for now)
$admin_profile = [
  'name' => 'Queen Stephanie C. Betonio',
  'email' => 'queenstephanie@nmsc.edu.ph',
  'phone' => '09106370493',
  'dob' => 'March 5, 2005',
  'role' => 'Super Administrator',
  'access_level' => 'Full Access'
];

// Handle profile update
$profile_updated = false;
$update_error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $dob = trim($_POST['dob'] ?? '');

  // Validation
  if (empty($name)) {
    $update_error = 'Name is required';
  } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $update_error = 'Valid email is required';
  } elseif (empty($phone)) {
    $update_error = 'Phone number is required';
  } elseif (empty($dob)) {
    $update_error = 'Date of birth is required';
  } else {
    // In a real app, you would update the database here
    // For now, we'll just update the session data
    $_SESSION['admin_profile'] = [
      'name' => $name,
      'email' => $email,
      'phone' => $phone,
      'dob' => $dob
    ];
    
    $admin_profile = $_SESSION['admin_profile'];
    $profile_updated = true;
  }
}

// Use session data if available, otherwise use defaults
if (isset($_SESSION['admin_profile'])) {
  $admin_profile = array_merge($admin_profile, $_SESSION['admin_profile']);
}

$page_alerts = [];
if ($profile_updated) {
  $page_alerts[] = [
    'type' => 'success',
    'title' => 'Profile Updated',
    'message' => 'Your profile information has been updated successfully.'
  ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>QueenLib | Admin Profile Settings</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="public/css/main.css">
  <link rel="stylesheet" href="public/css/admin.css">
  <!-- SweetAlert2 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
  <style>
    .admin-profile-page {
      background: var(--admin-bg);
      min-height: 100vh;
    }

    .admin-profile-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 40px 20px;
    }

    .admin-profile-hero {
      background: linear-gradient(135deg, var(--admin-accent) 0%, var(--admin-accent-dark) 100%);
      border-radius: 20px;
      padding: 40px;
      color: #000000;
      margin-bottom: 40px;
      display: grid;
      grid-template-columns: auto 1fr;
      gap: 30px;
      align-items: center;
      box-shadow: 0 10px 30px rgba(210, 71, 24, 0.2);
    }

    .admin-profile-hero-pic {
      width: 200px;
      height: 240px;
      border-radius: 16px;
      overflow: hidden;
      border: 5px solid rgba(0, 0, 0, 0.15);
      flex-shrink: 0;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    }

    .admin-profile-hero-pic img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .admin-profile-hero-info h2 {
      margin: 0 0 8px;
      font-family: "Cormorant Garamond", serif;
      font-size: 2rem;
      letter-spacing: -0.02em;
      color: #000000;
    }

    .admin-profile-role {
      margin: 0 0 16px;
      font-size: 1.1rem;
      color: #000000;
    }

    .admin-profile-badges {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
    }

    .admin-badge {
      display: inline-block;
      padding: 6px 14px;
      background: rgba(0, 0, 0, 0.1);
      border: 1.5px solid #000000;
      border-radius: 20px;
      font-size: 0.9rem;
      font-weight: 500;
      color: #000000;
    }

    .admin-profile-details {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 20px;
      margin-bottom: 40px;
    }

    .admin-detail-card {
      background: white;
      border: 1px solid var(--admin-line);
      border-radius: 16px;
      padding: 24px;
      transition: all 0.3s ease;
    }

    .admin-detail-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
      border-color: var(--admin-accent);
    }

    .admin-detail-label {
      color: var(--admin-muted);
      font-size: 0.9rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      font-weight: 600;
      margin-bottom: 8px;
    }

    .admin-detail-value {
      margin: 0;
      font-size: 1.2rem;
      color: var(--admin-text);
      font-weight: 500;
    }

    .admin-profile-form-card {
      background: white;
      border: 1px solid var(--admin-line);
      border-radius: 20px;
      padding: 40px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }

    .admin-form-title {
      margin: 0 0 8px;
      font-family: "Cormorant Garamond", serif;
      font-size: 1.8rem;
      letter-spacing: -0.02em;
      color: #000000;
    }

    .admin-form-subtitle {
      margin: 0 0 30px;
      color: var(--admin-muted);
      font-size: 1rem;
    }

    .admin-form-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 24px;
      margin-bottom: 30px;
    }

    .admin-form-group {
      display: grid;
      gap: 8px;
    }

    .admin-form-group label {
      font-weight: 600;
      color: var(--admin-text);
      font-size: 0.95rem;
    }

    .admin-form-group input {
      padding: 12px 14px;
      border: 1.5px solid var(--admin-line);
      border-radius: 10px;
      font-family: inherit;
      font-size: 1rem;
      transition: all 0.3s ease;
      background: #ffffff;
    }

    .admin-form-group input:hover {
      border-color: #d0d0d0;
    }

    .admin-form-group input:focus {
      outline: none;
      border-color: var(--admin-accent);
      box-shadow: 0 0 0 3px rgba(210, 71, 24, 0.1);
      background: #fafaf8;
    }

    .admin-form-hint {
      margin: 4px 0 0;
      font-size: 0.85rem;
      color: var(--admin-muted);
      font-style: italic;
    }

    .admin-form-error {
      background: #fff2ef;
      border: 1px solid #f0b7a7;
      border-radius: 12px;
      padding: 12px 14px;
      margin-bottom: 24px;
      color: #a62f0d;
      font-size: 0.95rem;
      font-weight: 500;
    }

    .admin-form-actions {
      display: flex;
      gap: 16px;
      margin-top: 32px;
      padding-top: 24px;
      border-top: 1px solid var(--admin-line);
    }

    .admin-form-actions button,
    .admin-form-actions a {
      flex: 1;
      padding: 12px 20px;
      border: 0;
      border-radius: 10px;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      text-align: center;
      transition: all 0.3s ease;
      font-family: inherit;
      font-size: 1rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .admin-form-actions button {
      background: #000000;
      color: white;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    }

    .admin-form-actions button:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.4);
      background: #1a1a1a;
    }

    .admin-form-actions a {
      background: var(--admin-card);
      color: var(--admin-text);
      border: 1.5px solid var(--admin-line);
    }

    .admin-form-actions a:hover {
      background: #f0f0f0;
      border-color: var(--admin-accent);
      color: var(--admin-accent);
    }

    @media (max-width: 1000px) {
      .admin-profile-details {
        grid-template-columns: repeat(2, 1fr);
      }

      .admin-form-grid {
        grid-template-columns: 1fr;
      }

      .admin-profile-hero {
        grid-template-columns: 1fr;
        text-align: center;
      }

      .admin-profile-hero-pic {
        margin: 0 auto;
      }
    }

    @media (max-width: 600px) {
      .admin-profile-container {
        padding: 20px 16px;
      }

      .admin-profile-hero {
        padding: 24px;
        gap: 20px;
      }

      .admin-profile-details {
        grid-template-columns: 1fr;
        gap: 16px;
      }

      .admin-profile-form-card {
        padding: 24px;
      }

      .admin-form-actions {
        flex-direction: column;
        gap: 12px;
      }

      .admin-form-actions button,
      .admin-form-actions a {
        width: 100%;
      }
    }
  </style>
</head>

<body class="admin-page-body">
  <header class="site-header">
    <div class="container nav-wrap">
      <a href="admin-dashboard.php" class="brand">QueenLib Admin</a>
      <div class="nav-actions">
        <a class="nav-link" href="admin-dashboard.php">Dashboard</a>
        <a class="button button-small button-primary" href="#" onclick="adminLogoutConfirm(event)">Logout</a>
      </div>
    </div>
  </header>

  <main class="admin-profile-page">
    <div class="admin-profile-container">
      <!-- Hero Section -->
      <section class="admin-profile-hero">
        <div class="admin-profile-hero-pic">
          <img src="images/admin_pic.jpg" alt="Administrator Profile Picture">
        </div>
        <div class="admin-profile-hero-info">
          <h2><?php echo htmlspecialchars($admin_profile['name']); ?></h2>
          <p class="admin-profile-role"><?php echo htmlspecialchars($admin_profile['role']); ?></p>
          <div class="admin-profile-badges">
            <span class="admin-badge">Full System Access</span>
            <span class="admin-badge">Super Administrator</span>
          </div>
        </div>
      </section>

      <!-- Details Cards -->
      <section class="admin-profile-details">
        <div class="admin-detail-card">
          <div class="admin-detail-label">Email Address</div>
          <p class="admin-detail-value"><?php echo htmlspecialchars($admin_profile['email']); ?></p>
        </div>
        <div class="admin-detail-card">
          <div class="admin-detail-label">Phone Number</div>
          <p class="admin-detail-value"><?php echo htmlspecialchars($admin_profile['phone']); ?></p>
        </div>
        <div class="admin-detail-card">
          <div class="admin-detail-label">Date of Birth</div>
          <p class="admin-detail-value"><?php echo htmlspecialchars($admin_profile['dob']); ?></p>
        </div>
      </section>

      <!-- Edit Form -->
      <section class="admin-profile-form-card">
        <h3 class="admin-form-title">Update Your Information</h3>
        <p class="admin-form-subtitle">Keep your profile information up to date</p>

        <?php if ($update_error): ?>
          <div class="admin-form-error">
            <?php echo htmlspecialchars($update_error); ?>
          </div>
        <?php endif; ?>

        <form method="POST">
          <div class="admin-form-grid">
            <div class="admin-form-group">
              <label for="name">Full Name</label>
              <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($admin_profile['name']); ?>" required>
              <p class="admin-form-hint">Enter your full legal name</p>
            </div>

            <div class="admin-form-group">
              <label for="email">Email Address</label>
              <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($admin_profile['email']); ?>" required>
              <p class="admin-form-hint">Your official admin email</p>
            </div>

            <div class="admin-form-group">
              <label for="phone">Phone Number</label>
              <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($admin_profile['phone']); ?>" required>
              <p class="admin-form-hint">Contact number</p>
            </div>

            <div class="admin-form-group">
              <label for="dob">Date of Birth</label>
              <input type="text" id="dob" name="dob" placeholder="e.g., March 5, 2005" value="<?php echo htmlspecialchars($admin_profile['dob']); ?>" required>
              <p class="admin-form-hint">e.g., March 5, 2005</p>
            </div>
          </div>

          <div class="admin-form-actions">
            <button type="submit">Save Changes</button>
            <a href="admin-dashboard.php">Go Back</a>
          </div>
        </form>
      </section>
    </div>
  </main>

  <?php renderSweetAlertScripts(); ?>
  <?php renderPageAlerts($page_alerts); ?>
  <script>
    function adminLogoutConfirm(e) {
      e.preventDefault();
      SweetAlerts.warning(
        'Logout',
        'Are you sure you want to logout from admin?',
        'Yes, Logout',
        'Cancel',
        function() {
          window.location.href = <?php echo json_encode(appPath('admin-logout.php')); ?>;
        }
      );
    }
  </script>
</body>

</html>
