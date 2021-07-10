<?php

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

declare(strict_types=1);

use MirazMac\HtmlSanitizer\BasicWhitelist;
use MirazMac\HtmlSanitizer\Sanitizer;
use MirazMac\HtmlSanitizer\Whitelist;
use PHPUnit\Framework\TestCase;

class SanitizerTest extends TestCase
{
    protected $basicSanitizer;

    protected $sanitizer;

    public function setUp() : void
    {
        $this->basicSanitizer = new Sanitizer($this->getBasicWhitelist());
        $this->sanitizer = new Sanitizer(new Whitelist);
    }

    /**
     * Check if all tags are removed and only the text string remains
     *
     * @return void
     */
    public function testSimpleHTML() : void
    {
        $string = $this->sanitizer->sanitize('<div id="fake"><h5 class="foo">Lorem ipsum</h5></div>');
        $this->assertEquals("Lorem ipsum", $string);
    }

    /**
     * Check if empty values return valid empty value
     *
     */
    public function testEmptyString() : void
    {
        $this->assertEmpty($this->sanitizer->sanitize(''));
    }

    /**
     * tests host filtering
     *
     */
    public function testHostFilter() : void
    {
        $string = $this->basicSanitizer->sanitize('<img src="https://bing.com"><img src="https://google.com">');
        $this->assertEquals('<img src=""><img src="https://google.com">', $string);
    }

    /**
     * Test fixing of a invalid boolean value using the basicwhitelist
     *
     */
    public function testBooleanAttribute() : void
    {
        $string = $this->basicSanitizer->sanitize('<a href="#" download="true">Link</a>');
        $this->assertEquals('<a href="#" download="">Link</a>', $string);
    }

    /**
     * Tests allowance of a custom attribute
     *
     */
    public function testCustomAttribute() : void
    {
        $string = $this->basicSanitizer->sanitize('<img src="1.png" data-src="1.png">');
        $this->assertEquals('<img src="1.png" data-src="1.png">', $string);
    }

    protected function getBasicWhitelist()
    {
        $whitelist = new BasicWhitelist;
        // Allow support for a few attribute for testing
        $whitelist->allowAttribute('img', ['data-src', 'data-lazyload'])
                  ->setAllowedHosts('img', ['google.com'])
                  ->treatAttributesAsURL(['data-src'])
                  ->treatAttributesAsBoolean(['data-lazyload']);
        return $whitelist;
    }
}
