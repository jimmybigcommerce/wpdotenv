<?php declare(strict_types=1);

namespace WpWildfire;

use RuntimeException;
use Closure;
use WpWildfire\ConfigLoader\Database;
use WpWildfire\ConfigLoader\Environment;

/**
 * Processes environment variables into Wordpress configuration
 */
final class ConfigLoader
{

    /**
     * All custom loader classes must implement this interface
     */
    const REQUIRED_INTERFACE = ConfigLoaderInterface::class;

    /**
     * @var array of environment variables to be processed
     */
    private static array $env = [];

    /**
     * @var array of php variables to be processed
     */
    private static array $var = [];

    /**
     * @var array of callbacks which will be executed after the wp-settings inclusion
     */
    private static array $callbacks = [];

    /**
     * @var array of loaders which will be invoked to handle configuration loading
     */
    private static $loaders = [
        Database::class,
        Environment::class
    ];

    /**
     * Registers the loaders
     */
    public function __construct()
    {
        $this->registerAdditionalLoaders();
        $this->executeLoaders();
    }

    /**
     * Loops through all registered loaders and calls the load() method
     */
    private function executeLoaders(): void
    {
        foreach (self::$loaders as $loader) {
            (new $loader())->load($this);
        }
    }

    /**
     * Reads the WORDPRESS_ENV_LOADERS variable from the .env file and registers
     * any additional loaders that have been defined (as csv list of fq class names)
     */
    private function registerAdditionalLoaders(): void
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
     */
    private function registerLoader(string $class_name): void
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
     */
    public function addEnvs(array $envs): void
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
    public function addEnv(string $name, string $env_key): void
    {
        if (!getenv($env_key)) {
            throw new RuntimeException(
                sprintf('Environment variable %s not defined', $env_key)
            );
        }
        $this->addEnvValue($name, $_ENV[$env_key] ?? '');
    }

    /**
     * Add a named env variable and assign the variable to it
     *
     * @param string $key
     * @param string $val
     */
    public function addEnvValue(string $key, mixed $val): void
    {
        self::$env[$key] = $val;
    }

    /**
     * Add a PHP variable and supply its value
     */
    public function addVar(string $name, string $val)
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
