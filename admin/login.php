<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$settings = get_settings();
if (empty($settings['setup_complete'])) {
    redirect('../install.php');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $password = $_POST['password'] ?? '';
    if (attempt_login($password)) {
        redirect('dashboard.php');
    }
    $error = 'Incorrect password. Please try again.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin login — <?= h($settings['church_name']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="page">
  <div class="container">
    <div class="profile">
      <?php if ($settings['logo_filename']): ?>
        <img class="profile-logo" src="<?= h(UPLOADS_URL) ?>/logo/<?= h($settings['logo_filename']) ?>" alt="">
      <?php endif; ?>
      <h1 class="profile-name">Admin login</h1>
    </div>

    <div class="card">
      <?php if ($error): ?><div class="alert alert-error"><?= h($error) ?></div><?php endif; ?>
      <form method="post">
        <?= csrf_field() ?>
        <div class="field">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" required autofocus>
        </div>
        <button type="submit" class="btn btn-primary">Log in</button>
      </form>
    </div>
  </div>
</div>
</body>
</html>
