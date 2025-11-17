<?php
// File: app/Traits/AuditableMeasurement.php

namespace App\Traits;

use App\Models\MeasurementHistory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait AuditableMeasurement
{
    protected static function bootAuditableMeasurement()
    {
        static::created(function ($model) {
            $model->logHistory('created', [], $model->toArray());
        });

        static::updated(function ($model) {
            $original = $model->getOriginal();
            $changes = $model->getChanges();

            // Hapus timestamps dari changes
            unset($changes['updated_at']);

            if (!empty($changes)) {
                $model->logHistory('updated', $original, $changes, array_keys($changes));
            }
        });

        static::deleted(function ($model) {
            $model->logHistory('deleted', $model->toArray(), []);
        });
    }

    public function logHistory(string $action, array $oldValues = [], array $newValues = [], array $changedFields = [], string $notes = null)
    {
        $this->histories()->create([
            'user_id' => Auth::id(),
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'changed_fields' => $changedFields,
            'ip_address' => Request::ip(),
            'notes' => $notes,
        ]);
    }

    /**
     * Log approval action
     */
    public function logApproval(bool $approved, string $notes = null)
    {
        $action = $approved ? 'approved' : 'rejected';
        $this->logHistory($action, [], [], [], $notes);
    }

    /**
     * Log set pending action
     */
    public function logSetPending(string $notes = null)
    {
        $this->logHistory('set_pending', [], [], [], $notes);
    }
}
