<?php
/**
 * Array Collection
 * @package repose
 */

require_once('repose_Session.php');
require_once('repose_InstanceCache.php');
require_once('repose_IProxy.php');
require_once('repose_ICollection.php');

/**
 * Array Collection
 * @package repose
 */
class repose_ArrayCollection implements repose_ICollection, ArrayAccess, Iterator, Countable {

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
     * Proxy
     * @var string
     */
    private $___repose_proxyId;

    /**
     * Property
     * @var repose_MappedClassProperty
     */
    private $___repose_property;

    /**
     * Queried?
     * @var bool
     */
    private $___repose_isQueried;

    /**
     * Query string
     * @var string
     */
    private $___repose_queryString;

    /**
     * Proxy ID map
     * @var array
     */
    private $___repose_idMap = array();

    /**
     * Container
     * @var array
     */
    private $___repose_container = array();

    /**
     * Constructor
     * @param repose_Session $session Session
     * @param repose_InstanceCache $instanceCache Instance cache
     * @param repose_IProxy $proxy Instance
     * @param repose_MappedClassProperty $property Property
     * @param array $data Data
     */
    public function __construct($session, $instanceCache, repose_IProxy $proxy, repose_MappedClassProperty $property, $data = null) {

        $this->___repose_sessionId = $session->id();
        $this->___repose_instanceCacheId = $instanceCache->id();
        $this->___repose_proxyId = $proxy->___repose_id();
        $this->___repose_property = $property;
        $this->___repose_isQueried = false;

        $this->___repose_queryString =
            'FROM ' . $property->className() .  ' __rc__ ' .
            'WHERE __rc__.' . $property->backref() . ' = :__rc_backref__';

        if ( ! is_null($data) and is_array($data) ) {
            foreach ( $data as $item ) {
                $this->___repose_container[] = $session->add($item);
            }
        }

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
     * Get the proxy
     * @return repose_IProxy
     */
    public function ___repose_proxy() {
        return $this->___repose_instanceCache()->findProxyById(
            $this->___repose_proxyId
        );
    }

    /**
     * Assert that the proxy has been added
     * @param repose_IProxy $proxy Proxy
     */
    public function ___repose_assertAdded(repose_IProxy $proxy) {
        if ( ! isset($this->___repose_idMap[$proxy->___repose_id()]) ) {
            $this->offsetSet("", $proxy);
        }
    }

    /**
     * Prepare collection for processing
     */
    public function ___repose_prepare() {
        if ( ! $this->___repose_isQueried and $this->___repose_proxy()->___repose_isPersisted() ) {
            $results = $this->___repose_session()->execute(
                $this->___repose_queryString,
                array('__rc_backref__' => $this->___repose_proxy())
            );
            foreach ( $results->all() as $row ) {
                $this->___repose_container[] = $row;
                $this->___repose_idMap[$row->___repose_id()] = true;
            }
            $this->___repose_isQueried = true;
        }
    }

    public function offsetSet($offset,$value) {
        // TODO Determine if we actually want to ping the database when
        // we add new items.
        // NOTE If we enable this, it will always ping the database when
        // a relationship is registered via instance cache.
        //$this->___repose_prepare();
        $value = $this->___repose_session()->add($value);
        if ($offset == "") {
            $this->___repose_container[] = $value;
        }else {
            $this->___repose_container[$offset] = $value;
        }
        $this->___repose_idMap[$value->___repose_id()] = true;
    }

    public function offsetExists($offset) {
        $this->___repose_prepare();
        return isset($this->___repose_container[$offset]);
    }

    public function offsetUnset($offset) {
        $this->___repose_prepare();
        if ( $this->offsetExists($offset) ) {
            $value = $this->___repose_container[$offset];
            if ( $this->___repose_property->cascadeDeleteOrphan() ) {
                $this->___repose_session()->delete($value);
            } else {
                $value->___repose_propertySetter(
                    $this->___repose_property->backref(),
                    null
                );
            }
            unset($this->___repose_idMap[$value->___repose_id()]);
        }
        unset($this->___repose_container[$offset]);
    }

    public function offsetGet($offset) {
        $this->___repose_prepare();
        return isset($this->___repose_container[$offset]) ? $this->___repose_container[$offset] : null;
    }

    public function rewind() {
        $this->___repose_prepare();
        reset($this->___repose_container);
    }

    public function current() {
        $this->___repose_prepare();
        return current($this->___repose_container);
    }

    public function key() {
        $this->___repose_prepare();
        return key($this->___repose_container);
    }

    public function next() {
        $this->___repose_prepare();
        return next($this->___repose_container);
    }

    public function valid() {
        $this->___repose_prepare();
        return $this->current() !== false;
    }    

    public function count() {
        $this->___repose_prepare();
        return count($this->___repose_container);
    }

}

?>
