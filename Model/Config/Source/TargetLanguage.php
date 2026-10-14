<?php

/**
 * @package     Supertext Translation for Magento
 * @copyright   (C) Supertext AG
 * @license     MIT
 */

declare(strict_types=1);

namespace Supertext\Translation\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Supertext\Translation\Api\LanguageCodes;

/**
 * The *Supertext language* choices: Supertext's target codes (the SDK's list of `GET features`),
 * with an empty option for "the locale, if it is a Supertext code". Language names stay English,
 * as Supertext lists them; the code comes first so the list can be searched by typing it.
 */
class TargetLanguage implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        $options = [['value' => '', 'label' => __('– The locale, if it is a Supertext code –')]];

        foreach (LanguageCodes::targets() as $language) {
            $options[] = ['value' => $language['code'], 'label' => $language['code'] . ' · ' . $language['name']];
        }

        return $options;
    }
}
