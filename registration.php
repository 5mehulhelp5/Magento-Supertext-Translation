<?php

/**
 * @package     Supertext Translation for Magento
 * @copyright   (C) Supertext AG
 * @license     MIT
 */

declare(strict_types=1);

use Magento\Framework\Component\ComponentRegistrar;

// The vendored Supertext SDK (lib/supertext-sdk, namespace Supertext\Sdk): Magento's autoloader only
// knows the module's own namespace in app/code installs, so the SDK brings its own.
require_once __DIR__ . '/lib/supertext-sdk/autoload.php';

ComponentRegistrar::register(ComponentRegistrar::MODULE, 'Supertext_Translation', __DIR__);
