<div class="space-y-4 max-h-96 overflow-y-auto">
    @php
        $formatValue = function ($value) {
            if (is_bool($value)) {
                return $value ? '✓ Ya' : '✗ Tidak';
            }
            if (is_null($value)) {
                return '(kosong)';
            }
            if (is_array($value)) {
                return json_encode($value, JSON_UNESCAPED_UNICODE);
            }
            if (is_numeric($value)) {
                return number_format($value, 2, ',', '.');
            }
            return e($value);
        };

        $fieldLabels = [
            'gross_at_mine' => 'Berat Kotor Tambang',
            'gross_at_jetty' => 'Berat Kotor di Jetty',
            'loses' => 'Loses',
            'tare_at_jetty' => 'Berat Tara',
            'net_at_jetty' => 'Berat Bersih',
            'vehicle_number' => 'Nomor Kendaraan',
            'jetty_entry_time' => 'Waktu Masuk Jetty',
        ];
    @endphp

    @forelse ($record->histories()->with('user')->latest()->get() as $history)
        @php
            $user = e($history->user->name ?? 'Sistem');

            $action = strtolower($history->action);
            $badgeClass = match (true) {
                str_contains($action, 'approve') => 'bg-green-100 text-green-800',
                str_contains($action, 'reject') => 'bg-red-100 text-red-800',
                str_contains($action, 'update') => 'bg-blue-100 text-blue-800',
                str_contains($action, 'create') => 'bg-gray-100 text-gray-800',
                default => 'bg-gray-100 text-gray-800',
            };
        @endphp

        <div class="p-4 border border-gray-200 rounded-lg bg-white shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center space-x-2">
                    <span class="text-sm font-semibold text-gray-800">{{ $user }}</span>
                    <span
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeClass }}">
                        {{ ucfirst(str_replace('_', ' ', $action)) }}
                    </span>
                </div>
                <div class="text-xs text-gray-500">{{ $history->created_at->format('d M Y H:i:s') }}</div>
            </div>

            @if ($history->action_note)
                <div class="mb-2 p-2 bg-yellow-50 border border-yellow-200 rounded text-sm text-yellow-800">
                    <strong>Catatan:</strong> {{ $history->action_note }}
                </div>
            @endif

            @if (!empty($history->changed_fields) && !empty($history->old_values) && !empty($history->new_values))
                <div class="space-y-3">
                    <div class="text-sm font-semibold text-gray-800 border-b pb-1">📝 Detail Perubahan:</div>

                    @foreach ($history->changed_fields as $field)
                        @continue(!array_key_exists($field, $fieldLabels))

                        @php
                            $old = $history->old_values[$field] ?? null;
                            $new = $history->new_values[$field] ?? null;
                            $changed = json_encode($old) !== json_encode($new);
                            $fieldLabel = $fieldLabels[$field];
                        @endphp

                        <div class="border border-gray-300 rounded-lg p-3 bg-white shadow-sm">
                            <div class="flex items-center mb-2">
                                <span class="mr-2">{{ $changed ? '🔄' : '✓' }}</span>
                                <span class="text-sm font-semibold text-gray-800">{{ $fieldLabel }}</span>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                                <div class="space-y-1">
                                    <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Nilai
                                        Sebelumnya</div>
                                    <div
                                        class="p-2 bg-red-50 border border-red-200 rounded font-mono text-red-700 break-words">
                                        {{ $formatValue($old) }}
                                    </div>
                                </div>
                                <div class="space-y-1">
                                    <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Nilai
                                        Sesudahnya</div>
                                    <div
                                        class="p-2 bg-green-50 border border-green-200 rounded font-mono text-green-700 break-words">
                                        {{ $formatValue($new) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($history->description)
                <div class="mt-3 p-2 bg-blue-50 border border-blue-200 rounded">
                    <div class="text-sm text-blue-800">
                        <strong>Ringkasan:</strong> {{ $history->description }}
                    </div>
                </div>
            @endif
        </div>
    @empty
        <p class="text-gray-500 text-sm">Belum ada riwayat perubahan.</p>
    @endforelse
</div>
