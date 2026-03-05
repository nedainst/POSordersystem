<?php

namespace App\Helpers;

use App\Models\SiteSetting;

class SettingsHelper
{
    protected static $cache = [];

    public static function get($key, $default = null)
    {
        if (isset(static::$cache[$key])) {
            return static::$cache[$key];
        }

        $value = SiteSetting::get($key, $default);
        static::$cache[$key] = $value;
        return $value;
    }

    public static function clearCache()
    {
        static::$cache = [];
    }
}
