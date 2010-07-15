<?php
/**
 * Query
 * @package repose
 */

require_once('repose_QueryResponse.php');
require_once('repose_QueryParser.php');

/**
 * Query
 * @package repose
 */
class repose_Query {

    /**
     * Session
     * @var repose_Session
     */
    protected $session;
    
    /**
     * Mapping
     * @var repose_Mapping
     */
    protected $mapping;
    
    /**
     * Query parser
     * @var repose_QueryParser
     */
    protected $queryParser;
    
    /**
     * Is the query prepared to execute?
     * @var bool
     */
    private $isPrepared = false;

    /**
     * Constructor
     * @param repose_Session $session Repose Session
     * @param repose_Mapping $mapping Repose Mapping
     * @param string $queryString Query string
     */
    public function __construct(repose_Session $session, repose_Mapping $mapping, $queryString) {
        $this->session = $session;
        $this->mapping = $mapping;
        $this->queryParser = new repose_QueryParser($session, $mapping, $queryString);
        $this->queryParser->execute();
    }

    /**
     * Execute query
     * @param array $values
     * @return repose_QueryResponse Response
     */
    public function execute($values = null) {

        $rows = $this->session->engine()->query(
            $this->session,
            $this->queryParser->sql(),
            $values
        );
        
        return new repose_QueryResponse($this->queryParser->generateResults($rows));
        
    }
    
    /**
     * Prepare query for execution
     */
    protected function prepare() {
        
        // Bail if we have already been prepared.
        if ( $this->isPrepared ) return;

        // We are now prepared.
        $this->isPrepared = true;
        
    }

}

?>
