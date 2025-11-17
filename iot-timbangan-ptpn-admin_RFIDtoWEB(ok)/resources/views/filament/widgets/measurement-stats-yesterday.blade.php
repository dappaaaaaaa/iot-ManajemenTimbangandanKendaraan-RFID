<x-filament::widget>
    <x-filament::card>
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold">Kemarin</h2>
        </div>

        {{-- Spacer kecil --}}
        <div class="my-4 border-t"></div>

        {{-- Menampilkan Net Weight --}}
        <div class="text-center">
            <h2 class="text-lg font-semibold">Berat Bersih (Kg)</h2>
            <p class="text-3xl font-bold text-primary-600 mt-2">
                {{ number_format($netWeight, 0, ',', '.') }} Kg
            </p>
        </div>
    </x-filament::card>
</x-filament::widget>
