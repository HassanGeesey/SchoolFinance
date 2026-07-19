<?php
require_once 'config.php';
requireLogin();
$db = getDB();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_expense'])) {
    $category_id = (int)$_POST['expense_category_id'];
    $description = trim($_POST['description']);
    $amount = (float)$_POST['amount'];
    $expense_date = $_POST['expense_date'];
    $payment_method = $_POST['payment_method'];
    $reference_number = trim($_POST['reference_number']);

    if (!$category_id || empty($description) || $amount <= 0 || empty($expense_date)) {
        $error = 'Please fill all required fields.';
    } else {
        $db->prepare("INSERT INTO expenses (expense_category_id, description, amount, expense_date, payment_method, reference_number, recorded_by) VALUES (?,?,?,?,?,?,?)")
           ->execute([$category_id, $description, $amount, $expense_date, $payment_method, $reference_number ?: null, $_SESSION['user_id']]);
        $success = 'Expense recorded successfully.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_expense'])) {
    $db->prepare("DELETE FROM expenses WHERE id=?")->execute([(int)$_POST['id']]);
    $success = 'Expense deleted.';
}

$categories = $db->query("SELECT id, name FROM expense_categories WHERE status='active' ORDER BY name")->fetchAll();

$expenses = $db->query("
    SELECT e.*, ec.name as category_name
    FROM expenses e
    JOIN expense_categories ec ON e.expense_category_id=ec.id
    ORDER BY e.expense_date DESC, e.id DESC
    LIMIT 50
")->fetchAll();

require_once 'header.php';
?>

<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="d-flex flex-wrap" style="gap:1.5rem;">
    <div style="flex:1 1 300px; max-width:100%;">
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-plus-circle mr-2" style="color:var(--primary);"></i>Record Expense</h3></div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Category *</label>
                        <select name="expense_category_id" class="form-select" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description *</label>
                        <input type="text" name="description" class="form-control" placeholder="e.g. Office rent for July" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Amount ($) *</label>
                            <input type="number" name="amount" class="form-control" placeholder="0.00" min="0.01" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Date *</label>
                            <input type="date" name="expense_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Payment Method</label>
                            <select name="payment_method" class="form-select">
                                <option value="cash">Cash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="card">Card</option>
                                <option value="online">Online</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Reference #</label>
                            <input type="text" name="reference_number" class="form-control" placeholder="Optional">
                        </div>
                    </div>
                    <button type="submit" name="record_expense" class="btn btn-primary w-100"><i class="fas fa-save mr-2"></i>Record Expense</button>
                </form>
            </div>
        </div>
    </div>

    <div style="flex:2 1 500px; max-width:100%;">
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-receipt mr-2"></i>Recent Expenses (<?= count($expenses) ?>)</h3></div>
            <div class="table-wrapper">
                <table class="table">
                    <thead><tr><th>Date</th><th>Category</th><th>Description</th><th>Method</th><th>Amount</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php if (empty($expenses)): ?>
                            <tr><td colspan="6" class="text-center text-muted" style="padding:3rem;">No expenses recorded yet.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($expenses as $e): ?>
                            <tr>
                                <td class="text-muted"><?= date('M d, Y', strtotime($e['expense_date'])) ?></td>
                                <td><span class="badge badge-secondary"><?= htmlspecialchars($e['category_name']) ?></span></td>
                                <td class="font-medium"><?= htmlspecialchars($e['description']) ?></td>
                                <td class="text-muted text-sm"><?= ucfirst(str_replace('_', ' ', $e['payment_method'])) ?></td>
                                <td style="color:var(--accent);font-weight:600;">$<?= number_format($e['amount'], 2) ?></td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('Delete this expense?')" style="display:inline;">
                                        <input type="hidden" name="id" value="<?= $e['id'] ?>">
                                        <button type="submit" name="delete_expense" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
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

<?php require_once 'footer.php'; ?>
