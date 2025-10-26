<?php

declare(strict_types=1);

namespace Inerba\DbConfig;

use Filament\Notifications\Notification;
use RuntimeException;

trait DbConfigTrait
{
    /**
     * Returns the formatted last-updated timestamp for the settings group associated with this page.
     *
     * Accepts timezone and format parameters and returns a formatted string, or null if the
     * timestamp is not available.
     *
     * @param  string  $timezone  Timezone identifier, e.g., 'UTC', 'Europe/Rome'.
     * @param  string  $format  Date format string compatible with PHP's date() function.
     * @return string|null Formatted timestamp or null if not available.
     */
    public function lastUpdatedAt(string $timezone = 'UTC', string $format = 'F j, Y, g:i a'): ?string
    {
        return DbConfig::getGroupLastUpdatedAt($this->settingName(), $format, $timezone);
    }

    /**
     * Initializes the page state by loading persisted values for the settings group and merging them with defaults.
     *
     * The merged result is assigned to the internal `$data` property.
     * If the page defines a `$form` or `$content` property, it is filled with the merged data.
     */
    public function mount(): void
    {
        $db = DbConfig::getGroup($this->settingName()) ?? [];
        $defaults = $this->getDefaultData();

        // Merge defaults with DB values: DB values take precedence.
        $this->data = array_replace_recursive($defaults, $db);

        // Support both $this->content and $this->form for the schema instance.
        if (! isset($this->form)) {
            $this->form = $this->content;
        }

        $this->form->fill($this->data);
    }

    /**
     * Persists the current form state into the associated settings group.
     *
     * If `$this->form` is not set, `$this->content` is used as fallback. The method verifies at runtime
     * that the form instance exposes `getState()`; it iterates every key/value pair returned by `getState()`
     * and calls `DbConfig::set("{settingName}.{key}", $value)` to persist each value. A Filament
     * notification is sent upon successful completion.
     *
     * @throws RuntimeException When the form instance is missing or does not provide `getState()`.
     */
    public function save(): void
    {
        // Support both $this->content and $this->form for the schema instance.
        if (! isset($this->form)) {
            $this->form = $this->content;
        }

        if (! is_object($this->form) || ! method_exists($this->form, 'getState')) {
            throw new \RuntimeException('Expected $this->form to be an object exposing getState().');
        }

        /** @var array<string,mixed> $state */
        $state = $this->form->getState();

        collect($state)->each(function ($setting, $key) {
            DbConfig::set($this->settingName() . '.' . $key, $setting);
        });

        Notification::make()
            ->success()
            ->title(__('db-config::db-config.saved_title'))
            ->body(__('db-config::db-config.saved_body'))
            ->send();
    }
}
