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

}

?>
