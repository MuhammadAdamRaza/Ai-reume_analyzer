<?php

namespace App\Providers;

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
        // If an orphaned Vite hot file exists but Vite dev server is not running,
        // automatically clean it up so php artisan serve uses pre-built assets.
        if ($this->app->environment('local') && file_exists(public_path('hot'))) {
            $hotUrl = trim((string) @file_get_contents(public_path('hot')));
            if (!empty($hotUrl)) {
                $parsed = parse_url($hotUrl);
                $host = $parsed['host'] ?? '127.0.0.1';
                $port = $parsed['port'] ?? 5173;

                $fp = @fsockopen($host, (int) $port, $errno, $errstr, 0.1);
                if (!$fp) {
                    @unlink(public_path('hot'));
                } else {
                    fclose($fp);
                }
            }
        }
    }
}
