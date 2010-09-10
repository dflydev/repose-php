<?php
/**
 * UUID Property Generator
 * @package repose
 */

require_once('repose_AbstractPropertyGenerator.php');
require_once('repose_Uuid.php');

/**
 * UUID Property Generator
 * @package repose
 */
class repose_UuidPropertyGenerator extends repose_AbstractPropertyGenerator {
    public function generate(repose_Session $session, repose_MappedClass $clazz, repose_MappedClassProperty $property, $object, $newId = null) {
        return repose_Uuid::v4();
    }
    public function shouldGenerateOnAdd() { return true; }
}