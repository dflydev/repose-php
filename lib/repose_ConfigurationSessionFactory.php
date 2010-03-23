<?php
/**
 * Configuration Session Factory
 * @package repose
 */

require_once('repose_Configuration.php');
require_once('repose_Session.php');

/**
 * Configuration Session Factory
 * @package repose
 */
class repose_ConfigurationSessionFactory {

    /**
     * Constructor
     * @param repose_Configuration $configuration Configuration
     */
    public function __construct(repose_Configuration $configuration) {
        $this->configuration = $configuration;
        $this->currentSession = $this->openSession();
    }

    /**
     * Get the current session
     * @return respose_Session
     */
    public function currentSession() {
        return $this->currentSession;
    }

    /**
     * Opens a new session
     * @return respose_Session
     */
    public function openSession() {
        return new repose_Session(
            $this->configuration->engine(),
            $this->configuration->mapping()
        );
    }

}
?>
