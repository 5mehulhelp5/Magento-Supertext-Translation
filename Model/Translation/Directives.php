<?php

/**
 * @package     Supertext Translation for Magento
 * @copyright   (C) Supertext AG
 * @license     MIT
 */

declare(strict_types=1);

namespace Supertext\Translation\Model\Translation;

/**
 * Magento template directives ({{widget …}}, {{store url=…}}) in rich text are code: they are
 * wrapped in translate="no" before sending and unwrapped in the translation. Directives inside
 * tags (attribute values such as src="{{media url=…}}") are not touched.
 *
 * No Magento dependencies.
 */
final class Directives
{
    private const KEEP_OPEN = '<span translate="no" class="notranslate" data-st-keep="1">';

    public static function protect(string $html): string
    {
        $parts = preg_split('/(<[^>]*>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$html];

        foreach ($parts as $i => $part) {
            if ($part === '' || $part[0] === '<') {
                continue;
            }

            $parts[$i] = (string) preg_replace('/\{\{.*?\}\}/s', self::KEEP_OPEN . '$0</span>', $part);
        }

        return implode('', $parts);
    }

    public static function unprotect(string $html): string
    {
        return (string) preg_replace('#<span(?=[^>]*\bdata-st-keep\b)[^>]*>(.*?)</span>#s', '$1', $html);
    }
}
