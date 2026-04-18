<?php

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) === realpath((string)$_SERVER['SCRIPT_FILENAME'])) {
  http_response_code(404);
  exit('Not Found');
}

$portalRole = strtolower(trim((string)($portalRole ?? 'borrower')));
if (!in_array($portalRole, ['borrower', 'librarian'], true)) {
  $portalRole = 'borrower';
}

$portalCurrentPage = strtolower(trim((string)($portalCurrentPage ?? '')));
$portalIdentityName = trim((string)($portalIdentityName ?? ''));
$portalIdentityMeta = trim((string)($portalIdentityMeta ?? ''));
$portalBrandSub = trim((string)($portalBrandSub ?? ''));

if ($portalIdentityName === '') {
  $portalIdentityName = $portalRole === 'librarian' ? 'Librarian User' : 'Borrower User';
}

if ($portalBrandSub === '') {
  $portalBrandSub = $portalRole === 'librarian' ? 'Librarian Portal' : 'Borrower Portal';
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
    case 'fines':
      return '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.6"/><path d="M12 8V16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>';
    case 'logout':
      return '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15 7L20 12L15 17" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M20 12H9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M4 4H9V20H4" stroke="currentColor" stroke-width="1.6"/></svg>';
    default:
      return '';
  }
};

$portalNavItems = $navByRole[$portalRole];
?>
<aside class="admin-sidebar" aria-label="<?php echo htmlspecialchars(ucfirst($portalRole) . ' navigation', ENT_QUOTES, 'UTF-8'); ?>">
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

  <nav class="admin-nav">
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
</aside>
