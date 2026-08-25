<?php

namespace App\Providers;

use App\Models\indemnite\Convocations;
use App\Observers\ConvocationObserver;
use Illuminate\Support\Facades\Schema;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
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

        // Event::listen(ConvocationCreated::class, NotifierAdminsNouvelleConvocation::class)
        // est volontairement absent : Laravel découvre déjà automatiquement
        // NotifierAdminsNouvelleConvocation::handle(ConvocationCreated $event)
        // (auto-discovery des listeners par signature de handle()) —
        // l'ajouter ici aussi enregistre le même listener DEUX fois
        // (constaté : 2 entrées dans Event::getListeners(), une notification
        // dupliquée par convocation créée). Le vrai blocage était ailleurs
        // (voir NotifierAdminsNouvelleConvocation, ShouldQueue retiré).
    }
}
