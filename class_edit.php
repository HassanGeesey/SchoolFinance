<?php
require_once 'config.php';
requireLogin();
$db = getDB();
$error = '';
$id = (int)($_GET['id'] ?? 0);

$class = $db->prepare("SELECT * FROM classes WHERE id=?");
$class->execute([$id]);
$class = $class->fetch();
if (!$class) { header('Location: classes.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $start_date  = $_POST['start_date'];
    $max_students= (int)($_POST['max_students'] ?? 30);
    $status      = $_POST['status'];

    if (empty($name) || empty($start_date)) {
        $error = 'Please fill all required fields.';
    } else {
        $db->prepare("UPDATE classes SET name=?,start_date=?,max_students=?,status=? WHERE id=?")
           ->execute([$name, $start_date, $max_students, $status, $id]);
        header('Location: class_view.php?id='.$id);
        exit;
    }
}

require_once 'header.php';
?>

<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div style="max-width:750px;">
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-chalkboard mr-2" style="color:var(--primary);"></i>Edit Class — <?= htmlspecialchars($class['name']) ?></h3></div>
        <div class="card-body">
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Class Name *</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($class['name']) ?>" placeholder="e.g. Grade 1 - Section A" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Start Date *</label>
                        <input type="date" name="start_date" class="form-control" value="<?= $class['start_date'] ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Max Students</label>
                        <input type="number" name="max_students" class="form-control" value="<?= $class['max_students'] ?>" min="1">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active" <?= $class['status']==='active' ? 'selected' : '' ?>>Active</option>
                            <option value="archived" <?= $class['status']==='archived' ? 'selected' : '' ?>>Archived</option>
                            <option value="completed" <?= $class['status']==='completed' ? 'selected' : '' ?>>Completed</option>
                        </select>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-2"></i>Update Class</button>
                    <a href="class_view.php?id=<?= $id ?>" class="btn btn-outline">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
