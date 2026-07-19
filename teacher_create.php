<?php
require_once 'config.php';
requireLogin();
$db = getDB();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $specialization = trim($_POST['specialization']);
    $qualification = trim($_POST['qualification']);
    $status = $_POST['status'];

    if (empty($name)) {
        $error = 'Name is required';
    }

    if (empty($error)) {
        try {
            $db->beginTransaction();
            
            $stmt = $db->prepare("INSERT INTO teachers (name, email, phone, specialization, qualification, status) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$name, $email, $phone, $specialization, $qualification, $status]);
            $teacher_id = $db->lastInsertId();

            $db->commit();
            $success = 'Teacher added successfully';
            header('Location: teachers.php?success=' . urlencode($success));
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

require_once 'header.php';
?>

<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div style="max-width:700px;">
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-user-plus mr-2" style="color:var(--primary);"></i>Add New Teacher</h3></div>
        <div class="card-body">
            <form method="POST" id="teacherForm">
                <div class="form-group">
                    <label class="form-label">Full Name *</label>
                    <input type="text" name="name" class="form-control" placeholder="Enter teacher name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                </div>
                <div class="d-flex flex-wrap gap-3">
                    <div class="form-group" style="flex:1;min-width:200px;">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="email@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                    <div class="form-group" style="flex:1;min-width:200px;">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" placeholder="+1234567890" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-3">
                    <div class="form-group" style="flex:1;min-width:200px;">
                        <label class="form-label">Specialization</label>
                        <input type="text" name="specialization" class="form-control" placeholder="e.g. Grammar, Conversation" value="<?= htmlspecialchars($_POST['specialization'] ?? '') ?>">
                    </div>
                    <div class="form-group" style="flex:1;min-width:200px;">
                        <label class="form-label">Qualification</label>
                        <input type="text" name="qualification" class="form-control" placeholder="e.g. M.A. TESOL" value="<?= htmlspecialchars($_POST['qualification'] ?? '') ?>">
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-3">
                    <div class="form-group" style="flex:1;min-width:200px;">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex gap-3 mt-4">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-2"></i>Save Teacher</button>
                    <a href="teachers.php" class="btn btn-outline">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
