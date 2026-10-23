<?php

namespace App\Providers;

use App\Services\Supabase\GoTrueClient;
use App\Services\Supabase\PostgrestClient;
use App\Services\Supabase\SupabaseSession;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class SupabaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GoTrueClient::class, function (Application $app) {
            $config = $app['config']->get('supabase');

            return new GoTrueClient(
                $config['url'],
                $config['anon_key'],
                $config['secret_key'],
            );
        });

        $this->app->singleton(PostgrestClient::class, function (Application $app) {
            $config = $app['config']->get('supabase');

            return new PostgrestClient($config['url']);
        });

        $this->app->singleton(SupabaseSession::class, function (Application $app) {
            return new SupabaseSession(
                $app->make('session.store'),
                $app->make(GoTrueClient::class),
            );
        });
    }

    public function boot(): void
    {
        //
    }
}
