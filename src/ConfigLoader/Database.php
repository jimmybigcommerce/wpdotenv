<?php declare(strict_types=1);

namespace WpWildfire\ConfigLoader;

use WpWildfire\ConfigLoader;
use WpWildfire\ConfigLoaderInterface;

/**
 * Handles the loading of the Database configuration variables
 */
class Database implements ConfigLoaderInterface
{
    public function load(ConfigLoader $loader): void
    {
        $loader->addEnvValue('DB_CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8');
        $loader->addEnvValue('DB_COLLATE', $_ENV['DB_COLLATE'] ?? '');
        $loader->addVar('table_prefix', $_ENV['DB_TABLE_PREFIX'] ?? 'wp_');
        $loader->addEnvs([
            'DB_NAME',
            'DB_USER',
            'DB_PASSWORD',
            'DB_HOST'
        ]);
    }
}
