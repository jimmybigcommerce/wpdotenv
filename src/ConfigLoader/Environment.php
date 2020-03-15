<?php declare(strict_types=1);

namespace WpWildfire\ConfigLoader;

use WpWildfire\ConfigLoader;
use WpWildfire\ConfigLoaderInterface;

/**
 * Handles the loading of all the standard Wordpress configuration variables
 */
class Environment implements ConfigLoaderInterface
{
    public function load(ConfigLoader $loader)
    {
        $loader->addEnvValue('WP_DEBUG', isset($_ENV['WP_DEBUG']) ? (bool)$_ENV['WP_DEBUG'] : false);
        $loader->addEnvs([
            'AUTH_KEY',
            'SECURE_AUTH_KEY',
            'LOGGED_IN_KEY',
            'NONCE_KEY',
            'AUTH_SALT',
            'SECURE_AUTH_SALT',
            'LOGGED_IN_SALT',
            'NONCE_SALT'
        ]);
    }
}
