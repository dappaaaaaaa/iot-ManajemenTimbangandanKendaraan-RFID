<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GateHelper
{
    public static function openGate(): bool
    {
        try {
            $response = Http::timeout(5)->get("http://10.111.172.31/gate/open");

            if ($response->successful()) {
                Log::info("Gate opened successfully", $response->json());
                return true;
            }

            Log::error("Failed to open gate", ['response' => $response->body()]);
            return false;
        } catch (\Exception $e) {
            Log::error("Exception when opening gate", ['error' => $e->getMessage()]);
            return false;
        }
    }
}
