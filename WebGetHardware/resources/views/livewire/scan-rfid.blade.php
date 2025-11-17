<div>
    @extends('filament::components.layouts.app') {{-- pastikan ini layout yang benar --}}

    {{-- Form Input Data --}}
    <form wire:submit.prevent="save">
        {{-- Tampilkan form --}}
        {{ $this->form }}

        <div class="mt-6 flex gap-2">
            <x-filament::button type="submit" :disabled="empty($scannedUid)">
                Simpan
            </x-filament::button>

            <x-filament::button color="gray" type="button" onclick="rescanRfid()">
                Scan Ulang
            </x-filament::button>

            <x-filament::button color="gray" type="button" wire:click="cancel">
                Batal
            </x-filament::button>
        </div>
    </form>

    @push('scripts')
        <script>
            let pollingInterval = null;

            function startPollingRfid() {
                const scannedUid = @json($scannedUid);
                if (scannedUid) return;

                pollingInterval = setInterval(async () => {
                    try {
                        const response = await fetch('/rfid/latest');
                        const data = await response.json();
                        if (data.uid) {
                            Livewire.emit('setScannedUid', data.uid);
                            clearInterval(pollingInterval);
                        }
                    } catch (error) {
                        console.error('❌ Gagal polling UID:', error);
                    }
                }, 1000);
            }

            function rescanRfid() {
                Livewire.emit('resetScannedUid');
                startPollingRfid();
            }

            document.addEventListener('DOMContentLoaded', startPollingRfid);
        </script>
    @endpush
</div>
