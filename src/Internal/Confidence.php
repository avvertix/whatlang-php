<?php

declare(strict_types=1);

namespace Avvertix\WhatLang\Internal;

/**
 * Ported from whatlang-rs (src/core/confidence.rs).
 *
 * @internal
 */
final class Confidence
{
    /**
     * How sure we are that the top-scoring language is the right one.
     *
     * The further the winner is ahead of the runner-up, the more confident we
     * are — but a short text needs a bigger lead to earn the same confidence.
     *
     * @param  float  $highestScore  0.0..1.0
     * @param  float  $secondScore  0.0..1.0
     * @param  int  $count  number of characters or trigrams the scores came from
     */
    public static function calculate(float $highestScore, float $secondScore, int $count): float
    {
        if ($highestScore === 0.0) {
            return 0.0;
        }

        if ($secondScore === 0.0) {
            return $highestScore;
        }

        if ($count === 0) {
            return 0.0;
        }

        // Hyperbola: anything above the curve is fully confident, anything
        // below scales down proportionally. The constants come from whatlang-rs
        // and were tuned experimentally.
        $confidentRate = (3.0 / $count) + 0.015;
        $rate = ($highestScore - $secondScore) / $secondScore;

        return $rate > $confidentRate ? 1.0 : $rate / $confidentRate;
    }
}
