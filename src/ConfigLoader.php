<?php declare(strict_types=1);

namespace WpWildfire;

use RuntimeException;
use Closure;
use Symfony\Component\Dotenv\Dotenv;

/**
 * Acts as a registry service for Wordpress configuration via .env variables
 */
final class ConfigLoader
{

    /**
     * All custom loader classes must implement this interface
     */
    const REQUIRED_INTERFACE = 'WpWildfire\ConfigLoaderInterface';

    /**
     * @var array of environment variables to be processed
     */
    private static $env = [];

    /**
     * @var array of php variables to be processed
     */
    private static $var = [];

    /**
     * @var array of callbacks which will be executed after the wp-settings inclusion
     */
    private static $callbacks = [];

    /**
     * @var array of loaders which will be invoked to handle configuration loading
     */
    private static $loaders = [
        'WpWildfire\ConfigLoader\Database',
        'WpWildfire\ConfigLoader\Environment'
    ];

    /**
     * Invokes dotenv to process the supplied .env file and registers any additional loaders
     *
     * @param string $path
     * @param string ...$extraPaths
     */
    public function __construct(string $path, string ...$extraPaths)
    {
        $dotenv = new Dotenv();
        $dotenv->load($path);
        $this->registerAdditionalLoaders();
        $this->executeLoaders();
    }

    /**
     * Loops through all registered loaders and calls the load() method
     */
    private function executeLoaders()
    {
        foreach (self::$loaders as $loader) {
            (new $loader())->load($this);
        }
    }

    /**
     * Reads the WORDPRESS_ENV_LOADERS variable from the .env file and registers
     * any additional loaders that have been defined (as csv list of fq class names)
     */
    private function registerAdditionalLoaders()
    {
        if (isset($_ENV['WORDPRESS_ENV_LOADERS'])) {
            foreach (explode(",", $_ENV['WORDPRESS_ENV_LOADERS']) as $loader) {
               $this->registerLoader($loader);
            }
        }
    }

    /**
     * Registers additional loader classes after validating that they exist
     * and that they implement the required interface for loaders
     *
     * @param $class_name
     */
    private function registerLoader($class_name)
    {
        if (class_exists($class_name)) {
            $interfaces = class_implements($class_name);
            if (isset($interfaces[self::REQUIRED_INTERFACE])) {
                self::$loaders[] = $class_name;
            } else {
                throw new RuntimeException(
                    sprintf('Loader %s must implement %s', $class_name, self::REQUIRED_INTERFACE)
                );
            }
        } else {
            throw new RuntimeException(
                sprintf('Class %s cannot be found', $class_name)
            );
        }
    }

    /**
     * Accepts an array of environment variables names and adds them to the list
     * of environment variables which will be processed by the loading method. This assumes
     * that the variable name in the .env file will match exactly to the required
     * constant name which will be defined
     *
     * @param array $envs
     */
    public function addEnvs(array $envs)
    {
        foreach ($envs as $name) {
            $this->addEnv($name, $name);
        }
    }

    /**
     * Add named env var, and define which .dotenv key it should read it from
     *
     * @param string $name The $_ENV var name to assign
     * @param string $env_key The .env var name to read the value from
     */
    public function addEnv($name, $env_key)
    {
        $this->addEnvValue($name, $_ENV[$env_key]);
    }

    /**
     * Add a named env variable and assign the variable to it
     *
     * @param string $key
     * @param string $val
     */
    public function addEnvValue($key, $val)
    {
        self::$env[$key] = $val;
    }

    /**
     * Add a PHP variable and define which .env key the value should be ready from
     *
     * @param $name
     * @param $env_key
     */
    public function addEnvVar($name, $env_key)
    {
        $this->addVar($name, $_ENV[$env_key]);
    }

    /**
     * Add a PHP variable and supply its value
     *
     * @param $name
     * @param $val
     */
    public function addVar($name, $val)
    {
        self::$var[$name] = $val;
    }

    /**
     * Add a callback to be invoked after the wp-settings is included
     *
     * @param Closure $callback
     */
    public function addCallback(Closure $callback)
    {
        self::$callbacks[] = $callback;
    }

    /**
     * Handles the loading of all variables into the environment
     *
     * @return array
     */
    public function load()
    {

        // Process the environment variables
        foreach (self::$env as $name => $val) {
            define($name, $val);
        }

        // Return the array of global vars which will need to be
        // handled by the caller in the global namespace
        return self::$var;

    }

    /**
     * Execute callbacks - callbacks can be used to add settings post-inclusion hooks
     * eg: add_filter, add_action, wp_enqueue_style, update_option etc
     */
    public function invokeCallbacks()
    {

        // Callbacks are only for web context
        if (php_sapi_name() != 'cli') {
            return;
        }

        // Loop through and execute callbacks
        foreach (self::$callbacks as $callback) {
            $callback();
        }
    }

}