<?php
/**
 * Mapped Class Property
 * @package repose
 */

/**
 * Mapped Class Property
 * @package repose
 */
class repose_MappedClassProperty {

    /**
     * Name
     * @var string
     */
    private $name;

    /**
     * Type
     * @var string
     */
    protected $type;

    /**
     * Column name
     * @var string
     */
    protected $columnName;

    /**
     * Is a object?
     * @var bool
     */
    protected $isObject;

    /**
     * Is a collection?
     * @var bool
     */
    protected $isCollection;

    /**
     * Is primary key?
     * @var bool
     */
    protected $isPrimaryKey;
    
    /**
     * Name of generator
     * @var string
     */
    protected $generator;

    /**
     * Class name
     * @var string
     */
    protected $className;

    /**
     * Foreign key
     * @var string
     */
    protected $foreignKey;

    /**
     * Backref
     * @var string
     */
    protected $backref;

    /**
     * Cascade
     * @var string
     */
    protected $cascade;
    
    /**
     * Is collection lazy loaded?
     * @var unknown_type
     */
    protected $isLazy;

    /**
     * Constructor
     * @param string $name Name
     * @param array $config Configuration
     */
    public function __construct($name, $config = array()) {
        if ( $config and ! is_array($config) ) {
            $config = array('columnName'=>$config);
        }
        $this->name = $name;
        $this->type = isset($config['relationship']) ?
            $config['relationship'] : 'property';
        switch($this->type) {
            case 'property':
                $this->isObject = false;
                $this->isCollection = false;
                break;
            case 'many-to-one':
                $this->isObject = true;
                $this->isCollection = false;
                break;
            case 'one-to-many':
                $this->isObject = false;
                $this->isCollection = true;
                break;
        }
        $this->isPrimaryKey = isset($config['primaryKey']) ? true : false;
        if ( $this->isObject ) {
            if ( ! isset($config['className']) ) {
                throw new Exception('Object relationship must have class name specified.');
            }
            $this->className = $config['className'];
        } elseif ( $this->isCollection ) {
            if ( ! isset($config['className']) ) {
                throw new Exception('Set relationship must have class name specified.');
            }
            $this->className = $config['className'];
        } else {
            $this->className = null;
        }
        if ( isset($config['columnName']) ) {
            $this->columnName = $config['columnName'];
        }
        $this->foreignKey = isset($config['foreignKey']) ? $config['foreignKey'] : null;
        $this->backref = isset($config['backref']) ? $config['backref'] : null;
        $this->cascade = isset($config['cascade']) ? $config['cascade'] : 'none';
        if ( ! isset($config['generator']) ) {
            $config['generator'] = 'auto';
        }
        if ( is_object($config['generator']) ) {
            $this->generator = $config['generator'];
        } else {
            switch($config['generator']) {
                case 'assigned':
                case 'auto':
                case 'uuid':
                    $generatorClazz = 'repose_' . ucfirst($config['generator']) . 'PropertyGenerator';
                    require_once($generatorClazz . '.php');
                    $this->generator = new $generatorClazz();
                    break;
                default:
                    $generatorClazz = $config['generator'];
                    if ( ! class_exists($generatorClazz) ) {
                        die("Generator class $generatorClazz not loaded. Was it required?");
                    }
                    $this->generator = new $generatorClazz();
                    break;
            }
        }
        $this->isLazy = true;
        if ( isset($config['lazy']) and ! $config['lazy'] ) { $this->isLazy = false; }
    }

    /**
     * Column name
     * @param repose_Mapping $mapping Mapping
     */
    public function columnName(repose_Mapping $mapping = null) {
        return $this->deriveColumnName($mapping, 'columnName');
    }

    /**
     * Foreign key column name
     * @param repose_Mapping $mapping Mapping
     */
    public function foreignKey(repose_Mapping $mapping = null) {
        return $this->deriveColumnName($mapping, 'foreignKey');
    }

    /**
     * Name
     * @return string
     */
    public function name() {
        return $this->name;
    }

    /**
     * Type
     * @return string
     */
    public function type() {
        return $this->type;
    }

    /**
     * Is an object?
     * @return bool
     */
    public function isObject() {
        return $this->isObject;
    }

    /**
     * Is a collection?
     * @return bool
     */
    public function isCollection() {
        return $this->isCollection;
    }

    /**
     * Is a primary key?
     * @return bool
     */
    public function isPrimaryKey() {
        return $this->isPrimaryKey;
    }
    
    /**
     * Generator
     * @return string
     */
    public function generator() {
        return $this->generator;
    }

    /**
     * Class name
     * @return string
     */
    public function className() {
        return $this->className;
    }

    /**
     * Derive the column name
     * @param repose_Mapping $mapping Mapping
     * @param string $type Type of column to map (columName or foreignKey)
     */
    protected function deriveColumnName(repose_Mapping $mapping = null, $type) {
        if ( $this->$type === null ) {
            if ( $this->isObject() ) {

                $mappedClass = $mapping->mappedClass($this->className);
                $primaryKey = $mappedClass->primaryKey();

                if ( $primaryKey->isComposite() ) {
                    throw new Exception('Unable to handle composite primary key relationships.');
                } else {
                    $this->$type = $primaryKey->property()->columnName();
                }

            }
            if ( $this->$type === null ) {
                $this->$type = $this->name();
            }
        }
        return $this->$type;
    }

    /**
     * Backref
     * @param repose_Mapping $mapping Mapping
     * @return string
     */
    public function backref(repose_Mapping $mapping = null) {
        return $this->deriveColumnName($mapping, 'backref');
    }
    
    /**
     * Cascade
     * @return string
     */
    public function cascade() {
        return $this->cascade;
    }
    
    /**
     * Is colleciton lazy?
     * @return bool
     */
    public function isLazy() {
        return $this->isLazy;
    }

    /**
     * Cascade delete orphans?
     * @return bool
     */
    public function cascadeDeleteOrphan() {
        return preg_match('/delete-orphan/', $this->cascade());
    }

}
?>
