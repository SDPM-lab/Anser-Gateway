<?php

namespace AnserGateway\Config;

class BaseConfig
{
    public function __construct()
    {
        // 取得class中的所有元素
        $properties  = array_keys(get_object_vars($this));
        // 對應的namespace class名稱
        $prefix      = static::class;
        // 切割class名稱，取得class名稱前的文字數量
        $slashAt     = strrpos($prefix, '\\');
        // class名稱
        $shortPrefix = strtolower(substr($prefix, $slashAt === false ? 0 : $slashAt + 1));

        foreach ($properties as $property) {
            $this->initEnvValue($this->{$property}, $property, $prefix, $shortPrefix);
        }
    }

    /**
     * Initialization an environment-specific configuration setting
     *
     * @param array|bool|float|int|string|null $property
     *
     * @return void
     */
    protected function initEnvValue(&$property, string $name, string $prefix, string $shortPrefix)
    {
        if (is_array($property)) {
            foreach (array_keys($property) as $key) {
                $this->initEnvValue($property[$key], "{$name}.{$key}", $prefix, $shortPrefix);
            }
        } elseif (($value = $this->getEnvValue($name, $prefix, $shortPrefix)) !== false && $value !== null) {
            if ($value === 'false') {
                $value = false;
            } elseif ($value === 'true') {
                $value = true;
            }
            if (is_bool($value)) {
                $property = $value;

                return;
            }

            $value = trim($value, '\'"');

            if (is_int($property)) {
                $value = (int) $value;
            } elseif (is_float($property)) {
                $value = (float) $value;
            }

            $property = $value;
        }
    }

    /**
     * Retrieve an environment-specific configuration setting
     *
     * @return string|null
     */
    protected function getEnvValue(string $property, string $prefix, string $shortPrefix)
    {
        $shortPrefix        = ltrim($shortPrefix, '\\');
        $underscoreProperty = str_replace('.', '_', $property);

        switch (true) {
            case array_key_exists("{$shortPrefix}.{$property}", $_ENV):
                return $_ENV["{$shortPrefix}.{$property}"];

            case array_key_exists("{$shortPrefix}_{$underscoreProperty}", $_ENV):
                return $_ENV["{$shortPrefix}_{$underscoreProperty}"];

            case array_key_exists("{$shortPrefix}.{$property}", $_SERVER):
                return $_SERVER["{$shortPrefix}.{$property}"];

            case array_key_exists("{$shortPrefix}_{$underscoreProperty}", $_SERVER):
                return $_SERVER["{$shortPrefix}_{$underscoreProperty}"];

            case array_key_exists("{$prefix}.{$property}", $_ENV):
                return $_ENV["{$prefix}.{$property}"];

            case array_key_exists("{$prefix}_{$underscoreProperty}", $_ENV):
                return $_ENV["{$prefix}_{$underscoreProperty}"];

            case array_key_exists("{$prefix}.{$property}", $_SERVER):
                return $_SERVER["{$prefix}.{$property}"];

            case array_key_exists("{$prefix}_{$underscoreProperty}", $_SERVER):
                return $_SERVER["{$prefix}_{$underscoreProperty}"];

            default:
                $value = getenv("{$shortPrefix}.{$property}");
                $value = $value === false ? getenv("{$shortPrefix}_{$underscoreProperty}") : $value;
                $value = $value === false ? getenv("{$prefix}.{$property}") : $value;
                $value = $value === false ? getenv("{$prefix}_{$underscoreProperty}") : $value;

                return $value === false ? null : $value;
        }
    }
}
