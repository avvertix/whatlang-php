<?php

declare(strict_types=1);

namespace Avvertix\WhatLang\Internal;

use Avvertix\WhatLang\Lang;
use Avvertix\WhatLang\Script;

/**
 * Counts how many characters of the text belong to each script.
 *
 * Ported from whatlang-rs (src/scripts/detect.rs, src/scripts/grouping.rs).
 *
 * @internal
 */
final class ScriptDetection
{
    /**
     * Scripts written by more than one language, which therefore need a second
     * pass (alphabet/trigram) to pin down the language.
     */
    private const MULTI_LANG_SCRIPTS = ['Latin', 'Cyrillic', 'Arabic', 'Devanagari', 'Hebrew'];

    /**
     * @param  array<string, int>  $counts  script name => number of characters
     * @param  list<string>  $order  script names, most characters first
     */
    private function __construct(
        private readonly array $counts,
        private readonly array $order,
    ) {}

    /**
     * Counts characters per script.
     *
     * @param  list<int>  $codepoints
     */
    public static function rawDetect(array $codepoints): self
    {
        // A local copy of the range table, kept in the same shape whatlang-rs
        // uses: a list we reorder as we go, so the scripts that keep matching
        // drift to the front and get tested first.
        $scripts = [];

        foreach (Chars::detectionOrder() as $script) {
            $scripts[] = [$script->value, Chars::ranges($script), 0];
        }

        $total = count($scripts);

        foreach ($codepoints as $codepoint) {
            if (Chars::isStopChar($codepoint)) {
                continue;
            }

            for ($i = 0; $i < $total; $i++) {
                $found = false;

                foreach ($scripts[$i][1] as [$start, $end]) {
                    if ($codepoint >= $start && $codepoint <= $end) {
                        $found = true;
                        break;
                    }
                }

                if (! $found) {
                    continue;
                }

                $scripts[$i][2]++;

                // Move a matching script one step towards the front, so texts
                // dominated by one or two scripts get faster over time.
                if ($i > 0) {
                    [$scripts[$i - 1], $scripts[$i]] = [$scripts[$i], $scripts[$i - 1]];
                }

                break;
            }
        }

        $counts = [];
        foreach ($scripts as [$name, , $count]) {
            $counts[$name] = $count;
        }

        // Stable sort, so scripts with equal counts keep the order above.
        $order = array_keys($counts);
        usort($order, static fn (string $a, string $b): int => $counts[$b] <=> $counts[$a]);

        return new self($counts, $order);
    }

    /**
     * The script with the most characters, or null when the text carries no
     * script information at all.
     */
    public function mainScript(): ?Script
    {
        $top = $this->order[0] ?? null;

        if ($top === null || $this->counts[$top] === 0) {
            return null;
        }

        return Script::from($top);
    }

    public function count(Script $script): int
    {
        return $this->counts[$script->value];
    }

    /**
     * Whether the script is written by several languages, and so needs a
     * language detection pass of its own.
     */
    public static function isMultiLang(Script $script): bool
    {
        return in_array($script->value, self::MULTI_LANG_SCRIPTS, true);
    }

    /**
     * The single language a script implies, when there is only one.
     *
     * Returns null for multi-language scripts and for Mandarin, which is
     * special-cased because Mandarin script can also be Japanese.
     */
    public static function soleLang(Script $script): ?Lang
    {
        if (self::isMultiLang($script) || $script === Script::Mandarin) {
            return null;
        }

        return $script->langs()[0];
    }
}
