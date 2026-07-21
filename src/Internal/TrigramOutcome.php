<?php

declare(strict_types=1);

namespace Avvertix\WhatLang\Internal;

use Avvertix\WhatLang\Lang;

/**
 * Result of the trigram pass.
 *
 * @internal
 */
final class TrigramOutcome
{
    /**
     * @param  int  $trigramsCount  distinct trigrams found in the text
     * @param  list<array{Lang, int}>  $distances  closest first
     * @param  list<array{Lang, float}>  $scores  normalised to 0.0..1.0, best first
     */
    public function __construct(
        public readonly int $trigramsCount,
        public readonly array $distances,
        public readonly array $scores,
    ) {}
}
