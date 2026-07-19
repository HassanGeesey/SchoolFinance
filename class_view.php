<?php
require_once 'config.php';
requireLogin();
$db = getDB();
$id = (int)($_GET['id'] ?? 0);

$class = $db->prepare("SELECT * FROM classes WHERE id=?");
$class->execute([$id]);
$class = $class->fetch();
if (!$class) { header('Location: classes.php'); exit; }

$students = $db->prepare("SELECT id, name, enrollment_date, status FROM students WHERE current_class_id=? ORDER BY name");
$students->execute([$id]);
$students = $students->fetchAll();

// Fetch schedule
$schedule_stmt = $db->prepare("
    SELECT cs.*, s.name as subject_name, s.code as subject_code,
           t.name as teacher_name, ts.start_time, ts.end_time
    FROM class_subjects cs
    JOIN subjects s ON cs.subject_id=s.id
    JOIN teachers t ON cs.teacher_id=t.id
    JOIN time_slots ts ON cs.time_slot_id=ts.id
    WHERE cs.class_id=? AND cs.status='active'
    ORDER BY ts.start_time, FIELD(cs.day_of_week,'Mon','Tue','Wed','Thu','Fri','Sat','Sun')
");
$schedule_stmt->execute([$id]);
$schedule_entries = $schedule_stmt->fetchAll();

$days = ['Mon'=>'Mon','Tue'=>'Tue','Wed'=>'Wed','Thu'=>'Thu','Fri'=>'Fri','Sat'=>'Sat','Sun'=>'Sun'];
$timeSlots = $db->query("SELECT * FROM time_slots ORDER BY start_time")->fetchAll();

$subject_colors = ['#3b82f6','#059669','#7c3aed','#f59e0b','#ef4444','#06b6d4','#ec4899','#84cc16','#f97316','#6366f1'];
$all_subjects = $db->query("SELECT id, name FROM subjects ORDER BY name")->fetchAll();
$color_map = [];
foreach ($all_subjects as $i=>$s) {
    $color_map[$s['id']] = $subject_colors[$i % count($subject_colors)];
}

$schedule = [];
foreach ($schedule_entries as $row) {
    $schedule[$row['day_of_week']][$row['time_slot_id']] = $row;
}

// Subject summary
$subject_summary = [];
foreach ($schedule_entries as $row) {
    $key = $row['subject_id'];
    if (!isset($subject_summary[$key])) {
        $subject_summary[$key] = ['name'=>$row['subject_name'],'teacher'=>$row['teacher_name'],'count'=>0,'color'=>$color_map[$row['subject_id']] ?? '#6b7280'];
    }
    $subject_summary[$key]['count']++;
}

require_once 'header.php';
?>

<div class="d-flex align-center justify-between mb-4" style="flex-wrap:wrap;gap:1rem;">
    <div>
        <h2><?= htmlspecialchars($class['name']) ?></h2>
        <p class="text-muted text-sm">Created <?= date('M d, Y', strtotime($class['start_date'])) ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="class_schedule.php?class_id=<?= $id ?>" class="btn btn-success"><i class="fas fa-calendar-alt mr-2"></i>Edit Schedule</a>
        <a href="class_edit.php?id=<?= $id ?>" class="btn btn-primary"><i class="fas fa-edit mr-2"></i>Edit</a>
    </div>
</div>

<div class="d-flex flex-wrap mb-4" style="gap:1.5rem;">
    <div class="card" style="flex:1;min-width:160px;">
        <div class="card-body text-center">
            <p class="text-muted text-sm mb-1">Students</p>
            <p class="font-semibold" style="font-size:2rem;"><?= count($students) ?></p>
        </div>
    </div>
    <div class="card" style="flex:1;min-width:160px;">
        <div class="card-body text-center">
            <p class="text-muted text-sm mb-1">Max Students</p>
            <p class="font-semibold" style="font-size:2rem;"><?= $class['max_students'] ?></p>
        </div>
    </div>
    <div class="card" style="flex:1;min-width:160px;">
        <div class="card-body text-center">
            <p class="text-muted text-sm mb-1">Subjects</p>
            <p class="font-semibold" style="font-size:2rem;"><?= count($subject_summary) ?></p>
        </div>
    </div>
    <div class="card" style="flex:1;min-width:160px;">
        <div class="card-body text-center">
            <p class="text-muted text-sm mb-1">Status</p>
            <span class="badge <?= $class['status']==='active' ? 'badge-success' : 'badge-secondary' ?>" style="font-size:0.9rem;padding:0.4rem 0.8rem;"><?= ucfirst($class['status']) ?></span>
        </div>
    </div>
</div>

<!-- Schedule Grid -->
<?php if (!empty($schedule_entries)): ?>
<div class="card mb-4">
    <div class="card-header">
        <h3><i class="fas fa-calendar mr-2" style="color:var(--primary);"></i>Weekly Schedule</h3>
    </div>
    <div class="table-wrapper" style="overflow-x:auto;">
        <table class="table" style="min-width:900px;">
            <thead>
                <tr>
                    <th style="width:130px;">Time</th>
                    <?php foreach ($days as $k=>$v): ?>
                        <th style="text-align:center;"><?= $v ?></th>
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
                                <?php if (isset($schedule[$dk][$ts['id']])):
                                    $e = $schedule[$dk][$ts['id']];
                                    $color = $color_map[$e['subject_id']] ?? '#6b7280';
                                ?>
                                    <div style="background:<?= $color ?>;color:white;border-radius:8px;padding:6px 8px;margin-bottom:4px;font-size:0.78rem;line-height:1.3;">
                                        <div class="font-semibold"><?= htmlspecialchars($e['subject_name']) ?></div>
                                        <div style="opacity:0.85;font-size:0.7rem;"><?= htmlspecialchars($e['teacher_name']) ?></div>
                                    </div>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="d-flex flex-wrap" style="gap:1.5rem;">
    <div style="flex:1 1 300px; max-width:100%;">
        <?php if (!empty($subject_summary)): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h3><i class="fas fa-book mr-2" style="color:var(--primary);"></i>Subjects (<?= count($subject_summary) ?>)</h3>
            </div>
            <div class="table-wrapper">
                <table class="table">
                    <thead><tr><th>Subject</th><th>Teacher</th><th>Slots/Week</th></tr></thead>
                    <tbody>
                        <?php foreach ($subject_summary as $ss): ?>
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;gap:0.5rem;">
                                        <div style="width:12px;height:12px;border-radius:3px;background:<?= $ss['color'] ?>;"></div>
                                        <span class="font-semibold"><?= htmlspecialchars($ss['name']) ?></span>
                                    </div>
                                </td>
                                <td class="text-muted"><?= htmlspecialchars($ss['teacher']) ?></td>
                                <td><span class="font-semibold"><?= $ss['count'] ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div style="flex:1 1 300px; max-width:100%;">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-users mr-2"></i>Students (<?= count($students) ?>)</h3>
            </div>
            <div class="table-wrapper">
                <table class="table">
                    <thead><tr><th>Name</th><th>Status</th><th>Enrolled</th></tr></thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr><td colspan="3" class="text-center text-muted" style="padding:2rem;">No students enrolled</td></tr>
                        <?php endif; ?>
                        <?php foreach ($students as $s): ?>
                            <tr>
                                <td class="font-medium"><?= htmlspecialchars($s['name']) ?></td>
                                <td><span class="badge <?= $s['status']==='active' ? 'badge-success' : 'badge-danger' ?>"><?= ucfirst($s['status']) ?></span></td>
                                <td class="text-muted text-sm"><?= date('M d, Y', strtotime($s['enrollment_date'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
