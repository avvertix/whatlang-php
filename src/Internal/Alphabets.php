<?php

declare(strict_types=1);

namespace Avvertix\WhatLang\Internal;

use Avvertix\WhatLang\FilterList;
use Avvertix\WhatLang\Script;

/**
 * Scores languages by the characters they use.
 *
 * Very rough on its own, but it costs almost nothing and rescues short texts
 * where there are too few trigrams to compare. For every non-stop character
 * of the lowercased text, each language whose alphabet contains that character
 * gains a point; scores are then normalised by the character count.
 *
 * Ported from whatlang-rs (src/alphabets/).
 *
 * @internal
 */
final class Alphabets
{
    /**
     * Inverted alphabets per script: codepoint => list of language codes.
     *
     * @var array<string, array<int, list<string>>>
     */
    private static array $invertedMaps = [];

    /**
     * Scripts that have no alphabet table yet; they fall back to a flat score.
     */
    private const WITHOUT_ALPHABETS = ['Arabic', 'Devanagari', 'Hebrew'];

    /**
     * @param  list<int>  $lowercaseCodepoints
     */
    public static function rawDetect(Script $script, array $lowercaseCodepoints, FilterList $filterList): AlphabetOutcome
    {
        if (in_array($script->value, self::WITHOUT_ALPHABETS, true)) {
            return self::flat($script, $filterList);
        }

        return self::calculateScores($script, $lowercaseCodepoints, $filterList);
    }

    /**
     * @param  list<int>  $codepoints
     */
    private static function calculateScores(Script $script, array $codepoints, FilterList $filterList): AlphabetOutcome
    {
        $map = self::invertedMap($script);
        $scriptLangs = $script->langs();
        $scriptLangCount = count($scriptLangs);

        $charScores = [];
        $maxRawScore = 0;

        foreach ($codepoints as $codepoint) {
            if (Chars::isStopChar($codepoint)) {
                continue;
            }

            $maxRawScore++;

            if (isset($map[$codepoint])) {
                // Award 2 per hit and subtract the character count at the end,
                // which spreads scores over -maxRawScore..maxRawScore.
                $charScores[$codepoint] = ($charScores[$codepoint] ?? 0) + 2;
            }
        }

        if ($maxRawScore === 0) {
            return new AlphabetOutcome(0, [], []);
        }

        $langScores = [];
        $commonScore = 0;

        foreach ($charScores as $codepoint => $charScore) {
            $langs = $map[$codepoint];

            // A character shared by every language of the script tells us
            // nothing, so it is tracked once instead of per language.
            if (count($langs) === $scriptLangCount) {
                $commonScore += $charScore;

                continue;
            }

            foreach ($langs as $code) {
                $langScores[$code] = ($langScores[$code] ?? 0) + $charScore;
            }
        }

        $rawScores = [];

        foreach ($scriptLangs as $lang) {
            if (! $filterList->isAllowed($lang)) {
                continue;
            }

            $score = ($langScores[$lang->value] ?? 0) + $commonScore - $maxRawScore;

            $rawScores[] = [$lang, max(0, $score)];
        }

        usort($rawScores, static fn (array $a, array $b): int => $b[1] <=> $a[1]);

        $scores = array_map(
            static fn (array $pair): array => [$pair[0], (float) $pair[1] / $maxRawScore],
            $rawScores,
        );

        return new AlphabetOutcome($maxRawScore, $rawScores, $scores);
    }

    /**
     * Equal scores for every language of a script we have no alphabet for.
     */
    private static function flat(Script $script, FilterList $filterList): AlphabetOutcome
    {
        $raw = [];
        $scores = [];

        foreach ($script->langs() as $lang) {
            if (! $filterList->isAllowed($lang)) {
                continue;
            }

            $raw[] = [$lang, 1];
            $scores[] = [$lang, 1.0];
        }

        return new AlphabetOutcome(1, $raw, $scores);
    }

    /**
     * @return array<int, list<string>>
     */
    private static function invertedMap(Script $script): array
    {
        if (isset(self::$invertedMaps[$script->value])) {
            return self::$invertedMaps[$script->value];
        }

        /** @var array<string, string> $alphabets */
        $alphabets = require dirname(__DIR__, 2).'/resources/alphabets/'.strtolower($script->value).'.php';

        $map = [];

        foreach ($alphabets as $code => $alphabet) {
            foreach (Text::toCodepoints($alphabet) as $codepoint) {
                $map[$codepoint][] = $code;
            }
        }

        ksort($map);

        return self::$invertedMaps[$script->value] = $map;
    }
}
