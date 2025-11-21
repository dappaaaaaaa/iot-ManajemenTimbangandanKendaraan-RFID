<?php

namespace App\Filament\Resources\MeasurementResource\Observer;

use App\Models\Measurement;
use Illuminate\Support\Facades\Auth;

class MeasurementObserver
{
    public function creating(Measurement $measurement): void
    {
        $measurement->user_id = $measurement->user_id ?? Auth::id();
    }
}
