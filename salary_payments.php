<?php
require_once 'config.php';
requireLogin();
$db = getDB();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_salary'])) {
    $teacher_id = (int)$_POST['teacher_id'];
    $amount = (float)$_POST['amount'];
    $payment_date = $_POST['payment_date'];
    $month_for = $_POST['month_for'];
    $payment_method = $_POST['payment_method'];
    $remarks = trim($_POST['remarks']);

    if (!$teacher_id || $amount <= 0 || empty($payment_date) || empty($month_for)) {
        $error = 'Please fill all required fields.';
    } else {
        $db->beginTransaction();
        try {
            $db->prepare("INSERT INTO salary_payments (teacher_id, amount, payment_date, month_for, payment_method, remarks, recorded_by) VALUES (?,?,?,?,?,?,?)")
               ->execute([$teacher_id, $amount, $payment_date, $month_for, $payment_method, $remarks ?: null, $_SESSION['user_id']]);

            // Auto-create expense under Salaries category
            $salaryCat = $db->prepare("SELECT id FROM expense_categories WHERE name='Salaries' LIMIT 1");
            $salaryCat->execute();
            $catId = $salaryCat->fetchColumn();
            if ($catId) {
                $teacherName = $db->prepare("SELECT name FROM teachers WHERE id=?");
                $teacherName->execute([$teacher_id]);
                $teacherName = $teacherName->fetchColumn();
                $db->prepare("INSERT INTO expenses (expense_category_id, description, amount, expense_date, payment_method, recorded_by) VALUES (?,?,?,?,?,?)")
                   ->execute([$catId, "Salary: $teacherName ($month_for)", $amount, $payment_date, $payment_method, $_SESSION['user_id']]);
            }

            $db->commit();
            $success = 'Salary payment recorded.';
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_salary'])) {
    $id = (int)$_POST['id'];
    $db->beginTransaction();
    try {
        // Get salary info to find and delete corresponding expense
        $sp = $db->prepare("SELECT sp.*, t.name as teacher_name FROM salary_payments sp JOIN teachers t ON sp.teacher_id=t.id WHERE sp.id=?");
        $sp->execute([$id]);
        $sp = $sp->fetch();
        if ($sp) {
            $salaryCat = $db->prepare("SELECT id FROM expense_categories WHERE name='Salaries' LIMIT 1");
            $salaryCat->execute();
            $catId = $salaryCat->fetchColumn();
            if ($catId) {
                $db->prepare("DELETE FROM expenses WHERE expense_category_id=? AND description LIKE ? AND amount=? AND expense_date=?")
                   ->execute([$catId, "%Salary: {$sp['teacher_name']} ({$sp['month_for']})%", $sp['amount'], $sp['payment_date']]);
            }
            $db->prepare("DELETE FROM salary_payments WHERE id=?")->execute([$id]);
        }
        $db->commit();
        $success = 'Salary payment deleted.';
    } catch (Exception $e) {
        $db->rollBack();
        $error = 'Error: ' . $e->getMessage();
    }
}

$teachers = $db->query("SELECT id, name, salary FROM teachers WHERE status='active' ORDER BY name")->fetchAll();

$payments = $db->query("
    SELECT sp.*, t.name as teacher_name
    FROM salary_payments sp
    JOIN teachers t ON sp.teacher_id=t.id
    ORDER BY sp.payment_date DESC, sp.id DESC
    LIMIT 50
")->fetchAll();

require_once 'header.php';
?>

<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="d-flex flex-wrap" style="gap:1.5rem;">
    <div style="flex:1 1 300px; max-width:100%;">
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-plus-circle mr-2" style="color:var(--primary);"></i>Pay Salary</h3></div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Teacher *</label>
                        <select name="teacher_id" class="form-select" required id="teacherSelect">
                            <option value="">Select Teacher</option>
                            <?php foreach ($teachers as $t): ?>
                                <option value="<?= $t['id'] ?>" data-salary="<?= $t['salary'] ?>"><?= htmlspecialchars($t['name']) ?> — $<?= number_format($t['salary'], 2) ?>/mo</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Amount ($) *</label>
                            <input type="number" name="amount" class="form-control" id="salaryAmount" placeholder="0.00" min="0.01" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Month For *</label>
                            <input type="month" name="month_for" class="form-control" value="<?= date('Y-m') ?>" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Payment Date *</label>
                            <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Payment Method</label>
                            <select name="payment_method" class="form-select">
                                <option value="cash">Cash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="card">Card</option>
                                <option value="online">Online</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Optional"></textarea>
                    </div>
                    <button type="submit" name="record_salary" class="btn btn-primary w-100"><i class="fas fa-save mr-2"></i>Record Salary Payment</button>
                </form>
            </div>
        </div>
    </div>

    <div style="flex:2 1 500px; max-width:100%;">
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-history mr-2"></i>Salary Payment History (<?= count($payments) ?>)</h3></div>
            <div class="table-wrapper">
                <table class="table">
                    <thead><tr><th>Teacher</th><th>Month</th><th>Date</th><th>Method</th><th>Amount</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php if (empty($payments)): ?>
                            <tr><td colspan="6" class="text-center text-muted" style="padding:3rem;">No salary payments recorded yet.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($payments as $p): ?>
                            <tr>
                                <td class="font-semibold"><?= htmlspecialchars($p['teacher_name']) ?></td>
                                <td><span class="badge badge-primary"><?= htmlspecialchars($p['month_for']) ?></span></td>
                                <td class="text-muted"><?= date('M d, Y', strtotime($p['payment_date'])) ?></td>
                                <td class="text-muted text-sm"><?= ucfirst(str_replace('_', ' ', $p['payment_method'])) ?></td>
                                <td style="color:var(--accent);font-weight:600;">$<?= number_format($p['amount'], 2) ?></td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('Delete this salary payment?')" style="display:inline;">
                                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                        <button type="submit" name="delete_salary" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('teacherSelect').addEventListener('change', function() {
    var selected = this.options[this.selectedIndex];
    var salary = selected.getAttribute('data-salary');
    if (salary) document.getElementById('salaryAmount').value = parseFloat(salary).toFixed(2);
});
</script>

<?php require_once 'footer.php'; ?>
