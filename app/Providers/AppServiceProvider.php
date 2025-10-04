<?php

namespace App\Providers;

use App\Models\User;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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
        Scramble::configure()
            ->routes(function (Route $route){
                return Str::startsWith($route->uri, 'api/');
            })
            ->expose(
                ui: '/docs/v1/api',
                document: '/docs/v1/openapi.json',
            )
            ->withDocumentTransformers(function (OpenApi $openApi) {
                $openApi->secure(
                    SecurityScheme::http('bearer', 'JWT')
                );
            });

        Gate::define('viewApiDoc', function (User $user) {
            return true;
        });

        // Auto-discover observers from config/observers.php
        $this->registerObservers();
    }

    /**
     * Register model observers from config file
     */
    protected function registerObservers(): void
    {
        $observers = config('observers.observers', []);
        
        foreach ($observers as $model => $observer) {
            $model::observe($observer);
        }
    }
}
