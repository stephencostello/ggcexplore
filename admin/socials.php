<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_login();

$settings = get_settings();
$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $fields = [];
    foreach (SOCIAL_PLATFORMS as $key => $meta) {
        $col = social_column($key);
        $val = trim($_POST[$col] ?? '');
        if ($val !== '' && !is_valid_url($val)) {
            $errors[] = $meta['label'] . ' must be a full URL, including https://';
        }
        $fields[$col] = $val !== '' ? $val : null;
    }

    if (!$errors) {
        update_settings($fields);
        $success = 'Socials updated.';
        $settings = get_settings();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Socials — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="page">
  <?php $adminTitle = 'Socials'; $activeNav = 'socials'; require __DIR__ . '/../includes/admin-nav.php'; ?>

  <div class="container">
    <?php foreach ($errors as $error): ?><div class="alert alert-error"><?= h($error) ?></div><?php endforeach; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= h($success) ?></div><?php endif; ?>

    <div class="card">
      <form method="post">
        <?= csrf_field() ?>
        <p class="field-hint" style="margin-bottom:16px;">Shown as a row of icons under the intro text, in this order. Leave a field blank to hide that icon.</p>

        <?php foreach (SOCIAL_PLATFORMS as $key => $meta): $col = social_column($key); ?>
          <div class="field">
            <label for="<?= h($col) ?>"><?= h($meta['label']) ?></label>
            <input type="url" id="<?= h($col) ?>" name="<?= h($col) ?>" value="<?= h($settings[$col] ?? '') ?>" placeholder="https://">
          </div>
        <?php endforeach; ?>

        <button type="submit" class="btn btn-primary">Save socials</button>
      </form>
    </div>
  </div>
</div>
</body>
</html>
