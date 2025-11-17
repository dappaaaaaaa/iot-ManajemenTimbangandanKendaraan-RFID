<x-filament::widget>
    <x-filament::card class="w-full p-6">
        <div class="w-full text-center">
            <h2 class="text-lg font-semibold whitespace-nowrap">
                Total Bersih di Jetty
            </h2>
            <p class="text-xl font-bold text-primary">
                {{ number_format($netAtJetty, 2) }} kg
            </p>
        </div>
    </x-filament::card>
</x-filament::widget>
