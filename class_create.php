<?php
require_once 'config.php';
requireLogin();
$db = getDB();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $start_date  = $_POST['start_date'];
    $max_students= (int)($_POST['max_students'] ?? 30);
    $status      = $_POST['status'];

    if (empty($name) || empty($start_date)) {
        $error = 'Please fill all required fields.';
    } else {
        $db->prepare("INSERT INTO classes (name,start_date,max_students,status) VALUES (?,?,?,?)")
           ->execute([$name, $start_date, $max_students, $status]);
        $class_id = $db->lastInsertId();
        header('Location: class_schedule.php?class_id='.$class_id);
        exit;
    }
}

require_once 'header.php';
?>

<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div style="max-width:750px;">
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-chalkboard mr-2" style="color:var(--primary);"></i>Create New Class</h3></div>
        <div class="card-body">
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Class Name *</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Grade 1 - Section A" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Start Date *</label>
                        <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Max Students</label>
                        <input type="number" name="max_students" class="form-control" value="30" min="1">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active">Active</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>
                </div>
                <p class="text-muted text-sm mb-3"><i class="fas fa-info-circle mr-1"></i>After creating the class, you can assign subjects, teachers, and schedule from the Schedule Builder.</p>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-2"></i>Create Class</button>
                    <a href="classes.php" class="btn btn-outline">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
