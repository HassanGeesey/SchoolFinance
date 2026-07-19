<?php
require_once 'config.php';
requireLogin();
$db = getDB();

$totalStudents   = $db->query("SELECT COUNT(*) FROM students WHERE status='active'")->fetchColumn();
$totalClasses    = $db->query("SELECT COUNT(*) FROM classes WHERE status='active'")->fetchColumn();
$totalTeachers   = $db->query("SELECT COUNT(*) FROM teachers WHERE status='active'")->fetchColumn();
$monthlyRevenue  = $db->query("SELECT COALESCE(SUM(amount),0) FROM fee_payments WHERE MONTH(payment_date)=MONTH(CURDATE())")->fetchColumn();
$monthlyExpenses = $db->query("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE MONTH(expense_date)=MONTH(CURDATE())")->fetchColumn();
$monthlySalaries = $db->query("SELECT COALESCE(SUM(amount),0) FROM salary_payments WHERE MONTH(payment_date)=MONTH(CURDATE())")->fetchColumn();
$netProfit = $monthlyRevenue - $monthlyExpenses - $monthlySalaries;

$totalSubjects = $db->query("SELECT COUNT(*) FROM subjects WHERE status='active'")->fetchColumn();

$timeSlots = $db->query("SELECT * FROM time_slots ORDER BY start_time")->fetchAll();

$dayAbbrs = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
$dayFull = ['Mon'=>'Monday','Tue'=>'Tuesday','Wed'=>'Wednesday','Thu'=>'Thursday','Fri'=>'Friday','Sat'=>'Saturday','Sun'=>'Sunday'];

// Build schedule: [day][time_slot_id] = [{subject_name, teacher_name, class_name, color}]
$raw = $db->query("
    SELECT cs.day_of_week, cs.time_slot_id,
           s.name as subject_name, t.name as teacher_name, c.name as class_name, c.id as class_id, cs.subject_id
    FROM class_subjects cs
    JOIN subjects s ON cs.subject_id=s.id
    JOIN teachers t ON cs.teacher_id=t.id
    JOIN classes c ON cs.class_id=c.id
    WHERE cs.status='active' AND c.status='active'
    ORDER BY cs.time_slot_id, FIELD(cs.day_of_week,'Mon','Tue','Wed','Thu','Fri','Sat','Sun')
")->fetchAll();

$subject_colors = ['#3b82f6','#059669','#7c3aed','#f59e0b','#ef4444','#06b6d4','#ec4899','#84cc16','#f97316','#6366f1'];
$all_subjects = $db->query("SELECT id FROM subjects ORDER BY name")->fetchAll();
$color_map = [];
foreach ($all_subjects as $i=>$s) {
    $color_map[$s['id']] = $subject_colors[$i % count($subject_colors)];
}

$schedule = [];
foreach ($raw as $row) {
    $row['color'] = $color_map[$row['subject_id']] ?? '#6b7280';
    $schedule[$row['day_of_week']][$row['time_slot_id']][] = $row;
}

require_once 'header.php';
?>
<style>
.stat-card { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); border-radius: 16px; border: none; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03); }
.stat-card:hover { transform: translateY(-4px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05); }
.quick-action-btn { transition: all 0.2s ease; border: 1px solid var(--border); background: var(--bg-surface); border-radius: 12px; text-decoration: none; color: var(--text-main); font-weight: 500; }
.quick-action-btn:hover { border-color: transparent; box-shadow: 0 8px 20px -6px rgba(0,0,0,0.15); background: var(--bg-surface); transform: translateY(-2px); color: var(--primary); }
.schedule-cell { border-radius: 8px; padding: 5px 7px; margin-bottom: 3px; color: white; font-size: 0.75rem; line-height: 1.3; }
.schedule-cell .subj-name { font-weight: 600; }
.schedule-cell .meta { opacity: 0.85; font-size: 0.65rem; }
</style>

<div class="d-flex flex-wrap mb-4" style="gap:1.5rem;">
    <div class="card stat-card" style="flex:1;min-width:170px;">
        <div class="card-body d-flex align-center justify-between">
            <div>
                <p class="text-muted font-medium text-sm mb-1">Classes</p>
                <p class="font-bold" style="font-size:2.25rem;margin:0;line-height:1;"><?= $totalClasses ?></p>
            </div>
            <div style="width:56px;height:56px;background:linear-gradient(135deg, rgba(42,57,90,0.1), rgba(42,57,90,0.2));border-radius:14px;display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-chalkboard-teacher" style="color:var(--primary);font-size:1.5rem;"></i>
            </div>
        </div>
    </div>
    <div class="card stat-card" style="flex:1;min-width:170px;">
        <div class="card-body d-flex align-center justify-between">
            <div>
                <p class="text-muted font-medium text-sm mb-1">Students</p>
                <p class="font-bold" style="font-size:2.25rem;margin:0;line-height:1;"><?= $totalStudents ?></p>
            </div>
            <div style="width:56px;height:56px;background:linear-gradient(135deg, rgba(16,185,129,0.1), rgba(16,185,129,0.2));border-radius:14px;display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-users" style="color:#059669;font-size:1.5rem;"></i>
            </div>
        </div>
    </div>
    <div class="card stat-card" style="flex:1;min-width:170px;">
        <div class="card-body d-flex align-center justify-between">
            <div>
                <p class="text-muted font-medium text-sm mb-1">Teachers</p>
                <p class="font-bold" style="font-size:2.25rem;margin:0;line-height:1;"><?= $totalTeachers ?></p>
            </div>
            <div style="width:56px;height:56px;background:linear-gradient(135deg, rgba(139,92,246,0.1), rgba(139,92,246,0.2));border-radius:14px;display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-user-tie" style="color:#7c3aed;font-size:1.5rem;"></i>
            </div>
        </div>
    </div>
    <div class="card stat-card" style="flex:1;min-width:170px;">
        <div class="card-body d-flex align-center justify-between">
            <div>
                <p class="text-muted font-medium text-sm mb-1">Subjects</p>
                <p class="font-bold" style="font-size:2.25rem;margin:0;line-height:1;"><?= $totalSubjects ?></p>
            </div>
            <div style="width:56px;height:56px;background:linear-gradient(135deg, rgba(6,182,212,0.1), rgba(6,182,212,0.2));border-radius:14px;display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-book" style="color:#06b6d4;font-size:1.5rem;"></i>
            </div>
        </div>
    </div>
    <div class="card stat-card" style="flex:1;min-width:170px;">
        <div class="card-body d-flex align-center justify-between">
            <div>
                <p class="text-muted font-medium text-sm mb-1">Revenue</p>
                <p class="font-bold" style="font-size:2.25rem;margin:0;line-height:1;color:var(--accent);">$<?= number_format($monthlyRevenue,0) ?></p>
            </div>
            <div style="width:56px;height:56px;background:linear-gradient(135deg, rgba(252,68,102,0.1), rgba(252,68,102,0.2));border-radius:14px;display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-dollar-sign" style="color:var(--accent);font-size:1.5rem;"></i>
            </div>
        </div>
    </div>
    <div class="card stat-card" style="flex:1;min-width:170px;">
        <div class="card-body d-flex align-center justify-between">
            <div>
                <p class="text-muted font-medium text-sm mb-1">Expenses</p>
                <p class="font-bold" style="font-size:2.25rem;margin:0;line-height:1;color:#ef4444;">$<?= number_format($monthlyExpenses + $monthlySalaries,0) ?></p>
            </div>
            <div style="width:56px;height:56px;background:linear-gradient(135deg, rgba(239,68,68,0.1), rgba(239,68,68,0.2));border-radius:14px;display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-receipt" style="color:#ef4444;font-size:1.5rem;"></i>
            </div>
        </div>
    </div>
    <div class="card stat-card" style="flex:1;min-width:170px;">
        <div class="card-body d-flex align-center justify-between">
            <div>
                <p class="text-muted font-medium text-sm mb-1">Net Profit</p>
                <p class="font-bold" style="font-size:2.25rem;margin:0;line-height:1;color:<?= $netProfit >= 0 ? '#059669' : '#ef4444' ?>;"><?= $netProfit >= 0 ? '$' : '-$' ?><?= number_format(abs($netProfit),0) ?></p>
            </div>
            <div style="width:56px;height:56px;background:linear-gradient(135deg, rgba(16,185,129,0.1), rgba(16,185,129,0.2));border-radius:14px;display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-chart-line" style="color:#059669;font-size:1.5rem;"></i>
            </div>
        </div>
    </div>
</div>

<div class="d-flex flex-wrap mb-4" style="gap:1rem;">
    <a href="class_create.php" class="quick-action-btn d-flex align-center" style="flex:1;min-width:160px;padding:1rem;">
        <span style="width:44px;height:44px;background:var(--primary);border-radius:10px;display:inline-flex;align-items:center;justify-content:center;margin-right:1rem;box-shadow:0 4px 10px rgba(42,57,90,0.2);">
            <i class="fas fa-chalkboard fa-lg" style="color:#fff;"></i>
        </span>
        <span style="font-size:1.05rem;">New Class</span>
    </a>
    <a href="class_schedule.php" class="quick-action-btn d-flex align-center" style="flex:1;min-width:160px;padding:1rem;">
        <span style="width:44px;height:44px;background:#059669;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;margin-right:1rem;box-shadow:0 4px 10px rgba(5,150,105,0.2);">
            <i class="fas fa-calendar-alt fa-lg" style="color:#fff;"></i>
        </span>
        <span style="font-size:1.05rem;">Schedule</span>
    </a>
    <a href="fee_payments.php" class="quick-action-btn d-flex align-center" style="flex:1;min-width:160px;padding:1rem;">
        <span style="width:44px;height:44px;background:var(--accent);border-radius:10px;display:inline-flex;align-items:center;justify-content:center;margin-right:1rem;box-shadow:0 4px 10px rgba(252,68,102,0.2);">
            <i class="fas fa-wallet fa-lg" style="color:#fff;"></i>
        </span>
        <span style="font-size:1.05rem;">Record Payment</span>
    </a>
    <a href="expenses.php" class="quick-action-btn d-flex align-center" style="flex:1;min-width:160px;padding:1rem;">
        <span style="width:44px;height:44px;background:#ef4444;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;margin-right:1rem;box-shadow:0 4px 10px rgba(239,68,68,0.2);">
            <i class="fas fa-receipt fa-lg" style="color:#fff;"></i>
        </span>
        <span style="font-size:1.05rem;">Record Expense</span>
    </a>
</div>

<!-- Schedule Grid: Days x Time Slots -->
<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-calendar-alt mr-2" style="color:var(--primary);"></i>Weekly Schedule</h2>
        <a href="class_schedule.php" class="btn btn-outline-primary btn-sm">Edit Schedule</a>
    </div>
    <div class="table-wrapper" style="overflow-x:auto;">
        <table class="table" style="min-width:1000px;">
            <thead>
                <tr>
                    <th style="width:120px;">Time</th>
                    <?php foreach ($dayAbbrs as $da): ?>
                        <th style="text-align:center;min-width:130px;"><?= $dayFull[$da] ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($timeSlots as $ts): ?>
                    <tr>
                        <td>
                            <span class="font-semibold" style="font-size:0.8rem;white-space:nowrap;">
                                <?= date('g:i A', strtotime($ts['start_time'])) ?><br>
                                <span class="text-muted" style="font-size:0.7rem;"><?= date('g:i A', strtotime($ts['end_time'])) ?></span>
                            </span>
                        </td>
                        <?php foreach ($dayAbbrs as $da): ?>
                            <td style="vertical-align:top;padding:3px;">
                                <?php
                                $entries = $schedule[$da][$ts['id']] ?? [];
                                if (empty($entries)):
                                ?>
                                    <span style="color:var(--text-muted);opacity:0.3;font-size:0.8rem;">&mdash;</span>
                                <?php else: ?>
                                    <?php foreach ($entries as $e): ?>
                                        <a href="class_view.php?id=<?= $e['class_id'] ?>" class="schedule-cell" style="display:block;background:<?= $e['color'] ?>;text-decoration:none;margin-bottom:3px;">
                                            <div class="subj-name"><?= htmlspecialchars($e['subject_name']) ?></div>
                                            <div class="meta"><?= htmlspecialchars($e['class_name']) ?></div>
                                            <div class="meta"><?= htmlspecialchars($e['teacher_name']) ?></div>
                                        </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>
