<?php
require_once 'config.php';
requireLogin();
$db = getDB();
$error = '';
$success = '';

$classes = $db->query("SELECT id, name FROM classes WHERE status='active' ORDER BY name")->fetchAll();
$subjects = $db->query("SELECT id, name, code FROM subjects WHERE status='active' ORDER BY name")->fetchAll();
$teachers = $db->query("SELECT id, name FROM teachers WHERE status='active' ORDER BY name")->fetchAll();
$timeSlots = $db->query("SELECT * FROM time_slots ORDER BY start_time")->fetchAll();
$days = ['Mon'=>'Monday','Tue'=>'Tuesday','Wed'=>'Wednesday','Thu'=>'Thursday','Fri'=>'Friday','Sat'=>'Saturday','Sun'=>'Sunday'];

$selected_class = (int)($_GET['class_id'] ?? ($_POST['class_id'] ?? ($classes[0]['id'] ?? 0)));

// Add schedule entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_entry'])) {
    $class_id = (int)$_POST['class_id'];
    $subject_id = (int)$_POST['subject_id'];
    $teacher_id = (int)$_POST['teacher_id'];
    $time_slot_id = (int)$_POST['time_slot_id'];
    $day = $_POST['day_of_week'];
    $frequency = (int)($_POST['weekly_frequency'] ?? 1);

    // Check teacher conflict
    $conflict = $db->prepare("SELECT cs.*, c.name as class_name FROM class_subjects cs JOIN classes c ON cs.class_id=c.id WHERE cs.teacher_id=? AND cs.day_of_week=? AND cs.time_slot_id=? AND cs.status='active' AND cs.class_id!=? LIMIT 1");
    $conflict->execute([$teacher_id, $day, $time_slot_id, $class_id]);
    $conflictRow = $conflict->fetch();
    if ($conflictRow) {
        $error = "Teacher is already assigned to {$conflictRow['class_name']} at this time slot on $day.";
    }

    // Check class time conflict
    $conflict2 = $db->prepare("SELECT cs.*, t.name as teacher_name FROM class_subjects cs JOIN teachers t ON cs.teacher_id=t.id WHERE cs.class_id=? AND cs.day_of_week=? AND cs.time_slot_id=? AND cs.status='active' LIMIT 1");
    $conflict2->execute([$class_id, $day, $time_slot_id]);
    $conflictRow2 = $conflict2->fetch();
    if ($conflictRow2) {
        $error = "This class already has a subject at this time slot on $day ({$conflictRow2['teacher_name']}).";
    }

    if (empty($error)) {
        $db->prepare("INSERT INTO class_subjects (class_id, subject_id, teacher_id, time_slot_id, day_of_week, weekly_frequency, status) VALUES (?,?,?,?,?,?,?)")
           ->execute([$class_id, $subject_id, $teacher_id, $time_slot_id, $day, $frequency, 'active']);
        $success = 'Schedule entry added';
    }
}

// Delete entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_entry'])) {
    $entry_id = (int)$_POST['entry_id'];
    $db->prepare("DELETE FROM class_subjects WHERE id=?")->execute([$entry_id]);
    $success = 'Entry removed';
}

// Fetch current schedule
$schedule = [];
if ($selected_class) {
    $stmt = $db->prepare("
        SELECT cs.*, s.name as subject_name, s.code as subject_code,
               t.name as teacher_name, ts.start_time, ts.end_time
        FROM class_subjects cs
        JOIN subjects s ON cs.subject_id=s.id
        JOIN teachers t ON cs.teacher_id=t.id
        JOIN time_slots ts ON cs.time_slot_id=ts.id
        WHERE cs.class_id=? AND cs.status='active'
        ORDER BY ts.start_time, FIELD(cs.day_of_week,'Mon','Tue','Wed','Thu','Fri','Sat','Sun')
    ");
    $stmt->execute([$selected_class]);
    foreach ($stmt as $row) {
        $schedule[$row['day_of_week']][] = $row;
    }
}

// Color map for subjects
$subject_colors = ['#3b82f6','#059669','#7c3aed','#f59e0b','#ef4444','#06b6d4','#ec4899','#84cc16','#f97316','#6366f1'];
$subject_ids = array_column($subjects, 'id');
$color_map = [];
foreach ($subject_ids as $i => $sid) {
    $color_map[$sid] = $subject_colors[$i % count($subject_colors)];
}

require_once 'header.php';
?>

<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="d-flex align-center justify-between mb-4" style="flex-wrap:wrap;gap:1rem;">
    <h2><i class="fas fa-calendar-alt mr-2" style="color:var(--primary);"></i>Class Schedule Builder</h2>
    <a href="class_view.php?id=<?= $selected_class ?>" class="btn btn-outline-primary btn-sm"><i class="fas fa-eye mr-2"></i>View Class</a>
</div>

<div class="mb-4" style="max-width:350px;">
    <form method="GET">
        <div class="form-group">
            <label class="form-label font-semibold">Select Class</label>
            <select name="class_id" class="form-select" onchange="this.form.submit()">
                <?php foreach ($classes as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $c['id']==$selected_class ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
</div>

<!-- Add Entry Form -->
<div class="card mb-4">
    <div class="card-header">
        <h3><i class="fas fa-plus-circle mr-2" style="color:#059669;"></i>Add Schedule Entry</h3>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="class_id" value="<?= $selected_class ?>">
            <input type="hidden" name="add_entry" value="1">
            <div class="d-flex flex-wrap" style="gap:1rem;align-items:flex-end;">
                <div class="form-group" style="flex:1;min-width:150px;">
                    <label class="form-label">Subject *</label>
                    <select name="subject_id" class="form-select" required>
                        <option value="">Select</option>
                        <?php foreach ($subjects as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="flex:1;min-width:150px;">
                    <label class="form-label">Teacher *</label>
                    <select name="teacher_id" class="form-select" required>
                        <option value="">Select</option>
                        <?php foreach ($teachers as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="flex:1;min-width:140px;">
                    <label class="form-label">Day *</label>
                    <select name="day_of_week" class="form-select" required>
                        <?php foreach ($days as $k=>$v): ?>
                            <option value="<?= $k ?>"><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="flex:1;min-width:160px;">
                    <label class="form-label">Time Slot *</label>
                    <select name="time_slot_id" class="form-select" required>
                        <?php foreach ($timeSlots as $ts): ?>
                            <option value="<?= $ts['id'] ?>"><?= date('g:i A', strtotime($ts['start_time'])) ?> - <?= date('g:i A', strtotime($ts['end_time'])) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="min-width:100px;">
                    <label class="form-label">Freq.</label>
                    <input type="number" name="weekly_frequency" class="form-control" value="1" min="1" max="7" title="Weekly frequency (informational)">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-success"><i class="fas fa-plus mr-2"></i>Add</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Schedule Grid -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-calendar mr-2" style="color:var(--primary);"></i>Weekly Schedule</h3>
    </div>
    <div class="table-wrapper" style="overflow-x:auto;">
        <table class="table" style="min-width:900px;">
            <thead>
                <tr>
                    <th style="width:130px;">Time</th>
                    <?php foreach ($days as $k=>$v): ?>
                        <th style="text-align:center;min-width:120px;"><?= $v ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($timeSlots as $ts): ?>
                    <tr>
                        <td>
                            <span class="font-semibold" style="font-size:0.8rem;white-space:nowrap;">
                                <?= date('g:i A', strtotime($ts['start_time'])) ?><br>
                                <span class="text-muted"><?= date('g:i A', strtotime($ts['end_time'])) ?></span>
                            </span>
                        </td>
                        <?php foreach ($days as $dk=>$dv): ?>
                            <td style="vertical-align:top;padding:4px;">
                                <?php
                                $entries = array_filter($schedule[$dk] ?? [], fn($e) => $e['time_slot_id']==$ts['id']);
                                foreach ($entries as $entry):
                                    $color = $color_map[$entry['subject_id']] ?? '#6b7280';
                                ?>
                                    <div style="background:<?= $color ?>;color:white;border-radius:8px;padding:6px 8px;margin-bottom:4px;position:relative;font-size:0.78rem;line-height:1.3;">
                                        <div class="font-semibold"><?= htmlspecialchars($entry['subject_name']) ?></div>
                                        <div style="opacity:0.85;font-size:0.7rem;"><?= htmlspecialchars($entry['teacher_name']) ?></div>
                                        <form method="POST" style="position:absolute;top:2px;right:2px;" onsubmit="return confirm('Remove this entry?')">
                                            <input type="hidden" name="entry_id" value="<?= $entry['id'] ?>">
                                            <input type="hidden" name="delete_entry" value="1">
                                            <input type="hidden" name="class_id" value="<?= $selected_class ?>">
                                            <button type="submit" style="background:rgba(0,0,0,0.3);border:none;color:white;border-radius:50%;width:18px;height:18px;font-size:0.6rem;cursor:pointer;display:flex;align-items:center;justify-content:center;line-height:1;" title="Remove">&times;</button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Subject Legend -->
<div class="card mt-4">
    <div class="card-header"><h3><i class="fas fa-palette mr-2"></i>Subject Legend</h3></div>
    <div class="card-body d-flex flex-wrap" style="gap:0.75rem;">
        <?php foreach ($subjects as $i=>$s): ?>
            <?php $color = $subject_colors[$i % count($subject_colors)]; ?>
            <div style="display:flex;align-items:center;gap:0.4rem;">
                <div style="width:14px;height:14px;border-radius:4px;background:<?= $color ?>;"></div>
                <span style="font-size:0.8rem;font-weight:500;"><?= htmlspecialchars($s['name']) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>
