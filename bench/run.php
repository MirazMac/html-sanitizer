<?php

use MirazMac\HtmlSanitizer\BasicWhitelist;
use MirazMac\HtmlSanitizer\Sanitizer;

require_once __DIR__ . '/../vendor/autoload.php';

$sanitizer = new Sanitizer(new BasicWhitelist);


echo "Running...\n";

$input = file_get_contents(__DIR__.'/payload.html');
$times = 100;
$time = microtime(true);


for ($i = 0; $i < $times; ++$i) {
    $output = $sanitizer->sanitize($input);
}

$total = (microtime(true) - $time) * 1000;

echo 'Total for '.$times.' loops: '.round($total, 2)."ms\n";
echo 'Time per loop: '.round($total / $times, 2)."ms\n";
echo "\n";
