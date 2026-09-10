<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';

$settings = get_settings();

// Already set up — nothing to do here.
if (!empty($settings['setup_complete'])) {
    redirect('admin/login.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $churchName = trim($_POST['church_name'] ?? '');
    $intro = trim($_POST['intro_text'] ?? '');
    $copyright = trim($_POST['copyright_text'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if ($churchName === '') $errors[] = 'Church name is required.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $passwordConfirm) $errors[] = 'Passwords do not match.';

    $logoFilename = null;
    try {
        $logoFilename = handle_image_upload($_FILES['logo'] ?? [], UPLOADS_LOGO_DIR);
    } catch (RuntimeException $e) {
        $errors[] = $e->getMessage();
    }

    if (!$errors) {
        update_settings([
            'church_name' => $churchName,
            'intro_text' => $intro,
            'copyright_text' => $copyright,
            'logo_filename' => $logoFilename,
            'admin_password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'setup_complete' => 1,
        ]);
        redirect('admin/login.php');
    } elseif ($logoFilename) {
        delete_image_if_exists($logoFilename, UPLOADS_LOGO_DIR);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Set up your link page</title>
<link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="page">
  <div class="container">
    <div class="profile">
      <h1 class="profile-name">Set up your link page</h1>
      <p class="profile-intro">This runs once. Fill this in to create your admin password and page branding.</p>
    </div>

    <div class="card">
      <?php foreach ($errors as $error): ?>
        <div class="alert alert-error"><?= h($error) ?></div>
      <?php endforeach; ?>

      <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <div class="field">
          <label for="church_name">Church name</label>
          <input type="text" id="church_name" name="church_name" value="<?= h($_POST['church_name'] ?? 'Grace Generation Church') ?>" required>
        </div>

        <div class="field">
          <label for="logo">Logo (optional, square works best)</label>
          <input type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/webp">
          <p class="field-hint">JPG, PNG or WebP, up to 2MB.</p>
        </div>

        <div class="field">
          <label for="intro_text">Short intro paragraph</label>
          <textarea id="intro_text" name="intro_text" placeholder="A few words about the church or this page."><?= h($_POST['intro_text'] ?? '') ?></textarea>
        </div>

        <div class="field">
          <label for="copyright_text">Copyright / footer message</label>
          <input type="text" id="copyright_text" name="copyright_text" value="<?= h($_POST['copyright_text'] ?? '© ' . date('Y') . ' Grace Generation Church') ?>">
        </div>

        <div class="field">
          <label for="password">Admin password</label>
          <input type="password" id="password" name="password" required minlength="8">
          <p class="field-hint">At least 8 characters. This is the one shared login for admins.</p>
        </div>

        <div class="field">
          <label for="password_confirm">Confirm password</label>
          <input type="password" id="password_confirm" name="password_confirm" required minlength="8">
        </div>

        <button type="submit" class="btn btn-primary">Create page</button>
      </form>
    </div>
  </div>
</div>
</body>
</html>
