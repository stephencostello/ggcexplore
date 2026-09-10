<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_login();

$settings = get_settings();
$links = get_admin_links();
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Links — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="page">
  <?php $adminTitle = 'Links'; $activeNav = 'links'; require __DIR__ . '/../includes/admin-nav.php'; ?>

  <div class="container">

    <?php if ($flash): ?>
      <div class="alert alert-success"><?= h($flash) ?></div>
    <?php endif; ?>

    <div class="top-actions">
      <a href="link-form.php" class="btn btn-primary">+ Add link</a>
    </div>

    <?php if (empty($links)): ?>
      <div class="empty-state">No links yet. Add your first one above.</div>
    <?php else: ?>
      <ul class="admin-links">
        <?php
        $lastIndex = count($links) - 1;
        foreach ($links as $i => $link):
            $isFirst = $i === 0;
            $isLast = $i === $lastIndex;
        ?>
          <li class="admin-link-row<?= $link['visible'] ? '' : ' admin-link-row--hidden' ?>">
            <?php if ($link['image_filename']): ?>
              <img class="admin-link-thumb" src="<?= h(UPLOADS_URL) ?>/links/<?= h($link['image_filename']) ?>" alt="">
            <?php else: ?>
              <div class="admin-link-thumb"></div>
            <?php endif; ?>

            <div class="admin-link-info">
              <div class="admin-link-name">
                <?= h($link['name']) ?>
                <?php if (!$link['visible']): ?><span class="tag" style="background:#4A453D;">HIDDEN</span><?php endif; ?>
              </div>
              <div class="admin-link-url"><?= h($link['url']) ?></div>
            </div>

            <div class="admin-link-actions">
              <div class="icon-btn-row">
                <form method="post" action="move.php">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $link['id'] ?>">
                  <input type="hidden" name="direction" value="up">
                  <button class="icon-btn" title="Move up" <?= $isFirst ? 'disabled' : '' ?>>↑</button>
                </form>
                <form method="post" action="move.php">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $link['id'] ?>">
                  <input type="hidden" name="direction" value="down">
                  <button class="icon-btn" title="Move down" <?= $isLast ? 'disabled' : '' ?>>↓</button>
                </form>
                <a class="icon-btn" href="link-form.php?id=<?= (int) $link['id'] ?>" title="Edit">✎</a>
              </div>
              <div class="icon-btn-row">
                <form method="post" action="visibility.php">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $link['id'] ?>">
                  <input type="hidden" name="visible" value="<?= $link['visible'] ? '0' : '1' ?>">
                  <button class="icon-btn" title="<?= $link['visible'] ? 'Hide' : 'Show' ?>"><?= $link['visible'] ? '🙈' : '👁' ?></button>
                </form>
                <form method="post" action="delete.php" onsubmit="return confirm('Move &quot;<?= h(addslashes($link['name'])) ?>&quot; to trash?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $link['id'] ?>">
                  <button class="icon-btn" title="Delete">🗑</button>
                </form>
              </div>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

  </div>
</div>
</body>
</html>
