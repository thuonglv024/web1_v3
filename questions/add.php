<?php
require_once __DIR__ . '/../includes/functions.php';

ensure_login();

$modules = $pdo->query("SELECT module_id, module_name FROM modules ORDER BY module_name")->fetchAll();
$allTags = $pdo->query("SELECT id, name FROM tags ORDER BY name")->fetchAll();
$error = '';
if (isPost()) {
  $title = sanitize($_POST['title'] ?? '');
  $content = trim($_POST['body'] ?? '');
  $module_id = (int)($_POST['module_id'] ?? 0);
  $user_id = (int)($_SESSION['user_id'] ?? 1); // default to 1 if no auth
  $tagIds = array_map('intval', $_POST['tags'] ?? []);

  if (!$title || !$content || !$module_id) {
    $error = 'Please fill all required fields.';
  } else {
    $image = null;
    if (!empty($_FILES['image']['name'])) {
      $saved = uploadImage($_FILES['image'], UPLOADS_POSTS_DIR);
      if ($saved === false) {
        $error = 'Invalid image file (jpg, png, gif, max 5MB).';
      } else {
        $image = $saved;
      }
    }
    if (!$error) {
      $stmt = $pdo->prepare("INSERT INTO questions (title, content, image, user_id, module_id, status) VALUES (?,?,?,?,?, 'pending')");
      $stmt->execute([$title, $content, $image, $user_id, $module_id]);
      $qid = (int)$pdo->lastInsertId();
      if ($tagIds && $qid) {
        $ins = $pdo->prepare("INSERT INTO question_tags (question_id, tag_id) VALUES (?, ?)");
        foreach ($tagIds as $tid) { $ins->execute([$qid, $tid]); }
      }
      redirect('questions/list.php');
    }
  }
}

// Trending tags
$trendingStmt = $pdo->query("SELECT t.name, COUNT(*) cnt
                      FROM question_tags qt
                      JOIN tags t ON t.id=qt.tag_id
                      GROUP BY t.id
                      ORDER BY cnt DESC, t.name ASC
                      LIMIT 8");
$trending = $trendingStmt->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<main class="container home-light">
  <div class="layout-grid two-col">
    <section class="feed">
      <div class="feed-header">
        <h1>Ask a Question</h1>
      </div>

      <?php if ($error) alert($error, 'error'); ?>

      <div class="card">
        <form method="post" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:16px;">
          <!-- Title -->
          <div>
            <label style="display:block;margin-bottom:6px;color:#696969;font-weight:500;">Title <span style="color:#ef4444;">*</span></label>
            <input name="title" placeholder="What's your programming question? Be specific." required style="width:100%;background:#0b1220;color:#e5e7eb;border:1px solid #1f2937;border-radius:8px;padding:12px;font-size:15px;" />
            <p style="color:#9ca3af;font-size:12px;margin:4px 0 0;">Be specific and imagine you're asking a question to another person</p>
          </div>

          <!-- Module -->
          <div>
            <label style="display:block;margin-bottom:6px;color:#e5e7eb;font-weight:500;">Module <span style="color:#ef4444;">*</span></label>
            <select name="module_id" required style="width:100%;background:#0b1220;color:#e5e7eb;border:1px solid #1f2937;border-radius:8px;padding:12px;font-size:15px;">
              <option value="">-- Select a module --</option>
              <?php foreach($modules as $m): ?>
                <option value="<?php echo (int)$m['module_id']; ?>"><?php echo e($m['module_name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Body -->
          <div>
            <label style="display:block;margin-bottom:6px;color:#e5e7eb;font-weight:500;">Description <span style="color:#ef4444;">*</span></label>
            <textarea name="body" rows="10" placeholder="Include all the information someone would need to answer your question..." required style="width:100%;background:#0b1220;color:#e5e7eb;border:1px solid #1f2937;border-radius:8px;padding:12px;font-size:15px;resize:vertical;"></textarea>
            <p style="color:#9ca3af;font-size:12px;margin:4px 0 0;">Provide context, what you've tried, and what you expect to happen</p>
          </div>

          <!-- Image -->
          <div>
            <label style="display:block;margin-bottom:6px;color:#e5e7eb;font-weight:500;">Image (Optional)</label>
            <input type="file" name="image" accept="image/*" style="width:100%;background:#0b1220;color:#e5e7eb;border:1px solid #1f2937;border-radius:8px;padding:12px;font-size:14px;" />
            <p style="color:#9ca3af;font-size:12px;margin:4px 0 0;">Upload a screenshot or diagram (JPG, PNG, GIF - Max 5MB)</p>
          </div>

          <!-- Tags -->
          <div>
            <label style="display:block;margin-bottom:10px;color:#e5e7eb;font-weight:500;">Tags (Click to select)</label>
            <div id="tags-container" style="display:flex;flex-wrap:wrap;gap:8px;">
              <?php foreach($allTags as $t): ?>
                <span class="tag-selector" data-tag-id="<?php echo (int)$t['id']; ?>" style="display:inline-block;padding:8px 14px;background:#0b1220;border:1px solid #1f2937;border-radius:999px;cursor:pointer;transition:all .18s ease;color:#e5e7eb;font-size:13px;user-select:none;" onmouseover="if(!this.classList.contains('selected')) this.style.borderColor='#22c55e'" onmouseout="if(!this.classList.contains('selected')) this.style.borderColor='#1f2937'">
                  #<?php echo e($t['name']); ?>
                </span>
              <?php endforeach; ?>
            </div>
            <div id="hidden-tags"></div>
          </div>

          <script>
            document.addEventListener('DOMContentLoaded', function() {
              const tagSelectors = document.querySelectorAll('.tag-selector');
              const hiddenTagsContainer = document.getElementById('hidden-tags');
              
              tagSelectors.forEach(tag => {
                tag.addEventListener('click', function() {
                  const tagId = this.getAttribute('data-tag-id');
                  
                  if (this.classList.contains('selected')) {
                    // Deselect
                    this.classList.remove('selected');
                    this.style.background = '#0b1220';
                    this.style.borderColor = '#1f2937';
                    this.style.color = '#e5e7eb';
                    
                    // Remove hidden input
                    const hiddenInput = hiddenTagsContainer.querySelector(`input[value="${tagId}"]`);
                    if (hiddenInput) hiddenInput.remove();
                  } else {
                    // Select
                    this.classList.add('selected');
                    this.style.background = '#22c55e';
                    this.style.borderColor = '#22c55e';
                    this.style.color = '#0b1220';
                    
                    // Add hidden input
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'tags[]';
                    hiddenInput.value = tagId;
                    hiddenTagsContainer.appendChild(hiddenInput);
                  }
                });
              });
            });
          </script>

          <!-- Submit Button -->
          <div style="display:flex;gap:12px;padding-top:16px;border-top:1px solid #1f2937;">
            <button type="submit" class="btn-ask" style="padding:12px 24px;font-size:15px;flex:1;">Post Question</button>
            <a href="<?php echo BASE_URL; ?>questions/list.php" class="tag-chip" style="padding:12px 24px;font-size:15px;text-align:center;display:flex;align-items:center;justify-content:center;text-decoration:none;">Cancel</a>
          </div>
        </form>
      </div>
    </section>

    <!-- Sidebar -->
    <aside class="right-rail">
      <!-- Tips Card -->
      <div class="card">
        <h3>Writing a good question</h3>
        <ul style="color:#9ca3af;font-size:13px;line-height:1.8;padding-left:20px;margin:10px 0 0;">
          <li>Make your title question-specific</li>
          <li>Be clear and concise</li>
          <li>Include what you've tried</li>
          <li>Show your code (if applicable)</li>
          <li>Add relevant tags</li>
          <li>Proofread before posting</li>
        </ul>
      </div>

      <!-- Trending Topics -->
      <div class="card">
        <h3>Trending Topics</h3>
        <?php if (!$trending): ?>
          <p style="color:#9ca3af;font-size:14px;">No tags yet.</p>
        <?php else: ?>
          <div class="tag-cloud">
            <?php foreach($trending as $t): ?>
              <span class="tag-chip">#<?php echo e($t['name']); ?></span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Quick Links -->
      <div class="card">
        <h3>Quick Links</h3>
        <div style="display:flex;flex-direction:column;gap:8px;margin-top:10px;">
          <a href="<?php echo BASE_URL; ?>questions/list.php" class="tag-chip" style="text-align:center;display:block;">Browse Questions</a>
          <a href="<?php echo BASE_URL; ?>modules/list.php" class="tag-chip" style="text-align:center;display:block;">View Modules</a>
          <a href="<?php echo BASE_URL; ?>tags/list.php" class="tag-chip" style="text-align:center;display:block;">Browse Tags</a>
        </div>
      </div>
    </aside>
  </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
