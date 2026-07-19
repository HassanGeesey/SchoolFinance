<?php
require_once 'config.php';
requireLogin();
$db = getDB();

if (isset($_GET['action']) && $_GET['action'] === 'download_template') {
    header('Location: download_student_template.php');
    exit;
}

// Stats
$totalStudents  = $db->query("SELECT COUNT(*) FROM students WHERE status='active'")->fetchColumn();
$totalTeachers  = $db->query("SELECT COUNT(*) FROM teachers WHERE status='active'")->fetchColumn();
$totalClasses   = $db->query("SELECT COUNT(*) FROM classes WHERE status='active'")->fetchColumn();
$totalPayments  = $db->query("SELECT COALESCE(SUM(amount),0) FROM fee_payments")->fetchColumn();
$monthlyRevenue = $db->query("SELECT COALESCE(SUM(amount),0) FROM fee_payments WHERE MONTH(payment_date)=MONTH(CURDATE())")->fetchColumn();
$monthlyExpenses = $db->query("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE MONTH(expense_date)=MONTH(CURDATE())")->fetchColumn();
$monthlySalaries = $db->query("SELECT COALESCE(SUM(amount),0) FROM salary_payments WHERE MONTH(payment_date)=MONTH(CURDATE())")->fetchColumn();
$totalExpensesAll = $db->query("SELECT COALESCE(SUM(amount),0) FROM expenses")->fetchColumn();
$totalSalariesAll = $db->query("SELECT COALESCE(SUM(amount),0) FROM salary_payments")->fetchColumn();
$netProfit = $monthlyRevenue - $monthlyExpenses - $monthlySalaries;

// Recent payments
$recentPayments = $db->query("
    SELECT fp.amount, fp.payment_date, s.name as student_name, fs.name as fee_name
    FROM fee_payments fp JOIN students s ON fp.student_id=s.id JOIN fee_structures fs ON fp.fee_structure_id=fs.id
    ORDER BY fp.payment_date DESC LIMIT 10
")->fetchAll();

// Expenses by category
$expensesByCategory = $db->query("
    SELECT ec.name, COALESCE(SUM(e.amount),0) as total
    FROM expense_categories ec
    LEFT JOIN expenses e ON ec.id=e.expense_category_id AND MONTH(e.expense_date)=MONTH(CURDATE())
    WHERE ec.status='active'
    GROUP BY ec.id, ec.name
    HAVING total > 0
    ORDER BY total DESC
")->fetchAll();

// Recent salary payments
$recentSalaries = $db->query("
    SELECT sp.amount, sp.payment_date, sp.month_for, t.name as teacher_name
    FROM salary_payments sp JOIN teachers t ON sp.teacher_id=t.id
    ORDER BY sp.payment_date DESC LIMIT 5
")->fetchAll();

// Classes with student counts
$classesByLevel = $db->query("
    SELECT c.name as class_name, (SELECT COUNT(*) FROM students WHERE current_class_id=c.id) as student_count
    FROM classes c WHERE c.status='active' ORDER BY c.name
")->fetchAll();

require_once 'header.php';
?>

<div class="d-flex flex-wrap mb-4" style="gap:1.5rem;">
    <div class="card" style="flex:1;min-width:150px;">
        <div class="card-body text-center">
            <div style="width:48px;height:48px;background:rgba(42,57,90,0.1);border-radius:10px;display:flex;align-items:center;justify-content:center;margin:0 auto 0.75rem;">
                <i class="fas fa-user-graduate" style="color:var(--primary);font-size:1.25rem;"></i>
            </div>
            <p class="text-muted text-sm mb-1">Active Students</p>
            <p class="font-semibold" style="font-size:1.75rem;"><?= $totalStudents ?></p>
        </div>
    </div>
    <div class="card" style="flex:1;min-width:150px;">
        <div class="card-body text-center">
            <div style="width:48px;height:48px;background:rgba(139,92,246,0.1);border-radius:10px;display:flex;align-items:center;justify-content:center;margin:0 auto 0.75rem;">
                <i class="fas fa-user-tie" style="color:#7c3aed;font-size:1.25rem;"></i>
            </div>
            <p class="text-muted text-sm mb-1">Teachers</p>
            <p class="font-semibold" style="font-size:1.75rem;"><?= $totalTeachers ?></p>
        </div>
    </div>
    <div class="card" style="flex:1;min-width:150px;">
        <div class="card-body text-center">
            <div style="width:48px;height:48px;background:rgba(16,185,129,0.1);border-radius:10px;display:flex;align-items:center;justify-content:center;margin:0 auto 0.75rem;">
                <i class="fas fa-chalkboard" style="color:#059669;font-size:1.25rem;"></i>
            </div>
            <p class="text-muted text-sm mb-1">Active Classes</p>
            <p class="font-semibold" style="font-size:1.75rem;"><?= $totalClasses ?></p>
        </div>
    </div>
    <div class="card" style="flex:1;min-width:150px;">
        <div class="card-body text-center">
            <div style="width:48px;height:48px;background:rgba(252,68,102,0.1);border-radius:10px;display:flex;align-items:center;justify-content:center;margin:0 auto 0.75rem;">
                <i class="fas fa-dollar-sign" style="color:var(--accent);font-size:1.25rem;"></i>
            </div>
            <p class="text-muted text-sm mb-1">Total Revenue</p>
            <p class="font-semibold" style="font-size:1.75rem;color:var(--accent);">$<?= number_format($totalPayments, 0) ?></p>
        </div>
    </div>
    <div class="card" style="flex:1;min-width:150px;">
        <div class="card-body text-center">
            <div style="width:48px;height:48px;background:rgba(239,68,68,0.1);border-radius:10px;display:flex;align-items:center;justify-content:center;margin:0 auto 0.75rem;">
                <i class="fas fa-receipt" style="color:#ef4444;font-size:1.25rem;"></i>
            </div>
            <p class="text-muted text-sm mb-1">Total Expenses</p>
            <p class="font-semibold" style="font-size:1.75rem;color:#ef4444;">$<?= number_format($totalExpensesAll + $totalSalariesAll, 0) ?></p>
        </div>
    </div>
    <div class="card" style="flex:1;min-width:150px;">
        <div class="card-body text-center">
            <div style="width:48px;height:48px;background:rgba(16,185,129,0.1);border-radius:10px;display:flex;align-items:center;justify-content:center;margin:0 auto 0.75rem;">
                <i class="fas fa-chart-line" style="color:#059669;font-size:1.25rem;"></i>
            </div>
            <p class="text-muted text-sm mb-1">Net Profit (This Month)</p>
            <p class="font-semibold" style="font-size:1.75rem;color:<?= $netProfit >= 0 ? '#059669' : '#ef4444' ?>;"><?= $netProfit >= 0 ? '$' : '-$' ?><?= number_format(abs($netProfit), 0) ?></p>
        </div>
    </div>
</div>

<div class="d-flex flex-wrap" style="gap:1.5rem;">
    <div style="flex:1 1 300px; max-width:100%;">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-layer-group mr-2"></i>Students by Class</h3>
                <a href="?action=download_template" class="btn btn-sm btn-outline-primary" style="font-size:0.75rem;">
                    <i class="fas fa-download mr-1"></i>Import Template
                </a>
            </div>
            <div class="table-wrapper">
                <table class="table">
                    <thead><tr><th>Class</th><th>Students</th></tr></thead>
                    <tbody>
                        <?php foreach ($classesByLevel as $row): ?>
                            <tr>
                                <td class="font-medium"><?= htmlspecialchars($row['class_name']) ?></td>
                                <td><span class="badge badge-primary"><?= $row['student_count'] ?? 0 ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h3><i class="fas fa-tags mr-2"></i>Expenses by Category (This Month)</h3>
                <span class="badge badge-danger" style="font-size:0.9rem;">Total: $<?= number_format($monthlyExpenses + $monthlySalaries, 0) ?></span>
            </div>
            <div class="table-wrapper">
                <table class="table">
                    <thead><tr><th>Category</th><th>Amount</th></tr></thead>
                    <tbody>
                        <?php if (empty($expensesByCategory)): ?>
                            <tr><td colspan="2" class="text-center text-muted" style="padding:2rem;">No expenses this month.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($expensesByCategory as $row): ?>
                            <tr>
                                <td class="font-medium"><?= htmlspecialchars($row['name']) ?></td>
                                <td style="color:#ef4444;font-weight:600;">$<?= number_format($row['total'], 2) ?></td>
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
                <h3><i class="fas fa-receipt mr-2"></i>Recent Payments</h3>
                <span class="badge badge-accent" style="font-size:0.9rem;">This month: $<?= number_format($monthlyRevenue,0) ?></span>
            </div>
            <div class="table-wrapper">
                <table class="table">
                    <thead><tr><th>Payment Details</th><th class="text-right">Amount & Date</th></tr></thead>
                    <tbody>
                        <?php if (empty($recentPayments)): ?>
                            <tr><td colspan="4" class="text-center text-muted" style="padding:3rem;">No payments recorded.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($recentPayments as $p): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-center">
                                        <div style="width:38px;height:38px;background:rgba(42,57,90,0.1);border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--primary);margin-right:0.75rem;"><i class="fas fa-file-invoice-dollar"></i></div>
                                        <div>
                                            <div class="font-semibold text-main" style="line-height:1.2;margin-bottom:0.15rem;font-size:0.95rem;"><?= htmlspecialchars($p['student_name']) ?></div>
                                            <div class="text-xs text-muted" style="line-height:1;"><?= htmlspecialchars($p['fee_name']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-right">
                                    <div style="color:var(--accent);font-weight:700;font-size:1.05rem;line-height:1.1;">$<?= number_format($p['amount'],2) ?></div>
                                    <div class="text-xs text-muted mt-1"><i class="far fa-calendar-alt mr-1"></i><?= date('M d', strtotime($p['payment_date'])) ?></div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h3><i class="fas fa-money-check-alt mr-2"></i>Recent Salary Payments</h3>
            </div>
            <div class="table-wrapper">
                <table class="table">
                    <thead><tr><th>Teacher</th><th>Month</th><th>Date</th><th class="text-right">Amount</th></tr></thead>
                    <tbody>
                        <?php if (empty($recentSalaries)): ?>
                            <tr><td colspan="4" class="text-center text-muted" style="padding:2rem;">No salary payments yet.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($recentSalaries as $s): ?>
                            <tr>
                                <td class="font-medium"><?= htmlspecialchars($s['teacher_name']) ?></td>
                                <td><span class="badge badge-primary"><?= htmlspecialchars($s['month_for']) ?></span></td>
                                <td class="text-muted"><?= date('M d', strtotime($s['payment_date'])) ?></td>
                                <td class="text-right" style="color:#ef4444;font-weight:600;">$<?= number_format($s['amount'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
