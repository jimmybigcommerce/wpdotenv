<?php

/** Composer auto-loader */
require_once(__DIR__ . '/vendor/autoload.php');

/** Call the loader to process the env file into Wordpress configuration */
$loader = new WpWildfire\ConfigLoader(__DIR__.'/.env');
foreach ($loader->load() as $global_var => $value) {
    $$global_var = $value;
}

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', dirname( __FILE__ ) . '/wordpress/' );
}

/** Sets up WordPress vars and included files. */
require_once( ABSPATH . 'wp-settings.php' );

/** Handle any callbacks to execute post wp-settings load behaviors */
$loader->invokeCallbacks();
