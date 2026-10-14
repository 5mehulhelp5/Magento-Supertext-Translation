<?php

/**
 * @package     Supertext Translation for Magento
 * @copyright   (C) Supertext AG
 * @license     MIT
 */

declare(strict_types=1);

namespace Supertext\Translation\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Supertext\Translation\Model\Reporting;

/**
 * admin_system_config_changed_section_supertext: after the settings were saved, the automatic API
 * key check (shown as a message) and the `installed` / `settings_saved` report. Never fails the save.
 */
class SettingsSaved implements ObserverInterface
{
    public function __construct(
        private readonly Reporting $reporting,
        private readonly ManagerInterface $messages,
    ) {
    }

    public function execute(Observer $observer): void
    {
        try {
            $check = $this->reporting->afterSettingsSaved()?->check;

            if ($check === null) {
                return;
            }

            $message = $this->reporting->describe($check);

            // The deprecated adders keep the message's HTML (the links); describe() escapes everything else.
            if ($message['ok']) {
                $this->messages->addSuccess($message['html']);
            } else {
                $this->messages->addWarning($message['html']);
            }
        } catch (\Throwable) {
            // Reporting never breaks a save.
        }
    }
}
