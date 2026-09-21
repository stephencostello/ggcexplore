<?php
require_once __DIR__ . '/includes/functions.php';

prune_trash();

$settings = get_settings();

// If nobody has completed initial setup yet, send the church staff to the wizard.
if (empty($settings['setup_complete'])) {
    redirect('install.php');
}

$links = get_public_links();
$socials = get_social_links($settings);

$churchName = $settings['church_name'] ?: 'Grace Generation Church';
$intro = $settings['intro_text'] ?? '';
$logo = $settings['logo_filename'] ?? null;

$pageUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://')
    . ($_SERVER['HTTP_HOST'] ?? '') . '/';
// The on-page logo circle always shows the static assets/img/logo.jpg (see
// below) regardless of what's uploaded in Admin -> Settings, so the social
// share preview image follows the same rule for consistency.
$ogImage = $pageUrl . ($logo ? ltrim(UPLOADS_URL, '/') . '/logo/' . $logo : 'assets/img/logo.jpg');
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
<link rel="stylesheet" href="assets/css/style.css?v=<?= css_version() ?>">
</head>
<body class="home">
<div class="hero" role="presentation"></div>
<div class="page">
  <div class="container">

    <div class="profile">
      <!-- Always the static brand mark, regardless of Admin -> Settings ->
           Logo upload (that field still exists but no longer feeds this). -->
      <img class="profile-logo" src="assets/img/logo.jpg" alt="<?= h($churchName) ?> logo">
      <h1 class="profile-name"><?= h($churchName) ?></h1>

      <?php if ($intro): ?>
        <p class="profile-intro"><?= nl2br(h($intro)) ?></p>
      <?php endif; ?>

      <!-- Fixed site URL, independent of church_name/settings — always this. -->
      <a href="http://gracegeneration.co.uk" class="site-url" target="_blank" rel="noopener noreferrer">gracegeneration.co.uk</a>

      <?php if ($socials): ?>
        <div class="socials">
          <?php foreach ($socials as $key => $url): ?>
            <a class="social-icon" href="<?= h($url) ?>" target="_blank" rel="noopener"
               aria-label="<?= h(SOCIAL_PLATFORMS[$key]['label']) ?>"><?= social_icon_svg($key) ?></a>
          <?php endforeach; ?>
        </div>
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

    <!-- Hardcoded, not settings-driven — copyright_text still exists as an
         unused DB column (additive-only migration policy: leave, don't drop). -->
    <div class="site-footer">&copy; <?= date('Y') ?> Grace Generation Church</div>

  </div>
</div>
</body>
</html>
