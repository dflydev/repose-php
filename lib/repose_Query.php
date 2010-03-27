<?php
/**
 * Query
 * @package repose
 */

/**
 * Query
 * @package repose
 */
class repose_Query {

    private $where = '';

    private $select = array();

    private $selectActualAlias = array();

    private $selectPath = array();

    private $selectIdx = 0;

    private $selectExpressions = array();

    private $selectResults = null;

    private $from = array();

    private $fromActualAlias = array();

    private $fromAlias = array();

    private $fromPath = array();

    private $fromIdx = 0;

    private $tableAliasCounter = 0;

    private $columnAliasCounter = 0;

    private $sql = null;

    protected $session;
    public function __construct($session, $queryString) {
        $this->session = $session;
        $this->parseQueryString($queryString);
        $this->sql = $this->generateSql();
    }
    protected function processFrom($fromPart, $path = null) {
        $fromInfo = array(
            'idx' => $this->fromIdx,
            'className' => null,
            'alias' => null,
            'actualAlias' => 'rta_t' . $this->tableAliasCounter++,
        );
        if ( preg_match('/^(\S+)\s+(as\s+|)(\S+)$/is', $fromPart, $fromActualAliasMatch) ) {
            $fromInfo['className'] = $fromActualAliasMatch[1];
            $fromInfo['alias'] = $fromActualAliasMatch[3];
        } else {
            $fromInfo['className'] = $fromPart;
            $fromInfo['alias'] = $fromPart;
        }
        $config = $this->session->getMappedClass($fromInfo['className']);
        $fromInfo['tableName'] = $config->tableName();
        $fromInfo['path'] = $path === null ? $fromInfo['alias'] : $path;
        $primaryKey = $config->primaryKey();
        $fromInfo['primaryKeyColumnName'] = $primaryKey->property()->columnName($this->session->mapping());
        $fromInfo['primaryKeyPropertyName'] = $primaryKey->property()->name();;
        $this->fromActualAlias[$fromInfo['actualAlias']] = $this->fromIdx;
        $this->fromAlias[$fromInfo['alias']] = $this->fromIdx;
        $this->fromPath[$fromInfo['path']] = $fromInfo;
        $this->from[$this->fromIdx++] = $fromInfo;
        return $fromInfo;
    }
    protected function processFromForSelect($from, $base = null) {

        $config = $this->session->getMappedClass($from['className']);

        $path = $base === null ?  $from['alias'] : $base;

        foreach ( $config->mappedClassProperties() as $property ) {

            if ( $property->isObject() ) {
                $objectPath = implode('.', array($path, $property->name()));
                $relatedFrom = $this->processFrom($property->className(), $objectPath);
                $this->processFromForSelect($relatedFrom, $objectPath);
            } else {

                $selectInfo = array(
                    'idx' => $this->selectIdx,
                    'path' => $path,
                    'actualAlias' => 'c' . $this->columnAliasCounter++,
                    'propertyName' => $property->name(),
                );
                $this->selectExpressions[] =
                    $from['actualAlias'] . '.' . $property->columnName($this->session->mapping()) . ' AS ' . $selectInfo['actualAlias'];

                $this->selectActualAlias[$selectInfo['actualAlias']] = $this->selectIdx;
                if ( ! isset($this->selectPath[$selectInfo['path']]) ) {
                    $this->selectPath[$selectInfo['path']] = array();
                }
                $this->selectPath[$selectInfo['path']][] = $this->selectIdx;
                $this->select[$this->selectIdx++] = $selectInfo;
            }
        }
    }
    protected function parseQueryString($queryString) {
        if ( preg_match("/from\s+(.+?)\s*(join|where|group\s+by|limit|$)/is", $queryString, $fromMatches) ) {
            foreach ( preg_split('/\s*,\s*/', $fromMatches[1]) as $fromPart ) {
                $this->processFrom($fromPart);
            }
        }
        if ( preg_match("/(join\s+.+?)\s*(where|group\s+by|limit|$)/is", $queryString, $joinMatches) ) {
        }
        foreach ( $this->from as $from ) {
            $this->processFromForSelect($from);
        }
        if ( preg_match("/where\s+(.+?)\s*(group\s+by|limit|$)/is", $queryString, $whereMatches) ) {
            $rawWhere = $whereMatches[1];
            if ( preg_match_all('/([\w\.\:]+)/', $rawWhere, $fields) ) {
                foreach ( $fields[1] as $field ) {
                    if ( strpos($field, ':') === false ) {
                        if ( preg_match('/^(.+)\.([^\.]+)$/s', $field, $fieldParts) ) {

                            // Break out the matches.
                            list($dummy, $object, $propertyName) = $fieldParts;

                            $objectFrom = $this->fromPath[$object];
                            $property = $this->session->getProperty(
                                $objectFrom['className'],
                                $propertyName
                            );

                            $rawWhere = preg_replace('/' . $field . '/s', $objectFrom['actualAlias'] . '.' . $property->columnName($this->session->mapping()), $rawWhere);

                        }
                    }
                }
            }
            $this->where = $rawWhere;
        }
        if ( preg_match("/select\s+(.+?)\s*(from|join|where|group\s+by|limit|$)/is", $queryString, $selectMatches) ) {
            $rawSelect = $selectMatches[1];
            $this->selectResults = preg_split('/\s*,\s*/', $rawSelect);
        }
    }
    public function execute($values = null) {
        $rows = $this->session->engine()->query(
            $this->session,
            $this->sql,
            $values
        );
        $results = array();
        foreach ( $rows as $row ) {
            $objects = array();
            foreach ( $this->from as $from ) {
                $objectData = array();
                foreach ( $this->selectPath[$from['path']] as $selectIdx ) {
                    $select = $this->select[$selectIdx];
                    $objectData[$select['propertyName']]= $row[$select['actualAlias']];
                }
                if ( ! $objectData[$from['primaryKeyPropertyName']] ) {
                    continue;
                }
                $object = $this->session->load(
                    $from['className'],
                    $objectData[$from['primaryKeyPropertyName']],
                    false
                );
                if ( $object === null ) {
                    $objects[$from['path']] = array(
                        'object' => $this->session->addFromData(
                            $from['className'],
                            $objectData
                        ),
                        'from' => $from,
                    );
                } else {
                    $objects[$from['path']] = array(
                        'object' => $object,
                        'from' => $from,
                    );
                }

            }

            $result = array();
            foreach ( $objects as $path => $objectInfo ) {

                if ( preg_match('/^(.+)\.([^\.]+)$/s', $path, $pathParts) ) {

                    // Break out the matches.
                    list($dummy, $parent, $propertyName) = $pathParts;

                    $parentObject = $objects[$parent];

                    $parentObject['object']->___repose_propertySetter(
                        $this->session,
                        $propertyName,
                        $objectInfo['object']
                    );

                } else {
                    if ( $this->selectResults === null ) {
                        $result[] = $objectInfo['object'];
                    }
                }

            }

            if ( $this->selectResults !== null ) {
                foreach ( $this->selectResults as $selectResult ) {
                    $result[] = $objects[$selectResult]['object'];
                }
            }

            if ( count($result) == 1 ) {
                $results[] = $result[0];
            } else {
                $results[] = $result;
            }

        }
        if ( count($results) ) {
            if ( is_array($results[0]) ) {
                throw new Exception('Multiple return objects currently unsupported.');
            } else {
                $nonUniqueResults = $results;
                $results = array();
                foreach ( $nonUniqueResults as $result ) {
                    if ( ! in_array($result, $results, true) ) {
                        $results[] = $result;
                    } else {
                        echo " [ already found ]\n";
                    }
                }
            }
        }
        return $results;;

    }
    public function generateSql() {

        $sql = '';

        $sql .= 'SELECT ';
        $sql .= implode(",\n       ", $this->selectExpressions);
        $sql .= "\n";

        $joinExpressions = array();
        $leftJoinExpressions = array();
        foreach ( $this->from as $from ) {

            if ( preg_match('/^(.+)\.([^\.]+)$/', $from['path'], $relationshipMatches) ) {

                // Break out the matches.
                list($dummy, $parent, $method) = $relationshipMatches;

                // Get the related from.
                $relatedFrom = $this->fromPath[$parent];

                $property = $this->session->getProperty(
                    $relatedFrom['className'],
                    $method
                );

                $leftJoinExpressions[] =
                    $from['tableName'] . ' AS ' . $from['actualAlias'] .
                    ' ON (' .
                    $from['actualAlias'] .'.' . $property->foreignKey($this->session->mapping()) .
                    ' = ' .
                    $relatedFrom['actualAlias'] . '.' . $property->columnName($this->session->mapping()) .
                    ')';
            } else {
                $joinExpressions[] =
                    $from['tableName'] . ' AS ' . $from['actualAlias'];
            }
        }
        $sql .= '  FROM ';
        if ( count($joinExpressions) ) {
            $sql .= implode(",\n       ", $joinExpressions) . "\n      ";
        }
        if ( count($leftJoinExpressions) ) {
            foreach ( $leftJoinExpressions as $expression ) {
                $sql .= "\n  LEFT JOIN " . $expression;
            }
        }
        $sql .= "\n";

        if ( $this->where ) {
            $sql .= ' WHERE ' . $this->where;
        }

        return $sql;

    }
}

?>
