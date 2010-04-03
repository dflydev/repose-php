<?php
/**
 * Path Autoloader interface
 * @package repose
 */

require_once('repose_IAutoloader.php');

/**
 * Path Autoloader interface
 * @package repose
 */
class repose_PathAutoloader implements repose_IAutoloader {

    /**
     * Paths
     * @var array
     */
    protected $paths;

    /**
     *
     * @param <type> $paths
     */
    public function __construct($paths = null) {
        if ( ! is_array($paths) ) $paths = array($paths);
        foreach ( $paths as $path ) {
            if ( ! is_null($path) ) $this->paths[] = $path;
        }


    }

    /**
     * Load class if not already loaded
     * @param string $clazz Class to load
     */
    public function loadClass($clazz) {
        if ( class_exists($clazz) ) return;
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