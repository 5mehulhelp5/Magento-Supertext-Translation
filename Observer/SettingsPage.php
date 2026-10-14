<?php

/**
 * @package     Supertext Translation for Magento
 * @copyright   (C) Supertext AG
 * @license     MIT
 */

declare(strict_types=1);

namespace Supertext\Translation\Observer;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Supertext\Translation\Model\Reporting;

/**
 * controller_action_predispatch_adminhtml_system_config_edit: opening Stores → Configuration →
 * Supertext runs the module's back-end check (a changed key, an update) after the response.
 */
class SettingsPage implements ObserverInterface
{
    public function __construct(
        private readonly Reporting $reporting,
        private readonly RequestInterface $request,
    ) {
    }

    public function execute(Observer $observer): void
    {
        if ($this->request->getParam('section') === 'supertext') {
            $this->reporting->onRequest();
        }
    }
}
