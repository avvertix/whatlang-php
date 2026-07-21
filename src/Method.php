<?php

declare(strict_types=1);

namespace Avvertix\WhatLang;

/**
 * Which detection algorithm to run.
 *
 * Ported from whatlang-rs (src/core/method.rs).
 */
enum Method: string
{
    /** Compare trigram frequency profiles. Accurate on longer texts. */
    case Trigram = 'Trigram';

    /** Score languages by the characters they use. Cheap, helps on short texts. */
    case Alphabet = 'Alphabet';

    /** Weighted blend of the two, weighted by text length. The default. */
    case Combined = 'Combined';

    /**
     * Parses a method name, ignoring case and surrounding whitespace.
     */
    public static function fromName(string $name): ?self
    {
        return match (strtolower(trim($name))) {
            'trigram' => self::Trigram,
            'alphabet' => self::Alphabet,
            'combined' => self::Combined,
            default => null,
        };
    }
}
