<?php
require_once 'config.php';
requireLogin();
$db = getDB();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $status = $_POST['status'] ?? 'active';

    if (empty($name)) {
        $error = 'Category name is required.';
    } else {
        $db->prepare("INSERT INTO expense_categories (name, description, status) VALUES (?,?,?)")
           ->execute([$name, $description, $status]);
        $success = 'Expense category added.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category'])) {
    $id = (int)$_POST['id'];
    $count = $db->prepare("SELECT COUNT(*) FROM expenses WHERE expense_category_id=?");
    $count->execute([$id]);
    if ($count->fetchColumn() > 0) {
        $error = 'Cannot delete category — it has existing expenses. Set it to inactive instead.';
    } else {
        $db->prepare("DELETE FROM expense_categories WHERE id=?")->execute([$id]);
        $success = 'Expense category deleted.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $id = (int)$_POST['id'];
    $newStatus = $_POST['new_status'];
    $db->prepare("UPDATE expense_categories SET status=? WHERE id=?")->execute([$newStatus, $id]);
    $success = 'Category status updated.';
}

$categories = $db->query("
    SELECT ec.*, (SELECT COUNT(*) FROM expenses WHERE expense_category_id=ec.id) as expense_count,
           (SELECT COALESCE(SUM(amount),0) FROM expenses WHERE expense_category_id=ec.id) as total_spent
    FROM expense_categories ec ORDER BY ec.name
")->fetchAll();

require_once 'header.php';
?>

<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="d-flex flex-wrap" style="gap:1.5rem;">
    <div style="flex:1 1 300px; max-width:100%;">
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-plus-circle mr-2" style="color:var(--primary);"></i>Add Category</h3></div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Rent" required>
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
                    <button type="submit" name="add_category" class="btn btn-primary w-100"><i class="fas fa-save mr-2"></i>Save Category</button>
                </form>
            </div>
        </div>
    </div>

    <div style="flex:2 1 500px; max-width:100%;">
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-list mr-2"></i>Expense Categories (<?= count($categories) ?>)</h3></div>
            <div class="table-wrapper">
                <table class="table">
                    <thead><tr><th>Name</th><th>Description</th><th>Total Spent</th><th>Expenses</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                            <tr><td colspan="6" class="text-center text-muted" style="padding:3rem;">No categories defined yet.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($categories as $c): ?>
                            <tr>
                                <td class="font-semibold"><?= htmlspecialchars($c['name']) ?></td>
                                <td class="text-muted text-sm"><?= htmlspecialchars($c['description'] ?? '-') ?></td>
                                <td style="color:var(--accent);font-weight:600;">$<?= number_format($c['total_spent'], 2) ?></td>
                                <td><span class="badge badge-secondary"><?= $c['expense_count'] ?></span></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                        <input type="hidden" name="new_status" value="<?= $c['status'] === 'active' ? 'inactive' : 'active' ?>">
                                        <button type="submit" name="toggle_status" class="btn btn-sm <?= $c['status'] === 'active' ? 'btn-success' : 'btn-secondary' ?>" title="Toggle status">
                                            <?= ucfirst($c['status']) ?>
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('Delete this category?')" style="display:inline;">
                                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                        <button type="submit" name="delete_category" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
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
