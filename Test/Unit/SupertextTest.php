<?php

declare(strict_types=1);

namespace Supertext\Translation\Test\Unit;

use PHPUnit\Framework\TestCase;
use Supertext\Sdk\Http\HttpRequest;
use Supertext\Sdk\Http\HttpResponse;
use Supertext\Sdk\Http\HttpTransport;
use Supertext\Sdk\V1\Segment;
use Supertext\Sdk\V1\SupertextException as SdkException;
use Supertext\Translation\Api\Supertext;
use Supertext\Translation\Api\SupertextException;

/**
 * The module's use of the Supertext SDK: its defaults and how SDK errors become the module's
 * translatable messages. The protocol itself is tested in the SDK.
 */
final class SupertextTest extends TestCase
{
    /** @var list<HttpRequest> */
    private array $requests = [];

    /** @param callable(HttpRequest): HttpResponse $handler */
    private function transport(callable $handler): HttpTransport
    {
        return new class ($handler, $this->requests) implements HttpTransport {
            /** @param list<HttpRequest> $requests */
            public function __construct(private $handler, private array &$requests)
            {
            }

            public function send(HttpRequest $request): HttpResponse
            {
                $this->requests[] = $request;

                return ($this->handler)($request);
            }
        };
    }

    public function testTranslatesFieldsThroughTheSdk(): void
    {
        $transport = $this->transport(static function (HttpRequest $r): HttpResponse {
            return match (true) {
                $r->method === 'POST'                    => new HttpResponse(200, '{"file_id":"f1"}'),
                str_ends_with($r->url, '/status')        => new HttpResponse(200, '{"status":"done"}'),
                str_ends_with($r->url, '/translation')   => new HttpResponse(200, '<div data-st-id="0">Pralinen</div><div data-st-id="1"><p>In <b>Bern</b> gemacht.</p></div>'),
                default                                  => new HttpResponse(204),
            };
        });

        $client = Supertext::client('Supertext-Auth-Key test-key', 'https://api.test/v1', 60, '1.2.3', $transport, 0.0, static function (): void {
        });

        $result = $client->translateTexts(
            ['name' => 'Pralines', 'description' => Segment::html('<p>Made in <b>Bern</b>.</p>'), 'empty' => ' '],
            targetLang: 'de-CH',
            sourceLang: 'en',
            politeness: 'more',
        );

        self::assertSame(['name' => 'Pralinen', 'description' => '<p>In <b>Bern</b> gemacht.</p>', 'empty' => ' '], $result);

        $post = $this->requests[0];
        self::assertSame('https://api.test/v1/translate/ai/file', $post->url);
        self::assertSame('Supertext-Auth-Key test-key', $post->headers['Authorization']);
        self::assertStringContainsString('Magento-Supertext-Translation/1.2.3', $post->headers['User-Agent']);
        self::assertStringContainsString("name=\"target_lang\"\r\n\r\nde-CH\r\n", (string) $post->body);
        self::assertStringContainsString("name=\"politeness\"\r\n\r\nmore\r\n", (string) $post->body);
        self::assertSame('DELETE', end($this->requests)->method);
    }

    public function testNormalizesThePastedKeyAndEndpoints(): void
    {
        self::assertSame('abc', Supertext::normalizeKey('  Supertext-Auth-Key abc '));
        self::assertSame('abc', Supertext::normalizeKey('abc'));
        self::assertSame(Supertext::LIVE, Supertext::baseUrlFor('live'));
        self::assertSame(Supertext::STAGING, Supertext::baseUrlFor('staging'));
        self::assertSame('http://127.0.0.1:8765/v1/', Supertext::baseUrlFor('live', 'http://127.0.0.1:8765/v1/'));
    }

    public function testAuthenticationFailureLinksToTheAccountAndKeyPages(): void
    {
        $e = Supertext::error(new SdkException(SdkException::AUTHENTICATION_FAILURE, 401, 'Invalid API key'));

        self::assertInstanceOf(SupertextException::class, $e);
        self::assertStringStartsWith('Authentication failed.', $e->template());
        self::assertSame([Supertext::SIGNUP_URL, Supertext::API_KEY_URL], $e->parameters());
        self::assertSame('Invalid API key', $e->detail());
        self::assertSame(401, $e->getCode());
        self::assertStringContainsString('https://www.supertext.com/person/en/account/signin', $e->getMessage());
    }

    public function testLanguagePairNamesBothCodesWithoutTheDetail(): void
    {
        $e = Supertext::error(new SdkException(SdkException::INVALID_LANGUAGE_PAIR, 400, 'INVALID_LANGUAGE_PAIR', 'INVALID_LANGUAGE_PAIR', 'en', 'xx'));

        self::assertSame(['en', 'xx'], $e->parameters());
        self::assertSame('', $e->detail());
        self::assertStringContainsString('"en" into "xx"', $e->getMessage());

        // Supertext didn't name the codes: the ones that were sent.
        self::assertSame(['en', 'xx-XX'], Supertext::error(new SdkException(SdkException::INVALID_LANGUAGE_PAIR, 400), 'en', 'xx-XX')->parameters());

        $unknown = Supertext::error(new SdkException(SdkException::INVALID_LANGUAGE_PAIR, 400));
        self::assertStringContainsString('Choose another Supertext language', $unknown->getMessage());
        self::assertSame([], $unknown->parameters());
        self::assertStringStartsWith('Supertext does not translate into this language.', $unknown->getMessage());
    }

    public function testUnsupportedLanguageAndFeature(): void
    {
        $target = Supertext::error(new SdkException(SdkException::UNSUPPORTED_LANGUAGE, targetLang: 'es_ES'));
        self::assertSame(['es_ES'], $target->parameters());
        self::assertStringContainsString('"es_ES" has no Supertext language yet', $target->getMessage());
        self::assertSame('', $target->detail());

        $source = Supertext::error(new SdkException(SdkException::UNSUPPORTED_LANGUAGE, sourceLang: 'xx_XX'));
        self::assertStringStartsWith('The source language "xx_XX"', $source->getMessage());

        $feature = Supertext::error(new SdkException(SdkException::UNSUPPORTED_FEATURE, 400, 'FEATURE_ERROR', 'FEATURE_ERROR', 'en', 'en-US', feature: 'politeness'));
        self::assertStringStartsWith('Supertext offers no formal or informal tone for this language.', $feature->getMessage());
    }

    public function testCodesAreSentAsGiven(): void
    {
        $transport = $this->transport(static fn (HttpRequest $r): HttpResponse => match (true) {
            $r->method === 'POST'                  => new HttpResponse(200, '{"file_id":"f1"}'),
            str_ends_with($r->url, '/status')      => new HttpResponse(200, '{"status":"done"}'),
            str_ends_with($r->url, '/translation') => new HttpResponse(200, '<div data-st-id="0">Hola</div>'),
            default                                => new HttpResponse(204),
        });

        Supertext::client('k', 'https://api.test/v1', 60, '1.0.0', $transport, 0.0, static function (): void {
        })->translateTexts(['t' => 'Hello'], 'es', 'en');

        self::assertStringContainsString("name=\"target_lang\"\r\n\r\nes\r\n", (string) $this->requests[0]->body);
    }

    public function testEveryErrorCodeHasItsOwnMessage(): void
    {
        $codes = [];

        foreach ((new \ReflectionClass(SdkException::class))->getReflectionConstants(\ReflectionClassConstant::IS_PUBLIC) as $constant) {
            $codes[] = (string) $constant->getValue();
        }

        $seen  = [];

        foreach ($codes as $code) {
            $template = Supertext::error(new SdkException($code, 500))->template();
            $seen[$template][] = $code;
        }

        // Only unexpected_status uses the generic "HTTP %1" message.
        self::assertSame(\count($codes), \count($seen));
        self::assertSame(['500'], Supertext::error(new SdkException(SdkException::UNEXPECTED_STATUS, 500))->parameters());
    }
}
