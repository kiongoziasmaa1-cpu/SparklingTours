<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\Region;
use App\Models\SiteSetting;
use App\Policies\BookingPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped('site.context', fn () => ['site' => SiteSetting::pluck('value', 'key')->all(), 'regions' => Region::all()]);
    }

    public function boot(): void
    {
        Gate::policy(Booking::class, BookingPolicy::class);
        Paginator::useBootstrapFive();
        if (app()->environment('production')) {
            $debugWasEnabled = (bool) config('app.debug');
            // A configuration failure must not expose a production exception trace.
            config(['app.debug' => false]);
            $c = config('tourism.captcha');
            if (! $c['enabled'] || ! $c['secret'] || ! $c['site_key'] || preg_match('/^[123]x0+/', $c['secret']) || empty($c['hostnames'])) {
                throw new \RuntimeException('Configure live Turnstile credentials and production hostnames. Development bypass/test keys are forbidden.');
            }
            if ($debugWasEnabled) {
                throw new \RuntimeException('Disable debug output in production.');
            }
            $appUrl = parse_url((string) config('app.url'));
            if (! is_array($appUrl) || empty($appUrl['scheme']) || empty($appUrl['host'])) {
                throw new \RuntimeException('APP_URL must be an absolute URL with a hostname.');
            }
            $rootUrl = $appUrl['scheme'].'://'.$appUrl['host'].(isset($appUrl['port']) ? ':'.$appUrl['port'] : '');
            URL::forceRootUrl($rootUrl);
            URL::forceScheme('https');
        }
        View::composer('*', function ($view) {
            $context = app('site.context');
            $view->with('site', $context['site']);
            $view->with('navigationRegions', $context['regions']);
        });
        RateLimiter::for('public-forms', fn (Request $r) => [Limit::perMinute(6)->by('form:'.$r->ip()), Limit::perHour(60)->by('form-hour:'.$r->ip())]);
        RateLimiter::for('estimates', fn (Request $r) => Limit::perMinute(60)->by('estimate:'.$r->ip()));
        RateLimiter::for('staff-login', fn (Request $r) => [Limit::perMinute(20)->by('login-ip:'.$r->ip()), Limit::perMinute(5)->by(hash('sha256', strtolower((string) $r->input('email')).'|'.$r->ip()))]);
        RateLimiter::for('documents', fn (Request $r) => Limit::perMinute(30)->by('document:'.$r->ip()));
    }
}
