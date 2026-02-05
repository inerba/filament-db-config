---
name: filament-db-config
description: >-
    Creates database-backed settings pages and config pages with filament-db-config or db-config package.
    Activates when creating settings page, config page, configuration page, or when user mentions db-config,
    db_config, DbConfig, database settings, dynamic configuration, runtime config, storing settings in database.
    ALWAYS use php artisan make:db-config command to scaffold. NEVER create files manually. NEVER create tests.
---

# Filament DB Config

## When to Apply

Activate this skill when:

- Creating settings pages or config pages
- Working with db-config, db_config(), DbConfig
- User mentions database settings, dynamic configuration
- User asks to store configuration in database

## Documentation

Use `search-docs` for Filament form components and patterns. Do NOT rely on training data — always check the installed Filament version documentation.

## Critical Workflow

STEP 1: Always scaffold with artisan command first:

```bash
php artisan make:db-config HomeSeo --no-interaction
```

IMPORTANT: Only pass the Name. NEVER add a second argument like "admin" or "panel". The command only takes one argument.
This generates `app/Filament/Pages/{Name}Settings.php`. The generator automatically adds "Settings" suffix.

STEP 2: Edit the generated file to add your fields in the `form()` method.

NEVER create files manually.

NEVER create tests. Tests are NOT needed for settings pages.

## After Scaffolding

Edit the generated page class to customize the `form()` method:

<code-snippet name="Settings Page Form Example" lang="php">
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

public function form(Schema $schema): Schema
{
return $schema
->components([
TextInput::make('site_name')->label('Site Name'),
TextInput::make('contact_email')->label('Contact Email'),
Toggle::make('maintenance_mode')->label('Maintenance Mode'),
])
->statePath('data');
}
</code-snippet>

## Default Values

Override `getDefaultData()` to pre-fill the form:

<code-snippet name="Default Values Example" lang="php">
public function getDefaultData(): array
{
    return [
        'posts_per_page' => 10,
        'allow_comments' => true,
    ];
}
</code-snippet>

## Reading Values

<code-snippet name="Reading db_config Values" lang="php">
// Standard helper
$siteName = db_config('website.site_name', 'Default');

// Safe helper (for early boot, migrations, service providers)
$siteName = safe_db_config('website.site_name', 'Default');
</code-snippet>

Blade directive:

<code-snippet name="Blade Directive" lang="blade">
<h1>@db_config('website.site_name', 'Default')</h1>
</code-snippet>

## Writing Values

<code-snippet name="Writing Values Programmatically" lang="php">
use Inerba\DbConfig\DbConfig;

DbConfig::set('website.site_name', 'Acme Inc.');
$group = DbConfig::getGroup('website');
</code-snippet>

## IMPORTANT: Do NOT Create Tests

Do NOT create any test files for db-config settings pages. Tests are NOT needed and NOT wanted unless the user explicitly asks for them.

## Filament Property Types

When setting Filament page properties, always use the correct union types:

<code-snippet name="Correct Property Types" lang="php">
protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-cog';
protected static ?string $navigationGroup = 'Settings';
protected static ?int $navigationSort = 10;
</code-snippet>

NEVER write `protected static string $navigationIcon` — always include `| BackedEnum | null`.

## Common Mistakes

- NEVER create files manually — always run `php artisan make:db-config` first
- NEVER create tests — no tests for settings pages unless explicitly requested
- NEVER use `config()` — always use `db_config()` helper
- NEVER forget `statePath('data')` — forms must have `->statePath('data')`
- NEVER forget union types — navigationIcon must be `string | BackedEnum | null`
