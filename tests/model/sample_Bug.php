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

    /**
     * Bug ID
     * @return int
     */
    public function getBugId() {
        return $this->bugId;
    }

    /**
     * Project
     * @return sample_Project
     */
    public function getProject() {
        return $this->project;
    }

    /**
     * Set project
     * @param sample_Project $project Project
     */
    public function setProject($project) {
        $this->project = $project;
    }

    /**
     * Title
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Set title
     * @param string $title Title
     */
    public function setTitle($title) {
        $this->title = $title;
    }

    /**
     * Body
     * @return string
     */
    public function getBody() {
        return $this->body;
    }

    /**
     * Set body
     * @param string $body body
     */
    public function setBody($body) {
        $this->body = $body;
    }

    /**
     * Reporter
     * @return sample_User
     */
    public function getReporter() {
        return $this->reporter;
    }

    /**
     * Set reporter
     * @param sample_User $reporter Reporter
     */
    public function setReporter($reporter) {
        $this->reporter = $reporter;
    }

    /**
     * Owner
     * @return sample_User
     */
    public function getOwner() {
        return $this->owner;
    }

    /**
     * Set owner
     * @param sample_User $owner Owner
     */
    public function setOwner($owner) {
        $this->owner = $owner;
    }

}

?>
