<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');
$payload = json_decode(file_get_contents('php://input'), true) ?: [];

if (!is_logged_in()) {
  http_response_code(401);
  echo json_encode(['ok'=>false,'error'=>'AUTH']);
  exit;
}

$qid = (int)($payload['postId'] ?? 0);
$type = $payload['type'] ?? '';
if (!$qid || !in_array($type,['up','down'], true)) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>'BAD_REQUEST']);
  exit;
}

$userId = (int)$_SESSION['user_id'];
$val = $type === 'up' ? 1 : -1;

// Ensure question exists
$q = $pdo->prepare("SELECT id FROM questions WHERE id=? AND status='approved'");
$q->execute([$qid]);
if (!$q->fetch()) {
  http_response_code(404);
  echo json_encode(['ok'=>false,'error'=>'NOT_FOUND']);
  exit;
}

// Upsert vote
$pdo->beginTransaction();
try {
  $sel = $pdo->prepare("SELECT value FROM question_votes WHERE user_id=? AND question_id=?");
  $sel->execute([$userId, $qid]);
  $existing = $sel->fetchColumn();

  $newState = null;
  if ($existing === false) {
    $ins = $pdo->prepare("INSERT INTO question_votes (user_id, question_id, value, created_at) VALUES (?,?,?,NOW())");
    $ins->execute([$userId, $qid, $val]);
    $newState = $type;
  } elseif ((int)$existing === $val) {
    // same vote -> remove (toggle off)
    $del = $pdo->prepare("DELETE FROM question_votes WHERE user_id=? AND question_id=?");
    $del->execute([$userId, $qid]);
    $newState = null;
  } else {
    $upd = $pdo->prepare("UPDATE question_votes SET value=?, created_at=NOW() WHERE user_id=? AND question_id=?");
    $upd->execute([$val, $userId, $qid]);
    $newState = $type;
  }

  $scoreStmt = $pdo->prepare("SELECT COALESCE(SUM(value),0) FROM question_votes WHERE question_id=?");
  $scoreStmt->execute([$qid]);
  $score = (int)$scoreStmt->fetchColumn();
  $pdo->commit();
  echo json_encode(['ok'=>true,'score'=>$score,'state'=>$newState]);
} catch (Throwable $e) {
  $pdo->rollBack();
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'SERVER']);
}
