<?php

namespace Inerba\DbConfig\Commands;

use Filament\Facades\Filament;
use Filament\Support\Commands\Concerns\CanOpenUrlInBrowser;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Pluralizer;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\suggest;
use function Laravel\Prompts\text;

#[AsCommand(name: 'make:db-config', aliases: [
    'db-config',
])]
/**
 * @method mixed argument(string|null $key = null)
 * @method void info(string $message)
 * @method void warn(string $message)
 */
class DbConfigCommand extends Command
{
    use CanOpenUrlInBrowser;

    public $signature = 'make:db-config {name?} {panel?}';

    public $description = 'Create a new Filament settings Page class and its Blade view. '
        . 'Usage: php artisan make:db-config [name?] [panel?] — generates '
        . 'app/Filament/{Panel}/Pages/{Name}Settings.php and '
        . 'resources/views/filament/config-pages/{name}.blade.php. '
        . 'If arguments are not provided, you will be prompted interactively. '
        . 'Existing files will not be overwritten.';

    /**
     * @var array<string>
     */
    protected $aliases = [
        'db-config',
    ];

    /**
     * Filesystem instance
     */
    protected Filesystem $files;

    /**
     * Cached name argument
     */
    protected ?string $cachedName = null;

    /**
     * Cached panel argument
     */
    protected ?string $cachedPanel = null;

    /**
     * Whether panel argument has been cached
     */
    protected bool $panelCached = false;

    /**
     * Create a new command instance.
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        // Collect input interactively if not provided
        $name = $this->getNameArgument();
        $panel = $this->getPanelArgument();

        $path = $this->getSourceFilePath();

        $this->makeDirectory(dirname($path));

        $contents = $this->getSourceFile();

        $this->createViewFromStub('filament.pages.' . Str::of($name)->headline()->lower()->slug() . '-settings');

        if ($contents === false) {
            $this->warn("Could not build source file contents for {$path}");

            return;
        }

        if (! $this->files->exists($path)) {
            $this->files->put($path, $contents);
            $this->info("File : {$path} created");
        } else {
            $this->warn("File : {$path} already exits");
        }

        $this->askToStar();
    }

    /**
     * Get the name argument interactively if not provided
     */
    protected function getNameArgument(): string
    {
        if ($this->cachedName !== null) {
            return $this->cachedName;
        }

        $name = $this->argument('name');

        if (! $name) {
            if ($this->option('no-interaction')) {
                $this->error('The name argument is required when using --no-interaction flag.');
                exit(1);
            }

            $name = text(
                label: 'What is the name of the settings page?',
                placeholder: 'E.g. Site, General, Mail',
                hint: 'This will generate a {Name}Settings page class.',
                required: true
            );
        }

        // Remove trailing "settings" suffix (case-insensitive) and trim whitespace
        $name = (string) Str::of($name)
            ->replaceMatches('/settings$/i', '')
            ->trim();

        return $this->cachedName = $name;
    }

    /**
     * Get the panel argument interactively if not provided
     */
    protected function getPanelArgument(): ?string
    {
        if ($this->panelCached) {
            return $this->cachedPanel;
        }

        $panel = $this->argument('panel');

        if (! $panel && ! $this->option('no-interaction')) {
            $availablePanels = $this->getAvailablePanels();

            if (count($availablePanels) < 2) {
                $this->panelCached = true;

                return $this->cachedPanel = null;
            }

            $panel = suggest(
                label: 'Which Filament panel should this settings page be created for?',
                options: $availablePanels,
                placeholder: 'Leave empty for default panel',
                hint: 'This will determine the directory structure for your settings page.'
            );

            // If user pressed enter without selecting anything, return null
            if (empty($panel)) {
                $panel = null;
            }
        }

        $this->panelCached = true;

        return $this->cachedPanel = $panel;
    }

    /**
     * Get available Filament panels from the app/Filament directory
     *
     * @return array<int,string> List of available panel names
     */
    protected function getAvailablePanels(): array
    {
        $panels = Filament::getPanels();

        return array_keys($panels);
    }

    /**
     * Create a new view file from the stub.
     *
     * @param  string  $viewName  The name of the view.
     */
    public function createViewFromStub(string $viewName): void
    {
        // Define the path to the view stub.
        $viewStubPath = __DIR__ . '/../../stubs/view.stub';

        // Define the path to the new view file.
        $newViewPath = \resource_path('views/' . str_replace('.', '/', $viewName) . '.blade.php');

        if ($this->files->exists($newViewPath)) {
            $this->warn("File : {$newViewPath} already exists");

            return;
        }

        // Read the contents of the view stub.
        $viewStubContents = file_get_contents($viewStubPath);

        if ($viewStubContents === false) {
            $this->warn("Unable to read view stub at {$viewStubPath}");

            return;
        }

        // Replace any variables in the stub contents.
        // In this example, we're replacing a variable named 'VIEW_NAME'.
        $viewContents = str_replace('$VIEW_NAME$', $viewName, $viewStubContents);

        // Create the directory for the new view file, if it doesn't already exist.
        $this->makeDirectory(dirname($newViewPath));

        // Write the view contents to the new view file using the Filesystem API.
        $this->files->put($newViewPath, $viewContents);

        $this->info("View file : {$newViewPath} created");
    }

    /**
     * Return the stub file path
     */
    public function getStubPath(): string
    {
        return __DIR__ . '/../../stubs/page.stub';
    }

    /**
     * Map the stub variables present in stub to its value
     *
     * @return array<string,string>
     */
    public function getStubVariables(): array
    {
        $name = $this->getNameArgument();
        $panel = $this->getPanelArgument();

        $singularClassName = $this->getSingularClassName($name);

        return [
            'TITLE' => Str::headline($singularClassName),
            'PANEL' => $panel ? ucfirst($panel) . '\\' : '',
            'CLASS_NAME' => $singularClassName,
            'SETTING_NAME' => Str::of($name)->headline()->lower()->slug(),
        ];
    }

    /**
     * Get the stub path and the stub variables
     *
     * @return string|false The generated source contents or false on failure
     */
    public function getSourceFile(): string | false
    {
        return $this->getStubContents($this->getStubPath(), $this->getStubVariables());
    }

    /**
     * Replace the stub variables(key) with the desire value
     *
     * @param  array<string,string>  $stubVariables
     */
    public function getStubContents(string $stub, array $stubVariables = []): string | false
    {
        $contents = file_get_contents($stub);

        if ($contents === false) {
            return false;
        }

        foreach ($stubVariables as $search => $replace) {
            $contents = str_replace('$' . $search . '$', $replace, $contents);
        }

        return $contents;
    }

    /**
     * Get the full path of the generated class.
     */
    public function getSourceFilePath(): string
    {
        $name = $this->getNameArgument();
        $panel = $this->getPanelArgument();

        $panelPrefix = $panel ? ucfirst($panel) . '\\' : '';

        $path = \base_path('app\\Filament\\' . $panelPrefix . 'Pages') . '\\' . $this->getSingularClassName($name) . 'Settings.php';

        return str_replace('\\', '/', $path);
    }

    /**
     * Return the Singular Capitalize Name
     */
    public function getSingularClassName(string $name): string
    {
        return ucwords(Pluralizer::singular($name));
    }

    /**
     * Build the directory for the class if necessary.
     */
    protected function makeDirectory(string $path): string
    {

        if (! $this->files->isDirectory($path)) {
            $this->files->makeDirectory($path, 0777, true, true);
        }

        return $path;
    }

    protected function askToStar(): void
    {
        if (! $this->input->isInteractive()) {
            return;
        }

        if (! confirm(
            label: '🚀 All set! If this tool saved you time, why not give it a ⭐ on GitHub? Your support helps keep it alive!',
            default: true,
        )) {
            return;
        }

        $this->openUrlInBrowser('https://github.com/inerba/filament-db-config');
    }
}
