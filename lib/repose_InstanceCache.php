<?php
/**
 * Instance Cache
 * @package repose
 */

require_once('repose_IProxy.php');
require_once('repose_ProxyGenerator.php');

/**
 * Instance Cache
 * @package repose
 */
class repose_InstanceCache {

    /**
     * Cache of instance wrappers.
     * @var array
     */
    protected $wrappers;

    /**
     * Proxy generator.
     * @var repose_ProxyGenerator
     */
    protected $proxyGenerator;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->wrappers = array();
        $this->proxyGenerator = new repose_ProxyGenerator();
    }

    /**
     * Add an instance to the cache.
     * @param object $instance Object instance
     * @param string $clazz Class name
     * @return object Proxy
     */
    public function add($instance, $clazz = null) {

        // TODO We should check to make certain this instance is cached
        // otherwise this could potentially be an instance from another
        // session!
        if ( $instance instanceof repose_IProxy ) return $instance;

        // Assume that the outermost classname is going to work.
        if ( $clazz === null ) $clazz = get_class($instance);

        if ( ! isset($this->wrappers[$clazz]) ) {
            // Ensure the wrappers array for this class exists.
            $this->wrappers[$clazz] = array();
        }

        foreach ( $this->find($clazz) as $wrapper ) {
            if ( $wrapper['instance'] === $instance ) {
                return $wrapper['proxy'];
            }
        }

        $proxy = $this->proxyGenerator->makeProxy($clazz, $instance);

        $this->wrappers[$clazz][] = array(
            'state' => 'added',
            'instance' => $instance,
            'proxy' => $proxy
        );

        return $proxy;

    }

    /**
     * Set of all proxies marked as added.
     * @return array
     */
    public function added() {
        return $this->find(null, 'added', 'proxy');
    }

    /**
     * Set of all proxies marked as deleted.
     * @return array
     */
    public function deleted() {
        return $this->find(null, 'deleted', 'proxy');
    }

    /**
     * Set of all proxies considered dirty.
     * @return array
     */
    public function dirty() {
        return $this->find(null, 'dirty', 'proxy');
    }

    /**
     * Perform a search on wrappers.
     * @param string $clazz Specific class name
     * @param string $state Specific state name
     * @param string $which Which attribute to return?
     * @return array
     */
    protected function find($clazz = null, $state = null, $which = 'wrapper') {

        $results = array();

        // If not clazz is specified, use all of the wrappers.
        $clazzes = $clazz === null ?
            array_keys($this->wrappers) :
            array($clazz);

        foreach ( $clazzes as $clazz ) {
            if ( isset($this->wrappers[$clazz]) ) {
                foreach ( $this->wrappers[$clazz] as $wrapper ) {
                    if ( $state === null or $wrapper['state'] == $state ) {
                        $results[] = $which == 'wrapper' ?
                            $wrapper :
                            $wrapper[$which];

                    }
                }
            }
        }

        return $results;

    }

}
?>
