<?php
require_once __DIR__ . '/../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM questions WHERE id = ?");
$stmt->execute([$id]);
$q = $stmt->fetch();
if (!$q) { echo 'Question not found'; exit; }

// Owner-or-admin gate
if (!is_admin() && (!is_logged_in() || (int)$_SESSION['user_id'] !== (int)$q['user_id'])) { http_response_code(403); echo 'Forbidden'; exit; }

$modules = $pdo->query("SELECT module_id, module_name FROM modules ORDER BY module_name")->fetchAll();
$allTags = $pdo->query("SELECT id, name FROM tags ORDER BY name")->fetchAll();
// Load existing tags for question
$existingTags = $pdo->prepare("SELECT tag_id FROM question_tags WHERE question_id=?");
$existingTags->execute([$id]);
$selectedTagIds = array_map('intval', array_column($existingTags->fetchAll(), 'tag_id'));
$error = '';
if (isPost()) {
  $title = sanitize($_POST['title'] ?? '');
  $content = trim($_POST['body'] ?? '');
  $module_id = (int)($_POST['module_id'] ?? 0);
  $tagIds = array_map('intval', $_POST['tags'] ?? []);

  if (!$title || !$content || !$module_id) {
    $error = 'Please fill all required fields.';
  } else {
    $image = $q['image'];
    if (!empty($_FILES['image']['name'])) {
      $saved = uploadImage($_FILES['image'], UPLOADS_POSTS_DIR);
      if ($saved === false) {
        $error = 'Invalid image file (jpg, png, gif, max 5MB).';
      } else {
        $image = $saved;
      }
    }
    if (!$error) {
      $u = $pdo->prepare("UPDATE questions SET title=?, content=?, image=?, module_id=?, updated_at=NOW() WHERE id=?");
      $u->execute([$title, $content, $image, $module_id, $id]);
      // Update tags
      $pdo->prepare("DELETE FROM question_tags WHERE question_id=?")->execute([$id]);
      if ($tagIds) {
        $ins = $pdo->prepare("INSERT INTO question_tags (question_id, tag_id) VALUES (?, ?)");
        foreach ($tagIds as $tid) { $ins->execute([$id, $tid]); }
      }
      redirect('questions/list.php');
    }
  }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<main class="container">
  <h1>Edit Question</h1>
  <?php if ($error) alert($error, 'error'); ?>
  <form method="post" enctype="multipart/form-data">
    <label>Title <input name="title" value="<?php echo e($q['title']); ?>" required></label>
    <label>Module
      <select name="module_id" required>
        <?php foreach($modules as $m): $sel = ($m['module_id']==$q['module_id'])?'selected':''; ?>
          <option value="<?php echo (int)$m['module_id']; ?>" <?php echo $sel; ?>><?php echo e($m['module_name']); ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Body <textarea name="body" rows="6" required><?php echo e($q['content']); ?></textarea></label>
    <label>Image <input type="file" name="image" accept="image/*"></label>
    <?php if (!empty($q['image'])): ?>
      <p>Current: <img src="<?php echo BASE_URL; ?>assets/uploads/posts/<?php echo e($q['image']); ?>" alt="" style="height:80px"></p>
    <?php endif; ?>
    <fieldset>
      <legend>Tags</legend>
      <?php foreach($allTags as $t): $checked = in_array((int)$t['id'], $selectedTagIds, true) ? 'checked' : ''; ?>
        <label style="display:inline-block;margin-right:12px;">
          <input type="checkbox" name="tags[]" value="<?php echo (int)$t['id']; ?>" <?php echo $checked; ?>> <?php echo e($t['name']); ?>
        </label>
      <?php endforeach; ?>
    </fieldset>
    <button class="primary" type="submit">Save</button>
  </form>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
