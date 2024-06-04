<?php

namespace App\Constants;

class UGProviderConfigConstants
{
    const TABLE_NAME = 'ug_provider_configs';

    // odds Expression
    const ODDS_EXPRESSION_DECIMAL = 'decimal';
    const ODDS_EXPRESSION_MALAY = 'malay';
    const ODDS_EXPRESSION_HONGKONG = 'hongkong';
    const ODDS_EXPRESSION_INDO = 'indo';

    public static function oddsExpressions()
    {
        return [
            static::ODDS_EXPRESSION_DECIMAL => 'Decimal Odds',
            static::ODDS_EXPRESSION_MALAY => 'Malay Odds',
            static::ODDS_EXPRESSION_HONGKONG => 'Hong Kong Odds',
            static::ODDS_EXPRESSION_INDO => 'Indonesia Odds',

        ];
    }

    public static function oddsExpression($oddsExpressionValue)
    {
        return static::oddsExpressions()[$oddsExpressionValue] ?? null;
    }

    // themes
    const THEME_CLASSIC_BLUE = 'style';
    const THEME_EARTH_YELLOW = 'style2';
    const THEME_LIGHT_GREEN = 'style3';
    const THEME_DARK_RED = 'style4';
    const THEME_COOL_BLUE = 'style5';
    const THEME_BRONZE_BLACK = 'style6';

    public static function themes()
    {
        return [
            static::THEME_CLASSIC_BLUE => 'Classic Blue',
            static::THEME_EARTH_YELLOW => 'Earth Yellow',
            static::THEME_LIGHT_GREEN => 'Light Green',
            static::THEME_DARK_RED => 'Dark Red',
            static::THEME_COOL_BLUE => 'Cool blue',
            static::THEME_BRONZE_BLACK => 'Bronze Black',
        ];
    }

    public static function theme($themeValue)
    {
        return static::themes()[$themeValue] ?? null;
    }

    // templates
    const TEMPLATE_STANDARD = 'standard';
    const TEMPLATE_CLASSIC = 'classic';
    const TEMPLATE_TRADITIONAL = 'traditional';

    public static function templates()
    {
        return [
            static::TEMPLATE_STANDARD => 'Standard Template',
            static::TEMPLATE_CLASSIC => 'Classic Template',
            static::TEMPLATE_TRADITIONAL => 'Traditional Template',
        ];
    }

    public static function template($templateValue)
    {
        return static::templates()[$templateValue] ?? null;
    }

    // game modes
    const GAME_MODE_ALL_SPORTS = 0;
    const GAME_MODE_FANTASY_PAGE = 1;

    public static function gameModes()
    {
        return [
            static::GAME_MODE_ALL_SPORTS => 'All Sports',
            static::GAME_MODE_FANTASY_PAGE => 'Fantasy page',
        ];
    }

    public static function gameMode($gameModeValue)
    {
        return static::gameModes()[$gameModeValue] ?? null;
    }

    // favorite sport
    const FAVORITE_SPORT_SOCCER = 1;
    const FAVORITE_SPORT_BASKETBALL = 2;
    const FAVORITE_SPORT_TENNIS = 7;
    const FAVORITE_SPORT_CRICKET = 11;

    public static function favoriteSports()
    {
        return [
            static::FAVORITE_SPORT_SOCCER => 'SOCCER',
            static::FAVORITE_SPORT_BASKETBALL => 'BASKETBALL',
            static::FAVORITE_SPORT_TENNIS => 'TENNIS',
            static::FAVORITE_SPORT_CRICKET => 'CRICKET',
        ];
    }

    public static function favoriteSport($favoriteSportValue)
    {
        return static::favoriteSports()[$favoriteSportValue] ?? null;
    }

    // default market 
    const DEFAULT_MARKET_FAST = 'fast';
    const DEFAULT_MARKET_LIVE = 'live';
    const DEFAULT_MARKET_TODAY = 'today';
    const DEFAULT_MARKET_EARLY = 'early';
    const DEFAULT_MARKET_OUTRIGHT = 'outright';

    public static function defaultMarkets()
    {
        return [
            static::DEFAULT_MARKET_FAST => 'fast',
            static::DEFAULT_MARKET_LIVE => 'live',
            static::DEFAULT_MARKET_TODAY => 'today',
            static::DEFAULT_MARKET_EARLY => 'early',
            static::DEFAULT_MARKET_OUTRIGHT => 'outright',
        ];
    }

    public static function defaultMarket($defaultMarketValue)
    {
        return static::defaultMarkets()[$defaultMarketValue] ?? null;
    }
}