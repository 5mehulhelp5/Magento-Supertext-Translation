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
use Supertext\Translation\Model\Reporting;

/** The latest automatic API key check (on save, *Test connection*, a rejected translation), with its time. */
class KeyCheck extends Field
{
    public function __construct(Context $context, private readonly Reporting $reporting, array $data = [])
    {
        parent::__construct($context, $data);
    }

    public function render(AbstractElement $element): string
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
    }

    protected function _getElementHtml(AbstractElement $element): string
    {
        $check = $this->reporting->lastCheck();

        if ($check === null) {
            return '<div class="control-value" style="padding-top:7px">'
                . $this->escapeHtml(__('Not checked yet. Saving the configuration checks the key.')) . '</div>';
        }

        $message = $this->reporting->describe($check);

        return '<div class="message ' . ($message['ok'] ? 'message-success success' : 'message-warning warning') . '" id="supertext-key-check" style="margin:0">'
            . '<div>' . $message['html'] . '<br><small>' . $this->escapeHtml(__('Checked: %1', $message['time'])) . '</small></div></div>';
    }

    protected function _isInheritCheckboxRequired(AbstractElement $element): bool
    {
        return false;
    }
}
