<?php
require_once 'config.php';
requireLogin();
$db = getDB();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_slot'])) {
    $start = $_POST['start_time'];
    $end   = $_POST['end_time'];
    if ($start >= $end) {
        $error = 'End time must be after start time.';
    } else {
        try {
            $db->prepare("INSERT INTO time_slots (start_time,end_time) VALUES (?,?)")->execute([$start,$end]);
            $success = 'Time slot added.';
        } catch (Exception $e) {
            $error = 'That time slot already exists.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_slot'])) {
    $sid = (int)$_POST['id'];
    $inUse = $db->prepare("SELECT COUNT(*) FROM classes WHERE time_slot_id=?");
    $inUse->execute([$sid]);
    if ($inUse->fetchColumn() > 0) {
        $error = 'Cannot delete — this slot is used by active classes.';
    } else {
        $db->prepare("DELETE FROM time_slots WHERE id=?")->execute([$sid]);
        $success = 'Time slot deleted.';
    }
}

$slots  = $db->query("SELECT ts.*, (SELECT COUNT(DISTINCT cs.class_id) FROM class_subjects cs WHERE cs.time_slot_id=ts.id) as class_count FROM time_slots ts ORDER BY ts.start_time")->fetchAll();

require_once 'header.php';
?>

<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="d-flex flex-wrap" style="gap:1.5rem;">
    <div style="flex:1 1 300px; max-width:100%;">
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-plus-circle mr-2" style="color:var(--primary);"></i>Add Time Slot</h3></div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Start Time *</label>
                        <input type="time" name="start_time" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">End Time *</label>
                        <input type="time" name="end_time" class="form-control" required>
                    </div>
                    <button type="submit" name="add_slot" class="btn btn-primary w-100"><i class="fas fa-plus mr-2"></i>Add Slot</button>
                </form>
            </div>
        </div>
    </div>

    <div style="flex:2 1 500px; max-width:100%;">
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-clock mr-2"></i>Time Slots (<?= count($slots) ?>)</h3></div>
            <div class="table-wrapper">
                <table class="table">
                    <thead><tr><th>Start Time</th><th>End Time</th><th>Duration</th><th>Classes</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php if (empty($slots)): ?>
                            <tr><td colspan="5" class="text-center text-muted" style="padding:3rem;">No time slots defined.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($slots as $s): ?>
                            <?php
                            $start = strtotime($s['start_time']);
                            $end   = strtotime($s['end_time']);
                            $duration = round(($end - $start) / 60) . ' min';
                            ?>
                            <tr>
                                <td class="font-semibold"><?= date('g:i A', $start) ?></td>
                                <td class="text-muted"><?= date('g:i A', $end) ?></td>
                                <td><span class="badge badge-secondary"><?= $duration ?></span></td>
                                <td><?= $s['class_count'] ?> class(es)</td>
                                <td>
                                    <?php if ($s['class_count'] == 0): ?>
                                        <form method="POST" onsubmit="return confirm('Delete this slot?')" style="display:inline;">
                                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                            <button type="submit" name="delete_slot" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted text-sm">In use</span>
                                    <?php endif; ?>
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
