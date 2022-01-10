<?php

declare(strict_types=1);

namespace MirazMac\HtmlSanitizer;

use function \array_merge;
use function \array_reverse;
use function \explode;
use function \in_array;
use function \is_array;

/**
 * Whitelist
 *
 * Base whitelist object
 *
 * @package MirazMac\HtmlSanitizer
 */
class Whitelist
{
    /**
     * Allowed HTML Tags, along with their attributes
     *
     * @var array
     */
    protected $tags = [];

    /**
     * Allowed protocols
     *
     * @var array
     */
    protected $protocols = [];

    /**
     * List of custom attributes that would be treated as if they contain URLs
     *
     * @var array
     */
    protected $treatAsURL = [];

    /**
     * List of custom attributes that would be treated as if they're boolean
     *
     * @var array
     */
    protected $treatAsBoolean = [];

    /**
     * Allowed values for specific attributes
     *
     * @var        array
     */
    protected $values = [];

    /**
     * Internally required tags
     *
     * @var array
     */
    private $requiredTags = ['#document', '#text'];

    /**
     * Create a new Whitelist instance
     *
     * @param array $tags List of allowed tags
     * @param array $protocols List of allowed protocols
     */
    public function __construct(array $tags = [], array $protocols = [])
    {
        $this->setTags($tags);
        $this->setProtocols($protocols);
    }

    /**
     * Allow an HTML tag to whitelist.
     * Will be overwritten if already allowed
     *
     * @param string $tagName
     * @param array  $attributes Allowed attributes in this format: ['src', 'href', 'data-src']
     *
     * @throws \InvalidArgumentException If trying to overwrite default required nodes @see $requiredTags
     */
    public function allowTag(string $tagName, array $attributes = []) : Whitelist
    {
        if ($this->isRequiredTag($tagName)) {
            throw new \InvalidArgumentException("Unable to overwrite required tag: {$tagName}");
        }

        $this->tags[$tagName]['allowed_hosts'] = [];

        foreach ($attributes as $attr) {
            $this->tags[$tagName]['attributes'][$attr] = true;
        }
        
        return $this;
    }

    /**
     * Remove one or more tags from the whitelist
     *
     * @param  string|array $tagName A tag name or array of tags
     * @return Whitelist
     */
    public function removeTag($tagName) : Whitelist
    {
        foreach ((array) $tagName as $tag) {
            unset($this->tags[$tag]);
        }

        return $this;
    }

    /**
     * Allow one or more attributes for a tag
     *
     * @param  string $tagName
     * @param  string|array $attributes
     * @return Whitelist
     * @throws \LogicException If the tag isn't present in the whitelist
     */
    public function allowAttribute(string $tagName, $attributes) : Whitelist
    {
        if (!$this->isTagAllowed($tagName)) {
            throw new \LogicException("Failed to allow attribute(s) on tag `{$tagName}`, because the tag itself isn't allowed.");
        }

        foreach ((array) $attributes as $attr) {
            $this->tags[$tagName]['attributes'][$attr] = true;
        }

        return $this;
    }

    /**
     * Remove one or more attributes for a tag
     *
     * @param  string $tagName
     * @param  string|array $attributes
     * @return Whitelist
     * @throws \LogicException If the tag isn't present in the whitelist
     */
    public function removeAttribute(string $tagName, $attributes) : Whitelist
    {
        if (!$this->isTagAllowed($tagName)) {
            throw new \LogicException("Failed to remove attribute(s) on tag `{$tagName}`, because the tag itself isn't allowed.");
        }

        foreach ((array) $attributes as $attr) {
            unset($this->tags[$tagName]['attributes'][$attr]);
        }

        return $this;
    }

    /**
     * Sets list of allowed values for an attribute under a tag name.
     * If this is set and the value of the attribute doesn't match with these, the attribute will be removed.
     * This mainly should be used for custom data attributes where you only want a specific set of values.
     *
     * @param      string           $tagName    The tag name
     * @param      string           $attribute  The attribute
     * @param      array            $values     The values
     *
     * @throws     \LogicException  If tag isn't allowed
     *
     * @return     self
     */
    public function setAllowedValues(string $tagName, string $attribute, array $values)
    {
        if (!$this->isTagAllowed($tagName)) {
            throw new \LogicException("Failed to allow values on attribute `{$attribute}` on tag `{$tagName}`, because the tag itself isn't allowed.");
        }

        $this->values[$tagName][$attribute] = $values;

        return $this;
    }

    /**
     * Add one or many protocols to the whitelist
     *
     * @param string|array $protocols
     */
    public function addProtocol($protocols) : Whitelist
    {
        foreach ((array) $protocols as $protocol) {
            $this->protocols[$protocol] = true;
        }

        return $this;
    }

    /**
     * Remove one or many protocols from the whitelist
     *
     * @param string|array $protocols
     */
    public function removeProtocol($protocols) : Whitelist
    {
        foreach ((array) $protocols as $protocol) {
            unset($this->protocols[$protocol]);
        }

        return $this;
    }


    /**
     * Set a list of allowed hosts for a tag
     *
     * @param string $tagName
     * @param array  $hosts
     * @return Whitelist
     * @throws \LogicException If the tag isn't present in the whitelist
     */
    public function setAllowedHosts(string $tagName, array $hosts, bool $merge = false) : Whitelist
    {
        if (!$this->isTagAllowed($tagName)) {
            throw new \LogicException("Failed to allow host(s) on tag `{$tagName}`, because the tag itself isn't allowed.");
        }

        if (!$merge) {
            $this->tags[$tagName]['allowed_hosts'] = $hosts;
            return $this;
        }

        $this->tags[$tagName]['allowed_hosts'] = array_merge($this->tags[$tagName]['allowed_hosts'], $hosts);

        return $this;
    }

    /**
     * Overwrite the protocols
     *
     * @param array $protocols
     * @param bool $merge To merge with existing protocols
     */
    public function setProtocols(array $protocols, bool $merge = false) : Whitelist
    {
        $formattedProtocols = [];

        foreach ($protocols as $protocol) {
            $formattedProtocols[$protocol] = true;
        }
        

        if (!$merge) {
            $this->protocols = $formattedProtocols;
            return $this;
        }

        $this->protocols = array_merge($this->protocols, $formattedProtocols);

        return $this;
    }

    /**
     * Set a array of tags
     * Format should be:
     * [
     *     'tagName'    =>  ['class', 'id'],
     *     'tagName2'   =>  ['class', 'id']
     * ];
     *
     * @param array   $tags
     * @param boolean $merge Whether to merge with the existing tags
     */
    public function setTags(array $tags, $merge = false) : Whitelist
    {
        $formattedTags = [];

        foreach ($tags as $tag => $attributes) {
            $formattedTags[$tag]['allowed_hosts'] = [];
            $formattedTags[$tag]['attributes'] = [];
            if (is_array($attributes)) {
                foreach ($attributes as $attr) {
                    $formattedTags[$tag]['attributes'][$attr] = true;
                }
            }
        }

        if (!$merge) {
            $this->tags = $formattedTags;
            return $this;
        }

        $this->tags = array_merge($this->tags, $formattedTags);
        return $this;
    }


    /**
     * Set a list of attributes to be treated as if they contain URL
     *
     * @param array $attributes
     */
    public function treatAttributesAsUrl(array $attributes) : Whitelist
    {
        $this->treatAsURL = $attributes;
        return $this;
    }

    /**
     * Set a list of attributes to be treated as if they're boolean
     *
     * @param array $attributes
     */
    public function treatAttributesAsBoolean(array $attributes) : Whitelist
    {
        $this->treatAsBoolean = $attributes;
        return $this;
    }

    /**
     * Returns the allowed tag list with their attributes
     *
     * @return array
     */
    public function getAllowedTags() : array
    {
        return $this->tags;
    }

    /**
     * Get allowed attributes for a tag
     *
     * @param  string $tagName
     * @return array
     */
    public function getAllowedAttributes(string $tagName) : array
    {
        if ($this->isTagAllowed($tagName)) {
            return $this->tags[$tagName]['attributes'];
        }

        return [];
    }

    /**
     * Get a list of allowed hosts for a tag
     *
     * @param  string $tagName
     * @return array
     */
    public function getAllowedHosts(string $tagName) : array
    {
        if (empty($this->tags[$tagName]['allowed_hosts'])) {
            return [];
        }

        return $this->tags[$tagName]['allowed_hosts'];
    }


    /**
     * Check if an attribute has been manually set to treat as URLs
     *
     * @param  string  $attribute
     * @return boolean
     */
    public function isUrlAttribute(string $attribute) : bool
    {
        return in_array($attribute, $this->treatAsURL);
    }

    /**
     * Check if an attribute has been manually set to be treated as boolean
     *
     * @param  string  $attribute
     * @return boolean
     */
    public function isBooleanAttribute(string $attribute) : bool
    {
        return in_array($attribute, $this->treatAsBoolean);
    }

    /**
     * See if a tag is required or not
     *
     * @param  string  $tagName
     * @return boolean
     */
    public function isRequiredTag(string $tagName) : bool
    {
        return in_array($tagName, $this->requiredTags);
    }

    /**
     * Check if a HTML tag is present in the whitelist
     *
     * @param  string  $tagName
     * @return boolean
     */
    public function isTagAllowed(string $tagName) : bool
    {
        if ($this->isRequiredTag($tagName)) {
            return true;
        }

        return isset($this->tags[$tagName]);
    }

    /**
     * Check if a protocol is allowed
     *
     * @param  string  $protocol
     * @return boolean
     */
    public function isProtocolAllowed(string $protocol) : bool
    {
        return isset($this->protocols[$protocol]);
    }

    /**
     * Check if a attribute is allowed on a certain element
     *
     * @param  string  $tagName
     * @param  string  $attribute
     * @return boolean
     */
    public function isAttributeAllowed(string $tagName, string $attribute) : bool
    {
        if (!$this->isTagAllowed($tagName)) {
            return false;
        }

        return isset($this->tags[$tagName]['attributes'][$attribute]);
    }

    /**
     * Check if a host is allowed on a certain tag
     *
     * @param  string  $tagName
     * @param  string  $host
     * @return boolean
     */
    public function isHostAllowed(string $tagName, string $host) : bool
    {
        // tag not allowed in the first place, that's weird, no one should reach inside this condition
        // just here for edge-cases, idk what kinda cases, and i'm way too drunk to care
        // fuck it! it stays here
        if (!$this->isTagAllowed($tagName)) {
            return false;
        }

        // If the list is empty we allow every host
        if (empty($this->tags[$tagName]['allowed_hosts'])) {
            return true;
        }

        $parts = array_reverse(explode('.', $host));

        foreach ($this->tags[$tagName]['allowed_hosts'] as $allowedHost) {
            if ($this->matchAllowedHostParts($parts, array_reverse(explode('.', $allowedHost)))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determines if value is allowed for an attribute under.
     *
     * @param      string  $tagName    The tag name
     * @param      string  $attribute  The attribute
     * @param      string  $value      The value
     *
     * @return     bool
     */
    public function isValueAllowed(string $tagName, string $attribute, $value) : bool
    {
        // Allowed by default unless added explicitly
        if (!isset($this->values[$tagName][$attribute])) {
            return true;
        }

        return in_array($value, $this->values[$tagName][$attribute]);
    }

    /**
     * Iteratively ensures the host domain is allowed
     * Taken from tgalopin/html-sanitizer
     *
     * @see https://github.com/tgalopin/html-sanitizer/blob/master/src/Sanitizer/UrlSanitizerTrait.php
     * @param  array  $uriParts
     * @param  array  $trustedParts
     * @return bool
     */
    private function matchAllowedHostParts(array $uriParts, array $trustedParts): bool
    {
        // Check each chunk of the domain is valid
        foreach ($trustedParts as $key => $trustedPart) {
            if ($uriParts[$key] !== $trustedPart) {
                return false;
            }
        }

        return true;
    }
}
