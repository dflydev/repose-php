<?php
/**
 * Session Factory interface
 * @package repose
 */

require_once('repose_Session.php');

/**
 * Session Factory interface
 * @package repose
 */
interface repose_ISessionFactory {

    /**
     * Get the current session
     * @return repose_Session
     */
    public function currentSession();

    /**
     * Opens a new session
     * @return repose_Session
     */
    public function openSession();

}
?>