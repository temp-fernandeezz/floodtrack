<?php

namespace App\Providers;

use Illuminate\Support\Carbon;
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
        // Todo o conteúdo do app é em pt-BR — datas relativas (diffForHumans/since)
        // devem seguir o mesmo idioma, mesmo com APP_LOCALE=en.
        Carbon::setLocale('pt_BR');
    }
}
