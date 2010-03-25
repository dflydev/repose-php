<?php
/**
 * Sample User model
 * @package repose_tests
 */

/**
 * Sample User model
 * @package repose_tests
 */
class sample_User {

    /**
     * User ID
     * @var int
     */
    public $userId;

    /**
     * Name
     * @var string
     */
    public $name;

    /**
     * Constructor
     * @param string $name Name
     */
    public function __construct($name) {
        $this->name = $name;
    }

    /**
     * User ID
     * @return int
     */
    public function getUserId() {
        return $this->userId;
    }

    /**
     * Name
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Set name
     * @param string $name Name
     */
    public function setName($name) {
        $this->name = $name;
    }

}

?>
