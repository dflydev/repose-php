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
    public function makeProxy($session, $clazz, $instance, $data = null, $isPersisted = false) {
        $proxy = null;
        $proxyClazz = null;
        if ( $instance instanceof repose_IProxy ) {
            $proxy = $instance;
            $proxyClazz = get_class($proxy);
        } else {

            $meta = $this->assertProxyClassExists($clazz);

            $proxyClazz = $this->proxyClassName($clazz);

            // TODO Someday this should be smarter. In the case of
            // PHP >= 5.3, this is completely not needed. However,
            // this step is critical to PHP < 5.3 otherwise any
            // private or protected properties will be lost.
            $serializedParts = explode(':', serialize($instance));
            $serializedParts[1] = strlen($proxyClazz);
            $serializedParts[2] = '"' . $proxyClazz . '"';
            $proxy = unserialize(implode(':', $serializedParts));

            //$proxy = $this->proxyReflectionClass($clazz)->newInstance();

            $reflectionProperties = $this->reflectionClassProperties($clazz);
            $proxyReflectionProperties = $this->proxyReflectionClassProperties(
                $clazz
            );

            foreach ( $reflectionProperties as $name => $reflectionProperty ) {
                $originalValue = $reflectionProperty->getValue($instance);
                $proxyReflectionproperty = $this->reflectionClassProperty(
                    $clazz,
                    $name
                );
                $proxyReflectionproperty->setValue($proxy, $originalValue);
            }

        }
        $proxy->___repose_init($session, $proxyClazz, $clazz, $data, $isPersisted);
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
     * Get the reflection class property for the class
     * @param string $clazz Class name
     * @param string $name Name
     * @return ReflectionClass
     */
    public function reflectionClassProperty($clazz, $name) {
        $meta = $this->assertProxyClassExists($clazz);
        return isset($meta['reflectionClassProperties'][$name]) ?
            $meta['reflectionClassProperties'][$name] : null;
    }

    /**
     * Get the reflection class properties for the class
     * @param string $clazz Class name
     * @return ReflectionClass
     */
    public function reflectionClassProperties($clazz) {
        $meta = $this->assertProxyClassExists($clazz);
        return $meta['reflectionClassProperties'];
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
     * Get the reflection class property for the proxy for this class
     * @param string $clazz Class name
     * @param string $name Name of property
     * @return ReflectionClass
     */
    public function proxyReflectionClassProperty($clazz, $name) {
        $meta = $this->assertProxyClassExists($clazz);
        return isset($meta['proxyReflectionClassProperties'][$name]) ?
            $meta['proxyReflectionClassProperties'][$name] : null;
    }

    /**
     * Get the reflection class properties for the proxy for this class
     * @param string $clazz Class name
     * @return ReflectionClass
     */
    public function proxyReflectionClassProperties($clazz) {
        $meta = $this->assertProxyClassExists($clazz);
        return $meta['proxyReflectionClassProperties'];
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
            $reflectionClass = new ReflectionClass($clazz);
            $proxyReflectionClass = new ReflectionClass($proxyClazz);
            $reflectionClassProperties = array();
            $proxyReflectionClassProperties = array();
            foreach ( $reflectionClass->getProperties() as $property ) {
                $reflectionClassProperties[$property->getName()] = $property;
            }
            foreach ( $proxyReflectionClass->getProperties() as $property ) {
                $proxyReflectionClassProperties[$property->getName()] = $property;
            }
            self::$PROXIES_LOADED[$clazz] = array(
                'reflectionClass' => $reflectionClass,
                'reflectionClassProperties' => $reflectionClassProperties,
                'proxyReflectionClass' => $proxyReflectionClass,
                'proxyReflectionClassProperties' => $proxyReflectionClassProperties,
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
