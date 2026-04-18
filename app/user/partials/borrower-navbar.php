<?php

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) === realpath((string)$_SERVER['SCRIPT_FILENAME'])) {
  http_response_code(404);
  exit('Not Found');
}

$currentPageKey = isset($currentPage) ? (string)$currentPage : '';
$firstName = trim((string)($user['first_name'] ?? 'Borrower'));
if ($firstName === '') {
  $firstName = 'Borrower';
}

$borrowerNavItems = [
  [
    'key' => 'dashboard',
    'label' => 'Dashboard',
    'path' => 'index.php',
  ],
  [
    'key' => 'catalog',
    'label' => 'Catalog',
    'path' => 'catalog.php',
  ],
  [
    'key' => 'reservations',
    'label' => 'Reservations',
    'path' => 'reservations.php',
  ],
  [
    'key' => 'history',
    'label' => 'Loan History',
    'path' => 'history.php',
  ],
  [
    'key' => 'account',
    'label' => 'Settings',
    'path' => 'account.php',
  ],
];
?>
<nav class="borrower-navbar" aria-label="Borrower navigation">
  <a href="<?php echo htmlspecialchars(appPath('index.php'), ENT_QUOTES, 'UTF-8'); ?>" class="borrower-brand">QueenLib</a>
  <div class="borrower-nav-right">
    <span class="borrower-greeting">Welcome, <?php echo htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8'); ?>!</span>
    <?php foreach ($borrowerNavItems as $item): ?>
      <?php $isActive = $currentPageKey === (string)$item['key']; ?>
      <a
        href="<?php echo htmlspecialchars(appPath((string)$item['path']), ENT_QUOTES, 'UTF-8'); ?>"
        class="borrower-nav-link<?php echo $isActive ? ' is-active' : ''; ?>"
        <?php if ($isActive): ?>aria-current="page"<?php endif; ?>><?php echo htmlspecialchars((string)$item['label'], ENT_QUOTES, 'UTF-8'); ?></a>
    <?php endforeach; ?>
    <a href="<?php echo htmlspecialchars(appPath('logout.php'), ENT_QUOTES, 'UTF-8'); ?>" class="borrower-nav-link is-logout">Logout</a>
  </div>
</nav>
