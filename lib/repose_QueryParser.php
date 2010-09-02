<?php
/**
 * Query Parser
 * @package repose
 */

/**
 * Query Parser
 * @package repose
 */
class repose_QueryParser {
    
    /**
     * Reserved SQL words
     * @var array
     */
    static protected $RESERVED_WORDS = array(
        'SELECT' => null,
        'FROM' => null,
        'JOIN' => null,
        'WHERE' => null,
        'ORDER BY' => 'ORDER\s+BY',
        'GROUP BY' => 'GROUP\s+BY',
        'HAVING' => null,
        'LIMIT' => null,
        'OFFSET' => null
    );

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
     * Raw query string
     * @var string
     */
    protected $rawQueryString;
    
    /**
     * Is the query prepared to execute?
     * @var bool
     */
    private $isPrepared = false;
    
    /**
     * Root object alias
     * @var string
     */
    private $rootObjectAlias = null;
    
    /**
     * Has the root unamed object alias been used?
     * @var unknown_type
     */
    //private $rootObjectAliasUsed = false;
    
    /**
     * Counter used to generate table aliases
     * @var int
     */
    private $tableAliasCounter = 0;
    
    /**
     * Counter used to generate column aliases
     * @var int
     */
    private $columnAliasCounter = 0;
    
    /**
     * Counter used to generate FROM entries
     * @var int
     */
    private $fromCounter = 0;
    
    /**
     * Counter used to generate SELECT entries
     * @var int
     */
    private $selectCounter = 0;
    
    /**
     * Collection of FROM entries
     * @var array
     */
    private $from = array();

    /**
     * Map from path to FROM entries
     * @var array
     */
    private $fromPath = array();

    /**
     * Collection of SELECT entries
     * @var array
     */
    private $select = array();
    
    /**
     * Map from path to SELECT entries
     * @var array
     */
    private $selectPath = array();
    
    /**
     * Collection of SELECT expressions
     * @var array
     */
    private $selectExpressions = array();

    /**
     * Collection of lazy loaded properties
     * @var array
     */
    private $lazyProperties = array();
    
    /**
     * Requested SELECT results
     * 
     * Used if the query has a custom SELECT query instead of
     * getting back just the root object(s) as the results.
     * @var string
     */
    private $selectResults = null;
    
    /**
     * Contains the WHERE clause
     * @var string
     */
    private $where = null;
    
    /**
     * Do not load anything of these classes.
     * @var array
     */
    protected $doNoLoad = array();
    
    /**
     * Do not load anything of these paths.
     * @var array
     */
    protected $skipPaths = array();
    
    /**
     * Constructor
     * @param repose_Session $session Repose Session
     * @param repose_Mapping $mapping Repose Mapping
     * @param string $queryString Query string
     * @param array $doNotLoad Classes not to load
     */
    public function __construct(repose_Session $session, repose_Mapping $mapping, $queryString, $doNotLoad = null, $skipPaths = null) {
        $this->session = $session;
        $this->mapping = $mapping;
        $this->rawQueryString = $queryString;
        $this->doNotLoad = $doNotLoad === null ? array() : $doNotLoad;
        $this->skipPaths = $skipPaths === null ? array() : $skipPaths;
    }
    
    public function rewriteObjectReferences($input) {
        $alreadyFound = array();
        if ( preg_match_all('/([\w\.\:]+)/', $input, $fields) ) {
            foreach ( $fields[1] as $field ) {
                if ( strpos($field, ':') === false ) {
                    if ( strpos($field, '.') === false ) {
                        if ( isset($alreadyFound[$field]) ) continue;
                        $objectFrom = $this->fromPath[$this->rootObjectAlias];
                        if ( $this->session->hasProperty($objectFrom['className'],$field) ) {
                            $property = $this->session->getProperty(
                                $objectFrom['className'],
                                $field
                            );
                            $input = preg_replace('/(^|[^:])' . $field . '/s', '$1' . $objectFrom['actualAlias'] . '.' . $property->columnName($this->mapping), $input);
                            $alreadyFound[$field] = true;
                            continue;
                        }   
                    }               
                    if ( preg_match('/^(.+)\.([^\.]+)$/s', $field, $fieldParts) ) {
                        
                        // Break out the matches.
                        list($dummy, $object, $propertyName) = $fieldParts;

                        $objectFrom = $this->fromPath[$object];
                        $property = $this->session->getProperty(
                            $objectFrom['className'],
                            $propertyName
                        );
                        
                        $input = preg_replace('/(^|[^:])' . $field . '/s', '$1' . $objectFrom['actualAlias'] . '.' . $property->columnName($this->mapping), $input);
                        
                    }
                }
            }
        }
        return $input;
    }
    
    /**
     * Execute the query parser
     */
    public function execute() {
        
        // Break out the various parts of the query.
        $select = $this->findChunk('SELECT');
        $from = $this->findChunk('FROM');
        $where = $this->findChunk('WHERE');
        $having = $this->findChunk('HAVING');
        $orderBy = $this->findChunk('ORDER BY');
        $groupBy = $this->findChunk('GROUP BY');
        $limit = $this->findChunk('LIMIT');
        $offset = $this->findChunk('OFFSET');
        
        foreach ( preg_split('/\s*,\s*/', $from) as $fromPart ) {
            $this->parseFrom($fromPart);
        }

        if ( $select ) {
            $this->selectResults = preg_split('/\s*,\s*/', $select);
        }
        
        $this->where = $this->rewriteObjectReferences($where);
        $this->orderBy = $this->rewriteObjectReferences($orderBy);
        $this->limit = $limit;
        $this->offset = $offset;
        
    }
    
    /**
     * Get the SQL
     * @return string
     */
    public function sql() {
        
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
                    $from['actualAlias'] .'.' . $property->foreignKey($this->mapping) .
                    ' = ' .
                    $relatedFrom['actualAlias'] . '.' . $property->columnName($this->mapping) .
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

        if ( $this->orderBy !== null ) {
            $sql .= ' ORDER BY ' . $this->orderBy;
        }
        
        if ( $this->limit !== null ) {
            $sql .= ' LIMIT ' . $this->limit;
            if ( $this->offset !== null ) {
                $sql .= ' OFFSET ' . $this->offset;
            }
        }
        
        return $sql;
        
    }
    
    /**
     * Generate results
     * @param array rows
     */
    public function generateResults($rows) {
        $results = array();
        $deferred = array();
        $lazyRequests = array();
        foreach ( $rows as $row ) {
            $objects = array();
            foreach ( $this->from as $from ) {
                $objectData = array();
                $deferredData = array();
                foreach ( $this->selectPath[$from['path']] as $selectIdx ) {
                    $select = $this->select[$selectIdx];
                    if ( $select['defer'] ) {
                        $id = $row[$select['actualAlias']];
                        if ( $id ) {
                            $deferredData[] = array(
                                'className' => $select['className'],
                                'property' => $select['propertyName'],
                                'id' => $row[$select['actualAlias']],
                            );
                        }
                    } else {
                        $objectData[$select['propertyName']]= $row[$select['actualAlias']];
                    }
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
                        'object' => $object = $this->session->addFromData(
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
                foreach ( $deferredData as $deferredHere ) {
                    if ( ! isset($deferred[$deferredHere['className']])) {
                        $deferred[$deferredHere['className']] = array();
                    }
                    if ( ! isset($deferredHere['object']) ) { $deferredHere['object'] = array(); }
                    $deferredHere['object'][] = $object;
                    $deferred[$deferredHere['className']][$deferredHere['id']] = $deferredHere;
                }
                if ( isset($this->lazyProperties[$from['path']]) ) {
                    foreach ( $this->lazyProperties[$from['path']] as $property ) {
                        if ( ! isset($lazyRequests[$property->className()]) ) {
                            $lazyRequests[$property->className()] = array();
                        }
                        if ( ! isset($lazyRequests[$property->className()][$from['path'] . '.'. $property->name()]) ) {
                            $lazyRequests[$property->className()][$from['path'] . '.' . $property->name()] = array(
                                'from' => $from,
                                'property' => $property,
                                'objects' => array(),
                            );
                        }
                        $lazyRequests[$property->className()][$from['path'] . '.' . $property->name()]['objects'][] = $object;
                    }
                }
            }
            $result = array();
            foreach ( $objects as $path => $objectInfo ) {
                
                if ( preg_match('/^(.+)\.([^\.]+)$/s', $path, $pathParts) ) {

                    // Break out the matches.
                    list($dummy, $parent, $propertyName) = $pathParts;

                    $parentObject = $objects[$parent];

                    $parentObject['object']->___repose_propertySetter(
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
        
        $localSkipPaths = $this->skipPaths;
        foreach ( $lazyRequests as $lazyClass => $targets ) {
            foreach ( $targets as $path => $target ) {
                $localSkipPaths[] = $lazyClass . "." . $target['property']->backref();
                $localSkipPaths[] = $target['from']['className'] . "." . $target['property']->name();
            }
        }
        
        foreach ( $deferred as $className => $deferredInfo ) {
            $doLookup = array();
            foreach ( $deferredInfo as $objId => $stillDeferred) {
                $object = $this->session->load(
                    $className,
                    $objId,
                    false
                );
                if ( $object === null ) {
                    $doLookup[$objId] = $stillDeferred;
                } else {
                    foreach ( $stillDeferred['object'] as $stillDeferredObject ) {
                        $stillDeferredObject->___repose_propertySetter(
                            $stillDeferred['property'],
                            $object
                        );
                    }
                }
            }
            if ( $doLookup ) {
                $primaryKey = $this->session->getPrimaryKey($className)->property()->name();
                foreach ( $this->session->find($className)->filterIn($primaryKey, array_keys($doLookup))->skipPaths($localSkipPaths)->all() as $obj ) {
                    $primaryKey = $obj->___repose_propertyGetter($primaryKey);
                    foreach ( $doLookup[$primaryKey]['object'] as $stillDeferredObj ) {
                        $stillDeferredObj->___repose_propertySetter(
                            $doLookup[$primaryKey]['property'],
                            $obj
                        );
                    }
                }
            }
        }

        foreach ( $lazyRequests as $lazyClass => $targets ) {
            foreach ( $targets as $path => $target ) {
                $objects = $this->session->find(
                    $lazyClass
                )->filterIn(
                    $target['property']->backref($this->session->mapping()),
                    $target['objects']
                )->skipPaths(
                    $localSkipPaths
                )->all();
                foreach ( $objects as $object ) {
                    $other = $object->___repose_propertyGetter($target['property']->backref($this->session->mapping()));
                    $collection = $other->___repose_propertyGetter($target['property']->name());
                    $collection[] = $object;
                    $collection->___repose_fakeIsQueried();
                }
            }
        }

        return $results;
        
    }
    
    /**
     * Parse FROM information
     * @param string $from FROM information
     * @param string $path Path to this class
     */
    protected function parseFrom($from, $path = null) {
        
        $className = $from;
        $alias = null;
        
        if ( preg_match('/^(\S+)\s+(as\s+|)(\S+|)$/is', $from, $fromActualAliasMatch) ) {
            $className = $fromActualAliasMatch[1];
            if ( $fromActualAliasMatch[3] ) {
                $alias = $fromActualAliasMatch[3];
            } else {
                $alias = $className;
            }
        } else {
            $className = $from;
            $alias = $from;
        }
        
        // TODO: Might be useful for handling non-root object references?
        //if ( ! $alias ) {
        //    if ( $this->rootObjectAliasUsed ) {
        //        $alias = $className;
        //    } else {
        //        $this->rootObjectAliasUsed = true;
        //        $alias = $this->rootObjectAlias;
        //    }
        //}
        
        $mappedClass = $this->session->getMappedClass($className);
        $primaryKey = $mappedClass->primaryKey();
        
        $fromId = $this->generateNextFromId();
        
        $fromInfo = array(
            'id' => $fromId,
            'className' => $className,
            'alias' => $alias,
            'actualAlias' => $this->generateNextTableAlias(),
            'path' => $path === null ? $alias : $path,
            'primaryKeyColumnName' => $primaryKey->property()->columnName($this->mapping),
            'primaryKeyPropertyName' => $primaryKey->property()->name(),
            'tableName' => $mappedClass->tableName(),
        );
        
        $this->from[$fromId] = $fromInfo;
        $this->fromPath[$fromInfo['path']] = $fromInfo;
        
        $this->parseSelect($fromInfo, $path);
        
        return $fromInfo;
        
    }
    
    /**
     * Parse select information
     * @param array $from FROM information
     * @param string $base Base path
     */
    protected function parseSelect($from, $base = null) {
        $config = $this->session->getMappedClass($from['className']);
        $path = $base === null ?  $from['alias'] : $base;
            
        if ( $this->rootObjectAlias === null ) {
            $this->rootObjectAlias = $path;
        }

        foreach ( $config->mappedClassProperties() as $property ) {
            $parentPath = $from['className'] . '.' . $property->name();
            if ( $property->isCollection() ) {
                if ( $this->checkForCiruclarReference($property->className(), $path) or in_array($parentPath, $this->skipPaths) ) {
                    // maybe do nothing here?
                } else {
                    if ( ! $property->isLazy() ) {
                        $this->addLazyProperty($path, $property);
                    }
                }
                // TODO Is this where we can handle non-lazy loading
                // of collections?
            } elseif ( $property->isObject() ) {
                if ( in_array($property->className(), $this->doNotLoad) ) {
                    break;
                }
                if ( $this->checkForCiruclarReference($property->className(), $path) or in_array($parentPath, $this->skipPaths) ) {
                    $this->addSelectExpression($path, $from, $property, true);
                } else {
                    $objectPath = implode('.', array($path, $property->name()));
                    $this->parseFrom($property->className(), $objectPath);
                }
            } else {
                $this->addSelectExpression($path, $from, $property);
            }
        }
    }
    
    /**
     * Keep track of lazy loaded properties
     * @param string $path
     * @param repose_MappedClassProperty $property
     */
    protected function addLazyProperty($path, $property) {
        if ( ! isset($this->lazyProperties[$path]) ) $this->lazyProperties[$path] = array();
        $this->lazyProperties[$path][$property->name()] = $property;
    }
    
    protected function addSelectExpression($path, $from, $property, $defer = null) {
        
        $selectId = $this->generateNextSelectId();
        
        $selectInfo = array(
            'id' => $selectId,
            'path' => $path,
            'actualAlias' => $this->generateNextColumnAlias(),
            'propertyName' => $property->name(),
            'defer' => $defer,
            'className' => $property->className(),
        );
        
        $this->selectExpressions[] =
            $from['actualAlias'] . '.' . $property->columnName($this->mapping) . ' AS ' . $selectInfo['actualAlias'];
            
        if ( ! isset($this->selectPath[$path]) ) {
            $this->selectPath[$path] = array();
        }
        $this->selectPath[$path][] = $selectId;
        
        $this->select[$selectId] = $selectInfo;
        
    }
    
    /**
     * Determine if the path is circular
     * 
     * If the class name exists in the calling path, this would be considered
     * a circular reference.
     * @param string $className Class name to check against
     * @param string $path Object path
     */
    protected function checkForCiruclarReference($className, $path, $max = 1) {
        
        $isCircular = false;

        $objectPathParts = explode('.', $path);
        
        $found = 0;
        
        while ( count($objectPathParts) ) {
            $testObjectPath = implode('.', $objectPathParts);
            if ( $className == $this->fromPath[$testObjectPath]['className']) {
                if ( ++$found >= $max ) {
                    $isCircular = true;
                    break;
                }
            }
            array_pop($objectPathParts);
        }
        
        return $isCircular;
        
    }
    /**
     * Find a chunk of SQL
     * @param mixed $words Reserved word(s) section
     * @param mixed $excepted Until these words are found
     */
    protected function findChunk($words, $excepted = null) {

        $regex1 = '/' . $this->reservedWords($words) . '\s*(.*?)$/i';
        $regex2 = '/^(.*?)\s*' . $this->reservedWordsExcept() . '/i';
        
        if ( preg_match($regex1, $this->rawQueryString, $matches1) ) {
            if ( preg_match($regex2, $matches1[1], $matches2) ) {
                return $matches2[1];
            }
            return $matches1[1];
        }

        return null;

    }
    
    /**
     * Create a list of reserved words based on requested words
     * @param mixed $requestedWords Words to use
     * @return array Regexes representing words
     */
    protected function reservedWords($requestedWords) {
        if ( ! is_array($requestedWords) ) $requestedWords = array($requestedWords);
        $words = array();
        foreach ( $requestedWords as $requestedWord ) {
            $words[] = self::$RESERVED_WORDS[$requestedWord] ?
                self::$RESERVED_WORDS[$requestedWord] : $requestedWord;
            
        }
        return $this->wrapReservedWords($words);
    }
    
    /**
     * Create a list of reserved words with exceptions
     * @param mixed $exceptions Do not include these words
     * @param bool $orNothing Incude '' (nothing)
     * @return array Regexes representing words
     */
    protected function reservedWordsExcept($exceptions = null, $orNothing = false) {
        if ( is_null($exceptions) ) $exceptions = array();
        if ( ! is_array($exceptions) ) $exceptions = array($exceptions);
        $words = array();
        foreach ( self::$RESERVED_WORDS as $word => $regex ) {
            if ( ! in_array($word, $exceptions) ) {
                $words[] = $regex === null ? $word : $regex;
            }
            
        }
        if ( $orNothing ) $words[] = '';
        return $this->wrapReservedWords($words);
    }

    /**
     * Wrap reserved words in a regex.
     * @param array $words Words (list of regexes)
     * @return string Regex
     */
    private function wrapReservedWords($words) {
        return '(?:\b(?:' . implode('|', $words) . ')\b)';
    }
    
    /**
     * Generate the next table alias
     */
    private function generateNextTableAlias() {
        return 'rta_t' . $this->tableAliasCounter++;
    }
    
    /**
     * Generate the next column alias
     */
    private function generateNextColumnAlias() {
        return 'c' . $this->columnAliasCounter++;
    }
    
    /**
     * Generate the next from
     */
    private function generateNextFromId() {
        return $this->fromCounter++;
    }
    
    /**
     * Generate the next select
     */
    private function generateNextSelectId() {
        return $this->selectCounter++;
    }   
}

?>