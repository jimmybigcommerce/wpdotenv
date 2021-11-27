<?php
/*
 * This file is part of the WpWildfire Config Loader package.
 *
 * (c) WPWildfire (wpwildfire@gmail.com)
 *
 */

namespace WpWildfire\ConfigLoader\Tests;

use PHPUnit\Framework\TestCase;

class ConfigLoaderTest extends TestCase
{

    /**
     * @var resource A temp file we will use to simulate a .env file
     */
    public $test_env_file;

    /**
     * Create the temp .env file we will use for testing
     */
    protected function setUp(): void
    {
        @mkdir($tmpdir = sys_get_temp_dir().'/wpwildfire_configloader');
        $this->test_env_file = tempnam($tmpdir, 'wpwildfire_configloader-');
        file_put_contents($this->test_env_file, $this->getTestDotEnvFileContents());
    }

    /**
     * @return string A sample .env file contents to use for testing
     */
    public function getTestDotEnvFileContents()
    {
        return <<<DOTENVFILE
DB_NAME=dbname
DB_USER=dbusername
DB_PASSWORD=dbpassword
DB_HOST=mysql
DB_TABLE_PREFIX=wp_
ENVIRONMENT=debug
SITEURL='https://testsite.local'
THEME=twentyseventeen
AUTH_KEY='rand_auth_key'
SECURE_AUTH_KEY='rand_secure_auth_key'
LOGGED_IN_KEY='rand_logged_in_key'
NONCE_KEY='rand_nonce_key'
AUTH_SALT='rand_auth_salt'
SECURE_AUTH_SALT='rand_secure_auth_salt'
LOGGED_IN_SALT='rand_logged_in_salt'
NONCE_SALT='rand_none_salt'
WORDPRESS_ENV_LOADERS=WpWildfire\ConfigLoader\AdminLockdown
LOCKDOWN=true
DOTENVFILE;
    }

    /**
     * Tests the loading of the file and validates the expected values
     */
    public function testConfigLoader()
    {
        $loader = new \WpWildfire\ConfigLoader($this->test_env_file);
        foreach ($loader->load() as $global_var => $value) {
            $$global_var = $value;
        }

        /** @var string $table_prefix test that the global variable $table_prefix was loaded correctly  */
        $this->assertSame('wp_', $table_prefix);

        /**
         * Make sure all our expected env values have been populated with the correct values
         */
        $this->assertSame('dbname', $_ENV['DB_NAME']);
        $this->assertSame('dbusername', $_ENV['DB_USER']);
        $this->assertSame('dbpassword', $_ENV['DB_PASSWORD']);
        $this->assertSame('debug', $_ENV['ENVIRONMENT']);
        $this->assertSame('https://testsite.local', $_ENV['SITEURL']);
        $this->assertSame('twentyseventeen', $_ENV['THEME']);
        $this->assertSame('rand_auth_key', $_ENV['AUTH_KEY']);
        $this->assertSame('rand_secure_auth_key', $_ENV['SECURE_AUTH_KEY']);
        $this->assertSame('rand_logged_in_key', $_ENV['LOGGED_IN_KEY']);
        $this->assertSame('rand_nonce_key', $_ENV['NONCE_KEY']);
        $this->assertSame('rand_auth_salt', $_ENV['AUTH_SALT']);
        $this->assertSame('rand_secure_auth_salt', $_ENV['SECURE_AUTH_SALT']);
        $this->assertSame('rand_logged_in_salt', $_ENV['LOGGED_IN_SALT']);
        $this->assertSame('rand_none_salt', $_ENV['NONCE_SALT']);
        $this->assertSame('true', $_ENV['LOCKDOWN']);
    }

    /**
     * Test the callback functionality
     */
    public function testConfigLoaderCallbacks()
    {
        $this->mockWordpressFunctions();
        $loader = new \WpWildfire\ConfigLoader($this->test_env_file);
        $loader->invokeCallbacks();

        // The WpWildfire\ConfigLoader\AdminLockdown config has a callback
        // which invokes `add_filter` - so the mock function should have
        // set it to true if the callback was successfully fired in the
        // invokation above. This is pretty clumsy, but at least provides
        // a guarantee that the callbacks are firing
        // todo - better tests for the callbacks
        global $add_filter;
        $this->assertTrue($add_filter);
        global $plugin_action_links;
        $this->assertNotTrue($plugin_action_links);

    }

    /**
     * Mock functions for Wordpress functions which can be invoked in callbacks.
     * Simply creates a global variable of the same name as the function and sets
     * it to true for each function.
     */
    public function mockWordpressFunctions()
    {
        foreach (['plugin_action_links', 'add_filter'] as $wordpress_function)
        {
            eval("function $wordpress_function(){ global $$wordpress_function; $$wordpress_function = true; }");
        }

    }

}
