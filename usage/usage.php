<?php

header('Content-Type: text/html; charset=utf-8');

use MirazMac\HtmlSanitizer\BasicWhitelist;
use MirazMac\HtmlSanitizer\Sanitizer;
use MirazMac\HtmlSanitizer\Whitelist;

require_once '../vendor/autoload.php';

$whitelist = new BasicWhitelist;
$whitelist->setAllowedValues('a', 'href', ['#', '#2']);
$whitelist->setAllowedValues('a', 'title', ['No more']);

$htmlsanitizer = new Sanitizer($whitelist);

$payload = file_get_contents('payload.txt');


echo $htmlsanitizer->sanitize($payload, true);
