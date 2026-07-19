<?php
require_once 'config.php';
requireLogin();
$db = getDB();

$classes = $db->query("
    SELECT c.id, c.name, c.status, c.start_date, c.max_students,
           (SELECT COUNT(*) FROM students WHERE current_class_id=c.id) as student_count,
           (SELECT COUNT(DISTINCT cs.subject_id) FROM class_subjects cs WHERE cs.class_id=c.id AND cs.status='active') as subject_count
    FROM classes c
    ORDER BY c.status ASC, c.name
")->fetchAll();

require_once 'header.php';
?>

<div class="d-flex align-center justify-between mb-4">
    <p class="text-muted"><?= count($classes) ?> class(es) total</p>
    <a href="class_create.php" class="btn btn-primary"><i class="fas fa-plus mr-2"></i>Create Class</a>
</div>

<div class="card">
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr><th>Class Name</th><th>Subjects</th><th>Students</th><th>Start Date</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php if (empty($classes)): ?>
                    <tr><td colspan="6" class="text-center text-muted" style="padding:3rem;">No classes yet. <a href="class_create.php">Create one</a>.</td></tr>
                <?php endif; ?>
                <?php foreach ($classes as $c): ?>
                    <tr>
                        <td class="font-semibold"><?= htmlspecialchars($c['name']) ?></td>
                        <td>
                            <span class="font-semibold"><?= $c['subject_count'] ?></span>
                            <span class="text-muted text-xs">subject<?= $c['subject_count']!=1?'s':'' ?></span>
                        </td>
                        <td>
                            <span class="font-semibold"><?= $c['student_count'] ?></span>
                            <span class="text-muted text-xs">/ <?= $c['max_students'] ?></span>
                        </td>
                        <td class="text-muted"><?= date('M d, Y', strtotime($c['start_date'])) ?></td>
                        <td><span class="badge <?= $c['status']==='active' ? 'badge-success' : ($c['status']==='completed' ? 'badge-primary' : 'badge-secondary') ?>"><?= ucfirst($c['status']) ?></span></td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="class_view.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline" title="View"><i class="fas fa-eye"></i></a>
                                <a href="class_schedule.php?class_id=<?= $c['id'] ?>" class="btn btn-sm btn-success" title="Schedule"><i class="fas fa-calendar-alt"></i></a>
                                <a href="class_edit.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline" title="Edit"><i class="fas fa-edit"></i></a>
                                <a href="class_delete.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this class?')" title="Delete"><i class="fas fa-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'footer.php'; ?>
