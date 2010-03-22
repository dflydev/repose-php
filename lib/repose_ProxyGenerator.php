<?php
/**
 * Proxy Generator
 * @package repose
 */

/**
 * Proxy Generator
 * @package repose
 */
class repose_ProxyGenerator {

    /**
     * Make a proxy object
     * @param string $clazz Class name
     * @param object $instance Object instance
     */
    public function makeProxy($clazz, $instance) {
        return new repose_DummyProxy($clazz, $instance);
    }

}
class repose_DummyProxy {
    public $clazz;
    public $instance;
    public function __construct($clazz, $instance) {
        $this->clazz = $clazz;
        $this->instance = $instance;
    }
}
?>
