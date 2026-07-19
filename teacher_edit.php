<?php
require_once 'config.php';
requireLogin();
$db = getDB();
$error = '';
$id = (int)($_GET['id'] ?? 0);

$teacher = $db->prepare("SELECT * FROM teachers WHERE id=?");
$teacher->execute([$id]);
$teacher = $teacher->fetch();
if (!$teacher) { header('Location: teachers.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $specialization = trim($_POST['specialization']);
    $status = $_POST['status'];

    if (empty($name)) {
        $error = 'Name is required';
    } else {
        $db->prepare("UPDATE teachers SET name=?,email=?,phone=?,specialization=?,status=? WHERE id=?")->execute([$name,$email,$phone,$specialization,$status,$id]);
        header('Location: teachers.php');
        exit;
    }
}

require_once 'header.php';
?>

<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div style="max-width:700px;">
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-user-edit mr-2" style="color:var(--primary);"></i>Edit Teacher</h3></div>
        <div class="card-body">
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($teacher['name']) ?>" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($teacher['email'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($teacher['phone'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Specialization</label>
                        <input type="text" name="specialization" class="form-control" value="<?= htmlspecialchars($teacher['specialization'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active" <?= $teacher['status']==='active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $teacher['status']==='inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-2"></i>Update Teacher</button>
                    <a href="teachers.php" class="btn btn-outline">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
