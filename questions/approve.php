<?php
require_once __DIR__ . '/../includes/functions.php';
ensure_admin();

$id = (int)($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

if (!$id || !in_array($action, ['approve', 'reject'], true)) {
  redirect('admin/manage_questions.php');
}

// Check if question exists
$stmt = $pdo->prepare("SELECT id, title FROM questions WHERE id=?");
$stmt->execute([$id]);
$question = $stmt->fetch();

if (!$question) {
  redirect('admin/manage_questions.php');
}

// Update status
$newStatus = $action === 'approve' ? 'approved' : 'rejected';
$update = $pdo->prepare("UPDATE questions SET status=?, updated_at=NOW() WHERE id=?");
$update->execute([$newStatus, $id]);

// Redirect back
redirect('admin/manage_questions.php?success=' . urlencode("Question #{$id} has been {$newStatus}."));
