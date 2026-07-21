<?php
require_once 'config.php';
requireLogin();
$db = getDB();

// ── Core Stats ──────────────────────────────────────────
$totalStudents    = $db->query("SELECT COUNT(*) FROM students WHERE status='active'")->fetchColumn();
$totalClasses     = $db->query("SELECT COUNT(*) FROM classes WHERE status='active'")->fetchColumn();
$totalTeachers    = $db->query("SELECT COUNT(*) FROM teachers WHERE status='active'")->fetchColumn();
$totalSubjects    = $db->query("SELECT COUNT(*) FROM subjects WHERE status='active'")->fetchColumn();
$monthlyRevenue   = $db->query("SELECT COALESCE(SUM(amount),0) FROM fee_payments WHERE MONTH(payment_date)=MONTH(CURDATE()) AND YEAR(payment_date)=YEAR(CURDATE())")->fetchColumn();
$monthlyExpenses  = $db->query("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE MONTH(expense_date)=MONTH(CURDATE()) AND YEAR(expense_date)=YEAR(CURDATE())")->fetchColumn();
$monthlySalaries  = $db->query("SELECT COALESCE(SUM(amount),0) FROM salary_payments WHERE MONTH(payment_date)=MONTH(CURDATE()) AND YEAR(payment_date)=YEAR(CURDATE())")->fetchColumn();
$netProfit        = $monthlyRevenue - $monthlyExpenses - $monthlySalaries;

// ── Trend: Last Month Revenue ───────────────────────────
$lastMonthRevenue = $db->query("SELECT COALESCE(SUM(amount),0) FROM fee_payments WHERE MONTH(payment_date)=MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(payment_date)=YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))")->fetchColumn();
$revenueTrend = 0;
if ($lastMonthRevenue > 0) {
    $revenueTrend = round(($monthlyRevenue - $lastMonthRevenue) / $lastMonthRevenue * 100);
}

// ── New Enrollments This Month ──────────────────────────
$newEnrollments = $db->query("SELECT COUNT(*) FROM student_enrollments WHERE MONTH(enrollment_date)=MONTH(CURDATE()) AND YEAR(enrollment_date)=YEAR(CURDATE())")->fetchColumn();

// ── Subjects Covered ────────────────────────────────────
$subjectsCovered = $db->query("SELECT COUNT(DISTINCT subject_id) FROM class_subjects WHERE status='active'")->fetchColumn();

// ── Today's Schedule ────────────────────────────────────
$todayAbbr = date('D'); // Mon, Tue, Wed, ...
$todaySchedule = $db->query("
    SELECT ts.start_time, ts.end_time,
           c.name AS class_name, s.name AS subject_name, t.name AS teacher_name
    FROM class_subjects cs
    JOIN time_slots ts ON cs.time_slot_id = ts.id
    JOIN classes c ON cs.class_id = c.id
    JOIN subjects s ON cs.subject_id = s.id
    JOIN teachers t ON cs.teacher_id = t.id
    WHERE cs.status = 'active'
      AND cs.day_of_week = '{$todayAbbr}'
    ORDER BY ts.start_time
    LIMIT 12
")->fetchAll(PDO::FETCH_ASSOC);

// ── Class Capacity ──────────────────────────────────────
$classCapacity = $db->query("
    SELECT c.name, c.max_students,
           (SELECT COUNT(*) FROM students WHERE current_class_id = c.id AND status = 'active') AS enrolled
    FROM classes c
    WHERE c.status = 'active'
    ORDER BY c.name
")->fetchAll(PDO::FETCH_ASSOC);

// ── Pending Fees ────────────────────────────────────────
$pendingFees = $db->query("
    SELECT sf.amount_due - sf.amount_paid AS remaining,
           s.name AS student_name, s.student_uid,
           fs.name AS fee_name, sf.due_date
    FROM student_fees sf
    JOIN students s ON sf.student_id = s.id
    JOIN fee_structures fs ON sf.fee_structure_id = fs.id
    WHERE sf.status IN ('pending', 'partial', 'overdue')
      AND s.status = 'active'
    ORDER BY sf.due_date ASC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$totalPending = $db->query("
    SELECT COALESCE(SUM(sf.amount_due - sf.amount_paid), 0)
    FROM student_fees sf
    JOIN students s ON sf.student_id = s.id
    WHERE sf.status IN ('pending', 'partial', 'overdue')
      AND s.status = 'active'
")->fetchColumn();

// ── Revenue vs Expenses Chart (6 months) ────────────────
$chartData = [];
for ($i = 5; $i >= 0; $i--) {
    $monthStart = date('Y-m-01', strtotime("-{$i} months"));
    $monthEnd   = date('Y-m-t', strtotime("-{$i} months"));
    $monthLabel = date('M', strtotime("-{$i} months"));
    $rev = $db->query("SELECT COALESCE(SUM(amount),0) FROM fee_payments WHERE payment_date BETWEEN '{$monthStart}' AND '{$monthEnd}'")->fetchColumn();
    $exp = $db->query("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE expense_date BETWEEN '{$monthStart}' AND '{$monthEnd}'")->fetchColumn();
    $sal = $db->query("SELECT COALESCE(SUM(amount),0) FROM salary_payments WHERE payment_date BETWEEN '{$monthStart}' AND '{$monthEnd}'")->fetchColumn();
    $chartData[] = ['label' => $monthLabel, 'revenue' => (float)$rev, 'expense' => (float)$exp + (float)$sal];
}
$maxVal = 1;
foreach ($chartData as $d) { $maxVal = max($maxVal, $d['revenue'], $d['expense']); }
$maxVal = max(1000, ceil($maxVal / 1000) * 1000);

// ── Recent Payments ─────────────────────────────────────
$recentPayments = $db->query("
    SELECT fp.amount, fp.payment_date, fp.payment_method,
           s.name AS student_name, s.student_uid,
           fs.name AS fee_name
    FROM fee_payments fp
    JOIN students s ON fp.student_id = s.id
    JOIN fee_structures fs ON fp.fee_structure_id = fs.id
    ORDER BY fp.payment_date DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// ── Recent Expenses ─────────────────────────────────────
$recentExpenses = $db->query("
    SELECT e.amount, e.expense_date, e.description, ec.name AS category_name
    FROM expenses e
    JOIN expense_categories ec ON e.expense_category_id = ec.id
    ORDER BY e.expense_date DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// ── Time-of-Day Greeting ────────────────────────────────
$hour = (int)date('H');
if ($hour < 12) $greeting = 'Good morning';
elseif ($hour < 17) $greeting = 'Good afternoon';
else $greeting = 'Good evening';

require_once 'header.php';
?>

<!-- ═══ LAYER 1: Welcome Banner ═══ -->
<div class="dash-greeting">
    <h2><?= $greeting ?>, <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></h2>
    <div class="greeting-date"><?= date('l, F j, Y') ?></div>
    <div class="greeting-stats">
        <span><i class="fas fa-user-graduate"></i> <?= $totalStudents ?> student<?= $totalStudents !== 1 ? 's' : '' ?></span>
        <span><i class="fas fa-chalkboard"></i> <?= $totalClasses ?> active class<?= $totalClasses !== 1 ? 'es' : '' ?></span>
        <span><i class="fas fa-user-tie"></i> <?= $totalTeachers ?> teacher<?= $totalTeachers !== 1 ? 's' : '' ?></span>
        <span><i class="fas fa-dollar-sign"></i> $<?= number_format($monthlyRevenue, 0) ?> collected this month</span>
    </div>
</div>

<!-- ═══ LAYER 2: KPI Cards ═══ -->
<div class="dash-kpi-row">
    <div class="kpi-card kpi-students">
        <div class="kpi-icon"><i class="fas fa-user-graduate"></i></div>
        <div class="kpi-info">
            <div class="kpi-label">Students</div>
            <div class="kpi-value"><?= $totalStudents ?></div>
            <?php if ($newEnrollments > 0): ?>
                <div class="kpi-trend up"><i class="fas fa-arrow-up"></i> <?= $newEnrollments ?> new this month</div>
            <?php else: ?>
                <div class="kpi-trend neutral">enrolled across <?= $totalClasses ?> classes</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="kpi-card kpi-classes">
        <div class="kpi-icon"><i class="fas fa-chalkboard"></i></div>
        <div class="kpi-info">
            <div class="kpi-label">Active Classes</div>
            <div class="kpi-value"><?= $totalClasses ?></div>
            <div class="kpi-trend neutral"><?= $subjectsCovered ?> subject<?= $subjectsCovered !== 1 ? 's' : '' ?> scheduled</div>
        </div>
    </div>

    <div class="kpi-card kpi-teachers">
        <div class="kpi-icon"><i class="fas fa-user-tie"></i></div>
        <div class="kpi-info">
            <div class="kpi-label">Teachers</div>
            <div class="kpi-value"><?= $totalTeachers ?></div>
            <div class="kpi-trend neutral"><?= $totalSubjects ?> subject<?= $totalSubjects !== 1 ? 's' : '' ?> covered</div>
        </div>
    </div>

    <div class="kpi-card kpi-revenue">
        <div class="kpi-icon"><i class="fas fa-dollar-sign"></i></div>
        <div class="kpi-info">
            <div class="kpi-label">Monthly Revenue</div>
            <div class="kpi-value">$<?= number_format($monthlyRevenue, 0) ?></div>
            <?php if ($lastMonthRevenue > 0): ?>
                <div class="kpi-trend <?= $revenueTrend >= 0 ? 'up' : 'down' ?>">
                    <i class="fas fa-arrow-<?= $revenueTrend >= 0 ? 'up' : 'down' ?>"></i>
                    <?= abs($revenueTrend) ?>% vs last month
                </div>
            <?php else: ?>
                <div class="kpi-trend neutral">this month</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ═══ LAYER 3: Financial Snapshot ═══ -->
<?php
$financeTotal = max($monthlyRevenue, $monthlyExpenses + $monthlySalaries, 1);
$revPct = round(($monthlyRevenue / $financeTotal) * 100);
$expPct = round(($monthlyExpenses / $financeTotal) * 100);
$salPct = round(($monthlySalaries / $financeTotal) * 100);
$netPct = $monthlyRevenue > 0 ? round(abs($netProfit) / $monthlyRevenue * 100) : 0;
?>
<div class="dash-finance-strip">
    <div class="finance-metric fm-revenue">
        <div class="fm-label"><i class="fas fa-arrow-down"></i> Revenue</div>
        <div class="fm-value">$<?= number_format($monthlyRevenue, 0) ?></div>
        <div class="fm-bar"><div class="fm-bar-fill" style="width:<?= $revPct ?>%"></div></div>
    </div>
    <div class="finance-metric fm-expenses">
        <div class="fm-label"><i class="fas fa-receipt"></i> Expenses</div>
        <div class="fm-value">$<?= number_format($monthlyExpenses, 0) ?></div>
        <div class="fm-bar"><div class="fm-bar-fill" style="width:<?= $expPct ?>%"></div></div>
    </div>
    <div class="finance-metric fm-salaries">
        <div class="fm-label"><i class="fas fa-money-check-alt"></i> Salaries</div>
        <div class="fm-value">$<?= number_format($monthlySalaries, 0) ?></div>
        <div class="fm-bar"><div class="fm-bar-fill" style="width:<?= $salPct ?>%"></div></div>
    </div>
    <div class="finance-metric fm-net">
        <div class="fm-label"><i class="fas fa-chart-line"></i> Net Profit</div>
        <div class="fm-value" style="color:<?= $netProfit >= 0 ? 'var(--success)' : 'var(--danger)' ?>"><?= $netProfit >= 0 ? '$' : '-$' ?><?= number_format(abs($netProfit), 0) ?></div>
        <div class="fm-bar"><div class="fm-bar-fill" style="width:<?= $netPct ?>%;background:<?= $netProfit >= 0 ? 'var(--success)' : 'var(--danger)' ?>"></div></div>
    </div>
</div>

<!-- ═══ LAYER 4: Today's Schedule + Class Capacity ═══ -->
<div class="dash-grid-2">
    <!-- Today's Schedule -->
    <div class="card dash-card-compact">
        <div class="card-header">
            <h3><i class="fas fa-calendar-day mr-2" style="color:var(--primary);"></i>Today's Schedule</h3>
            <span class="badge badge-primary"><?= date('D') ?></span>
        </div>
        <?php if (empty($todaySchedule)): ?>
            <div class="dash-empty">
                <i class="fas fa-calendar-times"></i>
                <p>No classes scheduled for <?= date('l') ?></p>
                <a href="class_schedule.php">View full schedule</a>
            </div>
        <?php else: ?>
            <div class="card-body" style="padding-top:0;">
                <div class="schedule-list">
                    <?php foreach ($todaySchedule as $s): ?>
                        <div class="schedule-row">
                            <span class="schedule-time"><?= date('H:i', strtotime($s['start_time'])) ?></span>
                            <span class="schedule-class"><?= htmlspecialchars($s['class_name']) ?></span>
                            <span class="schedule-subject"><?= htmlspecialchars($s['subject_name']) ?></span>
                            <span class="schedule-teacher"><i class="fas fa-user"></i> <?= htmlspecialchars($s['teacher_name']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Class Capacity -->
    <div class="card dash-card-compact">
        <div class="card-header">
            <h3><i class="fas fa-users mr-2" style="color:var(--success);"></i>Class Capacity</h3>
            <span class="badge badge-success"><?= $totalClasses ?> active</span>
        </div>
        <div class="card-body" style="padding-top:0;">
            <?php if (empty($classCapacity)): ?>
                <div class="dash-empty">
                    <i class="fas fa-chalkboard"></i>
                    <p>No active classes</p>
                </div>
            <?php else: ?>
                <div class="capacity-list">
                    <?php foreach ($classCapacity as $c):
                        $pct = $c['max_students'] > 0 ? round(($c['enrolled'] / $c['max_students']) * 100) : 0;
                        $fillClass = $pct >= 90 ? 'high' : ($pct >= 70 ? 'mid' : 'low');
                    ?>
                        <div class="capacity-row">
                            <span class="capacity-name" title="<?= htmlspecialchars($c['name']) ?>"><?= htmlspecialchars($c['name']) ?></span>
                            <div class="capacity-track">
                                <div class="capacity-fill <?= $fillClass ?>" style="width:<?= min($pct, 100) ?>%"></div>
                            </div>
                            <span class="capacity-count"><?= $c['enrolled'] ?>/<?= $c['max_students'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ═══ LAYER 5: Revenue Chart + Pending Fees ═══ -->
<div class="dash-grid-2">
    <!-- Revenue Chart -->
    <div class="card dash-card-compact">
        <div class="card-header">
            <h3><i class="fas fa-chart-bar mr-2" style="color:var(--primary);"></i>Revenue vs Expenses</h3>
            <div class="d-flex gap-3" style="font-size:0.75rem;">
                <span class="d-flex align-center gap-2"><span class="dot" style="background:var(--success);"></span> Revenue</span>
                <span class="d-flex align-center gap-2"><span class="dot" style="background:var(--danger);"></span> Expenses</span>
            </div>
        </div>
        <div class="card-body">
            <?php if (array_sum(array_column($chartData, 'revenue')) == 0 && array_sum(array_column($chartData, 'expense')) == 0): ?>
                <div class="dash-empty">
                    <i class="fas fa-chart-bar"></i>
                    <p>No financial data yet</p>
                </div>
            <?php else: ?>
                <div class="chart-bars">
                    <?php foreach ($chartData as $d): ?>
                        <div class="chart-col">
                            <span class="chart-tip"><span style="color:var(--success);">$<?= number_format($d['revenue'], 0) ?></span> / <span style="color:var(--danger);">$<?= number_format($d['expense'], 0) ?></span></span>
                            <div class="chart-col-bars">
                                <span class="bar-r" style="height:<?= ($d['revenue'] / $maxVal) * 100 ?>%;"></span>
                                <span class="bar-e" style="height:<?= ($d['expense'] / $maxVal) * 100 ?>%;"></span>
                            </div>
                            <span class="chart-label"><?= $d['label'] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="chart-totals">
                    <span><span class="dot" style="background:var(--success);"></span> Revenue: $<?= number_format(array_sum(array_column($chartData, 'revenue')), 0) ?></span>
                    <span><span class="dot" style="background:var(--danger);"></span> Expenses: $<?= number_format(array_sum(array_column($chartData, 'expense')), 0) ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pending Fees -->
    <div class="card dash-card-compact">
        <div class="card-header">
            <h3><i class="fas fa-exclamation-triangle mr-2" style="color:var(--warning);"></i>Pending Fees</h3>
            <?php if ($totalPending > 0): ?>
                <span class="badge badge-danger">$<?= number_format($totalPending, 0) ?> outstanding</span>
            <?php else: ?>
                <span class="badge badge-success"><i class="fas fa-check mr-1"></i>All clear</span>
            <?php endif; ?>
        </div>
        <?php if (empty($pendingFees)): ?>
            <div class="dash-empty">
                <i class="fas fa-check-circle" style="color:var(--success);"></i>
                <p>All fees are up to date</p>
            </div>
        <?php else: ?>
            <div class="card-body" style="padding-top:0;">
                <div class="pending-list">
                    <?php foreach ($pendingFees as $pf):
                        $initials = '';
                        foreach (explode(' ', $pf['student_name']) as $w) $initials .= strtoupper($w[0] ?? '');
                        $initials = substr($initials, 0, 2);
                    ?>
                        <div class="pending-row">
                            <div class="pending-student">
                                <div class="pending-avatar"><?= $initials ?></div>
                                <div class="pending-info">
                                    <div class="pending-name"><?= htmlspecialchars($pf['student_name']) ?></div>
                                    <div class="pending-fee"><?= htmlspecialchars($pf['fee_name']) ?><?= $pf['due_date'] ? ' · Due ' . date('M d', strtotime($pf['due_date'])) : '' ?></div>
                                </div>
                            </div>
                            <div class="pending-amount">$<?= number_format($pf['remaining'], 0) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ═══ LAYER 6: Recent Payments + Recent Expenses ═══ -->
<div class="dash-grid-equal">
    <!-- Recent Payments -->
    <div class="card dash-card-compact">
        <div class="card-header">
            <h3><i class="fas fa-money-bill-wave mr-2" style="color:var(--success);"></i>Recent Payments</h3>
            <a href="fee_payments.php" class="btn btn-sm btn-outline">View All</a>
        </div>
        <div class="table-wrapper">
            <table class="table dash-table">
                <thead><tr><th>Student</th><th>Fee</th><th class="text-right">Amount & Date</th></tr></thead>
                <tbody>
                    <?php if (empty($recentPayments)): ?>
                        <tr><td colspan="3" class="text-center text-muted" style="padding:2rem;">No payments recorded yet.</td></tr>
                    <?php else: foreach ($recentPayments as $p):
                        $initials = '';
                        foreach (explode(' ', $p['student_name']) as $w) $initials .= strtoupper($w[0] ?? '');
                        $initials = substr($initials, 0, 2);
                    ?>
                        <tr>
                            <td>
                                <div class="d-flex align-center" style="gap:0.6rem;">
                                    <div class="activity-avatar av-payment"><?= $initials ?></div>
                                    <div>
                                        <div class="font-medium" style="line-height:1.2;"><?= htmlspecialchars($p['student_name']) ?></div>
                                        <div class="text-xs text-muted"><?= htmlspecialchars($p['student_uid'] ?? '') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-muted text-sm"><?= htmlspecialchars($p['fee_name']) ?></td>
                            <td class="text-right">
                                <div style="color:var(--success);font-weight:700;font-size:0.95rem;">$<?= number_format($p['amount'], 0) ?></div>
                                <div class="text-xs text-muted"><?= date('M d', strtotime($p['payment_date'])) ?> · <span class="badge badge-secondary" style="font-size:0.65rem;padding:0.15rem 0.4rem;"><?= ucfirst(str_replace('_', ' ', $p['payment_method'])) ?></span></div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Expenses -->
    <div class="card dash-card-compact">
        <div class="card-header">
            <h3><i class="fas fa-receipt mr-2" style="color:var(--danger);"></i>Recent Expenses</h3>
            <a href="expenses.php" class="btn btn-sm btn-outline">View All</a>
        </div>
        <div class="table-wrapper">
            <table class="table dash-table">
                <thead><tr><th>Description</th><th>Category</th><th class="text-right">Amount & Date</th></tr></thead>
                <tbody>
                    <?php if (empty($recentExpenses)): ?>
                        <tr><td colspan="3" class="text-center text-muted" style="padding:2rem;">No expenses recorded yet.</td></tr>
                    <?php else: foreach ($recentExpenses as $e): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-center" style="gap:0.6rem;">
                                    <div class="activity-avatar av-expense"><i class="fas fa-receipt" style="font-size:0.7rem;"></i></div>
                                    <div class="font-medium" style="line-height:1.2;"><?= htmlspecialchars($e['description']) ?></div>
                                </div>
                            </td>
                            <td><span class="badge badge-secondary"><?= htmlspecialchars($e['category_name']) ?></span></td>
                            <td class="text-right">
                                <div style="color:var(--danger);font-weight:700;font-size:0.95rem;">$<?= number_format($e['amount'], 0) ?></div>
                                <div class="text-xs text-muted"><?= date('M d', strtotime($e['expense_date'])) ?></div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
