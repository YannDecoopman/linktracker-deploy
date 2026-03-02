<?php

namespace App\Providers;

use App\Models\Backlink;
use App\Observers\BacklinkObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Backlink::observe(BacklinkObserver::class);

        $this->configureRateLimiting();
    }

    /**
     * STORY-044 : Rate limiting avancé par IP et par utilisateur
     */
    protected function configureRateLimiting(): void
    {
        // Vérification manuelle d'un backlink : 60 req/min par utilisateur authentifié
        RateLimiter::for('backlink-check', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Import CSV : 5 req/min par utilisateur (opération lourde)
        RateLimiter::for('backlink-import', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });

        // Routes générales backlinks : 60 req/min par IP
        RateLimiter::for('backlinks-general', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        // Refresh métriques SEO : 3 req/min par utilisateur
        RateLimiter::for('seo-refresh', function (Request $request) {
            return Limit::perMinute(3)->by($request->user()?->id ?: $request->ip());
        });
    }
}
