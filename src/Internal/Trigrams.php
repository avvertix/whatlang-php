<?php

declare(strict_types=1);

namespace Avvertix\WhatLang\Internal;

use Avvertix\WhatLang\FilterList;
use Avvertix\WhatLang\Lang;
use Avvertix\WhatLang\Script;

/**
 * Compares the text against per-language trigram frequency profiles.
 *
 * Each language ships a list of its 300 most common three-character sequences,
 * ordered by frequency. We rank the text's own trigrams the same way and score
 * a language by how far its ranking is from ours.
 *
 * Ported from whatlang-rs (src/trigrams/).
 *
 * @internal
 */
final class Trigrams
{
    /** How far apart a single trigram's ranks may count as. */
    public const MAX_TRIGRAM_DISTANCE = 300;

    /** 300 trigrams each at most MAX_TRIGRAM_DISTANCE away. */
    public const MAX_TOTAL_DISTANCE = self::MAX_TRIGRAM_DISTANCE * self::MAX_TRIGRAM_DISTANCE;

    /** Only the text's 600 most frequent trigrams are considered. */
    public const TEXT_TRIGRAMS_SIZE = 600;

    /**
     * @var array<string, array<string, list<string>>>
     */
    private static array $profiles = [];

    public static function rawDetect(Script $script, Text $text, FilterList $filterList): TrigramOutcome
    {
        $positions = self::positions($text);
        $uniqueCount = count($positions);

        $distances = [];

        foreach (self::profiles($script) as $code => $profile) {
            $lang = Lang::from($code);

            if (! $filterList->isAllowed($lang)) {
                continue;
            }

            $distances[] = [$lang, self::distance($profile, $positions, $uniqueCount)];
        }

        usort($distances, static fn (array $a, array $b): int => $a[1] <=> $b[1]);

        $maxDistance = $uniqueCount * self::MAX_TRIGRAM_DISTANCE;

        $scores = array_map(
            static fn (array $pair): array => [
                $pair[0],
                $maxDistance > 0 ? (float) ($maxDistance - $pair[1]) / $maxDistance : 0.0,
            ],
            $distances,
        );

        return new TrigramOutcome($uniqueCount, $distances, $scores);
    }

    /**
     * Ranks the text's trigrams by how often they occur: trigram => rank,
     * where rank 0 is the most frequent.
     *
     * @return array<string, int>
     */
    public static function positions(Text $text): array
    {
        $occurrences = self::count($text);

        // Most frequent first; ties broken by the trigram itself so the ranking
        // is stable for a given text. Byte order over UTF-8 matches codepoint
        // order, which is what whatlang-rs compares on.
        uksort(
            $occurrences,
            static fn (string $a, string $b): int => $occurrences[$b] <=> $occurrences[$a] ?: strcmp($b, $a),
        );

        $positions = [];
        $rank = 0;

        foreach ($occurrences as $trigram => $_) {
            if ($rank >= self::TEXT_TRIGRAMS_SIZE) {
                break;
            }

            $positions[$trigram] = $rank++;
        }

        return $positions;
    }

    /**
     * Counts every trigram in the text, treating punctuation and digits as
     * spaces and ignoring runs that are mostly space.
     *
     * @return array<string, int>
     */
    private static function count(Text $text): array
    {
        $chars = mb_str_split($text->lowercase(), 1, 'UTF-8');

        foreach ($chars as $i => $char) {
            // Every stop character is single-byte, so this check stays cheap.
            if (strlen($char) === 1 && Chars::isStopChar(ord($char))) {
                $chars[$i] = ' ';
            }
        }

        // A trailing space closes the final trigram, mirroring the leading one.
        $chars[] = ' ';

        $occurrences = [];
        $c1 = ' ';
        $c2 = array_shift($chars);

        foreach ($chars as $c3) {
            if (! ($c2 === ' ' && ($c1 === ' ' || $c3 === ' '))) {
                $trigram = $c1.$c2.$c3;
                $occurrences[$trigram] = ($occurrences[$trigram] ?? 0) + 1;
            }

            $c1 = $c2;
            $c2 = $c3;
        }

        return $occurrences;
    }

    /**
     * How far a language profile sits from the text's trigram ranking.
     *
     * @param  list<string>  $profile
     * @param  array<string, int>  $positions
     */
    private static function distance(array $profile, array $positions, int $uniqueCount): int
    {
        $total = 0;

        foreach ($profile as $i => $trigram) {
            $total += isset($positions[$trigram])
                ? abs($positions[$trigram] - $i)
                : self::MAX_TRIGRAM_DISTANCE;
        }

        // A short text cannot possibly match all 300 profile trigrams, so drop
        // the penalty it was never able to avoid.
        if ($uniqueCount < self::MAX_TRIGRAM_DISTANCE) {
            $total -= (self::MAX_TRIGRAM_DISTANCE - $uniqueCount) * self::MAX_TRIGRAM_DISTANCE;
        }

        return max(0, min($total, self::MAX_TOTAL_DISTANCE));
    }

    /**
     * @return array<string, list<string>>
     */
    private static function profiles(Script $script): array
    {
        return self::$profiles[$script->value] ??= require dirname(__DIR__, 2)
            .'/resources/trigrams/'.strtolower($script->value).'.php';
    }
}
