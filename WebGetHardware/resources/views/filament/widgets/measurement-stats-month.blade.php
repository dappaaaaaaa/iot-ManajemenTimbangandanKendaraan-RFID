<x-filament::widget>
    <x-filament::card>
        <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
            <h2 class="text-lg font-bold whitespace-nowrap">Bulan Ini</h2>

            <form wire:submit.prevent="updateDateRange" class="flex gap-3 items-end">
                <div class="w-48">
                    {{ $this->form->getComponent('startDateTime') }}
                </div>
                <div class="w-48">
                    {{ $this->form->getComponent('endDateTime') }}
                </div>
                <div>
                    <x-filament::button type="submit" class="h-36">
                        Tampilkan
                    </x-filament::button>
                </div>
            </form>
        </div>

        <div class="my-4 border-t"></div>

        <div class="text-center">
            <h2 class="text-lg font-semibold">Berat Bersih (Kg)</h2>
            <p class="text-3xl font-bold text-primary-600 mt-2">
                {{ number_format($netWeight, 0, ',', '.') }} Kg
            </p>
        </div>
    </x-filament::card>
</x-filament::widget>
