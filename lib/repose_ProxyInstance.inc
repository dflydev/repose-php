<?php
/**
 * Repox Proxy Instance
 * @package repose
 */

require_once('repose_Uuid.php');
require_once('repose_ArrayCollection.php');

if ( ! class_exists('PROXY_TEMPLATE') ) {
    /**
     * Placeholder proxy for the proxy template class
     */
    class PROXY_TEMPLATE {}
}

/**
 * Repox Proxy Instance Class
 * @package repose
 */
class PROXY_TEMPLATE__ReposeProxy__ extends PROXY_TEMPLATE implements repose_IProxy {

    /**
     * ID
     * @var string
     */
    private $___repose_id;

    /**
     * Session
     * @var string
     */
    private $___repose_sessionId;

    /**
     * Instance cache
     * @var string
     */
    private $___repose_instanceCacheId;

    /**
     * Mapped class name
     * @var string
     */
    private $___repose_clazz;

    /**
     * Reflection properties
     * @var array
     */
    private $___repose_reflectionProperties;

    /**
     * Proxy class name
     * @var string
     */
    private $___repose_proxyClazz;

    /**
     * Data history
     * @var array
     */
    private $___repose_history;

    /**
     * Is persisted?
     * @var bool
     */
    private $___repose_isPersisted;

    /**
     * Is deleted?
     * @var bool
     */
    private $___repose_isDeleted;
    
    /**
     * Is really deleted?
     * @var bool
     */
    private $___repose_isReallyDeleted;
    
    /**
     * Are we flushing?
     * @var bool
     */
    private $___repose_isFlushing = false;

    /**
     * Constructor
     */
    public function __construct() {
        // noop
    }

    /**
     * Initialization
     * @param repose_Session $session Session
     * @param repose_InstanceCache $instanceCache Instance cache
     * @param string $proxyClazz Proxy class
     * @param string $clazz Class
     * @param array $reflectionProperties Reflection properties
     */
    public function ___repose_init($session, $instanceCache, $proxyClazz, $clazz, $reflectionProperties, $data = null, $isPersisted = false) {
        $this->___repose_id = repose_Uuid::v4();
        $this->___repose_sessionId = $session->id();
        $this->___repose_instanceCacheId = $instanceCache->id();
        $this->___repose_proxyClazz = $proxyClazz;
        $this->___repose_clazz = $clazz;
        $this->___repose_reflectionProperties = $reflectionProperties;
        $this->___repose_isPersisted = $isPersisted;
        $this->___repose_populateInstanceFromData($data);
        $this->___repose_populateHistoryFromInstance();
        $this->___repose_isDeleted = false;
        $this->___repose_isReallyDeleted = false;
    }

    /**
     * Internal ID for this proxy instance
     * @return string
     */
    public function ___repose_id() {
        return $this->___repose_id;
    }

    /**
     * Get the session
     * @return repose_Session
     */
    public function ___repose_session() {
        return repose_Session::BY_ID($this->___repose_sessionId);
    }

    /**
     * Get the instance cache
     * @return repose_InstanceCache
     */
    public function ___repose_instanceCache() {
        return repose_InstanceCache::BY_ID($this->___repose_instanceCacheId);
    }

    /**
     * Is persisted?
     * @return bool
     */
    public function ___repose_isPersisted() {
        return $this->___repose_isPersisted;
    }

    /**
     * Is deleted?
     * @return bool
     */
    public function ___repose_isDeleted() {
        return $this->___repose_isDeleted;
    }
    
    /**
     * Is really deleted?
     * @return bool
     */
    public function ___repose_isReallyDeleted() {
        return $this->___repose_isReallyDeleted;
    }
    
    /**
     * A mapped class
     * @param string $clazz Class
     * @return repose_MappedClass
     */
    public function ___repose_mappedClass($clazz = null) {
        if ( $clazz === null ) $clazz = $this->___repose_clazz;
        return $this->___repose_session()->getMappedClass($clazz);
    }

    /**
     * Get the properties for a class
     * @param string $clazz Class
     * @return array
     */
    public function ___repose_getProperties($clazz = null) {
        if ( $clazz === null ) $clazz = $this->___repose_clazz;
        return $this->___repose_session()->getProperties($clazz);
    }

    /**
     * Get a property for a class
     * @param string $clazz Class
     * @param string $name Name of property
     * @return array
     */
    public function ___repose_getProperty($clazz = null, $name) {
        if ( $clazz === null ) $clazz = $this->___repose_clazz;
        return $this->___repose_session()->getProperty($clazz, $name);
    }

    /**
     * Populate cache from current instance values
     */
    public function ___repose_populateHistoryFromInstance() {
        foreach ( $this->___repose_getProperties() as $property ) {
            $name = $property->name();
            $value = $this->___repose_propertyGetter($name);
            if ( $property->isObject() ) {
                if ( ! is_null($value) ) {
                    if ( ! $value instanceof repose_IProxy ) {
                        $value = $this->___repose_session()->add($value);
                        $this->___repose_propertySetter($name, $value);
                    }
                    $this->___repose_instanceCache()->registerRelationship($value, $this, $property);
                }
            }
            if ( $property->isCollection() and ! $value instanceof repose_ICollection ) {
                $value = new repose_ArrayCollection(
                    $this->___repose_session(),
                    $this->___repose_instanceCache(),
                    $this,
                    $property,
                    $value
                );
                $this->___repose_propertySetter($name, $value);
            }
            if ( $property->isObject() and $value and $value instanceof repose_IProxy and $value->___repose_isDeleted() ) {
                $this->___repose_history[$name] = null;
            } else {
                $this->___repose_history[$name] = $value;
            }
        }
    }

    /**
     * Populate current instance from data
     */
    public function ___repose_populateInstanceFromData($data = null) {
        if ( $data === null ) return;
        foreach ( $this->___repose_getProperties() as $property ) {
            $name = $property->name();
            $value = isset($data[$name]) ? $data[$name] : null;
            if ( $property->isObject() ) {
                if ( ! is_null($value) ) {
                    if ( ! $value instanceof repose_IProxy ) {
                        $value = $this->___repose_session()->add($value);
                    }
                    $this->___repose_instanceCache()->registerRelationship($value, $this, $property);
                }
            }
            if ( $property->isCollection() and ! $value instanceof repose_ICollection ) {
                $value = new repose_ArrayCollection(
                    $this->___repose_session(),
                    $this->___repose_instanceCache(),
                    $this,
                    $property,
                    $value
                );
            }
            if ( $property->isObject() and $value and $value instanceof repose_IProxy and $value->___repose_isDeleted() ) {
                $this->___repose_propertySetter($name, null);
            } else {
                $this->___repose_propertySetter($name, $value);
            }
        }
    }

    /**
     * Is we dirty?
     * @return bool
     */
    public function ___repose_isDirty() {
        foreach ( $this->___repose_getProperties() as $property ) {
            if ( $property->isCollection() ) continue;
            $name = $property->name();
            $value = isset($this->___repose_history[$name]) ?
                $this->___repose_history[$name] : null;
            $vTest = $this->___repose_propertyGetter($name);
            if ( $value !== $vTest ) return true;
        }
        return false;
    }

    /**
     * Flush relationships
     */
    public function ___repose_flushRelationships() {
        foreach ( $this->___repose_getProperties() as $property ) {
            if ( $property->isObject() ) {
                $name = $property->name();
                $value = $this->___repose_propertyGetter($name);
                if ( $value !== null ) {
                    if ( ! $value instanceof repose_IProxy ) {
                        $value = $this->___repose_session()->add($value);
                        $this->___repose_propertySetter($name, $value);
                    }
                    if ( ! $value->___repose_isPersisted() ) {
                        //$value->___repose_persist();
                    } elseif ( $value->___repose_isDirty() ) {
                        $value->___repose_flush();
                    }
                }
            }
        }
    }

    /**
     * Flush
     */
    public function ___repose_flush() {

        // Avoid deep recursion.
        if ( $this->___repose_isFlushing ) return;

        // Set so if a related object tries to flush us again,
        // we can skip that step.
        $this->___repose_isFlushing = true;

        // Try to recursively flush our relationships.
        $this->___repose_flushRelationships();

        // Did we flush?
        $didFlush = true;

        if ( $this->___repose_isDeleted ) {
            if ( $this->___repose_isPersisted ) {
                // Delete this proxy from our engine.
                $newId = $this->___repose_session()->engine()->delete($this->___repose_session(), $this);
                $this->___repose_isPersisted = false;
            }
            $this->___repose_isReallyDeleted = true;
        } elseif ( ! $this->___repose_isPersisted ) {

            //
            // If we have never been persisted, we should attempt
            // to do so now. 
            //

            $clazz = $this->___repose_clazz;
            $primaryKey = $this->___repose_session()->getPrimaryKey($this->___repose_clazz);
            $primaryKeyProperty = $primaryKey->isComposite() ? null : $primaryKey->property();
            
            if ( $primaryKeyProperty !== null ) {
                $name = $primaryKeyProperty->name();
                switch($primaryKeyProperty->generator()) {
                    case 'uuid':
                        $this->___repose_propertySetter($name, repose_Uuid::v4());
                        break;
                }
            }
            
            // Persist this proxy in our engine.
            $newId = $this->___repose_session()->engine()->persist($this->___repose_session(), $this);

            if ( $primaryKeyProperty !== null ) {
                if ( $primaryKeyProperty->generator() == 'auto' ) {
                    $name = $primaryKeyProperty->name();
                    $this->___repose_propertySetter($name, $newId);
                }
            }

            // And now we know...
            $this->___repose_isPersisted = true;

        } elseif ( $this->___repose_isDirty() ) {

            //
            // If we have been persisted, we should update
            // the proxy in the engine.
            //

            $this->___repose_session()->engine()->update($this->___repose_session(), $this);

        } else {
            // Nothing to flush!
            $didFlush = false;
        }

        if ( $didFlush ) {

            // Capture the primary keys from our history and current state.
            $oldPk = $this->___repose_serializedPrimaryKey('history');
            $newPk = $this->___repose_serializedPrimaryKey('current');

            // Sync history from our current state.
            $this->___repose_populateHistoryFromInstance();

            // Move things around as needed inside of the identity map
            // based on our old and new primary keys.
            $this->___repose_instanceCache()->updateIdentityMap(
                $this->___repose_id,
                $this->___repose_clazz,
                $oldPk,
                $newPk
            );

        }

        // We are no longer flushing.
        $this->___repose_isFlushing = false;

    }

    /**
     * Derive the actual data store value for a property
     * @param repose_MappedClassProperty $property Property
     * @param mixed $value Current value
     */
    protected function derivePropertyValue($property, $value = null) {
        if ( $property->isObject() ) {
            return $value;
        } elseif ( $property->isCollection() ) {
            return $value;
        } else {
            return $value;
        }
    }

    /**
     * Current data
     * @return array
     */
    public function ___repose_currentData() {
        $data = array();
        foreach ( $this->___repose_getProperties() as $property ) {
            $name = $property->name();
            $value = $this->___repose_propertyGetter($name);
            if ( isset($value) ) {
                if ( ! $property->isCollection() ) {
                    $data[$name] = $this->derivePropertyValue(
                        $property,
                        $value
                    );
                }
            }
        }
        return $data;
    }

    /**
     * Changed data
     * @return array
     */
    public function ___repose_changedData() {
        $data = array();
        foreach ( $this->___repose_getProperties() as $property ) {
            $name = $property->name();
            if ( ! $property->isCollection() ) {
                $value = $this->___repose_propertyGetter($name);
                if ( ! isset($this->___repose_history[$name]) or $value != $this->___repose_history[$name] ) {
                    $data[$name] = $this->derivePropertyValue(
                        $property,
                        $value
                    );
                }
            }
        }
        return $data;
    }

    /**
     * Primary key data
     * @param string $which Which data? 'current' or 'history'
     */
    public function ___repose_primaryKeyData($which = 'current') {
        $data = array();
        $clazz = $this->___repose_clazz;
        foreach ( $this->___repose_session()->getPrimaryKey($clazz)->properties() as $property ) {
            $name = $property->name();
            switch($which) {
                case 'current':
                    $value = $this->___repose_propertyGetter($name);
                    if ( isset($value) ) $data[$name] = $value;
                    break;
                case 'history':
                    if ( isset($this->___repose_history[$name]) ) {
                        $data[$name] = $this->___repose_history[$name];
                    }
                    break;
            }
        }
        return $data;
    }

    /**
     * Primary key
     * @return repose_MappedClassPrimaryKey
     */
    public function ___repose_primaryKey() {
        return $this->___repose_session()->getPrimaryKey($this->___repose_clazz);
    }

    /**
     * Primary key value
     * @param string $which Which data? 'current' or 'history'
     */
    public function ___repose_primaryKeyValue($which = 'current') {
        $data = $this->___repose_primaryKeyData($which);
        foreach ( $data as $key => $value ) { return $value; }
        return NULL;
    }

    /**
     * Serialized primary key data
     * @param string $which Which data? 'current' or 'history'
     */
    public function ___repose_serializedPrimaryKey($which = 'current') {
        return $this->___repose_session()->serializePrimaryKey(
            $this->___repose_primaryKeyData($which),
            $this->___repose_id
        );
    }

    /**
     * Delete
     */
    public function ___repose_delete() {
        $this->___repose_isDeleted = true;
        $this->___repose_instanceCache()->pruneRelationship($this);
    }

    /**
     * Get a property
     * @param string $name Property name
     * @return mixed
     */
    public function ___repose_propertyGetter($name) {
        $reflectionProperty = $this->___repose_reflectionProperties[$name];
        return $reflectionProperty->getValue($this);
    }

    /**
     * Set a property
     * @param string $name Property name
     * @param mixed $value Value
     */
    public function ___repose_propertySetter($name, $value = null) {
        $reflectionProperty = $this->___repose_reflectionProperties[$name];
        return $reflectionProperty->setValue($this, $value);
    }

    /**
     * Destroy
     */
    public function destroy() {
        //$this->session = null;
        //$this->instanceCache = null;
    }

    /**
     * Instance Cache by ID
     * @param string $id Instance Cache ID
     * @return repose_Session
     */
    static public function SESSION($id) {
        if ( isset(self::$CACHE[$id]) ) return self::$CACHE[$id];
        return null;
    }

}

?>
