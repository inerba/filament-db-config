<?php

declare(strict_types=1);

namespace Inerba\DbConfig;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Manages reading, writing, and caching of configuration settings stored in the database.
 *
 * Retrieves configuration entries from a configurable table, uses the application cache
 * with a configurable TTL, and provides helpers to fetch all settings for a group and
 * to obtain the group's last updated timestamp.
 */
class DbConfig
{
    /**
     * Retrieve a configuration value from the database, using cache when available.
     *
     * Accepts dotted keys in the form "group.setting" or "group.setting.subkey".
     * Returns the provided default when the key is absent.
     *
     * @param  string  $key  The configuration key.
     * @param  mixed  $default  The default value to return if the configuration key is not found.
     * @return mixed The configuration value.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        [$group, $setting, $subKey] = static::parseKey($key);

        $cacheKey = static::getCacheKey($group, $setting);
        $cacheTtl = config('db-config.cache.ttl');

        $callback = fn () => static::fetchConfig($group, $setting);

        // Use remember() with TTL if provided, otherwise rememberForever()
        $data = ($cacheTtl > 0)
            ? Cache::remember($cacheKey, $cacheTtl * 60, $callback)
            : Cache::rememberForever($cacheKey, $callback);

        $value = data_get($data, $subKey, $default);

        return $value ?? $default;
    }

    /**
     * Store a configuration value in the database and invalidate the related cache.
     *
     * The key must use dotted notation "group.setting". The value is JSON-serialized
     * before being persisted.
     *
     * @param  string  $key  The configuration key.
     * @param  mixed  $value  The configuration value.
     */
    public static function set(string $key, mixed $value): void
    {
        [$group, $setting] = static::parseKey($key);

        $cacheKey = static::getCacheKey($group, $setting);

        Cache::forget($cacheKey);

        static::storeConfig($group, $setting, $value);
    }

    /**
     * Retrieve all settings for a specific group from the storage table.
     *
     * Returns an associative array where keys are setting names and values are the
     * decoded JSON values or null.
     *
     * @param  string  $group  The group name.
     * @return array<string, mixed|null>|null Associative array mapping setting keys to decoded values or null; returns an empty array if the group has no settings.
     */
    public static function getGroup(string $group): ?array
    {
        $settings = [];

        $tableName = config('db-config.table_name', 'db_config');

        DB::table($tableName)->where('group', $group)->get()->each(function (\stdClass $setting) use (&$settings) {
            $settings[$setting->key] = json_decode($setting->settings, true);
        });

        return $settings;
    }

    /**
     * Return the formatted last updated timestamp for a specific group.
     *
     * Performs an aggregated query to obtain the maximum `updated_at` timestamp
     * for the requested group and returns it formatted in the requested timezone
     * and format. Returns null if no timestamp is available or on parse errors.
     *
     * usage: DbConfig::getLastUpdated('general');
     *
     * @param  string  $group  The group name.
     * @param  string  $format  The date format (default: 'F j, Y, g:i a').
     * @param  string  $timezone  The timezone (default: 'UTC').
     * @return string|null The formatted last updated timestamp or null if not found.
     */
    public static function getGroupLastUpdatedAt(string $group, string $format = 'F j, Y, g:i a', string $timezone = 'UTC'): ?string
    {
        $tableName = config('db-config.table_name', 'db_config');

        // Un'unica query con aggregazione
        $timestamp = DB::table($tableName)
            ->where('group', $group)
            ->max('updated_at');

        if (empty($timestamp)) {
            return null;
        }

        try {
            $fromTz = config('app.timezone', 'UTC');
            $dt = $timestamp instanceof Carbon
                ? $timestamp
                : Carbon::parse($timestamp, $fromTz);

            return $dt->setTimezone($timezone)->translatedFormat($format);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Parses a given key and returns an array containing the group and setting.
     *
     * @param  string  $key  The key to be parsed.
     * @return array{0:string,1:?string,2:string} [group, setting, subKey]
     */
    protected static function parseKey(string $key): array
    {
        $keyParts = explode('.', $key);
        $group = array_shift($keyParts);
        $setting = $keyParts[0] ?? null;
        $subKey = implode('.', $keyParts);

        return [$group, $setting, $subKey];
    }

    /**
     * Fetch configuration data for a specific group and setting from the database.
     *
     * @param  string  $group  The group name.
     * @param  string  $setting  The setting name.
     * @return array<string, mixed>
     */
    protected static function fetchConfig(string $group, string $setting): array
    {
        $tableName = config('db-config.table_name', 'db_config');

        /** @var \stdClass|null $item */
        $item = DB::table($tableName)
            ->where('group', $group)
            ->where('key', $setting)
            ->first();

        if ($item === null || ! property_exists($item, 'settings')) {
            return [];
        }

        return [
            $setting => json_decode($item->settings, true),
        ];
    }

    /**
     * Persist a configuration value for a given group and setting into the storage table.
     *
     * The provided value is JSON-encoded before persisting. A RuntimeException is thrown
     * if encoding fails. This method also updates timestamps and performs an upsert.
     *
     * @param  string  $group  The group name.
     * @param  string  $setting  The setting name.
     * @param  mixed  $value  The value to be stored.
     */
    protected static function storeConfig(string $group, string $setting, mixed $value): void
    {
        $tableName = config('db-config.table_name', 'db_config');

        try {
            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \RuntimeException('Unable to serialize value to JSON: ' . $e->getMessage(), 0, $e);
        }

        DB::table($tableName)->upsert(
            [
                [
                    'group' => $group,
                    'key' => $setting,
                    'settings' => $encoded,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ],
            ['group', 'key'], // unique by
            ['settings', 'updated_at'] // columns to update on duplicate
        );
    }

    /**
     * Build the cache key used to store/fetch cached settings for a group and setting.
     *
     * The prefix is read from configuration and combined with group and setting
     * to produce a namespaced cache key string.
     *
     * @param  string  $group  The group name.
     * @param  string  $setting  The setting name.
     * @return string The constructed cache key.
     */
    protected static function getCacheKey(string $group, string $setting): string
    {
        $prefix = config('db-config.cache.prefix', 'db-config');

        return "{$prefix}.{$group}.{$setting}";
    }
}
