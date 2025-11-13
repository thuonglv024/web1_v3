<?php
// Include functions and header/navbar
require_once __DIR__ . '/../includes/functions.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<!-- Coming soon page content -->
<main class="container home-dark" style="min-height:70vh;display:flex;align-items:center;justify-content:center;">
  <div class="card" style="max-width:600px;width:100%;text-align:center;padding:60px 40px;">
    <h1 style="font-size:32px;font-weight:700;margin:0 0 16px;color:#e5e7eb;">This feature is under development</h1>
    <p style="color:#9ca3af;font-size:16px;margin:0 0 32px;line-height:1.6;">
      Please check back later.
    </p>
    <!-- Link back to home -->
    <a href="<?php echo BASE_URL; ?>" class="btn-ask" style="padding:12px 24px;text-decoration:none;display:inline-block;">Back to Home</a>
  </div>
</main>
<!-- Include footer -->
<?php include __DIR__ . '/../includes/footer.php'; ?>