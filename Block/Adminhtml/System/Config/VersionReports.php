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

/**
 * *Send version information to Supertext*: says when SUPERTEXT_VERSION_REPORTS overrides the
 * setting, and shows the ping address while reports are on.
 */
class VersionReports extends Field
{
    public function __construct(Context $context, private readonly Reporting $reporting, array $data = [])
    {
        parent::__construct($context, $data);
    }

    protected function _getElementHtml(AbstractElement $element): string
    {
        $notes = [];

        if ($this->reporting->forcedByEnvironment()) {
            $element->setDisabled(true);
            $notes[] = $this->escapeHtml($this->reporting->enabled()
                ? __('Turned on by the SUPERTEXT_VERSION_REPORTS environment variable on the server.')
                : __('Turned off by the SUPERTEXT_VERSION_REPORTS environment variable on the server.'));
        }

        $ping = $this->reporting->pingUrl();

        if ($this->reporting->enabled() && $ping !== null) {
            $notes[] = $this->escapeHtml(__('Ping address for Supertext:')) . ' <code>' . $this->escapeHtml($ping) . '</code>';
        }

        if ($notes !== []) {
            $comment = (string) $element->getComment();
            $element->setComment(($comment !== '' ? $comment . '<br>' : '') . implode('<br>', $notes));
        }

        return parent::_getElementHtml($element);
    }
}
