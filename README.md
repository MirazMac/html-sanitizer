![PHP Requirements Checker](https://user-images.githubusercontent.com/13865787/130739385-da8a7794-af57-49a9-b534-b6414890ec48.png)

# HTMLSanitizer
A super lightweight PHP library to sanitize HTML string against a whitelist. It has all the features an HTML sanitizer should have, including tag based whitelisting, allowing custom tags and attributes and even the ability of treating custom attributes as Boolean or URL.

# Prologue
Almost every PHP app needs to sanitize HTML once in a while. Whether it is a simple comment or a full blown WYSIWYG editor output. It's crucial to ensure only HTML that you allow gets through. There are tons of HTML sanitizer library out there for PHP. Now don't get me wrong, but most of them are.. bloated. And I get it, since they need to ensure the absolute security for the users it can get pretty complicated. But most of us don't need that sort of functionalities.
Now, ``HtmlSanitizer`` doesn't concern itself with validating, or fixing the HTML at all. It treats HTML as is. Matches it against a defined ``WhiteList`` of HTML tags and attributes and escapes them where necessary. In addition to this, it also allows you to define types for these attributes. Currently the supported ones are URL and Boolean. Also you can define allowed hosts for a specific tag, for example you may wish to allow only youtube.com URLs on an iframe, that can be done very easily.

### Requirements
``HtmlSanitizer`` has no external dependencies, only native PHP ones. Most of them are very common, and almost bundled with PHP 90% of the time
- PHP >=7.0
- mbstring
- libxml
- dom


### Install

```shell
composer require mirazmac/html-sanitizer dev-master
```


## Usage

```php
use MirazMac\HtmlSanitizer\BasicWhitelist;
use MirazMac\HtmlSanitizer\Sanitizer;

require_once '../vendor/autoload.php';

// A basic pre-defined whitelist, you can off course customize, add, remove or create your own whitelist
$whitelist = new BasicWhitelist;

// Create the sanitizer instance that uses this whitelist
$htmlsanitizer = new Sanitizer($whitelist);

// returns sanitized string
$sanitizedHTML = $htmlsanitizer->sanitize('....HTML STRING...');

```

## Quirks
* Currently doesn't support URL filtering on attributes that contain multiple URLs, for example: srcset

## Todos
* Full tests coverage
* Write extended docs
