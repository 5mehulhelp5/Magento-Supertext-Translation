<?php

/**
 * @package     Supertext Translation for Magento
 * @copyright   (C) Supertext AG
 * @license     MIT
 */

declare(strict_types=1);

namespace Supertext\Translation\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Supertext\Translation\Model\Config;

/**
 * The store view's *Supertext language*: Supertext's list, plus a code saved before that isn't in
 * it (kept and sent as it is), and below it what the store view is translated into.
 */
class LanguageSelect extends Field
{
    public function __construct(Context $context, private readonly Config $config, array $data = [])
    {
        parent::__construct($context, $data);
    }

    protected function _getElementHtml(AbstractElement $element): string
    {
        $value = trim((string) $element->getValue());
        /** @var list<array{value: string, label: mixed}> $values */
        $values = (array) $element->getValues();

        if ($value !== '' && !\in_array($value, array_column($values, 'value'), true)) {
            $values[] = ['value' => $value, 'label' => $value];
            $element->setValues($values);
        }

        $storeId = (int) $this->getRequest()->getParam('store');

        if ($storeId > 0) {
            $locale = $this->config->locale($storeId);
            $target = $this->config->targetCodeOrNull($storeId);
            $note   = $target === null
                ? '<strong style="color:#e22626">' . $this->escapeHtml(__('Not mapped: the locale %1 is not a Supertext code. Choose its Supertext language, or nothing is translated into this store view.', $locale)) . '</strong>'
                : $this->escapeHtml(__('This store view is translated into %1 (locale %2).', $target, $locale));
            $comment = (string) $element->getComment();
            $element->setComment($comment !== '' ? $comment . '<br>' . $note : $note);
        }

        return parent::_getElementHtml($element);
    }
}
