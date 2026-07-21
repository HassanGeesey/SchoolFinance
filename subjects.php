<?php
require_once 'config.php';
requireLogin();
$db = getDB();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subject'])) {
    $name = trim($_POST['name'] ?? '');
    $code = trim($_POST['code'] ?? '');
    $description = trim($_POST['description'] ?? '');
    if (empty($name)) {
        $error = 'Subject name is required';
    } else {
        try {
            $db->prepare("INSERT INTO subjects (name, code, description, status) VALUES (?,?,?,?)")
               ->execute([$name, $code, $description, 'active']);
            $success = 'Subject added successfully';
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_subject'])) {
    $id = (int)$_POST['subject_id'];
    $newStatus = $_POST['new_status'];
    $db->prepare("UPDATE subjects SET status=? WHERE id=?")->execute([$newStatus, $id]);
    header('Location: subjects.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_subject'])) {
    $id = (int)$_POST['subject_id'];
    $count = $db->prepare("SELECT COUNT(*) FROM class_subjects WHERE subject_id=? AND status='active'");
    $count->execute([$id]);
    if ($count->fetchColumn() > 0) {
        $error = 'Cannot delete subject - it is assigned to classes.';
    } else {
        $db->prepare("DELETE FROM teacher_subjects WHERE subject_id=?")->execute([$id]);
        $db->prepare("DELETE FROM subjects WHERE id=?")->execute([$id]);
        header('Location: subjects.php');
        exit;
    }
}

$subjects = $db->query("
    SELECT s.*,
           (SELECT COUNT(*) FROM teacher_subjects ts WHERE ts.subject_id=s.id) as teacher_count,
           (SELECT COUNT(DISTINCT cs.class_id) FROM class_subjects cs WHERE cs.subject_id=s.id AND cs.status='active') as class_count
    FROM subjects s ORDER BY s.name
")->fetchAll();

require_once 'header.php';
?>

<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="d-flex flex-wrap" style="gap:1.5rem;">
    <div style="flex:1 1 300px; max-width:100%;">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-book mr-2" style="color:var(--primary);"></i>Add Subject</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Subject Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Mathematics" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Code</label>
                        <input type="text" name="code" class="form-control" placeholder="e.g. MATH" maxlength="20">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Brief description"></textarea>
                    </div>
                    <button type="submit" name="add_subject" class="btn btn-success w-100">
                        <i class="fas fa-plus mr-2"></i>Add Subject
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div style="flex:2 1 500px; max-width:100%;">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-book-open mr-2" style="color:var(--primary);"></i>All Subjects (<?= count($subjects) ?>)</h3>
            </div>
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr><th>Subject</th><th>Code</th><th>Teachers</th><th>Classes</th><th>Status</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($subjects)): ?>
                            <tr><td colspan="6" class="text-center text-muted" style="padding:3rem;">No subjects yet.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($subjects as $s): ?>
                            <tr>
                                <td>
                                    <div class="font-semibold"><?= htmlspecialchars($s['name']) ?></div>
                                    <?php if ($s['description']): ?><div class="text-xs text-muted"><?= htmlspecialchars($s['description']) ?></div><?php endif; ?>
                                </td>
                                <td><span class="badge badge-primary" style="font-size:0.75rem;"><?= htmlspecialchars($s['code'] ?: '—') ?></span></td>
                                <td><span class="font-semibold"><?= $s['teacher_count'] ?></span></td>
                                <td><span class="font-semibold"><?= $s['class_count'] ?></span></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="subject_id" value="<?= $s['id'] ?>">
                                        <input type="hidden" name="new_status" value="<?= $s['status']==='active' ? 'inactive' : 'active' ?>">
                                        <input type="hidden" name="toggle_subject" value="1">
                                        <button type="submit" class="badge <?= $s['status']==='active' ? 'badge-success' : 'badge-secondary' ?>" style="cursor:pointer;border:none;"><?= ucfirst($s['status']) ?></button>
                                    </form>
                                </td>
                                <td>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this subject?')">
                                        <input type="hidden" name="subject_id" value="<?= $s['id'] ?>">
                                        <input type="hidden" name="delete_subject" value="1">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
