<!-- Footer section -->
<footer class="footer">
  <div class="container footer-top">
    <!-- About section -->
    <div class="footer-about">
      <h4><?php echo APP_NAME; ?></h4>
      <p>A community-driven Q&A platform for students to ask questions, share knowledge, and learn together.</p>
      <p>Empowering students through collaborative learning.</p>
    </div>
    <!-- Quick links -->
    <div class="footer-connect">
      <h4>Quick Links</h4>
      <ul>
        <li><a href="<?php echo BASE_URL; ?>questions/list.php">Browse Questions</a></li>
        <li><a href="<?php echo BASE_URL; ?>modules/list.php">Modules</a></li>
        <li><a href="<?php echo BASE_URL; ?>tags/list.php">Tags</a></li>
        <li><a href="<?php echo BASE_URL; ?>contact/contact.php">Contact Us</a></li>
      </ul>
    </div>
    <!-- Community links -->
    <div class="footer-connect">
      <h4>Community</h4>
      <ul>
        <li><a href="<?php echo BASE_URL; ?>contributors/leaderboard.php">Top Contributors</a></li>
        <?php if (is_logged_in()): ?>
          <li><a href="<?php echo BASE_URL; ?>users/profile.php">My Profile</a></li>
        <?php else: ?>
          <li><a href="<?php echo BASE_URL; ?>auth/login.php">Login</a></li>
          <li><a href="<?php echo BASE_URL; ?>auth/register.php">Register</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
  <!-- Footer bottom with copyright -->
  <div class="footer-bottom">
    <div class="container">
      <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> | design by Thuong Le Van | COMP1841 Web Development Project</p>
    </div>
  </div>
  <!-- Include JavaScript files -->
  <script src="<?php echo BASE_URL; ?>assets/js/script.js"></script>
  <script src="<?php echo BASE_URL; ?>assets/js/vote.js"></script>
</footer>
</body>
</html>
