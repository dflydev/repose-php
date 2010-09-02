<?php
/**
 * Configuration Session Factory
 * @package repose
 */

require_once('repose_Configuration.php');
require_once('repose_Session.php');
require_once('repose_AbstractSessionFactory.php');

/**
 * Configuration Session Factory
 * @package repose
 */
class repose_ConfigurationSessionFactory extends repose_AbstractSessionFactory {

    /**
     * Constructor
     * @param repose_Configuration $configuration Configuration
     */
    public function __construct(repose_Configuration $configuration) {
        $this->configuration = $configuration;
    }

    /**
     * Opens a new session
     * @return repose_Session
     */
    public function openSession() {
        return new repose_Session(
            $this->configuration->engine(),
            $this->configuration->mapping(),
            $this->configuration->autoloader()
        );
    }

}
?>
