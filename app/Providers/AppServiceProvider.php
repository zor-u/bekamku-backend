<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        Password::defaults(function () {
            $rule = Password::min(8);

            return app()->isProduction()
                ? $rule->letters()->numbers()->symbols()->uncompromised()
                : $rule;
        });

        RateLimiter::for('register', function (Request $request) {
            $email = $request->input('email');

            return [
                Limit::perMinute(5)->by('register:email:'.(is_string($email) ? strtolower($email) : '')),
                Limit::perMinute(20)->by('register:ip:'.$request->ip()),
            ];
        });
    }
}
