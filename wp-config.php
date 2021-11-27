<?php

/** Composer auto-loader */
require_once(__DIR__ . '/vendor/autoload.php');

/**
 * If a .env file exists in the root, process into ENV vars
 */
if (file_exists(__DIR__.'/.env')) {
    (new Symfony\Component\Dotenv\Dotenv())->load(__DIR__.'/.env');
}

/**
 * Call the config loader to process environment variables
 * into Wordpress configuration
 */
$wp_config_loader = new WpWildfire\ConfigLoader();
foreach ($wp_config_loader->load() as $global_var => $value) {
    $$global_var = $value;
}

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', dirname( __FILE__ ) . '/wordpress/' );
}

/** Sets up WordPress vars and included files. */
require_once( ABSPATH . 'wp-settings.php' );

/** Handle any callbacks to execute post wp-settings load behaviors */
$wp_config_loader->invokeCallbacks();
