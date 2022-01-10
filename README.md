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
composer require mirazmac/html-sanitizer dev-main
```


## Usage

```php
use MirazMac\HtmlSanitizer\Whitelist;
use MirazMac\HtmlSanitizer\Sanitizer;

require_once '../vendor/autoload.php';

$whitelist = new Whitelist;

// Allow the anchor tag with specific attributes
$whitelist->allowTag('a', ['href', 'title', 'download', 'data-url', 'data-loaded']);

// You can add multiple tags at once as well if that's what you prefer
$whitelist->setTags(
    [
        // allows the `abbr` tag and it's title attribute
        'abbr' =>  ['title'],
        // allows only the em tag, any attributes would be stripped off
        'em'   =>  [],
    ],
    true
);

// Set allowed hosts for the URL attributes on the `a` tag
$whitelist->setAllowedHosts('a', ['google.com', 'facebook.com']);

// Set the allowed protocols for this document
$whitelist->setProtocols(['http', '//', 'https']);

// Set a list of allowed values for an attribute's tag
$whitelist->setAllowedValues('abbr', 'title', ['one', 'two', 'three']);

// Set a list of custom attributes to be treated as URL (i.e to use the host & protocol filter)
$whitelist->treatAttributesAsUrl(['data-url']);

// Set a list of custom attributes to be treated as HTML Boolean (Not true/false ) (i.e their values would be set to blank or the name of the attribute itself)
$whitelist->treatAttributesAsBoolean(['data-load']);

// Create the sanitizer instance that uses this whitelist
$htmlsanitizer = new Sanitizer($whitelist);

// returns sanitized string
$sanitizedHTML = $htmlsanitizer->sanitize('<a href="//google.com" data-download="">Google</a> <a href="https://bing.com" data-url="https://bing.com">My URL would be removed</a>');

echo "HTML Source Output: <pre>";
echo htmlspecialchars($sanitizedHTML);
echo "</pre><br>Rendered Output:<br>" . $sanitizedHTML;


```

## Quirks
* Currently doesn't support URL filtering on attributes that contain multiple URLs, for example: srcset

## Todos
* Full tests coverage
* Write extended docs
