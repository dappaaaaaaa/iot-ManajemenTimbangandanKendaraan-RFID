<?php

namespace App\Filament\Resources\MeasurementResource\Pages;

use App\Models\User;
use Filament\Actions;
use App\Models\MeasurementHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\MeasurementResource;

class EditMeasurement extends EditRecord
{
    protected static string $resource = MeasurementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Mengecek apakah user memiliki role tertentu
     */
    private function userHasRole(string $role): bool
    {
        $user = Auth::user();

        if (!$user) return false;

        if (method_exists($user, 'hasRole')) {
            return $user->hasRole($role);
        }

        return DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_has_roles.model_type', User::class)
            ->where('model_has_roles.model_id', $user->id)
            ->where('roles.name', $role)
            ->exists();
    }

    /**
     * Mutasi data sebelum simpan
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->userHasRole('operator')) {
            $record = $this->record;

            $criticalFields = [
                'vehicle_number',
                'gross_at_mine',
                'tare_at_mine',
                'gross_at_jetty',
                'tare_at_jetty',
                'jetty_entry_time',
                'attachments',
            ];

            $changes = [];
            foreach ($criticalFields as $field) {
                if (array_key_exists($field, $data) && $data[$field] != $record->{$field}) {
                    $changes[$field] = $data[$field];
                    $data[$field] = $record->{$field}; // Batalkan update langsung
                }
            }


            // di dalam mutateFormDataBeforeSave
            if (!empty($changes)) {
                $oldValues = $record->only(array_keys($changes));

                $data['is_pending'] = true;
                $data['is_approved'] = null;
                $data['edited_by'] = Auth::id();

                unset($changes['pending_changes']); // Pastikan tidak nested
                $data['pending_changes'] = $changes;

                Log::info('✅ FINAL pending_changes (should be flat): ' . json_encode($changes));

                MeasurementHistory::create([
                    'measurement_id' => $record->id,
                    'user_id' => Auth::id(),
                    'action' => 'edit_pending',
                    'old_values' => $oldValues,
                    'new_values' => $changes,
                    'changed_fields' => array_keys($changes),
                    'description' => 'Perubahan diajukan oleh operator',
                ]);
            }
        }

        return $data;
    }

    /**
     * Notifikasi setelah simpan
     */
    protected function afterSave(): void
    {
        $user = Auth::user();
        $roleNames = [];

        if ($user) {
            $roleNames = DB::table('model_has_roles')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->where('model_has_roles.model_type', User::class)
                ->where('model_has_roles.model_id', $user->id)
                ->pluck('roles.name')
                ->toArray();
        }

        Log::info('[EditMeasurement@afterSave] User ID: ' . ($user?->id ?? '-') . ' Role: ' . json_encode($roleNames));

        if (in_array('operator', $roleNames)) {
            Notification::make()
                ->title('Perubahan disimpan sebagai draft')
                ->body('Menunggu konfirmasi dari Accessor.')
                ->success()
                ->duration(5000)
                ->send();
        } else {
            Notification::make()
                ->title('Perubahan berhasil disimpan')
                ->success()
                ->duration(4000)
                ->send();
        }
    }
}
