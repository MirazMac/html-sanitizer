<?php

declare(strict_types=1);

namespace MirazMac\HtmlSanitizer;

/**
 * BasicWhitelist
 *
 * A basic whitelist for filtering WYSIWYG editors, tags are based on WordPress' editor
 *
 * @package MirazMac\HtmlSanitizer
 */
class BasicWhitelist extends Whitelist
{
    /**
     * Create a new BasicWhitelist instance
     */
    public function __construct()
    {
        parent::setTags(static::getBasicTags());
        parent::setProtocols(static::getBasicProtocols());
    }

    /**
     * Returns the basic allowed protocols
     *
     * @return     array
     */
    public static function getBasicProtocols() : array
    {
        return ['http', 'https', 'ftp', '//', 'mailto', 'data'];
    }

    /**
     * Gets the basic tags.
     *
     * @return     array
     */
    public static function getBasicTags() : array
    {
        return [
        'address' => [],
        'a' => [
        'href',
        'rel',
        'rev',
        'name',
        'target',
        'title',
        'download'
        ],
        'abbr' => [],
        'acronym' => [],
        'area' => [
        'alt',
        'coords',
        'href',
        'nohref',
        'shape',
        'target'
        ],
        'article' => [
        'align',
        'dir',
        'lang',
        'xml:lang'
        ],
        'aside' => [
        'align',
        'dir',
        'lang',
        'xml:lang'
        ],
        'audio' => [
        'autoplay',
        'controls',
        'loop',
        'muted',
        'preload',
        'src'
        ],
        'b' => [],
        'bdo' => [
        'dir'
        ],
        'big' => [],
        'blockquote' => [
        'cite',
        'lang',
        'xml:lang'
        ],
        'br' => [],
        'button' => [
        'disabled',
        'name',
        'type',
        'value'
        ],
        'caption' => [
        'align'
        ],
        'cite' => [
        'dir',
        'lang'
        ],
        'code' => [],
        'col' => [
        'align',
        'char',
        'charoff',
        'span',
        'dir',
        'valign',
        'width'
        ],
        'colgroup' => [
        'align',
        'char',
        'charoff',
        'span',
        'valign',
        'width'
        ],
        'del' => [
        'datetime'
        ],
        'dd' => [],
        'dfn' => [],
        'details' => [
        'align',
        'dir',
        'lang',
        'open',
        'xml:lang'
        ],
        'div' => [
        'align',
        'dir',
        'lang',
        'xml:lang'
        ],
        'dl' => [],
        'dt' => [],
        'em' => [],
        'fieldset' => [],
        'figure' => [
        'align',
        'dir',
        'lang',
        'xml:lang'
        ],
        'figcaption' => [
        'align',
        'dir',
        'lang',
        'xml:lang'
        ],
        'font' => [
        'color',
        'face',
        'size'
        ],
        'footer' => [
        'align',
        'dir',
        'lang',
        'xml:lang'
        ],
        'h1' => [
        'align'
        ],
        'h2' => [
        'align'
        ],
        'h3' => [
        'align'
        ],
        'h4' => [
        'align'
        ],
        'h5' => [
        'align'
        ],
        'h6' => [
        'align'
        ],
        'header' => [
        'align',
        'dir',
        'lang',
        'xml:lang'
        ],
        'hgroup' => [
        'align',
        'dir',
        'lang',
        'xml:lang'
        ],
        'hr' => [
        'align',
        'noshade',
        'size',
        'width'
        ],
        'i' => [],
        'img' => [
        'alt',
        'align',
        'border',
        'height',
        'hspace',
        'longdesc',
        'vspace',
        'src',
        'usemap',
        'width'
        ],
        'ins' => [
        'datetime',
        'cite'
        ],
        'kbd' => [],
        'label' => [
        'for'
        ],
        'legend' => [
        'align'
        ],
        'li' => [
        'align',
        'value'
        ],
        'map' => [
        'name'
        ],
        'mark' => [],
        'menu' => [
        'type'
        ],
        'nav' => [
        'align',
        'dir',
        'lang',
        'xml:lang'
        ],
        'p' => [
        'align',
        'dir',
        'lang',
        'xml:lang'
        ],
        'pre' => [
        'width'
        ],
        'q' => [
        'cite'
        ],
        's' => [],
        'samp' => [],
        'span' => [
        'dir',
        'align',
        'lang',
        'xml:lang'
        ],
        'section' => [
        'align',
        'dir',
        'lang',
        'xml:lang'
        ],
        'small' => [],
        'strike' => [],
        'strong' => [],
        'sub' => [],
        'summary' => [
        'align',
        'dir',
        'lang',
        'xml:lang'
        ],
        'sup' => [],
        'source' => [
            'src', 'type', 'sizes', 'srcset', 'media'
        ],
        'table' => [
        'align',
        'bgcolor',
        'border',
        'cellpadding',
        'cellspacing',
        'dir',
        'rules',
        'summary',
        'width'
        ],
        'tbody' => [
        'align',
        'char',
        'charoff',
        'valign'
        ],
        'td' => [
        'abbr',
        'align',
        'axis',
        'bgcolor',
        'char',
        'charoff',
        'colspan',
        'dir',
        'headers',
        'height',
        'nowrap',
        'rowspan',
        'scope',
        'valign',
        'width'
        ],
        'textarea' => [
        'cols',
        'rows',
        'disabled',
        'name',
        'readonly'
        ],
        'tfoot' => [
        'align',
        'char',
        'charoff',
        'valign'
        ],
        'th' => [
        'abbr',
        'align',
        'axis',
        'bgcolor',
        'char',
        'charoff',
        'colspan',
        'headers',
        'height',
        'nowrap',
        'rowspan',
        'scope',
        'valign',
        'width'
        ],
        'thead' => [
        'align',
        'char',
        'charoff',
        'valign'
        ],
        'tr' => [
        'align',
        'bgcolor',
        'char',
        'charoff',
        'valign'
        ],
        'track' => [
        'default',
        'kind',
        'label',
        'src',
        'srclang'
        ],
        'tt' => [],
        'u' => [],
        'ul' => [
        'type'
        ],
        'ol' => [
        'start',
        'type',
        'reversed'
        ],
        'var' => [],
        'video' => [
        'autoplay',
        'controls',
        'height',
        'loop',
        'muted',
        'poster',
        'preload',
        'src',
        'width'
        ]
        ];
    }
}
