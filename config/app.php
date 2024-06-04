<?php

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

return [

    //SABA Credentials
    'saba_base_url' => env('SABA_API_BASE_URL'),
    'saba_vendor_id' => [
        'vndk' => env('VNDK_SABA_VENDOR_ID'),
        'php' => env('PHP_SABA_VENDOR_ID'),
        'inr' => env('INR_SABA_VENDOR_ID'),
    ],
    'saba_operator_id' => [
        'vndk' => env('VNDK_SABA_OPERATOR_ID'),
        'php' => env('PHP_SABA_OPERATOR_ID'),
        'inr' => env('INR_SABA_OPERATOR_ID'),
    ],
    'saba_seamless_secret' => env('SABA_SEAMLESS_SECRETE'),

    //KM Credentials
    'km_base_url' => env('KM_BASE_URL'),
    'km_game_base_url' => env('KM_GAME_BASE_URL'),
    'km_client_id' => env('KM_CLIENT_ID'),
    'km_client_secret' => env('KM_CLIENT_SECRET'),
    'km_lobby_pc_path' => env('KM_LOBBY_PC_PATH'),
    'km_lobby_mobile_path' => env('KM_LOBBY_MOBILE_PATH'),

    //UG Credentials
    'ug_base_url' => env('UG_BASE_URL'),
    'ug_login_base_url' => env('UG_LOGIN_BASE_URL'),
    'ug_operator_id' => [
        'vndk' => env('VNDK_UG_OPERATOR_ID'),
        'php' => env('PHP_UG_OPERATOR_ID'),
        'inr' => env('INR_UG_OPERATOR_ID'),
    ],
    'ug_api_key' => [
        'vndk' => env('VNDK_UG_API_KEY'),
        'php' => env('PHP_UG_API_KEY'),
        'inr' => env('INR_UG_API_KEY'),
    ],
    'ug_return_url' => [
        'vndk' => env('VNDK_UG_RETURN_URL'),
        'php' => env('PHP_UG_RETURN_URL'),
        'inr' => env('INR_UG_RETURN_URL'),
    ],


    //Pinnacle Credentials
    'pinnacle_base_url' => env('PINNACLE_BASE_URL'),
    'pinnacle_agent_code' => [
        'vndk' => env('VNDK_PINNACLE_AGENT_CODE'),
        'php' => env('PHP_PINNACLE_AGENT_CODE'),
        'inr' => env('INR_PINNACLE_AGENT_CODE'),
    ],
    'pinnacle_agent_key' => [
        'vndk' => env('VNDK_PINNACLE_AGENT_KEY'),
        'php' => env('PHP_PINNACLE_AGENT_KEY'),
        'inr' => env('INR_PINNACLE_AGENT_KEY'),
    ],
    'pinnacle_secret_key' => [
        'vndk' => env('VNDK_PINNACLE_SECRET_KEY'),
        'php' => env('PHP_PINNACLE_SECRET_KEY'),
        'inr' => env('INR_PINNACLE_SECRET_KEY'),
    ],
    'pinnacle_client_url' => [
        'vndk' => env('VNDK_PINNACLE_CLIENT_URL'),
        'php' => env('PHP_PINNACLE_CLIENT_URL'),
        'inr' => env('INR_PINNACLE_CLIENT_URL'),
    ],

    //SS Credentials
    'ss_base_url' => env('SS_BASE_URL'),
    'ss_company_code' => [
        'vndk' => env('VNDK_SS_COMPANY_CODE'),
        'php' => env('PHP_SS_COMPANY_CODE'),
        'inr' => env('INR_SS_COMPANY_CODE'),
    ],
    'ss_prefix_code' => [
        'vndk' => env('VNDK_SS_PREFIX_CODE'),
        'php' => env('PHP_SS_PREFIX_CODE'),
        'inr' => env('INR_SS_PREFIX_CODE'),
    ],
    'ss_pass_key' => [
        'vndk' => env('VNDK_SS_PASS_KEY'),
        'php' => env('PHP_SS_PASS_KEY'),
        'inr' => env('INR_SS_PASS_KEY'),
    ],
    'ss_external_url' => [
        'vndk' => env('VNDK_SS_EXTERNAL_URL'),
        'php' => env('PHP_SS_EXTERNAL_URL'),
        'inr' => env('INR_SS_EXTERNAL_URL'),
    ],

     //ONE Credentials
     'one_base_url' => env('ONE_BASE_URL'),
     'one_api_key' => [
         'vndk' => env('VNDK_ONE_API_KEY'),
         'php' => env('PHP_ONE_API_KEY'),
         'inr' => env('INR_ONE_API_KEY'),
     ],
     'one_api_secret' => [
         'vndk' => env('VNDK_ONE_API_SECRET'),
         'php' => env('PHP_ONE_API_SECRET'),
         'inr' => env('INR_ONE_API_SECRET'),
     ],
     'one_external_url' => [
         'vndk' => env('VNDK_ONE_EXTERNAL_URL'),
         'php' => env('PHP_ONE_EXTERNAL_URL'),
         'inr' => env('INR_ONE_EXTERNAL_URL'),
     ],

    //EVO Credentials
    'evo_casino_key' => [
        'vndk' => env('VNDK_EVO_CASINO_KEY'),
        'php' => env('PHP_EVO_CASINO_KEY'),
        'inr' => env('INR_EVO_CASINO_KEY'),
    ],
    'evo_api_token' => [
        'vndk' => env('VNDK_EVO_API_TOKEN'),
        'php' => env('PHP_EVO_API_TOKEN'),
        'inr' => env('INR_EVO_API_TOKEN'),
    ],
    'evo_base_url' => env('EVO_BASE_URL'),

    //CMD Credentials
    'cmd_partner_key' => [
        'vndk' => env('VNDK_CMD_PARTNER_KEY'),
        'php' => env('PHP_CMD_PARTNER_KEY'),
        'inr' => env('INR_CMD_PARTNER_KEY'),
    ],
    'cmd_auth_key' => [
        'vndk' => env('VNDK_CMD_AUTH_KEY'),
        'php' => env('PHP_CMD_AUTH_KEY'),
        'inr' => env('INR_CMD_AUTH_KEY'),
    ],
    'cmd_pc_login_base_url' => env('CMD_PC_LOGIN_BASE_URL'),
    'cmd_mobile_login_base_url' => env('CMD_MOBILE_LOGIN_BASE_URL'),
    'cmd_api_base_url' => env('CMD_API_BASE_URL'),

    // DS88

    'ds88_auth' => [
        'vndk' => env('VNDK_DS88_AUTH_KEY'),
        'php' => env('PHP_DS88_AUTH_KEY'),
        'inr' => env('INR_DS88_AUTH_KEY'),
    ],
    'ds88_secret_key' => [
        'vndk' => env('VNDK_DS88_SECRET_KEY'),
        'php' => env('PHP_DS88_SECRET_KEY'),
        'inr' => env('INR_DS88_SECRET_KEY'),
    ],
    'ds88_base_url' => env('DS88_BASE_URL'),

    //VIA Credentials
    'via_vendor_id' => [
        'vndk' => env('VNDK_VIA_VENDOR_ID'),
        'php' => env('PHP_VIA_VENDOR_ID'),
        'inr' => env('INR_VIA_VENDOR_ID'),
    ],
    'via_base_url' => env('VIA_BASE_URL'),
    'via_game_provider_id' => env('VIA_GAME_PROVIDER_ID'),
    'via_access_key' => env('VIA_ACCESS_KEY'),

    //AWC Credentials
    'awc_agent_id' => [
        'vndk' => env('VNDK_AWC_AGENT_ID'),
        'php' => env('PHP_AWC_AGENT_ID'),
        'inr' => env('INR_AWC_AGENT_ID'),
    ],
    'awc_security_code' => [
        'vndk' => env('VNDK_AWC_SECURITY_CODE'),
        'php' => env('PHP_AWC_SECURITY_CODE'),
        'inr' => env('INR_AWC_SECURITY_CODE'),
    ],
    'awc_external_url' => [
        'vndk' => env('VNDK_AWC_EXTERNAL_URL'),
        'php' => env('PHP_AWC_EXTERNAL_URL'),
        'inr' => env('INR_AWC_EXTERNAL_URL'),
    ],
    'awc_seamless_secret' => env('AWC_SEAMLESS_SECRETE'),
    'awc_base_url' => env('AWC_BASE_URL'),

    //WGB Credentials
    'wgb_secret_key' => env('WGB_SECRET_KEY'),
    'wgb_base_url' => env('WGB_BASE_URL'),
    'wgb_site_id' => env('WGB_SITE_ID'),

    //Gemini Credentials
    'gemini_pid' => [
        // 'vndk' => env('VNDK_GEMINI_PARTNER_KEY'),
        'php' => env('PHP_GEMINI_PID'),
        // 'inr' => env('INR_GEMINI_PARTNER_KEY'),
    ],
    'gemini_auth_key' => [
        // 'vndk' => env('VNDK_GEMINI_AUTH_KEY'),
        'php' => env('PHP_GEMINI_AUTH_KEY'),
        // 'inr' => env('INR_GEMINI_AUTH_KEY'),
    ],
    'gemini_secret_key' => [
        // 'vndk' => env('VNDK_GEMINI_AUTH_KEY'),
        'php' => env('PHP_GEMINI_SECRET_KEY'),
        // 'inr' => env('INR_GEMINI_AUTH_KEY'),
    ],
    // 'cmd_pc_login_base_url' => env('CMD_PC_LOGIN_BASE_URL'),
    // 'cmd_mobile_login_base_url' => env('CMD_MOBILE_LOGIN_BASE_URL'),
    'gemini_bingo_api_base_url' => env('GEMINI_BINGO_API_BASE_URL'),
    'gemini_hash_api_base_url'  => env('GEMINI_HASH_API_BASE_URL'),
    'gemini_base_url'           => env('GEMINI_BASE_URL'),

    //Enable Compression
    'enable_compression' => env('ENABLE_COMPRESSION', false),

    //Payment Providers
    'payment_providers' => [
        'deposit_callback_url' => env('PAYMENT_DEPOSIT_CALLBACK_URL'),
        'withdraw_callback_url' => env('PAYMENT_WITHDRAW_CALLBACK_URL'),
        'ai' => [
            'mchid' => env('PAYMENT_AI_MCHID'),
            'base_url' => env('PAYMENT_AI_BASE_URL'),
            'secret_key' => env('PAYMENT_AI_SECRET_KEY'),
            'notify_url' => env('PAYMENT_AI_NOTIFY_URL'),
            'withdraw_url' => env('PAYMENT_AI_WITHDRAW_URL'),
        ],
        'galaxy' => [
            'mchid' => env('PAYMENT_GALAXY_MCHID'),
            'base_url' => env('PAYMENT_GALAXY_BASE_URL'),
            'secret_key' => env('PAYMENT_GALAXY_SECRET_KEY'),
            'withdraw_url' => env('PAYMENT_GALAXY_WITHDRAW_URL'),
        ],
        'EZPAY' => [
            'mchid' => env('PAYMENT_EZPAY_MCHID'),
            'base_url' => env('PAYMENT_EZPAY_BASE_URL'),
            'secret_key' => env('PAYMENT_EZPAY_SECRET_KEY'),
            'withdraw_url' => env('PAYMENT_EZPAY_WITHDRAW_URL'),
            'deposit_url' => env('PAYMENT_EZPAY_DEPOSIT_URL'),
        ],
    ],

    'philippines_ip' => env('PHILIPPINES_IP'),
    
    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),
    'out_going_ip' => env('OUT_GOING_IP'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    'asset_url' => env('ASSET_URL'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */

    'timezone' => 'UTC',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */

    'locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */

    'fallback_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Faker Locale
    |--------------------------------------------------------------------------
    |
    | This locale will be used by the Faker PHP library when generating fake
    | data for your database seeds. For example, this will be used to get
    | localized telephone numbers, street address information and more.
    |
    */

    'faker_locale' => 'en_US',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => 'file',
        // 'store'  => 'redis',
    ],

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */

    'providers' => ServiceProvider::defaultProviders()->merge([
        /*
         * Package Service Providers...
         */

        /*
         * Application Service Providers...
         */
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        App\Providers\BroadcastServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
        Spatie\Permission\PermissionServiceProvider::class,
    ])->toArray(),

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */

    'aliases' => Facade::defaultAliases()->merge([
        // 'Example' => App\Facades\Example::class,
    ])->toArray(),

];
