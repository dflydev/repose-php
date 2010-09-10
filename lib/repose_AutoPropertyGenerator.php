<?php
/**
 * Auto Property Generator
 * @package repose
 */

require_once('repose_AbstractPropertyGenerator.php');

/**
 * Auto Property Generator
 * @package repose
 */
class repose_AutoPropertyGenerator extends repose_AbstractPropertyGenerator {
    public function generate(repose_Session $session, repose_MappedClass $clazz, repose_MappedClassProperty $property, $object, $newId = null) {
        return $newId;
    }
    public function shouldGenerateAfterPersist() { return true; }
}