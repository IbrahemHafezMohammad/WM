<?php

namespace App\Constants;

class AWCProviderConfigConstants
{
    const TABLE_NAME = 'awc_provider_configs';

    // SEXYBCRT
    const SEXYBCRT_VNDK_10_80000_BET_LIMIT_ID = 281108;
    const SEXYBCRT_VNDK_100_5000_BET_LIMIT_ID = 281104;
    const SEXYBCRT_VNDK_200_10000_BET_LIMIT_ID = 281105;
    const SEXYBCRT_VNDK_500_25000_BET_LIMIT_ID = 281106;
    const SEXYBCRT_VNDK_1000_50000_BET_LIMIT_ID = 281107;
    const SEXYBCRT_VNDK_10_500_BET_LIMIT_ID = 281101;
    const SEXYBCRT_VNDK_20_1000_BET_LIMIT_ID = 281102;
    const SEXYBCRT_VNDK_50_2500_BET_LIMIT_ID = 281103;

    public static function getSexyBcrtVNDKBetLimits()
    {
        return [
            self::SEXYBCRT_VNDK_10_80000_BET_LIMIT_ID => [
                'min' => 10,
                'max' => 80000,
            ],
            self::SEXYBCRT_VNDK_100_5000_BET_LIMIT_ID => [
                'min' => 100,
                'max' => 5000,
            ],
            self::SEXYBCRT_VNDK_200_10000_BET_LIMIT_ID => [
                'min' => 200,
                'max' => 10000,
            ],
            self::SEXYBCRT_VNDK_500_25000_BET_LIMIT_ID => [
                'min' => 500,
                'max' => 25000,
            ],
            self::SEXYBCRT_VNDK_1000_50000_BET_LIMIT_ID => [
                'min' => 1000,
                'max' => 50000,
            ],
            self::SEXYBCRT_VNDK_10_500_BET_LIMIT_ID => [
                'min' => 10,
                'max' => 500,
            ],
            self::SEXYBCRT_VNDK_20_1000_BET_LIMIT_ID => [
                'min' => 20,
                'max' => 1000,
            ],
            self::SEXYBCRT_VNDK_50_2500_BET_LIMIT_ID => [
                'min' => 50,
                'max' => 2500,
            ],
        ];
    }

    public static function getSexyBcrtVNDKBetLimit($bet_limit_id)
    {
        return self::getSexyBcrtVNDKBetLimits()[$bet_limit_id] ?? null;
    }

    // AE Sexy Bet Limits
    const SEXYBCRT_PHP_50_15000_BET_LIMIT_ID = 282001;
    const SEXYBCRT_PHP_100_25000_BET_LIMIT_ID = 282002;
    const SEXYBCRT_PHP_100_50000_BET_LIMIT_ID = 282003;

    const SEXYBCRT_PROD_PHP_50_200000_BET_LIMIT_ID = 342004;
    const SEXYBCRT_PROD_PHP_100_15000_BET_LIMIT_ID = 342001;
    const SEXYBCRT_PROD_PHP_100_50000_BET_LIMIT_ID = 342003;
    const SEXYBCRT_PROD_PHP_100_25000_BET_LIMIT_ID = 342002;

    public static function getSexyBcrtPHPBetLimits()
    {
        return [
            self::SEXYBCRT_PHP_50_15000_BET_LIMIT_ID => [
                'min' => 50,
                'max' => 15000,
            ],
            self::SEXYBCRT_PHP_100_25000_BET_LIMIT_ID => [
                'min' => 100,
                'max' => 25000,
            ],
            self::SEXYBCRT_PHP_100_50000_BET_LIMIT_ID => [
                'min' => 100,
                'max' => 50000,
            ],
            self::SEXYBCRT_PROD_PHP_50_200000_BET_LIMIT_ID => [
                'min' => 50,
                'max' => 200000,
            ],
            self::SEXYBCRT_PROD_PHP_100_15000_BET_LIMIT_ID => [
                'min' => 100,
                'max' => 15000,
            ],
            self::SEXYBCRT_PROD_PHP_100_50000_BET_LIMIT_ID => [
                'min' => 100,
                'max' => 50000,
            ],
            self::SEXYBCRT_PROD_PHP_100_25000_BET_LIMIT_ID => [
                'min' => 100,
                'max' => 25000,
            ],
        ];
    }

    public static function getSexyBcrtPHPBetLimit($bet_limit_id)
    {
        return self::getSexyBcrtPHPBetLimits()[$bet_limit_id] ?? null;
    }


    const SEXYBCRT_INR_300_15000_BET_LIMIT_ID = 281504;
    const SEXYBCRT_INR_600_30000_BET_LIMIT_ID = 281505;
    const SEXYBCRT_INR_1000_50000_BET_LIMIT_ID = 281506;
    const SEXYBCRT_INR_200_7500_BET_LIMIT_ID = 281503;
    const SEXYBCRT_INR_20_5000_BET_LIMIT_ID = 281501;

    public static function getSexyBcrtINRBetLimits()
    {
        return [
            self::SEXYBCRT_INR_300_15000_BET_LIMIT_ID => [
                'min' => 300,
                'max' => 15000,
            ],
            self::SEXYBCRT_INR_600_30000_BET_LIMIT_ID => [
                'min' => 600,
                'max' => 30000,
            ],
            self::SEXYBCRT_INR_1000_50000_BET_LIMIT_ID => [
                'min' => 1000,
                'max' => 50000,
            ],
            self::SEXYBCRT_INR_200_7500_BET_LIMIT_ID => [
                'min' => 200,
                'max' => 7500,
            ],
            self::SEXYBCRT_INR_20_5000_BET_LIMIT_ID => [
                'min' => 20,
                'max' => 5000,
            ],
        ];
    }

    public static function getSexyBcrtINRBetLimit($bet_limit_id)
    {
        return self::getSexyBcrtINRBetLimits()[$bet_limit_id] ?? null;
    }

    // HORSEBOOK

    const HORSEBOOK_MULTIPLE_OF_FARE = 30;

    const HORSEBOOK_VND_MIN_BET = 30;
    const HORSEBOOK_VND_MAX_BET = 7500;
    const HORSEBOOK_VND_MAX_BET_SUM_PER_HOUR = 15000;
    const HORSEBOOK_VND_MINOR_MIN_BET = 30;
    const HORSEBOOK_VND_MINOR_MAX_BET = 3000;
    const HORSEBOOK_VND_MINOR_MAX_BET_SUM_PER_HOUR = 7500;

    const HORSEBOOK_INR_MIN_BET = 50;
    const HORSEBOOK_INR_MAX_BET = 25000;
    const HORSEBOOK_INR_MAX_BET_SUM_PER_HOUR = 50000;
    const HORSEBOOK_INR_MINOR_MIN_BET = 50;
    const HORSEBOOK_INR_MINOR_MAX_BET = 8000;
    const HORSEBOOK_INR_MINOR_MAX_BET_SUM_PER_HOUR = 25000;

    public static function getVNDKDefaultBetLimit()
    {
        return [
            GamePlatformConstants::AWC_SUB_PROVIDER_SEXYBCRT => [
                GamePlatformConstants::AWC_SUB_PROVIDER_TYPE_LIVE => [
                    'limitId' => [
                        self::SEXYBCRT_VNDK_10_80000_BET_LIMIT_ID,
                        self::SEXYBCRT_VNDK_100_5000_BET_LIMIT_ID,
                        self::SEXYBCRT_VNDK_200_10000_BET_LIMIT_ID,
                        self::SEXYBCRT_VNDK_500_25000_BET_LIMIT_ID,
                        self::SEXYBCRT_VNDK_1000_50000_BET_LIMIT_ID,
                        self::SEXYBCRT_VNDK_10_500_BET_LIMIT_ID,
                    ],
                ],
            ],
            GamePlatformConstants::AWC_SUB_PROVIDER_HORSEBOOK => [
                GamePlatformConstants::AWC_SUB_PROVIDER_TYPE_LIVE => [
                    'minbet' => self::HORSEBOOK_VND_MIN_BET,
                    'maxbet' => self::HORSEBOOK_VND_MAX_BET,
                    'maxBetSumPerHorse' => self::HORSEBOOK_VND_MAX_BET_SUM_PER_HOUR,
                    'minorMinbet' => self::HORSEBOOK_VND_MINOR_MIN_BET,
                    'minorMaxbet' => self::HORSEBOOK_VND_MINOR_MAX_BET,
                    'minorMaxBetSumPerHorse' => self::HORSEBOOK_VND_MINOR_MAX_BET_SUM_PER_HOUR,
                ],
            ]
        ];
    }

    public static function getPHPDefaultBetLimit($is_prod)
    {
        if ($is_prod) {

            return [
                GamePlatformConstants::AWC_SUB_PROVIDER_SEXYBCRT => [
                    GamePlatformConstants::AWC_SUB_PROVIDER_TYPE_LIVE => [
                        'limitId' => [
                            self::SEXYBCRT_PROD_PHP_50_200000_BET_LIMIT_ID,
                            self::SEXYBCRT_PROD_PHP_100_15000_BET_LIMIT_ID,
                            self::SEXYBCRT_PROD_PHP_100_50000_BET_LIMIT_ID,
                            self::SEXYBCRT_PROD_PHP_100_25000_BET_LIMIT_ID,
                        ],
                    ],
                ],
            ];
        }

        return [
            GamePlatformConstants::AWC_SUB_PROVIDER_SEXYBCRT => [
                GamePlatformConstants::AWC_SUB_PROVIDER_TYPE_LIVE => [
                    'limitId' => [
                        self::SEXYBCRT_PHP_50_15000_BET_LIMIT_ID,
                        self::SEXYBCRT_PHP_100_25000_BET_LIMIT_ID,
                        self::SEXYBCRT_PHP_100_50000_BET_LIMIT_ID,
                    ],
                ],
            ],
        ];
    }

    public static function getINRDefaultBetLimit()
    {
        return [
            GamePlatformConstants::AWC_SUB_PROVIDER_SEXYBCRT => [
                GamePlatformConstants::AWC_SUB_PROVIDER_TYPE_LIVE => [
                    'limitId' => [
                        self::SEXYBCRT_INR_300_15000_BET_LIMIT_ID,
                        self::SEXYBCRT_INR_600_30000_BET_LIMIT_ID,
                        self::SEXYBCRT_INR_1000_50000_BET_LIMIT_ID,
                        self::SEXYBCRT_INR_200_7500_BET_LIMIT_ID,
                        self::SEXYBCRT_INR_20_5000_BET_LIMIT_ID,
                    ],
                ],
            ],
            GamePlatformConstants::AWC_SUB_PROVIDER_HORSEBOOK => [
                GamePlatformConstants::AWC_SUB_PROVIDER_TYPE_LIVE => [
                    'minbet' => self::HORSEBOOK_INR_MIN_BET,
                    'maxbet' => self::HORSEBOOK_INR_MAX_BET,
                    'maxBetSumPerHorse' => self::HORSEBOOK_INR_MAX_BET_SUM_PER_HOUR,
                    'minorMinbet' => self::HORSEBOOK_INR_MINOR_MIN_BET,
                    'minorMaxbet' => self::HORSEBOOK_INR_MINOR_MAX_BET,
                    'minorMaxBetSumPerHorse' => self::HORSEBOOK_INR_MINOR_MAX_BET_SUM_PER_HOUR,
                ],
            ]
        ];
    }
}
