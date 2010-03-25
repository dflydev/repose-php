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

    /**
     * Project Info ID
     * @return int
     */
    public function getProjectInfoId() {
        return $this->projectInfoId;
    }

    /**
     * Project
     * @return sample_Project
     */
    public function getProject() {
        return $this->project;
    }

    /**
     * Description
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * Set description
     * @param string $description Description
     */
    public function setDescription($description) {
        $this->description = $description;
    }

}

?>
