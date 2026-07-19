<?php
require_once 'config.php';
requireLogin();
$db = getDB();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $institute_name    = trim($_POST['institute_name']);
    $institute_email   = trim($_POST['institute_email']);
    $institute_phone   = trim($_POST['institute_phone']);
    $institute_address = trim($_POST['institute_address']);
    
    $logo_filename = $settings['logo_filename'] ?? '';
    
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];
        
        if (in_array($file_ext, $allowed_exts)) {
            $logo_filename = 'logo_' . time() . '.' . $file_ext;
            $upload_path = $upload_dir . $logo_filename;
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
                if (!empty($settings['logo_filename']) && file_exists($upload_dir . $settings['logo_filename'])) {
                    unlink($upload_dir . $settings['logo_filename']);
                }
            } else {
                $error = 'Failed to upload logo.';
            }
        } else {
            $error = 'Invalid file type. Allowed: jpg, png, gif, svg, webp.';
        }
    }

    if (!$error) {
        $settings_arr = [
            'institute_name'    => $institute_name,
            'institute_email'   => $institute_email,
            'institute_phone'   => $institute_phone,
            'institute_address' => $institute_address,
            'logo_filename'     => $logo_filename,
        ];

        foreach ($settings_arr as $key => $value) {
            $db->prepare("INSERT INTO settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")
               ->execute([$key, $value]);
        }
        $success = 'Settings saved successfully.';
    }
}

$settings = [];
foreach ($db->query("SELECT setting_key, setting_value FROM settings")->fetchAll() as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

require_once 'header.php';
?>

<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div style="max-width:700px;">
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-cog mr-2" style="color:var(--primary);"></i>Institute Settings</h3></div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="form-label">Institute Logo</label>
                    <?php if (!empty($settings['logo_filename']) && file_exists(__DIR__ . '/uploads/' . $settings['logo_filename'])): ?>
                        <div style="margin-bottom: 1rem; padding: 1rem; background: var(--bg-input); border-radius: var(--radius); display: inline-block;">
                            <img src="uploads/<?= htmlspecialchars($settings['logo_filename']) ?>" alt="Current Logo" style="max-height: 80px; max-width: 200px;">
                            <span style="margin-left: 1rem; color: var(--text-light); font-size: 0.875rem;">Current logo</span>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="logo" class="form-control" accept="image/*" style="padding-top: 0.5rem;">
                    <small style="color: var(--text-light);">Max size: 2MB. Formats: jpg, png, gif, svg, webp</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Institute Name</label>
                    <input type="text" name="institute_name" class="form-control" value="<?= htmlspecialchars($settings['institute_name'] ?? '') ?>" placeholder="English Institute">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="institute_email" class="form-control" value="<?= htmlspecialchars($settings['institute_email'] ?? '') ?>" placeholder="info@institute.com">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="text" name="institute_phone" class="form-control" value="<?= htmlspecialchars($settings['institute_phone'] ?? '') ?>" placeholder="+1234567890">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Address</label>
                    <textarea name="institute_address" class="form-control" rows="3" placeholder="Institute address"><?= htmlspecialchars($settings['institute_address'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-2"></i>Save Settings</button>
            </form>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header"><h3><i class="fas fa-info-circle mr-2"></i>System Info</h3></div>
        <div class="card-body">
            <div class="d-flex flex-wrap" style="gap:1.5rem;">
                <div>
                    <p class="text-muted text-sm mb-1">PHP Version</p>
                    <p class="font-semibold"><?= phpversion() ?></p>
                </div>
                <div>
                    <p class="text-muted text-sm mb-1">Server Time</p>
                    <p class="font-semibold"><?= date('F d, Y H:i') ?></p>
                </div>
                <div>
                    <p class="text-muted text-sm mb-1">Database</p>
                    <p class="font-semibold">MySQL / MariaDB</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
