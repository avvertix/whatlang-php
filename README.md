# whatlang-php

[![Latest Version on Packagist](https://img.shields.io/packagist/v/avvertix/whatlang-php.svg?style=flat-square)](https://packagist.org/packages/avvertix/whatlang-php)
[![Tests](https://github.com/avvertix/whatlang-php/actions/workflows/run-tests.yml/badge.svg)](https://github.com/avvertix/whatlang-php/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/avvertix/whatlang-php.svg?style=flat-square)](https://packagist.org/packages/avvertix/whatlang-php)

Detects the natural language and writing system of a text. 70 languages, 25 scripts, no
extensions beyond `mbstring`, no network calls, no model files to download.

This is a port of [whatlang-rs](https://github.com/greyblake/whatlang-rs) by Sergey Potapov.
It follows the Rust implementation closely enough that the two agree on 1416 of 1426 samples
in the cross-check corpus, and the remaining ten are exact scoring ties where either answer
is equally correct — see [Parity with whatlang-rs](#parity-with-whatlang-rs).

## Installation

```bash
composer require avvertix/whatlang-php
```

Requires PHP 8.3 and `ext-mbstring`.

## Usage

```php
use Avvertix\WhatLang\WhatLang;

$info = WhatLang::detect('Ĉu vi ne volas eklerni Esperanton? Bonvolu! Estas unu de la plej bonaj aferoj!');

$info->lang;                // Lang::Epo
$info->lang->value;         // 'epo'  (ISO 639-3)
$info->lang->nativeName();  // 'Esperanto'
$info->lang->englishName(); // 'Esperanto'
$info->script;              // Script::Latin
$info->confidence;          // 1.0
$info->isReliable();        // true
```

`detect()` returns `null` when the text carries no signal — empty, whitespace, digits and
punctuation only, or every candidate language filtered out.

```php
WhatLang::detect('1234567890-,;!'); // null
```

### Just the language, or just the script

```php
WhatLang::detectLang('There is no reason not to learn Esperanto.'); // Lang::Eng
WhatLang::detectScript('Благодаря Эсперанто');                      // Script::Cyrillic
```

`detectScript()` is much cheaper than a full detection: it only counts characters per
Unicode block and skips language scoring entirely.

### Narrowing the candidates

If you know the text can only be one of a handful of languages, say so. Accuracy on short
texts improves considerably.

```php
use Avvertix\WhatLang\Detector;
use Avvertix\WhatLang\Lang;

$detector = Detector::withAllowlist([Lang::Eng, Lang::Rus]);
$detector->detectLang('That is not Russian'); // Lang::Eng

$detector = Detector::withDenylist([Lang::Eng, Lang::Ita]);
$detector->detectLang('Jen la trinkejo fermitis, ni iras tra mallumo kaj pluvo.'); // Lang::Epo
```

### Confidence

`confidence` runs from 0.0 to 1.0 and reflects how far ahead the winning language is of the
runner-up, scaled by how much text there was to go on — a short text needs a bigger lead to
earn the same confidence. `isReliable()` is true above 0.9.

```php
$info = WhatLang::detect($text);

if (! $info?->isReliable()) {
    // too little to go on — fall back to a default, or ask
}
```

### Detection method

```php
use Avvertix\WhatLang\Method;
use Avvertix\WhatLang\Options;

WhatLang::detect($text, new Options(method: Method::Trigram));
```

| Method | What it does | When it helps |
| --- | --- | --- |
| `Combined` (default) | Blends the two below, weighted by text length | Almost always |
| `Trigram` | Compares three-character frequency profiles | Longer texts |
| `Alphabet` | Scores languages by the characters they use | Very short texts; fastest |

The combined method leans on the alphabet score for short input and shifts its weight to
trigrams as the text grows past roughly a hundred characters.

### Supported languages and scripts

```php
use Avvertix\WhatLang\Lang;
use Avvertix\WhatLang\Script;

Lang::all();               // 70 languages
Script::all();             // 25 scripts
Script::Cyrillic->langs(); // [Lang::Rus, Lang::Ukr, Lang::Srp, Lang::Bel, Lang::Bul, Lang::Mkd]
Lang::Jpn->scripts();      // [Script::Hiragana, Script::Katakana]
```

## How it works

1. **Script detection.** Every character is matched against the Unicode ranges of 25
   scripts; the script with the most characters wins. Punctuation and digits are ignored.
2. **Script to language.** Most scripts imply a single language — Georgian script means
   Georgian — and detection stops there with full confidence. Five scripts (Latin, Cyrillic,
   Arabic, Devanagari, Hebrew) are shared, and go on to step 3.
3. **Language scoring.** The alphabet pass scores each candidate by the characters it uses.
   The trigram pass ranks the text's three-character sequences by frequency and compares
   that ranking against each language's stored profile of its 300 most common trigrams.

Mandarin is special-cased: its script is also used to write Japanese, so the two are told
apart by how much Kana is mixed in.

## Parity with whatlang-rs

`tests/fixtures/reference.json` holds 783 samples with the language, script and confidence
whatlang-rs produces. Test `ReferenceParityTest` ensure parity between the PHP implementation
and the Rust implementation.

To regenerate the fixture you need `cargo` and a checkout of whatlang-rs beside this one:

```bash
php tools/generate-fixture.php [path-to-whatlang-rs]
```

The trigram profiles and alphabet tables under `resources/` are likewise generated from the
Rust sources rather than transcribed by hand:

```bash
php tools/generate-data.php [path-to-whatlang-rs]
```

## Testing

```bash
composer test
```

## Credits

- [Alessio Vertemati](https://github.com/avvertix)
- [All Contributors](../../contributors)

## Derivation

whatlang-php is a derivative work of [whatlang-rs](https://github.com/greyblake/whatlang-rs)
(Rust, MIT) by Sergey Potapov. The detection algorithm, the trigram profiles and the alphabet
tables all originate there.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
