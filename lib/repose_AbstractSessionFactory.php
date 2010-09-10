<?php
/**
 * Abstract Session Factory a
 * @package repose
 */

require_once('repose_Session.php');
require_once('repose_ISessionFactory.php');

/**
 * Abstract Session Factory
 * @package repose
 */
abstract class repose_AbstractSessionFactory implements repose_ISessionFactory {

    /**
     * Current Session
     * @var repose_Session
     */
    protected $currentSession = null;

    /**
     * Get the current session
     * @return repose_Session
     */
    public function currentSession() {
        if ( $this->currentSession === null ) {
            $this->currentSession = $this->openSession();
        }
        return $this->currentSession;
    }

}
?>