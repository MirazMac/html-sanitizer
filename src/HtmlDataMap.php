<?php

declare(strict_types=1);

namespace MirazMac\HtmlSanitizer;

use function \in_array;

/**
* Stores basic data about HTML tags and attributes
*/
final class HtmlDataMap
{
    /**
     * Boolean HTML attributes
     *
     * @var array
     */
    const BOOLEAN_ATTRIBUTES = [
        'allowfullscreen', 'allowpaymentrequest', 'async', 'autofocus', 'autoplay',
        'checked', 'controls', 'default', 'disabled', 'formnovalidate', 'hidden',
        'ismap', 'itemscope', 'loop', 'multiple', 'muted', 'nomodule', 'novalidate',
        'open', 'playsinline', 'readonly', 'required', 'reversed', 'selected', 'truespeed',
        'download'
    ];

    /**
     * Atrributes that can contain URL values
     *
     * @var array
     */
    const URL_ATTRIBUTES = [
        'href', 'background', 'cite', 'action', 'profile', 'longdesc', 'classid',
        'codebase', 'data', 'usemap', 'formaction', 'icon', 'src', 'manifest',
        'formaction', 'poster', 'srcset', 'archive'
    ];

    /**
     * @todo Support URL sanitization for attributes with multiple values
     *
     * @var array
     */
    const MULTI_URL_ATTRIBUTES = ['srcset'];

    /**
     * If the HTML attribye is booolean type
     *
     * @param  string  $attrName
     * @return boolean
     */
    public static function isBooleanAttribute(string $attrName)
    {
        return in_array($attrName, static::BOOLEAN_ATTRIBUTES);
    }

    /**
     * If the HTML attribute can contain URL by defination
     *
     * @param  string  $attrName
     * @return boolean
     */
    public static function isUrlAttribute(string $attrName)
    {
        return in_array($attrName, static::URL_ATTRIBUTES);
    }
}
