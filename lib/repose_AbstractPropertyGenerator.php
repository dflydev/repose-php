<?php
/**
 * Abstract Property Generator
 * @package repose
 */

require_once('repose_IPropertyGenerator.php');

/**
 * UUID Property Generator
 * @package repose
 */
abstract class repose_AbstractPropertyGenerator implements repose_IPropertyGenerator {
    public function shouldGenerateOnAdd() { return false; }
    public function shouldGenerateBeforePersist() { return false; }
    public function shouldGenerateAfterPersist() { return false; }
}
?>