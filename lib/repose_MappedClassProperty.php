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
     * Constructor
     * @param string $name Name
     * @param array $config Configuration
     */
    public function __construct($name, $config = array()) {
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

}
?>
