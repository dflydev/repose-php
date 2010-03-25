<?php
/**
 * Sample Project model
 * @package repose_tests
 */

/**
 * Sample Project model
 * @package repose_tests
 */
class sample_Project {

    /**
     * Project ID
     * @var int
     */
    public $projectId;

    /**
     * Project name
     * @var string
     */
    public $name;

    /**
     * Manager (User)
     * @var sample_User
     */
    public $manager;

    /**
     * Constructor
     * @param string $name Name
     * @param sample_User $manager Manager
     */
    public function __construct($name, $manager) {
        $this->name = $name;
        $this->manager = $manager;
    }

    /**
     * Access to project ID
     * @return int
     */
    public function getProjectId() {
        return $this->projectId;
    }

    /**
     * Project name
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Set project name
     * @param string $name Name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * Get project manager
     * @return sample_User
     */
    public function getManager() {
        return $this->manager;
    }

    /**
     * Set project manager
     * @param sample_User $manager Manager
     */
    public function setManager($manager) {
        $this->manager = $manager;
    }
}

?>
