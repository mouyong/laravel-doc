<?php

use Illuminate\Database\Eloquent\Model;
use MouYong\LaravelDoc\Models\Config;

if (! function_exists('db_config')) {
    function db_config($itemKey = null): string|array|Model
    {
        $configModel = config('laravel-doc.config_model', Config::class);
        
        if (is_string($itemKey)) {
            return $configModel::getItemValueByItemKey($itemKey);
        }

        if (is_array($itemKey)) {
            return $configModel::getValueByKeys($itemKey);
        }

        return new $configModel();
    }
}
