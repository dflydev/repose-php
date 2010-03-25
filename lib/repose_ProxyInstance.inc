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
                if ( $property->isObject() and ! $value instanceof repose_IProxy ) {
                    print " [ attempting to add $name ]\n";
                    $this->$name = $value = $session->add($value);
                }
                $this->___repose_history[$name] = $value;
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
                if ( $property->isObject() and ! $value instanceof repose_IProxy ) {
                    print " [ attempting to add $name ]\n";
                    $value = $session->add($value);
                }
                $this->$name = $value;
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

    /**
     * Persist
     * @param repose_Session $session Session
     */
    public function ___repose_persist($session) {
        $this->___repose_flushRelationships($session);
        if ( $this->___repose_isPersisted() ) return;
        print " [ persisting " . $this->___repose_clazz . " ]\n";
        $this->___repose_isPersisted = true;
        $newId = $session->engine()->persist($session, $this);
        $clazz = $this->___repose_clazz;
        $primaryKey = $session->getPrimaryKey($this->___repose_clazz);
        if ( ! $primaryKey->isComposite() ) {
            $primaryKeyProperty = $primaryKey->property();
            $name = $primaryKeyProperty->name();
            print " [ storying $clazz $name as $newId ]\n";
            $this->$name = $newId;
        }
        $oldPk = $this->___repose_serializedPrimaryKey($session, 'history');
        $newPk = $this->___repose_serializedPrimaryKey($session, 'current');
        $this->___repose_populateHistoryFromInstance($session);
        $session->instanceCache()->updateIdentityMap(
            $this->___repose_id,
            $this->___repose_clazz,
            $oldPk,
            $newPk
        );
    }

    /**
     * Flush
     * @param repose_Session $session Session
     */
    public function ___repose_flush($session) {
        $this->___repose_flushRelationships($session);
        if ( ! $this->___repose_isDirty($session) ) return;
        print " [ flushing " . $this->___repose_clazz . " ]\n";
        $session->engine()->update($session, $this);
        $oldPk = $this->___repose_serializedPrimaryKey($session, 'history');
        $newPk = $this->___repose_serializedPrimaryKey($session, 'current');
        $this->___repose_populateHistoryFromInstance($session);
        $this->___repose_isPersisted = true;
        $session->instanceCache()->updateIdentityMap(
            $this->___repose_id,
            $this->___repose_clazz,
            $oldPk,
            $newPk
        );
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

            /*
            if ( $value !== null ) {
                if ( ! $value instanceof repose_IProxy ) {
                    $value = $session->add($value);
                }
                if ( ! $value->___repose_isPersisted() ) {
                    $value->___repose_persist($session);
                } elseif ( $value->___repose_isDirty($session) ) {
                    $value->___repose_flush($session);
                }
                return $value->___repose_primaryKeyValue($session);
            }
            */

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
            if ( isset($this->$name) ) {
                if ( ! isset($this->___repose_history[$name]) or $this->$name != $this->___repose_history[$name] ) {
                    $data[$name] = $this->derivePropertyValue(
                        $session,
                        $property,
                        $this->$name
                    );
                }
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

}

?>