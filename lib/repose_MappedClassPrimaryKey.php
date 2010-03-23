<?php
/**
 * Mapped Class Primary Key
 * @package repose
 */

/**
 * Mapped Class Primary Key
 * @package repose
 */
class repose_MappedClassPrimaryKey {

    /**
     * Properties
     * @var array
     */
    protected $properties;

    /**
     * Is this primary key a composite?
     * @var bool
     */
    protected $isComposite;

    /**
     * Constructor
     * @param array $properties Properties
     */
    public function __construct($properties = array()) {
        $this->properties = $properties;
        $this->isComposite = count($properties) > 1 ? true : false;
    }

    /**
     * The property that make up this primary key
     * @return array
     */
    public function property() {
        if ( $this->isComposite ) {
            // TODO Should this be an exception?
            return null;
        }
        return $this->properties[0];
    }

    /**
     * The properties that make up this primary key
     * @return array
     */
    public function properties() {
        return array_values($this->properties);
    }

    /**
     * Composite primary key?
     * @return bool
     */
    public function isComposite() {
        return $this->isComposite;
    }

}
?>
