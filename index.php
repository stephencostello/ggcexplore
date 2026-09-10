<?php
require_once __DIR__ . '/includes/functions.php';

prune_trash();

$settings = get_settings();

// If nobody has completed initial setup yet, send the church staff to the wizard.
if (empty($settings['setup_complete'])) {
    redirect('install.php');
}

$links = get_public_links();

$churchName = $settings['church_name'] ?: 'Grace Generation Church';
$intro = $settings['intro_text'] ?? '';
$copyright = $settings['copyright_text'] ?? '';
$logo = $settings['logo_filename'] ?? null;

$pageUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://')
    . ($_SERVER['HTTP_HOST'] ?? '') . '/';
$ogImage = $logo ? $pageUrl . ltrim(UPLOADS_URL, '/') . '/logo/' . $logo : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($churchName) ?></title>
<meta name="description" content="<?= h($intro) ?>">

<!-- Open Graph / social share preview -->
<meta property="og:type" content="website">
<meta property="og:title" content="<?= h($churchName) ?>">
<meta property="og:description" content="<?= h($intro) ?>">
<meta property="og:url" content="<?= h($pageUrl) ?>">
<?php if ($ogImage): ?><meta property="og:image" content="<?= h($ogImage) ?>"><?php endif; ?>
<meta name="twitter:card" content="summary<?= $ogImage ? '_large_image' : '' ?>">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="page">
  <div class="container">

    <div class="profile">
      <?php if ($logo): ?>
        <img class="profile-logo" src="<?= h(UPLOADS_URL) ?>/logo/<?= h($logo) ?>" alt="<?= h($churchName) ?> logo">
      <?php else: ?>
        <div class="profile-logo profile-logo--placeholder"><?= h(mb_substr($churchName, 0, 1)) ?></div>
      <?php endif; ?>
      <h1 class="profile-name"><?= h($churchName) ?></h1>
      <?php if ($intro): ?>
        <p class="profile-intro"><?= nl2br(h($intro)) ?></p>
      <?php endif; ?>
    </div>

    <?php if (empty($links)): ?>
      <div class="empty-state">Links are on their way — check back soon.</div>
    <?php else: ?>
      <ul class="links">
        <?php foreach ($links as $link): ?>
          <li>
            <a class="link-card"
               href="go.php?id=<?= (int) $link['id'] ?>" target="_blank" rel="noopener">
              <?php if ($link['image_filename']): ?>
                <img class="link-image" src="<?= h(UPLOADS_URL) ?>/links/<?= h($link['image_filename']) ?>" alt="">
              <?php endif; ?>
              <div class="link-body">
                <p class="link-name"><?= h($link['name']) ?></p>
                <?php if ($link['description']): ?>
                  <p class="link-description"><?= h($link['description']) ?></p>
                <?php endif; ?>
              </div>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <?php if ($copyright): ?>
      <div class="site-footer"><?= h($copyright) ?></div>
    <?php endif; ?>

  </div>
</div>
</body>
</html>
