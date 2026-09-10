<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_login();

$settings = get_settings();
$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $form = $_POST['form'] ?? '';

    if ($form === 'branding') {
        $churchName = trim($_POST['church_name'] ?? '');
        $intro = trim($_POST['intro_text'] ?? '');
        $copyright = trim($_POST['copyright_text'] ?? '');
        $removeLogo = !empty($_POST['remove_logo']);

        if ($churchName === '') $errors[] = 'Church name is required.';

        $newLogo = null;
        try {
            $newLogo = handle_image_upload($_FILES['logo'] ?? [], UPLOADS_LOGO_DIR);
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }

        if (!$errors) {
            $finalLogo = $settings['logo_filename'];
            if ($newLogo) {
                delete_image_if_exists($finalLogo, UPLOADS_LOGO_DIR);
                $finalLogo = $newLogo;
            } elseif ($removeLogo) {
                delete_image_if_exists($finalLogo, UPLOADS_LOGO_DIR);
                $finalLogo = null;
            }
            update_settings([
                'church_name' => $churchName,
                'intro_text' => $intro,
                'copyright_text' => $copyright,
                'logo_filename' => $finalLogo,
            ]);
            $success = 'Branding updated.';
            $settings = get_settings();
        } elseif ($newLogo) {
            delete_image_if_exists($newLogo, UPLOADS_LOGO_DIR);
        }
    }

    if ($form === 'password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!password_verify($current, $settings['admin_password_hash'] ?? '')) {
            $errors[] = 'Current password is incorrect.';
        }
        if (strlen($new) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        }
        if ($new !== $confirm) {
            $errors[] = 'New passwords do not match.';
        }

        if (!$errors) {
            update_settings(['admin_password_hash' => password_hash($new, PASSWORD_DEFAULT)]);
            $success = 'Password changed.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Settings — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="page">
  <div class="admin-header">
    <h1 class="admin-title">Settings</h1>
    <a href="logout.php" class="icon-btn" title="Log out" aria-label="Log out">⏻</a>
  </div>

  <nav class="admin-nav">
    <a href="dashboard.php">Links</a>
    <a href="trash.php">Trash</a>
    <a href="settings.php" class="active">Settings</a>
    <a href="../index.php" target="_blank">View page ↗</a>
  </nav>

  <div class="container">
    <?php foreach ($errors as $error): ?><div class="alert alert-error"><?= h($error) ?></div><?php endforeach; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= h($success) ?></div><?php endif; ?>

    <p class="section-title">Branding</p>
    <div class="card">
      <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="form" value="branding">

        <div class="field">
          <label for="church_name">Church name</label>
          <input type="text" id="church_name" name="church_name" value="<?= h($settings['church_name']) ?>" required>
        </div>

        <div class="field">
          <label>Logo</label>
          <?php if ($settings['logo_filename']): ?>
            <img class="logo-preview" src="<?= h(UPLOADS_URL) ?>/logo/<?= h($settings['logo_filename']) ?>" alt="">
            <div class="checkbox-row">
              <input type="checkbox" id="remove_logo" name="remove_logo" value="1">
              <label for="remove_logo">Remove current logo</label>
            </div>
          <?php endif; ?>
          <input type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/webp">
          <p class="field-hint">JPG, PNG or WebP, up to 2MB. Square works best.</p>
        </div>

        <div class="field">
          <label for="intro_text">Short intro paragraph</label>
          <textarea id="intro_text" name="intro_text"><?= h($settings['intro_text']) ?></textarea>
        </div>

        <div class="field">
          <label for="copyright_text">Copyright / footer message</label>
          <input type="text" id="copyright_text" name="copyright_text" value="<?= h($settings['copyright_text']) ?>">
        </div>

        <button type="submit" class="btn btn-primary">Save branding</button>
      </form>
    </div>

    <p class="section-title">Change password</p>
    <div class="card">
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="form" value="password">

        <div class="field">
          <label for="current_password">Current password</label>
          <input type="password" id="current_password" name="current_password" required>
        </div>
        <div class="field">
          <label for="new_password">New password</label>
          <input type="password" id="new_password" name="new_password" required minlength="8">
        </div>
        <div class="field">
          <label for="confirm_password">Confirm new password</label>
          <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
        </div>

        <button type="submit" class="btn btn-primary">Change password</button>
      </form>
    </div>
  </div>
</div>
</body>
</html>
