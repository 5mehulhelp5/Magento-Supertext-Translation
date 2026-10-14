<?php

/**
 * @package     Supertext Translation for Magento
 * @copyright   (C) Supertext AG
 * @license     MIT
 */

declare(strict_types=1);

namespace Supertext\Translation\Model\Config\Backend;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\LocalizedException;

/** The store view's Supertext language, e.g. "fr-CH", "es" or "gsw-u-sd-chzh" (optional where the locale is a Supertext code). */
class LanguageCode extends Value
{
    public function beforeSave()
    {
        $value = trim((string) $this->getValue());

        if ($value !== '' && !preg_match('/^[A-Za-z]{2,3}([-_][A-Za-z0-9]{1,8})*$/', $value)) {
            throw new LocalizedException(__('"%1" is not a valid language code. Use a code such as de-CH or es.', $value));
        }

        $this->setValue(str_replace('_', '-', $value));

        return parent::beforeSave();
    }
}
