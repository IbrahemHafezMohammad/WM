<?php

use Illuminate\Support\Facades\Log;
use App\Constants\GamePlatformConstants;
use App\Services\Providers\KMProvider\KMProvider;
use App\Services\Providers\AWCProvider\AWCProvider;
use App\Services\Providers\VIAProvider\VIAProvider;
use App\Services\Providers\SABAProvider\SABAProvider;

if (!function_exists('getUrl')) {

    function getUrl($url, $newParams)
    {
        $parsedUrl = parse_url($url);
        $query = isset($parsedUrl['query']) ? $parsedUrl['query'] : '';

        parse_str($query, $existingParams);

        $mergedParams = array_merge($existingParams, $newParams);

        $updatedQuery = http_build_query($mergedParams);

        return $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $parsedUrl['path'] . '?' . $updatedQuery;
    }
}

if (!function_exists('objectToArray')) {

    function objectToArray($data)
    {
        if (is_object($data)) {
            $data = get_object_vars($data);
        }

        if (is_array($data)) {
            return array_map('objectToArray', $data);
        }

        return $data;
    }
}

if (!function_exists('getDeviceType')) {

    function getDeviceType($userAgent)
    {
        if (strpos($userAgent, 'Mobile') !== false || strpos($userAgent, 'Android') !== false) {
            return 'Mobile';
        } elseif (strpos($userAgent, 'Tablet') !== false || strpos($userAgent, 'iPad') !== false) {
            return 'Tablet';
        } else {
            return 'Desktop';
        }
    }
}


if (!function_exists('manageGameLoginResult')) {

    function manageGameLoginResult($result, $platform)
    {
        try {
            if ($result) {

                if ($platform === GamePlatformConstants::PLATFORM_VIA) {

                    $result = json_decode($result);

                    if ($result->code == 1005) {

                        return 'REGISTER';
                    }

                    if ($result->code == 0) {

                        return [
                            'data' => $result->data->redirectUrl,
                            'is_url' => true,
                        ];
                    }

                    if ($result->error == VIAProvider::ERROR_CODE_CURRENCY_NOT_SUPPORTED) {

                        return 'CURRENCY_NOT_SUPPORTED';
                    }
                }
                if ($platform === GamePlatformConstants::PLATFORM_UG) {

                    $result = json_decode($result);

                    if (!$result->is_user) {

                        return 'REGISTER';
                    }

                    return [
                        'data' => $result->login_url,
                        'is_url' => true,
                    ];
                } elseif ($platform === GamePlatformConstants::PLATFORM_KM) {

                    $result = json_decode($result);

                    if (isset($result->url)) {

                        return [
                            'data' => $result->url,
                            'is_url' => true,
                        ];
                    }

                    if ($result->error == KMProvider::ERROR_CODE_CURRENCY_NOT_SUPPORTED) {

                        return 'CURRENCY_NOT_SUPPORTED';
                    }
                } elseif ($platform === GamePlatformConstants::PLATFORM_EVO) {

                    $result = json_decode($result);

                    if (isset($result->entry)) {

                        return [
                            'data' => $result->entry,
                            'is_url' => true,
                        ];
                    }
                } elseif ($platform === GamePlatformConstants::PLATFORM_CMD) {

                    $result = json_decode($result);

                    if (isset($result->is_user) && $result->is_user) {

                        return [
                            'data' => $result->login_url,
                            'is_url' => true,
                        ];
                    } else {

                        return 'REGISTER';
                    }
                } elseif ($platform === GamePlatformConstants::PLATFORM_PINNACLE) {

                    $result = json_decode($result);

                    if (isset($result->loginUrl) && $result->loginUrl) {

                        return [
                            'data' => $result->loginUrl,
                            'is_url' => true,
                        ];
                    }
                } elseif ($platform === GamePlatformConstants::PLATFORM_DS88) {

                    $result = json_decode($result);

                    if ($result->code == 'OK') {

                        return [
                            'data' => $result->game_link,
                            'is_url' => true,
                        ];
                    }

                    if ($result->code == 'ERROR' && $result->message == 'Invalid username or password.') {

                        return 'REGISTER';
                    }
                } elseif (in_array($platform, array_keys(GamePlatformConstants::getONESubProviders()))) {

                    $result = json_decode($result);

                    if ($result->status == 'SC_OK') {

                        return [
                            'data' => $result->data->gameUrl,
                            'is_url' => true,
                        ];
                    }
                    
                } elseif ($platform === GamePlatformConstants::PLATFORM_SS) {

                    $result = json_decode($result);

                    if ($result->error_status == '10') {

                        return 'REGISTER';
                    }

                    if ($result->error_status == '0') {

                        return [
                            'data' => $result->Data,
                            'is_url' => true,
                        ];
                    }
                } elseif (in_array($platform, array_keys(GamePlatformConstants::getAWCSubProviders()))) {

                    $result = json_decode($result);

                    if ($result->status == "1002") {

                        return 'REGISTER';
                    }

                    if ($result->status == "0000") {

                        return [
                            'data' => $result->url,
                            'is_url' => true,
                        ];
                    }

                    if ($result->error == AWCProvider::ERROR_CODE_CURRENCY_NOT_SUPPORTED) {

                        return 'CURRENCY_NOT_SUPPORTED';
                    }
                } elseif ($platform === GamePlatformConstants::PLATFORM_SABA) {

                    $result = json_decode($result);

                    if ($result->error_code == 2) {

                        return 'REGISTER';
                    } elseif ($result->error_code == 0) {

                        return [
                            'data' => $result->Data,
                            'is_url' => true,
                        ];
                    }

                    if ($result->error == SABAProvider::ERROR_CODE_CURRENCY_NOT_SUPPORTED) {

                        return 'CURRENCY_NOT_SUPPORTED';
                    }
                } elseif ($platform === GamePlatformConstants::PLATFORM_DAGA) {
                    Log::info('------------------------------DAGA CASE------------------------');
                    Log::info($result);
                    $result = json_decode($result);
                    if ($result->status == "USER_NOT_FOUND") {
                        return 'REGISTER';
                    } elseif ($result->status == "SUCCESS") {
                        return [
                            'data' => $result->link,
                            'is_url' => true,
                        ];
                    }
                }
                elseif ($platform === GamePlatformConstants::PLATFORM_GEMINI) {
                    Log::info('------------------------------GEMINI CASE------------------------');
                    Log::info($result);
                    $result = json_decode($result);
                    if ($result->status == "Success") 
                    {
                        return [
                            'data' => $result->link,
                            'is_url' => true,
                        ];
                    }
                }
            }

            Log::info('---------------------------------------------------------------------------------------------');
            Log::info('Get Game Login Url Failed For Platform: ' . $platform);
            Log::info(json_encode($result));
            Log::info('---------------------------------------------------------------------------------------------');

            return null;
        } catch (Exception $exception) {
            Log::info('---------------------------------------------------------------------------------------------');
            Log::info('Get Game Login Url Unexpected Response For Platform: ' . $platform);
            Log::info(json_encode($result));
            Log::info($exception);
            Log::info('---------------------------------------------------------------------------------------------');
            return null;
        }
    }
}

if (!function_exists('manageRegisterToGameResult')) {

    function manageRegisterToGameResult($register, $platform)
    {
        try {

            if ($register) {

                $register = json_decode($register) ?? $register;

                if ($platform === GamePlatformConstants::PLATFORM_VIA) {

                    if ($register->code == 0) {

                        return "SUCCESS";
                    }

                    if ($register->error == VIAProvider::ERROR_CODE_CURRENCY_NOT_SUPPORTED) {

                        return 'CURRENCY_NOT_SUPPORTED';
                    }
                } elseif ($platform === GamePlatformConstants::PLATFORM_UG) {

                    if ($register->code == '0') {

                        return "SUCCESS";
                    }
                } elseif ($platform === GamePlatformConstants::PLATFORM_DS88) {

                    if ($register->code == 'OK') {

                        return "SUCCESS";
                    }
                } elseif ($platform === GamePlatformConstants::PLATFORM_CMD) {

                    if ($register->Code == '000000') {

                        return "SUCCESS";
                    }
                } elseif ($platform === GamePlatformConstants::PLATFORM_SS) {

                    if ($register->error_status == '0') {

                        return "SUCCESS";
                    }
                } elseif ($platform === GamePlatformConstants::PLATFORM_DAGA) {

                    if ($register->status == 'SUCCESS') {

                        return "SUCCESS";
                    }
                } elseif (in_array($platform, array_keys(GamePlatformConstants::getAWCSubProviders()))) {

                    if ($register->status == "0000") {

                        return "SUCCESS";
                    }

                    if ($register->error == AWCProvider::ERROR_CODE_CURRENCY_NOT_SUPPORTED) {

                        return 'CURRENCY_NOT_SUPPORTED';
                    }
                } elseif ($platform === GamePlatformConstants::PLATFORM_SABA) {

                    if ($register->error_code == 0) {

                        return "SUCCESS";
                    }

                    if ($register->error == SABAProvider::ERROR_CODE_CURRENCY_NOT_SUPPORTED) {

                        return 'CURRENCY_NOT_SUPPORTED';
                    }
                }
            }

            Log::info('---------------------------------------------------------------------------------------------');
            Log::info('Register To Game Failed For Platform: ' . $platform);
            Log::info(json_encode($register));
            Log::info('---------------------------------------------------------------------------------------------');

            return null;
        } catch (Exception $exception) {

            Log::info('---------------------------------------------------------------------------------------------');
            Log::info('Register To Game Unexpected Response For Platform: ' . $platform);
            Log::info(json_encode($register));
            Log::info($exception);
            Log::info('---------------------------------------------------------------------------------------------');
            return null;
        }
    }
}
