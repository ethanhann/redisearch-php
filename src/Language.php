<?php

namespace Ehann\RediSearch;

class Language
{
    public const ARABIC = 'arabic';
    public const BASQUE = 'basque';
    public const CATALAN = 'catalan';
    public const CHINESE = 'chinese';
    public const DANISH = 'danish';
    public const DUTCH = 'dutch';
    public const ENGLISH = 'english';
    public const FINNISH = 'finnish';
    public const FRENCH = 'french';
    public const GERMAN = 'german';
    public const GREEK = 'greek';
    public const HUNGARIAN = 'hungarian';
    public const INDONESIAN = 'indonesian';
    public const IRISH = 'irish';
    public const ITALIAN = 'italian';
    public const LITHUANIAN = 'lithuanian';
    public const NEPALI = 'nepali';
    public const NORWEGIAN = 'norwegian';
    public const PORTUGUESE = 'portuguese';
    public const ROMANIAN = 'romanian';
    public const RUSSIAN = 'russian';
    public const SPANISH = 'spanish';
    public const SWEDISH = 'swedish';
    public const TAMIL = 'tamil';
    public const TURKISH = 'turkish';

    private static array $supported = [
        self::ARABIC,
        self::BASQUE,
        self::CATALAN,
        self::CHINESE,
        self::DANISH,
        self::DUTCH,
        self::ENGLISH,
        self::FINNISH,
        self::FRENCH,
        self::GERMAN,
        self::GREEK,
        self::HUNGARIAN,
        self::INDONESIAN,
        self::IRISH,
        self::ITALIAN,
        self::LITHUANIAN,
        self::NEPALI,
        self::NORWEGIAN,
        self::PORTUGUESE,
        self::ROMANIAN,
        self::RUSSIAN,
        self::SPANISH,
        self::SWEDISH,
        self::TAMIL,
        self::TURKISH,
    ];

    public static function isSupported(string $language): bool
    {
        return in_array(strtolower($language), self::$supported, true);
    }

    public static function getSupported(): array
    {
        return self::$supported;
    }
}
