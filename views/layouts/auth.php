<?php
/**
 * Layout for login and install pages (no sidebar).
 */
use App\Core\View;
use App\Core\App;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= View::e($title ?? 'CAMS') ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= View::e(App::url('/public/assets/css/app.css')) ?>">
</head>
<body class="auth-body">
<?= $content ?>
</body>
</html>
