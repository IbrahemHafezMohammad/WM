<?php
namespace App\Services\Providers;

interface ProviderInterface
{
    public function loginToGame($language, $loginIp, $deviceType): ?string;
    public function registerToGame($language, $loginIp): ?string;
}
?>