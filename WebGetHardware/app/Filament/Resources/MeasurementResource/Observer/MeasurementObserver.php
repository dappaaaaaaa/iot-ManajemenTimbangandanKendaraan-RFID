<?php

namespace App\Filament\Resources\MeasurementResource\Observer;

use App\Models\Measurement;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MeasurementObserver
{
    private bool $isRecordingHistory = false;

    public function creating(Measurement $measurement): void
    {
        $userId = Auth::id();
        $measurement->is_pending = false;
        $measurement->is_approved = true;
        $measurement->approved_by = $userId;
        $measurement->updated_by = $userId;

        if (!$this->isAccessor()) {
            $measurement->edited_by = $userId;
        }
    }

    public function saving(Measurement $measurement): void
    {
        $user = Auth::user();
        if ($user && $this->isAdmin($user) && $measurement->exists) {
            $original = $measurement->getOriginal();
            if (isset($original['updated_by'])) {
                $measurement->updated_by = $original['updated_by'];
            }
        }
    }

    public function updating(Measurement $measurement): void
    {
        if ($this->isRecordingHistory || !Auth::check()) return;

        if (property_exists($measurement, 'bypassObserver') && $measurement->bypassObserver === true) {
            Log::info('🚫 Observer dilewati karena bypassObserver = true');
            return;
        }

        $userId = Auth::id();
        $original = $measurement->getOriginal();
        $dirty = $measurement->getDirty();
        $changedFields = array_keys($dirty);

        $ignoredFields = ['updated_at', 'created_at', 'id'];
        $significantChanges = array_diff($changedFields, $ignoredFields);
        if (empty($significantChanges)) return;

        $isOperator = Auth::user()->hasRole('operator');
        $oldValues = array_intersect_key($original, array_flip($changedFields));
        $newValues = array_intersect_key($dirty, array_flip($changedFields));

        $measurement->updated_by = $userId;
        $action = 'updated';
        $actionBy = $userId;

        if ($isOperator) {
            $measurement->is_pending = true;
            $measurement->is_approved = null;
            $measurement->edited_by = $userId;

            // ✅ PASTIKAN pending_changes disimpan sebagai array
            if (is_string($newValues)) {
                $newValues = json_decode($newValues, true) ?? [];
            }
            // Jangan nested! Simpan langsung array perubahan yang penting
            // $measurement->pending_changes = (array) $newValues;

            Log::info('✅ [Operator] Simpan pending_changes sebagai array', [
                'type' => gettype($measurement->pending_changes),
                'value' => $measurement->pending_changes,
            ]);

            $this->recordHistory(
                $measurement,
                'updated',
                $oldValues,
                $newValues,
                'Perubahan diajukan oleh operator',
                $changedFields,
                $userId
            );
        } else {
            $measurement->temp_history_payload = [
                'action' => $action,
                'oldValues' => $oldValues,
                'newValues' => $newValues,
                'changedFields' => $changedFields,
                'actionBy' => $actionBy,
                'actionDescription' => '',
            ];

            if (array_key_exists('is_approved', $dirty)) {
                if ($dirty['is_approved'] === true) {
                    $measurement->approved_by = $userId;
                    $measurement->rejected_by = null;
                    $measurement->temp_history_payload['action'] = 'approved';
                    $measurement->temp_history_payload['actionDescription'] = 'Data disetujui oleh accessor';
                } elseif ($dirty['is_approved'] === false) {
                    $measurement->rejected_by = $userId;
                    $measurement->approved_by = null;
                    $measurement->temp_history_payload['action'] = 'rejected';
                    $measurement->temp_history_payload['actionDescription'] = 'Data ditolak oleh accessor';
                }
            }
        }
    }

    public function updated(Measurement $measurement): void
    {
        if ($this->isRecordingHistory || $measurement->wasRecentlyCreated) return;

        $data = $measurement->temp_history_payload ?? null;
        if (!$data) return;

        $this->isRecordingHistory = true;
        unset($measurement->temp_history_payload);

        $this->recordHistory(
            $measurement,
            $data['action'],
            $data['oldValues'],
            $data['newValues'],
            $data['actionDescription'] ?: 'Data diperbarui',
            $data['changedFields'],
            $data['actionBy']
        );

        $this->isRecordingHistory = false;
    }

    private function recordHistory(
        Measurement $measurement,
        string $action,
        array $oldValues,
        array $newValues,
        string $description,
        array $changedFields,
        $actionBy = null
    ): void {
        $ignored = [
            'updated_by',
            'approved_by',
            'rejected_by',
            'is_pending',
            'is_approved',
            'edited_by',
            'pending_changes',
            'updated_at',
            'created_at',
        ];

        $relevant = array_diff($changedFields, $ignored);
        if (empty($relevant)) return;

        $filteredOld = array_intersect_key($oldValues, array_flip($relevant));
        $filteredNew = array_intersect_key($newValues, array_flip($relevant));

        $actor = User::find($actionBy)?->name ?? Auth::user()?->name ?? 'Unknown';
        $actionNote = match ($action) {
            'approved' => "Disetujui oleh: $actor",
            'rejected' => "Ditolak oleh: $actor",
            default     => "Diedit oleh: $actor",
        };

        try {
            $measurement->histories()->create([
                'measurement_id' => $measurement->id,
                'user_id' => $actionBy ?: Auth::id(),
                'action' => $action,
                'description' => $description,
                'old_values' => $this->sanitizeArrayForJson($filteredOld),
                'new_values' => $this->sanitizeArrayForJson($filteredNew),
                'changed_fields' => array_values($relevant),
                'action_note' => $actionNote,
            ]);
        } catch (\Exception $e) {
            Log::error('Error recording measurement history: ' . $e->getMessage(), [
                'measurement_id' => $measurement->id,
                'action' => $action,
                'old_values' => $filteredOld,
                'new_values' => $filteredNew,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function sanitizeArrayForJson(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeArrayForJson($value);
            } elseif (is_string($value)) {
                $decoded = json_decode($value, true);
                $sanitized[$key] = $decoded !== null ? $decoded : $value;
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    private function isAccessor(): bool
    {
        return Auth::check() && Auth::user()->hasRole('accessor');
    }

    private function isAdmin($user): bool
    {
        return in_array($user->role ?? '', ['admin', 'accessor', 'supervisor']);
    }
}
