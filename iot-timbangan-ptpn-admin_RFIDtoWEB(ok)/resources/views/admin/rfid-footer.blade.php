<x-filament::page>
    @parent

    @push('scripts')
        <script src="{{ asset('js/rfid-poller.js') }}"></script>
    @endpush
</x-filament::page>
