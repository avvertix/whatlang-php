<?php

declare(strict_types=1);

namespace Avvertix\WhatLang;

use Avvertix\WhatLang\Internal\Chars;

/**
 * A writing system (Latin, Cyrillic, Arabic, ...).
 *
 * Ported from whatlang-rs (src/scripts/script.rs).
 */
enum Script: string
{
    case Arabic = 'Arabic';
    case Armenian = 'Armenian';
    case Bengali = 'Bengali';
    case Cyrillic = 'Cyrillic';
    case Devanagari = 'Devanagari';
    case Ethiopic = 'Ethiopic';
    case Georgian = 'Georgian';
    case Greek = 'Greek';
    case Gujarati = 'Gujarati';
    case Gurmukhi = 'Gurmukhi';
    case Hangul = 'Hangul';
    case Hebrew = 'Hebrew';
    case Hiragana = 'Hiragana';
    case Kannada = 'Kannada';
    case Katakana = 'Katakana';
    case Khmer = 'Khmer';
    case Latin = 'Latin';
    case Malayalam = 'Malayalam';
    case Mandarin = 'Mandarin';
    case Myanmar = 'Myanmar';
    case Oriya = 'Oriya';
    case Sinhala = 'Sinhala';
    case Tamil = 'Tamil';
    case Telugu = 'Telugu';
    case Thai = 'Thai';

    /**
     * Every known script.
     *
     * @return list<self>
     */
    public static function all(): array
    {
        return self::cases();
    }

    /**
     * Parses a script name, ignoring case and surrounding whitespace.
     */
    public static function fromName(string $name): ?self
    {
        $needle = strtolower(trim($name));

        foreach (self::cases() as $script) {
            if (strtolower($script->value) === $needle) {
                return $script;
            }
        }

        return null;
    }

    /**
     * The script name, e.g. "Cyrillic".
     */
    public function scriptName(): string
    {
        return $this->value;
    }

    /**
     * Languages that are written in this script.
     *
     * @return list<Lang>
     */
    public function langs(): array
    {
        return Chars::scriptLangs($this);
    }

    /**
     * Whether a codepoint belongs to this script.
     */
    public function matches(int $codepoint): bool
    {
        return Chars::inScript($this, $codepoint);
    }
}
