<x-filament::widget>
    <x-filament::card>
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold">RFID Terdaftar</h2>
        </div>

        {{-- Spacer kecil --}}
        <div class="my-4 border-t"></div>

        {{-- Menampilkan jumlah RFID --}}
        <div class="text-center">
            <h2 class="text-lg font-semibold">Jumlah kartu RFID yang terdaftar pada sistem</h2>
            <p class="text-3xl font-bold text-primary-600 mt-2">
                {{ number_format($rfid ?? 0) }}
            </p>
        </div>
    </x-filament::card>
</x-filament::widget>
