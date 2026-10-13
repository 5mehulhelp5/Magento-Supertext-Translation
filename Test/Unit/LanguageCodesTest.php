<?php

declare(strict_types=1);

namespace Supertext\Translation\Test\Unit;

use PHPUnit\Framework\TestCase;
use Supertext\Sdk\LanguageCodes;
use Supertext\Translation\Model\Translation\EntityTypes;

/**
 * Magento locales through the SDK's language codes, as Config::targetCode() and sourceCode()
 * use them: the store view's "Supertext language code" wins over its locale.
 */
final class LanguageCodesTest extends TestCase
{
    public function testSourceIsTheBareLanguage(): void
    {
        self::assertSame('en', LanguageCodes::source('en_US'));
        self::assertSame('en', LanguageCodes::source('en_GB'));
        self::assertSame('de', LanguageCodes::source('de_CH'));
        self::assertSame('', LanguageCodes::source(''));
    }

    public function testTargetsKeepTheLocaleRegion(): void
    {
        self::assertSame('de-CH', LanguageCodes::target('de_CH'));
        self::assertSame('fr-CH', LanguageCodes::target('fr_CH'));
        self::assertSame('zh-Hant-TW', LanguageCodes::target('zh_Hant_TW'));
        self::assertSame('es-419', LanguageCodes::target('es-419'));
    }

    public function testBareOverridesGetADefaultRegion(): void
    {
        self::assertSame('de-DE', LanguageCodes::target('de'));
        self::assertSame('en-US', LanguageCodes::target('EN'));
        self::assertSame('sl-SI', LanguageCodes::target('sl'));
        self::assertSame('rm', LanguageCodes::target('rm'));
    }

    public function testFormatsTagsForTheGrid(): void
    {
        self::assertSame('de-AT', EntityTypes::formatTag('de_at'));
        self::assertSame('zh-Hant', EntityTypes::formatTag('zh_hant'));
    }
}
