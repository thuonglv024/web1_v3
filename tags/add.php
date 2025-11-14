<?php
/**
 * Add Tag Page
 * 
 * Note: Tag creation is handled in:
 * - admin/manage_tags.php (for admins)
 * - tags/list.php (for admins)
 * 
 * This file is kept for potential future use.
 */
require_once __DIR__ . '/../includes/functions.php';
ensure_admin();

// Redirect to admin tag management
redirect('admin/manage_tags.php');
?>
