<?php
/**
 * Autoloader interface
 * @package repose
 */

/**
 * Autoloader interface
 * @package repose
 */
interface repose_IAutoloader {
    /**
     * Load class if not already loaded
     * @param string $clazz Class to load
     */
    public function loadClass($clazz);
}
?>