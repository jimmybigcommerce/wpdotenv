# WP Dotenv

WP Dotenv enables Wordpress instances to be completely 
configured using .env files and/or environment variables, 
by mapping the environment variables to the constants and variables required by Wordpress.

This is helpful for containerising Wordpress and for managing
configuration values without needing to edit/maintain a wp-config.php
which contains secrets.

## What? / Why?

Configuring multiple Wordpress environments (eg dev/staging/production) 
by maintaining separate wp-config.php files per environment (or worse, using
conditional logic in wp-config.php to define each environment)
is messy and unmaintainable. It makes modern CI/CD, automated builds and 
containerisation difficult. 

Also, wp-config.php has become a dumping ground for settings, 
configuration and callbacks for themes and plugins. It is not unusual to
end up with a very long and complex wp-config.php file which also cannot
be committed to your source respository due to containing secrets/credentials.

WP Dotenv enables all Wordpress configuration to be managed simply
by placing all configuration variables into a .env file

It is completely extendable using an OO approach - so plugins, themes and
custom code can register their own loaders which can define mappings from
the environment files to PHP contants and/or global PHP variables. They
can also register callbacks to invoke any 
[Wordpress function](https://developer.wordpress.org/reference/functions/) 
after the settings are included (another common use case for wp-config.php). 

## Features:

* Configure Wordpress with .env files
* Installable via Composer
* Universal wp-config.php which can be safely committed
* Allows extended configuration for themes, plugins and custom code
using classes, including the ability to add hooks for Wordpress functions
after the wp-settings.php file has been included.

## Installation:

Install with Composer :

```bash
$ composer require wpwildfire/wpdotenv
```

Then either copy and paste, or symlink the reference `wp-config.php` 
file to your wordpress root from:
```
./vendor/wpwildfire/wpdotenv/wp-config.php
```
(you can safely commit this wp-config.php to your if you copy and paste it
into your project)

Finally create a `.env` file in the root of your project with your Wordpress
settings:
```bash
DB_NAME=dbname
DB_USER=dbusername
DB_PASSWORD=dbpassword
DB_HOST=mysql
DB_TABLE_PREFIX=wp_
AUTH_KEY='put your unique phrase here'
SECURE_AUTH_KEY='put your unique phrase here'
LOGGED_IN_KEY='put your unique phrase here'
NONCE_KEY='put your unique phrase here'
AUTH_SALT='put your unique phrase here'
SECURE_AUTH_SALT='put your unique phrase here'
LOGGED_IN_SALT='put your unique phrase here'
NONCE_SALT='put your unique phrase here'
```

This is enough for a standard Wordpress installation to work.

A couple of things to note here, `DB_TABLE_PREFIX` maps to a global PHP
variable called `$table_prefix` as required by Wordpress.

Also you might note that `DB_CHARSET` and `DB_COLLATE` are missing. These
are initialised in this library with the default values normally defined
in wp-config.php, but can be overwritten by adding them to `.env`

## Customising and Extending

`wp-config.php` often contains constants, variables and callbacks
to suport theme and plugin behavior. This is fully supported
in WP Dotenv by using custom configuration classes which
define which environment variables to map into Wordpress.

This configuration can be grouped by related components, rather than
just having a big clump of unrelated configuration settings all
sitting in one long configuration file.

A custom configuration class must implement the `WpWildfire\ConfigLoaderInterface` 
interface.

Custom configuration classes are registered in the `.env` file 
using the `WORDPRESS_ENV_LOADERS` environment variable.

Here's a quick example of a custom environment loader which
disables file editing, auto-updates and plugin management in admin:

```php
class AdminLockdown implements ConfigLoaderInterface
{
    public function load(ConfigLoader $loader)
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
```

Which can then be registered by adding the following 2 lines to the
`.env` file

```bash
WORDPRESS_ENV_LOADERS=WpWildfire\ConfigLoader\AdminLockdown
LOCKDOWN=true
```

`WORDPRESS_ENV_LOADERS` accepts a comma-delimited list of 
loaders, so any amount of custom loaders can be included.

## Additional notes
*  WP Dotenv works very well with John Blocks excellent implementation
of [Wordpress installation via Composer](https://github.com/johnpbloch/wordpress)
* WP Dotenv uses the [Symfony Dotenv](https://symfony.com/doc/current/components/dotenv.html) component under
the hood

## To do
Add some really cool config handling for multi-site Wordpress which 
is always a pain to deal with.
