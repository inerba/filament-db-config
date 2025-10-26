<?php

declare(strict_types=1);

namespace Inerba\DbConfig;

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Widgets\Widget;

/**
 * Abstract widget for Filament settings widgets that persist a named configuration group.
 *
 * Loads default values, merges them with persisted data from the database, and provides
 * lifecycle helpers and a save routine that persists form state into the corresponding
 * configuration group.
 *
 * @property object|null $form Instance of the content (form/schema) used by the page.
 * @property object|null $content Instance of the content (form/schema) used by the page.
 */
abstract class AbstractWidgetSettings extends Widget implements HasForms
{
    use DbConfigTrait;
    use InteractsWithForms;

    /**
     * Data loaded from the DB config group.
     *
     * @var array<string,mixed>|null
     */
    public ?array $data = [];

    protected string $view = 'db-config::filament.widgets.default';

    abstract protected function settingName(): string;

    protected int | string | array $columnSpan = 2;

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
}
