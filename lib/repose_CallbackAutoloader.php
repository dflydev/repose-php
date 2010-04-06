<?php
/**
 * Callback Autoloader interface
 * @package repose
 */

require_once('repose_IAutoloader.php');

/**
 * Callback Autoloader interface
 * @package repose
 */
class repose_CallbackAutoloader implements repose_IAutoloader {

    /**
     * Callback
     * @var callback
     */
    protected $callback;

    /**
     * Constructor
     * @param callback $callback Callback
     */
    public function __construct($callback) {
        $this->callback = $callback;
    }

    /**
     * Load class if not already loaded
     * @param string $clazz Class to load
     */
    public function loadClass($clazz) {
        if ( class_exists($clazz) ) return;
        return call_user_func($this->callback, $clazz);
    }

}
?>