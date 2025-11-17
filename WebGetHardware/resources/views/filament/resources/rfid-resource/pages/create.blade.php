<x-filament::page>
    <div x-data="{ waiting: false }" x-init="$watch('waiting', v => $wire.set('waitingUid', v))" class="space-y-4">
        <h2 class="text-xl font-bold">Tambah Kartu RFID</h2>

        <template x-if="waiting">
            <div class="p-4 bg-yellow-100 rounded">Tempelkan kartu RFID…</div>
        </template>

        <x-filament::button x-show="!waiting" x-on:click="waiting = true; $wire.startScan()" color="primary">
            Tap Kartu
        </x-filament::button>

        {{ $this->form }}

        {{ $this->getRenderedActions() }}
    </div>
</x-filament::page>
