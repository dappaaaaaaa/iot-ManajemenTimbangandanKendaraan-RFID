<?php

namespace App\Imports;

use App\Models\Measurement;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Filament\Notifications\Notification;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\ImportFailed;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class MeasurementsImport implements
    ToModel,
    WithHeadingRow,
    WithChunkReading,
    ShouldQueue,
    WithValidation,
    SkipsOnFailure,
    WithEvents,
    SkipsEmptyRows
{
    use SkipsFailures;

    protected int $importedRows = 0;
    protected int $userId;

    public function __construct(int $userId)
    {
        $this->userId = $userId;
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function model(array $row)
    {
        Log::debug('📥 Mulai memproses baris: ' . json_encode($row));

        if (collect($row)->filter()->isEmpty()) {
            Log::info('⚠️ Baris kosong dilewati.');
            return null;
        }

        try {
            $this->importedRows++;

            $created = Measurement::create([
                'vehicle_number'      => $row['vehicle_number'] ?? null,
                'gross_at_mine'       => $row['gross_at_mine'] ?? null,
                'tare_at_mine'        => $row['tare_at_mine'] ?? null,
                'gross_at_jetty'      => $row['gross_at_jetty'] ?? null,
                'tare_at_jetty'       => $row['tare_at_jetty'] ?? null,
                'jetty_entry_time'    => $this->formatExcelDate($row['jetty_entry_time'] ?? null),
                'measurement_status'  => Str::slug($row['measurement_status'] ?? 'unknown', '_'),
                'user_id'             => $this->userId,
            ]);

            Log::info("✅ [BERHASIL] Baris ke-{$this->importedRows}, ID: {$created->id}, Vehicle: {$created->vehicle_number}");
            return $created;
        } catch (\Exception $e) {
            Log::error("❌ [GAGAL] Baris ke-{$this->importedRows}, Vehicle: " . ($row['vehicle_number'] ?? 'null'));
            Log::error('📄 Data Baris: ' . json_encode($row));
            Log::error('💥 Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Format semua jenis tanggal dan waktu dari Excel
     */
    private function formatExcelDate($value)
    {
        try {
            if (is_null($value)) return null;

            // Format bawaan Excel (serial number)
            if (is_numeric($value)) {
                return Date::excelToDateTimeObject($value)->format('Y-m-d H:i:s');
            }

            // Format teks umum (manual input atau sel format teks)
            $formats = [
                'Y-m-d H:i:s',
                'Y-m-d H:i',
                'Y-m-d',
                'd-m-Y H:i:s',
                'd-m-Y H:i',
                'd-m-Y',
                'd/m/Y H:i:s',
                'd/m/Y H:i',
                'd/m/Y',
                'm/d/Y H:i:s',
                'm/d/Y H:i',
                'm/d/Y',
            ];

            foreach ($formats as $format) {
                try {
                    return Carbon::createFromFormat($format, $value)->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    continue;
                }
            }

            // Fallback parsing otomatis
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            Log::error('❌ Gagal parsing tanggal: ' . json_encode($value));
            return null;
        }
    }

    public function rules(): array
    {
        return [
            '*.vehicle_number'     => 'required|string|max:20',
            '*.gross_at_mine'      => 'nullable|numeric',
            '*.tare_at_mine'       => 'nullable|numeric',
            '*.gross_at_jetty'     => 'nullable|numeric',
            '*.tare_at_jetty'      => 'nullable|numeric',
            '*.jetty_entry_time'   => ['nullable', function ($attribute, $value, $fail) {
                $isExcelSerial = is_numeric($value);
                $isParsable = strtotime($value) !== false;

                if (!$isExcelSerial && !$isParsable) {
                    $fail("Kolom $attribute harus berupa tanggal yang valid atau Excel serial number.");
                }
            }],
            '*.measurement_status' => 'nullable|string|max:50',
        ];
    }

    public function registerEvents(): array
    {
        return [
            ImportFailed::class => function (ImportFailed $event) {
                Log::critical('❌ Import gagal total: ' . $event->getException()->getMessage());
            },

            AfterImport::class => function (AfterImport $event) {
                $import = $event->getConcernable();
                $count = $import->importedRows ?? 0;

                Log::info("✅ Import selesai. Total baris berhasil diproses: {$count}");

                Notification::make()
                    ->title("Import selesai: {$count} baris berhasil disimpan.")
                    ->success()
                    ->send();
            },
        ];
    }

    public function onFailure(...$failures)
    {
        foreach ($failures as $failure) {
            Log::warning('⚠️ Gagal validasi pada baris ' . $failure->row() . ': ' . json_encode($failure->errors()));
        }
    }
}
