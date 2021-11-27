<?php declare(strict_types=1);

namespace WpWildfire\ConfigLoader;

use WpWildfire\ConfigLoader;
use WpWildfire\ConfigLoaderInterface;

/**
 * Example config loader which disables dangerous admin functions.
 * This is not wired up, it is just here as an example of how this can be used
 * You would enable this by adding the following two values to your .env file:
 *
 * WORDPRESS_ENV_LOADERS=WpWildfire\ConfigLoader\AdminLockdown
 * LOCKDOWN=true
 *
 */
class AdminLockdown implements ConfigLoaderInterface
{
    public function load(ConfigLoader $loader): void
    {
        // If the LOCKDOWN value is set to true in .env, then invoke our custom behavior
        if (isset($_ENV['LOCKDOWN']) && (bool)$_ENV['LOCKDOWN']) {
            $loader->addEnvValue('WP_AUTO_UPDATE_CORE', false);
            $loader->addEnvValue('AUTOMATIC_UPDATER_DISABLED', true);
            $loader->addEnvValue('DISALLOW_FILE_EDIT', true);
            $loader->addEnvValue('DISALLOW_FILE_MODS', true);
            $loader->addCallback(function(){
                // Prevent users disabling plugins by removing the deactivate link.
                \add_filter('plugin_action_links', function ($actions, $plugin_file, $plugin_data, $context) {
                    if (array_key_exists('deactivate', $actions)) {
                        unset($actions['deactivate']);
                    }
                    return $actions;
                }, 10, 4);
            });
        }
    }

}
