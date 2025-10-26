<x-filament-panels::page>
    <form wire:submit="save" class="fi-page-content">
        {{ $this->form }}
        <div class="mt-6 flex items-end justify-between">
            <x-filament::button type="submit">
                {{ __('db-config::db-config.save') }}
            </x-filament::button>
            <small class="text-success">
                {{ __('db-config::db-config.last_updated') }}:
                {{ $this->lastUpdatedAt(timezone: 'UTC', format: 'F j, Y, H:i:s') . ' UTC' ?? 'Never' }}
            </small>
        </div>
    </form>
</x-filament-panels::page>