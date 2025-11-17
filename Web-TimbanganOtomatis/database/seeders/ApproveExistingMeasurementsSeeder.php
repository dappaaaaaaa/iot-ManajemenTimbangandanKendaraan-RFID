<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Measurement;

class ApproveExistingMeasurementsSeeder extends Seeder
{
    public function run(): void
    {
        $approvedBy = 1; // Ganti dengan ID user accessor yang valid

        $affected = Measurement::where(function ($query) {
            $query->whereNull('is_approved')
                ->orWhere('is_approved', false);
        })
            ->update([
                'is_approved' => true,
                'is_pending' => false,
                'approved_by' => $approvedBy,
            ]);

        $this->command->info("✅ $affected data Measurement berhasil di-setujui.");
    }
}
