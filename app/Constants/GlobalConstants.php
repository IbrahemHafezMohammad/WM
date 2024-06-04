<?php

namespace App\Constants;

class GlobalConstants
{
    // channels constants
    const TRANSACTIONS_BROADCAST_CHANEL_NAME = 'transactions';
    const GAME_TRANSACTIONS_BROADCAST_CHANEL_NAME = 'game-fund-transfer.';

    // EPOCH_TICKS
    // From Unix epoch start in ticks
    const EPOCH_TICKS = 621355968000000000;

    //images 
    const PAYMENT_METHOD_IMAGES_PATH = 'public/payment-methods';
    const GAME_PLATFORM_IMAGES_PATH = 'public/game-platform';
    const GAME_CATEGORY_IMAGES_PATH = 'public/game-category';
    const GAME_ITEM_IMAGES_PATH = 'public/game-item';
    const PROMOTIONS_IMAGES_PATH = 'public/promotions';
    const PROMOTION_CATEGORY_IMAGES_PATH = 'public/promotion-category';
    const TRANSACTION_IMAGES_PATH = 'public/transactions';
    const USER_IMAGES_PATH = 'public/users';
    const BANK_CODE_IMAGES_PATH = 'public/bank-codes';
    const PAYMENT_CATEGORY_PATH = 'public/payment-category';

    //languages
    const LANG_EN = 1; // English
    const LANG_HI = 2; // Hindi
    const LANG_TL = 3; // Pilipino 
    const LANG_VN = 4; // Vietnamese

    public static function getLanguages()
    {
        return [
            static::LANG_EN => 'English',
            static::LANG_HI => 'Hindi',
            static::LANG_TL => 'Pilipino',
            static::LANG_VN => 'Vietnamese'
        ];
    }

    public static function getLanguage($languageValue)
    {
        return static::getLanguages()[$languageValue] ?? null;
    }

    // Project Decimal Precision

    const DECIMAL_TOTALS = 26;
    const DECIMAL_PRECISION = 8;

    // countries
    const COUNTRY_INDIA = 1;
    const COUNTRY_PHIL = 2;
    const COUNTRY_VTNM = 4;

    public static function getCountries()
    {
        return [
            static::COUNTRY_INDIA => 'India',
            static::COUNTRY_PHIL => 'Philippines',
            static::COUNTRY_VTNM => 'Vietnam',
        ];
    }

    public static function getCountry($countryValue)
    {
        return static::getCountries()[$countryValue] ?? null;
    }

    //currency
    const CURRENCY_INR = 1; // Indian Currency (Indian Rupee)
    const CURRENCY_PHP = 2; // Philippine Currency (Philippine peso)
    const CURRENCY_VNDK = 4; // Vietnamese Currency (Vietnamese dong)
    const CURRENCY_USD = 8; // Dollars Currency

    public static function getCurrencies()
    {
        return [
            static::CURRENCY_INR => 'INR',
            static::CURRENCY_PHP => 'PHP',
            static::CURRENCY_VNDK => 'VNDK',
            static::CURRENCY_USD => 'USD',
        ];
    }

    public static function getCurrency($currencyValue)
    {
        return static::getCurrencies()[$currencyValue] ?? null;
    }

    public static function getConversionRates()
    {
        return [
            static::CURRENCY_INR => 0.012,
            static::CURRENCY_PHP => 0.018,
            static::CURRENCY_VNDK => 0.041,
            static::CURRENCY_USD => 1,
        ];
    }

    public static function getConversionRate($currencyValue)
    {
        return static::getConversionRates()[$currencyValue] ?? null;
    }

    //country codes
    const PHONE_CODE_INDIA = '+91';
    const PHONE_CODE_PHILIPPINES = '+63';
    const PHONE_CODE_VIETNAM = '+84';

    public static function getPhoneCodesWithImages()
    {
        return [
            [
                'image' => asset('images/India_Flag.png'),
                'code' => static::PHONE_CODE_INDIA
            ],
            [
                'image' => asset('images/Vietnam_Flag.png'),
                'code' => static::PHONE_CODE_VIETNAM
            ],
            [
                'image' => asset('images/Philippines_Flag.png'),
                'code' => static::PHONE_CODE_PHILIPPINES
            ]
        ];
    }
}