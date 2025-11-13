<?php 
// Include functions file
require_once __DIR__ . '/functions.php'; 
?>
<!-- HTML document declaration -->
<!doctype html>
<html lang="en">
<head>
  <!-- Meta tags for charset and viewport -->
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <!-- Page title using app name -->
  <title><?php echo e(APP_NAME); ?></title>
  <!-- Link to CSS file with cache busting -->
  <?php $cssPath = __DIR__ . '/../assets/css/style.css'; $cssVer = is_file($cssPath) ? filemtime($cssPath) : time(); ?>
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css?v=<?php echo $cssVer; ?>" />
  <!-- Set BASE_URL in JavaScript -->
  <script>window.BASE_URL = "<?php echo BASE_URL; ?>";</script>
</head>
<body>
  <?php
  // Display flash messages
  if (isset($_SESSION['flash_message'])) {
    $msg = $_SESSION['flash_message'];
    $type = $_SESSION['flash_type'] ?? 'success';
    echo "<div class='flash-message flash-{$type}'>" . e($msg) . "</div>";
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
  }
  ?>
