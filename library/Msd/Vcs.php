<?php
class Msd_Vcs
{
    /**
     * @var Zend_Loader_PluginLoader
     */
    private static $_loader = null;

    private static $_ignoreFileNames = array('Exception.php', 'Interface.php', 'Abstract.php');

    private function __construct()
    {
    }

    private static function _initLoader()
    {
        self::$_loader = new Zend_Loader_PluginLoader(
            array(
                'Msd_' => implode(DS, array(APPLICATION_PATH, '..', 'library', 'Msd')),
                'Module_' => implode(DS, array(APPLICATION_PATH, '..', 'modules', 'library')),
            )
        );
    }

    /**
     * @static
     * @throws Msd_Vcs_Exception
     * @param $adapter
     * @param array $adapterOptions
     * @return Msd_Vcs_Interface
     */
    public static function factory($adapter, $adapterOptions = array())
    {
        $vcsClass = str_replace('_', ' ', strtolower($adapter));
        $vcsClass = 'Vcs_' . str_replace(' ', '_', ucwords($vcsClass));
        if (self::$_loader === null) {
            self::_initLoader();
        }
        $className = self::$_loader->load($vcsClass);
        $vcs = new $className($adapterOptions);
        if (!$vcs instanceof Msd_Vcs_Interface) {
            throw new Msd_Vcs_Exception("The specified VCS adapter doesn't implement the interface Msd_Vcs_Interface.");
        }

        return $vcs;
    }

    public static function getAvailableAdapter()
    {
        if (self::$_loader === null) {
            self::_initLoader();
        }

        $paths = self::$_loader->getPaths();
        $classes = array();
        foreach ($paths as $path) {
            $classes = array_merge($classes, self::_getDirEntries($path[0] . DS . 'Vcs'));
        }
        return $classes;
    }

    private static function _getDirEntries($path)
    {
        $dir = new DirectoryIterator($path);
        $classes = array();
        for (; $dir->valid(); $dir->next()) {
            $filename = $dir->getFilename();
            if ($dir->isDot() || $filename{0} == '.' || in_array($filename, self::$_ignoreFileNames)) {
                continue;
            }
            if ($dir->isDir()) {
                $subdirs = self::_getDirEntries($dir->getPathname());
                foreach ($subdirs as $entry) {
                    $classes[] = $dir->getFilename() . '_' . $entry;
                }
            } else {
                $classes[] = substr($dir->getFilename(), 0, -4);
            }
        }
        return $classes;
    }

    public static function getAdapterOptions($adapter)
    {
        $vcsClass = str_replace('_', ' ', strtolower($adapter));
        $vcsClass = 'Vcs_' . str_replace(' ', '_', ucwords($vcsClass));
        if (self::$_loader === null) {
            self::_initLoader();
        }
        $className = self::$_loader->load($vcsClass);
        return $className::getAdapterOptions();
    }
}
