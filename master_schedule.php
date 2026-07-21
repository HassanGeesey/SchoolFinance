<?php
require_once 'config.php';
requireLogin();
$db = getDB();

// Fetch all active classes with their stats
$classes = $db->query("
    SELECT c.id, c.name, c.status, c.start_date
    FROM classes c
    WHERE c.status='active'
    ORDER BY c.name
")->fetchAll();

// Fetch all schedule entries for active classes
$entries = $db->query("
    SELECT cs.*, c.name as class_name, s.name as subject_name, s.code as subject_code,
           t.name as teacher_name, ts.start_time, ts.end_time
    FROM class_subjects cs
    JOIN classes c ON cs.class_id=c.id
    JOIN subjects s ON cs.subject_id=s.id
    JOIN teachers t ON cs.teacher_id=t.id
    JOIN time_slots ts ON cs.time_slot_id=ts.id
    WHERE cs.status='active' AND c.status='active'
    ORDER BY c.name, FIELD(cs.day_of_week,'Mon','Tue','Wed','Thu','Fri','Sat','Sun'), ts.start_time
")->fetchAll();

// Group entries by class_id
$schedule_by_class = [];
foreach ($entries as $e) {
    $schedule_by_class[$e['class_id']][] = $e;
}

$total_entries = count($entries);
$days_full = ['Mon'=>'Monday','Tue'=>'Tuesday','Wed'=>'Wednesday','Thu'=>'Thursday','Fri'=>'Friday','Sat'=>'Saturday','Sun'=>'Sunday'];

$subject_colors = ['#2a395a','#b85c3a','#3d7a52','#b89040','#b84c4c','#5a7a8a','#8a6a7a','#6a8a5a','#9a7040','#5a6a8a'];
$all_subjects = $db->query("SELECT id FROM subjects ORDER BY name")->fetchAll();
$color_map = [];
foreach ($all_subjects as $i=>$s) {
    $color_map[$s['id']] = $subject_colors[$i % count($subject_colors)];
}

// Day filter
$selected_day = $_GET['day'] ?? '';

require_once 'header.php';
?>

<style>
.class-schedule-card { transition: all 0.2s ease; }
.class-schedule-card:hover { box-shadow: 0 8px 20px -6px rgba(0,0,0,0.1); }
.entry-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; flex-shrink: 0; }
</style>

<div class="d-flex align-center justify-between mb-4" style="flex-wrap:wrap;gap:1rem;">
    <div>
        <h2><i class="fas fa-table mr-2" style="color:var(--primary);"></i>Master Schedule</h2>
        <p class="text-muted text-sm"><?= count($classes) ?> active classes &middot; <?= $total_entries ?> schedule entries</p>
    </div>
    <div class="d-flex gap-2">
        <form method="GET" class="d-flex align-center" style="gap:0.5rem;">
            <select name="day" class="form-select" style="min-width:130px;" onchange="this.form.submit()">
                <option value="">All Days</option>
                <?php foreach ($days_full as $k=>$v): ?>
                    <option value="<?= $k ?>" <?= $selected_day===$k ? 'selected' : '' ?>><?= $v ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ($selected_day): ?>
                <a href="master_schedule.php" class="btn btn-sm btn-outline"><i class="fas fa-times"></i> Clear</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php if (empty($classes)): ?>
    <div class="card"><div class="card-body text-center text-muted" style="padding:3rem;">No active classes found. <a href="class_create.php">Create one</a>.</div></div>
    <?php require_once 'footer.php'; exit; ?>
<?php endif; ?>

<?php foreach ($classes as $class):
    $class_entries = $schedule_by_class[$class['id']] ?? [];
    // Apply day filter
    if ($selected_day) {
        $class_entries = array_filter($class_entries, fn($e) => $e['day_of_week'] === $selected_day);
    }
?>
<div class="card class-schedule-card mb-4">
    <div class="card-header d-flex align-center justify-between" style="flex-wrap:wrap;gap:0.5rem;">
        <div class="d-flex align-center" style="gap:0.75rem;">
            <div style="width:36px;height:36px;border-radius:8px;background:var(--primary);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-chalkboard" style="color:#fff;font-size:1rem;"></i>
            </div>
            <div>
                <p class="text-muted text-xs" style="margin:0;line-height:1.2;">Class</p>
                <h3 style="margin:0;font-size:1.25rem;"><?= htmlspecialchars($class['name']) ?></h3>
            </div>
            <span class="badge badge-success" style="font-size:0.7rem;"><?= ucfirst($class['status']) ?></span>
            <span class="text-muted text-xs"><?= count($class_entries) ?> slot<?= count($class_entries)!==1?'s':'' ?></span>
        </div>
        <div class="d-flex gap-2">
            <a href="class_view.php?id=<?= $class['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye mr-1"></i>View</a>
            <a href="class_schedule.php?class_id=<?= $class['id'] ?>" class="btn btn-sm btn-success"><i class="fas fa-calendar-alt mr-1"></i>Edit Schedule</a>
        </div>
    </div>
    <div class="table-wrapper">
        <table class="table" style="margin:0;">
            <thead>
                <tr>
                    <th style="width:100px;">Day</th>
                    <th style="width:160px;">Time</th>
                    <th>Subject</th>
                    <th>Teacher</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($class_entries)): ?>
                    <tr><td colspan="4" class="text-center text-muted" style="padding:2rem;">No schedule entries<?= $selected_day ? ' for '.$days_full[$selected_day] : '' ?></td></tr>
                <?php else: ?>
                    <?php foreach ($class_entries as $e):
                        $color = $color_map[$e['subject_id']] ?? '#6b7280';
                    ?>
                    <tr>
                        <td><span class="font-medium"><?= $days_full[$e['day_of_week']] ?></span></td>
                        <td>
                            <span class="font-semibold"><?= date('g:i A', strtotime($e['start_time'])) ?></span>
                            <span class="text-muted">–</span>
                            <span class="text-muted"><?= date('g:i A', strtotime($e['end_time'])) ?></span>
                        </td>
                        <td>
                            <div class="d-flex align-center" style="gap:0.5rem;">
                                <span class="entry-dot" style="background:<?= $color ?>;"></span>
                                <span class="font-semibold"><?= htmlspecialchars($e['subject_name']) ?></span>

                            </div>
                        </td>
                        <td class="text-muted"><?= htmlspecialchars($e['teacher_name']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endforeach; ?>

<?php require_once 'footer.php'; ?>
