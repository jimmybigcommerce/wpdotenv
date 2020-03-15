<?php declare(strict_types=1);

namespace WpWildfire;

/**
 * An interface that all custom config loader classes must implement to allow
 * auto-wiring from the WORDPRESS_ENV_LOADERS variable in the .env file
 */
interface ConfigLoaderInterface
{
    public function load(ConfigLoader $loader);
}