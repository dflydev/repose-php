<?php
/**
 * Query Response
 * @package repose
 */

/**
 * Query Response
 * @package repose
 */
class repose_QueryResponse {

    /**
     * Results
     * @var array
     */
    private $results;

    /**
     * Constructor
     * @param array $results Results
     */
    public function __construct($results = array()) {
        $this->results = $results;
    }

    /**
     * First object
     * @return mixed
     */
    public function first() {
        if ( ! empty($this->results) ) return $this->results[0];
        return null;
    }

    /**
     * One object
     * @return mixed
     */
    public function one() {
        if ( empty($this->results) ) {
            throw new Exception('Expected one result but none were found');
        }
        if ( count($this->results) != 1 ) {
            throw new Exception('Expected one result but more were found');
        }
        return $this->results[0];
    }

    /**
     * All objects
     * @return mixed
     */
    public function all() {
        return $this->results;
    }

    /**
     * Number of objects
     * @return mixed
     */
    public function count() {
        return count($this->results);
    }

}

?>
