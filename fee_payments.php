<?php
require_once 'config.php';
requireLogin();
$db = getDB();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_payment'])) {
    $student_id      = (int)$_POST['student_id'];
    $fee_structure_id= (int)$_POST['fee_structure_id'];
    $amount          = (float)$_POST['amount'];
    $payment_date    = $_POST['payment_date'];
    $payment_method  = $_POST['payment_method'];
    $transaction_id  = trim($_POST['transaction_id']);
    $remarks         = trim($_POST['remarks']);

    if (!$student_id || !$fee_structure_id || $amount <= 0) {
        $error = 'Please fill all required fields.';
    } else {
        $db->prepare("INSERT INTO fee_payments (student_id,fee_structure_id,amount,payment_date,payment_method,transaction_id,remarks,received_by) VALUES (?,?,?,?,?,?,?,1)")
           ->execute([$student_id,$fee_structure_id,$amount,$payment_date,$payment_method,$transaction_id,$remarks]);
        $success = 'Payment recorded successfully.';
    }
}

$students    = $db->query("SELECT id, name FROM students WHERE status='active' ORDER BY name")->fetchAll();
$feeStructures = $db->query("SELECT id, name, amount FROM fee_structures WHERE status='active' ORDER BY name")->fetchAll();

$payments = $db->query("
    SELECT fp.*, s.name as student_name, fs.name as fee_name
    FROM fee_payments fp
    JOIN students s ON fp.student_id=s.id
    JOIN fee_structures fs ON fp.fee_structure_id=fs.id
    ORDER BY fp.payment_date DESC
    LIMIT 50
")->fetchAll();

require_once 'header.php';
?>

<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="d-flex flex-wrap" style="gap:1.5rem;">
    <div style="flex:1 1 300px; max-width:100%;">
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-plus-circle mr-2" style="color:var(--primary);"></i>Record Payment</h3></div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Student *</label>
                        <select name="student_id" class="form-select" required>
                            <option value="">Select Student</option>
                            <?php foreach ($students as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fee Structure *</label>
                        <select name="fee_structure_id" class="form-select" required>
                            <option value="">Select Fee</option>
                            <?php foreach ($feeStructures as $f): ?>
                                <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['name']) ?> ($<?= number_format($f['amount'],2) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Amount ($) *</label>
                        <input type="number" name="amount" class="form-control" placeholder="0.00" min="0" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Payment Date *</label>
                        <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-select">
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="online">Online</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Transaction ID</label>
                        <input type="text" name="transaction_id" class="form-control" placeholder="Optional">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Optional"></textarea>
                    </div>
                    <button type="submit" name="record_payment" class="btn btn-primary w-100"><i class="fas fa-save mr-2"></i>Record Payment</button>
                </form>
            </div>
        </div>
    </div>

    <div style="flex:2 1 500px; max-width:100%;">
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-history mr-2"></i>Recent Payments</h3></div>
            <div class="table-wrapper">
                <table class="table">
                    <thead><tr><th>Student</th><th>Fee</th><th>Amount</th><th>Date</th><th>Method</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php if (empty($payments)): ?>
                            <tr><td colspan="6" class="text-center text-muted" style="padding:3rem;">No payments recorded yet.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($payments as $p): ?>
                            <tr>
                                <td class="font-medium"><?= htmlspecialchars($p['student_name']) ?></td>
                                <td class="text-muted"><?= htmlspecialchars($p['fee_name']) ?></td>
                                <td style="color:var(--accent);font-weight:600;">$<?= number_format($p['amount'],2) ?></td>
                                <td class="text-muted"><?= date('M d, Y', strtotime($p['payment_date'])) ?></td>
                                <td><span class="badge badge-secondary"><?= ucfirst(str_replace('_',' ',$p['payment_method'])) ?></span></td>
                                <td><a href="receipt.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline" target="_blank"><i class="fas fa-receipt mr-1"></i>Receipt</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
