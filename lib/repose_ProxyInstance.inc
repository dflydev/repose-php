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
     * @param repose_Session $session Session
     * @param string $proxyClazz Proxy class
     * @param string $clazz Class
     */
    public function ___repose_init($session, $proxyClazz, $clazz) {
        $this->___repose_clazz = $clazz;
        $this->___repose_proxyClazz = $proxyClazz;
        $this->___repose_populateCacheFromInstance($session);
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
    public function ___repose_populateCacheFromInstance($session) {
        foreach ( $this->___repose_getProperties($session) as $property ) {
            $name = $property->name();
            $this->___repose_history[$name] = isset($this->$name) ? $this->$name : null;
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

}

?>
