<?php
/**
 * Session
 * @package repose
 */

require_once('repose_InstanceCache.php');
require_once('repose_IEngine.php');
require_once('repose_Mapping.php');
require_once('repose_Query.php');

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

    /**
     * Constructor
     * @param repose_IEngine $engine Engine
     * @param repose_Mapping $mapping Mapping
     * @param array $options Options
     */
    public function __construct(repose_IEngine $engine, repose_Mapping $mapping) {
        $this->engine = $engine;
        $this->mapping = $mapping;
        $this->instanceCache = new repose_InstanceCache();
    }

    /**
     * Place an object in the Session.
     * @param object $instance Object instance
     * @param string $clazz Class name
     * @return object Proxy
     */
    public function add($instance = null, $clazz = null) {
        if ( $instance === null ) return null;
        return $this->instanceCache->add($this, $instance, $clazz);
    }

    /**
     * Place an object in the Session.
     * @param string $clazz Class name
     * @param array $data Data
     * @return object Proxy
     */
    public function addFromData($clazz, $data) {
        return $this->instanceCache->addFromData($this, $clazz, $data);
    }

    /**
     * Set of all persistent instances marked as pending.
     * @return array
     */
    public function pending() {
        return $this->instanceCache->pending($this);
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
        return $this->instanceCache->delete($this, $instance);
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
    public function execute($queryString, $params) {
        $query = $this->query($queryString);
        return $query->execute($params);
    }

    /**
     * Remove an object instance from the Session.
     */
    public function expunge($instance) {
    }

    /**
     * Flush object changes to the database.
     */
    public function flush() {
        $this->instanceCache->flush($this);
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
        return new repose_Query($this, $queryString);
    }

    /**
     * Refresh the attributes on the given instance.
     * @param object $instance Object instance
     */
    public function refresh($instance) {
    }

    /**
     * Get a property for the specified class
     * @param string $clazz Class name
     * @param string $name Property name
     * @return repose_MappedClassProperty
     */
    public function getProperty($clazz, $name) {
        return $this->mapping->mappedClassProperty($clazz, $name);
    }

    /**
     * Get the properties for the specified class
     * @param string $clazz Class name
     * @return array
     */
    public function getProperties($clazz) {
        return $this->mapping->mappedClassProperties($clazz);
    }

    /**
     * Get the properties for the specified class
     * @param string $clazz Class name
     * @return array
     */
    public function getPropertyNames($clazz) {
        return $this->mapping->mappedClassPropertyNames($clazz);
    }

    /**
     * Get the primary key for the specified class
     * @param string $clazz Class name
     * @return repose_MappedClassPrimaryKey
     */
    public function getPrimaryKey($clazz) {
        return $this->mapping->mappedClassPrimaryKey($clazz);
    }

    /**
     * Get the mapping for a class
     * @param string $clazz Class name
     * @return repose_MappedClass
     */
    public function getMappedClass($clazz) {
        return $this->mapping->mappedClass($clazz);
    }

    /**
     * Serialize primary key data
     * @param repose_IProxy $proxy Proxy object
     * @param string $pendingId ID to use if no primary key data is available
     * @return string
     */
    public function serializePrimaryKey($primaryKeyData, $pendingId = null) {
        if ( empty($primaryKeyData) and $pendingId !== null ) {
            $primaryKeyData['___repose_id'] = $pendingId;
        }
        ksort($primaryKeyData);
        return json_encode(array_map(
            array($this, 'serializePrimaryKeyCb'),
            $primaryKeyData
        ));
    }

    /**
     * Ensure that all of our primary key values are cast as strings
     * @param mixed $v Value
     * @return string
     */
    protected function serializePrimaryKeyCb($v) {
        return (string)$v;
    }

    /**
     * Load an added instance
     * @param string $clazz Class
     * @param mixed $key Primary key
     */
    public function load($clazz, $key) {
        $primaryKey = $this->getPrimaryKey($clazz);
        if ( $primaryKey->isComposite() ) {
            if ( ! is_array($key) ) {
                throw new Exception('Must specify an array to load composite class ' . $clazz);
            }
            return $this->instanceCache->load(
                $clazz,
                $this->serializePrimaryKey($key)
            );
        } else {
            $primaryKeyData = array();
            if ( is_array($key) ) {
                $primaryKeyData = $key;
            } else {
                $primaryKeyData[$primaryKey->property()->name()] = $key;
            }
            return $this->instanceCache->load(
                $clazz,
                $this->serializePrimaryKey($primaryKeyData)
            );
        }
    }

    /**
     * Engine
     * @return repose_IEngine
     */
    public function engine() {
        return $this->engine;
    }

    /**
     * Mapping
     * @return repose_Mapping
     */
    public function mapping() {
        return $this->mapping;
    }

    /**
     * Instance cache
     * @return repose_InstanceCache
     */
    public function instanceCache() {
        return $this->instanceCache;
    }

}

?>
