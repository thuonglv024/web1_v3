<?php
// Include functions and ensure admin access
require_once __DIR__ . '/../includes/functions.php';
ensure_admin();

// Get status filter from GET parameter
$statusFilter = $_GET['status'] ?? 'all';
if (!in_array($statusFilter, ['all', 'pending', 'approved', 'rejected'], true)) {
  $statusFilter = 'all';
}

// Build query to fetch questions with status filter
$sql = "SELECT q.id, q.title, q.status, q.created_at, u.username, m.module_name
        FROM questions q
        JOIN users u ON q.user_id=u.id
        JOIN modules m ON q.module_id=m.module_id";

if ($statusFilter !== 'all') {
  $sql .= " WHERE q.status = :status";
}

$sql .= " ORDER BY q.created_at DESC";

$stmt = $pdo->prepare($sql);
if ($statusFilter !== 'all') {
  $stmt->bindValue(':status', $statusFilter, PDO::PARAM_STR);
}
$stmt->execute();
$rows = $stmt->fetchAll();

// Get counts for each status
$pendingCount = (int)$pdo->query("SELECT COUNT(*) FROM questions WHERE status='pending'")->fetchColumn();
$approvedCount = (int)$pdo->query("SELECT COUNT(*) FROM questions WHERE status='approved'")->fetchColumn();
$rejectedCount = (int)$pdo->query("SELECT COUNT(*) FROM questions WHERE status='rejected'")->fetchColumn();
$totalCount = $pendingCount + $approvedCount + $rejectedCount;

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>
<main class="container home-light">
  <div class="layout-grid">
    <!-- Include admin sidebar -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <section class="feed" style="grid-column:2/4;">
      <div class="feed-header mb-20">
        <h1>Manage Questions</h1>
      </div>

      <!-- Success message -->
      <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo e($_GET['success']); ?></div>
      <?php endif; ?>

      <!-- Status Filter Tabs -->
      <div class="card mb-20" style="padding:12px;">
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <a href="?status=all" class="tag-chip <?php echo $statusFilter==='all'?'tag-chip-active':''; ?>" style="padding:8px 14px;">
              All (<?php echo $totalCount; ?>)
            </a>
            <a href="?status=pending" class="tag-chip <?php echo $statusFilter==='pending'?'tag-chip-active':''; ?>" style="padding:8px 14px;border-color:<?php echo $statusFilter==='pending'?'#f59e0b':'#1f2937'; ?>;">
              Pending (<?php echo $pendingCount; ?>)
            </a>
            <a href="?status=approved" class="tag-chip <?php echo $statusFilter==='approved'?'tag-chip-active':''; ?>" style="padding:8px 14px;border-color:<?php echo $statusFilter==='approved'?'#22c55e':'#1f2937'; ?>;">
              Approved (<?php echo $approvedCount; ?>)
            </a>
            <a href="?status=rejected" class="tag-chip <?php echo $statusFilter==='rejected'?'tag-chip-active':''; ?>" style="padding:8px 14px;border-color:<?php echo $statusFilter==='rejected'?'#dc2626':'#1f2937'; ?>;">
              Rejected (<?php echo $rejectedCount; ?>)
            </a>
        </div>
      </div>

      <!-- Questions table -->
      <div class="card admin-table-card">
        <div class="admin-table-wrapper">
          <table class="admin-table">
            <thead>
              <tr>
                <th class="text-left-ID">ID</th>
                <th class="text-left-Title">Title</th>
                <th class="text-left-Module">Module</th>
                <th class="text-left-User">User</th>
                <th class="text-left-Status">Status</th>
                <th class="text-center-Actions">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($rows as $r): ?>
                <tr>
                  <td><?php echo (int)$r['id']; ?></td>
                  <td><?php echo e($r['title']); ?></td>
                  <td><?php echo e($r['module_name']); ?></td>
                  <td><?php echo e($r['username']); ?></td>
                  <td>
                    <?php
                      $statusClass = 'status-pill--pending';
                      if ($r['status'] === 'approved') {
                        $statusClass = 'status-pill--approved';
                      } elseif ($r['status'] === 'rejected') {
                        $statusClass = 'status-pill--rejected';
                      }
                    ?>
                    <span class="status-pill <?php echo $statusClass; ?>"><?php echo e($r['status']); ?></span>
                  </td>
                  <td class="table-actions">
                    <a href="<?php echo BASE_URL; ?>questions/view.php?id=<?php echo (int)$r['id']; ?>" class="tag-chip">View</a>
                    <?php if ($r['status'] !== 'approved'): ?>
                      <a href="<?php echo BASE_URL; ?>questions/approve.php?id=<?php echo (int)$r['id']; ?>&action=approve" class="tag-chip" style="border-color:#22c55e;" onclick="return confirm('Approve this question?')">Approve</a>
                    <?php endif; ?>
                    <?php if ($r['status'] !== 'rejected'): ?>
                      <a href="<?php echo BASE_URL; ?>questions/approve.php?id=<?php echo (int)$r['id']; ?>&action=reject" class="tag-chip" style="border-color:#dc2626;" onclick="return confirm('Reject this question?')">Reject</a>
                    <?php endif; ?>
                    <a href="<?php echo BASE_URL; ?>questions/edit.php?id=<?php echo (int)$r['id']; ?>" class="tag-chip">Edit</a>
                    <a href="<?php echo BASE_URL; ?>questions/delete.php?id=<?php echo (int)$r['id']; ?>" class="tag-chip" style="border-color:#7f1d1d;" onclick="return confirm('Delete this question?')">Delete</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
