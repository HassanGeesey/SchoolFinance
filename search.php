<?php
require_once 'config.php';
requireLogin();
$db = getDB();

$query = $_GET['q'] ?? '';
$results = [];

if (strlen($query) >= 2) {
    $searchTerm = "%{$query}%";
    
    $students = $db->prepare("
        SELECT id, name, phone, email
        FROM students WHERE status='active' 
        AND name LIKE ?
        LIMIT 5
    ");
    $students->execute([$searchTerm]);
    foreach ($students as $s) {
        $results[] = [
            'icon' => 'user-graduate',
            'title' => htmlspecialchars($s['name']),
            'subtitle' => 'Student',
            'url' => "students.php?search=" . urlencode($s['name'])
        ];
    }
    
    $teachers = $db->prepare("
        SELECT id, name, phone, email
        FROM teachers WHERE status='active' 
        AND (name LIKE ? OR phone LIKE ?)
        LIMIT 3
    ");
    $teachers->execute([$searchTerm, $searchTerm]);
    foreach ($teachers as $t) {
        $results[] = [
            'icon' => 'user-tie',
            'title' => htmlspecialchars($t['name']),
            'subtitle' => 'Teacher',
            'url' => "teacher_subjects.php?teacher_id=" . $t['id']
        ];
    }
    
    $classes = $db->prepare("
        SELECT id, name
        FROM classes
        WHERE status='active' AND name LIKE ?
        LIMIT 3
    ");
    $classes->execute([$searchTerm]);
    foreach ($classes as $c) {
        $results[] = [
            'icon' => 'chalkboard',
            'title' => htmlspecialchars($c['name']),
            'subtitle' => 'Class',
            'url' => "class_view.php?id=" . $c['id']
        ];
    }

    $subjects = $db->prepare("SELECT id, name, code FROM subjects WHERE status='active' AND (name LIKE ? OR code LIKE ?) LIMIT 3");
    $subjects->execute([$searchTerm, $searchTerm]);
    foreach ($subjects as $s) {
        $results[] = [
            'icon' => 'book',
            'title' => htmlspecialchars($s['name']),
            'subtitle' => 'Subject' . ($s['code'] ? ' (' . htmlspecialchars($s['code']) . ')' : ''),
            'url' => 'subjects.php'
        ];
    }

    $expCats = $db->prepare("SELECT id, name FROM expense_categories WHERE name LIKE ? AND status='active' LIMIT 3");
    $expCats->execute([$searchTerm]);
    foreach ($expCats as $ec) {
        $results[] = [
            'icon' => 'tags',
            'title' => htmlspecialchars($ec['name']),
            'subtitle' => 'Expense Category',
            'url' => 'expense_categories.php'
        ];
    }

    $expSearch = $db->prepare("SELECT e.id, e.description, ec.name as category_name FROM expenses e JOIN expense_categories ec ON e.expense_category_id=ec.id WHERE e.description LIKE ? OR ec.name LIKE ? LIMIT 3");
    $expSearch->execute([$searchTerm, $searchTerm]);
    foreach ($expSearch as $ex) {
        $results[] = [
            'icon' => 'receipt',
            'title' => htmlspecialchars($ex['description']),
            'subtitle' => 'Expense — ' . htmlspecialchars($ex['category_name']),
            'url' => 'expenses.php'
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($results);
