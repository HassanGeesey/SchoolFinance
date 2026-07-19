<?php
require_once 'config.php';
requireLogin();

$db = getDB();
$class_id = (int)($_GET['class_id'] ?? 0);

header('Content-Type: application/json');

if ($class_id) {
    $stmt = $db->prepare("SELECT id, name FROM students WHERE current_class_id = ? AND status = 'active' ORDER BY name");
    $stmt->execute([$class_id]);
    echo json_encode($stmt->fetchAll());
} else {
    echo json_encode([]);
}
