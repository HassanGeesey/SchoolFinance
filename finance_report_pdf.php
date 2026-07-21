<?php
require_once 'vendor/autoload.php';
require_once 'config.php';
requireLogin();
$db = getDB();

$schoolName = getAppName();

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
$generatedDate = date('M d, Y h:i A');

$totalStudents = $db->query("SELECT COUNT(*) FROM students WHERE status='active'")->fetchColumn();

$monthlyIncome = $db->query("SELECT COALESCE(SUM(amount),0) FROM fee_payments WHERE payment_date BETWEEN '$monthStart' AND '$monthEnd'")->fetchColumn();
$totalIncome = $db->query("SELECT COALESCE(SUM(amount),0) FROM fee_payments")->fetchColumn();

$monthlyExpenses = $db->query("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE expense_date BETWEEN '$monthStart' AND '$monthEnd'")->fetchColumn();
$totalExpensesAll = $db->query("SELECT COALESCE(SUM(amount),0) FROM expenses")->fetchColumn();

$monthlySalaries = $db->query("SELECT COALESCE(SUM(amount),0) FROM salary_payments WHERE payment_date BETWEEN '$monthStart' AND '$monthEnd'")->fetchColumn();
$totalSalariesAll = $db->query("SELECT COALESCE(SUM(amount),0) FROM salary_payments")->fetchColumn();

$monthlyNet = $monthlyIncome - $monthlyExpenses - $monthlySalaries;
$totalNet = $totalIncome - $totalExpensesAll - $totalSalariesAll;

$absMonthlyNet = abs($monthlyNet);
$totalAllExpenses = $totalExpensesAll + $totalSalariesAll;
$absTotalNet = abs($totalNet);
$netColor = $monthlyNet >= 0 ? '#3d7a52' : '#b84c4c';
$totalNetColor = $totalNet >= 0 ? '#3d7a52' : '#b84c4c';
$netSign = $monthlyNet >= 0 ? '' : '-';
$totalNetSign = $totalNet >= 0 ? '' : '-';

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

function pdfNum($n) { return number_format($n, 2); }
function e($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

ob_start();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: sans-serif; color: #333; font-size: 10pt; line-height: 1.4; }
    .header { text-align: center; padding-bottom: 12pt; border-bottom: 2pt solid #2a395a; margin-bottom: 16pt; }
    .header h1 { font-size: 18pt; color: #2a395a; margin-bottom: 2pt; }
    .header .subtitle { font-size: 11pt; color: #666; }
    .header .period { font-size: 10pt; color: #888; margin-top: 4pt; }
    .header .generated { font-size: 8pt; color: #aaa; margin-top: 2pt; }
    h2 { font-size: 12pt; color: #2a395a; margin: 14pt 0 8pt 0; padding-bottom: 4pt; border-bottom: 1pt solid #e5e7eb; }
    .summary-row table { width: 100%; border-collapse: collapse; margin-bottom: 10pt; }
    .summary-row td { width: 25%; text-align: center; padding: 8pt 4pt; border: 1pt solid #e5e7eb; }
    .summary-row .label { font-size: 8pt; color: #888; text-transform: uppercase; letter-spacing: 0.5pt; }
    .summary-row .value { font-size: 14pt; font-weight: bold; margin-top: 3pt; }
    .income { color: #3d7a52; }
    .expense { color: #b84c4c; }
    .warning { color: #b89040; }
    .primary { color: #2a395a; }
    .alltime-row table { width: 100%; border-collapse: collapse; margin-bottom: 12pt; }
    .alltime-row td { width: 25%; text-align: center; padding: 6pt 4pt; border: 1pt solid #e5e7eb; background: #f9fafb; }
    .alltime-row .label { font-size: 7pt; color: #888; text-transform: uppercase; }
    .alltime-row .value { font-size: 10pt; font-weight: bold; margin-top: 2pt; }
    table.data { width: 100%; border-collapse: collapse; margin-bottom: 12pt; font-size: 9pt; }
    table.data th { background: #f3f4f6; color: #2a395a; font-size: 8pt; text-transform: uppercase; letter-spacing: 0.3pt; padding: 6pt 8pt; text-align: left; border-bottom: 2pt solid #e5e7eb; }
    table.data td { padding: 5pt 8pt; border-bottom: 1pt solid #f0f0f0; }
    table.data tr:nth-child(even) td { background: #fafbfc; }
    .text-right { text-align: right !important; }
    .bold { font-weight: bold; }
    .green { color: #3d7a52; font-weight: bold; }
    .red { color: #b84c4c; font-weight: bold; }
    .badge { display: inline-block; padding: 1pt 6pt; border-radius: 3pt; font-size: 7.5pt; font-weight: bold; color: #fff; }
    .badge-success { background: #3d7a52; }
    .badge-warning { background: #b89040; }
    .badge-danger { background: #b84c4c; }
    .badge-secondary { background: #6c757d; }
    .footer { margin-top: 20pt; padding-top: 8pt; border-top: 1pt solid #e5e7eb; text-align: center; font-size: 7.5pt; color: #aaa; }
    @page { margin: 15mm 12mm; }
</style>
</head>
<body>

<div class="header">
    <h1><?= e($schoolName) ?></h1>
    <div class="subtitle">Finance Report</div>
    <div class="period">Period: <?= e($periodLabel) ?></div>
    <div class="generated">Generated: <?= e($generatedDate) ?></div>
</div>

<div class="summary-row">
<table>
<tr>
    <td><div class="label">Period Income</div><div class="value income">$<?= pdfNum($monthlyIncome) ?></div></td>
    <td><div class="label">Period Expenses</div><div class="value expense">$<?= pdfNum($monthlyExpenses) ?></div></td>
    <td><div class="label">Period Salaries</div><div class="value warning">$<?= pdfNum($monthlySalaries) ?></div></td>
    <td><div class="label">Net Balance (Period)</div><div class="value" style="color:<?= $netColor ?>;"><?= $netSign ?>$<?= pdfNum($absMonthlyNet) ?></div></td>
</tr>
</table>
</div>

<div class="alltime-row">
<table>
<tr>
    <td><div class="label">All-Time Income</div><div class="value income">$<?= pdfNum($totalIncome) ?></div></td>
    <td><div class="label">All-Time Expenses</div><div class="value expense">$<?= pdfNum($totalAllExpenses) ?></div></td>
    <td><div class="label">All-Time Net</div><div class="value" style="color:<?= $totalNetColor ?>;"><?= $totalNetSign ?>$<?= pdfNum($absTotalNet) ?></div></td>
    <td><div class="label">Active Students</div><div class="value primary"><?= $totalStudents ?></div></td>
</tr>
</table>
</div>

<h2>Payment Status</h2>
<table class="data">
<thead><tr><th>Status</th><th class="text-right">Students</th></tr></thead>
<tbody>
<tr><td><span class="badge badge-success">Paid</span></td><td class="text-right bold"><?= $paidStudents ?></td></tr>
<tr><td><span class="badge badge-warning">Partial</span></td><td class="text-right bold"><?= $partialStudents ?></td></tr>
<tr><td><span class="badge badge-danger">Unpaid</span></td><td class="text-right bold"><?= $unpaidCount ?></td></tr>
<tr><td class="bold" style="border-top:1pt solid #333;">Total Active</td><td class="text-right bold" style="border-top:1pt solid #333;"><?= $totalStudents ?></td></tr>
</tbody>
</table>

<?php if (!empty($incomeByMethod)): ?>
<h2>Income by Method</h2>
<table class="data">
<thead><tr><th>Method</th><th>Count</th><th class="text-right">Amount</th></tr></thead>
<tbody>
<?php foreach ($incomeByMethod as $row): ?>
<tr>
    <td><?= e(ucfirst(str_replace('_', ' ', $row['payment_method']))) ?></td>
    <td><?= $row['cnt'] ?></td>
    <td class="text-right green">$<?= pdfNum($row['total']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

<?php if (!empty($feeBreakdown)): ?>
<h2>Fee Collection</h2>
<table class="data">
<thead><tr><th>Fee</th><th>Rate</th><th>Paid</th><th class="text-right">Collected</th></tr></thead>
<tbody>
<?php foreach ($feeBreakdown as $fb): ?>
<tr>
    <td><?= e($fb['fee_name']) ?></td>
    <td>$<?= pdfNum($fb['fee_amount']) ?></td>
    <td><?= $fb['paid_count'] ?></td>
    <td class="text-right green">$<?= pdfNum($fb['collected']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

<?php if (!empty($monthlyTrend)): ?>
<h2>Monthly Trend (Last 6 Months)</h2>
<table class="data">
<thead><tr><th>Month</th><th class="text-right">Income</th><th class="text-right">Expenses</th><th class="text-right">Net</th></tr></thead>
<tbody>
<?php foreach ($monthlyTrend as $t):
    $monthLabel = date('M Y', strtotime($t['m'] . '-01'));
    $tExpenses = $db->query("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE DATE_FORMAT(expense_date,'%Y-%m')='{$t['m']}'")->fetchColumn();
    $tSalaries = $db->query("SELECT COALESCE(SUM(amount),0) FROM salary_payments WHERE DATE_FORMAT(payment_date,'%Y-%m')='{$t['m']}'")->fetchColumn();
    $tTotalExp = $tExpenses + $tSalaries;
    $tNet = $t['income'] - $tTotalExp;
    $tNetColor = $tNet >= 0 ? 'green' : 'red';
    $tNetSign = $tNet >= 0 ? '' : '-';
?>
<tr>
    <td class="bold"><?= $monthLabel ?></td>
    <td class="text-right green">$<?= pdfNum($t['income']) ?></td>
    <td class="text-right red">$<?= pdfNum($tTotalExp) ?></td>
    <td class="text-right <?= $tNetColor ?>"><?= $tNetSign ?>$<?= pdfNum(abs($tNet)) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

<?php if (!empty($recentPayments)): ?>
<h2>Income Transactions (<?= count($recentPayments) ?> payments)</h2>
<table class="data">
<thead><tr><th>Student</th><th>UID</th><th>Fee</th><th>Method</th><th>Date</th><th class="text-right">Amount</th></tr></thead>
<tbody>
<?php foreach ($recentPayments as $p): ?>
<tr>
    <td><?= e($p['student_name']) ?></td>
    <td><?= e($p['student_uid'] ?? '-') ?></td>
    <td><?= e($p['fee_name']) ?></td>
    <td><?= e(ucfirst(str_replace('_', ' ', $p['payment_method']))) ?></td>
    <td><?= date('M d', strtotime($p['payment_date'])) ?></td>
    <td class="text-right green">$<?= pdfNum($p['amount']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

<?php if (!empty($recentExpenses)): ?>
<h2>Expense Transactions (<?= count($recentExpenses) ?> expenses)</h2>
<table class="data">
<thead><tr><th>Description</th><th>Category</th><th>Date</th><th class="text-right">Amount</th></tr></thead>
<tbody>
<?php foreach ($recentExpenses as $e): ?>
<tr>
    <td><?= e($e['description']) ?></td>
    <td><span class="badge badge-secondary"><?= e($e['category_name']) ?></span></td>
    <td><?= date('M d', strtotime($e['expense_date'])) ?></td>
    <td class="text-right red">$<?= pdfNum($e['amount']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

<?php if (!empty($expenseByCategory)): ?>
<h2>Expenses by Category</h2>
<table class="data">
<thead><tr><th>Category</th><th class="text-right">Amount</th></tr></thead>
<tbody>
<?php foreach ($expenseByCategory as $cat): ?>
<tr>
    <td><?= e($cat['name']) ?></td>
    <td class="text-right red">$<?= pdfNum($cat['total']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

<div class="footer">
    <?= e($schoolName) ?> &mdash; Finance Report &mdash; <?= e($periodLabel) ?> &mdash; Generated <?= e($generatedDate) ?>
</div>

</body>
</html>
<?php
$html = ob_get_clean();

$dompdf = new \Dompdf\Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = 'Finance_Report_' . date('Y-m-d') . '.pdf';
$dompdf->stream($filename, ['Attachment' => true]);
exit;
