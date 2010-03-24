<?php
/**
 * Engine interface.
 * @package repose
 */

require_once('repose_Session.php');
require_once('repose_IProxy.php');

/**
 * Engine interface.
 * @package repose
 */
interface repose_IEngine {

    /**
     * Persist a proxy
     *
     * Think INSERT.
     *
     * @param repose_Session $session Session
     * @param repose_IProxy $proxy Proxy
     */
    public function persist(repose_Session $session, repose_IProxy $proxy);

    /**
     * Update a persisted proxy
     *
     * Think UPDATE WHERE.
     *
     * @param repose_Session $session Session
     * @param repose_IProxy $proxy Proxy
     */
    public function update(repose_Session $session, repose_IProxy $proxy);

    /**
     * Delete a persisted proxy
     *
     * Think DELETE WHERE.
     *
     * @param repose_Session $session Session
     * @param repose_IProxy $proxy Proxy
     */
    public function delete(repose_Session $session, repose_IProxy $proxy);

}

?>
