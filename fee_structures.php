<?php
require_once 'config.php';
requireLogin();
$db = getDB();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_fee'])) {
    $name        = trim($_POST['name']);
    $amount      = (float)$_POST['amount'];
    $fee_type    = $_POST['fee_type'];
    $description = trim($_POST['description']);
    $status      = $_POST['status'];

    if (empty($name) || $amount <= 0) {
        $error = 'Name and amount are required.';
    } else {
        $db->prepare("INSERT INTO fee_structures (name,amount,fee_type,description,status) VALUES (?,?,?,?,?)")
           ->execute([$name,$amount,$fee_type,$description,$status]);
        $success = 'Fee structure added.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_fee'])) {
    $db->prepare("DELETE FROM fee_structures WHERE id=?")->execute([(int)$_POST['id']]);
    $success = 'Fee structure deleted.';
}

$fees    = $db->query("SELECT fs.* FROM fee_structures fs ORDER BY fs.name")->fetchAll();

require_once 'header.php';
?>

<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="d-flex flex-wrap" style="gap:1.5rem;">
    <div style="flex:1 1 300px; max-width:100%;">
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-plus-circle mr-2" style="color:var(--primary);"></i>Add Fee Structure</h3></div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Monthly Tuition" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Amount ($) *</label>
                        <input type="number" name="amount" class="form-control" placeholder="0.00" min="0" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fee Type</label>
                        <select name="fee_type" class="form-select">
                            <option value="monthly">Monthly</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="yearly">Yearly</option>
                            <option value="one-time">One-Time</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Optional"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <button type="submit" name="add_fee" class="btn btn-primary w-100"><i class="fas fa-save mr-2"></i>Save Fee Structure</button>
                </form>
            </div>
        </div>
    </div>

    <div style="flex:2 1 500px; max-width:100%;">
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-list mr-2"></i>Fee Structures (<?= count($fees) ?>)</h3></div>
            <div class="table-wrapper">
                <table class="table">
                    <thead><tr><th>Name</th><th>Amount</th><th>Type</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php if (empty($fees)): ?>
                            <tr><td colspan="5" class="text-center text-muted" style="padding:3rem;">No fee structures defined yet.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($fees as $f): ?>
                            <tr>
                                <td class="font-semibold"><?= htmlspecialchars($f['name']) ?></td>
                                <td style="color:var(--accent);font-weight:600;">$<?= number_format($f['amount'],2) ?></td>
                                <td><span class="badge badge-secondary"><?= ucfirst($f['fee_type']) ?></span></td>
                                <td><span class="badge <?= $f['status']==='active' ? 'badge-success' : 'badge-secondary' ?>"><?= ucfirst($f['status']) ?></span></td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('Delete this fee structure?')" style="display:inline;">
                                        <input type="hidden" name="id" value="<?= $f['id'] ?>">
                                        <button type="submit" name="delete_fee" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
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
