<?php

declare(strict_types=1);

namespace Avvertix\WhatLang;

use Avvertix\WhatLang\Internal\Alphabets;
use Avvertix\WhatLang\Internal\Combined;
use Avvertix\WhatLang\Internal\Confidence;
use Avvertix\WhatLang\Internal\ScriptDetection;
use Avvertix\WhatLang\Internal\Text;
use Avvertix\WhatLang\Internal\Trigrams;

/**
 * Detects the language and writing system of a text.
 *
 * ```php
 * $detector = new Detector;
 * $info = $detector->detect('Ĉu vi ne volas eklerni Esperanton?');
 *
 * $info->lang;       // Lang::Epo
 * $info->script;     // Script::Latin
 * $info->confidence; // 1.0
 * ```
 *
 * Ported from whatlang-rs (src/core/detector.rs, src/core/detect.rs).
 */
final class Detector
{
    public readonly Options $options;

    public function __construct(?Options $options = null)
    {
        $this->options = $options ?? new Options;
    }

    /**
     * A detector restricted to the given languages.
     *
     * @param  iterable<Lang>  $allowlist
     */
    public static function withAllowlist(iterable $allowlist): self
    {
        return new self(new Options(FilterList::allow($allowlist)));
    }

    /**
     * A detector that will never return the given languages.
     *
     * @param  iterable<Lang>  $denylist
     */
    public static function withDenylist(iterable $denylist): self
    {
        return new self(new Options(FilterList::deny($denylist)));
    }

    /**
     * Detects language and script, or null when the text carries no signal
     * (empty, digits and punctuation only, or every candidate filtered out).
     */
    public function detect(string $text): ?DetectionInfo
    {
        $text = new Text($text);
        $scriptInfo = ScriptDetection::rawDetect($text->codepoints());
        $script = $scriptInfo->mainScript();

        if ($script === null) {
            return null;
        }

        if ($script === Script::Mandarin) {
            return $this->detectMandarinOrJapanese($scriptInfo);
        }

        $sole = ScriptDetection::soleLang($script);

        if ($sole !== null) {
            return new DetectionInfo($script, $sole, 1.0);
        }

        return $this->detectAmongLangs($script, $text);
    }

    /**
     * Detects only the language.
     */
    public function detectLang(string $text): ?Lang
    {
        return $this->detect($text)?->lang;
    }

    /**
     * Detects only the writing system. Much cheaper than a full detection,
     * and unaffected by any language filter.
     */
    public function detectScript(string $text): ?Script
    {
        return ScriptDetection::rawDetect(Text::toCodepoints($text))->mainScript();
    }

    /**
     * Picks between the languages that share a script.
     */
    private function detectAmongLangs(Script $script, Text $text): ?DetectionInfo
    {
        $filterList = $this->options->filterList;

        switch ($this->options->method) {
            case Method::Alphabet:
                $outcome = Alphabets::rawDetect($script, $text->lowercaseCodepoints(), $filterList);
                $count = $outcome->count;
                $scores = $outcome->scores;
                break;

            case Method::Trigram:
                $outcome = Trigrams::rawDetect($script, $text, $filterList);
                $count = $outcome->trigramsCount;
                $scores = $outcome->scores;
                break;

            case Method::Combined:
                $outcome = Combined::rawDetect($script, $text, $filterList);
                $count = $outcome['count'];
                $scores = $outcome['scores'];
                break;
        }

        if ($scores === []) {
            return null;
        }

        [$lang, $score] = $scores[0];

        $confidence = isset($scores[1])
            ? Confidence::calculate($score, $scores[1][1], $count)
            : 1.0;

        return new DetectionInfo($script, $lang, $confidence);
    }

    /**
     * Mandarin script is also used to write Japanese, so the two are told apart
     * by how much Kana is mixed in.
     *
     * See https://github.com/greyblake/whatlang-rs/pull/45
     */
    private function detectMandarinOrJapanese(ScriptDetection $scriptInfo): DetectionInfo
    {
        if (! $this->options->filterList->isAllowed(Lang::Cmn)) {
            return new DetectionInfo(Script::Mandarin, Lang::Jpn, 1.0);
        }

        $japanese = $scriptInfo->count(Script::Katakana) + $scriptInfo->count(Script::Hiragana);
        $total = $scriptInfo->count(Script::Mandarin) + $japanese;

        $japaneseShare = $total > 0 ? $japanese / $total : 0.0;

        // Kana never appears in Chinese, so even a trace of it points at
        // Japanese. See https://github.com/greyblake/whatlang-rs/issues/88
        [$lang, $confidence] = match (true) {
            $japaneseShare > 0.2 => [Lang::Jpn, 1.0],
            $japaneseShare > 0.05 => [Lang::Jpn, 0.5],
            $japaneseShare > 0.02 => [Lang::Cmn, 0.5],
            default => [Lang::Cmn, 1.0],
        };

        return new DetectionInfo(Script::Mandarin, $lang, $confidence);
    }
}
