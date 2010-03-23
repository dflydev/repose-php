<?php
/**
 * PDO Engine.
 * @package repose
 */

require_once('repose_IEngine.php');

/**
 * PDO Engine.
 * @package repose
 */
class repose_PdoEngine implements repose_IEngine {

    /**
     * Data source
     * @var PDO
     */
    protected $dataSource;

    /**
     * Constructor
     * @param PDO $dataSource PDO Data Source
     */
    public function __construct($dataSource) {
        $this->dataSource = $dataSource;
    }

}
?>
