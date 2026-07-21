<?php

declare(strict_types=1);

namespace Avvertix\WhatLang\Tests;

use Avvertix\WhatLang\FilterList;
use Avvertix\WhatLang\Internal\Alphabets;
use Avvertix\WhatLang\Internal\Confidence;
use Avvertix\WhatLang\Internal\ScriptDetection;
use Avvertix\WhatLang\Internal\Text;
use Avvertix\WhatLang\Internal\Trigrams;
use Avvertix\WhatLang\Lang;
use Avvertix\WhatLang\Script;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the detection stages, mirroring the whatlang-rs unit tests.
 */
final class InternalsTest extends TestCase
{
    #[DataProvider('trigramCountProvider')]
    public function test_it_counts_trigrams(string $text, array $expected): void
    {
        $positions = Trigrams::positions(new Text($text));

        $this->assertSame(count($expected), count($positions), 'number of distinct trigrams');

        foreach ($expected as $trigram) {
            $this->assertArrayHasKey($trigram, $positions, "missing trigram '{$trigram}'");
        }
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function trigramCountProvider(): iterable
    {
        yield 'empty' => ['', []];
        yield 'punctuation only' => [',', []];
        yield 'single letter' => ['a', [' a ']];
        yield 'letter between dashes' => ['-a-', [' a ']];
        yield 'one word' => ['yes', [' ye', 'yes', 'es ']];
        yield 'punctuation is a separator' => [
            'Give - IT...',
            [' gi', 'giv', 'ive', 've ', ' it', 'it '],
        ];
    }

    public function test_trigrams_are_ranked_by_frequency(): void
    {
        $positions = Trigrams::positions(new Text('xaaaaabbbb    d'));

        $this->assertSame(0, $positions['aaa']);
        $this->assertSame(1, $positions['bbb']);
    }

    public function test_the_trigram_pass_picks_german(): void
    {
        $outcome = Trigrams::rawDetect(
            Script::Latin,
            new Text('Die Ordnung muss für immer in diesem Codebase bleiben'),
            FilterList::all(),
        );

        $this->assertSame(50, $outcome->trigramsCount);
        $this->assertSame(Lang::Deu, $outcome->scores[0][0]);

        foreach ($outcome->scores as [$lang, $score]) {
            $this->assertGreaterThanOrEqual(0.0, $score, $lang->value);
            $this->assertLessThanOrEqual(1.0, $score, $lang->value);
        }
    }

    public function test_the_alphabet_pass_rewards_ukrainian_specific_characters(): void
    {
        $text = new Text('Дуже цікаво');

        $outcome = Alphabets::rawDetect(Script::Cyrillic, $text->lowercaseCodepoints(), FilterList::all());

        $this->assertSame(10, $outcome->count);

        $raw = self::index($outcome->rawScores);
        $this->assertSame(10, $raw['ukr']);
        $this->assertSame(8, $raw['rus']);

        $normalised = self::index($outcome->scores);
        $this->assertSame(1.0, $normalised['ukr']);
        $this->assertSame(0.8, $normalised['rus']);
    }

    public function test_the_alphabet_pass_falls_back_for_scripts_without_a_table(): void
    {
        foreach ([Script::Arabic, Script::Devanagari, Script::Hebrew] as $script) {
            $outcome = Alphabets::rawDetect($script, [], FilterList::all());

            $this->assertSame(1, $outcome->count);
            $this->assertCount(count($script->langs()), $outcome->scores);

            foreach ($outcome->scores as [, $score]) {
                $this->assertSame(1.0, $score);
            }
        }
    }

    public function test_script_counts_are_kept_per_script(): void
    {
        $info = ScriptDetection::rawDetect(Text::toCodepoints('Привет hello'));

        $this->assertSame(6, $info->count(Script::Cyrillic));
        $this->assertSame(5, $info->count(Script::Latin));
        $this->assertSame(0, $info->count(Script::Greek));
        $this->assertSame(Script::Cyrillic, $info->mainScript());
    }

    public function test_a_script_with_one_language_reports_it(): void
    {
        $this->assertSame(Lang::Kat, ScriptDetection::soleLang(Script::Georgian));
        $this->assertSame(Lang::Jpn, ScriptDetection::soleLang(Script::Katakana));

        $this->assertNull(ScriptDetection::soleLang(Script::Latin));
        $this->assertNull(ScriptDetection::soleLang(Script::Mandarin));
    }

    #[DataProvider('confidenceProvider')]
    public function test_confidence(float $highest, float $second, int $count, float $expected): void
    {
        $this->assertEqualsWithDelta($expected, Confidence::calculate($highest, $second, $count), 1e-12);
    }

    /**
     * @return iterable<string, array{float, float, int, float}>
     */
    public static function confidenceProvider(): iterable
    {
        yield 'no winner' => [0.0, 0.0, 10, 0.0];
        yield 'no runner up' => [0.75, 0.0, 10, 0.75];
        yield 'a tie is worth nothing' => [0.5, 0.5, 10, 0.0];
        yield 'a wide lead is certain' => [1.0, 0.1, 10, 1.0];
        yield 'a narrow lead on a long text' => [0.5, 0.49, 1000, 1.0];
    }

    /**
     * @param  list<array{Lang, int|float}>  $scores
     * @return array<string, int|float>
     */
    private static function index(array $scores): array
    {
        $indexed = [];

        foreach ($scores as [$lang, $score]) {
            $indexed[$lang->value] = $score;
        }

        return $indexed;
    }
}
