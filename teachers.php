<?php
require_once 'config.php';
requireLogin();
$db = getDB();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_teacher'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');
    $qualification = trim($_POST['qualification'] ?? '');
    if (empty($name)) {
        $error = 'Name is required';
    } else {
        try {
            $db->prepare("INSERT INTO teachers (name, email, phone, specialization, qualification, status) VALUES (?,?,?,?,?,?)")
               ->execute([$name, $email, $phone, $specialization, $qualification, 'active']);
            $success = 'Teacher added successfully';
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_teachers'])) {
    if (isset($_FILES['import_csv']) && $_FILES['import_csv']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['import_csv']['tmp_name'];
        $handle = fopen($file, "r");
        if ($handle !== FALSE) {
            $header = fgetcsv($handle, 1000, ",");
            $importedCount = 0;
            $db->beginTransaction();
            try {
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    $name = trim($data[0] ?? '');
                    $email = trim($data[1] ?? '');
                    $phone = trim($data[2] ?? '');
                    $specialization = trim($data[3] ?? '');
                    $qualification = trim($data[4] ?? '');
                    if (empty($name)) continue;
                    $db->prepare("INSERT IGNORE INTO teachers (name, email, phone, specialization, qualification, status) VALUES (?, ?, ?, ?, ?, 'active')")->execute([$name, $email, $phone, $specialization, $qualification]);
                    $importedCount++;
                }
                $db->commit();
                $success = "$importedCount teachers imported successfully";
            } catch (Exception $e) {
                $db->rollBack();
                $error = "Error importing teachers: " . $e->getMessage();
            }
            fclose($handle);
        } else {
            $error = 'Error opening the CSV file.';
        }
    } else {
        $error = 'Please upload a valid CSV file.';
    }
}

$teachers = $db->query("
    SELECT t.*,
           (SELECT COALESCE(SUM(amount),0) FROM salary_payments WHERE teacher_id=t.id AND MONTH(payment_date)=MONTH(CURDATE())) as paid_this_month,
           (SELECT MAX(payment_date) FROM salary_payments WHERE teacher_id=t.id) as last_paid
    FROM teachers t ORDER BY t.name
")->fetchAll();

// Pre-fetch subject assignments
$teacher_subject_map = [];
foreach ($teachers as $t) {
    $stmt = $db->prepare("SELECT s.name FROM subjects s JOIN teacher_subjects ts ON s.id=ts.subject_id WHERE ts.teacher_id=?");
    $stmt->execute([$t['id']]);
    $teacher_subject_map[$t['id']] = array_column($stmt->fetchAll(), 'name');
}

require_once 'header.php';
?>

<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="d-flex flex-wrap" style="gap:1.5rem;">
    <div style="flex:1 1 300px; max-width:100%;">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-user-plus mr-2" style="color:#059669;"></i>Quick Add Teacher</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="Enter name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="email@example.com">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" placeholder="Enter phone number">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Specialization</label>
                        <input type="text" name="specialization" class="form-control" placeholder="e.g. Grammar, Math">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Qualification</label>
                        <input type="text" name="qualification" class="form-control" placeholder="e.g. Ph.D. Education">
                    </div>
                    <button type="submit" name="add_teacher" class="btn btn-success w-100">
                        <i class="fas fa-plus mr-2"></i>Add Teacher
                    </button>
                </form>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h3><i class="fas fa-file-import mr-2" style="color:var(--primary);"></i>Import CSV</h3>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label class="form-label">Upload CSV File</label>
                        <input type="file" name="import_csv" class="form-control" accept=".csv" required>
                    </div>
                    <button type="submit" name="import_teachers" class="btn btn-outline-primary w-100 mb-3">
                        <i class="fas fa-upload mr-2"></i>Import Teachers
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div style="flex:2 1 500px; max-width:100%;">
        <div class="card">
            <div class="card-header flex-wrap" style="gap:1rem;">
                <h3><i class="fas fa-user-tie mr-2" style="color:var(--primary);"></i>All Teachers (<?= count($teachers) ?>)</h3>
                <a href="teacher_subjects.php" class="btn btn-primary btn-sm"><i class="fas fa-book mr-2"></i>Assign Subjects</a>
            </div>
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Teacher</th>
                            <th>Subjects</th>
                            <th>Contact</th>
                            <th>Salary</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($teachers)): ?>
                            <tr><td colspan="6" class="text-center text-muted" style="padding:3rem;">No teachers found.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($teachers as $t): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-center">
                                        <div style="width:36px;height:36px;background:var(--primary);border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:600;font-size:0.8rem;margin-right:0.75rem;"><?= strtoupper(substr($t['name'],0,1)) ?></div>
                                        <div>
                                            <div class="font-semibold" style="line-height:1.2;margin-bottom:0.15rem;"><?= htmlspecialchars($t['name']) ?></div>
                                            <?php if ($t['qualification']): ?><div class="text-xs text-muted" style="line-height:1;"><?= htmlspecialchars($t['qualification']) ?></div><?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $subs = $teacher_subject_map[$t['id']] ?? [];
                                    if (empty($subs)): ?>
                                        <span class="text-muted text-xs">None</span>
                                    <?php else: ?>
                                        <?php foreach ($subs as $sn): ?>
                                            <span class="badge badge-primary" style="font-size:0.65rem;margin:1px;"><?= htmlspecialchars($sn) ?></span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="text-xs text-muted" style="line-height:1.4;">
                                        <?php if ($t['phone']): ?><div><i class="fas fa-phone mr-1"></i><?= htmlspecialchars($t['phone']) ?></div><?php endif; ?>
                                        <?php if ($t['email']): ?><div><i class="fas fa-envelope mr-1"></i><?= htmlspecialchars($t['email']) ?></div><?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight:600;color:var(--accent);">$<?= number_format($t['salary'], 2) ?>/mo</div>
                                    <?php if ($t['paid_this_month'] > 0): ?>
                                        <div class="text-xs" style="color:#059669;"><i class="fas fa-check-circle mr-1"></i>Paid</div>
                                    <?php elseif ($t['last_paid']): ?>
                                        <div class="text-xs text-muted"><i class="far fa-clock mr-1"></i>Last: <?= date('M d', strtotime($t['last_paid'])) ?></div>
                                    <?php else: ?>
                                        <div class="text-xs text-muted"><i class="fas fa-exclamation-triangle mr-1"></i>Not paid</div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge <?= $t['status']==='active' ? 'badge-success' : 'badge-secondary' ?>"><?= ucfirst($t['status']) ?></span></td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="teacher_edit.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-edit"></i></a>
                                    </div>
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
