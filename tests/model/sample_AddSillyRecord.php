<?php
/**
 * Silly Record model
 * @package repose_tests
 */

require_once('lib/repose_AbstractPropertyGenerator.php');

/**
 * Silly Record model
 * @package repose_tests
 */
class sample_AddSillyRecord {

    /**
     * Record ID
     * @var string
     */
    public $recordId;
    
    /**
     * Name
     * @var string
     */
    public $name;

    /**
     * Constructor
     */
    public function __construct($name) {
        $this->name = $name;
    }
    
}

class sample_AddSillyRecordPropertyGenerator extends repose_AbstractPropertyGenerator {
    public function generate(repose_Session $session, repose_MappedClass $clazz, repose_MappedClassProperty $property, $object, $newId = null) {
        return 'ADD-SILLY-' . $object->name;
    }
    public function shouldGenerateOnAdd() { return true; }
}

?>