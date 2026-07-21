<?php
require_once 'config.php';
requireLogin();
$db = getDB();

$totalStudents   = $db->query("SELECT COUNT(*) FROM students WHERE status='active'")->fetchColumn();
$totalClasses    = $db->query("SELECT COUNT(*) FROM classes WHERE status='active'")->fetchColumn();
$totalTeachers   = $db->query("SELECT COUNT(*) FROM teachers WHERE status='active'")->fetchColumn();
$totalSubjects   = $db->query("SELECT COUNT(*) FROM subjects WHERE status='active'")->fetchColumn();
$monthlyRevenue  = $db->query("SELECT COALESCE(SUM(amount),0) FROM fee_payments WHERE MONTH(payment_date)=MONTH(CURDATE())")->fetchColumn();
$monthlyExpenses = $db->query("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE MONTH(expense_date)=MONTH(CURDATE())")->fetchColumn();
$monthlySalaries = $db->query("SELECT COALESCE(SUM(amount),0) FROM salary_payments WHERE MONTH(payment_date)=MONTH(CURDATE())")->fetchColumn();
$netProfit       = $monthlyRevenue - $monthlyExpenses - $monthlySalaries;

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

$recentPayments = $db->query("
    SELECT fp.amount, fp.payment_date, fp.payment_method, s.name AS student_name, fs.name AS fee_name
    FROM fee_payments fp JOIN students s ON fp.student_id = s.id JOIN fee_structures fs ON fp.fee_structure_id = fs.id
    ORDER BY fp.payment_date DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$recentExpenses = $db->query("
    SELECT e.amount, e.expense_date, e.description, ec.name AS category_name
    FROM expenses e JOIN expense_categories ec ON e.expense_category_id = ec.id
    ORDER BY e.expense_date DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

require_once 'header.php';
?>

<div class="d-flex flex-wrap" style="gap:1.5rem;">
    <div style="flex:2 1 500px;">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-bolt mr-2" style="color:var(--primary);"></i>Quick Actions</h3>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap" style="gap:0.75rem;">
                    <a href="class_create.php" class="btn btn-primary"><i class="fas fa-chalkboard mr-1"></i> New Class</a>
                    <a href="class_schedule.php" class="btn btn-outline"><i class="fas fa-calendar-alt mr-1"></i> Schedule</a>
                    <a href="fee_payments.php" class="btn btn-outline"><i class="fas fa-wallet mr-1"></i> Record Payment</a>
                    <a href="expenses.php" class="btn btn-outline"><i class="fas fa-receipt mr-1"></i> Record Expense</a>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h3><i class="fas fa-chart-bar mr-2" style="color:var(--primary);"></i>Revenue vs Expenses</h3>
                <div class="d-flex gap-3" style="font-size:0.8rem;">
                    <span class="d-flex align-center gap-2"><span style="width:10px;height:10px;border-radius:2px;background:var(--success);display:inline-block;"></span> Revenue</span>
                    <span class="d-flex align-center gap-2"><span style="width:10px;height:10px;border-radius:2px;background:var(--danger);display:inline-block;"></span> Expenses</span>
                </div>
            </div>
            <div class="card-body">
                <?php if (array_sum(array_column($chartData, 'revenue')) == 0 && array_sum(array_column($chartData, 'expense')) == 0): ?>
                    <p class="text-center text-muted" style="padding:2rem 0;">No financial data yet.</p>
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
                <?php endif; ?>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h3><i class="fas fa-money-bill-wave mr-2" style="color:var(--primary);"></i>Recent Payments</h3>
                <a href="fee_payments.php" class="btn btn-sm btn-outline">View All</a>
            </div>
            <div class="table-wrapper">
                <table class="table">
                    <thead><tr><th>Student</th><th>Fee</th><th>Amount</th><th>Date</th><th>Method</th></tr></thead>
                    <tbody>
                        <?php if (empty($recentPayments)): ?>
                            <tr><td colspan="5" class="text-center text-muted" style="padding:1.5rem;">No payments recorded yet.</td></tr>
                        <?php else: foreach ($recentPayments as $p): ?>
                            <tr>
                                <td class="font-medium"><?= htmlspecialchars($p['student_name']) ?></td>
                                <td class="text-muted"><?= htmlspecialchars($p['fee_name']) ?></td>
                                <td style="color:var(--success);font-weight:600;">$<?= number_format($p['amount'], 2) ?></td>
                                <td class="text-muted"><?= date('M d', strtotime($p['payment_date'])) ?></td>
                                <td><span class="badge badge-secondary"><?= ucfirst(str_replace('_', ' ', $p['payment_method'])) ?></span></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div style="flex:1 1 300px;">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-info-circle mr-2" style="color:var(--primary);"></i>Overview</h3>
            </div>
            <div class="card-body">
                <div class="d-flex flex-column" style="gap:0.75rem;">
                    <div class="d-flex align-center justify-between">
                        <span class="d-flex align-center gap-2 text-muted"><i class="fas fa-chalkboard-teacher" style="width:16px;text-align:center;"></i> Classes</span>
                        <span class="font-semibold"><?= $totalClasses ?></span>
                    </div>
                    <div class="d-flex align-center justify-between">
                        <span class="d-flex align-center gap-2 text-muted"><i class="fas fa-users" style="width:16px;text-align:center;"></i> Students</span>
                        <span class="font-semibold"><?= $totalStudents ?></span>
                    </div>
                    <div class="d-flex align-center justify-between">
                        <span class="d-flex align-center gap-2 text-muted"><i class="fas fa-user-tie" style="width:16px;text-align:center;"></i> Teachers</span>
                        <span class="font-semibold"><?= $totalTeachers ?></span>
                    </div>
                    <div class="d-flex align-center justify-between">
                        <span class="d-flex align-center gap-2 text-muted"><i class="fas fa-book" style="width:16px;text-align:center;"></i> Subjects</span>
                        <span class="font-semibold"><?= $totalSubjects ?></span>
                    </div>
                    <div style="height:1px;background:var(--border);"></div>
                    <div class="d-flex align-center justify-between">
                        <span class="d-flex align-center gap-2 text-muted"><i class="fas fa-dollar-sign" style="width:16px;text-align:center;color:var(--success);"></i> Revenue</span>
                        <span class="font-semibold" style="color:var(--success);">$<?= number_format($monthlyRevenue, 0) ?></span>
                    </div>
                    <div class="d-flex align-center justify-between">
                        <span class="d-flex align-center gap-2 text-muted"><i class="fas fa-receipt" style="width:16px;text-align:center;color:var(--danger);"></i> Expenses</span>
                        <span class="font-semibold" style="color:var(--danger);">$<?= number_format($monthlyExpenses + $monthlySalaries, 0) ?></span>
                    </div>
                    <div class="d-flex align-center justify-between">
                        <span class="d-flex align-center gap-2 text-muted"><i class="fas fa-chart-line" style="width:16px;text-align:center;color:<?= $netProfit >= 0 ? 'var(--success)' : 'var(--danger)' ?>;"></i> Net Profit</span>
                        <span class="font-semibold" style="color:<?= $netProfit >= 0 ? 'var(--success)' : 'var(--danger)' ?>;"><?= $netProfit >= 0 ? '$' : '-$' ?><?= number_format(abs($netProfit), 0) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h3><i class="fas fa-receipt mr-2" style="color:var(--primary);"></i>Recent Expenses</h3>
                <a href="expenses.php" class="btn btn-sm btn-outline">View All</a>
            </div>
            <div class="table-wrapper">
                <table class="table">
                    <thead><tr><th>Description</th><th>Category</th><th>Amount</th><th>Date</th></tr></thead>
                    <tbody>
                        <?php if (empty($recentExpenses)): ?>
                            <tr><td colspan="4" class="text-center text-muted" style="padding:1.5rem;">No expenses recorded yet.</td></tr>
                        <?php else: foreach ($recentExpenses as $e): ?>
                            <tr>
                                <td class="font-medium"><?= htmlspecialchars($e['description']) ?></td>
                                <td class="text-muted"><?= htmlspecialchars($e['category_name']) ?></td>
                                <td style="color:var(--danger);font-weight:600;">$<?= number_format($e['amount'], 2) ?></td>
                                <td class="text-muted"><?= date('M d', strtotime($e['expense_date'])) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
