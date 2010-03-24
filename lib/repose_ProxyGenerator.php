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
     * Proxy template
     * @var string
     */
    static private $PROXY_TEMPLATE = null;

    /**
     * Track which proxies have been loaded
     * @var array
     */
    static private $PROXIES_LOADED = array();

    /**
     * Make a proxy object
     * @param repose_Session $session Session
     * @param string $clazz Class name
     * @param object $instance Object instance
     */
    public function makeProxy($session, $clazz, $instance, $data = null) {
        $proxy = null;
        $proxyClazz = null;
        if ( $instance instanceof repose_IProxy ) {
            $proxy = $instance;
            $proxyClazz = get_class($proxy);
        } else {
            $this->assertProxyClassExists($clazz);
            $proxyClazz = $this->proxyClassName($clazz);
            $serializedParts = explode(':', serialize($instance));
            $serializedParts[1] = strlen($proxyClazz);
            $serializedParts[2] = '"' . $proxyClazz . '"';
            $proxy = unserialize(implode(':', $serializedParts));
        }
        $proxy->___repose_init($session, $proxyClazz, $clazz, $data);
        return $proxy;
    }

    /**
     * Get the name of the proxy class
     * @param string $clazz Class name
     * @return string proxy class name
     */
    public function proxyClassName($clazz) {
        $meta = $this->assertProxyClassExists($clazz);
        return $meta['proxyClazz'];
    }

    /**
     * Get the reflection class for the class
     * @param string $clazz Class name
     * @return ReflectionClass
     */
    public function reflectionClass($clazz) {
        $meta = $this->assertProxyClassExists($clazz);
        return $meta['reflectionClass'];
    }

    /**
     * Get the reflection class for the proxy for this class
     * @param string $clazz Class name
     * @return ReflectionClass
     */
    public function proxyReflectionClass($clazz) {
        $meta = $this->assertProxyClassExists($clazz);
        return $meta['proxyReflectionClass'];
    }

    /**
     * Assert that a proxy class has been loaded
     * @param string $clazz Class name
     */
    private function assertProxyClassExists($clazz) {
        if ( ! array_key_exists($clazz, self::$PROXIES_LOADED) ) {
            $proxyClazz = $clazz . '__ReposeProxy__';
            $proxyClazzCode = $this->buildProxyClassCode($clazz);
            eval($proxyClazzCode);
            self::$PROXIES_LOADED[$clazz] = array(
                'reflectionClass' => new ReflectionClass($clazz),
                'proxyReflectionClass' => new ReflectionClass($proxyClazz),
                'proxyClazz' => $proxyClazz,
            );
        }
        return self::$PROXIES_LOADED[$clazz];
    }

    /**
     * Build the code for the class's proxy instance
     * @param string $clazz Class
     * @return string
     */
    private function buildProxyClassCode($clazz) {

        if ( self::$PROXY_TEMPLATE === null ) {
            self::$PROXY_TEMPLATE = preg_replace(
                '/(^<\?php|\?>$)/',
                '',
                file_get_contents(
                    dirname(__FILE__) . '/repose_ProxyInstance.inc'
                )
            );
        }

        return preg_replace('/PROXY_TEMPLATE/', $clazz, self::$PROXY_TEMPLATE);

    }

}

?>
