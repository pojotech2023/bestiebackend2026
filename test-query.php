<?php
$lines = file(__DIR__.'/app/Http/Controllers/Admin/OrderController.php');
foreach ($lines as $i => $line) {
    if (strpos($line, 'Excel::download') !== false) {
        echo ($i + 1) . ": " . trim($line) . "\n";
    }
}
