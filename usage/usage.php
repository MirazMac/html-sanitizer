<?php

header('Content-Type: text/html; charset=utf-8');

use MirazMac\HtmlSanitizer\BasicWhitelist;
use MirazMac\HtmlSanitizer\Sanitizer;
use MirazMac\HtmlSanitizer\Whitelist;

require_once '../vendor/autoload.php';

$whitelist = new BasicWhitelist;
$htmlsanitizer = new Sanitizer($whitelist);

//r($htmlsanitizer->getWhitelist());

$payload = file_get_contents('payload.txt');

echo $htmlsanitizer->sanitize('<a href="#" download="true">Link</a>');
