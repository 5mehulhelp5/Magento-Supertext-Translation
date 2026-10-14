<?php

declare(strict_types=1);

namespace Supertext\Translation\Test\Unit;

use PHPUnit\Framework\TestCase;
use Supertext\Sdk\V1\HtmlDocument;
use Supertext\Translation\Model\Translation\FieldPlanner;

final class FieldPlannerTest extends TestCase
{
    private const FIELDS = [
        'name'        => ['html' => false, 'size' => 20],
        'description' => ['html' => true, 'size' => 0],
        'meta_title'  => ['html' => false, 'size' => 255],
        'available'   => ['html' => false, 'size' => 255],
    ];

    private const SOURCE = [
        'name'        => 'Praline box',
        'description' => '<p>Made in <strong>Bern</strong>.</p>',
        'meta_title'  => 'Pralines',
        'available'   => '',
    ];

    public function testFillsEmptyAndCopiedFieldsButKeepsTranslations(): void
    {
        // PrestaShop copies the default language into a new one: "Praline box" is untranslated.
        $planner = new FieldPlanner(self::FIELDS, self::SOURCE, ['name' => 'Praline box', 'description' => '', 'meta_title' => 'Pralinen'], false);

        self::assertSame(['name', 'description'], $planner->toTranslate());
        self::assertSame(['meta_title'], $planner->kept());
        self::assertSame('partial', $planner->state());
    }

    public function testOverwriteSendsEverythingWithText(): void
    {
        $planner = new FieldPlanner(self::FIELDS, self::SOURCE, ['meta_title' => 'Pralinen'], true);

        self::assertSame(['name', 'description', 'meta_title'], $planner->toTranslate());
        self::assertSame([], $planner->kept());
    }

    public function testStates(): void
    {
        self::assertSame('none', (new FieldPlanner(self::FIELDS, self::SOURCE, self::SOURCE, false))->state());
        self::assertSame('translated', (new FieldPlanner(self::FIELDS, self::SOURCE, [
            'name' => 'Pralinenbox', 'description' => '<p>In Bern gemacht.</p>', 'meta_title' => 'Pralinen',
        ], false))->state());
        // Only whitespace differs: still the copied source.
        self::assertFalse((new FieldPlanner(self::FIELDS, self::SOURCE, ['name' => "Praline\n box "], false))->isTranslated('name'));
    }

    public function testSegmentsAndApplyRoundTrip(): void
    {
        $planner  = new FieldPlanner(self::FIELDS, self::SOURCE, [], false);
        $segments = $planner->segments();

        // One SDK segment per field, keyed by field; empty fields are not sent.
        self::assertSame(['name', 'description', 'meta_title'], array_keys($segments));
        self::assertFalse($segments['name']->html);
        self::assertTrue($segments['description']->html);
        self::assertSame('<p>Made in <strong>Bern</strong>.</p>', $segments['description']->text);

        $values = $planner->apply([
            'name'        => 'Pralinenschachtel mit dunkler Schokolade',
            'description' => "\n<p>In <strong>Bern</strong> gemacht.</p> ",
            'meta_title'  => 'Pralinen',
        ]);

        // Plain fields are cut to their size, rich text is not.
        self::assertSame('Pralinenschachtel mi', $values['name']);
        self::assertSame('<p>In <strong>Bern</strong> gemacht.</p>', $values['description']);
        self::assertSame('Pralinen', $values['meta_title']);
        self::assertArrayNotHasKey('available', $values);
    }

    public function testProtectsMagentoDirectivesInRichText(): void
    {
        $source  = '<p>See {{widget type="Magento\\Cms\\Block\\Widget\\Page\\Link" page_id="2"}} and <a href="{{store url=\'contact\'}}">us</a></p>';
        $planner = new FieldPlanner(['description' => ['html' => true, 'size' => 0]], ['description' => $source], [], false);
        $sent    = $planner->segments()['description']->text;

        self::assertStringContainsString('<span translate="no" class="notranslate" data-st-keep="1">{{widget type="Magento\\Cms\\Block\\Widget\\Page\\Link" page_id="2"}}</span>', $sent);
        self::assertStringContainsString('<a href="{{store url=\'contact\'}}">', $sent);
        self::assertSame(['description' => $source], $planner->apply(['description' => $sent]));
    }

    public function testKeepsPageBuilderMarkupByteForByte(): void
    {
        $content = '<div data-content-type="row" data-appearance="contained" data-element="main">'
            . '<div data-enable-parallax="0" data-background-images=\'{\"desktop_image\":\"{{media url=wysiwyg/bern.jpg}}\"}\' data-element="inner">'
            . '<div data-content-type="text" data-element="main"><p>Made in <strong>Bern</strong>.</p></div>'
            . '<figure><img src="{{media url=wysiwyg/box.jpg}}" alt="Praline box"></figure>'
            . '</div></div>';
        $planner = new FieldPlanner(['content' => ['html' => true, 'size' => 0]], ['content' => $content], [], false);
        $segments = $planner->segments();

        // Through the SDK's document packing and back, as Supertext returns it unchanged.
        $list   = array_values($segments);
        $parsed = HtmlDocument::parse(HtmlDocument::build($list), $list);

        self::assertSame(['content' => $content], $planner->apply(['content' => (string) $parsed[0]]));
    }

    public function testSlugFollowsTheTitleOnlyWhileUntranslated(): void
    {
        $planner = new FieldPlanner(self::FIELDS, self::SOURCE, [], false);

        self::assertTrue($planner->shouldRewriteSlug('praline-box', 'praline-box'));
        self::assertTrue($planner->shouldRewriteSlug('praline-box', ''));
        self::assertFalse($planner->shouldRewriteSlug('praline-box', 'pralinen'));
        self::assertTrue((new FieldPlanner(self::FIELDS, self::SOURCE, [], true))->shouldRewriteSlug('praline-box', 'pralinen'));
    }
}
