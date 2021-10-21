<?php

declare(strict_types=1);

namespace MirazMac\HtmlSanitizer;

/**
 * HtmlSanitizer
 *
 * A lightweight library to make sanitizing HTML easier on PHP. Has no dependencies except Native DomDocument support,
 * faster than any other sanization library present for PHP
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
     * @throws \RuntimeException If failed to convert the HTML into UTF-8 via mb_convert_encoding()
     */
    public function sanitize(string $html) : string
    {
        // Because..
        libxml_use_internal_errors(true);
        libxml_clear_errors(true);

        // deprecated in PHP 8.0
        if (version_compare(\PHP_VERSION, '8.0.0', '<')) {
            libxml_disable_entity_loader(true);
        }

        // Remove NULL characters (ignored by some browsers).
        $html = str_replace(chr(0), '', $html);

        if (mb_strlen($html) < 1) {
            return '';
        }

        // Construct the DOM Document
        $dom = new \DOMDocument('1.0', 'UTF-8');

        // Fix encoding issues
        $html = @mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');

        if (empty($html)) {
            throw new \RuntimeException("Failed to convert the HTML into UTF-8 via mb_convert_encoding();");
        }

        // Nah, we're not HTMLPurifier (fuck that bloated ass library btw)
        $dom->strictErrorChecking = false;
        // nope
        $dom->validateOnParse = false;
        $dom->substituteEntities = false;
        $dom->resolveExternals  = false;
        // whenever possible, please..
        $dom->recover = true;
        // should this be a option to customize?
        // idk
        $dom->formatOutput = false;
        // same question
        $dom->preserveWhiteSpace  = false;

        // no shit sherlock
        $dom->encoding = 'UTF-8';

        // Finally load the HTML
        $dom->loadHTML($html);

        // Why again? Apparently it gets set to NULL after calling loadHTML(), so set it back to UTF-8 again,
        // otherwise saveHTML produces weird results
        $dom->encoding = 'UTF-8';

        return trim($dom->saveHTML($this->doSanitize($dom)));
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
    protected function escapeAttribute(string $string) : string
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
}
