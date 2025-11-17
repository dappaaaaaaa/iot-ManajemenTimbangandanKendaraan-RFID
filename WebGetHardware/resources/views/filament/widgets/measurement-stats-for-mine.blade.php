<x-filament::widget>
    <x-filament::card>
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <h2 class="text-lg font-bold text-center md:text-left">Mine Statistic Overview</h2>
            {{-- Form untuk pilih periode --}}
            <div class="w-1/4 md:w-auto ml-auto">
                {{ $this->form }}
            </div>
        </div>

        {{-- Spacer kecil --}}
        <div class="my-4 border-t"></div>

        {{-- Menampilkan Net Weight --}}
        <div class="text-center">
            <h2 class="text-lg font-semibold">Net Weight (Kg)</h2>
            <p class="text-3xl font-bold text-primary-600 mt-2">
                {{ number_format($netWeight, 0, ',', '.') }} Kg
            </p>
        </div>
    </x-filament::card>
</x-filament::widget>
