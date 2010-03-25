<?php
/**
 * Sample Project Info model
 * @package repose_tests
 */

/**
 * Sample Project Info model
 * @package repose_tests
 */
class sample_ProjectInfo {

    /**
     * Project Info ID
     * @var int
     */
    public $projectInfoId;

    /**
     * Project
     * @var sample_Project
     */
    public $project;

    /**
     * Description
     * @var string
     */
    public $description;

    /**
     * Constructor
     * @param sample_Project $project Project
     * @param string $description Description
     */
    public function __construct($project, $description) {
        $this->project = $project;
        $this->description = $description;
    }

}

?>
