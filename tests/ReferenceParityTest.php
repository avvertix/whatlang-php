<?php

declare(strict_types=1);

namespace Avvertix\WhatLang\Tests;

use Avvertix\WhatLang\FilterList;
use Avvertix\WhatLang\Internal\Combined;
use Avvertix\WhatLang\Internal\Text;
use Avvertix\WhatLang\Script;
use Avvertix\WhatLang\WhatLang;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Replays a corpus captured from whatlang-rs and asserts this port agrees.
 *
 * Regenerate the fixture with `php tools/generate-fixture.php` (needs cargo
 * and a checkout of whatlang-rs).
 */
final class ReferenceParityTest extends TestCase
{
    /**
     * @return iterable<string, array{array{text: string, lang: ?string, script: ?string, confidence: float}}>
     */
    public static function referenceProvider(): iterable
    {
        $records = json_decode(
            file_get_contents(__DIR__.'/fixtures/reference.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        foreach ($records as $index => $record) {
            // JSON has no way to keep 0.0 a float, so normalise on the way in.
            $record['confidence'] = (float) $record['confidence'];

            yield $index.': '.mb_substr($record['text'], 0, 40) => [$record];
        }
    }

    /**
     * @param  array{text: string, lang: ?string, script: ?string, confidence: float}  $record
     */
    #[DataProvider('referenceProvider')]
    public function test_it_matches_the_rust_implementation(array $record): void
    {
        $info = WhatLang::detect($record['text']);

        $this->assertSame($record['script'], $info?->script->value, 'script');

        $this->assertEqualsWithDelta(
            $record['confidence'],
            $info?->confidence ?? 0.0,
            1e-9,
            'confidence',
        );

        if ($record['confidence'] === 0.0 && $info !== null) {
            // A zero confidence means the top two languages scored identically,
            // so which one wins is arbitrary — whatlang-rs sorts with an
            // unstable sort and we sort stably. Assert the tie instead.
            $this->assertContains(
                $record['lang'],
                self::languagesTiedAtTheTop($record['text'], $info->script),
                'expected the reference language to share the top score',
            );

            return;
        }

        $this->assertSame($record['lang'], $info?->lang->value, 'lang');
    }

    /**
     * @return list<string>
     */
    private static function languagesTiedAtTheTop(string $text, Script $script): array
    {
        $outcome = Combined::rawDetect($script, new Text($text), FilterList::all());
        $best = $outcome['scores'][0][1];

        $tied = [];

        foreach ($outcome['scores'] as [$lang, $score]) {
            if (abs($score - $best) < 1e-15) {
                $tied[] = $lang->value;
            }
        }

        return $tied;
    }
}
