<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_login();

$settings = get_settings();
$links = get_admin_links(); // active first (manual order), then hidden/expired
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
<link rel="stylesheet" href="../assets/css/style.css?v=<?= css_version() ?>">
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
        <?php foreach ($links as $link):
            $status = link_status($link); // 'active' | 'hidden' | 'expired'
            $isActive = $status === 'active';
        ?>
          <li class="admin-link-row<?= $isActive ? '' : ' admin-link-row--hidden' ?>"
              <?php if ($isActive): ?>draggable="true" data-id="<?= (int) $link['id'] ?>"<?php endif; ?>>
            <span class="drag-handle<?= $isActive ? '' : ' drag-handle--disabled' ?>"
                  title="<?= $isActive ? 'Drag to reorder' : '' ?>" aria-hidden="true">
              <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><circle cx="8" cy="5" r="1.6"/><circle cx="16" cy="5" r="1.6"/><circle cx="8" cy="12" r="1.6"/><circle cx="16" cy="12" r="1.6"/><circle cx="8" cy="19" r="1.6"/><circle cx="16" cy="19" r="1.6"/></svg>
            </span>

            <?php if ($link['image_filename']): ?>
              <img class="admin-link-thumb" src="<?= h(UPLOADS_URL) ?>/links/<?= h($link['image_filename']) ?>" alt="">
            <?php else: ?>
              <div class="admin-link-thumb"></div>
            <?php endif; ?>

            <div class="admin-link-info">
              <div class="admin-link-name">
                <?= h($link['name']) ?>
                <?php if (!$isActive): ?><span class="tag" style="background:#4A453D;"><?= $status === 'expired' ? 'EXPIRED' : 'INACTIVE' ?></span><?php endif; ?>
              </div>
              <div class="admin-link-url"><?= h($link['url']) ?></div>
              <?php if ($link['expiry_date']): ?>
                <div class="admin-link-expiry">Expires <?= h(date('j M Y', strtotime($link['expiry_date'] . ' 23:59:59'))) ?></div>
              <?php endif; ?>
            </div>

            <a class="icon-btn" href="link-form.php?id=<?= (int) $link['id'] ?>" title="Edit">✎</a>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

  </div>
</div>
<script>
// Drag-and-drop reorder. Scoped exception to this app's "no JS beyond
// native forms" convention: everything else stays plain HTML forms, but
// there's no good non-JS way to do drag reordering. Only rows the server
// marked draggable="true" (active links) take part; hidden/expired rows
// are excluded from both dragging and as drop targets, so nothing can
// land above the active block only to be pushed back down on next load.
(function () {
  var list = document.querySelector('.admin-links');
  if (!list) return;
  var dragEl = null;

  list.addEventListener('dragstart', function (e) {
    var row = e.target.closest('.admin-link-row[draggable="true"]');
    if (!row) return;
    dragEl = row;
    row.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', row.dataset.id);
  });

  list.addEventListener('dragover', function (e) {
    if (!dragEl) return;
    var row = e.target.closest('.admin-link-row[draggable="true"]');
    if (!row || row === dragEl) return;
    e.preventDefault();
    var rect = row.getBoundingClientRect();
    var before = (e.clientY - rect.top) < rect.height / 2;
    list.insertBefore(dragEl, before ? row : row.nextSibling);
  });

  list.addEventListener('drop', function (e) { e.preventDefault(); });

  list.addEventListener('dragend', function () {
    if (dragEl) dragEl.classList.remove('dragging');
    dragEl = null;

    var rows = list.querySelectorAll('.admin-link-row[draggable="true"]');
    var params = new URLSearchParams();
    params.set('csrf_token', <?= json_encode(csrf_token()) ?>);
    rows.forEach(function (row) { params.append('order[]', row.dataset.id); });

    fetch('reorder.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: params.toString()
    });
  });
})();
</script>
</body>
</html>
