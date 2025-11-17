<x-filament::page>
    <div class="mb-4 text-sm text-gray-600">
        <strong>Role:</strong> {{ $this->getRole() }}
    </div>

    {{ $this->form }}

    <x-slot name="footer">
        <x-filament::button type="submit" form="edit-profile-form">
            {{ __('filament-panels::pages/auth/edit-profile.form.actions.save.label') }}
        </x-filament::button>
    </x-slot>
</x-filament::page>
