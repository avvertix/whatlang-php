<?php

declare(strict_types=1);

namespace Avvertix\WhatLang\Tests;

use Avvertix\WhatLang\Detector;
use Avvertix\WhatLang\FilterList;
use Avvertix\WhatLang\Lang;
use Avvertix\WhatLang\Method;
use Avvertix\WhatLang\Options;
use Avvertix\WhatLang\Script;
use Avvertix\WhatLang\WhatLang;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DetectorTest extends TestCase
{
    private const ESPERANTO = 'Ĉiuj redaktantoj de Esperanta Vikipedio estas volontuloj. Ili partoprenas en la kunlaborema komunumo, sen estro, kie la anoj kunordigas siajn strebojn kadre de temaj projektoj kaj pluraj diskutejoj.';

    public function test_it_detects_language_and_script(): void
    {
        $info = WhatLang::detect(self::ESPERANTO);

        $this->assertNotNull($info);
        $this->assertSame(Lang::Epo, $info->lang);
        $this->assertSame(Script::Latin, $info->script);
        $this->assertSame(1.0, $info->confidence);
        $this->assertTrue($info->isReliable());
    }

    public function test_it_detects_only_the_language(): void
    {
        $this->assertSame(Lang::Eng, WhatLang::detectLang('There is no reason not to learn Esperanto.'));
        $this->assertSame(Lang::Ukr, WhatLang::detectLang('Та нічого, все нормально. А в тебе як?'));
    }

    #[DataProvider('scriptProvider')]
    public function test_it_detects_the_script(string $text, ?Script $expected): void
    {
        $this->assertSame($expected, WhatLang::detectScript($text));
    }

    /**
     * @return iterable<string, array{string, ?Script}>
     */
    public static function scriptProvider(): iterable
    {
        yield 'no script at all' => ['1234567890-,;!', null];
        yield 'latin' => ['Hello!', Script::Latin];
        yield 'cyrillic' => ['Привет всем!', Script::Cyrillic];
        yield 'georgian' => ['ქართული ენა მსოფლიო ', Script::Georgian];
        yield 'mandarin' => ['県見夜上温国阪題富販', Script::Mandarin];
        yield 'arabic' => [' ككل حوالي 1.6، ومعظم الناس ', Script::Arabic];
        yield 'devanagari' => ['हिमालयी वन चिड़िया (जूथेरा सालिमअली)', Script::Devanagari];
        yield 'hebrew' => ['היסטוריה והתפתחות של האלפבית העברי', Script::Hebrew];
        yield 'ethiopic' => ['የኢትዮጵያ ፌዴራላዊ ዴሞክራሲያዊሪፐብሊክ', Script::Ethiopic];
        yield 'mixed, cyrillic wins' => ['Привет! Текст на русском with some English.', Script::Cyrillic];
        yield 'mixed, latin wins' => ['Russian word любовь means love.', Script::Latin];
    }

    public function test_it_returns_null_when_there_is_nothing_to_go_on(): void
    {
        $this->assertNull(WhatLang::detect(''));
        $this->assertNull(WhatLang::detect('1234567890-,;!'));
        $this->assertNull(WhatLang::detect('   '));
    }

    public function test_an_allowlist_narrows_the_candidates(): void
    {
        $detector = Detector::withAllowlist([Lang::Epo, Lang::Ukr]);

        $this->assertSame(Lang::Epo, $detector->detectLang('Mi ne scias!'));
        $this->assertSame(Lang::Eng, WhatLang::detectLang('There is no reason not to learn Esperanto.'));
    }

    public function test_a_denylist_removes_candidates(): void
    {
        $text = 'I am begging pardon';

        $this->assertSame(Lang::Tgl, WhatLang::detectLang($text));

        $detector = Detector::withDenylist([
            Lang::Jav, Lang::Nld, Lang::Uzb, Lang::Swe, Lang::Nob, Lang::Tgl, Lang::Cym,
        ]);

        $this->assertSame(Lang::Eng, $detector->detectLang($text));
    }

    public function test_filtering_out_every_language_of_a_script_yields_null(): void
    {
        $detector = Detector::withDenylist([Lang::Heb, Lang::Yid]);
        $this->assertNull($detector->detect('האקדמיה ללשון העברית'));

        $detector = Detector::withDenylist(Script::Cyrillic->langs());
        $this->assertNull($detector->detect('Мы хотим видеть дальше, чем окна дома напротив'));

        $detector = Detector::withDenylist(Script::Latin->langs());
        $this->assertNull($detector->detect('Mit dem Wissen wächst der Zweifel'));
    }

    public function test_a_filter_decides_between_mandarin_and_japanese(): void
    {
        $text = '水';

        $this->assertSame(Lang::Jpn, Detector::withAllowlist([Lang::Jpn])->detectLang($text));
        $this->assertSame(Lang::Cmn, Detector::withAllowlist([Lang::Cmn])->detectLang($text));
        $this->assertSame(Lang::Cmn, Detector::withDenylist([Lang::Jpn])->detectLang($text));
        $this->assertSame(Lang::Jpn, Detector::withDenylist([Lang::Cmn])->detectLang($text));
    }

    public function test_kana_mixed_into_mandarin_script_means_japanese(): void
    {
        $text = 'この間、川越城や松井田城などの諸城を拡張・改修 河越城の三の丸と八幡郭など拡張、松井田城の大道寺郭構築など';

        $info = WhatLang::detect($text);

        $this->assertNotNull($info);
        $this->assertSame(Script::Mandarin, $info->script);
        $this->assertSame(Lang::Jpn, $info->lang);
        $this->assertTrue($info->isReliable());
    }

    public function test_pure_mandarin_script_stays_mandarin(): void
    {
        $info = WhatLang::detect('民國卅八年，從南京經廣州、香港返回香日德。');

        $this->assertNotNull($info);
        $this->assertSame(Lang::Cmn, $info->lang);
    }

    #[DataProvider('methodProvider')]
    public function test_every_method_detects_a_clear_case(Method $method): void
    {
        $options = new Options(method: $method);

        $this->assertSame(Lang::Rus, WhatLang::detectLang('Мой дядя самых честных правил, когда не в шутку занемог', $options));
    }

    /**
     * @return iterable<string, array{Method}>
     */
    public static function methodProvider(): iterable
    {
        yield 'trigram' => [Method::Trigram];
        yield 'alphabet' => [Method::Alphabet];
        yield 'combined' => [Method::Combined];
    }

    public function test_a_script_with_one_language_is_fully_confident(): void
    {
        foreach ([
            'ქართული ენა' => Lang::Kat,
            'Ελληνικά' => Lang::Ell,
            '한국어 텍스트입니다' => Lang::Kor,
            'ភាសាខ្មែរ' => Lang::Khm,
        ] as $text => $lang) {
            $info = WhatLang::detect($text);

            $this->assertNotNull($info, $text);
            $this->assertSame($lang, $info->lang, $text);
            $this->assertSame(1.0, $info->confidence, $text);
        }
    }

    public function test_options_are_immutable(): void
    {
        $options = new Options;
        $narrowed = $options->withMethod(Method::Trigram);

        $this->assertSame(Method::Combined, $options->method);
        $this->assertSame(Method::Trigram, $narrowed->method);
    }

    public function test_detect_script_ignores_the_language_filter(): void
    {
        $detector = Detector::withAllowlist([Lang::Jpn]);

        $this->assertSame(Script::Cyrillic, $detector->detectScript('Кириллица'));
    }

    public function test_info_can_be_turned_into_an_array(): void
    {
        $info = WhatLang::detect(self::ESPERANTO);

        $this->assertSame([
            'lang' => 'epo',
            'script' => 'Latin',
            'confidence' => 1.0,
            'reliable' => true,
        ], $info?->toArray());
    }

    public function test_filter_list_membership(): void
    {
        $all = FilterList::all();
        $this->assertTrue($all->isAllowed(Lang::Epo));

        $allow = FilterList::allow([Lang::Rus, Lang::Ukr]);
        $this->assertTrue($allow->isAllowed(Lang::Rus));
        $this->assertFalse($allow->isAllowed(Lang::Epo));

        $deny = FilterList::deny([Lang::Rus, Lang::Ukr]);
        $this->assertFalse($deny->isAllowed(Lang::Rus));
        $this->assertTrue($deny->isAllowed(Lang::Epo));
    }
}
