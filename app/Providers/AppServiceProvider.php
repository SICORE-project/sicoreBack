<?php

namespace App\Providers;

use App\Events\ConvocationCreated;
use App\Listeners\NotifierAdminsNouvelleConvocation;
use App\Models\indemnite\Convocations;
use App\Observers\ConvocationObserver;
use Illuminate\Support\Facades\Schema;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip().'|'.mb_strtolower((string) $request->input('login')));
        });
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->getAuthIdentifier() ?: $request->ip()));

        Convocations::observe(ConvocationObserver::class);

        //Event::listen(ConvocationCreated::class, NotifierAdminsNouvelleConvocation::class);
    }
}
