<?php
/**
 * Instance Cache
 * @package repose
 */

require_once('repose_IProxy.php');
require_once('repose_ProxyGenerator.php');
require_once('repose_Uuid.php');

/**
 * Instance Cache
 * @package repose
 */
class repose_InstanceCache {

    /**
     * Cache of instance proxies
     * @var array
     */
    protected $proxies;

    /**
     * Identity map
     * @var array
     */
    protected $identityMap;

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
        $this->proxies = array();
        $this->identityMap = array();
        $this->wrappers = array();
        $this->proxyGenerator = new repose_ProxyGenerator();
    }

    /**
     * Load an instance from the cache.
     * @param string $clazz Class.
     * @param string $primaryKey Primary key structure.
     * @return object
     */
    public function load($clazz, $primaryKey) {

        if ( isset($this->identityMap[$clazz][$primaryKey]) ) {
            return $this->proxies[$this->identityMap[$clazz][$primaryKey]];
        }

        return null;

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

        if ( ! isset($this->identityMap[$clazz]) ) {
            // Ensure the identity map array for this class exists.
            $this->identityMap[$clazz] = array();
        }

        // This is the magic that makes it so that we can keep track
        // of the original variables from the outside world. If they
        // are already added to the system, we can dig out the proxy
        // that was created last time.
        foreach ( $this->wrappers[$clazz] as $id => $wrapper ) {
            if ( $wrapper['instance'] === $instance ) {
                return $wrapper['proxy'];
            }
        }

        $proxy = $this->proxyGenerator->makeProxy($session, $clazz, $instance);

        // Store a reference to the instance and to the proxy as a
        // simple array wrapper. We will reference this later if
        // the raw object is used again later.
        $this->wrappers[$clazz][$proxy->___repose_id()] = array(
            'instance' => $instance,
            'proxy' => $proxy
        );

        // Store the proxy by its internal ID.
        $this->proxies[$proxy->___repose_id()] = $proxy;

        // Store a map to this proxy by its internal ID.
        $this->identityMap[$clazz][$proxy->___repose_serializedPrimaryKey($session)] = $proxy->___repose_id();

        return $proxy;

    }

    /**
     * Add an instance to the cache.
     * @param repose_Session $session Session
     * @param string $clazz Class name
     * @param array $data Data
     * @return object Proxy
     */
    public function addFromData($session, $clazz, $data) {

        if ( ! isset($this->identityMap[$clazz]) ) {
            // Ensure the identity map array for this class exists.
            $this->identityMap[$clazz] = array();
        }

        $reflectionClass = $this->proxyGenerator->proxyReflectionClass($clazz);
        $instance = $reflectionClass->newInstance();

        $proxy = $this->proxyGenerator->makeProxy(
            $session,
            $clazz,
            $instance,
            $data,
            true
        );

        // Store the proxy by its internal ID.
        $this->proxies[$proxy->___repose_id()] = $proxy;

        // Store a map to this proxy by its internal ID.
        $this->identityMap[$clazz][$proxy->___repose_serializedPrimaryKey($session)] = $proxy->___repose_id();

        return $proxy;

    }

    /**
     * Set of all proxies marked as persisted.
     * @param repose_Session $session Session
     * @return array
     */
    public function persisted($session) {
        $results = array();
        foreach ( $this->proxies as $id => $proxy ) {
            if ( $proxy->___repose_isPersisted() ) $results[] = $proxy;
        }
        return $results;
    }

    /**
     * Set of all proxies marked as pending.
     * @param repose_Session $session Session
     * @return array
     */
    public function pending($session) {
        $results = array();
        foreach ( $this->wrappers as $clazz => $wrappers ) {
            foreach ( $wrappers as $id => $wrapper ) {
                $proxy = $wrapper['proxy'];
                if ( ! $proxy->___repose_isPersisted() ) {
                    // We are really only pending if we are not
                    // already persisted!
                    $results[] = $wrapper['proxy'];
                }
            }
        }
        return $results;
    }

    /**
     * Set of all proxies marked as deleted.
     * @param repose_Session $session Session
     * @return array
     */
    public function deleted($session) {
        // TODO Implement this
    }

    /**
     * Set of all proxies considered dirty.
     * @param repose_Session $session Session
     * @return array
     */
    public function dirty($session) {
        $results = array();
        foreach ( $this->proxies as $id => $proxy ) {
            if ( $proxy->___repose_isDirty($session) ) $results[] = $proxy;
        }
        return $results;
    }

    /**
     * Flush instances that are marked pending
     * @param repose_Session $session Session
     */
    public function flushPending($session) {
        foreach ( $this->pending($session) as $pending ) {
            $pending->___repose_persist($session);
        }
    }

    /**
     * Flush instances that are marked dirty
     * @param repose_Session $session Session
     */
    public function flushDirty($session) {
        foreach ( $this->dirty($session) as $dirty ) {
            $dirty->___repose_flush($session);
        }
    }

    /**
     * Update the identity map
     * @param string $id Internal ID
     * @param string $clazz Class name
     * @param string $oldPk Old primary key
     * @param string $newPk New primary key
     */
    public function updateIdentityMap($id, $clazz, $oldPk, $newPk) {

        if ( ! isset($this->identityMap[$clazz]) ) {
            // Ensure the identity map array for this class exists.
            $this->identityMap[$clazz] = array();
        }

        // Store our ID at our new primary key.
        $this->identityMap[$clazz][$newPk] = $id;

        // Delete the reference to our new ID from our old primary key.
        if ( $newPk != $oldPk ) unset($this->identityMap[$clazz][$oldPk]);

    }

}
?>
