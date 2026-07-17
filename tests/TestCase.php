<?php

namespace Inerba\DbConfig\Tests;

// CORE LARAVEL/TESTBENCH
use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
// FACADES and HELPERS
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
// SERVICE PROVIDERS
use Filament\Facades\Filament;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Panel;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Foundation\Application;
use Inerba\DbConfig\DbConfigServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;

 // Your package's provider

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            ActionsServiceProvider::class,
            BladeCaptureDirectiveServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeIconsServiceProvider::class,
            FilamentServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            LivewireServiceProvider::class,
            NotificationsServiceProvider::class,
            SupportServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            DbConfigServiceProvider::class,
        ];
    }

    /**
     * Definisci l'ambiente di test.
     *
     * @param  Application  $app
     */
    public function getEnvironmentSetUp($app)
    {
        // Usiamo un database SQLite in memoria: è velocissimo e non lascia file spazzatura.
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Register a default Filament panel so Filament pages can render in tests.
        Filament::registerPanel(
            Panel::make()->id('default')->default(true),
        );

        // Use a fixed application key derived from SHA-256 for reproducible CI runs.
        // hash(..., true) returns 32 bytes which base64-encodes to a valid AES-256 key.
        $app['config']->set('app.key', 'base64:' . base64_encode(hash('sha256', 'filament-db-config-test-key', true)));
    }
}
