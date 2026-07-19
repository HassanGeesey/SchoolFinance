<?php
$files = glob("*.php");
$count = 0;
foreach ($files as $f) {
    if ($f == 'students.php' || $f == 'teachers.php') continue;
    $content = file_get_contents($f);
    $new = preg_replace('/style="flex:\s*1;\s*min-width:\s*300px;"/', 'style="flex:1 1 300px; max-width:100%;"', $content);
    $new = preg_replace('/style="flex:\s*2;\s*min-width:\s*0;"/', 'style="flex:2 1 500px; max-width:100%;"', $new);
    if ($new !== $content) {
        file_put_contents($f, $new);
        $count++;
    }
}
echo "Refactored $count files.";
?>
