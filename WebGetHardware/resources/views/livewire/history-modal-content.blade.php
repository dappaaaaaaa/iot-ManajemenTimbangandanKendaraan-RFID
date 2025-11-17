<div>
    <x-filament::modal.heading>
        Riwayat Perubahan Data
    </x-filament::modal.heading>

    <ul class="space-y-2 mt-4">
        @forelse ($record->histories as $history)
            <li class="border-b pb-2">
                <div class="font-semibold">
                    {{ $history->user->name ?? 'Tidak diketahui' }}
                </div>
                <div class="text-sm text-gray-600">
                    {{ ucfirst($history->action) }} pada {{ $history->created_at->format('d M Y H:i') }}
                </div>
                @if ($history->description)
                    <div class="text-sm italic text-gray-500">
                        {{ $history->description }}
                    </div>
                @endif
            </li>
        @empty
            <li class="text-gray-500 italic">Tidak ada riwayat perubahan.</li>
        @endforelse
    </ul>
</div>
