<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_login();

prune_trash();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $id = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($id && $action === 'restore') {
        restore_link($id);
        $_SESSION['flash'] = 'Link restored.';
        redirect('dashboard.php');
    } elseif ($id && $action === 'delete_forever') {
        permanently_delete_link($id);
        $_SESSION['flash'] = 'Link permanently deleted.';
        redirect('trash.php');
    }
}

$trashed = get_trashed_links();
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Trash — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="page">
  <div class="admin-header">
    <h1 class="admin-title">Trash</h1>
    <a href="logout.php" class="icon-btn" title="Log out" aria-label="Log out">⏻</a>
  </div>

  <nav class="admin-nav">
    <a href="dashboard.php">Links</a>
    <a href="trash.php" class="active">Trash</a>
    <a href="settings.php">Settings</a>
    <a href="../index.php" target="_blank">View page ↗</a>
  </nav>

  <div class="container">
    <?php if ($flash): ?><div class="alert alert-success"><?= h($flash) ?></div><?php endif; ?>

    <p class="trash-note">Deleted links stay here for <?= TRASH_RETENTION_DAYS ?> days, then are removed automatically.</p>

    <?php if (empty($trashed)): ?>
      <div class="empty-state">Trash is empty.</div>
    <?php else: ?>
      <ul class="admin-links">
        <?php foreach ($trashed as $link): ?>
          <li class="admin-link-row">
            <?php if ($link['image_filename']): ?>
              <img class="admin-link-thumb" src="<?= h(UPLOADS_URL) ?>/links/<?= h($link['image_filename']) ?>" alt="">
            <?php else: ?>
              <div class="admin-link-thumb"></div>
            <?php endif; ?>
            <div class="admin-link-info">
              <div class="admin-link-name"><?= h($link['name']) ?></div>
              <div class="admin-link-url">Deleted <?= h($link['deleted_at']) ?></div>
            </div>
            <div class="admin-link-actions">
              <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $link['id'] ?>">
                <input type="hidden" name="action" value="restore">
                <button class="btn btn-secondary btn-small">Restore</button>
              </form>
              <form method="post" onsubmit="return confirm('Permanently delete this link? This cannot be undone.');">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $link['id'] ?>">
                <input type="hidden" name="action" value="delete_forever">
                <button class="btn btn-danger btn-small">Delete forever</button>
              </form>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
