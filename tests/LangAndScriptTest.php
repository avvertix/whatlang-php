<?php

declare(strict_types=1);

namespace Avvertix\WhatLang\Tests;

use Avvertix\WhatLang\Internal\Chars;
use Avvertix\WhatLang\Internal\Text;
use Avvertix\WhatLang\Lang;
use Avvertix\WhatLang\Method;
use Avvertix\WhatLang\Script;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LangAndScriptTest extends TestCase
{
    public function test_it_knows_seventy_languages_and_twenty_five_scripts(): void
    {
        $this->assertCount(70, Lang::all());
        $this->assertCount(25, Script::all());
    }

    public function test_languages_carry_their_names(): void
    {
        $this->assertSame('rus', Lang::Rus->code());
        $this->assertSame('Русский', Lang::Rus->nativeName());
        $this->assertSame('Russian', Lang::Rus->englishName());

        $this->assertSame('Deutsch', Lang::Deu->nativeName());
        $this->assertSame('German', Lang::Deu->englishName());
    }

    public function test_languages_are_looked_up_by_code(): void
    {
        $this->assertSame(Lang::Epo, Lang::from('epo'));
        $this->assertNull(Lang::tryFrom('xxx'));
    }

    public function test_every_language_belongs_to_at_least_one_script(): void
    {
        $mapped = [];

        foreach (Script::all() as $script) {
            foreach ($script->langs() as $lang) {
                $mapped[$lang->value] = true;
            }
        }

        foreach (Lang::all() as $lang) {
            $this->assertArrayHasKey($lang->value, $mapped, $lang->value.' belongs to no script');
        }
    }

    public function test_a_language_reports_its_scripts(): void
    {
        $this->assertSame([Script::Cyrillic], Lang::Rus->scripts());
        $this->assertSame([Script::Hiragana, Script::Katakana], Lang::Jpn->scripts());
    }

    public function test_scripts_are_parsed_by_name(): void
    {
        foreach (Script::all() as $script) {
            $this->assertSame($script, Script::fromName($script->scriptName()));
            $this->assertSame($script, Script::fromName(strtolower($script->scriptName())));
            $this->assertSame($script, Script::fromName(' '.strtoupper($script->scriptName()).' '));
        }

        $this->assertNull(Script::fromName('foobar'));
    }

    public function test_methods_are_parsed_by_name(): void
    {
        $this->assertSame(Method::Trigram, Method::fromName('trigram'));
        $this->assertSame(Method::Alphabet, Method::fromName('ALPHABET'));
        $this->assertSame(Method::Combined, Method::fromName(' Combined '));
        $this->assertNull(Method::fromName('foobar'));
    }

    #[DataProvider('scriptCharProvider')]
    public function test_characters_are_assigned_to_scripts(Script $script, string $char, bool $expected): void
    {
        $codepoint = Text::toCodepoints($char)[0];

        $this->assertSame($expected, $script->matches($codepoint));
    }

    /**
     * @return iterable<string, array{Script, string, bool}>
     */
    public static function scriptCharProvider(): iterable
    {
        yield 'z is latin' => [Script::Latin, 'z', true];
        yield 'č is latin' => [Script::Latin, 'č', true];
        yield 'Ĵ is latin' => [Script::Latin, 'Ĵ', true];
        yield 'ж is not latin' => [Script::Latin, 'ж', false];
        yield 'а is cyrillic' => [Script::Cyrillic, 'а', true];
        yield 'Ґ is cyrillic' => [Script::Cyrillic, 'Ґ', true];
        yield 'L is not cyrillic' => [Script::Cyrillic, 'L', false];
        yield 'ፚ is ethiopic' => [Script::Ethiopic, 'ፚ', true];
        yield 'რ is georgian' => [Script::Georgian, 'რ', true];
        yield 'ই is bengali' => [Script::Bengali, 'ই', true];
        yield 'カ is katakana' => [Script::Katakana, 'カ', true];
        yield 'ひ is hiragana' => [Script::Hiragana, 'ひ', true];
        yield 'φ is greek' => [Script::Greek, 'φ', true];
        yield 'ф is not greek' => [Script::Greek, 'ф', false];
        yield 'ա is armenian' => [Script::Armenian, 'ա', true];
        yield 'և is armenian' => [Script::Armenian, 'և', true];
        yield 'რ is not armenian' => [Script::Armenian, 'რ', false];
        yield 'ก is thai' => [Script::Thai, 'ก', true];
        yield 'ଐ is oriya' => [Script::Oriya, 'ଐ', true];
    }

    #[DataProvider('stopCharProvider')]
    public function test_stop_characters_are_recognised(string $char, bool $expected): void
    {
        $this->assertSame($expected, Chars::isStopChar(Text::toCodepoints($char)[0]));
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function stopCharProvider(): iterable
    {
        foreach ([' ', ',', '-', '9', '0', '.', '@', '[', ']', '^', '`', '|', '{', '}', '~'] as $char) {
            yield 'stop '.$char => [$char, true];
        }

        foreach (['a', 'z', 'A', 'Z', 'я', 'А', 'ß'] as $char) {
            yield 'not stop '.$char => [$char, false];
        }
    }

    public function test_text_splits_into_codepoints(): void
    {
        $this->assertSame([], Text::toCodepoints(''));
        $this->assertSame([0x48, 0x69], Text::toCodepoints('Hi'));
        $this->assertSame([0x416], Text::toCodepoints('Ж'));
        $this->assertSame([0x1F389], Text::toCodepoints('🎉'));
    }

    public function test_text_lowercases_lazily(): void
    {
        $text = new Text('Hello THERE');

        $this->assertSame('hello there', $text->lowercase());
        $this->assertSame($text->lowercaseCodepoints(), Text::toCodepoints('hello there'));
    }
}
