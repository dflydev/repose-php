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
     * Session
     * @var repose_Session
     */
    protected $session;

    /**
     * Instance cache
     * @var repose_InstanceCache
     */
    protected $instanceCache;

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
     * Constructor
     * @param repose_Session $session Session
     * @param repose_InstanceCache $instanceCache Instance cache
     */
    public function __construct(repose_Session $session, repose_InstanceCache $instanceCache) {
        $this->session = $session;
        $this->instanceCache = $instanceCache;
    }

    /**
     * Make a proxy object
     * @param string $clazz Class name
     * @param object $instance Object instance
     */
    public function makeProxy($clazz, $instance, $data = null, $isPersisted = false) {
        $proxy = null;
        $proxyClazz = null;
        $reflectionProperties = $this->reflectionClassProperties($clazz);
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

            // TODO Without this in place, Reflection was throwing
            // a strange exception. Adding this code back in seems
            // to resolve the issue.
            foreach ( $reflectionProperties as $name => $reflectionProperty ) {
                $originalValue = $reflectionProperty->getValue($instance);
                $proxyReflectionProperty = $this->reflectionClassProperty(
                    $clazz,
                    $name
                );
                $proxyReflectionProperty->setValue($proxy, $originalValue);
            }
            
        }
        $proxy->___repose_init(
            $this->session,
            $this->instanceCache,
            $proxyClazz,
            $clazz,
            $reflectionProperties,
            $data,
            $isPersisted
        );
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
            $properties = self::ALL_FIELDS($reflectionClass);
            foreach ( $this->session->getProperties($clazz) as $property ) {
                try {
                    $reflectionProperty = $properties[$property->name()];
                    if ( ! $reflectionProperty->isPublic() ) {
                        if ( method_exists($reflectionProperty, 'setAccessible') ) {
                            $reflectionProperty->setAccessible(true);
                        } else {
                           throw new Exception('Fatal error mapping Repose proxy class. Is property "' . $property->name() . '" defined in class "' . $clazz . '" marked as private or protected? Repose only supports private and protected properties for PHP version 5.3 or newer.');
                        }
                    }
                    $reflectionClassProperties[$property->name()] = $reflectionProperty;
                } catch (ReflectionException $e) {
                    throw new Exception('Fatal error mapping Repose proxy class. Is property named "' . $property->name() . '" defined in class "' . $clazz . '"?');
                }
            }
            self::$PROXIES_LOADED[$clazz] = array(
                'reflectionClass' => $reflectionClass,
                'reflectionClassProperties' => $reflectionClassProperties,
                'proxyReflectionClass' => $proxyReflectionClass,
                // TODO We do not actually do anything with the proxy reflection
                // class properties. Should we even keep these around?
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

        $this->session->autoloader()->loadClass($clazz);

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

    /**
     * Destroy
     */
    public function destroy() {
        $this->session = null;
        $this->instanceCache = null;
    }
    
    /**
     * Get Reflection Property instances for all available properties
     * 
     * Code borroed from ArcheType-PHP (thanks aek)
     * @author aek
     * @param mixed $clazz
     */
    static protected function ALL_FIELDS($clazz) {
        
        $properties = array();
        if($clazz instanceof ReflectionClass){
            $reflectionClass = $clazz;
        } else {
            $reflectionClass = new ReflectionClass($clazz);
        }

        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            $properties[$reflectionProperty->getName()] = $reflectionProperty;
            //if($reflectionProperty->isPrivate()){
            //    $methodName = 'get'.ucfirst($reflectionProperty->getName());
            //    if ($reflectionClass->hasMethod($methodName)) {
            //        $method = $reflectionClass->getMethod($methodName);
            //        if($method->isPublic()){
            //            $properties[$reflectionProperty->getName()] = $reflectionProperty;
            //        }
            //    }
            //} else {
            //    if($reflectionProperty->isPublic()){
            //        $properties[$reflectionProperty->getName()] = $reflectionProperty;
            //    }
            //}
        }
        if($reflectionClass->getParentClass() !== false){
            $properties = array_merge($properties, self::ALL_FIELDS($reflectionClass->getParentClass()));
        }

        return $properties;

    }

}

?>
