<?php
/**
 * Classpath Autoloader interface
 * @package repose
 */

require_once('repose_IAutoloader.php');

/**
 * Classpath Autoloader interface
 * @package repose
 */
class repose_ClasspathAutoloader implements repose_IAutoloader {

    /**
     * Last processed classpath
     * @var array
     */

    protected $lastClasspath = null;

    /**
     * Paths
     * @var array
     */
    protected $paths;

    /**
     * Load class if not already loaded
     * @param string $clazz Class to load
     */
    public function loadClass($clazz) {
        if ( class_exists($clazz) ) return;
        $currentClasspath = get_include_path();
        if ( $currentClasspath !== $this->lastClasspath ) {
            $this->lastClasspath = $currentClasspath;
            $this->paths = explode(PATH_SEPARATOR, $currentClasspath);
        }
        foreach ( $this->paths as $path ) {
            foreach ( $this->calculateTestPaths($path, $clazz) as $testPath ) {
                if ( file_exists($testPath) ) {
                    require_once($testPath);
                    return;
                }
            }
        }
    }

    /**
     * Calculate all possible paths to test
     *
     * Super simple for now.
     *
     * @param string $path Path
     * @param string $clazz Class
     * @return <type>
     */
    public function calculateTestPaths($path, $clazz) {
        return array(
            $path .'/' . $clazz . '.php',
        );
    }

}
?>