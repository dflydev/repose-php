<?php
/**
 * Repox Proxy Instance
 * @package repose
 */

/**
 * Repox Proxy Instance Class
 * @package repose
 */
class PROXY_TEMPLATE__ReposeProxy__ extends PROXY_TEMPLATE implements repose_IProxy {

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
    public function ___repose_init($session, $proxyClazz, $clazz, $data = null) {
        $this->___repose_clazz = $clazz;
        $this->___repose_proxyClazz = $proxyClazz;
        $this->___repose_populateInstanceFromData($session, $data);
        $this->___repose_populateHistoryFromInstance($session);
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
            $this->___repose_history[$name] = isset($this->$name) ? $this->$name : null;
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
            if ( isset($data[$name]) ) $this->$name = $data[$name];
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
     * Persist
     * @param repose_Session $session Session
     */
    public function ___repose_persist($session) {
        print " [ persisting " . $this->___repose_clazz . " ]\n";
        $session->engine()->persist($session, $this);
        $this->___repose_populateHistoryFromInstance($session);
    }

    /**
     * Flush
     * @param repose_Session $session Session
     */
    public function ___repose_flush($session) {
        print " [ flushing " . $this->___repose_clazz . " ]\n";
        $session->engine()->update($session, $this);
        $this->___repose_populateHistoryFromInstance($session);
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
            if ( isset($this->$name) ) $data[$name] = $this->$name;
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
                    $data[$name] = $this->$name;
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

}

?>
