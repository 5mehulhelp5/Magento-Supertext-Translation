<?php

/**
 * @package     Supertext Translation for Magento
 * @copyright   (C) Supertext AG
 * @license     MIT
 */

declare(strict_types=1);

namespace Supertext\Translation\Controller\Adminhtml\System;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Supertext\Translation\Model\Reporting;

/**
 * "Test connection" in the configuration: the automatic API key check (Supertext's free `features`
 * call) on the saved key, stored like the other checks; reports a changed result.
 */
class Test extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Supertext_Translation::config';

    public function __construct(Context $context, private readonly Reporting $reporting)
    {
        parent::__construct($context);
    }

    public function execute()
    {
        /** @var Json $json */
        $json  = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $check = $this->reporting->testConnection()?->check;

        if ($check === null) {
            $text = (string) __('The test could not be run.');

            return $json->setData(['ok' => false, 'message' => $text, 'html' => htmlspecialchars($text, ENT_QUOTES)]);
        }

        $message = $this->reporting->describe($check);

        return $json->setData(['ok' => $message['ok'], 'message' => $message['text'], 'html' => $message['html']]);
    }
}
