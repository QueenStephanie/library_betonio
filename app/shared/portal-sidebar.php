<?php

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) === realpath((string)$_SERVER['SCRIPT_FILENAME'])) {
  http_response_code(404);
  exit('Not Found');
}

$portalRole = strtolower(trim((string)($portalRole ?? 'borrower')));
if (!in_array($portalRole, ['borrower', 'librarian', 'admin'], true)) {
  $portalRole = 'borrower';
}

$portalCurrentPage = strtolower(trim((string)($portalCurrentPage ?? '')));
$portalIdentityName = trim((string)($portalIdentityName ?? ''));
$portalIdentityMeta = trim((string)($portalIdentityMeta ?? ''));
$portalBrandSub = trim((string)($portalBrandSub ?? ''));

if ($portalIdentityName === '') {
  if ($portalRole === 'admin') {
    $portalIdentityName = 'Administrator';
  } elseif ($portalRole === 'librarian') {
    $portalIdentityName = 'Librarian User';
  } else {
    $portalIdentityName = 'Borrower User';
  }
}

if ($portalBrandSub === '') {
  if ($portalRole === 'admin') {
    $portalBrandSub = 'Admin Portal';
  } elseif ($portalRole === 'librarian') {
    $portalBrandSub = 'Librarian Portal';
  } else {
    $portalBrandSub = 'Borrower Portal';
  }
}

$navByRole = [
  'borrower' => [
    ['key' => 'dashboard', 'label' => 'Dashboard', 'path' => 'index.php', 'icon' => 'dashboard'],
    ['key' => 'catalog', 'label' => 'Catalog', 'path' => 'catalog.php', 'icon' => 'books'],
    ['key' => 'reservations', 'label' => 'Reservations', 'path' => 'reservations.php', 'icon' => 'reservations'],
    ['key' => 'history', 'label' => 'History', 'path' => 'history.php', 'icon' => 'history'],
    ['key' => 'account', 'label' => 'Account', 'path' => 'account.php', 'icon' => 'account'],
    ['key' => 'logout', 'label' => 'Log Out', 'path' => 'logout.php', 'icon' => 'logout', 'logout' => true],
  ],
  'librarian' => [
    ['key' => 'dashboard', 'label' => 'Dashboard', 'path' => 'librarian-dashboard.php', 'icon' => 'dashboard'],
    ['key' => 'circulation', 'label' => 'Circulation', 'path' => 'librarian-circulation.php', 'icon' => 'circulation'],
    ['key' => 'books', 'label' => 'Books', 'path' => 'librarian-books.php', 'icon' => 'books'],
    ['key' => 'reservations', 'label' => 'Reservations', 'path' => 'librarian-reservations.php', 'icon' => 'reservations'],
    ['key' => 'fines', 'label' => 'Fines', 'path' => 'librarian-fines.php', 'icon' => 'fines'],
    ['key' => 'logout', 'label' => 'Log Out', 'path' => 'admin-logout.php', 'icon' => 'logout', 'logout' => true],
  ],
  'admin' => [
    ['key' => 'dashboard', 'label' => 'Dashboard', 'path' => 'admin-dashboard.php', 'icon' => 'dashboard'],
    ['key' => 'users', 'label' => 'User Management', 'path' => 'admin-users.php', 'icon' => 'users'],
    ['key' => 'change-password', 'label' => 'Change Password', 'path' => 'admin-change-password.php', 'icon' => 'password'],
    ['key' => 'fines', 'label' => 'Fines Report', 'path' => 'admin-fines.php', 'icon' => 'fines'],
    ['key' => 'logout', 'label' => 'Log Out', 'path' => 'admin-logout.php', 'icon' => 'logout', 'logout' => true],
  ],
];

$renderPortalIcon = static function ($icon) {
  switch ((string)$icon) {
    case 'dashboard':
      return '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 10.5L12 3L21 10.5V21H3V10.5Z" stroke="currentColor" stroke-width="1.6"/></svg>';
    case 'circulation':
      return '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 5H20V19H4V5Z" stroke="currentColor" stroke-width="1.6"/><path d="M8 9H16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M8 13H16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>';
    case 'books':
      return '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 4H18V20H6V4Z" stroke="currentColor" stroke-width="1.6"/><path d="M9 8H15" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>';
    case 'reservations':
      return '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 7H20" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><rect x="4" y="7" width="16" height="12" rx="2" stroke="currentColor" stroke-width="1.6"/></svg>';
    case 'history':
      return '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.6"/><path d="M12 8V12L15 14" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    case 'account':
      return '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 12C14.21 12 16 10.21 16 8C16 5.79 14.21 4 12 4C9.79 4 8 5.79 8 8C8 10.21 9.79 12 12 12Z" stroke="currentColor" stroke-width="1.8"/><path d="M4.93 20C5.83 17.1 8.57 15 12 15C15.43 15 18.17 17.1 19.07 20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>';
    case 'users':
      return '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M16 13C18.21 13 20 11.21 20 9C20 6.79 18.21 5 16 5" stroke="currentColor" stroke-width="1.6"/><path d="M4 20C4.9 17.3 7.7 15.5 11 15.5C14.3 15.5 17.1 17.3 18 20" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M11 13C13.21 13 15 11.21 15 9C15 6.79 13.21 5 11 5C8.79 5 7 6.79 7 9C7 11.21 8.79 13 11 13Z" stroke="currentColor" stroke-width="1.6"/></svg>';
    case 'password':
      return '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 10V8C6 5.79 7.79 4 10 4H14C16.21 4 18 5.79 18 8V10" stroke="currentColor" stroke-width="1.6"/><rect x="5" y="10" width="14" height="10" rx="2" stroke="currentColor" stroke-width="1.6"/></svg>';
    case 'fines':
      return '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.6"/><path d="M12 8V16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>';
    case 'logout':
      return '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15 7L20 12L15 17" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M20 12H9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M4 4H9V20H4" stroke="currentColor" stroke-width="1.6"/></svg>';
    default:
      return '';
  }
};

$portalNavItems = $navByRole[$portalRole];
$portalCurrentPageLabel = $portalBrandSub;
foreach ($portalNavItems as $portalNavItem) {
  if ($portalCurrentPage === (string)$portalNavItem['key']) {
    $portalCurrentPageLabel = (string)$portalNavItem['label'];
    break;
  }
}

$portalMobileDrawerId = 'admin-sidebar-mobile-drawer-' . $portalRole;
?>
<aside class="admin-sidebar" aria-label="<?php echo htmlspecialchars(ucfirst($portalRole) . ' navigation', ENT_QUOTES, 'UTF-8'); ?>">
  <div class="admin-sidebar-desktop">
    <div class="admin-brand-wrap">
      <div class="admin-brand">QueenLib</div>
      <div class="admin-brand-sub"><?php echo htmlspecialchars($portalBrandSub, ENT_QUOTES, 'UTF-8'); ?></div>
    </div>

    <div class="admin-sidebar-profile">
      <span class="admin-sidebar-avatar" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M12 12C14.21 12 16 10.21 16 8C16 5.79 14.21 4 12 4C9.79 4 8 5.79 8 8C8 10.21 9.79 12 12 12Z" stroke="currentColor" stroke-width="1.8" />
          <path d="M4.93 20C5.83 17.1 8.57 15 12 15C15.43 15 18.17 17.1 19.07 20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
        </svg>
      </span>
      <div>
        <div class="admin-sidebar-name"><?php echo htmlspecialchars($portalIdentityName, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php if ($portalIdentityMeta !== ''): ?>
          <div class="admin-sidebar-role"><?php echo htmlspecialchars($portalIdentityMeta, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
      </div>
    </div>

    <nav class="admin-nav" aria-label="<?php echo htmlspecialchars(ucfirst($portalRole) . ' sidebar links', ENT_QUOTES, 'UTF-8'); ?>">
      <?php foreach ($portalNavItems as $item): ?>
        <?php
        $isActive = $portalCurrentPage === (string)$item['key'];
        $isLogout = !empty($item['logout']);
        $itemClasses = 'admin-nav-item';
        if ($isActive) {
          $itemClasses .= ' is-active';
        }
        if ($isLogout) {
          $itemClasses .= ' admin-nav-logout';
        }
        ?>
        <a class="<?php echo htmlspecialchars($itemClasses, ENT_QUOTES, 'UTF-8'); ?>" href="<?php echo htmlspecialchars(appPath((string)$item['path']), ENT_QUOTES, 'UTF-8'); ?>" <?php if ($isActive): ?>aria-current="page"<?php endif; ?>>
          <?php echo $renderPortalIcon((string)$item['icon']); ?>
          <span><?php echo htmlspecialchars((string)$item['label'], ENT_QUOTES, 'UTF-8'); ?></span>
        </a>
      <?php endforeach; ?>
    </nav>
  </div>

  <div class="admin-sidebar-mobile" aria-label="<?php echo htmlspecialchars(ucfirst($portalRole) . ' mobile navigation', ENT_QUOTES, 'UTF-8'); ?>">
    <div class="admin-sidebar-mobile-bar">
      <div class="admin-sidebar-mobile-brand">
        <strong>QueenLib</strong>
        <span><?php echo htmlspecialchars($portalBrandSub, ENT_QUOTES, 'UTF-8'); ?></span>
      </div>
      <div class="admin-sidebar-mobile-current"><?php echo htmlspecialchars($portalCurrentPageLabel, ENT_QUOTES, 'UTF-8'); ?></div>
      <button
        type="button"
        class="admin-sidebar-mobile-toggle"
        aria-expanded="false"
        aria-controls="<?php echo htmlspecialchars($portalMobileDrawerId, ENT_QUOTES, 'UTF-8'); ?>"
        aria-label="Toggle navigation menu"
      >
        <span aria-hidden="true"></span>
        <span aria-hidden="true"></span>
        <span aria-hidden="true"></span>
      </button>
    </div>

    <button type="button" class="admin-sidebar-mobile-overlay" aria-label="Close navigation menu" hidden></button>

    <div id="<?php echo htmlspecialchars($portalMobileDrawerId, ENT_QUOTES, 'UTF-8'); ?>" class="admin-sidebar-mobile-drawer" aria-hidden="true" inert>
      <div class="admin-sidebar-profile">
        <span class="admin-sidebar-avatar" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 12C14.21 12 16 10.21 16 8C16 5.79 14.21 4 12 4C9.79 4 8 5.79 8 8C8 10.21 9.79 12 12 12Z" stroke="currentColor" stroke-width="1.8" />
            <path d="M4.93 20C5.83 17.1 8.57 15 12 15C15.43 15 18.17 17.1 19.07 20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
          </svg>
        </span>
        <div>
          <div class="admin-sidebar-name"><?php echo htmlspecialchars($portalIdentityName, ENT_QUOTES, 'UTF-8'); ?></div>
          <?php if ($portalIdentityMeta !== ''): ?>
            <div class="admin-sidebar-role"><?php echo htmlspecialchars($portalIdentityMeta, ENT_QUOTES, 'UTF-8'); ?></div>
          <?php endif; ?>
        </div>
      </div>

      <nav class="admin-nav" aria-label="<?php echo htmlspecialchars(ucfirst($portalRole) . ' drawer links', ENT_QUOTES, 'UTF-8'); ?>">
        <?php foreach ($portalNavItems as $item): ?>
          <?php
          $isActive = $portalCurrentPage === (string)$item['key'];
          $isLogout = !empty($item['logout']);
          $itemClasses = 'admin-nav-item';
          if ($isActive) {
            $itemClasses .= ' is-active';
          }
          if ($isLogout) {
            $itemClasses .= ' admin-nav-logout';
          }
          ?>
          <a class="<?php echo htmlspecialchars($itemClasses, ENT_QUOTES, 'UTF-8'); ?>" href="<?php echo htmlspecialchars(appPath((string)$item['path']), ENT_QUOTES, 'UTF-8'); ?>" <?php if ($isActive): ?>aria-current="page"<?php endif; ?>>
            <?php echo $renderPortalIcon((string)$item['icon']); ?>
            <span><?php echo htmlspecialchars((string)$item['label'], ENT_QUOTES, 'UTF-8'); ?></span>
          </a>
        <?php endforeach; ?>
      </nav>
    </div>
  </div>
</aside>
<script src="<?php echo htmlspecialchars(appPath('public/js/main.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
