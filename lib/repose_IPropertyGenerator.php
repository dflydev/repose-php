<?php
/**
 * Property Generator Interface
 * @package repose
 */

/**
 * Property Generator Interface
 * @package repose
 */
interface repose_IPropertyGenerator {
    
    /**
     * Generates an ID
     * @param $session Session
     * @param $clazz Mapped Class
     * @param $property Mapped Class Property
     * @param $object Instance of class
     * @param $newId New ID if available from engine (auto increment)
     */
    public function generate(repose_Session $session, repose_MappedClass $clazz, repose_MappedClassProperty $property, $object, $newId = null);
    
    /**
     * Should generate ID when object is added to session
     * @return bool
     */
    public function shouldGenerateOnAdd();

    /**
     * Should generate ID just before object will be persisted
     * @return bool
     */
    public function shouldGenerateBeforePersist();
    
    /**
     * Should generate ID right after object has been persisted (i.e., "last insert ID")
     * @return bool
     */
    public function shouldGenerateAfterPersist();
    
}
?>