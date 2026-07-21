<?php

declare(strict_types=1);

namespace Avvertix\WhatLang;

/**
 * A language, identified by its ISO 639-3 code.
 *
 * Ported from whatlang-rs (src/lang.rs).
 */
enum Lang: string
{
    /** Esperanto (Esperanto) */
    case Epo = 'epo';

    /** English (English) */
    case Eng = 'eng';

    /** Русский (Russian) */
    case Rus = 'rus';

    /** 普通话 (Mandarin) */
    case Cmn = 'cmn';

    /** Español (Spanish) */
    case Spa = 'spa';

    /** Português (Portuguese) */
    case Por = 'por';

    /** Italiano (Italian) */
    case Ita = 'ita';

    /** বাংলা (Bengali) */
    case Ben = 'ben';

    /** Français (French) */
    case Fra = 'fra';

    /** Deutsch (German) */
    case Deu = 'deu';

    /** Українська (Ukrainian) */
    case Ukr = 'ukr';

    /** ქართული (Georgian) */
    case Kat = 'kat';

    /** العربية (Arabic) */
    case Ara = 'ara';

    /** हिन्दी (Hindi) */
    case Hin = 'hin';

    /** 日本語 (Japanese) */
    case Jpn = 'jpn';

    /** עברית (Hebrew) */
    case Heb = 'heb';

    /** ייִדיש (Yiddish) */
    case Yid = 'yid';

    /** Polski (Polish) */
    case Pol = 'pol';

    /** አማርኛ (Amharic) */
    case Amh = 'amh';

    /** Basa Jawa (Javanese) */
    case Jav = 'jav';

    /** 한국어 (Korean) */
    case Kor = 'kor';

    /** Bokmål (Bokmal) */
    case Nob = 'nob';

    /** Dansk (Danish) */
    case Dan = 'dan';

    /** Svenska (Swedish) */
    case Swe = 'swe';

    /** Suomi (Finnish) */
    case Fin = 'fin';

    /** Türkçe (Turkish) */
    case Tur = 'tur';

    /** Nederlands (Dutch) */
    case Nld = 'nld';

    /** Magyar (Hungarian) */
    case Hun = 'hun';

    /** Čeština (Czech) */
    case Ces = 'ces';

    /** Ελληνικά (Greek) */
    case Ell = 'ell';

    /** Български (Bulgarian) */
    case Bul = 'bul';

    /** Беларуская (Belarusian) */
    case Bel = 'bel';

    /** मराठी (Marathi) */
    case Mar = 'mar';

    /** ಕನ್ನಡ (Kannada) */
    case Kan = 'kan';

    /** Română (Romanian) */
    case Ron = 'ron';

    /** Slovenščina (Slovene) */
    case Slv = 'slv';

    /** Hrvatski (Croatian) */
    case Hrv = 'hrv';

    /** Српски (Serbian) */
    case Srp = 'srp';

    /** Македонски (Macedonian) */
    case Mkd = 'mkd';

    /** Lietuvių (Lithuanian) */
    case Lit = 'lit';

    /** Latviešu (Latvian) */
    case Lav = 'lav';

    /** Eesti (Estonian) */
    case Est = 'est';

    /** தமிழ் (Tamil) */
    case Tam = 'tam';

    /** Tiếng Việt (Vietnamese) */
    case Vie = 'vie';

    /** اُردُو (Urdu) */
    case Urd = 'urd';

    /** ภาษาไทย (Thai) */
    case Tha = 'tha';

    /** ગુજરાતી (Gujarati) */
    case Guj = 'guj';

    /** Oʻzbekcha (Uzbek) */
    case Uzb = 'uzb';

    /** ਪੰਜਾਬੀ (Punjabi) */
    case Pan = 'pan';

    /** Azərbaycanca (Azerbaijani) */
    case Aze = 'aze';

    /** Bahasa Indonesia (Indonesian) */
    case Ind = 'ind';

    /** తెలుగు (Telugu) */
    case Tel = 'tel';

    /** فارسی (Persian) */
    case Pes = 'pes';

    /** മലയാളം (Malayalam) */
    case Mal = 'mal';

    /** ଓଡ଼ିଆ (Oriya) */
    case Ori = 'ori';

    /** မြန်မာစာ (Burmese) */
    case Mya = 'mya';

    /** नेपाली (Nepali) */
    case Nep = 'nep';

    /** සිංහල (Sinhalese) */
    case Sin = 'sin';

    /** ភាសាខ្មែរ (Khmer) */
    case Khm = 'khm';

    /** Türkmençe (Turkmen) */
    case Tuk = 'tuk';

    /** Akan (Akan) */
    case Aka = 'aka';

    /** IsiZulu (Zulu) */
    case Zul = 'zul';

    /** ChiShona (Shona) */
    case Sna = 'sna';

    /** Afrikaans (Afrikaans) */
    case Afr = 'afr';

    /** Lingua Latina (Latin) */
    case Lat = 'lat';

    /** Slovenčina (Slovak) */
    case Slk = 'slk';

    /** Català (Catalan) */
    case Cat = 'cat';

    /** Tagalog (Tagalog) */
    case Tgl = 'tgl';

    /** Հայերեն (Armenian) */
    case Hye = 'hye';

    /** Cymraeg (Welsh) */
    case Cym = 'cym';

    /**
     * Every language the detector knows about.
     *
     * @return list<self>
     */
    public static function all(): array
    {
        return self::cases();
    }

    /**
     * The ISO 639-3 code, e.g. "deu".
     */
    public function code(): string
    {
        return $this->value;
    }

    /**
     * The language name written in the language itself, e.g. "Deutsch".
     */
    public function nativeName(): string
    {
        return match ($this) {
            self::Epo => 'Esperanto',
            self::Eng => 'English',
            self::Rus => 'Русский',
            self::Cmn => '普通话',
            self::Spa => 'Español',
            self::Por => 'Português',
            self::Ita => 'Italiano',
            self::Ben => 'বাংলা',
            self::Fra => 'Français',
            self::Deu => 'Deutsch',
            self::Ukr => 'Українська',
            self::Kat => 'ქართული',
            self::Ara => 'العربية',
            self::Hin => 'हिन्दी',
            self::Jpn => '日本語',
            self::Heb => 'עברית',
            self::Yid => 'ייִדיש',
            self::Pol => 'Polski',
            self::Amh => 'አማርኛ',
            self::Jav => 'Basa Jawa',
            self::Kor => '한국어',
            self::Nob => 'Bokmål',
            self::Dan => 'Dansk',
            self::Swe => 'Svenska',
            self::Fin => 'Suomi',
            self::Tur => 'Türkçe',
            self::Nld => 'Nederlands',
            self::Hun => 'Magyar',
            self::Ces => 'Čeština',
            self::Ell => 'Ελληνικά',
            self::Bul => 'Български',
            self::Bel => 'Беларуская',
            self::Mar => 'मराठी',
            self::Kan => 'ಕನ್ನಡ',
            self::Ron => 'Română',
            self::Slv => 'Slovenščina',
            self::Hrv => 'Hrvatski',
            self::Srp => 'Српски',
            self::Mkd => 'Македонски',
            self::Lit => 'Lietuvių',
            self::Lav => 'Latviešu',
            self::Est => 'Eesti',
            self::Tam => 'தமிழ்',
            self::Vie => 'Tiếng Việt',
            self::Urd => 'اُردُو',
            self::Tha => 'ภาษาไทย',
            self::Guj => 'ગુજરાતી',
            self::Uzb => 'Oʻzbekcha',
            self::Pan => 'ਪੰਜਾਬੀ',
            self::Aze => 'Azərbaycanca',
            self::Ind => 'Bahasa Indonesia',
            self::Tel => 'తెలుగు',
            self::Pes => 'فارسی',
            self::Mal => 'മലയാളം',
            self::Ori => 'ଓଡ଼ିଆ',
            self::Mya => 'မြန်မာစာ',
            self::Nep => 'नेपाली',
            self::Sin => 'සිංහල',
            self::Khm => 'ភាសាខ្មែរ',
            self::Tuk => 'Türkmençe',
            self::Aka => 'Akan',
            self::Zul => 'IsiZulu',
            self::Sna => 'ChiShona',
            self::Afr => 'Afrikaans',
            self::Lat => 'Lingua Latina',
            self::Slk => 'Slovenčina',
            self::Cat => 'Català',
            self::Tgl => 'Tagalog',
            self::Hye => 'Հայերեն',
            self::Cym => 'Cymraeg',
        };
    }

    /**
     * The English name of the language, e.g. "German".
     */
    public function englishName(): string
    {
        return match ($this) {
            self::Epo => 'Esperanto',
            self::Eng => 'English',
            self::Rus => 'Russian',
            self::Cmn => 'Mandarin',
            self::Spa => 'Spanish',
            self::Por => 'Portuguese',
            self::Ita => 'Italian',
            self::Ben => 'Bengali',
            self::Fra => 'French',
            self::Deu => 'German',
            self::Ukr => 'Ukrainian',
            self::Kat => 'Georgian',
            self::Ara => 'Arabic',
            self::Hin => 'Hindi',
            self::Jpn => 'Japanese',
            self::Heb => 'Hebrew',
            self::Yid => 'Yiddish',
            self::Pol => 'Polish',
            self::Amh => 'Amharic',
            self::Jav => 'Javanese',
            self::Kor => 'Korean',
            self::Nob => 'Bokmal',
            self::Dan => 'Danish',
            self::Swe => 'Swedish',
            self::Fin => 'Finnish',
            self::Tur => 'Turkish',
            self::Nld => 'Dutch',
            self::Hun => 'Hungarian',
            self::Ces => 'Czech',
            self::Ell => 'Greek',
            self::Bul => 'Bulgarian',
            self::Bel => 'Belarusian',
            self::Mar => 'Marathi',
            self::Kan => 'Kannada',
            self::Ron => 'Romanian',
            self::Slv => 'Slovene',
            self::Hrv => 'Croatian',
            self::Srp => 'Serbian',
            self::Mkd => 'Macedonian',
            self::Lit => 'Lithuanian',
            self::Lav => 'Latvian',
            self::Est => 'Estonian',
            self::Tam => 'Tamil',
            self::Vie => 'Vietnamese',
            self::Urd => 'Urdu',
            self::Tha => 'Thai',
            self::Guj => 'Gujarati',
            self::Uzb => 'Uzbek',
            self::Pan => 'Punjabi',
            self::Aze => 'Azerbaijani',
            self::Ind => 'Indonesian',
            self::Tel => 'Telugu',
            self::Pes => 'Persian',
            self::Mal => 'Malayalam',
            self::Ori => 'Oriya',
            self::Mya => 'Burmese',
            self::Nep => 'Nepali',
            self::Sin => 'Sinhalese',
            self::Khm => 'Khmer',
            self::Tuk => 'Turkmen',
            self::Aka => 'Akan',
            self::Zul => 'Zulu',
            self::Sna => 'Shona',
            self::Afr => 'Afrikaans',
            self::Lat => 'Latin',
            self::Slk => 'Slovak',
            self::Cat => 'Catalan',
            self::Tgl => 'Tagalog',
            self::Hye => 'Armenian',
            self::Cym => 'Welsh',
        };
    }

    /**
     * The scripts this language can be written in.
     *
     * @return list<Script>
     */
    public function scripts(): array
    {
        return array_values(array_filter(
            Script::cases(),
            fn (Script $script): bool => in_array($this, $script->langs(), true),
        ));
    }
}
