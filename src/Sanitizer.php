<?php

declare(strict_types=1);

namespace MirazMac\HtmlSanitizer;

use function \chr;
use function \html_entity_decode;
use function \htmlspecialchars;
use function \libxml_clear_errors;
use function \libxml_disable_entity_loader;
use function \libxml_use_internal_errors;
use function \mb_strlen;
use function \mb_strpos;
use function \mb_strtolower;
use function \mb_substr;
use function \parse_url;
use function \preg_match;
use function \range;
use function \str_replace;
use function \trim;
use function \version_compare;

/**
 * HtmlSanitizer
 *
 * A lightweight library to make sanitizing HTML easier on PHP.
 * Has no dependencies except native PHP extensions like dom, libxml, mbstring.
 *
 * Should be faster than any other sanization library present for PHP
 *
 * @author Miraz Mac <mirazmac@gmail.com>
 * @link https://mirazmac.com
 */
class Sanitizer
{
    /**
     * A whitelist of elements and their attributes
     *
     * @var Whitelist
     */
    protected $whitelist;

    /**
     * Create a new HtmlSanitizer instance
     *
     * @param Whitelist $whitelist A whitelist of elements
     */
    public function __construct(Whitelist $whitelist)
    {
        $this->whitelist = $whitelist;
    }

    /**
     * Sanitize the provided HTML
     *
     * @param  string $html
     * @return string
     * @throws \InvalidArgumentException If supplied HTML is not valid UTF-8
     */
    public function sanitize(string $html) : string
    {
        if (!$this->isValidUtf8($html)) {
            throw new \InvalidArgumentException("Provided HTML must be valid utf-8");
        }

        // Remove NULL characters (ignored by some browsers).
        $html = str_replace(chr(0), '', $html);

        if (mb_strlen($html) < 1) {
            return '';
        }

        // Because..
        $previousState = libxml_use_internal_errors(true);
        libxml_clear_errors();

        // deprecated in PHP 8.0
        if (\PHP_VERSION_ID < 80000) {
            libxml_disable_entity_loader(true);
        }

        // Construct the DOM Document
        $dom = new \DOMDocument('1.0', 'UTF-8');

        // Nah
        $dom->strictErrorChecking = false;
        // nope
        $dom->validateOnParse = false;
        $dom->substituteEntities = false;
        // Don't even try
        $dom->resolveExternals  = false;
        // whenever possible, please..
        $dom->recover = true;
        $dom->formatOutput = false;
        $dom->preserveWhiteSpace  = false;

        // no shit sherlock
        $dom->encoding = 'UTF-8';

        // Finally load the HTML
        $dom->loadHTML(
            // Prepend the utf-8 encoding tags
            // ugly hack but works better than mb_convert_encoding()
            '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"><meta charset="UTF-8">'
            .
            $html,
            \LIBXML_NOERROR | \LIBXML_NOWARNING | \LIBXML_HTML_NODEFDTD
        );

        // Why again? Apparently it gets set to NULL after calling loadHTML(), so set it back to UTF-8 again,
        // otherwise saveHTML produces weird results
        $dom->encoding = 'UTF-8';

        $html = trim($dom->saveHTML($this->doSanitize($dom)));

        // Clear the errors
        libxml_clear_errors();

        // Restore the state
        libxml_use_internal_errors($previousState);

        return $html;
    }

    /**
     * Handles actual Sanitization process by looping over the DOM data
     *
     * @param  object $html
     * @return string
     */
    protected function doSanitize($html)
    {
        if ($html->hasChildNodes()) {
            foreach (range($html->childNodes->length - 1, 0) as $i) {
                $this->doSanitize($html->childNodes->item($i));
            }
        }

        // If the tag isn't allowed, remove the tag, but keep the child tags for further checking
        // by creating a new fragment
        if (!$this->whitelist->isTagAllowed($html->nodeName)) {
            $fragment = $html->ownerDocument->createDocumentFragment();

            if (!empty($html->childNodes)) {
                while ($html->childNodes->length > 0) {
                    $fragment->appendChild($html->childNodes->item(0));
                }
            }

            // do this again
            return $html->parentNode->replaceChild($fragment, $html);
        }

        // Make sure the tag has attributes
        if (!$html->hasAttributes()) {
            return $html;
        }

        // Loop through the attributes
        for ($i = $html->attributes->length; --$i >= 0;) {
            // Attribute name and value for easier access
            $name = $html->attributes->item($i)->name;
            $value = $html->attributes->item($i)->value;
                
            // Remove attribute if not allowed
            if (!$this->whitelist->isAttributeAllowed($html->nodeName, $name)) {
                $html->removeAttribute($name);
                continue; // no further action required, let's proceed to the next one
            }

            // Remove attribute if value doesn't match with an explicitly defined list
            if (!$this->whitelist->isValueAllowed($html->nodeName, $name, $value)) {
                $html->removeAttribute($name);
                continue;
            }

            // Handle boolean/blank attributes
            if (HtmlDataMap::isBooleanAttribute($name) || $this->whitelist->isBooleanAttribute($name)) {
                // If it's already empty or a valid boolean don't change anything
                if ($name === $value || mb_strlen($value) === 0) {
                    continue;
                }

                // Otherwise manually fix the value
                $value = "";
            }


            // Host and protocol filtering for URL attributes
            if (HtmlDataMap::isUrlAttribute($name) || $this->whitelist->isUrlAttribute($name)) {
                // Proceed to URL filtering
                $value = $this->filterURL(
                    $html->nodeName,
                    $value
                );
            }


            // Regardless of all this, every attribute gets escaped
            $html->setAttribute(
                $name,
                $this->escapeAttribute($value)
            );
        }


        return $html;
    }


    /**
     * Return the whitelist instance
     *
     * @return Whitelist
     */
    public function getWhitelist() : Whitelist
    {
        return $this->whitelist;
    }

    /**
     * Filter a URL value
     *
     * @param  string   $element
     * @param  \DomAttr $attr
     * @return string
     */
    protected function filterURL(string $element, $value) : string
    {
        $host = parse_url($value, PHP_URL_HOST);

        // No host found, so just verify and strip protocols (if applicable)
        if (empty($host)) {
            return $this->stripDangerousProtocols($value);
        }

        // A host is present, let's see if host whitelisting is present for the element in question
        if (!$this->whitelist->isHostAllowed($element, $host)) {
            return '';
        }

        return $this->stripDangerousProtocols($value);
    }


    /**
     * Escape a HTML attribute value
     *
     * @param  string $string
     * @return string
     */
    public function escapeAttribute(string $string) : string
    {
        $string = html_entity_decode($string, ENT_QUOTES, 'UTF-8');
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8', true);
    }

    /**
     * Iteratively remove any invalid protocol found in a URL value.
     * Taken from Drupal XSS sanitizer, more effective and secure than parse_url()
     *
     * @param  string $uri
     * @return string
     */
    protected function stripDangerousProtocols($uri) : string
    {
        // Iteratively remove any invalid protocol found.
        do {
            $before = $uri;
            $colonpos = mb_strpos($uri, ':');

            if ($colonpos > 0) {
                // We found a colon, possibly a protocol. Verify.
                $protocol = mb_substr($uri, 0, $colonpos);

                // If a colon is preceded by a slash, question mark or hash, it cannot
                // possibly be part of the URL scheme. This must be a relative URL, which
                // inherits the (safe) protocol of the base document.
                if (preg_match('![/?#]!', $protocol)) {
                    break;
                }

                // Check if this is a disallowed protocol. Per RFC2616, section 3.2.3
                // (URI Comparison) scheme comparison must be case-insensitive.
                if (!$this->whitelist->isProtocolAllowed(mb_strtolower($protocol))) {
                    $uri = mb_substr($uri, $colonpos + 1);
                }
            }
        } while ($before != $uri);

        return $uri;
    }

    /**
     * Determines whether the specified string is valid utf 8.
     *
     * @param      string  $string   The string
     *
     * @return     bool
     */
    protected function isValidUtf8(string $string): bool
    {
        return '' === $string || 1 === preg_match('/^./us', $string);
    }
}
