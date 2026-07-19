<?php
require_once 'config.php';
requireLogin();
require_once __DIR__ . '/vendor/autoload.php';
$db = getDB();
$error = '';
$success = '';

function generateStudentUID($db) {
    $year = date('y');
    $prefix = $year;
    $stmt = $db->prepare("SELECT COUNT(*) FROM students WHERE student_uid LIKE ?");
    $stmt->execute([$prefix . '%']);
    $count = $stmt->fetchColumn();
    return $prefix . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_student'])) {
    $id = (int)$_POST['id'];
    $db->prepare("DELETE FROM student_reassignments WHERE student_id = ?")->execute([$id]);
    $db->prepare("DELETE FROM student_enrollments WHERE student_id = ?")->execute([$id]);
    $db->prepare("DELETE FROM fee_payments WHERE student_id = ?")->execute([$id]);
    $db->prepare("DELETE FROM student_fees WHERE student_id = ?")->execute([$id]);
    $db->prepare("DELETE FROM students WHERE id = ?")->execute([$id]);
    $success = 'Student deleted successfully';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dropout_student'])) {
    $id = (int)$_POST['id'];
    $db->prepare("UPDATE students SET status='dropped', current_class_id=NULL WHERE id=?")->execute([$id]);
    $success = 'Student marked as dropped out';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_student'])) {
    $id = (int)$_POST['id'];
    $db->prepare("UPDATE students SET status='active' WHERE id=?")->execute([$id]);
    $success = 'Student restored successfully';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll'])) {
    $name = trim($_POST['name'] ?? '');
    $gender = in_array($_POST['gender'] ?? '', ['male', 'female', 'other']) ? $_POST['gender'] : null;
    $phone = trim($_POST['phone'] ?? '');
    $guardian_name = trim($_POST['guardian_name'] ?? '');
    $guardian_phone = trim($_POST['guardian_phone'] ?? '');
    $class_id = (int)($_POST['class_id'] ?? 0);
    
    if (!empty($name) && $class_id > 0) {
        try {
            $student_uid = generateStudentUID($db);
            $stmt = $db->prepare("INSERT INTO students (student_uid, name, gender, phone, guardian_name, guardian_phone, current_class_id, enrollment_date) VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE())");
            $stmt->execute([$student_uid, $name, $gender, $phone, $guardian_name, $guardian_phone, $class_id]);
            $success = "Student enrolled successfully (ID: $student_uid)";
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    } else {
        $error = 'Please fill required fields';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_students'])) {
    if (isset($_FILES['import_file']) && $_FILES['import_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['import_file']['tmp_name'];
        $ext = strtolower(pathinfo($_FILES['import_file']['name'], PATHINFO_EXTENSION));

        try {
            if ($ext === 'xlsx' || $ext === 'xls') {
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file);
                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($file);
            } else {
                $error = 'Please upload a valid .xlsx file. Download the template from the Reports page.';
                goto skip_import;
            }

            // Build class name->id lookup
            $classMap = [];
            foreach ($classes as $c) {
                $classMap[strtolower(trim($c['class_name']))] = $c['id'];
            }

            // Read Students sheet (index 1)
            if ($spreadsheet->getSheetCount() < 2) {
                $error = 'Template must have 2 sheets: Classes and Students.';
                goto skip_import;
            }
            $sheet = $spreadsheet->getSheet(1);
            $rows = $sheet->toArray();
            if (count($rows) < 2) {
                $error = 'The Students sheet is empty.';
                goto skip_import;
            }

            // Skip header row (index 0)
            $dataRows = array_slice($rows, 1);
            $importedCount = 0;
            $skippedCount = 0;
            $errors = [];
            $pass = password_hash('password123', PASSWORD_BCRYPT);

            $db->beginTransaction();
            try {
                foreach ($dataRows as $i => $data) {
                    $name = trim($data[0] ?? '');
                    if (empty($name)) { $skippedCount++; continue; }

                    $gender_raw = strtolower(trim($data[1] ?? ''));
                    $gender = in_array($gender_raw, ['male', 'female', 'other']) ? $gender_raw : null;
                    $email = trim($data[2] ?? '');
                    $phone = trim($data[3] ?? '');
                    $guardian_name = trim($data[4] ?? '');
                    $guardian_phone = trim($data[5] ?? '');
                    $class_name_raw = trim($data[6] ?? '');

                    // Resolve class name to ID
                    $class_id = null;
                    if (!empty($class_name_raw)) {
                        $class_id = $classMap[strtolower($class_name_raw)] ?? null;
                        if ($class_id === null) {
                            $errors[] = "Row " . ($i + 2) . ": Class '$class_name_raw' not found.";
                            $skippedCount++;
                            continue;
                        }
                    }

                    $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name)) . rand(1000, 9999);
                    $stmt = $db->prepare("SELECT id FROM users WHERE username=?");
                    $stmt->execute([$username]);
                    if (!$stmt->fetch()) {
                        $db->prepare("INSERT INTO users (username, password, role, email, phone) VALUES (?, ?, 'student', ?, ?)")->execute([$username, $pass, $email, $phone]);
                        $userId = $db->lastInsertId();
                        $student_uid = generateStudentUID($db);
                        $db->prepare("INSERT INTO students (student_uid, user_id, name, gender, email, phone, guardian_name, guardian_phone, current_class_id, enrollment_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE())")->execute([$student_uid, $userId, $name, $gender, $email, $phone, $guardian_name, $guardian_phone, $class_id]);
                        $importedCount++;
                    }
                }
                $db->commit();
                $msg = "$importedCount students imported successfully";
                if ($skippedCount > 0) $msg .= " ($skippedCount skipped)";
                if (!empty($errors)) $msg .= ". Errors: " . implode('; ', array_slice($errors, 0, 5));
                $success = $msg;
            } catch (Exception $e) {
                $db->rollBack();
                $error = "Error importing students: " . $e->getMessage();
            }
        } catch (Exception $e) {
            $error = "Error reading file: " . $e->getMessage();
        }
        skip_import:;
    } else {
        $error = 'Please upload a valid .xlsx file.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['transfer'])) {
    $student_id = (int)$_POST['student_id'];
    $to_class_id = (int)$_POST['to_class_id'];
    if ($to_class_id > 0) {
        $fromStmt = $db->prepare("SELECT current_class_id FROM students WHERE id=?");
        $fromStmt->execute([$student_id]);
        $fromClassId = $fromStmt->fetchColumn();
        $db->beginTransaction();
        try {
            $db->prepare("UPDATE students SET current_class_id=? WHERE id=?")->execute([$to_class_id, $student_id]);
            $db->prepare("INSERT INTO student_reassignments (student_id, from_class_id, to_class_id, reassign_date, reason) VALUES (?,?,?,CURDATE(),'Admin transfer')")->execute([$student_id, $fromClassId, $to_class_id]);
            $db->commit();
            $success = 'Student transferred successfully';
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Error: ' . $e->getMessage();
        }
    } else {
        $error = 'Please select a class';
    }
}

$classes = $db->query("
    SELECT c.id, c.name as class_name
    FROM classes c
    WHERE c.status='active' ORDER BY c.name
")->fetchAll();

$students = $db->query("
    SELECT s.id, s.student_uid, s.name, s.gender, s.phone, s.guardian_name, s.guardian_phone, s.enrollment_date, s.status, c.id as class_id, c.name as class_name
    FROM students s LEFT JOIN classes c ON s.current_class_id=c.id
    ORDER BY s.status ASC, s.name
")->fetchAll();

require_once 'header.php';
?>

<?php if ($error): ?><div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="d-flex flex-wrap" style="gap:1.5rem;">
    <div style="flex:1 1 300px; max-width:100%;">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-user-plus mr-2" style="color:#059669;"></i>Enroll Student</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Student Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="Enter name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-select">
                            <option value="">Select Gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Student Phone</label>
                        <input type="text" name="phone" class="form-control" placeholder="Enter phone number">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Guardian Name</label>
                        <input type="text" name="guardian_name" class="form-control" placeholder="Enter guardian name">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Guardian Phone</label>
                        <input type="text" name="guardian_phone" class="form-control" placeholder="Enter guardian number">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Assign to Class *</label>
                        <select name="class_id" class="form-select" required>
                            <option value="">Select Class</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?= $class['id'] ?>"><?= htmlspecialchars($class['class_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="enroll" class="btn btn-success w-100">
                        <i class="fas fa-plus mr-2"></i>Enroll Student
                    </button>
                </form>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h3><i class="fas fa-file-import mr-2" style="color:var(--primary);"></i>Import Students (XLSX)</h3>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap mb-3" style="gap:0.5rem;">
                    <a href="download_student_template.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-download mr-1"></i>Download Template
                    </a>
                    <a href="reports.php" class="btn btn-outline btn-sm" style="font-size:0.8rem;">
                        <i class="fas fa-chart-bar mr-1"></i>Reports
                    </a>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label class="form-label">Upload XLSX File</label>
                        <input type="file" name="import_file" class="form-control" accept=".xlsx,.xls" required>
                    </div>
                    <button type="submit" name="import_students" class="btn btn-outline-primary w-100 mb-3">
                        <i class="fas fa-upload mr-2"></i>Import Students
                    </button>
                    <div class="text-xs text-muted" style="padding:0.5rem;background:var(--bg-body);border-radius:6px;line-height:1.6;">
                        <strong style="display:block;margin-bottom:0.25rem;">How it works:</strong>
                        <ol style="margin:0;padding-left:1.2rem;">
                            <li>Download the template above</li>
                            <li>Sheet 1 (Classes) shows available classes</li>
                            <li>Fill in Sheet 2 (Students) — use the <strong>Class dropdown</strong> to select</li>
                            <li>Upload the completed file</li>
                        </ol>
                        <div class="mt-2" style="padding:0.4rem 0.5rem;background:rgba(16,185,129,0.08);border-radius:4px;color:#059669;">
                            <i class="fas fa-info-circle mr-1"></i>Student IDs are auto-generated (e.g. <?= date('y') ?>001). Default password: <code>password123</code>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div style="flex:2 1 500px; max-width:100%;">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-users mr-2" style="color:var(--primary);"></i>All Students (<?= count($students) ?>)</h3>
                <div class="d-flex align-center gap-2">
                    <div style="position:relative;">
                        <i class="fas fa-search" style="position:absolute;left:0.75rem;top:50%;transform:translateY(-50%);color:var(--text-light);font-size:0.8rem;"></i>
                        <input type="text" id="studentSearch" placeholder="Search students..." class="form-control" style="padding-left:2rem;width:220px;">
                    </div>
                    <select id="filterStatus" class="form-select" style="width:auto;padding:0.5rem 2rem 0.5rem 0.75rem;">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="dropped">Dropped</option>
                    </select>
                </div>
            </div>
            <div class="table-wrapper">
                <table class="table" id="studentTable">
                    <thead>
                        <tr>
                            <th style="width:30%;"></th>
                            <th>UID</th>
                            <th>Class</th>
                            <th>Status</th>
                            <th style="width:18%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr data-status="<?= htmlspecialchars($student['status']) ?>" class="student-row" style="cursor:pointer;">
                                <td>
                                    <div class="d-flex align-center">
                                        <i class="fas fa-chevron-right student-toggle-icon" style="font-size:0.65rem;color:var(--text-light);margin-right:0.5rem;transition:transform 0.2s;flex-shrink:0;"></i>
                                        <div style="width:34px;height:34px;background:var(--primary);border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:600;font-size:0.8rem;margin-right:0.625rem;flex-shrink:0;"><?= strtoupper(substr($student['name'],0,1)) ?></div>
                                        <span class="font-medium text-main" style="font-size:0.85rem;"><?= htmlspecialchars($student['name']) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($student['student_uid']): ?>
                                        <span class="badge badge-primary" style="font-family:monospace;font-size:0.8rem;letter-spacing:0.05em;"><?= htmlspecialchars($student['student_uid']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($student['class_id']): ?>
                                        <span class="badge badge-primary"><?= htmlspecialchars($student['class_name']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $student['status']==='active' ? 'badge-success' : ($student['status']==='dropped' ? 'badge-danger' : 'badge-secondary') ?>">
                                        <?= ucfirst($student['status']) ?>
                                    </span>
                                </td>
                                <td onclick="event.stopPropagation();">
                                    <div class="d-flex align-center" style="gap:0.35rem;flex-wrap:nowrap;">
                                        <?php if ($student['status']==='active' && $student['class_id']): ?>
                                            <form method="POST" class="d-flex align-center" style="gap:0.25rem;">
                                                <input type="hidden" name="student_id" value="<?= $student['id'] ?>">
                                                <select name="to_class_id" class="form-select" style="padding:0.2rem 0.4rem;font-size:0.7rem;width:auto;max-width:90px;">
                                                    <option value="">Move...</option>
                                                    <?php foreach ($classes as $class): ?>
                                                        <?php if ($class['id'] != $student['class_id']): ?>
                                                            <option value="<?= $class['id'] ?>"><?= htmlspecialchars($class['class_name']) ?></option>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="submit" name="transfer" class="btn btn-sm" style="background:#f59e0b;color:#fff;padding:0.2rem 0.5rem;font-size:0.7rem;">Go</button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if ($student['status']==='active'): ?>
                                            <form method="POST" onsubmit="return confirm('Mark as dropped out?')">
                                                <input type="hidden" name="id" value="<?= $student['id'] ?>">
                                                <button type="submit" name="dropout_student" class="btn btn-sm btn-warning" title="Drop Out" style="padding:0.25rem 0.5rem;"><i class="fas fa-user-slash" style="font-size:0.7rem;"></i></button>
                                            </form>
                                        <?php elseif ($student['status']==='dropped'): ?>
                                            <form method="POST" onsubmit="return confirm('Restore student?')">
                                                <input type="hidden" name="id" value="<?= $student['id'] ?>">
                                                <button type="submit" name="restore_student" class="btn btn-sm btn-success" title="Restore" style="padding:0.25rem 0.5rem;"><i class="fas fa-undo" style="font-size:0.7rem;"></i></button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="POST" onsubmit="return confirm('Permanently delete this student?')">
                                            <input type="hidden" name="id" value="<?= $student['id'] ?>">
                                            <button type="submit" name="delete_student" class="btn btn-sm btn-danger" title="Delete" style="padding:0.25rem 0.5rem;"><i class="fas fa-trash" style="font-size:0.7rem;"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <tr class="student-detail" style="display:none;">
                                <td colspan="5" style="padding:0;border:none;">
                                    <div style="background:var(--bg-body,#f9fafb);border-left:3px solid var(--primary,#10b981);margin:0 1rem 0.5rem 2.2rem;padding:0.6rem 1rem;border-radius:0 6px 6px 0;display:flex;flex-wrap:wrap;gap:1rem 2rem;font-size:0.8rem;">
                                        <div>
                                            <span class="text-muted" style="font-size:0.7rem;text-transform:uppercase;letter-spacing:0.04em;">Gender</span><br>
                                            <?php if ($student['gender']): ?>
                                                <i class="fas fa-venus-mars" style="font-size:0.7rem;color:var(--text-light);margin-right:0.2rem;"></i>
                                                <span style="text-transform:capitalize;"><?= htmlspecialchars($student['gender']) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <span class="text-muted" style="font-size:0.7rem;text-transform:uppercase;letter-spacing:0.04em;">Phone</span><br>
                                            <?php if ($student['phone']): ?>
                                                <i class="fas fa-phone" style="font-size:0.7rem;color:var(--text-light);margin-right:0.2rem;"></i><?= htmlspecialchars($student['phone']) ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <span class="text-muted" style="font-size:0.7rem;text-transform:uppercase;letter-spacing:0.04em;">Guardian</span><br>
                                            <?php if ($student['guardian_name'] || $student['guardian_phone']): ?>
                                                <i class="fas fa-user" style="font-size:0.7rem;color:var(--text-light);margin-right:0.2rem;"></i>
                                                <?= htmlspecialchars($student['guardian_name'] ?: '-') ?>
                                                <?php if ($student['guardian_phone']): ?>
                                                    <span class="text-muted" style="margin-left:0.4rem;"><i class="fas fa-phone" style="font-size:0.65rem;margin-right:0.15rem;"></i><?= htmlspecialchars($student['guardian_phone']) ?></span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <span class="text-muted" style="font-size:0.7rem;text-transform:uppercase;letter-spacing:0.04em;">Enrolled</span><br>
                                            <i class="fas fa-calendar" style="font-size:0.7rem;color:var(--text-light);margin-right:0.2rem;"></i><?= date('M d, Y', strtotime($student['enrollment_date'])) ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div id="noResults" style="display:none;text-align:center;padding:2rem;color:var(--text-muted);font-size:0.9rem;">
                    <i class="fas fa-search" style="font-size:1.5rem;margin-bottom:0.5rem;display:block;color:var(--text-light);"></i>
                    No students found matching your search.
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('studentSearch');
        const filterStatus = document.getElementById('filterStatus');
        const table = document.getElementById('studentTable');
        const tbody = table.querySelector('tbody');
        const rows = tbody.querySelectorAll('tr.student-row');
        const noResults = document.getElementById('noResults');

        rows.forEach(row => {
            row.addEventListener('click', function(e) {
                if (e.target.closest('form') || e.target.closest('select') || e.target.closest('button') || e.target.closest('a')) return;
                const detailRow = this.nextElementSibling;
                const icon = this.querySelector('.student-toggle-icon');
                if (detailRow && detailRow.classList.contains('student-detail')) {
                    const isVisible = detailRow.style.display !== 'none';
                    detailRow.style.display = isVisible ? 'none' : '';
                    if (icon) icon.style.transform = isVisible ? '' : 'rotate(90deg)';
                }
            });
        });

        function filterTable() {
            const query = searchInput.value.toLowerCase().trim();
            const status = filterStatus.value;
            let visibleCount = 0;

            rows.forEach(row => {
                const detailRow = row.nextElementSibling;
                const text = row.textContent.toLowerCase();
                const rowStatus = row.getAttribute('data-status');
                const matchSearch = !query || text.includes(query);
                const matchStatus = !status || rowStatus === status;

                if (matchSearch && matchStatus) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                    if (detailRow && detailRow.classList.contains('student-detail')) {
                        detailRow.style.display = 'none';
                        const icon = row.querySelector('.student-toggle-icon');
                        if (icon) icon.style.transform = '';
                    }
                }
            });

            noResults.style.display = visibleCount === 0 ? 'block' : 'none';
        }

        searchInput.addEventListener('input', filterTable);
        filterStatus.addEventListener('change', filterTable);
    });
    </script>
</div>

<?php require_once 'footer.php'; ?>
