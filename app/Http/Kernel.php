<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [
            // \App\Http\Middleware\TrustHosts::class,
        \App\Http\Middleware\TrustProxies::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \App\Http\Middleware\ResolveCrossSitePolicy::class
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array<string, array<int, class-string|string>>
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
                // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \Illuminate\Routing\Middleware\ThrottleRequests::class . ':api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /**
     * The application's middleware aliases.
     *
     * Aliases may be used instead of class names to conveniently assign middleware to routes and groups.
     *
     * @var array<string, class-string|string>
     */
    protected $middlewareAliases = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'compress.response' => \App\Http\Middleware\CompressResponse::class,
        'decompress.request' => \App\Http\Middleware\DecompressRequest::class,
        'whitelist.ip' => \App\Http\Middleware\WhitelistIPMiddleware::class,
        'scope.player' => \App\Http\Middleware\EnsureUserIsPlayer::class,
        'scope.admin'  => \App\Http\Middleware\EnsureUserIsAdmin::class,
        'scope.agent'  => \App\Http\Middleware\EnsureUserIsAgent::class,
        'pinnacle.provider.ip.check' => \App\Http\Middleware\PinnacleProviderIPCheck::class,
        'pinnacle.provider.auth.check' => \App\Http\Middleware\PinnacleProviderAuthCheck::class,
        'one.provider.ip.check' => \App\Http\Middleware\ONEProviderIPCheck::class,
        'one.provider.auth.check' => \App\Http\Middleware\ONEProviderAuthCheck::class,
        'ss.provider.ip.check' => \App\Http\Middleware\SSProviderIPCheck::class,
        'ss.provider.auth.check' => \App\Http\Middleware\SSProviderAuthCheck::class,
        'cmd.provider.ip.check' => \App\Http\Middleware\CMDProviderIPCheck::class,
        'ds88.provider.ip.check' => \App\Http\Middleware\DS88ProviderIPCheck::class,
        'ds88.provider.auth.check' => \App\Http\Middleware\DS88ProviderAuthCheck::class,
        'ug.provider.ip.check' => \App\Http\Middleware\UGProviderIPCheck::class,
        'ug.provider.auth.check' => \App\Http\Middleware\UGProviderAuthCheck::class,
        'evo.provider.ip.check' => \App\Http\Middleware\EVOProviderIPCheck::class,
        'evo.provider.auth.check' => \App\Http\Middleware\EVOProviderAuthCheck::class,
        'via.provider.ip.check' => \App\Http\Middleware\VIAProviderIPCheck::class,
        'via.provider.auth.check' => \App\Http\Middleware\VIAProviderAuthCheck::class,
        'km.provider.ip.check' => \App\Http\Middleware\KMProviderIPCheck::class,
        'km.provider.auth.check' => \App\Http\Middleware\KMProviderAuthCheck::class,
        'awc.provider.ip.check' => \App\Http\Middleware\AWCProviderIPCheck::class,
        'awc.provider.auth.check' => \App\Http\Middleware\AWCProviderAuthCheck::class,
        'saba.provider.ip.check' => \App\Http\Middleware\SABAProviderIPCheck::class,
        'saba.provider.auth.check' => \App\Http\Middleware\SABAProviderAuthCheck::class,
        'gemini.provider.ip.check' => \App\Http\Middleware\GeminiProviderIPCheck::class,
        'gemini.provider.auth.check' => \App\Http\Middleware\GeminiProviderAuthCheck::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'signed' => \App\Http\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        'cors' => \App\Http\Middleware\Cors::class,
    ];
}
