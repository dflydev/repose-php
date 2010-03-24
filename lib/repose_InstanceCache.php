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
     * @param repose_Session $session Session
     * @param object $instance Object instance
     * @param string $clazz Class name
     * @return object Proxy
     */
    public function add($session, $instance, $clazz = null) {

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

        foreach ( $this->find($session, $clazz) as $wrapper ) {
            if ( $wrapper['instance'] === $instance ) {
                return $wrapper['proxy'];
            }
        }

        $proxy = $this->proxyGenerator->makeProxy($session, $clazz, $instance);

        $this->wrappers[$clazz][] = array(
            'state' => 'pending',
            'instance' => $instance,
            'proxy' => $proxy
        );

        return $proxy;

    }

    /**
     * Set of all proxies marked as persisted.
     * @param repose_Session $session Session
     * @return array
     */
    public function persisted($session) {
        return $this->find($session, null, 'persisted', 'proxy');
    }

    /**
     * Set of all proxies marked as pending.
     * @param repose_Session $session Session
     * @return array
     */
    public function pending($session) {
        return $this->find($session, null, 'pending', 'proxy');
    }

    /**
     * Set of all proxies marked as deleted.
     * @param repose_Session $session Session
     * @return array
     */
    public function deleted($session) {
        return $this->find($session, null, 'deleted', 'proxy');
    }

    /**
     * Set of all proxies considered dirty.
     * @param repose_Session $session Session
     * @return array
     */
    public function dirty($session) {
        return $this->find($session, null, 'dirty', 'proxy');
    }

    /**
     * Perform a search on wrappers.
     * @param repose_Session $session Session
     * @param string $clazz Specific class name
     * @param string $state Specific state name
     * @param string $which Which attribute to return?
     * @return array
     */
    protected function find($session, $clazz = null, $state = null, $which = 'wrapper') {

        $results = array();

        // If not clazz is specified, use all of the wrappers.
        $clazzes = $clazz === null ?
            array_keys($this->wrappers) :
            array($clazz);

        foreach ( $clazzes as $clazz ) {
            if ( isset($this->wrappers[$clazz]) ) {
                foreach ( $this->wrappers[$clazz] as $wrapper ) {
                    $result = $which == 'wrapper' ?
                        $wrapper : $wrapper[$which];
                    if ( $state === null ) {
                        $results[] = $result;
                    } else {
                        switch($state) {
                            case 'pending':
                            case 'persistent':
                            case 'deleted':
                                if ( $wrapper['state'] == $state ) {
                                    $results[] = $result;
                                }
                                break;
                            case 'dirty':
                                if ( $wrapper['proxy']->___repose_isDirty($session) ) {
                                    $results[] = $result;
                                }
                                break;
                        }
                    }
                }
            }
        }

        return $results;

    }

}
?>
