<?php
require_once 'config.php';
requireLogin();
$db = getDB();

$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;
$preset = $_GET['preset'] ?? null;
$monthParam = $_GET['month'] ?? null;
$weekParam = $_GET['week'] ?? null;

if ($startDate && $endDate) {
    $monthStart = $startDate;
    $monthEnd = $endDate;
} elseif ($weekParam) {
    $today = new DateTime();
    switch($weekParam) {
        case 'this_week':
            $monthStart = (new DateTime('monday this week'))->format('Y-m-d');
            $monthEnd = $today->format('Y-m-d');
            break;
        case 'last_week':
            $monthStart = (new DateTime('monday last week'))->format('Y-m-d');
            $monthEnd = (new DateTime('sunday last week'))->format('Y-m-d');
            break;
        case '2_weeks':
            $monthStart = (new DateTime('-2 weeks monday'))->format('Y-m-d');
            $monthEnd = $today->format('Y-m-d');
            break;
        case '4_weeks':
            $monthStart = (new DateTime('-4 weeks monday'))->format('Y-m-d');
            $monthEnd = $today->format('Y-m-d');
            break;
        default:
            $monthStart = $today->format('Y-m-01');
            $monthEnd = $today->format('Y-m-t');
    }
} elseif ($monthParam) {
    $monthStart = $monthParam . '-01';
    $monthEnd = date('Y-m-t', strtotime($monthStart));
} elseif ($preset) {
    switch($preset) {
        case 'last_month':
            $monthStart = (new DateTime('first day of last month'))->format('Y-m-d');
            $monthEnd = (new DateTime('last day of last month'))->format('Y-m-d');
            break;
        case 'this_quarter':
            $q = ceil((int)date('m') / 3);
            $monthStart = date('Y') . '-' . str_pad(($q - 1) * 3 + 1, 2, '0', STR_PAD_LEFT) . '-01';
            $monthEnd = date('Y-m-t', strtotime($monthStart . ' +2 months'));
            break;
        case 'last_quarter':
            $q = ceil((int)date('m') / 3) - 1;
            if ($q < 1) { $q = 4; $qy = date('Y') - 1; } else { $qy = date('Y'); }
            $monthStart = $qy . '-' . str_pad(($q - 1) * 3 + 1, 2, '0', STR_PAD_LEFT) . '-01';
            $monthEnd = date('Y-m-t', strtotime($monthStart . ' +2 months'));
            break;
        case 'this_year':
            $monthStart = date('Y-01-01');
            $monthEnd = date('Y-12-31');
            break;
        case 'last_year':
            $monthStart = (date('Y') - 1) . '-01-01';
            $monthEnd = (date('Y') - 1) . '-12-31';
            break;
        case 'all_time':
            $monthStart = '2000-01-01';
            $monthEnd = date('Y-12-31');
            break;
        case 'this_month':
        default:
            $monthStart = date('Y-m-01');
            $monthEnd = date('Y-m-t');
            break;
    }
} else {
    $monthStart = date('Y-m-01');
    $monthEnd = date('Y-m-t');
}

$periodLabel = date('M d, Y', strtotime($monthStart)) . ' - ' . date('M d, Y', strtotime($monthEnd));

$totalStudents = $db->query("SELECT COUNT(*) FROM students WHERE status='active'")->fetchColumn();

$monthlyIncome = $db->query("SELECT COALESCE(SUM(amount),0) FROM fee_payments WHERE payment_date BETWEEN '$monthStart' AND '$monthEnd'")->fetchColumn();
$totalIncome = $db->query("SELECT COALESCE(SUM(amount),0) FROM fee_payments")->fetchColumn();

$monthlyExpenses = $db->query("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE expense_date BETWEEN '$monthStart' AND '$monthEnd'")->fetchColumn();
$totalExpensesAll = $db->query("SELECT COALESCE(SUM(amount),0) FROM expenses")->fetchColumn();

$monthlySalaries = $db->query("SELECT COALESCE(SUM(amount),0) FROM salary_payments WHERE payment_date BETWEEN '$monthStart' AND '$monthEnd'")->fetchColumn();
$totalSalariesAll = $db->query("SELECT COALESCE(SUM(amount),0) FROM salary_payments")->fetchColumn();

$monthlyNet = $monthlyIncome - $monthlyExpenses - $monthlySalaries;
$totalNet = $totalIncome - $totalExpensesAll - $totalSalariesAll;

$paidStudents = $db->query("
    SELECT COUNT(DISTINCT fp.student_id)
    FROM fee_payments fp
    WHERE fp.payment_date BETWEEN '$monthStart' AND '$monthEnd'
")->fetchColumn();

$partialStudents = $db->query("
    SELECT COUNT(*) FROM (
        SELECT sf.student_id, sf.amount_due, COALESCE(SUM(fp.amount),0) as total_paid
        FROM student_fees sf
        LEFT JOIN fee_payments fp ON fp.student_id=sf.student_id AND fp.fee_structure_id=sf.fee_structure_id AND fp.payment_date BETWEEN '$monthStart' AND '$monthEnd'
        WHERE sf.status='partial'
        GROUP BY sf.student_id, sf.amount_due
        HAVING total_paid > 0 AND total_paid < sf.amount_due
    ) t
")->fetchColumn();

$unpaidCount = max(0, $totalStudents - $paidStudents - $partialStudents);

$incomeByMethod = $db->query("
    SELECT payment_method, COUNT(*) as cnt, SUM(amount) as total
    FROM fee_payments
    WHERE payment_date BETWEEN '$monthStart' AND '$monthEnd'
    GROUP BY payment_method ORDER BY total DESC
")->fetchAll();

$expenseByCategory = $db->query("
    SELECT ec.name, COALESCE(SUM(e.amount),0) as total
    FROM expense_categories ec
    LEFT JOIN expenses e ON ec.id=e.expense_category_id AND e.expense_date BETWEEN '$monthStart' AND '$monthEnd'
    WHERE ec.status='active'
    GROUP BY ec.id, ec.name
    HAVING total > 0
    ORDER BY total DESC
")->fetchAll();

$recentPayments = $db->query("
    SELECT fp.amount, fp.payment_date, fp.payment_method, s.name as student_name, s.student_uid, fs.name as fee_name
    FROM fee_payments fp
    JOIN students s ON fp.student_id=s.id
    JOIN fee_structures fs ON fp.fee_structure_id=fs.id
    WHERE fp.payment_date BETWEEN '$monthStart' AND '$monthEnd'
    ORDER BY fp.payment_date DESC
")->fetchAll();

$recentExpenses = $db->query("
    SELECT e.amount, e.expense_date, e.description, e.payment_method, ec.name as category_name
    FROM expenses e
    JOIN expense_categories ec ON e.expense_category_id=ec.id
    WHERE e.expense_date BETWEEN '$monthStart' AND '$monthEnd'
    ORDER BY e.expense_date DESC
")->fetchAll();

$monthlyTrend = $db->query("
    SELECT
        DATE_FORMAT(payment_date, '%Y-%m') as m,
        SUM(amount) as income
    FROM fee_payments
    GROUP BY m ORDER BY m DESC LIMIT 6
")->fetchAll();

$feeBreakdown = $db->query("
    SELECT fs.name as fee_name, fs.amount as fee_amount, COUNT(fp.id) as paid_count, COALESCE(SUM(fp.amount),0) as collected
    FROM fee_structures fs
    LEFT JOIN fee_payments fp ON fs.id=fp.fee_structure_id AND fp.payment_date BETWEEN '$monthStart' AND '$monthEnd'
    WHERE fs.status='active'
    GROUP BY fs.id, fs.name, fs.amount
    ORDER BY collected DESC
")->fetchAll();

require_once 'header.php';
?>

<div class="d-flex flex-wrap mb-4" style="gap:1.5rem;">
    <div class="card" style="flex:1;min-width:220px;">
        <div class="card-body text-center">
            <div style="width:48px;height:48px;background:rgba(16,185,129,0.1);border-radius:10px;display:flex;align-items:center;justify-content:center;margin:0 auto 0.75rem;">
                <i class="fas fa-arrow-down" style="color:#059669;font-size:1.25rem;"></i>
            </div>
            <p class="text-muted text-sm mb-1">Period Income</p>
            <p class="font-semibold" style="font-size:1.75rem;color:#059669;">$<?= number_format($monthlyIncome, 2) ?></p>
        </div>
    </div>
    <div class="card" style="flex:1;min-width:220px;">
        <div class="card-body text-center">
            <div style="width:48px;height:48px;background:rgba(239,68,68,0.1);border-radius:10px;display:flex;align-items:center;justify-content:center;margin:0 auto 0.75rem;">
                <i class="fas fa-arrow-up" style="color:#ef4444;font-size:1.25rem;"></i>
            </div>
            <p class="text-muted text-sm mb-1">Period Expenses</p>
            <p class="font-semibold" style="font-size:1.75rem;color:#ef4444;">$<?= number_format($monthlyExpenses, 2) ?></p>
        </div>
    </div>
    <div class="card" style="flex:1;min-width:220px;">
        <div class="card-body text-center">
            <div style="width:48px;height:48px;background:rgba(245,158,11,0.1);border-radius:10px;display:flex;align-items:center;justify-content:center;margin:0 auto 0.75rem;">
                <i class="fas fa-money-check-alt" style="color:#d97706;font-size:1.25rem;"></i>
            </div>
            <p class="text-muted text-sm mb-1">Period Salaries</p>
            <p class="font-semibold" style="font-size:1.75rem;color:#d97706;">$<?= number_format($monthlySalaries, 2) ?></p>
        </div>
    </div>
    <div class="card" style="flex:1;min-width:220px;">
        <div class="card-body text-center">
            <div style="width:48px;height:48px;background:rgba(42,57,90,0.1);border-radius:10px;display:flex;align-items:center;justify-content:center;margin:0 auto 0.75rem;">
                <i class="fas fa-chart-line" style="color:var(--primary);font-size:1.25rem;"></i>
            </div>
            <p class="text-muted text-sm mb-1">Net Balance (Period)</p>
            <p class="font-semibold" style="font-size:1.75rem;color:<?= $monthlyNet >= 0 ? '#059669' : '#ef4444' ?>;"><?= $monthlyNet >= 0 ? '$' : '-$' ?><?= number_format(abs($monthlyNet), 2) ?></p>
        </div>
    </div>
</div>

<div class="d-flex flex-wrap mb-4" style="gap:1rem;">
    <div class="card" style="flex:1;min-width:120px;">
        <div class="card-body text-center" style="padding:1rem;">
            <p class="text-muted text-xs mb-1">All-Time Income</p>
            <p class="font-semibold" style="font-size:1.1rem;color:#059669;">$<?= number_format($totalIncome, 2) ?></p>
        </div>
    </div>
    <div class="card" style="flex:1;min-width:120px;">
        <div class="card-body text-center" style="padding:1rem;">
            <p class="text-muted text-xs mb-1">All-Time Expenses</p>
            <p class="font-semibold" style="font-size:1.1rem;color:#ef4444;">$<?= number_format($totalExpensesAll + $totalSalariesAll, 2) ?></p>
        </div>
    </div>
    <div class="card" style="flex:1;min-width:120px;">
        <div class="card-body text-center" style="padding:1rem;">
            <p class="text-muted text-xs mb-1">All-Time Net</p>
            <p class="font-semibold" style="font-size:1.1rem;color:<?= $totalNet >= 0 ? '#059669' : '#ef4444' ?>;"><?= $totalNet >= 0 ? '$' : '-$' ?><?= number_format(abs($totalNet), 2) ?></p>
        </div>
    </div>
    <div class="card" style="flex:1;min-width:120px;">
        <div class="card-body text-center" style="padding:1rem;">
            <p class="text-muted text-xs mb-1">Active Students</p>
            <p class="font-semibold" style="font-size:1.1rem;color:var(--primary);"><?= $totalStudents ?></p>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body" style="padding:1rem 1.5rem;">
        <form method="GET" id="filterForm" class="d-flex align-center flex-wrap" style="gap:0.75rem;">
            <div class="d-flex align-center" style="gap:0.5rem;">
                <label class="form-label mb-0" style="white-space:nowrap;font-size:0.8rem;">Month</label>
                <select id="presetMonth" class="form-select" style="width:auto;padding:0.4rem 2rem 0.4rem 0.6rem;font-size:0.8rem;">
                    <option value="">Select...</option>
                    <option value="this_month" <?= ($preset==='this_month' || (!$preset && !$startDate && !$weekParam && !$monthParam)) ? 'selected' : '' ?>>This Month</option>
                    <option value="last_month" <?= $preset==='last_month' ? 'selected' : '' ?>>Last Month</option>
                    <option value="this_quarter" <?= $preset==='this_quarter' ? 'selected' : '' ?>>This Quarter</option>
                    <option value="last_quarter" <?= $preset==='last_quarter' ? 'selected' : '' ?>>Last Quarter</option>
                    <option value="this_year" <?= $preset==='this_year' ? 'selected' : '' ?>>This Year</option>
                    <option value="last_year" <?= $preset==='last_year' ? 'selected' : '' ?>>Last Year</option>
                    <option value="all_time" <?= $preset==='all_time' ? 'selected' : '' ?>>All Time</option>
                </select>
            </div>
            <div class="d-flex align-center" style="gap:0.5rem;">
                <label class="form-label mb-0" style="white-space:nowrap;font-size:0.8rem;">Week</label>
                <select id="presetWeek" class="form-select" style="width:auto;padding:0.4rem 2rem 0.4rem 0.6rem;font-size:0.8rem;">
                    <option value="">Select...</option>
                    <option value="this_week" <?= $weekParam==='this_week' ? 'selected' : '' ?>>This Week</option>
                    <option value="last_week" <?= $weekParam==='last_week' ? 'selected' : '' ?>>Last Week</option>
                    <option value="2_weeks" <?= $weekParam==='2_weeks' ? 'selected' : '' ?>>Last 2 Weeks</option>
                    <option value="4_weeks" <?= $weekParam==='4_weeks' ? 'selected' : '' ?>>Last 4 Weeks</option>
                </select>
            </div>
            <div style="width:1px;height:24px;background:var(--border);"></div>
            <div class="d-flex align-center" style="gap:0.5rem;">
                <label class="form-label mb-0" style="white-space:nowrap;font-size:0.8rem;">From</label>
                <input type="date" id="startDate" name="start_date" value="<?= htmlspecialchars($monthStart) ?>" class="form-control" style="width:auto;padding:0.4rem 0.6rem;font-size:0.8rem;">
            </div>
            <div class="d-flex align-center" style="gap:0.5rem;">
                <label class="form-label mb-0" style="white-space:nowrap;font-size:0.8rem;">To</label>
                <input type="date" id="endDate" name="end_date" value="<?= htmlspecialchars($monthEnd) ?>" class="form-control" style="width:auto;padding:0.4rem 0.6rem;font-size:0.8rem;">
            </div>
            <button type="submit" class="btn btn-sm btn-primary" style="padding:0.4rem 1rem;"><i class="fas fa-filter mr-1"></i>Apply</button>
            <a href="?preset=this_month" class="btn btn-sm btn-outline" style="padding:0.4rem 1rem;">Reset</a>
            <span class="ml-auto text-muted text-xs" style="white-space:nowrap;"><i class="fas fa-calendar mr-1"></i><?= htmlspecialchars($periodLabel) ?></span>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const presetMonth = document.getElementById('presetMonth');
    const presetWeek = document.getElementById('presetWeek');
    const startDate = document.getElementById('startDate');
    const endDate = document.getElementById('endDate');
    const form = document.getElementById('filterForm');

    const monthRanges = {
        'this_month':  { s: '<?= date('Y-m-01') ?>', e: '<?= date('Y-m-t') ?>' },
        'last_month':  { s: '<?= (new DateTime("first day of last month"))->format("Y-m-d") ?>', e: '<?= (new DateTime("last day of last month"))->format("Y-m-d") ?>' },
        'this_quarter': { s: '<?= date("Y") ?>-<?= str_pad((ceil(date("m")/3)-1)*3+1, 2, "0", STR_PAD_LEFT) ?>-01', e: '<?= date("Y-m-t", strtotime(date("Y") . "-" . str_pad(ceil(date("m")/3)*3, 2, "0", STR_PAD_LEFT) . "-01")) ?>' },
        'this_year':   { s: '<?= date("Y") ?>-01-01', e: '<?= date("Y") ?>-12-31' },
        'all_time':    { s: '2000-01-01', e: '<?= date("Y") ?>-12-31' }
    };

    const weekRanges = {
        'this_week': { s: '<?= (new DateTime("monday this week"))->format("Y-m-d") ?>', e: '<?= date("Y-m-d") ?>' },
        'last_week': { s: '<?= (new DateTime("monday last week"))->format("Y-m-d") ?>', e: '<?= (new DateTime("sunday last week"))->format("Y-m-d") ?>' },
        '2_weeks':   { s: '<?= (new DateTime("-2 weeks monday"))->format("Y-m-d") ?>', e: '<?= date("Y-m-d") ?>' },
        '4_weeks':   { s: '<?= (new DateTime("-4 weeks monday"))->format("Y-m-d") ?>', e: '<?= date("Y-m-d") ?>' }
    };

    presetMonth.addEventListener('change', function() {
        if (this.value && monthRanges[this.value]) {
            presetWeek.value = '';
            startDate.value = monthRanges[this.value].s;
            endDate.value = monthRanges[this.value].e;
            form.submit();
        }
    });

    presetWeek.addEventListener('change', function() {
        if (this.value && weekRanges[this.value]) {
            presetMonth.value = '';
            startDate.value = weekRanges[this.value].s;
            endDate.value = weekRanges[this.value].e;
            form.submit();
        }
    });

    startDate.addEventListener('change', function() {
        presetMonth.value = '';
        presetWeek.value = '';
    });

    endDate.addEventListener('change', function() {
        presetMonth.value = '';
        presetWeek.value = '';
    });
});
</script>

<div class="d-flex flex-wrap" style="gap:1.5rem;">
    <div style="flex:1 1 300px; max-width:100%;">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-user-check mr-2"></i>Payment Status</h3>
            </div>
            <div class="table-wrapper">
                <table class="table">
                    <thead><tr><th>Status</th><th class="text-right">Students</th></tr></thead>
                    <tbody>
                        <tr>
                            <td><span class="badge badge-success">Paid</span></td>
                            <td class="text-right font-semibold"><?= $paidStudents ?></td>
                        </tr>
                        <tr>
                            <td><span class="badge badge-warning">Partial</span></td>
                            <td class="text-right font-semibold"><?= $partialStudents ?></td>
                        </tr>
                        <tr>
                            <td><span class="badge badge-danger">Unpaid</span></td>
                            <td class="text-right font-semibold"><?= $unpaidCount ?></td>
                        </tr>
                        <tr style="border-top:2px solid var(--border);">
                            <td class="font-semibold">Total Active</td>
                            <td class="text-right font-semibold"><?= $totalStudents ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h3><i class="fas fa-filter mr-2"></i>Income by Method</h3>
            </div>
            <div class="table-wrapper">
                <table class="table">
                    <thead><tr><th>Method</th><th>Count</th><th class="text-right">Amount</th></tr></thead>
                    <tbody>
                        <?php if (empty($incomeByMethod)): ?>
                            <tr><td colspan="3" class="text-center text-muted" style="padding:1.5rem;">No payments this month.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($incomeByMethod as $row): ?>
                            <tr>
                                <td class="font-medium" style="text-transform:capitalize;"><?= htmlspecialchars(str_replace('_',' ',$row['payment_method'])) ?></td>
                                <td><?= $row['cnt'] ?></td>
                                <td class="text-right" style="color:#059669;font-weight:600;">$<?= number_format($row['total'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h3><i class="fas fa-tags mr-2"></i>Fee Collection</h3>
            </div>
            <div class="table-wrapper">
                <table class="table">
                    <thead><tr><th>Fee</th><th>Rate</th><th>Paid</th><th class="text-right">Collected</th></tr></thead>
                    <tbody>
                        <?php foreach ($feeBreakdown as $fb): ?>
                            <tr>
                                <td class="font-medium"><?= htmlspecialchars($fb['fee_name']) ?></td>
                                <td class="text-muted">$<?= number_format($fb['fee_amount'], 2) ?></td>
                                <td><?= $fb['paid_count'] ?></td>
                                <td class="text-right" style="color:#059669;font-weight:600;">$<?= number_format($fb['collected'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($feeBreakdown)): ?>
                            <tr><td colspan="4" class="text-center text-muted" style="padding:1.5rem;">No fee structures defined.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div style="flex:2 1 500px; max-width:100%;">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-money-bill-wave mr-2"></i>Income vs Expenses (Last 6 Months)</h3>
            </div>
            <div class="table-wrapper">
                <table class="table">
                    <thead><tr><th>Month</th><th class="text-right">Income</th></tr></thead>
                    <tbody>
                        <?php foreach ($monthlyTrend as $t): ?>
                            <?php
                            $tExpenses = $db->query("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE DATE_FORMAT(expense_date,'%Y-%m')='{$t['m']}'")->fetchColumn();
                            $tSalaries = $db->query("SELECT COALESCE(SUM(amount),0) FROM salary_payments WHERE DATE_FORMAT(payment_date,'%Y-%m')='{$t['m']}'")->fetchColumn();
                            $tNet = $t['income'] - $tExpenses - $tSalaries;
                            ?>
                            <tr>
                                <td class="font-medium"><?= date('M Y', strtotime($t['m'] . '-01')) ?></td>
                                <td class="text-right">
                                    <span style="color:#059669;font-weight:600;">+$<?= number_format($t['income'], 2) ?></span>
                                    <span class="text-muted" style="margin:0 0.25rem;">/</span>
                                    <span style="color:#ef4444;">-$<?= number_format($tExpenses + $tSalaries, 2) ?></span>
                                    <span class="text-muted" style="margin-left:0.5rem;">=</span>
                                    <span style="font-weight:600;color:<?= $tNet >= 0 ? '#059669' : '#ef4444' ?>;"><?= $tNet >= 0 ? '+' : '' ?>$<?= number_format($tNet, 2) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($monthlyTrend)): ?>
                            <tr><td colspan="2" class="text-center text-muted" style="padding:2rem;">No payment data yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h3><i class="fas fa-file-invoice-dollar mr-2"></i>Income Transactions</h3>
                <span class="badge badge-success" style="font-size:0.85rem;"><?= count($recentPayments) ?> payments</span>
            </div>
            <div class="table-wrapper">
                <table class="table">
                    <thead><tr><th>Student</th><th>Fee</th><th>Method</th><th class="text-right">Amount</th></tr></thead>
                    <tbody>
                        <?php if (empty($recentPayments)): ?>
                            <tr><td colspan="4" class="text-center text-muted" style="padding:2rem;">No income this month.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($recentPayments as $p): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-center">
                                        <span class="badge badge-primary" style="font-family:monospace;font-size:0.7rem;margin-right:0.5rem;"><?= htmlspecialchars($p['student_uid'] ?: '-') ?></span>
                                        <span class="font-medium"><?= htmlspecialchars($p['student_name']) ?></span>
                                    </div>
                                </td>
                                <td class="text-muted"><?= htmlspecialchars($p['fee_name']) ?></td>
                                <td style="text-transform:capitalize;"><?= htmlspecialchars(str_replace('_',' ',$p['payment_method'])) ?></td>
                                <td class="text-right" style="color:#059669;font-weight:600;">$<?= number_format($p['amount'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h3><i class="fas fa-receipt mr-2"></i>Expense Transactions</h3>
                <span class="badge badge-danger" style="font-size:0.85rem;"><?= count($recentExpenses) ?> expenses</span>
            </div>
            <div class="table-wrapper">
                <table class="table">
                    <thead><tr><th>Description</th><th>Category</th><th>Date</th><th class="text-right">Amount</th></tr></thead>
                    <tbody>
                        <?php if (empty($recentExpenses)): ?>
                            <tr><td colspan="4" class="text-center text-muted" style="padding:2rem;">No expenses this month.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($recentExpenses as $e): ?>
                            <tr>
                                <td class="font-medium"><?= htmlspecialchars($e['description']) ?></td>
                                <td><span class="badge badge-secondary"><?= htmlspecialchars($e['category_name']) ?></span></td>
                                <td class="text-muted"><?= date('M d', strtotime($e['expense_date'])) ?></td>
                                <td class="text-right" style="color:#ef4444;font-weight:600;">$<?= number_format($e['amount'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if (!empty($expenseByCategory)): ?>
        <div class="card mt-4">
            <div class="card-header">
                <h3><i class="fas fa-chart-pie mr-2"></i>Expenses by Category</h3>
            </div>
            <div class="table-wrapper">
                <table class="table">
                    <thead><tr><th>Category</th><th class="text-right">Amount</th></tr></thead>
                    <tbody>
                        <?php foreach ($expenseByCategory as $cat): ?>
                            <tr>
                                <td class="font-medium"><?= htmlspecialchars($cat['name']) ?></td>
                                <td class="text-right" style="color:#ef4444;font-weight:600;">$<?= number_format($cat['total'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>
