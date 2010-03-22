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
    public function makeProxy($session, $clazz, $instance) {
        $this->assertProxyClassExists($clazz);
        $proxyClazz = $this->getProxyClassName($clazz);
        $serializedParts = explode(':', serialize($instance));
        $serializedParts[1] = strlen($proxyClazz);
        $serializedParts[2] = '"' . $proxyClazz . '"';
        $proxy = unserialize(implode(':', $serializedParts));
        $proxy->___repose_init($session, $proxyClazz, $clazz);
        return $proxy;
    }

    /**
     * Assert that a proxy class has been loaded
     * @param string $clazz Class name
     */
    private function assertProxyClassExists($clazz) {
        if ( ! array_key_exists($clazz, self::$PROXIES_LOADED) ) {
            $proxyClazz = $clazz . '__ReposeProxy__';
            $proxyClazzCode = $this->buildProxyClassCode($proxyClazz, $clazz);
            eval($proxyClazzCode);
            self::$PROXIES_LOADED[$clazz] = array(
                'reflectionClass' => new ReflectionClass($proxyClazz),
                'proxyClazz' => $proxyClazz,
            );
        }
        return self::$PROXIES_LOADED[$clazz];
    }

    /**
     * Get the name of the proxy class
     * @param string $clazz Class name
     * @return string proxy class name
     */
    private function getProxyClassName($clazz) {
        $meta = $this->assertProxyClassExists($clazz);
        return $meta['proxyClazz'];
    }

    /**
     * Get the reflection class for the proxy for this class
     * @param string $clazz Class name
     * @return ReflectionClass
     */
    private function getProxyReflectionClass($clazz) {
        $meta = $this->assertProxyClassExists($clazz);
        return $meta['reflectionClass'];
    }

    private function buildProxyClassCode($proxyClazz, $clazz) {

        $c = array();

        $c[] = 'class ' . $proxyClazz . ' extends ' . $clazz . ' implements repose_IProxy {';

        $c[] = 'private $___repose_clazz;';
        $c[] = 'private $___repose_proxyClazz;';
        $c[] = 'private $___repose_cache;';

        $c[] = 'public function ___repose_init($session, $proxyClazz, $clazz) {';
        $c[] = '$this->___repose_clazz = $clazz;';
        $c[] = '$this->___repose_proxyClazz = $proxyClazz;';
        $c[] = 'foreach ( $this->___repose_getProperties($session) as $param ) {';
        $c[] = '$this->___repose_cache[$param] = $this->$param;';
        $c[] = '}';
        $c[] = '}';

        $c[] = 'public function ___repose_getProperties($session, $clazz = null) {';
        $c[] = 'if ( $clazz === null ) $clazz = $this->___repose_clazz;';
        $c[] = 'return $session->getProperties($clazz);';
        $c[] = '}';

        $c[] = 'public function ___repose_getState($session) {';
        $c[] = 'foreach ( $this->___repose_cache as $k => $v ) {';
        $c[] = 'if ( $v !== $this->$k ) return \'dirty\';';
        $c[] = '}';
        $c[] = 'return \'added\';';
        $c[] = '}';

        $c[] = '}';

        return implode("\n", $c);

    }

}

?>
