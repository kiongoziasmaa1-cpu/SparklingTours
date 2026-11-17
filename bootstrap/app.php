<?php

use App\Http\Middleware\EnsureStaff;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', commands: __DIR__.'/../routes/console.php', health: '/up')
    ->withMiddleware(function (Middleware $m): void {
        $m->web(append: [SecurityHeaders::class]);
        $m->alias(['staff' => EnsureStaff::class]);
        $m->redirectGuestsTo(fn (Request $r) => route('login'));
    })
    ->withExceptions(function (Exceptions $e): void {
        $e->shouldRenderJsonWhen(fn (Request $r) => $r->expectsJson());
    })
    ->create();


$app->afterBootstrapping(LoadConfiguration::class, function (Application $app): void {
    if ($app->environment('production')) {

        // Use Namecheap public_html as Laravel's public directory.
        $publicPath = dirname($app->basePath()).'/public_html';

        if (is_dir($publicPath)) {
            $app->usePublicPath($publicPath);
        }

        TrustProxies::at('*');
        TrustProxies::withHeaders(
            Request::HEADER_X_FORWARDED_FOR |
                Request::HEADER_X_FORWARDED_HOST |
                Request::HEADER_X_FORWARDED_PORT |
                Request::HEADER_X_FORWARDED_PROTO,
        );
    }
});


return $app;
