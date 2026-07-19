<?php
require_once 'config.php';
requireLogin();
$db = getDB();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_assignments'])) {
    $teacher_id = (int)$_POST['teacher_id'];
    $subject_ids = $_POST['subjects'] ?? [];

    $db->beginTransaction();
    try {
        $db->prepare("DELETE FROM teacher_subjects WHERE teacher_id=?")->execute([$teacher_id]);
        $stmt = $db->prepare("INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES (?,?)");
        foreach ($subject_ids as $sid) {
            $stmt->execute([$teacher_id, (int)$sid]);
        }
        $db->commit();
        $success = 'Subject assignments updated successfully';
    } catch (Exception $e) {
        $db->rollBack();
        $error = 'Error: ' . $e->getMessage();
    }
}

$teachers = $db->query("SELECT id, name FROM teachers WHERE status='active' ORDER BY name")->fetchAll();
$subjects = $db->query("SELECT id, name, code FROM subjects WHERE status='active' ORDER BY name")->fetchAll();

$selected_teacher = (int)($_GET['teacher_id'] ?? ($_POST['teacher_id'] ?? ($teachers[0]['id'] ?? 0)));
$assigned_subjects = [];
if ($selected_teacher) {
    $stmt = $db->prepare("SELECT subject_id FROM teacher_subjects WHERE teacher_id=?");
    $stmt->execute([$selected_teacher]);
    $assigned_subjects = array_column($stmt->fetchAll(), 'subject_id');
}

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
                <h3><i class="fas fa-user-tie mr-2" style="color:var(--primary);"></i>Select Teacher</h3>
            </div>
            <div class="card-body">
                <form method="GET">
                    <div class="form-group">
                        <select name="teacher_id" class="form-select" onchange="this.form.submit()">
                            <?php foreach ($teachers as $t): ?>
                                <option value="<?= $t['id'] ?>" <?= $t['id']==$selected_teacher ? 'selected' : '' ?>><?= htmlspecialchars($t['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h3><i class="fas fa-list mr-2" style="color:#059669;"></i>All Teachers</h3>
            </div>
            <div class="table-wrapper">
                <table class="table">
                    <thead><tr><th>Teacher</th><th>Subjects</th></tr></thead>
                    <tbody>
                        <?php foreach ($teachers as $t): ?>
                            <tr style="cursor:pointer;" onclick="window.location='teacher_subjects.php?teacher_id=<?= $t['id'] ?>'">
                                <td class="font-semibold <?= $t['id']==$selected_teacher ? 'text-primary' : '' ?>"><?= htmlspecialchars($t['name']) ?></td>
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
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div style="flex:2 1 500px; max-width:100%;">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-book mr-2" style="color:var(--primary);"></i>Assign Subjects — <?= htmlspecialchars($teachers[array_search($selected_teacher, array_column($teachers, 'id'))]['name'] ?? '') ?></h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="teacher_id" value="<?= $selected_teacher ?>">
                    <input type="hidden" name="save_assignments" value="1">

                    <?php if (empty($subjects)): ?>
                        <p class="text-muted text-center" style="padding:2rem;">No active subjects. <a href="subjects.php">Create subjects first</a>.</p>
                    <?php else: ?>
                        <div class="d-flex flex-wrap" style="gap:1rem;margin-bottom:1.5rem;">
                            <?php foreach ($subjects as $s): ?>
                                <label style="display:flex;align-items:center;gap:0.5rem;padding:0.75rem 1rem;border:2px solid <?= in_array($s['id'], $assigned_subjects) ? 'var(--primary)' : 'var(--border)' ?>;border-radius:10px;cursor:pointer;transition:all 0.2s;min-width:180px;background:<?= in_array($s['id'], $assigned_subjects) ? 'rgba(42,57,90,0.05)' : 'transparent' ?>;">
                                    <input type="checkbox" name="subjects[]" value="<?= $s['id'] ?>" <?= in_array($s['id'], $assigned_subjects) ? 'checked' : '' ?> style="width:18px;height:18px;accent-color:var(--primary);">
                                    <div>
                                        <div class="font-semibold" style="font-size:0.9rem;"><?= htmlspecialchars($s['name']) ?></div>
                                        <?php if ($s['code']): ?><div class="text-xs text-muted"><?= htmlspecialchars($s['code']) ?></div><?php endif; ?>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary" <?= empty($subjects) ? 'disabled' : '' ?>>
                        <i class="fas fa-save mr-2"></i>Save Assignments
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
