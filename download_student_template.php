<?php
require_once 'config.php';
requireLogin();
$db = getDB();

require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

$spreadsheet = new Spreadsheet();

// ===================== SHEET 1: Classes (Reference) =====================
$classesSheet = $spreadsheet->getActiveSheet();
$classesSheet->setTitle('Classes');

$classes = $db->query("SELECT c.name, (SELECT COUNT(*) FROM students WHERE current_class_id=c.id) as student_count, c.max_students FROM classes c WHERE c.status='active' ORDER BY c.name")->fetchAll();

// Header style
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
    'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '2A395A']],
    'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
];

// Headers
$classesSheet->setCellValue('A1', 'Class Name');
$classesSheet->setCellValue('B1', 'Current Students');
$classesSheet->setCellValue('C1', 'Max Students');
$classesSheet->getStyle('A1:C1')->applyFromArray($headerStyle);

// Column widths
$classesSheet->getColumnDimension('A')->setWidth(30);
$classesSheet->getColumnDimension('B')->setWidth(18);
$classesSheet->getColumnDimension('C')->setWidth(15);

// Data rows
$row = 2;
foreach ($classes as $class) {
    $classesSheet->setCellValue('A'.$row, $class['name']);
    $classesSheet->setCellValue('B'.$row, (int)$class['student_count']);
    $classesSheet->setCellValue('C'.$row, (int)$class['max_students']);
    $classesSheet->getStyle('A'.$row.':C'.$row)->applyFromArray([
        'alignment' => ['vertical' => 'center'],
    ]);
    $row++;
}

$lastClassRow = max($row - 1, 2);

// Auto-filter
$classesSheet->setAutoFilter('A1:C'.$lastClassRow);

// ===================== SHEET 2: Students (Import) =====================
$studentsSheet = $spreadsheet->createSheet();
$studentsSheet->setTitle('Students');

// Headers
$headers = ['Name', 'Gender', 'Email', 'Phone', 'Guardian Name', 'Guardian Phone', 'Class'];
foreach ($headers as $col => $header) {
    $cell = chr(65 + $col) . '1';
    $studentsSheet->setCellValue($cell, $header);
}
$studentsSheet->getStyle('A1:G1')->applyFromArray($headerStyle);

// Column widths
$studentsSheet->getColumnDimension('A')->setWidth(25);
$studentsSheet->getColumnDimension('B')->setWidth(12);
$studentsSheet->getColumnDimension('C')->setWidth(25);
$studentsSheet->getColumnDimension('D')->setWidth(18);
$studentsSheet->getColumnDimension('E')->setWidth(25);
$studentsSheet->getColumnDimension('F')->setWidth(18);
$studentsSheet->getColumnDimension('G')->setWidth(30);

// Example rows
$examples = [
    ['Abdullahi Mohamed', 'male', 'abdullahi@example.com', '+252615123456', 'Mohamed Abdi', '+252615654321', $classes[0]['name'] ?? ''],
    ['Fatima Ahmed', 'female', 'fatima@example.com', '+252615234567', 'Ahmed Hassan', '+252615765432', $classes[1]['name'] ?? $classes[0]['name'] ?? ''],
];
$exampleStyle = [
    'font' => ['italic' => true, 'color' => ['rgb' => '999999']],
];

foreach ($examples as $i => $example) {
    $row = $i + 2;
    foreach ($example as $col => $value) {
        $cell = chr(65 + $col) . $row;
        $studentsSheet->setCellValue($cell, $value);
    }
    $studentsSheet->getStyle('A'.$row.':G'.$row)->applyFromArray($exampleStyle);
}

// ===================== DATA VALIDATION: Gender (Column B) =====================
$genderValidation = new DataValidation();
$genderValidation->setFormula1('"Male,Female,Other"');
$genderValidation->setErrorStyle(DataValidation::STYLE_STOP);
$genderValidation->setError('Please select Male, Female, or Other');
$genderValidation->setPrompt('Select gender from dropdown');
$genderValidation->setPromptTitle('Gender Selection');
$genderValidation->setShowDropDown(true);
$genderValidation->setAllowBlank(true);
// Apply to rows 2 through 1000
$studentsSheet->setDataValidation('B2:B1000', $genderValidation);

// ===================== DATA VALIDATION: Class (Column G) =====================
// Build class name list from the Classes sheet
$classNames = array_column($classes, 'name');
if (!empty($classNames)) {
    // For Excel data validation with list, we use comma-separated values
    // PhpSpreadsheet supports string formula for list validation
    $classList = '"' . implode(',', $classNames) . '"';
    
    $classValidation = new DataValidation();
    $classValidation->setFormula1($classList);
    $classValidation->setErrorStyle(DataValidation::STYLE_STOP);
    $classValidation->setError('Please select a valid class from the dropdown list.');
    $classValidation->setPrompt('Choose a class from the list');
    $classValidation->setPromptTitle('Class Selection');
    $classValidation->setShowDropDown(true);
    $classValidation->setAllowBlank(true);
    // Apply to rows 2 through 1000
    $studentsSheet->setDataValidation('G2:G1000', $classValidation);
}

// Freeze header rows
$classesSheet->freezePane('A2');
$studentsSheet->freezePane('A2');

// Set active sheet to Students
$spreadsheet->setActiveSheetIndex(1);

// ===================== OUTPUT =====================
$filename = 'student_import_template_' . date('Y-m-d') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
