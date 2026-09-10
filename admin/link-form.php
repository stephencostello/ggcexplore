<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$link = $id ? get_link($id) : null;
if ($id && (!$link || $link['deleted_at'] !== null)) {
    redirect('dashboard.php');
}
$isEdit = $link !== null;

$errors = [];
$values = $link ?: ['name' => '', 'url' => '', 'description' => '', 'pinned' => 0, 'visible' => 1, 'image_filename' => null];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $values['name'] = trim($_POST['name'] ?? '');
    $values['url'] = trim($_POST['url'] ?? '');
    $values['description'] = trim($_POST['description'] ?? '');
    $values['pinned'] = !empty($_POST['pinned']) ? 1 : 0;
    $values['visible'] = !empty($_POST['visible']) ? 1 : 0;
    $removeImage = !empty($_POST['remove_image']);

    if ($values['name'] === '') $errors[] = 'Name is required.';
    if ($values['url'] === '' || !is_valid_url($values['url'])) $errors[] = 'Please enter a valid URL, including https://';

    $newImageFilename = null;
    try {
        $newImageFilename = handle_image_upload($_FILES['image'] ?? [], UPLOADS_LINKS_DIR);
    } catch (RuntimeException $e) {
        $errors[] = $e->getMessage();
    }

    if (!$errors) {
        $finalImage = $values['image_filename'] ?? null;
        if ($newImageFilename) {
            delete_image_if_exists($finalImage, UPLOADS_LINKS_DIR);
            $finalImage = $newImageFilename;
        } elseif ($removeImage) {
            delete_image_if_exists($finalImage, UPLOADS_LINKS_DIR);
            $finalImage = null;
        }

        $payload = [
            'name' => $values['name'],
            'url' => $values['url'],
            'description' => $values['description'] ?: null,
            'pinned' => $values['pinned'],
            'visible' => $values['visible'],
            'image_filename' => $finalImage,
        ];

        if ($isEdit) {
            update_link($id, $payload);
            $_SESSION['flash'] = 'Link updated.';
        } else {
            create_link($payload);
            $_SESSION['flash'] = 'Link added.';
        }
        redirect('dashboard.php');
    } else {
        // keep the newly uploaded image reference out of errors flow to avoid orphaning it silently
        if ($newImageFilename) {
            delete_image_if_exists($newImageFilename, UPLOADS_LINKS_DIR);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $isEdit ? 'Edit link' : 'Add link' ?> — Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="page">
  <div class="admin-header">
    <h1 class="admin-title serif"><?= $isEdit ? 'Edit link' : 'Add link' ?></h1>
  </div>

  <div class="container">
    <?php foreach ($errors as $error): ?>
      <div class="alert alert-error"><?= h($error) ?></div>
    <?php endforeach; ?>

    <div class="card">
      <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <div class="field">
          <label for="name">Name</label>
          <input type="text" id="name" name="name" value="<?= h($values['name']) ?>" required placeholder="e.g. Visit our website">
        </div>

        <div class="field">
          <label for="url">URL</label>
          <input type="url" id="url" name="url" value="<?= h($values['url']) ?>" required placeholder="https://">
        </div>

        <div class="field">
          <label for="description">Description (optional)</label>
          <textarea id="description" name="description" placeholder="A short line about where this link goes"><?= h($values['description'] ?? '') ?></textarea>
        </div>

        <div class="field">
          <label for="image">Image (optional, 16:9 works best)</label>
          <?php if (!empty($values['image_filename'])): ?>
            <img src="<?= h(UPLOADS_URL) ?>/links/<?= h($values['image_filename']) ?>" alt="" style="width:100%;max-width:220px;aspect-ratio:16/9;object-fit:cover;border-radius:8px;margin-bottom:8px;">
            <div class="checkbox-row">
              <input type="checkbox" id="remove_image" name="remove_image" value="1">
              <label for="remove_image">Remove current image</label>
            </div>
          <?php endif; ?>
          <input type="file" id="image" name="image" accept="image/png,image/jpeg,image/webp">
          <p class="field-hint">JPG, PNG or WebP, up to 2MB. Uploading a new image replaces the current one.</p>
        </div>

        <div class="checkbox-row">
          <input type="checkbox" id="visible" name="visible" value="1" <?= $values['visible'] ? 'checked' : '' ?>>
          <label for="visible">Visible on the public page</label>
        </div>

        <div class="checkbox-row">
          <input type="checkbox" id="pinned" name="pinned" value="1" <?= $values['pinned'] ? 'checked' : '' ?>>
          <label for="pinned">Pin to top (only one link can be pinned at a time)</label>
        </div>

        <div class="btn-row">
          <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save changes' : 'Add link' ?></button>
          <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </div>
</div>
</body>
</html>
