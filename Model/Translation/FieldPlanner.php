<?php

/**
 * @package     Supertext Translation for Magento
 * @copyright   (C) Supertext AG
 * @license     MIT
 */

declare(strict_types=1);

namespace Supertext\Translation\Model\Translation;

use Supertext\Sdk\V1\Segment;

/**
 * Decides which fields of one item go to Supertext and what is written back.
 * No Magento dependencies, so the rules are unit-tested on their own.
 * The texts go to Supertext as Supertext SDK segments, one per field.
 *
 * A target field counts as *already translated* when it has text that differs from the
 * source. A store view without its own value shows the default value, so a
 * target identical to the source (or empty) is untranslated and is always filled.
 * Already translated fields are kept unless $overwrite is set.
 */
final class FieldPlanner
{
    /**
     * @param array<string, array{html: bool, size: int}> $fields field => definition
     * @param array<string, string>                         $source field => text in the source language
     * @param array<string, string>                         $target field => text in the target language
     */
    public function __construct(
        private readonly array $fields,
        private readonly array $source,
        private readonly array $target,
        private readonly bool $overwrite,
    ) {
    }

    /** @return list<string> fields to translate, in definition order */
    public function toTranslate(): array
    {
        $send = [];

        foreach (array_keys($this->fields) as $field) {
            $source = (string) ($this->source[$field] ?? '');

            if (trim($source) === '') {
                continue;
            }

            if (!$this->overwrite && $this->isTranslated($field)) {
                continue;
            }

            $send[] = $field;
        }

        return $send;
    }

    /** @return list<string> fields that keep their existing translation */
    public function kept(): array
    {
        if ($this->overwrite) {
            return [];
        }

        return array_values(array_filter(
            array_keys($this->fields),
            fn (string $field): bool => trim((string) ($this->source[$field] ?? '')) !== '' && $this->isTranslated($field),
        ));
    }

    /** "none", "partial" (some fields translated) or "translated" */
    public function state(): string
    {
        $kept  = \count($this->kept());
        $total = $kept + \count((new self($this->fields, $this->source, $this->target, false))->toTranslate());

        return match (true) {
            $kept === 0      => 'none',
            $kept === $total => 'translated',
            default          => 'partial',
        };
    }

    public function isTranslated(string $field): bool
    {
        $target = (string) ($this->target[$field] ?? '');

        return trim($target) !== '' && self::normalize($target) !== self::normalize((string) ($this->source[$field] ?? ''));
    }

    /**
     * The texts to send: field => segment, rich text as HTML with its Magento directives protected.
     *
     * @return array<string, Segment>
     */
    public function segments(): array
    {
        $segments = [];

        foreach ($this->toTranslate() as $field) {
            $text             = (string) $this->source[$field];
            $segments[$field] = $this->fields[$field]['html'] ? Segment::html(Directives::protect($text)) : Segment::text($text);
        }

        return $segments;
    }

    /**
     * The translated texts as field values, trimmed to each field's size.
     *
     * @param array<string, string> $translated field => translated text, as returned for segments()
     *
     * @return array<string, string> field => value to save
     */
    public function apply(array $translated): array
    {
        $values = [];

        foreach ($this->toTranslate() as $field) {
            $html = $this->fields[$field]['html'];
            $text = (string) ($translated[$field] ?? '');
            $text = $html ? trim(Directives::unprotect($text)) : $text;

            if (trim($text) === '') {
                continue;
            }

            $size = $this->fields[$field]['size'];

            if (!$html && $size > 0 && mb_strlen($text) > $size) {
                $text = rtrim(mb_substr($text, 0, $size));
            }

            $values[$field] = $text;
        }

        return $values;
    }

    /**
     * Whether the URL slug should follow a translated title: it is still the source's slug
     * (or empty), or $overwrite is set.
     */
    public function shouldRewriteSlug(string $sourceSlug, string $targetSlug): bool
    {
        return $this->overwrite || trim($targetSlug) === '' || $targetSlug === $sourceSlug;
    }

    private static function normalize(string $text): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }
}
