<?php

declare(strict_types=1);

namespace Avvertix\WhatLang\Internal;

/**
 * The text under inspection, with its codepoint and lowercase forms computed
 * lazily and cached — several detection passes ask for the same thing.
 *
 * Ported from whatlang-rs (src/core/text.rs).
 *
 * @internal
 */
final class Text
{
    /** @var list<int>|null */
    private ?array $codepoints = null;

    private ?string $lowercase = null;

    /** @var list<int>|null */
    private ?array $lowercaseCodepoints = null;

    public function __construct(
        public readonly string $original,
    ) {}

    /**
     * Splits a UTF-8 string into Unicode codepoints.
     *
     * @return list<int>
     */
    public static function toCodepoints(string $text): array
    {
        if ($text === '') {
            return [];
        }

        $utf32 = mb_convert_encoding($text, 'UTF-32BE', 'UTF-8');

        if ($utf32 === '') {
            return [];
        }

        /** @var list<int> */
        return array_values(unpack('N*', $utf32));
    }

    /**
     * @return list<int>
     */
    public function codepoints(): array
    {
        return $this->codepoints ??= self::toCodepoints($this->original);
    }

    public function lowercase(): string
    {
        return $this->lowercase ??= mb_strtolower($this->original, 'UTF-8');
    }

    /**
     * @return list<int>
     */
    public function lowercaseCodepoints(): array
    {
        return $this->lowercaseCodepoints ??= self::toCodepoints($this->lowercase());
    }
}
