<?php
require_once 'config.php';
requireLogin();
$db = getDB();
$error = '';
$success = '';
$currentUserId = $_SESSION['admin_id'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role     = $_POST['role'];
    $email    = trim($_POST['email']);

    if (empty($username) || empty($password)) {
        $error = 'Username and password are required.';
    } else {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $db->prepare("INSERT INTO users (username,password,role,email,status) VALUES (?,?,?,?,'active')")->execute([$username,$hash,$role,$email]);
            $success = 'User added successfully.';
        } catch (Exception $e) {
            $error = 'Username already exists.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $uid = (int)$_POST['id'];
    if ($uid !== $currentUserId) {
        $db->prepare("UPDATE users SET status = CASE WHEN status='active' THEN 'inactive' ELSE 'active' END WHERE id=?")->execute([$uid]);
        $success = 'User status updated.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $uid = (int)$_POST['id'];
    if ($uid !== $currentUserId) {
        $db->prepare("DELETE FROM users WHERE id=?")->execute([$uid]);
        $success = 'User deleted.';
    }
}

$users = $db->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();

require_once 'header.php';
?>

<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="d-flex flex-wrap" style="gap:1.5rem;">
    <div style="flex:1;min-width:280px;max-width:340px;">
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-user-plus mr-2" style="color:var(--primary);"></i>Add User</h3></div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Username *</label>
                        <input type="text" name="username" class="form-control" placeholder="Enter username" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password *</label>
                        <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="Optional">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select">
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <button type="submit" name="add" class="btn btn-primary w-100"><i class="fas fa-save mr-2"></i>Add User</button>
                </form>
            </div>
        </div>
    </div>

    <div style="flex:2 1 500px; max-width:100%;">
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-users mr-2"></i>All Users (<?= count($users) ?>)</h3></div>
            <div class="table-wrapper">
                <table class="table">
                    <thead><tr><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td>
                                    <span class="font-semibold"><?= htmlspecialchars($u['username']) ?></span>
                                    <?php if ($u['id'] == $currentUserId): ?>
                                        <span class="badge badge-accent ml-2" style="font-size:0.65rem;">You</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-muted"><?= htmlspecialchars($u['email'] ?? '-') ?></td>
                                <td>
                                    <span class="badge <?= $u['role']==='admin' ? 'badge-primary' : 'badge-secondary' ?>">
                                        <?= ucfirst($u['role']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= $u['status']==='active' ? 'badge-success' : 'badge-secondary' ?>">
                                        <?= ucfirst($u['status']) ?>
                                    </span>
                                </td>
                                <td class="text-muted"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                                <td>
                                    <?php if ($u['id'] != $currentUserId): ?>
                                        <div class="d-flex gap-2">
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                                <button type="submit" name="toggle_status" class="btn btn-sm btn-outline" title="Toggle Status">
                                                    <i class="fas <?= $u['status']==='active' ? 'fa-toggle-on' : 'fa-toggle-off' ?>"></i>
                                                </button>
                                            </form>
                                            <form method="POST" onsubmit="return confirm('Delete this user?')" style="display:inline;">
                                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                                <button type="submit" name="delete" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted text-sm text-xs">Current session</span>
                                    <?php endif; ?>
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
