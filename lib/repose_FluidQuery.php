<?php
/**
 * Fluid Query
 * @package repose
 */

require_once('repose_Query.php');
require_once('repose_QueryResponse.php');

/**
 * Fluid
 * @package repose
 */
class repose_FluidQuery {

    /**
     * The ID for our next placeholder.
     * @var int
     */
    static private $PLACEHOLDER_NAME_ID = 0;

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
     * Objects to find
     * @var array
     */
    protected $find = array();

    /**
     * Values
     * @var array
     */
    protected $values = array();

    /**
     * Wheres
     * @var array
     */
    protected $where = array();
    
    /**
     * Order by
     * @var array
     */
    protected $orderBy = array();
    
    /**
     * Group by
     * @var array
     */
    protected $groupBy = array();
    
    /**
     * Having
     * @var array
     */
    protected $having = array();
    
    /**
     * Limit
     * @var int
     */
    protected $limit = null;
    
    /**
     * Offset
     * @var int
     */
    protected $offset = null;

    /**
     * Query Response
     * @var repose_QueryResponse
     */
    protected $queryResponse;

    /**
     * Constructor
     * @param repose_Session $session Session
     * @param repose_Mapping $mapping Mapping
     */
    public function __construct($session, $mapping, $find) {
        $this->session = $session;
        $this->mapping = $mapping;
        $this->queryResponse = null;
        $this->find[] = $find;
    }

    /**
     * Filter objects by
     */
    public function filterBy() {
        $args = func_get_args();
        $filters = array();
        if ( count($args) % 2 == 0 ) {
            while ( count($args) ) {
                $k = array_shift($args);
                $v = array_shift($args);
                $filters[$k] = $v;
            }
        } elseif ( count($args) > 0 ) {
            $filters = $args[0];
        }
        foreach ( $filters as $k => $v ) {
            $placeholder = $this->generatePlaceholder();
            $this->wheres[] = $k . ' = :' . $placeholder;
            $this->values[$placeholder] = $v;
        }
        return $this;
    }

    /**
     * Group objects by
     */
    public function groupBy() {
        $args = func_get_args();
        foreach ( $args as $arg ) {
            if ( ! is_array($arg) ) $arg = array($arg);
            foreach ( $arg as $groupBy ) {
                $this->groupBy[] = $groupBy;
            }
        }
        return $this;
    }
    
    /**
     * Order objects by
     */
    public function orderBy() {
        $args = func_get_args();
        foreach ( $args as $arg ) {
            if ( ! is_array($arg) ) $arg = array($arg);
            foreach ( $arg as $orderBy ) {
                $this->orderBy[] = $orderBy;
            }
        }
        return $this;
    }
    
    /**
     * Limit
     * @param string $limit Limit
     */
    public function limit($limit = null) {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Offset
     * @param string $offset Offset
     */
    public function offset($offset = null) {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Perform the query
     * @return repose_QueryResponse
     */
    public function query() {
        if ( $this->queryResponse === null ) {
            $query = new repose_Query(
                $this->session,
                $this->mapping,
                $this->generateQueryString()
            );
            $this->queryResponse = $query->execute($this->values);
        }
        return $this->queryResponse;
    }

    /**
     * Generate a query string
     * @return string
     */
    protected function generateQueryString() {
        $queryString = 'FROM ' . join(', ', $this->find);
        if ( ! empty($this->wheres) ) {
            $queryString .= ' WHERE ' . implode(' AND ', $this->wheres);
        }
        if ( count($this->groupBy) > 0 ) {
            $queryString .= ' GROUP BY ' . implode(', ', $this->groupBy);
        }
        if ( count($this->having) > 0 ) {
            $queryString .= ' HAVING ' . implode(', ', $this->having);
        }
        if ( count($this->orderBy) > 0 ) {
            $queryString .= ' ORDER BY ' . implode(', ', $this->orderBy);
        }
        if ( $this->limit !== null ) {
            $queryString .= ' LIMIT ' . $this->limit;
            if ( $this->offset!== null ) {
                $queryString .= ' OFFSET ' . $this->offset;
            }
        }
        return $queryString;
    }

    /**
     * Generate a placeholder for the query
     * @return string
     */
    protected function generatePlaceholder() {
        return 'repose_anon_placeholder_' . self::$PLACEHOLDER_NAME_ID++;
    }

    /**
     * First object
     * @return mixed
     */
    public function first() {
        return $this->query()->first();
    }

    /**
     * One object
     * @return mixed
     */
    public function one() {
        return $this->query()->one();
    }

    /**
     * All objects
     * @return mixed
     */
    public function all() {
        return $this->query()->all();
    }

    /**
     * Number of objects
     * @return mixed
     */
    public function count() {
        return $this->query()->count();
    }

}

?>
