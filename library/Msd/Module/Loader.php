<?php
/**
 * This file is part of MySQLDumper released under the GNU/GPL 2 license
 * http://www.mysqldumper.net
 *
 * @package         MySQLDumper
 * @subpackage      Module
 * @version         SVN: $Rev$
 * @author          $Author$
 */
/**
 * Autoloader implementation for module support.
 *
 * @package         MySQLDumper
 * @subpackage      Module
 */
class Msd_Module_Loader implements Zend_Loader_Autoloader_Interface
{
    /**
     * @var Zend_Loader_PluginLoader
     */
    private $_pluginLoader;

    /**
     * Class constructor
     *
     * @param array $prefixesToPaths
     */
    public function __construct($prefixesToPaths = array())
    {
        $this->_pluginLoader = new Zend_Loader_PluginLoader($prefixesToPaths);
    }

    /**
     * Autoload a class
     *
     * @param   string $class
     * @return  mixed
     *          False [if unable to load $class]
     *          get_class($class) [if $class is successfully loaded]
     */
    public function autoload($class)
    {
        $classToLoad = str_replace('Module_', '', $class);
        return $this->_pluginLoader->load($classToLoad);
    }

}
