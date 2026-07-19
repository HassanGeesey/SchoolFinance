<?php
require_once 'config.php';
$db = getDB();

try {
    $db->exec("ALTER TABLE students ADD COLUMN student_uid VARCHAR(10) DEFAULT NULL AFTER id;");
    echo "Column 'student_uid' added successfully.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column 'student_uid' already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

try {
    $db->exec("ALTER TABLE students ADD UNIQUE KEY unique_student_uid (student_uid);");
    echo "Unique index 'unique_student_uid' added successfully.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate key name') !== false || strpos($e->getMessage(), 'Duplicate') !== false) {
        echo "Unique index 'unique_student_uid' already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

$students = $db->query("SELECT id, student_uid, enrollment_date FROM students WHERE student_uid IS NULL ORDER BY id")->fetchAll();
if (!empty($students)) {
    echo "Assigning UIDs to " . count($students) . " existing students...\n";
    $db->beginTransaction();
    try {
        foreach ($students as $s) {
            $year = date('y', strtotime($s['enrollment_date']));
            $prefix = $year;
            $stmt = $db->prepare("SELECT COUNT(*) FROM students WHERE student_uid LIKE ?");
            $stmt->execute([$prefix . '%']);
            $count = $stmt->fetchColumn();
            $uid = $prefix . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
            $db->prepare("UPDATE students SET student_uid=? WHERE id=?")->execute([$uid, $s['id']]);
            echo "  Assigned $uid to student #{$s['id']}\n";
        }
        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        echo "Error assigning UIDs: " . $e->getMessage() . "\n";
    }
} else {
    echo "All students already have UIDs.\n";
}

echo "\nDone!\n";
?>
