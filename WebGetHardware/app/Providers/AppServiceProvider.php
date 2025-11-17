<?php

namespace App\Providers;

use App\Models\Measurement;
use Illuminate\Support\ServiceProvider;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;
use App\Filament\Resources\MeasurementResource\Observer\MeasurementObserver;


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
        HeadingRowFormatter::default('none');
        Measurement::observe(MeasurementObserver::class);
    }
}
