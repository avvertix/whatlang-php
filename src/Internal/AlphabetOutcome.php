<?php

declare(strict_types=1);

namespace Avvertix\WhatLang\Internal;

use Avvertix\WhatLang\Lang;

/**
 * Result of the alphabet pass.
 *
 * @internal
 */
final class AlphabetOutcome
{
    /**
     * @param  int  $count  number of non-stop characters seen
     * @param  list<array{Lang, int}>  $rawScores
     * @param  list<array{Lang, float}>  $scores  normalised to 0.0..1.0, best first
     */
    public function __construct(
        public readonly int $count,
        public readonly array $rawScores,
        public readonly array $scores,
    ) {}
}
