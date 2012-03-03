<?php
class Testhelper
{
    private static $_copiedFiles = array();

    private static $_shutdownRegistered = false;

    /**
     * Prepare tests
     *
     * @return void
     */
    public static function setUp()
    {
        // create phpunit_test.ini in application/configs folder for tests
        $destinationFile = APPLICATION_PATH . DS . 'configs'. DS .'defaultConfig.ini';
        if (!in_array($destinationFile, self::$_copiedFiles)) {
            self::copyFile('defaultConfig.ini', $destinationFile);
        }
        self::setUpDb();
    }

    /**
     * Rollback actions, made by setUp() method
     *
     * @return void
     */
    public static function onShutdown()
    {
        // remove formerly copied fixture files
        foreach (self::$_copiedFiles as $copiedFile) {
            self::removeFile($copiedFile);
        }
    }

    /**
     * Copy a fixture to destination
     *
     * @param string $source      Filename of source in fixture folder
     * @param string $destination Filename of destination
     * @param bool   $overwrite   Allow an existing file to be overwritten (true) or not (false)
     * @throws Exception
     *
     * @return void
     */
    public static function copyFile($source, $destination, $overwrite = false)
    {
        $fixturePath = realpath(dirname(__FILE__) . DS . 'fixtures');
        $source = realpath($fixturePath . DS . $source);
        // delete target file if it exists
        if (file_exists($destination)) {
            if ($overwrite) {
                if (!unlink($destination)) {
                    throw new Exception('Error: Can\'t delete file "' . $destination .'"!');
                }
            } else {
                if (!rename($destination, $destination . '.phpunit')) {
                    throw new Exception('Error: Can\'t create backup of file "' . $destination .'"!');
                }
            }
        }
        if (!copy($source, $destination)) {
            throw new Exception(
                'Error: Can\'t copy file "' . $source . '" to "'
                . $destination .'"!'
            );
        };
        chmod($destination, 0755);
        self::$_copiedFiles[] = $destination;
        if (!self::$_shutdownRegistered) {
            register_shutdown_function(array(__CLASS__, 'onShutdown'));
            self::$_shutdownRegistered = true;
        }
    }

    /**
     * Remove a file
     *
     * @throws Exception
     * @param string $file File to remove
     *
     * @return void
     */
    public function removeFile($file)
    {
        if (!file_exists($file)) {
            return;
        }
        if (!unlink($file)) {
            throw new Exception('Error: Can\'t remove file "' . $file .'"');
        }

        if (file_exists($file . '.phpunit')) {
            if (!rename($file . '.phpunit', $file)) {
                throw new Exception('Error: Can\'t rename backup file "' . $file .'"');
            }
        }
    }

    /**
     * Executes docs/db_schema.sql and fills database
     *
     * @return void
     */
    public function setUpDb()
    {
        $sqlFile = file_get_contents(APPLICATION_PATH .'/../docs/db_schema.sql');
        $queries = explode(";\n", $sqlFile);
        $db = Msd_Db::getAdapter();
        $db->selectDb('phpunit_otc');
        foreach ($queries as $query) {
            $db->query($query);
        }
    }
}
