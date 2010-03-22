<?php
/**
 * Session
 * @package repose
 */

require_once('repose_InstanceCache.php');

/**
 * Session
 * @package repose
 */
class repose_Session {

    /**
     * Instance cache.
     * @var repose_InstanceCache
     */
    protected $instanceCache;

    public function __construct() {
        $this->instanceCache = new repose_InstanceCache();
    }

    /**
     * Place an object in the Session.
     * @param object $instance Object instance
     * @param string $clazz Class name
     * @return object Proxy
     */
    public function add($instance, $clazz = null) {
        $proxy = $this->instanceCache->add($this, $instance, $clazz);
        return $proxy;
    }

    /**
     * Set of all persistent instances marked as added.
     * @return array
     */
    public function added() {
        return $this->instanceCache->added($this);
    }

    /**
     * Flush pending changes and commit the current transaction.
     */
    public function commit() {
    }

    /**
     * Mark an object in the Session as deleted.
     * Will be removed from data source on flush ( SQL DELETE ).
     * @param object $instance Object instance
     */
    public function delete($instance) {
    }

    /**
     * Set of all persistent instances marked as deleted.
     * @return array
     */
    public function deleted() {
        return $this->instanceCache->deleted($this);
    }

    /**
     * Set of all persistent instances considered dirty.
     * @return array
     */
    public function dirty() {
        return $this->instanceCache->dirty($this);
    }

    /**
     * Execute a query.
     */
    public function execute($query, $params) {
    }

    /**
     * Remove an object instance from the Session.
     */
    public function expunge($instance) {
    }

    /**
     * Flush all object changes to the database.
     */
    public function flush() {
    }

    /**
     * Returns true if the object instance has modified attributes.
     * @param object $instance Object instance
     * @return bool
     */
    public function isModified($instance) {
    }

    /**
     * Merge the state of an instance into the Session.
     *
     * Copies the state an instance onto the persistent instance with the
     * same identifier.
     *
     * @param object $instance Object instance
     */
    public function merge($instance) {
    }

    /**
     * Create a query instance.
     * @param string $queryString Query
     * @return repose_Query
     */
    public function query($queryString) {
    }

    /**
     * Refresh the attributes on the given instance.
     * @param object $instance Object instance
     */
    public function refresh($instance) {
    }

    /**
     * Get the properties for the specified class
     * @param string $clazz Class name
     * @return array
     */
    public function getProperties($clazz) {
        return array('name');
    }

}

?>
