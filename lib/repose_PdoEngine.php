<?php
/**
 * PDO Engine.
 * @package repose
 */

require_once('repose_AbstractSqlEngine.php');

/**
 * PDO Engine.
 * @package repose
 */
class repose_PdoEngine extends repose_AbstractSqlEngine {

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

    /**
     * Insert data into a table
     * @param string $sql SQL
     * @param array $data Associative array
     */
    protected function doInsert($sql, array $params) {
        $statement = $this->dataSource->prepare($sql);
        $statement->execute(is_null($params) ? array() : $params);
        return $this->dataSource->lastInsertId();
    }

    /**
     * Update data in a table
     * @param string $sql SQL
     * @param array $data Associative array
     */
    protected function doUpdate($sql, array $params) {
        $statement = $this->dataSource->prepare($sql);
        $statement->execute(is_null($params) ? array() : $params);
    }

    /**
     * Delete data from a table
     * @param string $sql SQL
     * @param array $data Associative array
     */
    protected function doDelete($sql, array $params) {
        $statement = $this->dataSource->prepare($sql);
        $statement->execute(is_null($params) ? array() : $params);
    }

    /**
     * Select data
     * @param string $sql SQL
     * @param array $data Associative array
     * @return array
     */
    protected function doSelect($sql, array $params) {
        $rows = array();
        if ( ! is_array($params) ) $params = array();
        try {
            
            $statement = $this->dataSource->prepare($sql);

            /*
            $numeric = null;
            $counter = 1; // Only used for numeric bind params.
            foreach ( $params as $idx => $param ) {

                if ( $numeric === null ) {
                    if ( is_numeric($idx) ) { $numeric = true; }
                    else { $numeric = false; }
                }
                
                $type = PDO::PARAM_STR;

                //if ( is_float($param) ) { $type = PDO::PARAM_FLOAT; }
                //elseif ( is_numeric($param) ) { $type = PDO::PARAM_INT; }
                
                if ( $numeric ) {
                    $statement->bindParam($counter++, $param, $type);
                } else {
                    $statement->bindParam($idx, $param); //, PDO::PARAM_INT);
                }
                
            }
            
            $statement->execute();
            */
            
            $statement->execute(is_null($params) ? array() : $params);

            foreach ( $statement->fetchAll(PDO::FETCH_ASSOC) as $row ) {
                $rows[] = $row;
            }
            
        } catch (Exception $e) {
            print $sql . "\n\n";
            print_r($params);
            print $e->getMessage();
        }
        return $rows;
    }

}
?>
