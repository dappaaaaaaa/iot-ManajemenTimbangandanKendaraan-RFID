<x-filament::modal.heading>
    Histori Perubahan
</x-filament::modal.heading>

<div class="space-y-4">
    @forelse ($record->editHistories as $history)
        <div class="border p-4 rounded shadow-sm bg-white">
            <div><strong>Field:</strong> {{ $history->field }}</div>
            <div><strong>Dari:</strong> {{ $history->old_value }}</div>
            <div><strong>Menjadi:</strong> {{ $history->new_value }}</div>
            <div><strong>Oleh:</strong> {{ $history->user?->name ?? 'Tidak diketahui' }}</div>
            <div><strong>Tanggal:</strong> {{ $history->created_at->format('d M Y, H:i') }}</div>
        </div>
    @empty
        <div>Tidak ada histori edit.</div>
    @endforelse
</div>
