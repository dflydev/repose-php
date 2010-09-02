<?php
/**
 * Sample Resource model
 * @package repose_tests
 */

/**
 * Sample Resource model
 * @package repose_tests
 */
class sample_Resource {

    /**
     * Resource ID
     * @var string
     */
    public $resourceId;
    
    /**
     * Name
     * @var string
     */
    public $name;

    /**
     * Constructor
     * @param string $resourceId
     * @param string $name
     */
    public function __construct($resourceId, $name) {
        $this->resourceId = $resourceId;
        $this->name = $name;
    }
    
}

?>