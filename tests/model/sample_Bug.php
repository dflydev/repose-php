<?php
/**
 * Sample Bug model
 * @package repose_tests
 */

/**
 * Sample Bug model
 * @package repose_tests
 */
class sample_Bug {

    /**
     * Bug ID
     * @var int
     */
    public $bugId;

    /**
     * Title
     * @var string
     */
    public $title;

    /**
     * Body
     * @var string
     */
    public $body;

    /**
     * Project
     * @var sample_Project
     */
    public $project;

    /**
     * Reporter
     * @var sample_User
     */
    public $reporter;

    /**
     * Owner
     * @var sample_User
     */
    public $owner;

    /**
     * Constructor
     * @param sample_Project $project Project
     * @param string $title Title
     * @param string $body Body
     * @param sample_User $reporter Reporter
     * @param sample_User $owner Owner
     */
    public function __construct($project, $title, $body, $reporter, $owner = null) {
        $this->project = $project;
        $this->title = $title;
        $this->body = $body;
        $this->reporter = $reporter;
        $this->owner = $owner;
    }

}

?>
