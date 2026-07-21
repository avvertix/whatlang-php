<?php

declare(strict_types=1);

namespace Avvertix\WhatLang\Internal;

use Avvertix\WhatLang\Lang;
use Avvertix\WhatLang\Script;

/**
 * Unicode ranges per script, and the script -> language mapping.
 *
 * Ported from whatlang-rs (src/scripts/chars.rs, src/scripts/lang_mapping.rs).
 *
 * @internal
 */
final class Chars
{
    /**
     * Codepoint ranges per script, as [start, end] pairs (both inclusive).
     *
     * The order of the scripts here is the order in which raw script detection
     * starts checking them, and matters for reproducing whatlang-rs results.
     *
     * @var array<string, list<array{int, int}>>
     */
    private const RANGES = [
        'Latin' => [
            [0x0061, 0x007A], [0x0041, 0x005A], [0x0080, 0x00FF], [0x0100, 0x017F],
            [0x0180, 0x024F], [0x0250, 0x02AF], [0x1D00, 0x1D7F], [0x1D80, 0x1DBF],
            [0x1E00, 0x1EFF], [0x2100, 0x214F], [0x2C60, 0x2C7F], [0xA720, 0xA7FF],
            [0xAB30, 0xAB6F],
        ],
        'Cyrillic' => [
            [0x0400, 0x0484], [0x0487, 0x052F], [0x2DE0, 0x2DFF], [0xA640, 0xA69D],
            [0x1D2B, 0x1D2B], [0x1D78, 0x1D78], [0xA69F, 0xA69F],
        ],
        'Arabic' => [
            [0x0600, 0x06FF], [0x0750, 0x07FF], [0x08A0, 0x08FF], [0xFB50, 0xFDFF],
            [0xFE70, 0xFEFF], [0x10E60, 0x10E7F], [0x1EE00, 0x1EEFF],
        ],
        'Mandarin' => [
            [0x2E80, 0x2E99], [0x2E9B, 0x2EF3], [0x2F00, 0x2FD5], [0x3005, 0x3005],
            [0x3007, 0x3007], [0x3021, 0x3029], [0x3038, 0x303B], [0x3400, 0x4DB5],
            [0x4E00, 0x9FCC], [0xF900, 0xFA6D], [0xFA70, 0xFAD9],
        ],
        'Devanagari' => [
            [0x0900, 0x097F], [0xA8E0, 0xA8FF], [0x1CD0, 0x1CFF],
        ],
        'Hebrew' => [
            [0x0590, 0x05FF],
        ],
        'Ethiopic' => [
            [0x1200, 0x139F], [0x2D80, 0x2DDF], [0xAB00, 0xAB2F],
        ],
        'Georgian' => [
            [0x10A0, 0x10FF],
        ],
        'Bengali' => [
            [0x0980, 0x09FF],
        ],
        'Hangul' => [
            [0xAC00, 0xD7AF], [0x1100, 0x11FF], [0x3130, 0x318F], [0x3200, 0x32FF],
            [0xA960, 0xA97F], [0xD7B0, 0xD7FF], [0xFF00, 0xFFEF],
        ],
        'Hiragana' => [
            [0x3040, 0x309F],
        ],
        'Katakana' => [
            [0x30A0, 0x30FF],
        ],
        'Greek' => [
            [0x0370, 0x03FF],
        ],
        'Kannada' => [
            [0x0C80, 0x0CFF],
        ],
        'Tamil' => [
            [0x0B80, 0x0BFF],
        ],
        'Thai' => [
            [0x0E00, 0x0E7F],
        ],
        'Gujarati' => [
            [0x0A80, 0x0AFF],
        ],
        'Gurmukhi' => [
            [0x0A00, 0x0A7F],
        ],
        'Telugu' => [
            [0x0C00, 0x0C7F],
        ],
        'Malayalam' => [
            [0x0D00, 0x0D7F],
        ],
        'Oriya' => [
            [0x0B00, 0x0B7F],
        ],
        'Myanmar' => [
            [0x1000, 0x109F],
        ],
        'Sinhala' => [
            [0x0D80, 0x0DFF],
        ],
        'Khmer' => [
            [0x1780, 0x17FF], [0x19E0, 0x19FF],
        ],
        'Armenian' => [
            [0x0530, 0x058F], [0xFB13, 0xFB17],
        ],
    ];

    /**
     * Language codes per script, in the order whatlang-rs lists them.
     *
     * @var array<string, list<string>>
     */
    private const LANGS = [
        'Latin' => [
            'spa', 'eng', 'por', 'ind', 'fra', 'deu', 'jav', 'vie', 'ita', 'tur',
            'pol', 'ron', 'hrv', 'nld', 'uzb', 'hun', 'aze', 'ces', 'zul', 'swe',
            'aka', 'sna', 'afr', 'fin', 'slk', 'tgl', 'tuk', 'dan', 'nob', 'cat',
            'lit', 'slv', 'epo', 'lav', 'est', 'lat', 'cym',
        ],
        'Cyrillic' => ['rus', 'ukr', 'srp', 'bel', 'bul', 'mkd'],
        'Arabic' => ['ara', 'urd', 'pes'],
        'Devanagari' => ['hin', 'mar', 'nep'],
        'Hebrew' => ['heb', 'yid'],
        'Mandarin' => ['cmn'],
        'Bengali' => ['ben'],
        'Hangul' => ['kor'],
        'Georgian' => ['kat'],
        'Greek' => ['ell'],
        'Kannada' => ['kan'],
        'Tamil' => ['tam'],
        'Thai' => ['tha'],
        'Gujarati' => ['guj'],
        'Gurmukhi' => ['pan'],
        'Telugu' => ['tel'],
        'Malayalam' => ['mal'],
        'Oriya' => ['ori'],
        'Myanmar' => ['mya'],
        'Sinhala' => ['sin'],
        'Khmer' => ['khm'],
        'Ethiopic' => ['amh'],
        'Armenian' => ['hye'],
        'Katakana' => ['jpn'],
        'Hiragana' => ['jpn'],
    ];

    /**
     * Scripts in the order raw detection checks them.
     *
     * @return list<Script>
     */
    public static function detectionOrder(): array
    {
        return array_map(
            static fn (string $name): Script => Script::from($name),
            array_keys(self::RANGES),
        );
    }

    /**
     * @return list<array{int, int}>
     */
    public static function ranges(Script $script): array
    {
        return self::RANGES[$script->value];
    }

    public static function inScript(Script $script, int $codepoint): bool
    {
        foreach (self::RANGES[$script->value] as [$start, $end]) {
            if ($codepoint >= $start && $codepoint <= $end) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<Lang>
     */
    public static function scriptLangs(Script $script): array
    {
        return array_map(
            static fn (string $code): Lang => Lang::from($code),
            self::LANGS[$script->value],
        );
    }

    /**
     * Is this a space, punctuation or digit?
     *
     * A stop character carries no signal for script or language detection.
     */
    public static function isStopChar(int $codepoint): bool
    {
        return ($codepoint >= 0x0000 && $codepoint <= 0x0040)
            || ($codepoint >= 0x005B && $codepoint <= 0x0060)
            || ($codepoint >= 0x007B && $codepoint <= 0x007E);
    }
}
