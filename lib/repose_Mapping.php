<?php
/**
 * Mapping
 * @package repose
 */

require_once('repose_MappedClass.php');

/**
 * Mapping
 * @package repose
 */
class repose_Mapping {

    /**
     * Mapped classes
     * @var array
     */
    private $classes = array();

    /**
     * Add a mapped class
     * @param repose_MappedClass $mappedClass Mapped class
     */
    public function addMappedClass(repose_MappedClass $mappedClass) {
        $this->classes[$mappedClass->clazz()] = $mappedClass;
    }

    /**
     * Map a class
     * @param string $clazz Class to map
     * @param string $tableName Table name
     * @param array $properties Associative array of properties
     * @param array $config Configuration
     */
    public function mapClass($clazz, $tableName, $properties, $config = null) {
        $this->addMappedClass(new repose_MappedClass(
            $clazz, $tableName, $properties, $config
        ));
    }

    /**
     * A mapped class
     * @return repose_MappedClass
     */
    public function mappedClass($clazz) {
        return $this->classes[$clazz];
    }

    /**
     * Mapped classes
     * @return array
     */
    public function mappedClasses() {
        return array_values($this->classes);
    }

    /**
     * A mapped class property
     * @return repose_MappedClassProperty
     */
    public function mappedClassProperty($clazz, $property) {
        return $this->classes[$clazz]->mappedClassProperty($property);
    }

    /**
     * Mapped class properties
     * @return array
     */
    public function mappedClassProperties($clazz) {
        return $this->classes[$clazz]->mappedClassProperties();
    }

    /**
     * Mapped class property name
     * @return array
     */
    public function mappedClassPropertyNames($clazz) {
        return $this->classes[$clazz]->mappedClassPropertyNames();
    }

}

?>
