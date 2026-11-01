<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
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
        Blade::directive('displayNumber', static fn (string $expression): string =>
            "<?php echo e(\\App\\Support\\DisplayFormat::number({$expression})); ?>"
        );
        Blade::directive('displayDateTime', static fn (string $expression): string =>
            "<?php echo e(\\App\\Support\\DisplayFormat::dateTime({$expression})); ?>"
        );
    }
}
