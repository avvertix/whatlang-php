<?php

declare(strict_types=1);

namespace Avvertix\WhatLang;

/**
 * Natural language detection.
 *
 * ```php
 * WhatLang::detectLang('There is no reason not to learn Esperanto.'); // Lang::Eng
 * WhatLang::detectScript('Благодаря Эсперанто');                      // Script::Cyrillic
 * ```
 *
 * A PHP port of whatlang-rs by Sergey Potapov.
 */
final class WhatLang
{
    /**
     * Detects language and script, or null when the text carries no signal.
     */
    public static function detect(string $text, ?Options $options = null): ?DetectionInfo
    {
        return new Detector($options)->detect($text);
    }

    /**
     * Detects only the language.
     */
    public static function detectLang(string $text, ?Options $options = null): ?Lang
    {
        return self::detect($text, $options)?->lang;
    }

    /**
     * Detects only the writing system. Much cheaper than a full detection.
     */
    public static function detectScript(string $text): ?Script
    {
        return new Detector()->detectScript($text);
    }
}
