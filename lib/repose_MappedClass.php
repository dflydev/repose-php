<?php
/**
 * Mapped Class
 * @package repose
 */

require_once('repose_MappedClassProperty.php');
require_once('repose_MappedClassPrimaryKey.php');

/**
 * Mapped Class
 * @package repose
 */
class repose_MappedClass {

    /**
     * Mapped class name
     * @var string
     */
    private $clazz;

    /**
     * Table name
     * @var string
     */
    private $tableName;

    /**
     * Properties
     * @var array
     */
    private $properties;

    /**
     * Primary key
     * @var repose_MappedClassPrimaryKey
     */
    private $primaryKey;

    /**
     * Configuration
     * @var array
     */
    private $config;

    /**
     * Constructor
     * @param string $clazz Class to map
     * @param string $tableName Table name
     * @param array $properties Associative array of properties
     * @param array $config Configuration
     */
    public function __construct($clazz, $tableName, $properties = null, $config = null) {
        if ( $config === null ) $config = array();
        $this->clazz = $clazz;
        $this->tableName = $tableName;
        $this->config = $config;
        $this->mapClassProperties($properties);
        $primaryKeyProperties = array();
        foreach ( $this->mappedClassProperties() as $mappedProperty ) {
            if ( $mappedProperty->isPrimaryKey() ) {
                $primaryKeyProperties[] = $mappedProperty;
            }
        }
        if ( count($primaryKeyProperties) > 0 ) {
            $this->primaryKey = new repose_MappedClassPrimaryKey(
                $primaryKeyProperties
            );
        } else {
            $this->primaryKey = null;
        }
    }

    /**
     * Add a mapped class property
     * @param repose_MappedClassProperty $mappedClassProperty Mapped class property
     */
    public function addMappedClassProperty(repose_MappedClassProperty $mappedClassProperty) {
        $this->properties[$mappedClassProperty->name()] = $mappedClassProperty;
    }

    /**
     * Map a class property
     * @param string $propertyName Property name
     * @param array $propertyConfiguration Property configuration
     */
    public function mapClassProperty($propertyName, $propertyConfig = null) {
        $this->addMappedClassProperty(new repose_MappedClassProperty(
            $propertyName, $propertyConfig
        ));
    }

    /**
     * Map multiple class properties
     * @param array $properties Associative array of properties to map
     */
    public function mapClassProperties($properties = null) {
        foreach ( $properties as $propertyName => $propertyConfig ) {
            $this->mapClassProperty($propertyName, $propertyConfig);
        }
    }

    /**
     * A mapped class property
     * @return repose_MappedClassProperty
     */
    public function mappedClassProperty($property) {
        return $this->properties[$property];
    }

    /**
     * Mapped class properties
     * @return array
     */
    public function mappedClassProperties() {
        return array_values($this->properties);
    }

    /**
     * Mapped class property names
     * @return array
     */
    public function mappedClassPropertyNames() {
        return array_keys($this->properties);
    }

    /**
     * Class name
     * @return string
     */
    public function clazz() {
        return $this->clazz;
    }

    /**
     * Table name
     * @return string
     */
    public function tableName() {
        return $this->tableName;
    }

    /**
     * Configuration
     * @return array
     */
    public function config() {
        return $this->config;
    }

    /**
     * Primary key
     * @return repose_MappedClassPrimaryKey
     */
    public function primaryKey() {
        return $this->primaryKey;
    }

}

?>
