<?php
$content = file_get_contents('c:/xampp/htdocs/stock_management/products/import_products.php');
if (!$content)
    die("Cannot read file\n");
$tokens = token_get_all($content);
$count = 0;
foreach ($tokens as $t) {
    if (is_string($t)) {
        if ($t === '{') {
            $count++;
            echo "Line ? : { (Total: $count)\n";
        }
        elseif ($t === '}') {
            $count--;
            echo "Line ? : } (Total: $count)\n";
        }
    }
    else {
        if ($t[1] === '{') {
            $count++;
            echo "Line " . $t[2] . " : { (Total: $count)\n";
        }
        elseif ($t[1] === '}') {
            $count--;
            echo "Line " . $t[2] . " : } (Total: $count)\n";
        }
    }
}
echo "Final count: $count\n";
