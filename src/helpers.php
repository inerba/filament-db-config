<?php

declare(strict_types=1);

if (! function_exists('db_config')) {
    /**
     * Retrieve a configuration value from the database.
     *
     * @param  string  $key  The configuration key.
     * @param  mixed  $default  The default value to return if the configuration key is not found.
     * @return mixed The configuration value.
     */
    function db_config(string $key, mixed $default = null): mixed
    {
        return \Inerba\DbConfig\DbConfig::get($key, $default);
    }

    /**
     * Retrieve a configuration value from the database safely with exception handling.
     *
     * This is a fault-tolerant wrapper around db_config() that catches any exceptions
     * (e.g., database connection errors, table not found, or serialization issues)
     * and gracefully returns the default value instead of propagating the error.
     *
     * Use this helper when you want to prevent application crashes due to configuration
     * access failures, especially during bootstrapping, migrations, or in environments
     * where the database might be temporarily unavailable.
     *
     * @param  string  $key  The configuration key in 'group.setting' format.
     * @param  mixed  $default  The fallback value to return if an error occurs or the key is not found.
     * @return mixed The configuration value or the default value on error.
     */
    function safe_db_config(string $key, mixed $default = null): mixed
    {
        try {
            return \Inerba\DbConfig\DbConfig::get($key, $default);
        } catch (\Exception $e) {
            return $default;
        }
    }
}
