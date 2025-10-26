<?php

declare(strict_types=1);

namespace Inerba\DbConfig;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;

/**
 * Abstract base page for Filament settings pages that persist a named configuration group.
 *
 * Loads default values, merges them with persisted data from the database, and provides
 * lifecycle helpers and a save routine that persists form state into the corresponding
 * configuration group.
 *
 * @property object|null $form Instance of the content (form/schema) used by the page.
 * @property object|null $content Instance of the content (form/schema) used by the page.
 */
abstract class AbstractPageSettings extends Page
{
    use DbConfigTrait;

    /**
     * Data loaded from the DB config group.
     *
     * @var array<string,mixed>|null
     */
    public ?array $data = [];

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    /**
     * Returns the navigation group label used by the Filament UI to group this page.
     *
     * The value is retrieved from translation resources and may be null when a translation
     * is not defined.
     */
    public static function getNavigationGroup(): ?string
    {
        return __('db-config::db-config.navigation_group');
    }

    abstract protected function settingName(): string;

    /**
     * Returns the default data used to initialize the page state.
     *
     * These defaults are merged with persisted values; persisted values take precedence.
     *
     * @return array<string, mixed> Array of default values keyed by setting name.
     */
    public function getDefaultData(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label(__('db-config::db-config.save'))
                ->keyBindings(['mod+s'])
                ->action(fn () => $this->save()),
        ];
    }
}
