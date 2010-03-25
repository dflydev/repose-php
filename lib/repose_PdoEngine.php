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
     * @param string $tableName Table nane
     * @param array $data Associative array
     */
    protected function sqlInsert($tableName, $data) {
        $columns = array();
        $placeholders = array();
        $values = array();
        foreach ( $data as $columnName => $value ) {
            $columns[] = $columnName;
            $values[$columnName] = $value;
            $placeholders[] = ':' . $columnName;
        }
        $sql = 'INSERT INTO ' . $tableName . ' (' . implode(',', $columns) . ') VALUES (' . implode(',', $placeholders) . ')';
        print " [ $sql ]\n";
        print_r($values);
        $statement = $this->dataSource->prepare($sql);
        $statement->execute($values);
        return $this->dataSource->lastInsertId();
    }

    /**
     * Update data in a table
     * @param string $tableName Table nane
     * @param array $data Associative array with updated data
     * @param array $where Associative array containing WHERE information
     */
    protected function sqlUpdate($tableName, $data, $where) {
        $sets = array();
        $wheres = array();
        $values = array();
        foreach ( $data as $columnName => $value ) {
            $sets[] = $columnName . ' = :' . $columnName;
            $values[$columnName] = $value;
        }
        foreach ( $where as $columnName => $value ) {
            $wheres[] = $columnName . ' = :where_' . $columnName;
            $values['where_' . $columnName] = $value;
        }
        $sql = 'UPDATE ' . $tableName . ' SET ' . implode(', ', $sets) . ' WHERE ' . implode(' AND ', $wheres);
        print " [ $sql ]\n";
        print_r($values);
        $statement = $this->dataSource->prepare($sql);
        $statement->execute($values);
    }

}
?>
