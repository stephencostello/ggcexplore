<?php
/**
 * includes/admin-nav.php
 * Shared admin header + primary nav. Before including, set:
 *   $adminTitle — heading text
 *   $activeNav  — one of: links, socials, settings, trash
 */
$activeNav = $activeNav ?? '';
$navItems = [
    'links' => ['dashboard.php', 'Links'],
    'socials' => ['socials.php', 'Socials'],
    'settings' => ['settings.php', 'Settings'],
    'trash' => ['trash.php', 'Trash'],
];
?>
<div class="admin-header">
  <h1 class="admin-title"><?= h($adminTitle ?? '') ?></h1>
  <a href="logout.php" class="icon-btn" title="Log out" aria-label="Log out">⏻</a>
</div>

<nav class="admin-nav">
  <?php foreach ($navItems as $key => [$href, $label]): ?>
    <a href="<?= $href ?>"<?= $key === $activeNav ? ' class="active"' : '' ?>><?= $label ?></a>
  <?php endforeach; ?>
  <a href="../index.php" target="_blank" rel="noopener" class="btn btn-secondary btn-small admin-nav-view">View explorer ↗</a>
</nav>
