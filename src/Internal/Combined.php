<?php

declare(strict_types=1);

namespace Avvertix\WhatLang\Internal;

use Avvertix\WhatLang\FilterList;
use Avvertix\WhatLang\Lang;
use Avvertix\WhatLang\Script;

/**
 * Blends the alphabet and trigram scores.
 *
 * Ported from whatlang-rs (src/combined/mod.rs).
 *
 * @internal
 */
final class Combined
{
    /**
     * @return array{count: int, scores: list<array{Lang, float}>}
     */
    public static function rawDetect(Script $script, Text $text, FilterList $filterList): array
    {
        $alphabet = Alphabets::rawDetect($script, $text->lowercaseCodepoints(), $filterList);
        $trigram = Trigrams::rawDetect($script, $text, $filterList);

        $alphabetScores = [];
        foreach ($alphabet->scores as [$lang, $score]) {
            $alphabetScores[$lang->value] = $score;
        }

        $trigramScores = [];
        foreach ($trigram->scores as [$lang, $score]) {
            $trigramScores[$lang->value] = $score;
        }

        // Alphabet order first, then any language only the trigram pass saw.
        $codes = array_keys($alphabetScores + $trigramScores);

        $alphabetWeight = self::alphabetWeight($alphabet->count);
        $trigramWeight = 1.0 - $alphabetWeight;

        $scores = [];

        foreach ($codes as $code) {
            $scores[] = [
                Lang::from($code),
                ($alphabetScores[$code] ?? 0.0) * $alphabetWeight
                    + ($trigramScores[$code] ?? 0.0) * $trigramWeight,
            ];
        }

        usort($scores, static fn (array $a, array $b): int => $b[1] <=> $a[1]);

        return ['count' => $trigram->trigramsCount, 'scores' => $scores];
    }

    /**
     * How much the alphabet score is worth, given the text length.
     *
     * The longer the text, the more the trigram score is trusted instead:
     * the weight falls from 2/3 to 1/3 over the first hundred characters.
     */
    private static function alphabetWeight(int $count): float
    {
        $weight = -($count / 300.0) + 2.0 / 3.0;

        return max(1.0 / 3.0, min($weight, 2.0 / 3.0));
    }
}
