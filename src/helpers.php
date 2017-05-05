<?php

use Illuminate\Container\Container;
use Illuminate\Support\Str;

if ( ! function_exists('app')) {
    /**
     * Get the available container instance.
     *
     * @param string $abstract
     * @param array  $parameters
     *
     * @return mixed|\Illuminate\Foundation\Application
     */
    function app($abstract = null, array $parameters = [])
    {
        if (is_null($abstract)) {
            return Container::getInstance();
        }

        return empty($parameters)
            ? Container::getInstance()->make($abstract)
            : Container::getInstance()->makeWith($abstract, $parameters);
    }
}

if ( ! function_exists('env')) {
    /**
     * Gets the value of an environment variable.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    function env($key, $default = null)
    {
        $value = getenv($key);

        if ($value === false) {
            return value($default);
        }

        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return;
        }

        if (strlen($value) > 1 && Str::startsWith($value, '"') && Str::endsWith($value, '"')) {
            return substr($value, 1, -1);
        }

        return $value;
    }
}
