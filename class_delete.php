<?php
require_once 'config.php';
requireLogin();

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    $db->prepare("UPDATE students SET current_class_id = NULL WHERE current_class_id = ?")->execute([$id]);
    $db->prepare("DELETE FROM student_reassignments WHERE from_class_id = ? OR to_class_id = ?")->execute([$id, $id]);
    $db->prepare("DELETE FROM classes WHERE id = ?")->execute([$id]);
}

header('Location: classes.php');
exit;
