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
     * Favorite project
     * @var unknown_type
     */
    public $favoriteProject;

    /**
     * Constructor
     * @param string $name Name
     */
    public function __construct($name) {
        $this->name = $name;
    }

}

?>
