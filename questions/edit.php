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
<main class="container home-dark">
  <div class="edit-form">
    <h1>Edit Question</h1>
    
    <?php if ($error): ?>
      <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="post" enctype="multipart/form-data">
      <div class="form-group">
        <label for="title">Title</label>
        <input type="text" id="title" name="title" value="<?php echo e($q['title']); ?>" required>
      </div>
      
      <div class="form-group">
        <label for="module_id">Module</label>
        <select id="module_id" name="module_id" required>
          <?php foreach($modules as $m): $sel = ($m['module_id']==$q['module_id'])?'selected':''; ?>
            <option value="<?php echo (int)$m['module_id']; ?>" <?php echo $sel; ?>><?php echo e($m['module_name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      
      <div class="form-group">
        <label for="body">Body</label>
        <textarea id="body" name="body" rows="8" required><?php echo e($q['content']); ?></textarea>
      </div>
      
      <div class="form-group">
        <label for="image">Image</label>
        <input type="file" id="image" name="image" accept="image/*">
        <?php if (!empty($q['image'])): ?>
          <div class="current-image">
            <img src="<?php echo BASE_URL; ?>assets/uploads/posts/<?php echo e($q['image']); ?>" alt="Current image">
            <span>Current image</span>
          </div>
        <?php endif; ?>
      </div>
      
      <fieldset class="form-group">
        <legend>Tags</legend>
        <div class="checkbox-group">
          <?php foreach($allTags as $t): $checked = in_array((int)$t['id'], $selectedTagIds, true) ? 'checked' : ''; ?>
            <label>
              <input type="checkbox" name="tags[]" value="<?php echo (int)$t['id']; ?>" <?php echo $checked; ?>>
              <span><?php echo e($t['name']); ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      </fieldset>
      
      <div class="form-actions">
        <a href="<?php echo BASE_URL; ?>questions/view.php?id=<?php echo (int)$q['id']; ?>" class="btn-cancel">Cancel</a>
        <button type="submit" class="btn-submit">Save Changes</button>
      </div>
    </form>
  </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
