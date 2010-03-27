<?php
/**
 * Repox Proxy Instance
 * @package repose
 */

require_once('repose_Uuid.php');

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
     * Mapped class name
     * @var string
     */
    private $___repose_clazz;

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
     * @param string $proxyClazz Proxy class
     * @param string $clazz Class
     */
    public function ___repose_init($session, $proxyClazz, $clazz, $data = null, $isPersisted = false) {
        $this->___repose_id = repose_Uuid::v4();
        $this->___repose_clazz = $clazz;
        $this->___repose_proxyClazz = $proxyClazz;
        $this->___repose_isPersisted = $isPersisted;
        $this->___repose_populateInstanceFromData($session, $data);
        $this->___repose_populateHistoryFromInstance($session);
        $this->___repose_isDeleted = false;
    }

    /**
     * Internal ID for this proxy instance
     * @return string
     */
    public function ___repose_id() {
        return $this->___repose_id;
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
     * A mapped class
     * @param repose_Session $session Session
     * @param string $clazz Class
     * @return repose_MappedClass
     */
    public function ___repose_mappedClass($session, $clazz = null) {
        if ( $clazz === null ) $clazz = $this->___repose_clazz;
        return $session->getMappedClass($clazz);
    }

    /**
     * Get the properties for a class
     * @param repose_Session $session Session
     * @param string $clazz Class
     * @return array
     */
    public function ___repose_getProperties($session, $clazz = null) {
        if ( $clazz === null ) $clazz = $this->___repose_clazz;
        return $session->getProperties($clazz);
    }

    /**
     * Populate cache from current instance values
     * @param repose_Session $session Session
     */
    public function ___repose_populateHistoryFromInstance($session) {
        foreach ( $this->___repose_getProperties($session) as $property ) {
            $name = $property->name();
            if ( isset($this->$name) ) {
                $value = $this->$name;
                if ( $property->isObject() ) {
                    if ( ! $value instanceof repose_IProxy ) {
                        $this->$name = $value = $session->add($value);
                    }
                    $session->instanceCache()->registerRelationship($session, $value, $this, $property);
                }
                if ( $property->isObject() and $value and $value instanceof repose_IProxy and $value->___repose_isDeleted() ) {
                    $this->___repose_history[$name] = null;
                } else {
                    $this->___repose_history[$name] = $value;
                }
            }
        }
    }

    /**
     * Populate current instance from data
     * @param repose_Session $session Session
     */
    public function ___repose_populateInstanceFromData($session, $data = null) {
        if ( $data === null ) return;
        foreach ( $this->___repose_getProperties($session) as $property ) {
            $name = $property->name();
            if ( isset($data[$name]) ) {
                $value = $data[$name];
                if ( $property->isObject() ) {
                    if ( ! $value instanceof repose_IProxy ) {
                        $value = $session->add($value);
                    }
                    $session->instanceCache()->registerRelationship($session, $value, $this, $property);
                }
                if ( $property->isObject() and $value and $value instanceof repose_IProxy and $value->___repose_isDeleted() ) {
                    $this->$name = null;
                } else {
                    $this->$name = $value;
                }
            }
        }
    }

    /**
     * Is we dirty?
     * @param repose_Session $session Session
     * @return bool
     */
    public function ___repose_isDirty($session) {
        foreach ( $this->___repose_history as $k => $v ) {
            $vTest = isset($this->$k) ? $this->$k : null;
            if ( $v !== $vTest ) return true;
        }
        return false;
    }

    /**
     * Flush relationships
     * @param repose_Session $session Session
     */
    public function ___repose_flushRelationships($session) {
        foreach ( $this->___repose_getProperties($session) as $property ) {
            if ( $property->isObject() ) {
                $name = $property->name();
                $value = $this->$name;
                if ( $value !== null ) {
                    if ( ! $value instanceof repose_IProxy ) {
                        $this->name = $value = $session->add($value);
                    }
                    if ( ! $value->___repose_isPersisted() ) {
                        $value->___repose_persist($session);
                    } elseif ( $value->___repose_isDirty($session) ) {
                        $value->___repose_flush($session);
                    }
                }
            }
        }
    }

    /**
     * Flush
     * @param repose_Session $session Session
     */
    public function ___repose_flush($session) {

        // Avoid deep recursion.
        if ( $this->___repose_isFlushing ) return;

        // Set so if a related object tries to flush us again,
        // we can skip that step.
        $this->___repose_isFlushing = true;

        // Try to recursively flush our relationships.
        $this->___repose_flushRelationships($session);

        // Did we flush?
        $didFlush = true;

        if ( $this->___repose_isDeleted ) {
            if ( $this->___repose_isPersisted ) {
                // Delete this proxy from our engine.
                $newId = $session->engine()->delete($session, $this);
                $this->___repose_isPersisted = false;
            }
        } elseif ( ! $this->___repose_isPersisted ) {

            //
            // If we have never been persisted, we should attempt
            // to do so now. 
            //

            // Persist this proxy in our engine.
            $newId = $session->engine()->persist($session, $this);

            $clazz = $this->___repose_clazz;
            $primaryKey = $session->getPrimaryKey($this->___repose_clazz);

            if ( ! $primaryKey->isComposite() ) {
                $primaryKeyProperty = $primaryKey->property();
                $name = $primaryKeyProperty->name();
                $this->$name = $newId;
            }

            // And now we know...
            $this->___repose_isPersisted = true;

        } elseif ( $this->___repose_isDirty($session) ) {

            //
            // If we have been persisted, we should update
            // the proxy in the engine.
            //

            $session->engine()->update($session, $this);

        } else {
            // Nothing to flush!
            $didFlush = false;
        }

        if ( $didFlush ) {

            // Capture the primary keys from our history and current state.
            $oldPk = $this->___repose_serializedPrimaryKey($session, 'history');
            $newPk = $this->___repose_serializedPrimaryKey($session, 'current');

            // Sync history from our current state.
            $this->___repose_populateHistoryFromInstance($session);

            // Move things around as needed inside of the identity map
            // based on our old and new primary keys.
            $session->instanceCache()->updateIdentityMap(
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
     * @param repose_Session $session Session
     * @param repose_MappedClassProperty $property Property
     * @param mixed $value Current value
     */
    protected function derivePropertyValue($session, $property, $value = null) {
        if ( $property->isObject() ) {
            return $value;
        } else {
            return $value;
        }
    }

    /**
     * Current data
     * @param repose_Session $session Session
     * @return array
     */
    public function ___repose_currentData($session) {
        $data = array();
        foreach ( $this->___repose_getProperties($session) as $property ) {
            $name = $property->name();
            if ( isset($this->$name) ) {
                $data[$name] = $this->derivePropertyValue(
                    $session,
                    $property,
                    $this->$name
                );
            }
        }
        return $data;
    }

    /**
     * Changed data
     * @param repose_Session $session Session
     * @return array
     */
    public function ___repose_changedData($session) {
        $data = array();
        foreach ( $this->___repose_getProperties($session) as $property ) {
            $name = $property->name();
            if ( ! isset($this->___repose_history[$name]) or $this->$name != $this->___repose_history[$name] ) {
                $data[$name] = $this->derivePropertyValue(
                    $session,
                    $property,
                    $this->$name
                );
            }
        }
        return $data;
    }

    /**
     * Primary key data
     * @param repose_Session $session Session
     * @param string $which Which data? 'current' or 'history'
     */
    public function ___repose_primaryKeyData($session, $which = 'current') {
        $data = array();
        $clazz = $this->___repose_clazz;
        foreach ( $session->getPrimaryKey($clazz)->properties() as $property ) {
            $name = $property->name();
            switch($which) {
                case 'current':
                    if ( isset($this->$name) ) $data[$name] = $this->$name;
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
     * Primary key value
     * @param repose_Session $session Session
     * @param string $which Which data? 'current' or 'history'
     */
    public function ___repose_primaryKeyValue($session, $which = 'current') {
        $data = $this->___repose_primaryKeyData($session, $which);
        foreach ( $data as $key => $value ) { return $value; }
        return NULL;
    }

    /**
     * Serialized primary key data
     * @param repose_Session $session Session
     * @param string $which Which data? 'current' or 'history'
     */
    public function ___repose_serializedPrimaryKey($session, $which = 'current') {
        return $session->serializePrimaryKey(
            $this->___repose_primaryKeyData($session, $which),
            $this->___repose_id
        );
    }

    /**
     * Delete
     * @param repose_Session $session Session
     */
    public function ___repose_delete($session) {
        $this->___repose_isDeleted = true;
        $session->instanceCache()->pruneRelationship($session, $this);
    }

    /**
     * Set a property
     * @param repose_Session $session Session
     * @param string $name Property name
     * @param mixed $value Value
     */
    public function ___repose_propertySetter($session, $name, $value = null) {
        $this->$name = $value;
    }

}

?>
